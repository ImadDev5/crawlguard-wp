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
    exit;
}

// Define plugin constants
define('PAYPERCRAWL_VERSION', '3.0.0');
define('PAYPERCRAWL_PLUGIN_FILE', __FILE__);
define('PAYPERCRAWL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYPERCRAWL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PAYPERCRAWL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main PayPerCrawl Plugin Class
 */
class PayPerCrawl_Plugin {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
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
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // WordPress hooks
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // Bot detection hook
        add_action('wp', array($this, 'detect_bots'));
    }
    
    /**
     * Plugin initialization
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('pay-per-crawl', false, dirname(PAYPERCRAWL_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_database_tables();
        $this->set_default_options();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_detections';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bot_type varchar(100) NOT NULL,
            user_agent text,
            ip_address varchar(45) NOT NULL,
            page_url text,
            revenue decimal(10,4) NOT NULL DEFAULT 0.0500,
            detected_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bot_type (bot_type),
            KEY detected_at (detected_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        add_option('paypercrawl_detection_enabled', 1);
        add_option('paypercrawl_monetization_enabled', 1);
        add_option('paypercrawl_version', PAYPERCRAWL_VERSION);
        add_option('paypercrawl_install_date', current_time('mysql'));
        add_option('paypercrawl_revenue_per_crawl', 0.05);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Pay Per Crawl', 'pay-per-crawl'),
            __('Pay Per Crawl', 'pay-per-crawl'),
            'manage_options',
            'paypercrawl-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-money-alt',
            30
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            __('Dashboard', 'pay-per-crawl'),
            __('Dashboard', 'pay-per-crawl'),
            'manage_options',
            'paypercrawl-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            __('Settings', 'pay-per-crawl'),
            __('Settings', 'pay-per-crawl'),
            'manage_options',
            'paypercrawl-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $stats = $this->get_detection_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Pay Per Crawl Dashboard', 'pay-per-crawl'); ?></h1>
            
            <div class="ppc-dashboard">
                <div class="ppc-stats-grid">
                    <div class="ppc-stat-card">
                        <h3><?php _e('Total Detections', 'pay-per-crawl'); ?></h3>
                        <div class="ppc-stat-number"><?php echo number_format($stats['total_detections']); ?></div>
                    </div>
                    
                    <div class="ppc-stat-card">
                        <h3><?php _e('Total Revenue', 'pay-per-crawl'); ?></h3>
                        <div class="ppc-stat-number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    </div>
                    
                    <div class="ppc-stat-card">
                        <h3><?php _e('Today\'s Detections', 'pay-per-crawl'); ?></h3>
                        <div class="ppc-stat-number"><?php echo number_format($stats['today_detections']); ?></div>
                    </div>
                    
                    <div class="ppc-stat-card">
                        <h3><?php _e('Status', 'pay-per-crawl'); ?></h3>
                        <div class="ppc-status <?php echo get_option('paypercrawl_detection_enabled') ? 'active' : 'inactive'; ?>">
                            <?php echo get_option('paypercrawl_detection_enabled') ? __('Active', 'pay-per-crawl') : __('Inactive', 'pay-per-crawl'); ?>
                        </div>
                    </div>
                </div>
                
                <div class="ppc-recent-detections">
                    <h3><?php _e('Recent Bot Detections', 'pay-per-crawl'); ?></h3>
                    <?php $this->display_recent_detections(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $detection_enabled = get_option('paypercrawl_detection_enabled', 1);
        $revenue_per_crawl = get_option('paypercrawl_revenue_per_crawl', 0.05);
        ?>
        <div class="wrap">
            <h1><?php _e('Pay Per Crawl Settings', 'pay-per-crawl'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('paypercrawl_settings', 'paypercrawl_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Bot Detection', 'pay-per-crawl'); ?></th>
                        <td>
                            <input type="checkbox" name="detection_enabled" value="1" <?php checked($detection_enabled, 1); ?> />
                            <p class="description"><?php _e('Enable or disable AI bot detection', 'pay-per-crawl'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Revenue Per Crawl', 'pay-per-crawl'); ?></th>
                        <td>
                            <input type="number" name="revenue_per_crawl" value="<?php echo esc_attr($revenue_per_crawl); ?>" step="0.01" min="0" />
                            <p class="description"><?php _e('Amount earned per bot detection (USD)', 'pay-per-crawl'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['paypercrawl_nonce'], 'paypercrawl_settings')) {
            return;
        }
        
        update_option('paypercrawl_detection_enabled', isset($_POST['detection_enabled']) ? 1 : 0);
        update_option('paypercrawl_revenue_per_crawl', floatval($_POST['revenue_per_crawl']));
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'pay-per-crawl') . '</p></div>';
    }
    
    /**
     * Detect bots
     */
    public function detect_bots() {
        if (!get_option('paypercrawl_detection_enabled', 1)) {
            return;
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $bot_type = $this->identify_bot($user_agent);
        
        if ($bot_type) {
            $this->log_detection($bot_type, $user_agent);
        }
    }
    
    /**
     * Identify bot type from user agent
     */
    private function identify_bot($user_agent) {
        $bot_patterns = array(
            'ChatGPT' => array('ChatGPT-User', 'ChatGPT'),
            'Claude' => array('Claude-Web', 'anthropic-ai', 'ClaudeBot'),
            'GPTBot' => array('GPTBot'),
            'Google-Extended' => array('Google-Extended'),
            'CCBot' => array('CCBot'),
            'Bard' => array('Bard'),
            'Bing' => array('bingbot'),
        );
        
        foreach ($bot_patterns as $bot_name => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($user_agent, $pattern) !== false) {
                    return $bot_name;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Log bot detection
     */
    private function log_detection($bot_type, $user_agent) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_detections';
        $revenue = get_option('paypercrawl_revenue_per_crawl', 0.05);
        
        $wpdb->insert(
            $table_name,
            array(
                'bot_type' => $bot_type,
                'user_agent' => $user_agent,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'page_url' => $_SERVER['REQUEST_URI'] ?? '',
                'revenue' => $revenue,
                'detected_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%f', '%s')
        );
    }
    
    /**
     * Get detection statistics
     */
    private function get_detection_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_detections';
        
        $total_detections = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table_name");
        $today_detections = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(detected_at) = %s",
            current_time('Y-m-d')
        ));
        
        return array(
            'total_detections' => intval($total_detections),
            'total_revenue' => floatval($total_revenue),
            'today_detections' => intval($today_detections)
        );
    }
    
    /**
     * Display recent detections
     */
    private function display_recent_detections() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_detections';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY detected_at DESC LIMIT 10");
        
        if (empty($results)) {
            echo '<p>' . __('No bot detections yet.', 'pay-per-crawl') . '</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Bot Type', 'pay-per-crawl'); ?></th>
                    <th><?php _e('IP Address', 'pay-per-crawl'); ?></th>
                    <th><?php _e('Page', 'pay-per-crawl'); ?></th>
                    <th><?php _e('Revenue', 'pay-per-crawl'); ?></th>
                    <th><?php _e('Date', 'pay-per-crawl'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $detection): ?>
                <tr>
                    <td><strong><?php echo esc_html($detection->bot_type); ?></strong></td>
                    <td><?php echo esc_html($detection->ip_address); ?></td>
                    <td><?php echo esc_html($detection->page_url); ?></td>
                    <td>$<?php echo number_format($detection->revenue, 2); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($detection->detected_at)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'paypercrawl') === false) {
            return;
        }
        
        wp_enqueue_style(
            'paypercrawl-admin',
            PAYPERCRAWL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PAYPERCRAWL_VERSION
        );
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function frontend_enqueue_scripts() {
        // Frontend scripts if needed
    }
}

// Initialize the plugin
PayPerCrawl_Plugin::get_instance();