<?php
/**
 * Plugin Name: CrawlGuard WP
 * Plugin URI: https://creativeinteriorsstudio.com
 * Description: AI content monetization and bot detection for WordPress. Turn AI bot traffic into revenue with intelligent content protection.
 * Version: 1.0.0
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

// Define plugin constants
define('CRAWLGUARD_VERSION', '1.0.0');
define('CRAWLGUARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRAWLGUARD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRAWLGUARD_PLUGIN_FILE', __FILE__);

// Main plugin class
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
        // Load text domain
        load_plugin_textdomain('crawlguard-wp', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Load dependencies if they exist
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Only load files that actually exist
        $includes_dir = CRAWLGUARD_PLUGIN_PATH . 'includes/';
        
        if (file_exists($includes_dir . 'class-admin.php')) {
            require_once $includes_dir . 'class-admin.php';
        }
        
        if (file_exists($includes_dir . 'class-bot-detector.php')) {
            require_once $includes_dir . 'class-bot-detector.php';
        }
        
        if (file_exists($includes_dir . 'class-api-client.php')) {
            require_once $includes_dir . 'class-api-client.php';
        }
        
        if (file_exists($includes_dir . 'class-frontend.php')) {
            require_once $includes_dir . 'class-frontend.php';
        }
    }
    
    private function init_hooks() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Initialize components if classes exist
        if (is_admin() && class_exists('CrawlGuard_Admin')) {
            new CrawlGuard_Admin();
        }
        
        if (!is_admin() && class_exists('CrawlGuard_Frontend')) {
            new CrawlGuard_Frontend();
        }
        
        // Bot detection
        if (class_exists('CrawlGuard_Bot_Detector')) {
            add_action('wp', array($this, 'detect_bots'));
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'CrawlGuard',
            'CrawlGuard',
            'manage_options',
            'crawlguard',
            array($this, 'admin_page'),
            'dashicons-shield',
            30
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>CrawlGuard WP</h1>
            <p>Welcome to CrawlGuard - AI content monetization and bot detection plugin.</p>
            
            <div class="notice notice-info">
                <p><strong>Status:</strong> Plugin is active and running!</p>
            </div>
            
            <h2>Quick Setup</h2>
            <ol>
                <li>Configure your API settings</li>
                <li>Set up bot detection rules</li>
                <li>Enable monetization when ready</li>
            </ol>
        </div>
        <?php
    }
    
    public function detect_bots() {
        if (class_exists('CrawlGuard_Bot_Detector')) {
            $detector = new CrawlGuard_Bot_Detector();
            $detector->process_request();
        }
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('crawlguard_cleanup');
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            bot_detected tinyint(1) DEFAULT 0,
            bot_type varchar(50),
            action_taken varchar(20),
            revenue decimal(10,4) DEFAULT 0.0000,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        add_option('crawlguard_options', array(
            'api_key' => '',
            'api_url' => 'https://api.creativeinteriorsstudio.com/v1',
            'monetization_enabled' => false,
            'detection_level' => 'medium',
            'price_per_request' => 0.001
        ));
    }
}

// Initialize plugin
CrawlGuardWP::get_instance();
