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
define('PAYPERCRAWL_API_URL', 'https://crawlguard-api-prod.crawlguard-api.workers.dev/');
define('PAYPERCRAWL_STANDALONE_MODE', false); // Using existing backend infrastructure

class PayPerCrawl {
    private static $instance = null;
    private $api_key = null;
    private $bot_signatures = [];
    private $bot_detector;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Enhanced bot detection with multiple hooks
        add_action('wp', array($this, 'detect_bot'));
        add_action('template_redirect', array($this, 'advanced_bot_detection'));
        
        // AJAX handlers for real-time dashboard updates
        add_action('wp_ajax_paypercrawl_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_paypercrawl_bot_activity', array($this, 'ajax_bot_activity'));
        
        register_activation_hook(PAYPERCRAWL_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(PAYPERCRAWL_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Load dependencies after hooks are set
        $this->load_dependencies();
    }
    }
    
    public function activate() {
        $this->create_tables();
        // Schedule cron jobs, set default options etc.
    }

    public function deactivate() {
        // Clean up cron jobs if any
    }

    public function admin_init() {
        // Register settings
        register_setting('paypercrawl_settings', 'paypercrawl_options');
        
        // Add settings sections
        add_settings_section(
            'paypercrawl_general',
            'General Settings',
            array($this, 'settings_section_callback'),
            'paypercrawl'
        );
    }
    
    private function load_bot_signatures() {
        // This is now loaded from the bot detector class
    }
    
    public function init() {
        // create_tables is now called on activation
    }
    
