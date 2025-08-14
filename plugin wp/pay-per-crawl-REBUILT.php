<?php
/**
 * Plugin Name: Pay Per Crawl
 * Plugin URI: https://paypercrawl.tech
 * Description: Advanced AI Bot Detection & Monetization Platform - Turn Every AI Crawl Into Revenue
 * Version: 3.0.0
 * Author: PayPerCrawl.tech
 * Author URI: https://paypercrawl.tech
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pay-per-crawl
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Define plugin constants
if (!defined('PAYPERCRAWL_VERSION')) {
    define('PAYPERCRAWL_VERSION', '3.0.0');
}
if (!defined('PAYPERCRAWL_PLUGIN_FILE')) {
    define('PAYPERCRAWL_PLUGIN_FILE', __FILE__);
}
if (!defined('PAYPERCRAWL_PLUGIN_DIR')) {
    define('PAYPERCRAWL_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('PAYPERCRAWL_PLUGIN_URL')) {
    define('PAYPERCRAWL_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('PAYPERCRAWL_API_URL')) {
    define('PAYPERCRAWL_API_URL', 'https://crawlguard-api-prod.crawlguard-api.workers.dev/');
}

/**
 * Main PayPerCrawl Plugin Class
 */
class PayPerCrawl {
    
    /**
     * Single instance of this class
     */
    private static $instance = null;
    
    /**
     * Bot detector instance
     */
    private $bot_detector = null;
    
    /**
     * Analytics instance
     */
    private $analytics = null;
    
    /**
     * Bot signatures array
     */
    private $bot_signatures = array();
    
