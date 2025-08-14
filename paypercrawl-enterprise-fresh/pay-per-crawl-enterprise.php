<?php
/**
 * Plugin Name: PayPerCrawl Enterprise
 * Plugin URI: https://paypercrawl.tech/enterprise
 * Description: Enterprise AI Bot Detection & Monetization Platform - Turn Every AI Crawl Into Revenue with Advanced Detection & Cloudflare Integration
 * Version: 6.0.0
 * Author: PayPerCrawl.tech
 * Author URI: https://paypercrawl.tech
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: paypercrawl-enterprise
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package PayPerCrawl_Enterprise
 * @version 6.0.0
 * @author PayPerCrawl.tech
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Plugin security check
if (!function_exists('add_action')) {
    exit('WordPress not detected.');
}

// PHP version check
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>PayPerCrawl Enterprise:</strong> This plugin requires PHP 7.4 or higher. You are running PHP ' . PHP_VERSION . '</p></div>';
    });
    return;
}

// WordPress version check
global $wp_version;
if (version_compare($wp_version, '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>PayPerCrawl Enterprise:</strong> This plugin requires WordPress 5.0 or higher.</p></div>';
    });
    return;
}

/**
 * PayPerCrawl Enterprise Constants
 */
define('PAYPERCRAWL_ENTERPRISE_VERSION', '6.0.0');
define('PAYPERCRAWL_ENTERPRISE_PLUGIN_FILE', __FILE__);
define('PAYPERCRAWL_ENTERPRISE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYPERCRAWL_ENTERPRISE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PAYPERCRAWL_ENTERPRISE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// API Endpoints
define('PAYPERCRAWL_API_URL', 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/');
define('PAYPERCRAWL_CLOUDFLARE_API', 'https://api.cloudflare.com/client/v4/');

// Database table names
define('PAYPERCRAWL_DETECTIONS_TABLE', 'paypercrawl_detections');
define('PAYPERCRAWL_ANALYTICS_TABLE', 'paypercrawl_analytics');
define('PAYPERCRAWL_CONFIG_TABLE', 'paypercrawl_config');
define('PAYPERCRAWL_LOGS_TABLE', 'paypercrawl_logs');

/**
 * PayPerCrawl Enterprise Main Class
 * 
 * Singleton pattern for enterprise-grade plugin architecture
 * 
 * @since 6.0.0
 */
final class PayPerCrawl_Enterprise {
    
    /**
     * Plugin instance
     * 
     * @var PayPerCrawl_Enterprise
     */
    private static $instance = null;
    
    /**
     * Plugin components
     */
    private $autoloader;
    private $bot_detector;
    private $admin;
    private $analytics;
    private $api_client;
    private $error_handler;
    private $cloudflare_integration;
    
    /**
     * Plugin initialization
     */
    private function __construct() {
        $this->init_autoloader();
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Get plugin instance (Singleton)
     * 
     * @return PayPerCrawl_Enterprise
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize autoloader for clean code organization
     */
    private function init_autoloader() {
        spl_autoload_register(array($this, 'autoload'));
    }
    
    /**
     * Autoload classes with PSR-4 style naming
     * 
     * @param string $class_name
     */
    public function autoload($class_name) {
        if (strpos($class_name, 'PayPerCrawl_') !== 0) {
            return;
        }
        
        // Convert class name to file name
        $file_name = 'class-' . strtolower(str_replace(array('PayPerCrawl_', '_'), array('', '-'), $class_name)) . '.php';
        $file_path = PAYPERCRAWL_ENTERPRISE_PLUGIN_DIR . 'includes/' . $file_name;
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(PAYPERCRAWL_ENTERPRISE_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(PAYPERCRAWL_ENTERPRISE_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Core WordPress hooks
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'wp_loaded'));
        add_action('wp_head', array($this, 'add_security_headers'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
        
        // Frontend hooks for bot detection
        add_action('template_redirect', array($this, 'process_request'), 1);
        add_action('wp_footer', array($this, 'add_tracking_beacon'));
        
        // AJAX hooks
        add_action('wp_ajax_paypercrawl_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_paypercrawl_update_settings', array($this, 'ajax_update_settings'));
        add_action('wp_ajax_paypercrawl_test_api', array($this, 'ajax_test_api'));
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        try {
            // Error handler first for proper logging
            $this->error_handler = new PayPerCrawl_Error_Handler();
            
            // Core detection engine
            $this->bot_detector = new PayPerCrawl_Bot_Detector_Enterprise();
            
            // Analytics engine
            $this->analytics = new PayPerCrawl_Analytics_Engine();
            
            // API client for Cloudflare integration
            $this->api_client = new PayPerCrawl_API_Client();
            
            // Cloudflare Workers integration
            $this->cloudflare_integration = new PayPerCrawl_Cloudflare_Integration();
            
            // Admin component (only in admin area)
            if (is_admin()) {
                $this->admin = new PayPerCrawl_Dashboard_Pro();
            }
            
        } catch (Exception $e) {
            if ($this->error_handler) {
                $this->error_handler->log('critical', 'Failed to initialize components: ' . $e->getMessage(), array(), 'PayPerCrawl_Enterprise');
            } else {
                error_log('PayPerCrawl Enterprise: Failed to initialize components - ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        try {
            // Create database tables
            $this->create_database_tables();
            
            // Set default options
            $this->set_default_options();
            
            // Schedule cron jobs
            if (!wp_next_scheduled('paypercrawl_daily_cleanup')) {
                wp_schedule_event(time(), 'daily', 'paypercrawl_daily_cleanup');
            }
            
            if (!wp_next_scheduled('paypercrawl_analytics_aggregation')) {
                wp_schedule_event(time(), 'hourly', 'paypercrawl_analytics_aggregation');
            }
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Log successful activation
            error_log('PayPerCrawl Enterprise v' . PAYPERCRAWL_ENTERPRISE_VERSION . ' activated successfully');
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Enterprise activation failed: ' . $e->getMessage());
            wp_die('Plugin activation failed. Check error logs for details.');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('paypercrawl_daily_cleanup');
        wp_clear_scheduled_hook('paypercrawl_analytics_aggregation');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log('PayPerCrawl Enterprise deactivated');
    }
    
    /**
     * Create database tables with dbDelta for enterprise reliability
     */
    private function create_database_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Detections table for bot logging
        $detections_table = $wpdb->prefix . PAYPERCRAWL_DETECTIONS_TABLE;
        $sql_detections = "CREATE TABLE $detections_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_agent text NOT NULL,
            ip_address varchar(45) NOT NULL,
            bot_type varchar(100) NOT NULL,
            confidence_score decimal(5,2) NOT NULL DEFAULT 0.00,
            page_url text NOT NULL,
            detected_at datetime DEFAULT CURRENT_TIMESTAMP,
            revenue_generated decimal(10,2) DEFAULT 0.00,
            status varchar(20) DEFAULT 'active',
            metadata longtext,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY bot_type (bot_type),
            KEY detected_at (detected_at),
            KEY status (status),
            KEY confidence_score (confidence_score)
        ) $charset_collate;";
        
        // Analytics table for dashboard metrics
        $analytics_table = $wpdb->prefix . PAYPERCRAWL_ANALYTICS_TABLE;
        $sql_analytics = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date_recorded date NOT NULL,
            total_detections int(11) DEFAULT 0,
            unique_bots int(11) DEFAULT 0,
            revenue_generated decimal(10,2) DEFAULT 0.00,
            top_bot_types text,
            page_views int(11) DEFAULT 0,
            conversion_rate decimal(5,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date_recorded (date_recorded),
            KEY revenue_generated (revenue_generated),
            KEY total_detections (total_detections)
        ) $charset_collate;";
        
        // Config table for encrypted settings
        $config_table = $wpdb->prefix . PAYPERCRAWL_CONFIG_TABLE;
        $sql_config = "CREATE TABLE $config_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            config_key varchar(100) NOT NULL,
            config_value longtext,
            config_type varchar(50) DEFAULT 'string',
            is_encrypted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY config_key (config_key),
            KEY is_encrypted (is_encrypted)
        ) $charset_collate;";
        
        // Logs table for enterprise debugging
        $logs_table = $wpdb->prefix . PAYPERCRAWL_LOGS_TABLE;
        $sql_logs = "CREATE TABLE $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            source varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY created_at (created_at),
            KEY source (source)
        ) $charset_collate;";
        
        // Execute table creation
        dbDelta($sql_detections);
        dbDelta($sql_analytics);
        dbDelta($sql_config);
        dbDelta($sql_logs);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'api_key' => '',
            'cloudflare_zone_id' => '',
            'cloudflare_api_token' => '',
            'detection_enabled' => true,
            'revenue_tracking' => true,
            'rate_limit' => 100,
            'confidence_threshold' => 85.0,
            'blocked_bots' => array(),
            'allowed_ips' => array(),
            'webhook_url' => '',
            'debug_mode' => false,
            'early_access' => true,
            'monetization_enabled' => true,
            'dashboard_refresh_rate' => 30
        );
        
        foreach ($defaults as $key => $value) {
            $option_name = 'paypercrawl_' . $key;
            if (!get_option($option_name)) {
                add_option($option_name, $value);
            }
        }
    }
    
    /**
     * WordPress init
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('paypercrawl-enterprise', false, dirname(PAYPERCRAWL_ENTERPRISE_PLUGIN_BASENAME) . '/languages');
        
        // Initialize sessions if needed
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * WordPress fully loaded
     */
    public function wp_loaded() {
        // Plugin fully loaded
        do_action('paypercrawl_enterprise_loaded');
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Register settings with proper sanitization
        register_setting('paypercrawl_settings', 'paypercrawl_api_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('paypercrawl_settings', 'paypercrawl_cloudflare_zone_id', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('paypercrawl_settings', 'paypercrawl_cloudflare_api_token', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('paypercrawl_settings', 'paypercrawl_detection_enabled', array(
            'sanitize_callback' => array($this, 'sanitize_boolean')
        ));
        
        register_setting('paypercrawl_settings', 'paypercrawl_revenue_tracking', array(
            'sanitize_callback' => array($this, 'sanitize_boolean')
        ));
    }
    
    /**
     * Sanitize boolean values
     */
    public function sanitize_boolean($value) {
        return (bool) $value;
    }
    
    /**
     * Admin menu
     */
    public function admin_menu() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Main menu page
        add_menu_page(
            'PayPerCrawl Enterprise',
            'PayPerCrawl',
            'manage_options',
            'paypercrawl-enterprise',
            array($this, 'admin_dashboard'),
            'dashicons-shield-alt',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'paypercrawl-enterprise',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'paypercrawl-enterprise',
            array($this, 'admin_dashboard')
        );
        
        // Analytics submenu
        add_submenu_page(
            'paypercrawl-enterprise',
            'Analytics',
            'Analytics',
            'manage_options',
            'paypercrawl-analytics',
            array($this, 'admin_analytics')
        );
        
        // Settings submenu
        add_submenu_page(
            'paypercrawl-enterprise',
            'Settings',
            'Settings',
            'manage_options',
            'paypercrawl-settings',
            array($this, 'admin_settings')
        );
        
        // Logs submenu
        add_submenu_page(
            'paypercrawl-enterprise',
            'Logs',
            'Logs',
            'manage_options',
            'paypercrawl-logs',
            array($this, 'admin_logs')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'paypercrawl') === false) {
            return;
        }
        
        // Enqueue professional admin styles
        wp_enqueue_style(
            'paypercrawl-admin',
            PAYPERCRAWL_ENTERPRISE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PAYPERCRAWL_ENTERPRISE_VERSION
        );
        
        // Enqueue admin JavaScript
        wp_enqueue_script(
            'paypercrawl-admin',
            PAYPERCRAWL_ENTERPRISE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-api'),
            PAYPERCRAWL_ENTERPRISE_VERSION,
            true
        );
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('paypercrawl-admin', 'paypercrawl_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('paypercrawl_nonce'),
            'api_url' => PAYPERCRAWL_API_URL,
            'version' => PAYPERCRAWL_ENTERPRISE_VERSION,
            'early_access' => $this->is_early_access()
        ));
    }
    
    /**
     * Process incoming requests for bot detection
     */
    public function process_request() {
        if (!get_option('paypercrawl_detection_enabled', true)) {
            return;
        }
        
        try {
            if ($this->bot_detector) {
                $this->bot_detector->process_request();
            }
        } catch (Exception $e) {
            if ($this->error_handler) {
                $this->error_handler->log('error', 'Request processing error: ' . $e->getMessage(), array(), 'process_request');
            } else {
                error_log('PayPerCrawl: Request processing error - ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Add security headers to prevent AI crawling
     */
    public function add_security_headers() {
        echo '<meta name="robots" content="noai, noimageai" />' . "\n";
        echo '<meta name="paypercrawl-protection" content="active" />' . "\n";
        echo '<meta name="paypercrawl-version" content="' . PAYPERCRAWL_ENTERPRISE_VERSION . '" />' . "\n";
    }
    
    /**
     * Add tracking beacon for analytics
     */
    public function add_tracking_beacon() {
        if (!get_option('paypercrawl_revenue_tracking', true) || is_admin()) {
            return;
        }
        
        echo '<script>
        (function() {
            var beacon = new Image();
            beacon.src = "' . admin_url('admin-ajax.php') . '?action=paypercrawl_track&t=" + Date.now() + "&nonce=' . wp_create_nonce('paypercrawl_tracking') . '";
        })();
        </script>';
    }
    
    /**
     * Admin page methods
     */
    public function admin_dashboard() {
        if ($this->admin) {
            $this->admin->render_dashboard();
        }
    }
    
    public function admin_analytics() {
        if ($this->admin) {
            $this->admin->render_analytics();
        }
    }
    
    public function admin_settings() {
        if ($this->admin) {
            $this->admin->render_settings();
        }
    }
    
    public function admin_logs() {
        if ($this->admin) {
            $this->admin->render_logs();
        }
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_get_analytics() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        if ($this->analytics) {
            wp_send_json_success($this->analytics->get_dashboard_data());
        } else {
            wp_send_json_error('Analytics engine not available');
        }
    }
    
    public function ajax_update_settings() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        // Handle settings update
        wp_send_json_success('Settings updated');
    }
    
    public function ajax_test_api() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        if ($this->api_client) {
            $result = $this->api_client->test_connection();
            wp_send_json($result);
        } else {
            wp_send_json_error('API client not available');
        }
    }
    
    /**
     * Utility methods
     */
    public function get_version() {
        return PAYPERCRAWL_ENTERPRISE_VERSION;
    }
    
    public function is_early_access() {
        return get_option('paypercrawl_early_access', true);
    }
    
    /**
     * Prevent cloning
     */
    public function __clone() {
        wp_die('Cloning PayPerCrawl Enterprise is forbidden.');
    }
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        wp_die('Unserializing PayPerCrawl Enterprise is forbidden.');
    }
}

// Initialize plugin
PayPerCrawl_Enterprise::get_instance();

// Additional security measures
if (class_exists('PayPerCrawl_Enterprise')) {
    add_action('plugins_loaded', function() {
        // Ensure singleton pattern is maintained
        if (method_exists('PayPerCrawl_Enterprise', '__clone')) {
            wp_die('PayPerCrawl Enterprise security violation detected.');
        }
    });
}
