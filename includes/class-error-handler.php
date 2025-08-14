<?php
/**
 * Error Handler for PayPerCrawl Enterprise
 * 
 * @package PayPerCrawl
 * @subpackage ErrorHandling
 * @version 4.0.0
 * @author PayPerCrawl.tech
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Enterprise Error Handler
 * 
 * Features:
 * - Comprehensive error logging
 * - Error recovery mechanisms
 * - Performance monitoring
 * - Debug information collection
 * 
 * @since 4.0.0
 */
class PayPerCrawl_Error_Handler {
    
    /**
     * Error log file path
     * @var string
     */
    private $log_file;
    
    /**
     * Error statistics
     * @var array
     */
    private $error_stats = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_error_handler();
        $this->setup_log_file();
        $this->load_error_stats();
    }
    
    /**
     * Initialize error handler
     */
    private function init_error_handler() {
        set_error_handler([$this, 'handle_error'], E_ALL);
        set_exception_handler([$this, 'handle_exception']);
        register_shutdown_function([$this, 'handle_fatal_error']);
    }
    
    /**
     * Setup log file
     */
    private function setup_log_file() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/paypercrawl/logs/';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $this->log_file = $log_dir . 'paypercrawl-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Load error statistics
     */
    private function load_error_stats() {
        $this->error_stats = get_option('paypercrawl_error_stats', [
            'total_errors' => 0,
            'fatal_errors' => 0,
            'warnings' => 0,
            'notices' => 0,
            'last_error' => null,
            'error_trends' => [],
        ]);
    }
    
    /**
     * Handle PHP errors
     */
    public function handle_error($errno, $errstr, $errfile, $errline) {
        // Only handle PayPerCrawl related errors
        if (strpos($errfile, 'paypercrawl') === false && strpos($errfile, 'PayPerCrawl') === false) {
            return false;
        }
        
        $error_types = [
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
            E_USER_DEPRECATED => 'User Deprecated',
        ];
        
        $error_type = $error_types[$errno] ?? 'Unknown Error';
        
        $error_data = [
            'type' => $error_type,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'timestamp' => current_time('mysql'),
            'severity' => $this->get_error_severity($errno),
            'context' => $this->get_error_context(),
        ];
        
        $this->log_error($error_data);
        $this->update_error_stats($error_data);
        
        // Continue with normal error handling
        return false;
    }
    
    /**
     * Handle exceptions
     */
    public function handle_exception($exception) {
        $error_data = [
            'type' => 'Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'timestamp' => current_time('mysql'),
            'severity' => 'critical',
            'trace' => $exception->getTraceAsString(),
            'context' => $this->get_error_context(),
        ];
        
        $this->log_error($error_data);
        $this->update_error_stats($error_data);
        
        // Attempt graceful degradation
        $this->attempt_recovery($error_data);
    }
    
    /**
     * Handle fatal errors
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if ($error && $error['type'] === E_ERROR) {
            // Only handle PayPerCrawl related fatal errors
            if (strpos($error['file'], 'paypercrawl') !== false || strpos($error['file'], 'PayPerCrawl') !== false) {
                $error_data = [
                    'type' => 'Fatal Error',
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'timestamp' => current_time('mysql'),
                    'severity' => 'critical',
                    'context' => $this->get_error_context(),
                ];
                
                $this->log_error($error_data);
                $this->update_error_stats($error_data);
                
                // Emergency mode activation
                $this->activate_emergency_mode($error_data);
            }
        }
    }
    
    /**
     * Log error to file and database
     */
    private function log_error($error_data) {
        // Log to file
        $log_entry = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            $error_data['timestamp'],
            $error_data['type'],
            $error_data['message'],
            $error_data['file'],
            $error_data['line']
        );
        
        if (isset($error_data['trace'])) {
            $log_entry .= "Stack trace:\n" . $error_data['trace'] . "\n";
        }
        
        $log_entry .= "Context: " . json_encode($error_data['context']) . "\n\n";
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PayPerCrawl] ' . $error_data['type'] . ': ' . $error_data['message']);
        }
        
        // Store last error for admin notice
        update_option('paypercrawl_last_error', $error_data);
    }
    
    /**
     * Update error statistics
     */
    private function update_error_stats($error_data) {
        $this->error_stats['total_errors']++;
        
        switch ($error_data['severity']) {
            case 'critical':
                $this->error_stats['fatal_errors']++;
                break;
            case 'warning':
                $this->error_stats['warnings']++;
                break;
            case 'notice':
                $this->error_stats['notices']++;
                break;
        }
        
        $this->error_stats['last_error'] = $error_data;
        
        // Update daily trend
        $today = current_time('Y-m-d');
        if (!isset($this->error_stats['error_trends'][$today])) {
            $this->error_stats['error_trends'][$today] = 0;
        }
        $this->error_stats['error_trends'][$today]++;
        
        // Keep only last 30 days of trends
        $this->error_stats['error_trends'] = array_slice(
            $this->error_stats['error_trends'], 
            -30, 
            null, 
            true
        );
        
        update_option('paypercrawl_error_stats', $this->error_stats);
    }
    
    /**
     * Get error severity level
     */
    private function get_error_severity($errno) {
        switch ($errno) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                return 'critical';
                
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'warning';
                
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'notice';
                
            default:
                return 'unknown';
        }
    }
    
    /**
     * Get error context information
     */
    private function get_error_context() {
        return [
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => PAYPERCRAWL_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'time_limit' => ini_get('max_execution_time'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'is_admin' => is_admin(),
            'current_user' => get_current_user_id(),
            'active_plugins' => get_option('active_plugins', []),
        ];
    }
    
    /**
     * Attempt error recovery
     */
    private function attempt_recovery($error_data) {
        // Based on error type, attempt different recovery strategies
        
        if (strpos($error_data['message'], 'database') !== false) {
            $this->attempt_database_recovery();
        }
        
        if (strpos($error_data['message'], 'memory') !== false) {
            $this->attempt_memory_recovery();
        }
        
        if (strpos($error_data['message'], 'timeout') !== false) {
            $this->attempt_timeout_recovery();
        }
        
        // Generic recovery: disable non-essential features
        $this->disable_non_essential_features();
    }
    
    /**
     * Attempt database recovery
     */
    private function attempt_database_recovery() {
        global $wpdb;
        
        try {
            // Check database connection
            $wpdb->check_connection();
            
            // Repair tables if needed
            $this->repair_plugin_tables();
            
            $this->log_recovery_attempt('Database recovery attempted');
            
        } catch (Exception $e) {
            $this->log_recovery_attempt('Database recovery failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Attempt memory recovery
     */
    private function attempt_memory_recovery() {
        // Clear caches
        wp_cache_flush();
        delete_transient('paypercrawl_dashboard_cache');
        delete_transient('paypercrawl_dashboard_stats');
        
        // Disable analytics temporarily
        update_option('paypercrawl_analytics_enabled', false);
        
        $this->log_recovery_attempt('Memory recovery attempted - caches cleared');
    }
    
    /**
     * Attempt timeout recovery
     */
    private function attempt_timeout_recovery() {
        // Increase time limit if possible
        if (function_exists('set_time_limit')) {
            set_time_limit(300);
        }
        
        // Disable heavy operations
        update_option('paypercrawl_ml_detection_enabled', false);
        
        $this->log_recovery_attempt('Timeout recovery attempted - time limit increased');
    }
    
    /**
     * Disable non-essential features
     */
    private function disable_non_essential_features() {
        $emergency_options = [
            'paypercrawl_realtime_updates' => false,
            'paypercrawl_cloudflare_enabled' => false,
            'paypercrawl_ml_detection_enabled' => false,
        ];
        
        foreach ($emergency_options as $option => $value) {
            update_option($option, $value);
        }
        
        $this->log_recovery_attempt('Emergency mode activated - non-essential features disabled');
    }
    
    /**
     * Activate emergency mode
     */
    private function activate_emergency_mode($error_data) {
        update_option('paypercrawl_emergency_mode', true);
        update_option('paypercrawl_emergency_mode_reason', $error_data);
        
        // Disable all advanced features
        $this->disable_non_essential_features();
        
        // Send notification if configured
        $this->send_error_notification($error_data);
        
        $this->log_recovery_attempt('Emergency mode activated due to fatal error');
    }
    
    /**
     * Repair plugin tables
     */
    private function repair_plugin_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'paypercrawl_detections',
            $wpdb->prefix . 'paypercrawl_analytics',
            $wpdb->prefix . 'paypercrawl_config',
            $wpdb->prefix . 'paypercrawl_requests',
        ];
        
        foreach ($tables as $table) {
            $result = $wpdb->query("REPAIR TABLE {$table}");
            if ($result === false) {
                $this->log_recovery_attempt("Failed to repair table: {$table}");
            }
        }
    }
    
    /**
     * Send error notification
     */
    private function send_error_notification($error_data) {
        $notification_email = get_option('paypercrawl_notification_email', get_option('admin_email'));
        
        if (empty($notification_email)) {
            return;
        }
        
        $subject = 'PayPerCrawl Enterprise - Critical Error Detected';
        
        $message = "A critical error has been detected in PayPerCrawl Enterprise:\n\n";
        $message .= "Error Type: {$error_data['type']}\n";
        $message .= "Message: {$error_data['message']}\n";
        $message .= "File: {$error_data['file']}\n";
        $message .= "Line: {$error_data['line']}\n";
        $message .= "Time: {$error_data['timestamp']}\n\n";
        $message .= "Emergency mode has been activated to maintain system stability.\n";
        $message .= "Please check your WordPress admin dashboard for more details.\n\n";
        $message .= "Site: " . get_site_url() . "\n";
        
        wp_mail($notification_email, $subject, $message);
    }
    
    /**
     * Log recovery attempt
     */
    private function log_recovery_attempt($message) {
        $log_entry = sprintf(
            "[%s] RECOVERY: %s\n",
            current_time('mysql'),
            $message
        );
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PayPerCrawl Recovery] ' . $message);
        }
    }
    
    /**
     * Get error statistics
     */
    public function get_error_stats() {
        return $this->error_stats;
    }
    
    /**
     * Get recent errors
     */
    public function get_recent_errors($limit = 10) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recent_errors = [];
        
        // Parse log file (simplified)
        foreach (array_reverse(array_slice($lines, -($limit * 4))) as $line) {
            if (preg_match('/^\[([\d\-\s:]+)\]\s+([^:]+):\s+(.+)/', $line, $matches)) {
                $recent_errors[] = [
                    'timestamp' => $matches[1],
                    'type' => trim($matches[2]),
                    'message' => trim($matches[3]),
                ];
                
                if (count($recent_errors) >= $limit) {
                    break;
                }
            }
        }
        
        return array_reverse($recent_errors);
    }
    
    /**
     * Clear error log
     */
    public function clear_error_log() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
        
        // Reset error statistics
        $this->error_stats = [
            'total_errors' => 0,
            'fatal_errors' => 0,
            'warnings' => 0,
            'notices' => 0,
            'last_error' => null,
            'error_trends' => [],
        ];
        
        update_option('paypercrawl_error_stats', $this->error_stats);
        delete_option('paypercrawl_last_error');
    }
    
    /**
     * Check if system is in emergency mode
     */
    public function is_emergency_mode() {
        return get_option('paypercrawl_emergency_mode', false);
    }
    
    /**
     * Exit emergency mode
     */
    public function exit_emergency_mode() {
        delete_option('paypercrawl_emergency_mode');
        delete_option('paypercrawl_emergency_mode_reason');
        
        // Re-enable features (user should configure manually)
        $this->log_recovery_attempt('Emergency mode deactivated manually');
    }
    
    /**
     * Get system health status
     */
    public function get_health_status() {
        $status = [
            'overall' => 'good',
            'issues' => [],
            'recommendations' => [],
        ];
        
        // Check error rates
        $error_rate = $this->calculate_error_rate();
        if ($error_rate > 0.1) {
            $status['overall'] = 'warning';
            $status['issues'][] = "High error rate detected: {$error_rate}%";
            $status['recommendations'][] = 'Review error logs and consider updating plugin';
        }
        
        // Check memory usage
        $memory_usage = memory_get_usage(true);
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $memory_percent = ($memory_usage / $memory_limit) * 100;
        
        if ($memory_percent > 90) {
            $status['overall'] = 'critical';
            $status['issues'][] = "High memory usage: {$memory_percent}%";
            $status['recommendations'][] = 'Increase PHP memory limit or optimize plugin settings';
        } elseif ($memory_percent > 75) {
            $status['overall'] = 'warning';
            $status['issues'][] = "Moderate memory usage: {$memory_percent}%";
        }
        
        // Check if emergency mode is active
        if ($this->is_emergency_mode()) {
            $status['overall'] = 'critical';
            $status['issues'][] = 'System is in emergency mode';
            $status['recommendations'][] = 'Resolve critical errors and exit emergency mode';
        }
        
        return $status;
    }
    
    /**
     * Calculate error rate
     */
    private function calculate_error_rate() {
        $today = current_time('Y-m-d');
        $today_errors = $this->error_stats['error_trends'][$today] ?? 0;
        
        // Estimate requests (simplified)
        $estimated_requests = 1000; // This would be calculated from actual traffic
        
        return $estimated_requests > 0 ? ($today_errors / $estimated_requests) * 100 : 0;
    }
}

// End of file
