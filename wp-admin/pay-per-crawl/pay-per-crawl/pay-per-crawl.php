<?php
/**
 * Plugin Name: PayPerCrawl
 * Plugin URI: https://paypercrawl.com
 * Description: Turn AI bot traffic into revenue. Free beta - you keep 100% earnings!
 * Version: 1.0.0-beta
 * Author: PayPerCrawl
 * Author URI: https://paypercrawl.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: paypercrawl
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package PayPerCrawl
 * @version 1.0.0-beta
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PAYPERCRAWL_VERSION', '1.0.0-beta');
define('PAYPERCRAWL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYPERCRAWL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PAYPERCRAWL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main PayPerCrawl Plugin Class
 */
class PayPerCrawl {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Setup autoloader
        spl_autoload_register(array($this, 'autoload'));
        
        // Hook into WordPress
        add_action('init', array($this, 'setup'));
        add_action('wp', array($this, 'detect_bots'));
        
        // Admin hooks
        if (is_admin()) {
            // Initialize admin class
            if (class_exists('PayPerCrawl_Admin')) {
                PayPerCrawl_Admin::get_instance();
            }
        }
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Simple autoloader
     */
    public function autoload($class_name) {
        // Only load our classes
        if (strpos($class_name, 'PayPerCrawl_') !== 0) {
            return;
        }
        
        // Convert class name to file name
        $file_name = 'class-' . strtolower(str_replace('_', '-', substr($class_name, 12))) . '.php';
        $file_path = PAYPERCRAWL_PLUGIN_DIR . 'includes/' . $file_name;
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    /**
     * Setup plugin components
     */
    public function setup() {
        // Initialize components
        if (class_exists('PayPerCrawl_DB')) {
            PayPerCrawl_DB::get_instance();
        }
        
        if (class_exists('PayPerCrawl_Detector')) {
            PayPerCrawl_Detector::get_instance();
        }
        
        if (class_exists('PayPerCrawl_Analytics')) {
            PayPerCrawl_Analytics::get_instance();
        }
    }
    
    /**
     * Bot detection on every page load
     */
    public function detect_bots() {
        if (class_exists('PayPerCrawl_Detector')) {
            $detector = PayPerCrawl_Detector::get_instance();
            $detection = $detector->detect();
            
            if ($detection) {
                // Log the detection
                if (class_exists('PayPerCrawl_DB')) {
                    PayPerCrawl_DB::get_instance()->log_detection($detection);
                }
                
                // Take action based on settings
                $action = get_option('paypercrawl_bot_action', 'allow');
                if ($action === 'block') {
                    status_header(403);
                    exit('Access Denied');
                }
            }
        }
    }
    

    
    /**
     * AJAX handler for analytics
     */
    public function ajax_get_analytics() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'paypercrawl_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        if (class_exists('PayPerCrawl_Analytics')) {
            $analytics = PayPerCrawl_Analytics::get_instance();
            $data = $analytics->get_chart_data();
            wp_send_json_success($data);
        }
        
        wp_send_json_error('Analytics not available');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        if (class_exists('PayPerCrawl_DB')) {
            PayPerCrawl_DB::get_instance()->create_tables();
        }
        
        // Set default options
        add_option('paypercrawl_bot_action', 'allow');
        add_option('paypercrawl_js_detection', '0');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
    
    /**
     * Check for missing credentials
     */
    public function audit_credentials() {
        $needed = array('paypercrawl_api_key', 'paypercrawl_worker_url');
        $missing = array();
        
        foreach ($needed as $key) {
            if (!get_option($key)) {
                $missing[] = $key;
            }
        }
        
        return $missing;
    }
    
    /**
     * Ensure required classes are loaded
     */
    private function ensure_classes_loaded() {
        // Force load required classes if not already loaded
        if (!class_exists('PayPerCrawl_Analytics')) {
            require_once PAYPERCRAWL_PLUGIN_DIR . 'includes/class-analytics.php';
        }
        if (!class_exists('PayPerCrawl_DB')) {
            require_once PAYPERCRAWL_PLUGIN_DIR . 'includes/class-db.php';
        }
        if (!class_exists('PayPerCrawl_Detector')) {
            require_once PAYPERCRAWL_PLUGIN_DIR . 'includes/class-detector.php';
        }
        if (!class_exists('PayPerCrawl_API')) {
            require_once PAYPERCRAWL_PLUGIN_DIR . 'includes/class-api.php';
        }
    }
}

// Initialize the plugin
PayPerCrawl::get_instance();