    /**
     * Plugin initialization flag
     */
    private $initialized = false;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Private to prevent direct instantiation
     */
    private function __construct() {
        // Register activation and deactivation hooks first
        register_activation_hook(PAYPERCRAWL_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(PAYPERCRAWL_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Hook into WordPress initialization
        add_action('init', array($this, 'init'), 10);
        add_action('admin_init', array($this, 'admin_init'), 10);
        add_action('admin_menu', array($this, 'add_admin_menu'), 10);
        
        // Load dependencies immediately
        $this->load_dependencies();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        if ($this->initialized) {
            return;
        }
        
        // Load text domain
        load_plugin_textdomain('pay-per-crawl', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        $this->init_components();
        
        // Setup bot detection hooks
        add_action('wp', array($this, 'detect_bot'), 10);
        add_action('template_redirect', array($this, 'advanced_bot_detection'), 10);
        
        // Setup AJAX handlers
        add_action('wp_ajax_paypercrawl_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_paypercrawl_bot_activity', array($this, 'ajax_bot_activity'));
        
        // Setup script enqueuing
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        $this->initialized = true;
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        try {
            // Check if files exist before requiring
            $bot_detector_file = PAYPERCRAWL_PLUGIN_DIR . 'includes/class-paypercrawl-bot-detector.php';
            $analytics_file = PAYPERCRAWL_PLUGIN_DIR . 'includes/class-paypercrawl-analytics.php';
            
            if (file_exists($bot_detector_file)) {
                require_once $bot_detector_file;
            }
            
            if (file_exists($analytics_file)) {
                require_once $analytics_file;
            }
            
        } catch (Exception $e) {
            error_log('PayPerCrawl: Failed to load dependencies - ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        try {
            // Initialize bot detector
            if (class_exists('PayPerCrawl_Bot_Detector')) {
                $this->bot_detector = new PayPerCrawl_Bot_Detector();
                $this->bot_signatures = $this->bot_detector->get_bot_signatures();
            }
            
            // Initialize analytics
            if (class_exists('PayPerCrawl_Analytics')) {
                $this->analytics = new PayPerCrawl_Analytics();
            }
            
        } catch (Exception $e) {
            error_log('PayPerCrawl: Failed to initialize components - ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        try {
            // Create database tables
            $this->create_tables();
            
            // Set default options
            $this->set_default_options();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Log activation
            error_log('PayPerCrawl: Plugin activated successfully');
            
        } catch (Exception $e) {
            error_log('PayPerCrawl: Activation failed - ' . $e->getMessage());
            wp_die('Plugin activation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        try {
            // Clear scheduled events
            wp_clear_scheduled_hook('paypercrawl_cleanup');
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            // Log deactivation
            error_log('PayPerCrawl: Plugin deactivated');
            
        } catch (Exception $e) {
            error_log('PayPerCrawl: Deactivation error - ' . $e->getMessage());
        }
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        register_setting('paypercrawl_settings', 'paypercrawl_options', array($this, 'sanitize_options'));
        
        // Add settings sections
        add_settings_section(
            'paypercrawl_general',
            __('General Settings', 'pay-per-crawl'),
            array($this, 'settings_section_callback'),
            'paypercrawl'
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Pay Per Crawl', 'pay-per-crawl'),
            __('Pay Per Crawl', 'pay-per-crawl'),
            'manage_options',
            'paypercrawl-dashboard',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
        
        // Submenu pages
        $submenus = array(
            array('paypercrawl-dashboard', __('Dashboard', 'pay-per-crawl'), '📊 Dashboard', array($this, 'admin_page')),
            array('paypercrawl-analytics', __('Analytics', 'pay-per-crawl'), '📈 Analytics', array($this, 'analytics_page')),
            array('paypercrawl-bots', __('Bot Detection', 'pay-per-crawl'), '🤖 Bot Detection', array($this, 'bots_page')),
            array('paypercrawl-revenue', __('Revenue Settings', 'pay-per-crawl'), '💰 Revenue', array($this, 'revenue_page')),
            array('paypercrawl-settings', __('Settings', 'pay-per-crawl'), '⚙️ Settings', array($this, 'settings_page')),
            array('paypercrawl-support', __('Support', 'pay-per-crawl'), '🆘 Support', array($this, 'support_page'))
        );
        
        foreach ($submenus as $submenu) {
            add_submenu_page(
                'paypercrawl-dashboard',
                $submenu[1],
                $submenu[2],
                'manage_options',
                $submenu[0],
                $submenu[3]
            );
        }
    }
    
    /**
     * Bot detection
     */
    public function detect_bot() {
        if (!$this->bot_detector) {
            return;
        }
        
        try {
            $bot_info = $this->bot_detector->detect_bot();
            if ($bot_info) {
                $current_url = $this->get_current_url();
                $ip_address = $this->bot_detector->get_client_ip();
                $this->bot_detector->log_detection($bot_info, $current_url, $ip_address);
            }
        } catch (Exception $e) {
            error_log('PayPerCrawl: Bot detection error - ' . $e->getMessage());
        }
    }
    
    /**
     * Advanced bot detection
     */
    public function advanced_bot_detection() {
        // Additional detection methods can be added here
    }
    
    /**
     * Get current URL
     */
    private function get_current_url() {
        if (isset($_SERVER['REQUEST_URI'])) {
            return home_url($_SERVER['REQUEST_URI']);
        }
        return home_url();
    }
    
    /**
     * AJAX handler for dashboard stats
     */
    public function ajax_dashboard_stats() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        $stats = array(
            'today_bots' => $this->get_bot_count(),
            'today_revenue' => $this->get_revenue_potential(),
            'total_bots' => $this->get_total_bot_count(),
            'total_revenue' => $this->get_total_revenue()
        );
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX handler for bot activity
     */
    public function ajax_bot_activity() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        ob_start();
        $this->display_live_activity();
        $activity = ob_get_clean();
        
        wp_send_json_success(array('activity' => $activity));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Frontend scripts if needed
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'paypercrawl') === false) {
            return;
        }
        
        $admin_js_file = PAYPERCRAWL_PLUGIN_DIR . 'assets/admin.js';
        if (file_exists($admin_js_file)) {
            wp_enqueue_script(
                'paypercrawl-admin',
                PAYPERCRAWL_PLUGIN_URL . 'assets/admin.js',
                array('jquery'),
                PAYPERCRAWL_VERSION,
                true
            );
            
            wp_localize_script('paypercrawl-admin', 'paypercrawl_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('paypercrawl_nonce')
            ));
        }
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        // Get statistics
        $today_bots = $this->get_bot_count();
        $today_revenue = $this->get_revenue_potential();
        $total_bots = $this->get_total_bot_count();
        $total_revenue = $this->get_total_revenue();
        $pages_protected = wp_count_posts()->publish;
        $active_crawlers = $this->get_active_crawlers_count();
        
        ?>
        <div class="wrap paypercrawl-dashboard">
            <h1><?php _e('Pay Per Crawl Dashboard', 'pay-per-crawl'); ?></h1>
            
            <!-- Stats Grid -->
            <div class="ppc-stats-grid">
                <div class="ppc-stat-card primary">
                    <h3><?php _e('Today\'s Revenue Potential', 'pay-per-crawl'); ?></h3>
                    <div class="ppc-stat-value">$<?php echo number_format($today_revenue, 2); ?></div>
                </div>
                
                <div class="ppc-stat-card">
                    <h3><?php _e('AI Bots Today', 'pay-per-crawl'); ?></h3>
                    <div class="ppc-stat-value"><?php echo $today_bots; ?></div>
                </div>
                
                <div class="ppc-stat-card">
                    <h3><?php _e('Total Revenue Potential', 'pay-per-crawl'); ?></h3>
                    <div class="ppc-stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
                </div>
                
                <div class="ppc-stat-card">
                    <h3><?php _e('Protected Pages', 'pay-per-crawl'); ?></h3>
                    <div class="ppc-stat-value"><?php echo $pages_protected; ?></div>
                </div>
            </div>
            
            <!-- Activity Feed -->
            <div class="ppc-activity-section">
                <h2><?php _e('Recent Bot Activity', 'pay-per-crawl'); ?></h2>
                <div id="ppc-bot-activity">
                    <?php $this->display_live_activity(); ?>
                </div>
            </div>
        </div>
        
        <?php $this->render_dashboard_styles(); ?>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Analytics', 'pay-per-crawl') . '</h1>';
        echo '<p>' . __('Detailed analytics and reporting.', 'pay-per-crawl') . '</p>';
        echo '</div>';
    }
    
    /**
     * Bots page
     */
    public function bots_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Bot Detection', 'pay-per-crawl') . '</h1>';
        echo '<p>' . __('Manage bot detection settings and view detailed bot profiles.', 'pay-per-crawl') . '</p>';
        echo '</div>';
    }
    
    /**
     * Revenue page
     */
    public function revenue_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Revenue', 'pay-per-crawl') . '</h1>';
        echo '<p>' . __('Configure payment settings and view revenue details.', 'pay-per-crawl') . '</p>';
        echo '</div>';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Settings', 'pay-per-crawl') . '</h1>';
        echo '<p>' . __('General plugin settings.', 'pay-per-crawl') . '</p>';
        echo '</div>';
    }
    
    /**
     * Support page
     */
    public function support_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Support', 'pay-per-crawl') . '</h1>';
        echo '<p>' . __('Get help and support.', 'pay-per-crawl') . '</p>';
        echo '</div>';
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure your Pay Per Crawl settings below.', 'pay-per-crawl') . '</p>';
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        // Sanitize input options
        return $input;
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'detection_enabled' => true,
            'api_key' => '',
            'revenue_tracking' => true
        );
        
