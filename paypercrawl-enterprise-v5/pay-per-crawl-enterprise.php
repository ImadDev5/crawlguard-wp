<?php
/**
 * Plugin Name: PayPerCrawl Enterprise
 * Plugin URI: https://paypercrawl.tech/enterprise
 * Description: Enterprise AI Bot Detection & Monetization Platform - Turn Every AI Crawl Into Revenue with Cloudflare Integration
 * Version: 5.0.0
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
 * @version 5.0.0
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

/**
 * PayPerCrawl Enterprise Constants
 */
define('PAYPERCRAWL_ENTERPRISE_VERSION', '5.0.0');
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
 * @since 5.0.0
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
    
    /**
     * Plugin initialization
     */
    private function __construct() {
        $this->init_autoloader();
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Get plugin instance
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
     * Initialize autoloader
     */
    private function init_autoloader() {
        spl_autoload_register(array($this, 'autoload'));
    }
    
    /**
     * Autoload classes
     * 
     * @param string $class_name
     */
    public function autoload($class_name) {
        if (strpos($class_name, 'PayPerCrawl_') !== 0) {
            return;
        }
        
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
        
        // WordPress hooks
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'wp_loaded'));
        add_action('wp_head', array($this, 'add_security_headers'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
        
        // Frontend hooks
        add_action('template_redirect', array($this, 'process_request'), 1);
        add_action('wp_footer', array($this, 'add_tracking_beacon'));
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        try {
            // Error handler first
            $this->error_handler = new PayPerCrawl_Error_Handler();
            
            // Core components
            $this->bot_detector = new PayPerCrawl_Bot_Detector();
            $this->analytics = new PayPerCrawl_Analytics();
            $this->api_client = new PayPerCrawl_API_Client();
            
            // Admin component (only in admin)
            if (is_admin()) {
                $this->admin = new PayPerCrawl_Admin();
            }
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Enterprise: Failed to initialize components - ' . $e->getMessage());
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
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Log activation
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
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log('PayPerCrawl Enterprise deactivated');
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Detections table
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
            KEY status (status)
        ) $charset_collate;";
        
        // Analytics table
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
            UNIQUE KEY date_recorded (date_recorded)
        ) $charset_collate;";
        
        // Config table
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
            UNIQUE KEY config_key (config_key)
        ) $charset_collate;";
        
        // Logs table
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
            'confidence_threshold' => 80.0,
            'blocked_bots' => array(),
            'allowed_ips' => array(),
            'webhook_url' => '',
            'debug_mode' => false,
            'early_access' => true
        );
        
        foreach ($defaults as $key => $value) {
            if (!get_option('paypercrawl_' . $key)) {
                add_option('paypercrawl_' . $key, $value);
            }
        }
    }
    
    /**
     * WordPress init
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('paypercrawl-enterprise', false, dirname(PAYPERCRAWL_ENTERPRISE_PLUGIN_BASENAME) . '/languages');
        
        // Initialize sessions if needed
        if (!session_id()) {
            session_start();
        }
    }
    
    /**
     * WordPress loaded
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
        
        // Register settings
        register_setting('paypercrawl_settings', 'paypercrawl_api_key');
        register_setting('paypercrawl_settings', 'paypercrawl_cloudflare_zone_id');
        register_setting('paypercrawl_settings', 'paypercrawl_cloudflare_api_token');
        register_setting('paypercrawl_settings', 'paypercrawl_detection_enabled');
        register_setting('paypercrawl_settings', 'paypercrawl_revenue_tracking');
    }
    
    /**
     * Admin menu
     */
    public function admin_menu() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        add_menu_page(
            'PayPerCrawl Enterprise',
            'PayPerCrawl',
            'manage_options',
            'paypercrawl-enterprise',
            array($this, 'admin_dashboard'),
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'paypercrawl-enterprise',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'paypercrawl-enterprise',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'paypercrawl-enterprise',
            'Analytics',
            'Analytics',
            'manage_options',
            'paypercrawl-analytics',
            array($this, 'admin_analytics')
        );
        
        add_submenu_page(
            'paypercrawl-enterprise',
            'Settings',
            'Settings',
            'manage_options',
            'paypercrawl-settings',
            array($this, 'admin_settings')
        );
        
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
        
        // Enqueue styles
        wp_enqueue_style(
            'paypercrawl-admin',
            PAYPERCRAWL_ENTERPRISE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PAYPERCRAWL_ENTERPRISE_VERSION
        );
        
        // Enqueue scripts
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
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        // Localize script
        wp_localize_script('paypercrawl-admin', 'paypercrawl_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('paypercrawl_nonce'),
            'api_url' => PAYPERCRAWL_API_URL,
            'version' => PAYPERCRAWL_ENTERPRISE_VERSION
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
            error_log('PayPerCrawl: Request processing error - ' . $e->getMessage());
        }
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        echo '<meta name="robots" content="noai, noimageai" />' . "\n";
        echo '<meta name="paypercrawl-version" content="' . PAYPERCRAWL_ENTERPRISE_VERSION . '" />' . "\n";
    }
    
    /**
     * Add tracking beacon
     */
    public function add_tracking_beacon() {
        if (!get_option('paypercrawl_revenue_tracking', true)) {
            return;
        }
        
        echo '<script>
        (function() {
            var beacon = new Image();
            beacon.src = "' . admin_url('admin-ajax.php') . '?action=paypercrawl_track&t=" + Date.now();
        })();
        </script>';
    }
    
    /**
     * Admin dashboard page
     */
    public function admin_dashboard() {
        if ($this->admin) {
            $this->admin->render_dashboard();
        }
    }
    
    /**
     * Admin analytics page
     */
    public function admin_analytics() {
        if ($this->admin) {
            $this->admin->render_analytics();
        }
    }
    
    /**
     * Admin settings page
     */
    public function admin_settings() {
        if ($this->admin) {
            $this->admin->render_settings();
        }
    }
    
    /**
     * Admin logs page
     */
    public function admin_logs() {
        if ($this->admin) {
            $this->admin->render_logs();
        }
    }
    
    /**
     * Get plugin version
     * 
     * @return string
     */
    public function get_version() {
        return PAYPERCRAWL_ENTERPRISE_VERSION;
    }
    
    /**
     * Check if early access mode is enabled
     * 
     * @return bool
     */
    public function is_early_access() {
        return get_option('paypercrawl_early_access', true);
    }
}

// Initialize plugin
PayPerCrawl_Enterprise::get_instance();

// Prevent cloning and unserialization
if (class_exists('PayPerCrawl_Enterprise')) {
    add_action('plugins_loaded', function() {
        if (method_exists('PayPerCrawl_Enterprise', '__clone')) {
            wp_die('Cloning PayPerCrawl Enterprise is forbidden.');
        }
    });
}
