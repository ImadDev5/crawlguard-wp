<?php
/**
 * Enterprise Error Handler for PayPerCrawl
 * 
 * Comprehensive error logging, recovery, and debugging system
 * 
 * @package PayPerCrawl_Enterprise
 * @subpackage ErrorHandling
 * @version 6.0.0
 * @author PayPerCrawl.tech
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Enterprise Error Handler with recovery mechanisms
 */
class PayPerCrawl_Error_Handler {
    
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Maximum log entries to keep
     */
    const MAX_LOG_ENTRIES = 1000;
    
    /**
     * Error handler instance
     */
    private static $instance = null;
    
    /**
     * Debug mode
     */
    private $debug_mode;
    
    /**
     * Log file path
     */
    private $log_file;
    
    /**
     * Error counts
     */
    private $error_counts = array();
    
    /**
     * Initialize error handler
     */
    public function __construct() {
        $this->debug_mode = get_option('paypercrawl_debug_mode', false);
        $this->log_file = WP_CONTENT_DIR . '/paypercrawl-error.log';
        
        $this->init_error_handling();
        $this->register_shutdown_handler();
    }
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize error handling
     */
    private function init_error_handling() {
        // Set custom error handler for PayPerCrawl errors
        set_error_handler(array($this, 'handle_php_error'), E_ALL);
        
        // Register exception handler
        set_exception_handler(array($this, 'handle_exception'));
        
        // Hook into WordPress error handling
        add_action('wp_die_handler', array($this, 'handle_wp_die'));
    }
    
    /**
     * Register shutdown handler for fatal errors
     */
    private function register_shutdown_handler() {
        register_shutdown_function(array($this, 'handle_shutdown'));
    }
    
