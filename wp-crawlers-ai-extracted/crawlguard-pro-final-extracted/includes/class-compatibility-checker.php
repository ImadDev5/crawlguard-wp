<?php
/**
 * Compatibility Checker for CrawlGuard Pro
 * 
 * Ensures the plugin runs in compatible environments
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Compatibility_Checker {
    
    const MIN_PHP_VERSION = '7.4';
    const MIN_WP_VERSION = '5.0';
    const REQUIRED_EXTENSIONS = ['curl', 'json', 'openssl'];
    
    private $errors = [];
    private $warnings = [];
    
    /**
     * Run all compatibility checks
     */
    public function check_compatibility() {
        $this->check_php_version();
        $this->check_wordpress_version();
        $this->check_php_extensions();
        $this->check_ssl_support();
        $this->check_database_requirements();
        $this->check_server_configuration();
        
        return $this->get_results();
    }
    
    /**
     * Check PHP version
     */
    private function check_php_version() {
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
            $this->errors[] = sprintf(
                __('CrawlGuard Pro requires PHP %s or higher. Your server is running PHP %s.', 'crawlguard-wp'),
                self::MIN_PHP_VERSION,
                PHP_VERSION
            );
        }
    }
    
    /**
     * Check WordPress version
     */
    private function check_wordpress_version() {
        global $wp_version;
        
        if (version_compare($wp_version, self::MIN_WP_VERSION, '<')) {
            $this->errors[] = sprintf(
                __('CrawlGuard Pro requires WordPress %s or higher. You are running WordPress %s.', 'crawlguard-wp'),
                self::MIN_WP_VERSION,
                $wp_version
            );
        }
        
        // Check for specific WordPress features
        if (!function_exists('wp_remote_get')) {
            $this->errors[] = __('WordPress HTTP API is not available.', 'crawlguard-wp');
        }
    }
    
    /**
     * Check required PHP extensions
     */
    private function check_php_extensions() {
        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            if (!extension_loaded($extension)) {
                $this->errors[] = sprintf(
                    __('Required PHP extension "%s" is not installed.', 'crawlguard-wp'),
                    $extension
                );
            }
        }
        
        // Check optional extensions
        if (!extension_loaded('mbstring')) {
            $this->warnings[] = __('PHP mbstring extension is recommended for better performance.', 'crawlguard-wp');
        }
    }
    
    /**
     * Check SSL support
     */
    private function check_ssl_support() {
        if (!is_ssl() && !defined('CRAWLGUARD_ALLOW_HTTP')) {
            $this->warnings[] = __('Your site is not using HTTPS. SSL is recommended for secure API communication.', 'crawlguard-wp');
        }
        
        // Check SSL certificate verification capability
        $ssl_verify = wp_http_supports(['ssl']);
        if (!$ssl_verify) {
            $this->warnings[] = __('SSL certificate verification is not available. API calls may be less secure.', 'crawlguard-wp');
        }
    }
    
    /**
     * Check database requirements
     */
    private function check_database_requirements() {
        global $wpdb;
        
        // Check MySQL version
        $mysql_version = $wpdb->db_version();
        if (version_compare($mysql_version, '5.6', '<')) {
            $this->warnings[] = sprintf(
                __('CrawlGuard Pro recommends MySQL 5.6 or higher. You are running MySQL %s.', 'crawlguard-wp'),
                $mysql_version
            );
        }
        
        // Check database privileges
        $table_name = $wpdb->prefix . 'crawlguard_test';
        $can_create = $wpdb->query("CREATE TABLE IF NOT EXISTS $table_name (id INT) ENGINE=InnoDB");
        
        if ($can_create === false) {
            $this->errors[] = __('Unable to create database tables. Please check database permissions.', 'crawlguard-wp');
        } else {
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }
    }
    
    /**
     * Check server configuration
     */
    private function check_server_configuration() {
        // Check memory limit
        $memory_limit = $this->parse_size(ini_get('memory_limit'));
        $recommended_memory = 128 * 1024 * 1024; // 128MB
        
        if ($memory_limit < $recommended_memory) {
            $this->warnings[] = sprintf(
                __('PHP memory limit is %s. Recommended: 128M or higher for optimal performance.', 'crawlguard-wp'),
                size_format($memory_limit)
            );
        }
        
        // Check max execution time
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time > 0 && $max_execution_time < 30) {
            $this->warnings[] = sprintf(
                __('PHP max execution time is %d seconds. Recommended: 30 seconds or higher.', 'crawlguard-wp'),
                $max_execution_time
            );
        }
        
        // Check if WP-Cron is disabled
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            $this->warnings[] = __('WP-Cron is disabled. Some background tasks may not run automatically.', 'crawlguard-wp');
        }
    }
    
    /**
     * Parse size string to bytes
     */
    private function parse_size($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        
        return round($size);
    }
    
    /**
     * Get compatibility check results
     */
    public function get_results() {
        return [
            'compatible' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }
    
    /**
     * Display admin notices for compatibility issues
     */
    public function display_admin_notices() {
        if (!empty($this->errors)) {
            ?>
            <div class="notice notice-error">
                <p><strong><?php _e('CrawlGuard Pro Compatibility Issues:', 'crawlguard-wp'); ?></strong></p>
                <ul>
                    <?php foreach ($this->errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }
        
        if (!empty($this->warnings)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong><?php _e('CrawlGuard Pro Recommendations:', 'crawlguard-wp'); ?></strong></p>
                <ul>
                    <?php foreach ($this->warnings as $warning): ?>
                        <li><?php echo esc_html($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }
    }
}
