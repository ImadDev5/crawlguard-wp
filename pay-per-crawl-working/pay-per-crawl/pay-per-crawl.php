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

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PAYPERCRAWL_VERSION', '3.0.0');
define('PAYPERCRAWL_PLUGIN_FILE', __FILE__);
define('PAYPERCRAWL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYPERCRAWL_PLUGIN_URL', plugin_dir_url(__FILE__));

class PayPerCrawl {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    public function init() {
        // Bot detection logic here
        if (get_option('paypercrawl_detection_enabled', true)) {
            $this->detect_bot();
        }
    }
    
    public function admin_menu() {
        add_menu_page(
            'Pay Per Crawl',
            'Pay Per Crawl',
            'manage_options',
            'paypercrawl-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-money-alt',
            30
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'paypercrawl-settings',
            array($this, 'settings_page')
        );
    }
    
    public function dashboard_page() {
        $stats = $this->get_stats();
        ?>
        <div class="wrap">
            <h1>💰 Pay Per Crawl Dashboard</h1>
            <div class="ppc-dashboard">
                <div class="ppc-stats-grid">
                    <div class="ppc-stat-card">
                        <h3>Total Detections</h3>
                        <div class="ppc-stat-number"><?php echo number_format($stats['total_detections']); ?></div>
                    </div>
                    
                    <div class="ppc-stat-card">
                        <h3>Total Revenue</h3>
                        <div class="ppc-stat-number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    </div>
                    
                    <div class="ppc-stat-card">
                        <h3>Today's Detections</h3>
                        <div class="ppc-stat-number"><?php echo number_format($stats['today_detections']); ?></div>
                    </div>
                    
                    <div class="ppc-stat-card">
                        <h3>Status</h3>
                        <div class="ppc-status <?php echo get_option('paypercrawl_detection_enabled') ? 'active' : 'inactive'; ?>">
                            <?php echo get_option('paypercrawl_detection_enabled') ? 'Active' : 'Inactive'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="ppc-card">
                    <h3>🤖 Bot Detection Active</h3>
                    <p>Your site is now monitoring for AI bot traffic and earning revenue!</p>
                    <p><strong>Supported Bots:</strong> ChatGPT, Claude, GPTBot, Google-Extended, CCBot, Bard, Bing</p>
                </div>
                
                <?php $this->display_recent_detections(); ?>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $detection_enabled = get_option('paypercrawl_detection_enabled', true);
        $revenue_per_crawl = get_option('paypercrawl_revenue_per_crawl', 0.05);
        ?>
        <div class="wrap">
            <h1>⚙️ Pay Per Crawl Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('paypercrawl_settings', 'paypercrawl_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Bot Detection</th>
                        <td>
                            <input type="checkbox" name="detection_enabled" value="1" <?php checked($detection_enabled, 1); ?> />
                            <p class="description">Enable or disable AI bot detection</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Revenue Per Crawl</th>
                        <td>
                            <input type="number" name="revenue_per_crawl" value="<?php echo esc_attr($revenue_per_crawl); ?>" step="0.01" min="0" />
                            <p class="description">Amount earned per bot detection (USD)</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['paypercrawl_nonce'], 'paypercrawl_settings')) {
            return;
        }
        
        update_option('paypercrawl_detection_enabled', isset($_POST['detection_enabled']) ? 1 : 0);
        update_option('paypercrawl_revenue_per_crawl', floatval($_POST['revenue_per_crawl']));
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    private function detect_bot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $bot_signatures = array(
            'ChatGPT' => 'ChatGPT-User',
            'Claude' => 'Claude-Web',
            'GPTBot' => 'GPTBot',
            'Google-Extended' => 'Google-Extended',
            'CCBot' => 'CCBot',
            'anthropic-ai' => 'anthropic-ai',
            'ClaudeBot' => 'ClaudeBot',
            'Bard' => 'Bard',
            'bingbot' => 'bingbot'
        );
        
        foreach ($bot_signatures as $bot_name => $signature) {
            if (stripos($user_agent, $signature) !== false) {
                $this->log_bot_detection($bot_name, $user_agent);
                break;
            }
        }
    }
    
    private function log_bot_detection($bot_type, $user_agent) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        $revenue = get_option('paypercrawl_revenue_per_crawl', 0.05);
        
        $wpdb->insert(
            $table_name,
            array(
                'bot_type' => $bot_type,
                'user_agent' => $user_agent,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'revenue' => $revenue,
                'page_url' => $_SERVER['REQUEST_URI'] ?? '',
                'detected_at' => current_time('mysql')
            )
        );
    }
    
    private function get_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $total_detections = $wpdb->get_var("SELECT COUNT(*) FROM $table_name") ?: 0;
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table_name") ?: 0;
        $today_detections = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(detected_at) = %s",
            current_time('Y-m-d')
        )) ?: 0;
        
        return array(
            'total_detections' => intval($total_detections),
            'total_revenue' => floatval($total_revenue),
            'today_detections' => intval($today_detections)
        );
    }
    
    private function display_recent_detections() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY detected_at DESC LIMIT 10");
        
        echo '<div class="ppc-card">';
        echo '<h3>Recent Bot Detections</h3>';
        
        if (empty($results)) {
            echo '<p>No bot detections yet. When AI bots visit your site, they will appear here!</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Bot Type</th><th>IP Address</th><th>Revenue</th><th>Date</th></tr></thead>';
            echo '<tbody>';
            foreach ($results as $detection) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($detection->bot_type) . '</strong></td>';
                echo '<td>' . esc_html($detection->ip_address) . '</td>';
                echo '<td>$' . number_format($detection->revenue, 2) . '</td>';
                echo '<td>' . date('M j, Y g:i A', strtotime($detection->detected_at)) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        echo '</div>';
    }
    
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bot_type varchar(100) NOT NULL,
            user_agent text,
            ip_address varchar(45) NOT NULL,
            revenue decimal(10,4) NOT NULL DEFAULT 0,
            page_url text,
            detected_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bot_type (bot_type),
            KEY detected_at (detected_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function activate() {
        $this->create_tables();
        add_option('paypercrawl_detection_enabled', true);
        add_option('paypercrawl_revenue_per_crawl', 0.05);
        add_option('paypercrawl_version', PAYPERCRAWL_VERSION);
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function enqueue_scripts() {
        // Frontend scripts if needed
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'paypercrawl') === false) return;
        
        wp_enqueue_style('paypercrawl-admin', PAYPERCRAWL_PLUGIN_URL . 'assets/admin.css', [], PAYPERCRAWL_VERSION);
    }
}

// Initialize the plugin
PayPerCrawl::get_instance();