    /**
     * Log error with context
     */
    public function log($level, $message, $context = array(), $source = '') {
        if (!in_array($level, array(
            self::LEVEL_DEBUG, self::LEVEL_INFO, self::LEVEL_WARNING, 
            self::LEVEL_ERROR, self::LEVEL_CRITICAL
        ))) {
            $level = self::LEVEL_ERROR;
        }
        
        // Skip debug messages unless debug mode is enabled
        if ($level === self::LEVEL_DEBUG && !$this->debug_mode) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'source' => $source ?: $this->get_calling_function(),
            'user_id' => get_current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $this->get_client_ip(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        );
        
        // Log to database
        $this->log_to_database($log_entry);
        
        // Log to file if debug mode
        if ($this->debug_mode) {
            $this->log_to_file($log_entry);
        }
        
        // Log critical errors to WordPress error log
        if (in_array($level, array(self::LEVEL_ERROR, self::LEVEL_CRITICAL))) {
            error_log(\"PayPerCrawl [{$level}] {$source}: {$message}\");
        }
        
        // Update error counts
        $this->update_error_counts($level);
        
        // Trigger recovery mechanisms for critical errors
        if ($level === self::LEVEL_CRITICAL) {
            $this->trigger_recovery($message, $context);
        }
    }
    
    /**
     * Log to database
     */
    private function log_to_database($log_entry) {
        global $wpdb;
        
        try {
            $logs_table = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
            
            $wpdb->insert(
                $logs_table,
                array(
                    'level' => $log_entry['level'],
                    'message' => $log_entry['message'],
                    'context' => wp_json_encode($log_entry['context']),
                    'source' => $log_entry['source'],
                    'created_at' => $log_entry['timestamp']
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
            
            // Clean up old logs
            $this->cleanup_old_logs();
            
        } catch (Exception $e) {
            // Fallback to error_log if database logging fails
            error_log('PayPerCrawl Error Handler Failed: ' . $e->getMessage());
            error_log(\"Original Error [{$log_entry['level']}] {$log_entry['source']}: {$log_entry['message']}\");
        }
    }
    
    /**
     * Log to file
     */
    private function log_to_file($log_entry) {
        $log_line = sprintf(
            \"[%s] %s %s: %s %s\" . PHP_EOL,
            $log_entry['timestamp'],
            strtoupper($log_entry['level']),
            $log_entry['source'],
            $log_entry['message'],
            !empty($log_entry['context']) ? '| Context: ' . wp_json_encode($log_entry['context']) : ''
        );
        
        file_put_contents($this->log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        // Rotate log file if it gets too large (10MB limit)
        if (file_exists($this->log_file) && filesize($this->log_file) > 10 * 1024 * 1024) {
            $this->rotate_log_file();
        }
    }
    
    /**
     * Handle PHP errors
     */
    public function handle_php_error($errno, $errstr, $errfile, $errline) {
        // Only handle PayPerCrawl related errors
        if (strpos($errfile, 'paypercrawl') === false) {
            return false; // Let WordPress handle it
        }
        
        $error_type = $this->get_error_type_name($errno);
        $level = $this->get_log_level_from_error_type($errno);
        
        $this->log($level, $errstr, array(
            'file' => $errfile,
            'line' => $errline,
            'type' => $error_type,
            'errno' => $errno
        ), 'PHP Error Handler');
        
        // Don't prevent WordPress from handling the error
        return false;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public function handle_exception($exception) {
        $this->log(self::LEVEL_CRITICAL, $exception->getMessage(), array(
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode()
        ), 'Exception Handler');
    }
    
    /**
     * Handle WordPress die
     */
    public function handle_wp_die($message, $title = '', $args = array()) {
        if (is_string($message) && strpos($message, 'paypercrawl') !== false) {
            $this->log(self::LEVEL_CRITICAL, $message, array(
                'title' => $title,
                'args' => $args
            ), 'WordPress Die Handler');
        }
    }
    
    /**
     * Handle shutdown errors (fatal errors)
     */
    public function handle_shutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            // Only handle PayPerCrawl related fatal errors
            if (strpos($error['file'], 'paypercrawl') !== false) {
                $this->log(self::LEVEL_CRITICAL, $error['message'], array(
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => $this->get_error_type_name($error['type'])
                ), 'Shutdown Handler');
                
                // Attempt emergency recovery
                $this->emergency_recovery();
            }
        }
    }
    
    /**
     * Get error type name
     */
    private function get_error_type_name($errno) {
        $error_types = array(
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        );
        
        return $error_types[$errno] ?? 'Unknown Error';
    }
    
    /**
     * Get log level from error type
     */
    private function get_log_level_from_error_type($errno) {
        $critical_errors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        $warning_errors = array(E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING);
        $notice_errors = array(E_NOTICE, E_USER_NOTICE, E_STRICT, E_DEPRECATED, E_USER_DEPRECATED);
        
        if (in_array($errno, $critical_errors)) {
            return self::LEVEL_CRITICAL;
        } elseif (in_array($errno, $warning_errors)) {
            return self::LEVEL_WARNING;
        } elseif (in_array($errno, $notice_errors)) {
            return self::LEVEL_INFO;
        }
        
        return self::LEVEL_ERROR;
    }
    
    /**
     * Get calling function
     */
    private function get_calling_function() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        
        // Skip error handler functions
        foreach ($trace as $frame) {
            if (isset($frame['class']) && $frame['class'] !== __CLASS__) {
                return ($frame['class'] ?? '') . '::' . ($frame['function'] ?? 'unknown');
            } elseif (!isset($frame['class']) && !in_array($frame['function'], array('log', 'handle_php_error'))) {
                return $frame['function'] ?? 'unknown';
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Update error counts
     */
    private function update_error_counts($level) {
        if (!isset($this->error_counts[$level])) {
            $this->error_counts[$level] = 0;
        }
        
        $this->error_counts[$level]++;
        
        // Store in transient for dashboard display
        $counts = get_transient('paypercrawl_error_counts') ?: array();
        $counts[$level] = ($counts[$level] ?? 0) + 1;
        set_transient('paypercrawl_error_counts', $counts, HOUR_IN_SECONDS);
    }
    
    /**
     * Trigger recovery mechanisms
     */
    private function trigger_recovery($message, $context) {
        // Disable plugin temporarily if too many critical errors
        if ($this->error_counts[self::LEVEL_CRITICAL] >= 5) {
            $this->emergency_recovery();
        }
        
        // Send alert email to admin
        if (get_option('paypercrawl_alert_emails', true)) {
            $this->send_alert_email($message, $context);
        }
        
        // Log to external monitoring service
        $this->log_to_external_service($message, $context);
    }
    
    /**
     * Emergency recovery
     */
    private function emergency_recovery() {
        // Disable bot detection to prevent further errors
        update_option('paypercrawl_detection_enabled', false);
        update_option('paypercrawl_emergency_mode', true);
        update_option('paypercrawl_emergency_triggered_at', current_time('timestamp'));
        
        $this->log(self::LEVEL_CRITICAL, 'Emergency recovery triggered - plugin disabled', array(
            'error_counts' => $this->error_counts
        ), 'Emergency Recovery');
    }
    
    /**
     * Send alert email
     */
    private function send_alert_email($message, $context) {
        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }
        
        $subject = '[PayPerCrawl] Critical Error Alert - ' . get_bloginfo('name');
        
        $body = \"A critical error occurred in PayPerCrawl Enterprise:\\n\\n\";
        $body .= \"Error: {$message}\\n\\n\";
        $body .= \"Context: \" . wp_json_encode($context, JSON_PRETTY_PRINT) . \"\\n\\n\";
        $body .= \"Site: \" . get_site_url() . \"\\n\";
        $body .= \"Time: \" . current_time('mysql') . \"\\n\\n\";
        $body .= \"Please check your PayPerCrawl logs for more details.\";
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * Log to external monitoring service
     */
    private function log_to_external_service($message, $context) {
        // Implementation would depend on chosen monitoring service
        // Example: Sentry, Bugsnag, etc.
        
        $payload = array(
            'message' => $message,
            'context' => $context,
            'site_url' => get_site_url(),
            'plugin_version' => PAYPERCRAWL_ENTERPRISE_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'timestamp' => current_time('timestamp')
        );
        
        // Send to monitoring service API
        wp_remote_post('https://monitoring.paypercrawl.tech/api/errors', array(
            'body' => wp_json_encode($payload),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 5
        ));
    }
    
    /**
     * Clean up old logs
     */
    private function cleanup_old_logs() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
        
        // Keep only the most recent MAX_LOG_ENTRIES
        $wpdb->query($wpdb->prepare(
            \"DELETE FROM {$logs_table} WHERE id NOT IN (
                SELECT id FROM (
                    SELECT id FROM {$logs_table} ORDER BY created_at DESC LIMIT %d
                ) AS recent_logs
            )\",
            self::MAX_LOG_ENTRIES
        ));
    }
    
    /**
     * Rotate log file
     */
    private function rotate_log_file() {
        if (file_exists($this->log_file)) {
            $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s');
            rename($this->log_file, $backup_file);
            
            // Compress old log file
            if (function_exists('gzopen')) {
                $this->compress_log_file($backup_file);
            }
        }
    }
    
    /**
     * Compress log file
     */
    private function compress_log_file($file) {
        $gz_file = $file . '.gz';
        $fp_in = fopen($file, 'rb');
        $fp_out = gzopen($gz_file, 'wb9');
        
        if ($fp_in && $fp_out) {
            while (!feof($fp_in)) {
                gzwrite($fp_out, fread($fp_in, 1024));
            }
            
            fclose($fp_in);
            gzclose($fp_out);
            
            // Remove original file
            unlink($file);
        }
    }
    
    /**
     * Get error statistics
     */
    public function get_error_stats($days = 7) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
        
        $stats = $wpdb->get_results($wpdb->prepare(
            \"SELECT level, COUNT(*) as count, DATE(created_at) as date
             FROM {$logs_table}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY level, DATE(created_at)
             ORDER BY date DESC, level\",
            $days
        ), ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Get recent errors
     */
    public function get_recent_errors($limit = 50) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
        
        return $wpdb->get_results($wpdb->prepare(
            \"SELECT * FROM {$logs_table}
             ORDER BY created_at DESC
             LIMIT %d\",
            $limit
        ), ARRAY_A);
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
        $wpdb->query(\"TRUNCATE TABLE {$logs_table}\");
        
        // Clear log file
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
        
        // Clear error counts
        delete_transient('paypercrawl_error_counts');
        
        return true;
    }
    
    /**
     * Check if emergency mode is active
     */
    public function is_emergency_mode() {
        return get_option('paypercrawl_emergency_mode', false);
    }
    
    /**
     * Exit emergency mode
     */
    public function exit_emergency_mode() {
        delete_option('paypercrawl_emergency_mode');
        delete_option('paypercrawl_emergency_triggered_at');
        update_option('paypercrawl_detection_enabled', true);
        
        $this->log(self::LEVEL_INFO, 'Emergency mode disabled - plugin re-enabled', array(), 'Emergency Recovery');
        
        return true;
    }
    
    /**
     * Get system diagnostics
     */
    public function get_diagnostics() {
        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => PAYPERCRAWL_ENTERPRISE_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'debug_mode' => $this->debug_mode,
            'emergency_mode' => $this->is_emergency_mode(),
            'error_counts' => get_transient('paypercrawl_error_counts') ?: array(),
            'last_error' => get_option('paypercrawl_last_error', ''),
            'log_file_size' => file_exists($this->log_file) ? filesize($this->log_file) : 0
        );
    }
}