    private function load_dependencies() {
        require_once PAYPERCRAWL_PLUGIN_DIR . 'includes/class-paypercrawl-bot-detector.php';
        require_once PAYPERCRAWL_PLUGIN_DIR . 'includes/class-paypercrawl-analytics.php';
        
        // Initialize bot detector
        $this->bot_detector = new PayPerCrawl_Bot_Detector();
        
        // Load bot signatures
        $this->bot_signatures = $this->bot_detector->get_bot_signatures();
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Pay Per Crawl',
            'Pay Per Crawl',
            'manage_options',
            'paypercrawl-dashboard',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'paypercrawl-dashboard',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Analytics',
            '📈 Analytics',
            'manage_options',
            'paypercrawl-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Bot Detection',
            '🤖 Bot Detection',
            'manage_options',
            'paypercrawl-bots',
            array($this, 'bots_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Revenue Settings',
            '💰 Revenue',
            'manage_options',
            'paypercrawl-revenue',
            array($this, 'revenue_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Settings',
            '⚙️ Settings',
            'manage_options',
            'paypercrawl-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Support',
            '🆘 Support',
            'manage_options',
            'paypercrawl-support',
            array($this, 'support_page')
        );
    }
    
    public function detect_bot() {
        // Make sure bot detector is loaded
        if (!$this->bot_detector) {
            return;
        }
        
        $bot_info = $this->bot_detector->detect_bot();
        if ($bot_info) {
            $current_url = $this->get_current_url();
            $ip_address = $this->bot_detector->get_client_ip();
            $this->bot_detector->log_detection($bot_info, $current_url, $ip_address);
        }
    }

    private function get_current_url() {
        if (isset($_SERVER['REQUEST_URI'])) {
            return home_url($_SERVER['REQUEST_URI']);
        }
        return home_url();
    }

    public function advanced_bot_detection() {
        // This can be a secondary, more aggressive detection layer if needed
        // For now, the main detection is in detect_bot
    }

    public function admin_page() {
        // Get real-time statistics
        $today_bots = $this->get_bot_count();
        $today_revenue = $this->get_revenue_potential();
        $total_bots = $this->get_total_bot_count();
        $total_revenue = $this->get_total_revenue();
        $pages_protected = wp_count_posts()->publish;
        $active_crawlers = $this->get_active_crawlers_count();
        
        ?>
        <div class="wrap paypercrawl-dashboard">
            <!-- Header Section -->
            <div class="ppc-header">
                <div class="ppc-header-content">
                    <div class="ppc-logo-section">
                        <h1>💰 Pay Per Crawl</h1>
                        <p class="ppc-tagline">Turn Every AI Crawl Into Revenue</p>
                    </div>
                    <div class="ppc-header-stats">
                        <div class="ppc-mini-stat">
                            <span class="ppc-mini-value">$<?php echo number_format($today_revenue, 2); ?></span>
                            <span class="ppc-mini-label">Today's Revenue</span>
                        </div>
                        <div class="ppc-mini-stat">
                            <span class="ppc-mini-value"><?php echo $today_bots; ?></span>
                            <span class="ppc-mini-label">Bots Detected</span>
                        </div>
                    </div>
                </div>
                <div class="ppc-status-indicator <?php echo $this->get_system_status(); ?>">
                    <span class="ppc-status-dot"></span>
                    <span class="ppc-status-text"><?php echo $this->get_system_status_text(); ?></span>
                </div>
            </div>

            <!-- Main Statistics Grid -->
            <div class="ppc-stats-grid">
                <div class="ppc-stat-card primary">
                    <div class="ppc-stat-header">
                        <span class="ppc-stat-icon">💰</span>
                        <h3>Today's Revenue Potential</h3>
                    </div>
                    <div class="ppc-stat-value">$<?php echo number_format($today_revenue, 2); ?></div>
                    <div class="ppc-stat-change positive">
                        <span>↗️ +<?php echo $this->get_revenue_change(); ?>%</span>
                        <span>vs yesterday</span>
                    </div>
                </div>

                <div class="ppc-stat-card">
                    <div class="ppc-stat-header">
                        <span class="ppc-stat-icon">🤖</span>
                        <h3>AI Bots Today</h3>
                    </div>
                    <div class="ppc-stat-value"><?php echo $today_bots; ?></div>
                    <div class="ppc-stat-meta">
                        <span><?php echo $active_crawlers; ?> active crawler types</span>
                    </div>
                </div>

                <div class="ppc-stat-card">
                    <div class="ppc-stat-header">
                        <span class="ppc-stat-icon">📊</span>
                        <h3>Total Revenue Potential</h3>
                    </div>
                    <div class="ppc-stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="ppc-stat-meta">
                        <span>From <?php echo $total_bots; ?> total detections</span>
                    </div>
                </div>

                <div class="ppc-stat-card">
                    <div class="ppc-stat-header">
                        <span class="ppc-stat-icon">🛡️</span>
                        <h3>Protected Pages</h3>
                    </div>
                    <div class="ppc-stat-value"><?php echo $pages_protected; ?></div>
                    <div class="ppc-stat-meta">
                        <span>Active monitoring</span>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="ppc-two-column">
                <!-- Left Column: Revenue Analytics -->
                <div class="ppc-column-left">
                    <div class="ppc-card">
                        <div class="ppc-card-header">
                            <h3>📈 Revenue Analytics</h3>
                            <div class="ppc-card-actions">
                                <select id="ppc-timeframe">
                                    <option value="7d">Last 7 days</option>
                                    <option value="30d" selected>Last 30 days</option>
                                    <option value="90d">Last 90 days</option>
                                </select>
                            </div>
                        </div>
                        <div class="ppc-chart-container">
                            <canvas id="ppc-revenue-chart"></canvas>
                        </div>
                        <div class="ppc-revenue-breakdown">
                            <h4>Top Revenue Bots</h4>
                            <?php $this->display_top_bots(); ?>
                        </div>
                    </div>

                    <div class="ppc-card">
                        <div class="ppc-card-header">
                            <h3>🎯 Revenue Optimization</h3>
                        </div>
                        <div class="ppc-optimization-tips">
                            <div class="ppc-tip">
                                <span class="ppc-tip-icon">💡</span>
                                <div>
                                    <strong>Increase Your Rates</strong>
                                    <p>Premium bots like GPT-4 can pay up to $0.15 per crawl. Adjust rates for maximum revenue.</p>
                                    <a href="<?php echo admin_url('admin.php?page=paypercrawl-revenue'); ?>" class="button button-small">Optimize Rates</a>
                                </div>
                            </div>
                            <div class="ppc-tip">
                                <span class="ppc-tip-icon">🚀</span>
                                <div>
                                    <strong>Enable Auto-Scaling</strong>
                                    <p>Automatically adjust rates based on demand and bot value.</p>
                                    <a href="#" class="button button-small ppc-enable-autoscale">Enable</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Activity & Setup -->
                <div class="ppc-column-right">
                    <div class="ppc-card">
                        <div class="ppc-card-header">
                            <h3>🕒 Live Bot Activity</h3>
                            <button id="ppc-refresh-activity" class="button button-small">🔄 Refresh</button>
                        </div>
                        <div id="ppc-bot-activity" class="ppc-activity-feed">
                            <?php $this->display_live_activity(); ?>
                        </div>
                    </div>

                    <div class="ppc-card">
                        <div class="ppc-card-header">
                            <h3>⚡ Quick Setup</h3>
                        </div>
                        <div class="ppc-setup-checklist">
                            <?php $this->display_setup_checklist(); ?>
                        </div>
                    </div>

                    <div class="ppc-card">
                        <div class="ppc-card-header">
                            <h3>🌐 PayPerCrawl.tech</h3>
                        </div>
                        <div class="ppc-branding">
                            <p>Visit our website for advanced features:</p>
                            <ul>
                                <li>� Premium Bot Detection</li>
                                <li>📊 Advanced Analytics</li>
                                <li>💰 Higher Revenue Rates</li>
                                <li>🛠️ API Access</li>
                            </ul>
                            <a href="https://paypercrawl.tech" target="_blank" class="button button-primary">Visit PayPerCrawl.tech</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $this->render_dashboard_styles(); ?>
        <?php $this->render_dashboard_scripts(); ?>
        <?php
    }
    
    // Helper functions for the new dashboard
    private function get_system_status() {
        $detection_enabled = get_option('paypercrawl_detection_enabled', true);
        $api_connected = $this->is_api_connected();
        
        if ($detection_enabled && $api_connected) {
            return 'online';
        } elseif ($detection_enabled) {
            return 'warning';
        } else {
            return 'offline';
        }
    }
    
    private function get_system_status_text() {
        $status = $this->get_system_status();
        switch ($status) {
            case 'online':
                return 'System Online & Monitoring';
            case 'warning':
                return 'API Disconnected';
            case 'offline':
                return 'System Offline';
            default:
                return 'Unknown Status';
        }
    }
    
    private function get_revenue_change() {
        global $wpdb;
        $today = $wpdb->get_var("SELECT SUM(revenue) FROM {$wpdb->prefix}paypercrawl_logs WHERE DATE(detected_at) = CURDATE()") ?? 0;
        $yesterday = $wpdb->get_var("SELECT SUM(revenue) FROM {$wpdb->prefix}paypercrawl_logs WHERE DATE(detected_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)") ?? 1;
        
        if ($yesterday == 0) $yesterday = 1; // Avoid division by zero
        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }
    
    private function get_total_revenue() {
        global $wpdb;
        return $wpdb->get_var("SELECT SUM(revenue) FROM {$wpdb->prefix}paypercrawl_logs") ?? 0;
    }
    
    private function get_active_crawlers_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(DISTINCT bot_type) FROM {$wpdb->prefix}paypercrawl_logs WHERE DATE(detected_at) = CURDATE()") ?? 0;
    }
    
