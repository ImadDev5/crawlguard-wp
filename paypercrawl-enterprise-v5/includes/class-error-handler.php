<?php
/**
 * Error Handler for PayPerCrawl Enterprise
 * 
 * @package PayPerCrawl_Enterprise
 * @subpackage ErrorHandling
 * @version 5.0.0
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
     * 
     * @var PayPerCrawl_Error_Handler
     */
    private static $instance = null;
    
    /**
     * Debug mode
     * 
     * @var bool
     */
    private $debug_mode;
    
    /**
     * Log file path
     * 
     * @var string
     */
    private $log_file;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->debug_mode = get_option('paypercrawl_debug_mode', false);
        $this->log_file = WP_CONTENT_DIR . '/paypercrawl-error.log';
        
        // Set error handlers
        $this->init_error_handlers();
        
        // Register cleanup cron
        add_action('paypercrawl_daily_cleanup', array($this, 'cleanup_old_logs'));
    }
    
    /**
     * Get singleton instance
     * 
     * @return PayPerCrawl_Error_Handler
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize error handlers
     */
    private function init_error_handlers() {
        // Set custom error handler
        set_error_handler(array($this, 'handle_php_error'));
        
        // Set exception handler
        set_exception_handler(array($this, 'handle_exception'));
        
        // Register shutdown function
        register_shutdown_function(array($this, 'handle_shutdown'));
    }
    
    /**
     * Handle PHP errors
     * 
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     * @return bool
     */
    public function handle_php_error($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $level = $this->get_log_level_from_severity($severity);
        
        $context = array(
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        );
        
        $this->log($level, $message, $context, 'PHP_ERROR');
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     * 
     * @param Exception $exception
     */
    public function handle_exception($exception) {
        $context = array(
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
            'code' => $exception->getCode()
        );
        
        $this->log(self::LEVEL_CRITICAL, $exception->getMessage(), $context, 'EXCEPTION');
    }
    
    /**
     * Handle script shutdown
     */
    public function handle_shutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            $context = array(
                'type' => $error['type'],
                'file' => $error['file'],
                'line' => $error['line']
            );
            
            $this->log(self::LEVEL_CRITICAL, $error['message'], $context, 'FATAL_ERROR');
        }
    }
    
    /**
     * Log message to database and file
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @param string $source
     */
    public function log($level, $message, $context = array(), $source = 'GENERAL') {
        global $wpdb;
        
        try {
            // Sanitize inputs
            $level = sanitize_text_field($level);
            $message = sanitize_textarea_field($message);
            $source = sanitize_text_field($source);
            
            // Prepare context
            $context_json = wp_json_encode($context);
            
            // Log to database
            $table_name = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
            
            $wpdb->insert(
                $table_name,
                array(
                    'level' => $level,
                    'message' => $message,
                    'context' => $context_json,
                    'source' => $source,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
            
            // Log to file if debug mode
            if ($this->debug_mode) {
                $this->log_to_file($level, $message, $context, $source);
            }
            
            // Log critical errors to WordPress error log
            if (in_array($level, array(self::LEVEL_ERROR, self::LEVEL_CRITICAL))) {
                error_log("PayPerCrawl [{$level}] {$source}: {$message}");
            }
            
        } catch (Exception $e) {
            // Fallback to error_log if database logging fails
            error_log("PayPerCrawl Error Handler Failed: " . $e->getMessage());
            error_log("Original Error [{$level}] {$source}: {$message}");
        }
    }
    
    /**
     * Log to file
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @param string $source
     */
    private function log_to_file($level, $message, $context, $source) {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$source}: {$message}";
        
        if (!empty($context)) {
            $log_entry .= " | Context: " . wp_json_encode($context);
        }
        
        $log_entry .= PHP_EOL;
        
        // Write to file
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get recent logs
     * 
     * @param int $limit
     * @param string $level
     * @return array
     */
    public function get_recent_logs($limit = 50, $level = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
        
        $sql = "SELECT * FROM {$table_name}";
        $where_conditions = array();
        $params = array();
        
        if ($level) {
            $where_conditions[] = "level = %s";
            $params[] = $level;
        }
        
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;
        
        $prepared_sql = $wpdb->prepare($sql, $params);
        
        return $wpdb->get_results($prepared_sql, ARRAY_A);
    }
    
    /**
     * Get log statistics
     * 
     * @return array
     */
    public function get_log_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
        
        // Get counts by level
        $level_counts = $wpdb->get_results(
            "SELECT level, COUNT(*) as count FROM {$table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
             GROUP BY level",
            ARRAY_A
        );
        
        // Get total count
        $total_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        // Get error trends
        $hourly_errors = $wpdb->get_results(
            "SELECT DATE_FORMAT(created_at, '%H:00') as hour, COUNT(*) as count 
             FROM {$table_name} 
             WHERE level IN ('error', 'critical') 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY hour 
             ORDER BY hour",
            ARRAY_A
        );
        
        return array(
            'level_counts' => $level_counts,
            'total_count' => $total_count,
            'hourly_errors' => $hourly_errors
        );
    }
    
    /**
     * Clear old logs
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
        
        // Keep only latest MAX_LOG_ENTRIES
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} 
                 WHERE id NOT IN (
                     SELECT id FROM (
                         SELECT id FROM {$table_name} 
                         ORDER BY created_at DESC 
                         LIMIT %d
                     ) as keep_logs
                 )",
                self::MAX_LOG_ENTRIES
            )
        );
        
        // Clean up file log if it exists
        if (file_exists($this->log_file) && filesize($this->log_file) > 10485760) { // 10MB
            $lines = file($this->log_file);
            $keep_lines = array_slice($lines, -1000); // Keep last 1000 lines
            file_put_contents($this->log_file, implode('', $keep_lines));
        }
    }
    
    /**
     * Get log level from PHP error severity
     * 
     * @param int $severity
     * @return string
     */
    private function get_log_level_from_severity($severity) {
        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return self::LEVEL_CRITICAL;
                
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return self::LEVEL_WARNING;
                
            case E_NOTICE:
            case E_USER_NOTICE:
                return self::LEVEL_INFO;
                
            default:
                return self::LEVEL_DEBUG;
        }
    }
    
    /**
     * Debug helper function
     * 
     * @param mixed $data
     * @param string $label
     */
    public function debug($data, $label = 'DEBUG') {
        if (!$this->debug_mode) {
            return;
        }
        
        $message = $label . ': ' . (is_string($data) ? $data : wp_json_encode($data));
        $this->log(self::LEVEL_DEBUG, $message, array(), 'DEBUG');
    }
    
    /**
     * Performance monitoring
     * 
     * @param string $operation
     * @param callable $callback
     * @return mixed
     */
    public function monitor_performance($operation, $callback) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        try {
            $result = call_user_func($callback);
            
            $end_time = microtime(true);
            $end_memory = memory_get_usage();
            
            $execution_time = round(($end_time - $start_time) * 1000, 2);
            $memory_used = $end_memory - $start_memory;
            
            $this->log(self::LEVEL_INFO, "Performance: {$operation}", array(
                'execution_time_ms' => $execution_time,
                'memory_used_bytes' => $memory_used,
                'peak_memory_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2)
            ), 'PERFORMANCE');
            
            return $result;
            
        } catch (Exception $e) {
            $this->log(self::LEVEL_ERROR, "Operation failed: {$operation}", array(
                'error' => $e->getMessage(),
                'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
            ), 'PERFORMANCE');
            
            throw $e;
        }
    }
    
    /**
     * Export logs for debugging
     * 
     * @param int $limit
     * @return string
     */
    public function export_logs($limit = 100) {
        $logs = $this->get_recent_logs($limit);
        
        $export = "PayPerCrawl Enterprise Error Logs Export\n";
        $export .= "Generated: " . current_time('Y-m-d H:i:s') . "\n";
        $export .= "Version: " . PAYPERCRAWL_ENTERPRISE_VERSION . "\n";
        $export .= str_repeat("=", 50) . "\n\n";
        
        foreach ($logs as $log) {
            $export .= "[{$log['created_at']}] [{$log['level']}] {$log['source']}: {$log['message']}\n";
            
            if (!empty($log['context'])) {
                $context = json_decode($log['context'], true);
                if ($context) {
                    $export .= "Context: " . wp_json_encode($context, JSON_PRETTY_PRINT) . "\n";
                }
            }
            
            $export .= str_repeat("-", 50) . "\n";
        }
        
        return $export;
    }
}

// End of file
