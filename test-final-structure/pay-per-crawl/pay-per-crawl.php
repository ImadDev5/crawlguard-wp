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
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main PayPerCrawl Plugin Class
 */
class PayPerCrawl {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // WordPress hooks
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('init', array($this, 'detect_bots'));
    }
    
    public function activate() {
        global $wpdb;
        
        // Create database table
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bot_type varchar(100) NOT NULL,
            user_agent text,
            ip_address varchar(45) NOT NULL,
            revenue decimal(10,4) NOT NULL DEFAULT 0.0500,
            detected_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set default options
        add_option('paypercrawl_detection_enabled', 1);
        add_option('paypercrawl_revenue_per_crawl', 0.05);
    }
    
    public function admin_menu() {
        add_menu_page(
            'Pay Per Crawl',
            'Pay Per Crawl',
            'manage_options',
            'paypercrawl',
            array($this, 'dashboard_page'),
            'dashicons-money-alt',
            30
        );
    }
    
    public function dashboard_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        $total_detections = $wpdb->get_var("SELECT COUNT(*) FROM $table_name") ?: 0;
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table_name") ?: 0;
        
        ?>
        <div class="wrap">
            <h1>💰 Pay Per Crawl Dashboard</h1>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3 style="margin: 0 0 10px 0; color: #23282d;">Total Detections</h3>
                    <div style="font-size: 32px; font-weight: bold; color: #0073aa;"><?php echo number_format($total_detections); ?></div>
                </div>
                
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3 style="margin: 0 0 10px 0; color: #23282d;">Total Revenue</h3>
                    <div style="font-size: 32px; font-weight: bold; color: #46b450;">$<?php echo number_format($total_revenue, 2); ?></div>
                </div>
                
                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3 style="margin: 0 0 10px 0; color: #23282d;">Status</h3>
                    <div style="font-size: 18px; font-weight: bold; color: #46b450; background: #ecf7ed; padding: 8px 16px; border-radius: 4px;">ACTIVE</div>
                </div>
            </div>
            
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h3>🤖 AI Bot Detection</h3>
                <p>Your website is actively monitoring for AI bot traffic from:</p>
                <ul>
                    <li><strong>ChatGPT</strong> - OpenAI's bot</li>
                    <li><strong>Claude</strong> - Anthropic's bot</li>
                    <li><strong>GPTBot</strong> - OpenAI crawler</li>
                    <li><strong>Google-Extended</strong> - Google's AI bot</li>
                    <li><strong>CCBot</strong> - Common Crawl bot</li>
                </ul>
                <p>Every bot detection earns you <strong>$0.05</strong> in revenue!</p>
            </div>
            
            <?php
            // Show recent detections
            $recent = $wpdb->get_results("SELECT * FROM $table_name ORDER BY detected_at DESC LIMIT 5");
            if (!empty($recent)) {
                echo '<div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin: 20px 0;">';
                echo '<h3>Recent Bot Detections</h3>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Bot Type</th><th>IP Address</th><th>Revenue</th><th>Date</th></tr></thead>';
                echo '<tbody>';
                foreach ($recent as $detection) {
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($detection->bot_type) . '</strong></td>';
                    echo '<td>' . esc_html($detection->ip_address) . '</td>';
                    echo '<td>$' . number_format($detection->revenue, 2) . '</td>';
                    echo '<td>' . date('M j, Y g:i A', strtotime($detection->detected_at)) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            }
            ?>
        </div>
        <?php
    }
    
    public function detect_bots() {
        if (!get_option('paypercrawl_detection_enabled', 1)) {
            return;
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Bot signatures to detect
        $bots = array(
            'ChatGPT-User' => 'ChatGPT',
            'Claude-Web' => 'Claude',
            'GPTBot' => 'GPTBot',
            'Google-Extended' => 'Google-Extended',
            'CCBot' => 'CCBot',
            'anthropic-ai' => 'Claude',
            'ClaudeBot' => 'Claude'
        );
        
        foreach ($bots as $signature => $bot_name) {
            if (stripos($user_agent, $signature) !== false) {
                $this->log_detection($bot_name, $user_agent);
                break;
            }
        }
    }
    
    private function log_detection($bot_type, $user_agent) {
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
                'detected_at' => current_time('mysql')
            )
        );
    }
}

// Initialize the plugin
PayPerCrawl::get_instance();
