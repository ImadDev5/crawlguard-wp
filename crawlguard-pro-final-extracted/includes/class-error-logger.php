<?php
/**
 * Error Logger with Remote Monitoring
 * 
 * Comprehensive logging system for production debugging
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Error_Logger {
    
    const LOG_LEVEL_DEBUG = 'debug';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_CRITICAL = 'critical';
    
    private $log_file;
    private $max_file_size = 10485760; // 10MB
    private $remote_logging_enabled = false;
    private $remote_endpoint;
    private $site_identifier;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/crawlguard-logs';
        
        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Protect directory with .htaccess
            $htaccess_content = "Deny from all\n";
            file_put_contents($log_dir . '/.htaccess', $htaccess_content);
        }
        
        $this->log_file = $log_dir . '/crawlguard-' . date('Y-m-d') . '.log';
        $this->site_identifier = get_option('crawlguard_site_id', md5(get_site_url()));
        
        // Check if remote logging is enabled
        $options = get_option('crawlguard_options', []);
        if (!empty($options['remote_logging_enabled'])) {
            $this->remote_logging_enabled = true;
            $this->remote_endpoint = 'https://monitoring.crawlguard.com/api/logs';
        }
        
        // Set up WordPress error handler integration
        add_action('wp_error_added', [$this, 'handle_wp_error'], 10, 4);
        add_action('shutdown', [$this, 'check_for_fatal']);
    }
    
    /**
     * Log a message with context
     */
    public function log($message, $level = self::LOG_LEVEL_INFO, $context = []) {
        $timestamp = current_time('c');
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($backtrace[1]) ? $backtrace[1] : ['file' => 'unknown', 'line' => 0];
        
        $log_entry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'file' => str_replace(ABSPATH, '', $caller['file']),
            'line' => $caller['line'],
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $this->get_client_ip()
        ];
        
        // Write to local file
        $this->write_to_file($log_entry);
        
        // Send to remote monitoring if enabled and error is significant
        if ($this->should_send_remote($level)) {
            $this->send_to_remote($log_entry);
        }
        
        // Also log to WordPress debug.log if WP_DEBUG_LOG is enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[CrawlGuard] ' . $level . ': ' . $message);
        }
    }
    
    /**
     * Log debug information
     */
    public function debug($message, $context = []) {
        $this->log($message, self::LOG_LEVEL_DEBUG, $context);
    }
    
    /**
     * Log informational messages
     */
    public function info($message, $context = []) {
        $this->log($message, self::LOG_LEVEL_INFO, $context);
    }
    
    /**
     * Log warnings
     */
    public function warning($message, $context = []) {
        $this->log($message, self::LOG_LEVEL_WARNING, $context);
    }
    
    /**
     * Log errors
     */
    public function error($message, $context = []) {
        $this->log($message, self::LOG_LEVEL_ERROR, $context);
    }
    
    /**
     * Log critical errors
     */
    public function critical($message, $context = []) {
        $this->log($message, self::LOG_LEVEL_CRITICAL, $context);
        
        // Send immediate alert for critical errors
        $this->send_critical_alert($message, $context);
    }
    
    /**
     * Write log entry to file
     */
    private function write_to_file($log_entry) {
        // Check file size and rotate if necessary
        if (file_exists($this->log_file) && filesize($this->log_file) > $this->max_file_size) {
            $this->rotate_log_file();
        }
        
        $formatted_entry = json_encode($log_entry) . "\n";
        
        // Use file locking to prevent concurrent write issues
        $fp = fopen($this->log_file, 'a');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                fwrite($fp, $formatted_entry);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }
    
    /**
     * Rotate log files
     */
    private function rotate_log_file() {
        $timestamp = date('Y-m-d-His');
        $rotated_file = str_replace('.log', '-' . $timestamp . '.log', $this->log_file);
        rename($this->log_file, $rotated_file);
        
        // Compress old log file
        $this->compress_log_file($rotated_file);
        
        // Clean up old logs (keep last 30 days)
        $this->cleanup_old_logs();
    }
    
    /**
     * Compress log file using gzip
     */
    private function compress_log_file($file) {
        if (function_exists('gzopen')) {
            $gz_file = $file . '.gz';
            $fp = fopen($file, 'rb');
            $gz = gzopen($gz_file, 'wb9');
            
            if ($fp && $gz) {
                while (!feof($fp)) {
                    gzwrite($gz, fread($fp, 1024 * 512));
                }
                fclose($fp);
                gzclose($gz);
                unlink($file); // Remove uncompressed file
            }
        }
    }
    
    /**
     * Clean up old log files
     */
    private function cleanup_old_logs() {
        $log_dir = dirname($this->log_file);
        $files = glob($log_dir . '/crawlguard-*.log*');
        $now = time();
        
        foreach ($files as $file) {
            if ($now - filemtime($file) > 30 * 24 * 60 * 60) { // 30 days
                unlink($file);
            }
        }
    }
    
    /**
     * Determine if error should be sent to remote monitoring
     */
    private function should_send_remote($level) {
        if (!$this->remote_logging_enabled) {
            return false;
        }
        
        $levels_to_send = [
            self::LOG_LEVEL_WARNING,
            self::LOG_LEVEL_ERROR,
            self::LOG_LEVEL_CRITICAL
        ];
        
        return in_array($level, $levels_to_send);
    }
    
    /**
     * Send log entry to remote monitoring service
     */
    private function send_to_remote($log_entry) {
        $payload = [
            'site_id' => $this->site_identifier,
            'site_url' => get_site_url(),
            'plugin_version' => CRAWLGUARD_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'log_entry' => $log_entry
        ];
        
        wp_remote_post($this->remote_endpoint, [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json',
                'X-CrawlGuard-Site' => $this->site_identifier
            ],
            'timeout' => 5,
            'blocking' => false // Don't wait for response
        ]);
    }
    
    /**
     * Send critical alert
     */
    private function send_critical_alert($message, $context) {
        // Send email to admin
        $admin_email = get_option('admin_email');
        $subject = '[CrawlGuard Critical] ' . substr($message, 0, 50);
        $body = "Critical error detected on " . get_site_url() . "\n\n";
        $body .= "Message: " . $message . "\n\n";
        $body .= "Context: " . print_r($context, true);
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * Handle WordPress errors
     */
    public function handle_wp_error($code, $message, $data, $wp_error) {
        $this->error('WordPress Error: ' . $message, [
            'code' => $code,
            'data' => $data
        ]);
    }
    
    /**
     * Check for fatal errors on shutdown
     */
    public function check_for_fatal() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->critical('Fatal Error', [
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Get logs for display in admin
     */
    public function get_recent_logs($limit = 100, $level = null) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $logs = [];
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse($lines); // Most recent first
        
        foreach ($lines as $line) {
            $log_entry = json_decode($line, true);
            
            if ($log_entry) {
                if ($level === null || $log_entry['level'] === $level) {
                    $logs[] = $log_entry;
                    
                    if (count($logs) >= $limit) {
                        break;
                    }
                }
            }
        }
        
        return $logs;
    }
}
