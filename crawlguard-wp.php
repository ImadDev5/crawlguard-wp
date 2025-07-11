<?php
/**
 * Plugin Name: CrawlGuard WP
 * Plugin URI: https://crawlguard.com
 * Description: Monetize AI bot traffic and protect your content with intelligent bot detection and access control.
 * Version: 1.0.0
 * Author: CrawlGuard Team
 * License: GPL v2 or later
 * Text Domain: crawlguard-wp
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CRAWLGUARD_VERSION', '1.0.0');
define('CRAWLGUARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRAWLGUARD_PLUGIN_PATH', plugin_dir_path(__FILE__));
class CrawlGuardWP {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('crawlguard-wp', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize core components
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once CRAWLGUARD_PLUGIN_PATH . 'includes/class-bot-detector.php';
        require_once CRAWLGUARD_PLUGIN_PATH . 'includes/class-api-client.php';
        require_once CRAWLGUARD_PLUGIN_PATH . 'includes/class-admin.php';
        require_once CRAWLGUARD_PLUGIN_PATH . 'includes/class-frontend.php';
    }
    
    private function init_hooks() {
        // Initialize bot detection on every request
        add_action('wp', array($this, 'detect_and_handle_bots'));
        
        // Admin interface
        if (is_admin()) {
            new CrawlGuard_Admin();
        } else {
            new CrawlGuard_Frontend();
        }
    }
    
    public function detect_and_handle_bots() {
        $bot_detector = new CrawlGuard_Bot_Detector();
        $bot_detector->process_request();
    }
    
    public function activate() {
        // Create necessary database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for storing bot detection logs
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            bot_detected tinyint(1) DEFAULT 0 NOT NULL,
            bot_type varchar(50),
            action_taken varchar(20) DEFAULT 'allowed' NOT NULL,
            revenue_generated decimal(10,4) DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        $default_options = array(
            'api_key' => '',
            'monetization_enabled' => false,
            'detection_sensitivity' => 'medium',
            'allowed_bots' => array('googlebot', 'bingbot'),
            'pricing_per_request' => 0.001
        );
        
        add_option('crawlguard_options', $default_options);
    }
}

// Initialize the plugin
CrawlGuardWP::get_instance();