    private function is_api_connected() {
        // In standalone mode, return false to show setup prompts
        if (defined('PAYPERCRAWL_STANDALONE_MODE') && PAYPERCRAWL_STANDALONE_MODE) {
            return false;
        }
        
        // Check if API key is configured and valid
        $api_key = get_option('paypercrawl_api_key');
        return !empty($api_key);
    }
    
    private function display_top_bots() {
        global $wpdb;
        $top_bots = $wpdb->get_results("
            SELECT bot_type, COUNT(*) as count, SUM(revenue) as total_revenue 
            FROM {$wpdb->prefix}paypercrawl_logs 
            WHERE detected_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            GROUP BY bot_type 
            ORDER BY total_revenue DESC 
            LIMIT 5
        ");
        
        if (empty($top_bots)) {
            echo '<p class="ppc-no-data">No bot data available yet. Start by getting some AI bot visits!</p>';
            return;
        }
        
        echo '<div class="ppc-top-bots">';
        foreach ($top_bots as $bot) {
            $bot_info = isset($this->bot_signatures[$bot->bot_type]) ? $this->bot_signatures[$bot->bot_type] : ['company' => 'Unknown', 'type' => 'other'];
            echo '<div class="ppc-bot-item">';
            echo '<div class="ppc-bot-info">';
            echo '<span class="ppc-bot-name">' . esc_html($bot->bot_type) . '</span>';
            echo '<span class="ppc-bot-company">' . esc_html($bot_info['company']) . '</span>';
            echo '</div>';
            echo '<div class="ppc-bot-stats">';
            echo '<span class="ppc-bot-revenue">$' . number_format($bot->total_revenue, 2) . '</span>';
            echo '<span class="ppc-bot-count">' . $bot->count . ' visits</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    private function display_live_activity() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}paypercrawl_logs'");
        if (!$table_exists) {
            echo '<div class="ppc-activity-empty">';
            echo '<p>🤖 Bot detection is active. Activity will appear here as AI bots visit your site.</p>';
            echo '</div>';
            return;
        }
        
        $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}paypercrawl_logs ORDER BY detected_at DESC LIMIT 8");
        
        if (empty($logs)) {
            echo '<div class="ppc-activity-empty">';
            echo '<div class="ppc-activity-item">';
            echo '<span class="ppc-activity-icon">🤖</span>';
            echo '<div class="ppc-activity-details">';
            echo '<span class="ppc-activity-text">No bot activity detected yet</span>';
            echo '<span class="ppc-activity-time">System is actively monitoring...</span>';
            echo '</div>';
            echo '</div>';
            echo '<div class="ppc-activity-item">';
            echo '<span class="ppc-activity-icon">💡</span>';
            echo '<div class="ppc-activity-details">';
            echo '<span class="ppc-activity-text">AI bots like ChatGPT and Claude will automatically be detected</span>';
            echo '<span class="ppc-activity-time">Visit <a href="https://paypercrawl.tech" target="_blank">PayPerCrawl.tech</a> for tips</span>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            return;
        }
        
        echo '<div class="ppc-activity-list">';
        foreach ($logs as $log) {
            $bot_info = isset($this->bot_signatures[$log->bot_type]) ? $this->bot_signatures[$log->bot_type] : ['company' => 'Unknown', 'type' => 'other'];
            echo '<div class="ppc-activity-item">';
            echo '<span class="ppc-activity-icon">' . $this->get_bot_icon($bot_info['type']) . '</span>';
            echo '<div class="ppc-activity-details">';
            echo '<span class="ppc-activity-text"><strong>' . esc_html($log->bot_type) . '</strong> from ' . esc_html($bot_info['company']) . '</span>';
            echo '<span class="ppc-activity-time">' . human_time_diff(strtotime($log->detected_at)) . ' ago</span>';
            echo '</div>';
            echo '<div class="ppc-activity-revenue">+$' . number_format($log->revenue, 3) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    private function get_bot_icon($type) {
        switch ($type) {
            case 'premium': return '💎';
            case 'standard': return '🤖';
            case 'emerging': return '🚀';
            case 'research': return '🔬';
            case 'scraper': return '🕷️';
            default: return '🤖';
        }
    }
    
    private function display_setup_checklist() {
        $detection_enabled = get_option('paypercrawl_detection_enabled', true);
        $api_configured = $this->is_api_connected();
        $payment_configured = $this->is_payment_configured();
        
        echo '<div class="ppc-checklist">';
        
        // Step 1: Plugin Activation
        echo '<div class="ppc-checklist-item completed">';
        echo '<span class="ppc-checklist-icon">✅</span>';
        echo '<div class="ppc-checklist-content">';
        echo '<h4>Plugin Activated</h4>';
        echo '<p>Pay Per Crawl is successfully installed and running.</p>';
        echo '</div>';
        echo '</div>';
        
        // Step 2: Bot Detection
        echo '<div class="ppc-checklist-item ' . ($detection_enabled ? 'completed' : 'pending') . '">';
        echo '<span class="ppc-checklist-icon">' . ($detection_enabled ? '✅' : '⏳') . '</span>';
        echo '<div class="ppc-checklist-content">';
        echo '<h4>Bot Detection ' . ($detection_enabled ? 'Enabled' : 'Disabled') . '</h4>';
        echo '<p>AI bot detection and logging system.</p>';
        if (!$detection_enabled) {
            echo '<a href="' . admin_url('admin.php?page=paypercrawl-settings') . '" class="button button-small">Enable Detection</a>';
        }
        echo '</div>';
        echo '</div>';
        
        // Step 3: API Configuration
        echo '<div class="ppc-checklist-item ' . ($api_configured ? 'completed' : 'pending') . '">';
        echo '<span class="ppc-checklist-icon">' . ($api_configured ? '✅' : '⏳') . '</span>';
        echo '<div class="ppc-checklist-content">';
        echo '<h4>API Configuration</h4>';
        echo '<p>Connect to PayPerCrawl.tech for enhanced features.</p>';
        if (!$api_configured) {
            echo '<a href="' . admin_url('admin.php?page=paypercrawl-settings') . '" class="button button-small">Configure API</a>';
        }
        echo '</div>';
        echo '</div>';
        
        // Step 4: Payment Setup
        echo '<div class="ppc-checklist-item ' . ($payment_configured ? 'completed' : 'pending') . '">';
        echo '<span class="ppc-checklist-icon">' . ($payment_configured ? '✅' : '💰') . '</span>';
        echo '<div class="ppc-checklist-content">';
        echo '<h4>Payment Configuration</h4>';
        echo '<p>Setup automatic revenue collection.</p>';
        if (!$payment_configured) {
            echo '<a href="' . admin_url('admin.php?page=paypercrawl-revenue') . '" class="button button-small">Setup Payments</a>';
        }
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    private function is_payment_configured() {
        $stripe_key = get_option('paypercrawl_stripe_key');
        return !empty($stripe_key);
    }
    
    private function render_dashboard_styles() {
        ?>
        <style>
        /* Pay Per Crawl Dashboard Styles */
        .paypercrawl-dashboard {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* Header Section */
        .ppc-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .ppc-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.1;
        }
        
        .ppc-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .ppc-logo-section h1 {
            font-size: 2.5em;
            margin: 0;
            font-weight: 700;
            color: #fff;
        }
        
        .ppc-tagline {
            font-size: 1.2em;
            opacity: 0.9;
            margin: 5px 0 0 0;
        }
        
        .ppc-header-stats {
            display: flex;
            gap: 30px;
        }
        
        .ppc-mini-stat {
            text-align: center;
        }
        
        .ppc-mini-value {
            display: block;
            font-size: 1.8em;
            font-weight: bold;
            line-height: 1;
        }
        
        .ppc-mini-label {
            display: block;
            font-size: 0.9em;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .ppc-status-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            color: #fff;
        }
        
        .ppc-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ade80;
        }
        
        .ppc-status-indicator.warning .ppc-status-dot { background: #fbbf24; }
        .ppc-status-indicator.offline .ppc-status-dot { background: #ef4444; }
        
        /* Stats Grid */
        .ppc-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .ppc-stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .ppc-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .ppc-stat-card.primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .ppc-stat-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .ppc-stat-icon {
            font-size: 1.8em;
        }
        
        .ppc-stat-header h3 {
            margin: 0;
            font-size: 1.1em;
            font-weight: 600;
            color: #1f2937;
        }

        .ppc-stat-card.primary .ppc-stat-header h3 {
            color: #fff;
        }
        
        .ppc-stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
            line-height: 1;
        }
        
        .ppc-stat-change {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
        }
        
        .ppc-stat-change.positive {
            color: #10b981;
        }

        .ppc-stat-card.primary .ppc-stat-change {
            color: rgba(255,255,255,0.9);
        }
        
        .ppc-stat-meta {
            color: #6b7280;
            font-size: 0.9em;
        }
        
        .ppc-stat-card.primary .ppc-stat-meta {
            color: rgba(255,255,255,0.8);
        }
        
        /* Two Column Layout */
        .ppc-two-column {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 1200px) {
            .ppc-two-column {
                grid-template-columns: 1fr;
            }
        }
        
        /* Cards */
        .ppc-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 25px;
        }
        
        .ppc-card-header {
            padding: 20px 25px 15px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ppc-card-header h3 {
            margin: 0;
            font-size: 1.2em;
            font-weight: 600;
            color: #1f2937;
        }
        
        .ppc-card-actions select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }
        
        /* Chart Container */
        .ppc-chart-container {
            padding: 25px;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            color: #6b7280;
        }
        
        /* Revenue Breakdown */
        .ppc-revenue-breakdown {
            padding: 20px 25px;
        }
        
        .ppc-revenue-breakdown h4 {
            margin: 0 0 15px 0;
            font-size: 1em;
            font-weight: 600;
            color: #374151;
        }
        
        .ppc-top-bots {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .ppc-bot-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .ppc-bot-name {
            font-weight: 600;
            display: block;
            color: #1f2937;
        }
        
        .ppc-bot-company {
            font-size: 0.85em;
            color: #6b7280;
        }
        
        .ppc-bot-revenue {
            font-weight: 600;
            color: #10b981;
            display: block;
        }
        
        .ppc-bot-count {
            font-size: 0.85em;
            color: #6b7280;
        }
        
        .ppc-no-data {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            padding: 20px;
        }
        
        /* Optimization Tips */
        .ppc-optimization-tips {
            padding: 20px 25px;
        }
        
        .ppc-tip {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #3b82f6;
        }
        
        .ppc-tip-icon {
            font-size: 1.5em;
            flex-shrink: 0;
        }
        
        .ppc-tip strong {
            color: #1e3a8a;
        }

        .ppc-tip h4 {
            margin: 0 0 5px 0;
            font-size: 0.95em;
        }
        
        .ppc-tip p {
            margin: 0 0 10px 0;
            font-size: 0.9em;
            color: #6b7280;
        }
        
        /* Activity Feed */
        .ppc-activity-feed {
            padding: 20px 25px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .ppc-activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .ppc-activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .ppc-activity-icon {
            font-size: 1.3em;
            flex-shrink: 0;
        }
        
        .ppc-activity-details {
            flex: 1;
        }
        
        .ppc-activity-text {
            display: block;
            font-size: 0.9em;
            margin-bottom: 3px;
            color: #374151;
        }
        
        .ppc-activity-time {
            display: block;
            font-size: 0.8em;
            color: #6b7280;
        }
        
        .ppc-activity-revenue {
            font-weight: 600;
            color: #10b981;
            font-size: 0.9em;
        }
        
        .ppc-activity-empty {
            text-align: center;
            color: #6b7280;
            padding: 20px;
        }
        
        /* Checklist */
        .ppc-checklist {
            padding: 20px 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .ppc-checklist-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            background: #f9fafb;
        }
        
        .ppc-checklist-item.completed {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
        }
        
        .ppc-checklist-item.pending {
            background: #fefce8;
            border-left: 4px solid #eab308;
        }
        
        .ppc-checklist-icon {
            font-size: 1.2em;
            flex-shrink: 0;
        }
        
        .ppc-checklist-content h4 {
            margin: 0 0 5px 0;
            font-size: 0.95em;
            color: #1f2937;
        }
        
        .ppc-checklist-content p {
            margin: 0 0 10px 0;
            font-size: 0.85em;
            color: #6b7280;
        }
        
        /* Branding */
        .ppc-branding {
            padding: 20px 25px;
        }
        
        .ppc-branding ul {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        
        .ppc-branding li {
            padding: 5px 0;
            color: #4b5563;
        }
        </style>
        <?php
    }

    private function render_dashboard_scripts() {
        ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue Chart
            const ctx = document.getElementById('ppc-revenue-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'], // Replace with dynamic data
                        datasets: [{
                            label: 'Revenue',
                            data: [65, 59, 80, 81, 56, 55, 40], // Replace with dynamic data
                            fill: true,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // AJAX for live activity
            const refreshBtn = document.getElementById('ppc-refresh-activity');
            if(refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    // Add AJAX call to refresh activity feed
                });
            }
        });
        </script>
        <?php
    }

    public function analytics_page() { echo '<div class="wrap"><h1>Analytics</h1><p>Detailed analytics and reporting.</p></div>'; }
    public function bots_page() { echo '<div class="wrap"><h1>Bot Detection</h1><p>Manage bot detection settings and view detailed bot profiles.</p></div>'; }
    public function revenue_page() { echo '<div class="wrap"><h1>Revenue</h1><p>Configure payment settings and view revenue details.</p></div>'; }
    public function settings_page() { echo '<div class="wrap"><h1>Settings</h1><p>General plugin settings.</p></div>'; }
    public function support_page() { echo '<div class="wrap"><h1>Support</h1><p>Get help and support.</p></div>'; }

    public function ajax_dashboard_stats() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        $stats = [
            'today_bots' => $this->get_bot_count(),
            'today_revenue' => $this->get_revenue_potential(),
            'total_bots' => $this->get_total_bot_count(),
            'total_revenue' => $this->get_total_revenue()
        ];
        
        wp_send_json_success($stats);
    }

    public function ajax_bot_activity() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        ob_start();
        $this->display_live_activity();
        $activity = ob_get_clean();
        
        wp_send_json_success(['activity' => $activity]);
    }

    public function enqueue_scripts() {
        // Frontend scripts if needed
    }

    public function admin_enqueue_scripts() {
        $admin_js_file = PAYPERCRAWL_PLUGIN_DIR . 'assets/admin.js';
        if (file_exists($admin_js_file)) {
            wp_enqueue_script('paypercrawl-admin', PAYPERCRAWL_PLUGIN_URL . 'assets/admin.js', ['jquery'], PAYPERCRAWL_VERSION, true);
            wp_localize_script('paypercrawl-admin', 'paypercrawl_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('paypercrawl_nonce')
            ]);
        }
    }

    public function settings_section_callback() {
        echo '<p>Configure your Pay Per Crawl settings below.</p>';
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $logs_table = $wpdb->prefix . 'paypercrawl_logs';
        $sql_logs = "CREATE TABLE $logs_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bot_type varchar(255) NOT NULL,
            company varchar(255) DEFAULT '' NOT NULL,
            revenue decimal(10, 4) NOT NULL,
            detected_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            url varchar(2083) DEFAULT '' NOT NULL,
            ip_address varchar(100) DEFAULT '' NOT NULL,
            user_agent text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_logs);

        $requests_table = $wpdb->prefix . 'paypercrawl_requests';
        $sql_requests = "CREATE TABLE $requests_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(100) NOT NULL,
            request_time int(11) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_requests);
    }
}

// Initialize the plugin
PayPerCrawl::get_instance();
