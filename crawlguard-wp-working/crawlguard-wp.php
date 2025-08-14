<?php
/**
 * Plugin Name: CrawlGuard WP Pro
 * Plugin URI: https://creativeinteriorsstudio.com
 * Description: AI content monetization and bot detection for WordPress. Turn AI bot traffic into revenue with intelligent content protection and full features.
 * Version: 2.0.0
 * Author: CrawlGuard Team
 * Author URI: https://creativeinteriorsstudio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: crawlguard-wp
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('CRAWLGUARD_VERSION', '2.0.0');
define('CRAWLGUARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRAWLGUARD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRAWLGUARD_PLUGIN_FILE', __FILE__);

// Also define PRO constants for backward compatibility
define('CRAWLGUARD_PRO_VERSION', '2.0.0');
define('CRAWLGUARD_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRAWLGUARD_PRO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRAWLGUARD_PRO_PLUGIN_FILE', __FILE__);

/**
 * Main CrawlGuard Plugin Class
 */
class CrawlGuard_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'initialize_plugin'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    private function load_dependencies() {
        // Include required files that exist
        $includes = array(
            'class-simple-admin.php',
            'class-simple-bot-detector.php',
        );
        
        foreach ($includes as $filename) {
            $filepath = CRAWLGUARD_PLUGIN_PATH . 'includes/' . $filename;
            if (file_exists($filepath)) {
                require_once $filepath;
            } else {
                // Log missing file for debugging
                error_log("CrawlGuard: Missing file - " . $filepath);
            }
        }
    }
    
    public function initialize_plugin() {
        // Check if WordPress is loaded
        if (!function_exists('wp_get_current_user')) {
            return;
        }
        
        // Initialize components
        $this->init_components();
    }
    
    private function init_components() {
        // Initialize admin interface - ALWAYS load for menu to appear
        if (class_exists('CrawlGuard_Simple_Admin')) {
            new CrawlGuard_Simple_Admin();
        }
        
        // Initialize bot detector
        if (class_exists('CrawlGuard_Bot_Detector')) {
            new CrawlGuard_Bot_Detector();
        }
    }
    
    public function activate() {
        // Create necessary database tables
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('crawlguard_daily_report');
        wp_clear_scheduled_hook('crawlguard_cleanup_logs');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bot detections table
        $table_name = $wpdb->prefix . 'crawlguard_detections';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_agent text NOT NULL,
            ip_address varchar(45) NOT NULL,
            bot_type varchar(100) NOT NULL,
            confidence_score float NOT NULL,
            page_url text NOT NULL,
            detection_time datetime DEFAULT CURRENT_TIMESTAMP,
            monetized tinyint(1) DEFAULT 0,
            revenue decimal(10,4) DEFAULT 0.0000,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY bot_type (bot_type),
            KEY detection_time (detection_time)
        ) $charset_collate;";
        
        // Revenue tracking table
        $revenue_table = $wpdb->prefix . 'crawlguard_revenue';
        $revenue_sql = "CREATE TABLE $revenue_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            detection_id mediumint(9),
            amount decimal(10,4) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            stripe_transaction_id varchar(255),
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY detection_id (detection_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($revenue_sql);
    }
    
    private function set_default_options() {
        // Set default configuration options
        $defaults = array(
            'crawlguard_api_url' => 'https://crawlguard-api-prod.crawlguard-api.workers.dev',
            'crawlguard_detection_enabled' => true,
            'crawlguard_monetization_enabled' => true,
            'crawlguard_revenue_share' => 0.85, // 85% to publisher, 15% to platform
            'crawlguard_email_notifications' => true,
            'crawlguard_daily_reports' => true,
        );
        
        foreach ($defaults as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }
    
    private function schedule_cron_jobs() {
        // Schedule daily revenue report
        if (!wp_next_scheduled('crawlguard_daily_report')) {
            wp_schedule_event(time(), 'daily', 'crawlguard_daily_report');
        }
        
        // Schedule weekly log cleanup
        if (!wp_next_scheduled('crawlguard_cleanup_logs')) {
            wp_schedule_event(time(), 'weekly', 'crawlguard_cleanup_logs');
        }
    }
}

// Initialize the plugin
CrawlGuard_Plugin::get_instance();

// Add AJAX handlers for admin
add_action('wp_ajax_crawlguard_test_connection', 'crawlguard_ajax_test_connection');
add_action('wp_ajax_crawlguard_get_dashboard_data', 'crawlguard_ajax_get_dashboard_data');

function crawlguard_ajax_test_connection() {
    check_ajax_referer('crawlguard_nonce', 'nonce');
    
    $api_url = get_option('crawlguard_api_url', 'https://crawlguard-api-prod.crawlguard-api.workers.dev');
    
    $response = wp_remote_get($api_url . '/health');
    
    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => 'Failed to connect to API: ' . $response->get_error_message()
        ));
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data && isset($data['status']) && $data['status'] === 'healthy') {
        wp_send_json_success(array(
            'message' => 'API connection successful',
            'api_version' => $data['version'] ?? 'unknown'
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'API returned invalid response'
        ));
    }
}

function crawlguard_ajax_get_dashboard_data() {
    check_ajax_referer('crawlguard_nonce', 'nonce');
    
    global $wpdb;
    
    // Get detection stats for today
    $today = current_time('Y-m-d');
    $detections_today = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}crawlguard_detections WHERE DATE(detection_time) = %s",
        $today
    ));
    
    // Get revenue for today
    $revenue_today = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM {$wpdb->prefix}crawlguard_revenue WHERE DATE(created_at) = %s AND status = 'completed'",
        $today
    )) ?: 0;
    
    // Get recent detections
    $recent_detections = $wpdb->get_results(
        "SELECT bot_type, confidence_score, detection_time, revenue 
         FROM {$wpdb->prefix}crawlguard_detections 
         ORDER BY detection_time DESC 
         LIMIT 10"
    );
    
    wp_send_json_success(array(
        'detections_today' => intval($detections_today),
        'revenue_today' => floatval($revenue_today),
        'recent_detections' => $recent_detections,
        'timestamp' => current_time('c')
    ));
}