        add_option('paypercrawl_options', $default_options);
    }
    
    /**
     * Get bot count for today
     */
    private function get_bot_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if ($this->table_exists($table_name)) {
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name} WHERE DATE(detected_at) = CURDATE()"
            );
        }
        
        return 0;
    }
    
    /**
     * Get revenue potential for today
     */
    private function get_revenue_potential() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if ($this->table_exists($table_name)) {
            return (float) $wpdb->get_var(
                "SELECT SUM(revenue) FROM {$table_name} WHERE DATE(detected_at) = CURDATE()"
            );
        }
        
        return 0.0;
    }
    
    /**
     * Get total bot count
     */
    private function get_total_bot_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if ($this->table_exists($table_name)) {
            return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        }
        
        return 0;
    }
    
    /**
     * Get total revenue potential
     */
    private function get_total_revenue() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if ($this->table_exists($table_name)) {
            return (float) $wpdb->get_var("SELECT SUM(revenue) FROM {$table_name}");
        }
        
        return 0.0;
    }
    
    /**
     * Get active crawlers count
     */
    private function get_active_crawlers_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if ($this->table_exists($table_name)) {
            return (int) $wpdb->get_var(
                "SELECT COUNT(DISTINCT bot_type) FROM {$table_name} WHERE DATE(detected_at) = CURDATE()"
            );
        }
        
        return 0;
    }
    
    /**
     * Display live activity
     */
    private function display_live_activity() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            echo '<p>' . __('No bot activity detected yet. System is actively monitoring...', 'pay-per-crawl') . '</p>';
            return;
        }
        
        $logs = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY detected_at DESC LIMIT 10"
        );
        
        if (empty($logs)) {
            echo '<p>' . __('No bot activity detected yet. System is actively monitoring...', 'pay-per-crawl') . '</p>';
            return;
        }
        
        echo '<ul class="ppc-activity-list">';
        foreach ($logs as $log) {
            $bot_info = isset($this->bot_signatures[$log->bot_type]) 
                ? $this->bot_signatures[$log->bot_type] 
                : array('company' => 'Unknown');
            
            echo '<li class="ppc-activity-item">';
            echo '<strong>' . esc_html($log->bot_type) . '</strong> ';
            echo 'from ' . esc_html($bot_info['company']) . ' ';
            echo '<span class="revenue">+$' . number_format($log->revenue, 3) . '</span> ';
            echo '<span class="time">' . human_time_diff(strtotime($log->detected_at)) . ' ago</span>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Render dashboard styles
     */
    private function render_dashboard_styles() {
        ?>
        <style>
        .paypercrawl-dashboard {
            max-width: 1200px;
        }
        .ppc-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .ppc-stat-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .ppc-stat-card.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .ppc-stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
        }
        .ppc-stat-value {
            font-size: 28px;
            font-weight: bold;
            line-height: 1;
        }
        .ppc-activity-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .ppc-activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .ppc-activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .ppc-activity-item:last-child {
            border-bottom: none;
        }
        .revenue {
            color: #28a745;
            font-weight: bold;
        }
        .time {
            color: #6c757d;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    /**
     * Check if table exists
     */
    private function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Main logs table
        $logs_table = $wpdb->prefix . 'paypercrawl_logs';
        $sql_logs = "CREATE TABLE {$logs_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bot_type varchar(255) NOT NULL,
            company varchar(255) DEFAULT '' NOT NULL,
            revenue decimal(10, 4) NOT NULL DEFAULT 0.0000,
            detected_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            url varchar(2083) DEFAULT '' NOT NULL,
            ip_address varchar(100) DEFAULT '' NOT NULL,
            user_agent text NOT NULL,
            PRIMARY KEY (id),
            KEY bot_type (bot_type),
            KEY detected_at (detected_at),
            KEY ip_address (ip_address)
        ) {$charset_collate};";
        
        dbDelta($sql_logs);
        
        // Requests tracking table
        $requests_table = $wpdb->prefix . 'paypercrawl_requests';
        $sql_requests = "CREATE TABLE {$requests_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(100) NOT NULL,
            request_time int(11) NOT NULL,
            PRIMARY KEY (id),
            KEY ip_time (ip_address, request_time)
        ) {$charset_collate};";
        
        dbDelta($sql_requests);
        
        // Check if tables were created successfully
        if (!$this->table_exists($logs_table) || !$this->table_exists($requests_table)) {
            throw new Exception('Failed to create database tables');
        }
    }
}

// Initialize the plugin
function paypercrawl_init() {
    return PayPerCrawl::get_instance();
}

// Hook initialization to plugins_loaded to ensure WordPress is fully loaded
add_action('plugins_loaded', 'paypercrawl_init');

// Prevent multiple initializations
if (!function_exists('paypercrawl_get_instance')) {
    function paypercrawl_get_instance() {
        return PayPerCrawl::get_instance();
    }
}
