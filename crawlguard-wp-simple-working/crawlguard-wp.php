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
 * Text Domain: paypercrawl
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
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Load bot signatures
        $this->load_bot_signatures();
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
        $this->bot_signatures = [
            // OpenAI & ChatGPT Family
            'GPTBot' => ['rate' => 0.12, 'type' => 'premium', 'company' => 'OpenAI'],
            'ChatGPT-User' => ['rate' => 0.12, 'type' => 'premium', 'company' => 'OpenAI'],
            'OpenAI' => ['rate' => 0.12, 'type' => 'premium', 'company' => 'OpenAI'],
            
            // Anthropic Claude Family
            'CCBot' => ['rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'],
            'anthropic-ai' => ['rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'],
            'Claude-Web' => ['rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'],
            'ClaudeBot' => ['rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'],
            
            // Google AI Family
            'Google-Extended' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            'GoogleOther' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            'Bard' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            'PaLM' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            'Gemini' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            
            // Meta AI Family
            'FacebookBot' => ['rate' => 0.07, 'type' => 'standard', 'company' => 'Meta'],
            'Meta-ExternalAgent' => ['rate' => 0.07, 'type' => 'standard', 'company' => 'Meta'],
            'Llama' => ['rate' => 0.07, 'type' => 'standard', 'company' => 'Meta'],
            
            // Microsoft AI Family
            'BingBot' => ['rate' => 0.06, 'type' => 'standard', 'company' => 'Microsoft'],
            'msnbot' => ['rate' => 0.06, 'type' => 'standard', 'company' => 'Microsoft'],
            'CopilotBot' => ['rate' => 0.06, 'type' => 'standard', 'company' => 'Microsoft'],
            
            // Other AI Companies
            'PerplexityBot' => ['rate' => 0.05, 'type' => 'emerging', 'company' => 'Perplexity'],
            'YouBot' => ['rate' => 0.05, 'type' => 'emerging', 'company' => 'You.com'],
            'Bytespider' => ['rate' => 0.04, 'type' => 'emerging', 'company' => 'ByteDance'],
            'YandexBot' => ['rate' => 0.04, 'type' => 'emerging', 'company' => 'Yandex'],
            
            // Research & Academic Bots
            'ResearchBot' => ['rate' => 0.03, 'type' => 'research', 'company' => 'Various'],
            'AcademicBot' => ['rate' => 0.03, 'type' => 'research', 'company' => 'Various'],
            
            // Web Scrapers with AI Features
            'ScrapingBot' => ['rate' => 0.02, 'type' => 'scraper', 'company' => 'Various'],
            'DataBot' => ['rate' => 0.02, 'type' => 'scraper', 'company' => 'Various'],
        ];
    }
    
    public function init() {
        $this->create_tables();
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
                        <h3>Today's Revenue</h3>
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
                        <span class="ppc-stat-icon">�</span>
                        <h3>Total Revenue</h3>
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
                            <h4>Top Performing Bots</h4>
                            <?php $this->display_top_bots(); ?>
                        </div>
                    </div>

                    <div class="ppc-card">
                        <div class="ppc-card-header">
                            <h3>🎯 Revenue Optimization</h3>
                        </div>
                        <div class="ppc-optimization-tips">
                            <div class="ppc-tip">
                                <span class="ppc-tip-icon">�</span>
                                <div>
                                    <strong>Increase Your Rates</strong>
                                    <p>Premium bots like GPT-4 can pay up to $0.15 per crawl</p>
                                    <a href="<?php echo admin_url('admin.php?page=paypercrawl-revenue'); ?>" class="button button-small">Optimize Rates</a>
                                </div>
                            </div>
                            <div class="ppc-tip">
                                <span class="ppc-tip-icon">🚀</span>
                                <div>
                                    <strong>Enable Auto-Scaling</strong>
                                    <p>Automatically adjust rates based on demand</p>
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
                return 'Detection Active (Limited Features)';
            case 'offline':
                return 'System Offline';
            default:
                return 'Unknown Status';
        }
    }
    
    private function get_revenue_change() {
        global $wpdb;
        $today = $wpdb->get_var("SELECT SUM(revenue) FROM {$wpdb->prefix}paypercrawl_logs WHERE DATE(detected_at) = CURDATE()") ?: 0;
        $yesterday = $wpdb->get_var("SELECT SUM(revenue) FROM {$wpdb->prefix}paypercrawl_logs WHERE DATE(detected_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)") ?: 1;
        
        if ($yesterday == 0) $yesterday = 1; // Avoid division by zero
        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }
    
    private function get_total_revenue() {
        global $wpdb;
        return $wpdb->get_var("SELECT SUM(revenue) FROM {$wpdb->prefix}paypercrawl_logs") ?: 0;
    }
    
    private function get_active_crawlers_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(DISTINCT bot_type) FROM {$wpdb->prefix}paypercrawl_logs WHERE DATE(detected_at) = CURDATE()") ?: 0;
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
            WHERE DATE(detected_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
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
            $bot_info = $this->bot_signatures[$bot->bot_type] ?? ['company' => 'Unknown', 'type' => 'other'];
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
            $bot_info = $this->bot_signatures[$log->bot_type] ?? ['company' => 'Unknown', 'type' => 'other'];
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
        echo '<p>Pay Per Crawl is successfully installed and running</p>';
        echo '</div>';
        echo '</div>';
        
        // Step 2: Bot Detection
        echo '<div class="ppc-checklist-item ' . ($detection_enabled ? 'completed' : 'pending') . '">';
        echo '<span class="ppc-checklist-icon">' . ($detection_enabled ? '✅' : '⏳') . '</span>';
        echo '<div class="ppc-checklist-content">';
        echo '<h4>Bot Detection ' . ($detection_enabled ? 'Enabled' : 'Disabled') . '</h4>';
        echo '<p>AI bot detection and logging system</p>';
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
        echo '<p>Connect to PayPerCrawl.tech for enhanced features</p>';
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
        echo '<p>Setup automatic revenue collection</p>';
        if (!$payment_configured) {
            echo '<a href="' . admin_url('admin.php?page=paypercrawl-revenue') . '" class="button button-small">Setup Payments</a>';
        }
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    private function is_payment_configured() {
        return defined('PAYPERCRAWL_STRIPE_KEY') && !empty(PAYPERCRAWL_STRIPE_KEY);
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .ppc-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
        }
        
        .ppc-tip-icon {
            font-size: 1.5em;
            flex-shrink: 0;
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
            font-size: 0.9em;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .ppc-header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .ppc-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .ppc-two-column {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    private function render_dashboard_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Auto-refresh activity every 30 seconds
            setInterval(function() {
                refreshActivity();
            }, 30000);
            
            // Manual refresh button
            $('#ppc-refresh-activity').on('click', function() {
                var $btn = $(this);
                $btn.text('🔄 Refreshing...').prop('disabled', true);
                
                refreshActivity(function() {
                    $btn.text('🔄 Refresh').prop('disabled', false);
                });
            });
            
            // Enable auto-scaling
            $('.ppc-enable-autoscale').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                $btn.text('Enabling...').prop('disabled', true);
                
                // Simulate enabling auto-scale
                setTimeout(function() {
                    $btn.text('✅ Enabled').addClass('button-primary').prop('disabled', false);
                    $btn.closest('.ppc-tip').css('background', '#ecfdf5');
                }, 1500);
            });
            
            // Timeframe selector
            $('#ppc-timeframe').on('change', function() {
                var timeframe = $(this).val();
                // Here you would typically reload chart data
                console.log('Timeframe changed to:', timeframe);
            });
            
            // Initialize tooltips and other interactive elements
            initializeDashboard();
        });
        
        function refreshActivity(callback) {
            // In a real implementation, this would make an AJAX call
            $.post(ajaxurl, {
                action: 'paypercrawl_bot_activity',
                _ajax_nonce: '<?php echo wp_create_nonce("paypercrawl_activity"); ?>'
            }, function(response) {
                if (response.success) {
                    $('#ppc-bot-activity').html(response.data);
                }
                if (callback) callback();
            }).fail(function() {
                // Fallback: just reload the page section
                if (callback) callback();
            });
        }
        
        function initializeDashboard() {
            // Add smooth animations to stat cards
            $('.ppc-stat-card').each(function(index) {
                $(this).css('animation-delay', (index * 100) + 'ms');
                $(this).addClass('fade-in');
            });
            
            // Initialize chart placeholder
            var chartCanvas = document.getElementById('ppc-revenue-chart');
            if (chartCanvas) {
                var ctx = chartCanvas.getContext('2d');
                ctx.fillStyle = '#f3f4f6';
                ctx.fillRect(0, 0, chartCanvas.width, chartCanvas.height);
                ctx.fillStyle = '#6b7280';
                ctx.font = '16px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('📊 Revenue Chart Coming Soon', chartCanvas.width/2, chartCanvas.height/2);
                ctx.fillText('Connect to PayPerCrawl.tech for real-time analytics', chartCanvas.width/2, chartCanvas.height/2 + 25);
            }
        }
        </script>
        
        <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fade-in 0.6s ease-out forwards;
        }
        </style>
        <?php
    }
        
        <style>
        .cg-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .cg-stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #0073aa;
        }
        .cg-stat-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .cg-stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #0073aa;
            margin: 10px 0;
        }
        .cg-stat-card h3 {
            margin: 10px 0;
            color: #333;
            font-size: 16px;
        }
        .cg-stat-card p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        .cg-activity-feed, .cg-revenue-chart, .cg-setup-guide {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        .activity-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .revenue {
            color: #28a745;
            font-weight: bold;
        }
        .revenue-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .revenue-breakdown ul {
            list-style: none;
            padding: 0;
        }
        .revenue-breakdown li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .setup-steps {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .setup-step {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .setup-step.completed {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .setup-step.pending {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #0073aa;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .setup-step.completed .step-number {
            background: #28a745;
        }
        .setup-step.pending .step-number {
            background: #ffc107;
        }
        #refresh-activity {
            margin-top: 15px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#refresh-activity').click(function() {
                $(this).text('🔄 Refreshing...').prop('disabled', true);
                location.reload();
            });
            
            // Simulate real-time updates every 30 seconds
            setInterval(function() {
                // Add visual indicator of activity
                $('.cg-stat-card').addClass('pulse');
                setTimeout(function() {
                    $('.cg-stat-card').removeClass('pulse');
                }, 1000);
            }, 30000);
        });
        </script>
        
        <style>
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        .pulse {
            animation: pulse 0.5s ease-in-out;
        }
        </style>
        <?php
    }
    
    public function settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['paypercrawl_nonce'], 'paypercrawl_settings')) {
            update_option('paypercrawl_detection_enabled', isset($_POST['detection_enabled']));
            update_option('paypercrawl_monetization_enabled', isset($_POST['monetization_enabled']));
            update_option('paypercrawl_api_key', sanitize_text_field($_POST['api_key'] ?? ''));
            update_option('paypercrawl_webhook_url', esc_url_raw($_POST['webhook_url'] ?? ''));
            update_option('paypercrawl_rate_multiplier', floatval($_POST['rate_multiplier'] ?? 1.0));
            
            echo '<div class="notice notice-success"><p><strong>Settings saved successfully!</strong> Your Pay Per Crawl configuration has been updated.</p></div>';
        }
        
        // Get current settings
        $detection_enabled = get_option('paypercrawl_detection_enabled', true);
        $monetization_enabled = get_option('paypercrawl_monetization_enabled', true);
        $api_key = get_option('paypercrawl_api_key', '');
        $webhook_url = get_option('paypercrawl_webhook_url', '');
        $rate_multiplier = get_option('paypercrawl_rate_multiplier', 1.0);
        ?>
        <div class="wrap paypercrawl-settings">
            <h1>⚙️ Pay Per Crawl Settings</h1>
            <p class="ppc-settings-desc">Configure your AI bot detection and monetization settings.</p>
            
            <form method="post" class="ppc-settings-form">
                <?php wp_nonce_field('paypercrawl_settings', 'paypercrawl_nonce'); ?>
                
                <!-- Detection Settings -->
                <div class="ppc-settings-section">
                    <h2>🤖 Bot Detection Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Bot Detection</th>
                            <td>
                                <label class="ppc-toggle">
                                    <input type="checkbox" name="detection_enabled" <?php checked($detection_enabled); ?> />
                                    <span class="ppc-toggle-slider"></span>
                                </label>
                                <p class="description">Automatically detect and log AI bots visiting your site in real-time.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Enable Monetization</th>
                            <td>
                                <label class="ppc-toggle">
                                    <input type="checkbox" name="monetization_enabled" <?php checked($monetization_enabled); ?> />
                                    <span class="ppc-toggle-slider"></span>
                                </label>
                                <p class="description">Track and calculate revenue potential from AI bot visits.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Rate Multiplier</th>
                            <td>
                                <input type="number" name="rate_multiplier" value="<?php echo esc_attr($rate_multiplier); ?>" 
                                       step="0.1" min="0.1" max="10" class="regular-text" />
                                <p class="description">Multiply all bot rates by this factor (1.0 = default rates, 2.0 = double rates).</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- API Settings -->
                <div class="ppc-settings-section">
                    <h2>🔗 PayPerCrawl.tech API Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">API Key</th>
                            <td>
                                <input type="password" name="api_key" value="<?php echo esc_attr($api_key); ?>" 
                                       class="regular-text" placeholder="Enter your PayPerCrawl.tech API key" />
                                <p class="description">Get your API key from <a href="https://paypercrawl.tech/dashboard" target="_blank">PayPerCrawl.tech Dashboard</a> for enhanced features.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Webhook URL</th>
                            <td>
                                <input type="url" name="webhook_url" value="<?php echo esc_attr($webhook_url); ?>" 
                                       class="regular-text" placeholder="https://your-site.com/webhook" />
                                <p class="description">Optional: URL to receive real-time bot detection notifications.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button('Save Settings', 'primary', 'submit', true, ['class' => 'ppc-save-button']); ?>
            </form>
            
            <!-- Payment Setup Information -->
            <div class="ppc-payment-info">
                <h2>� Revenue Collection Setup</h2>
                <div class="ppc-payment-cards">
                    <div class="ppc-payment-card">
                        <h3>🚀 Quick Setup</h3>
                        <p>Connect your PayPerCrawl.tech account for automatic revenue collection:</p>
                        <ol>
                            <li>Visit <a href="https://paypercrawl.tech/signup" target="_blank">PayPerCrawl.tech</a></li>
                            <li>Create your free account</li>
                            <li>Add your website domain</li>
                            <li>Copy your API key above</li>
                            <li>Start earning automatically!</li>
                        </ol>
                        <a href="https://paypercrawl.tech/signup" target="_blank" class="button button-primary">Setup PayPerCrawl Account</a>
                    </div>
                    
                    <div class="ppc-payment-card">
                        <h3>⚡ How It Works</h3>
                        <ul>
                            <li><strong>AI bots visit your site</strong> (GPT, Claude, Gemini, etc.)</li>
                            <li><strong>Pay Per Crawl detects them</strong> in real-time</li>
                            <li><strong>Revenue is calculated</strong> based on bot type and rates</li>
                            <li><strong>Payments are processed</strong> via PayPerCrawl.tech</li>
                            <li><strong>You get paid</strong> directly to your account</li>
                        </ul>
                    </div>
                    
                    <div class="ppc-payment-card">
                        <h3>💎 Premium Features</h3>
                        <p>Available with PayPerCrawl.tech API connection:</p>
                        <ul>
                            <li>🔥 Advanced bot detection (50+ signatures)</li>
                            <li>📊 Real-time analytics dashboard</li>
                            <li>💰 Higher revenue rates</li>
                            <li>🎯 Smart rate optimization</li>
                            <li>📈 Detailed reporting</li>
                            <li>🛠️ API access for integration</li>
                        </ul>
                        <a href="https://paypercrawl.tech/features" target="_blank" class="button">Learn More</a>
                    </div>
                </div>
            </div>
            
            <!-- Current Bot Signatures -->
            <div class="ppc-bot-signatures-section">
                <h2>🤖 Current Bot Detection Signatures</h2>
                <p>These AI bots are currently being detected and monetized:</p>
                <?php $this->display_bot_signatures(); ?>
            </div>
        </div>
        
        <?php $this->render_settings_styles(); ?>
        <?php
    }
    
    private function render_settings_styles() {
        ?>
        <style>
        .paypercrawl-settings {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .ppc-settings-desc {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 30px;
        }
        
        .ppc-settings-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .ppc-settings-section {
            margin-bottom: 40px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 30px;
        }
        
        .ppc-settings-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .ppc-settings-section h2 {
            margin-top: 0;
            color: #1f2937;
            font-size: 1.4em;
            margin-bottom: 20px;
        }
        
        /* Toggle Switch */
        .ppc-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
            margin-right: 10px;
        }
        
        .ppc-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .ppc-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .ppc-toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        .ppc-toggle input:checked + .ppc-toggle-slider {
            background-color: #10b981;
        }
        
        .ppc-toggle input:checked + .ppc-toggle-slider:before {
            transform: translateX(26px);
        }
        
        .ppc-save-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            border: none !important;
            font-size: 1.1em !important;
            padding: 12px 30px !important;
            border-radius: 8px !important;
        }
        
        /* Payment Info */
        .ppc-payment-info {
            margin-top: 40px;
        }
        
        .ppc-payment-info h2 {
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .ppc-payment-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .ppc-payment-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        
        .ppc-payment-card h3 {
            margin-top: 0;
            color: #1f2937;
            font-size: 1.2em;
        }
        
        .ppc-payment-card ul, .ppc-payment-card ol {
            padding-left: 20px;
        }
        
        .ppc-payment-card li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        /* Bot Signatures Section */
        .ppc-bot-signatures-section {
            margin-top: 40px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .ppc-signatures-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .ppc-signature-card {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #6b7280;
        }
        
        .ppc-signature-card.premium { border-left-color: #10b981; background: #ecfdf5; }
        .ppc-signature-card.standard { border-left-color: #3b82f6; background: #eff6ff; }
        .ppc-signature-card.emerging { border-left-color: #f59e0b; background: #fffbeb; }
        .ppc-signature-card.research { border-left-color: #8b5cf6; background: #f3f4f6; }
        .ppc-signature-card.scraper { border-left-color: #ef4444; background: #fef2f2; }
        
        .ppc-signature-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .ppc-signature-icon {
            font-size: 1.5em;
        }
        
        .ppc-signature-header h4 {
            margin: 0;
            font-size: 1em;
            font-weight: 600;
        }
        
        .ppc-signature-details {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .ppc-signature-company {
            font-size: 0.9em;
            color: #6b7280;
        }
        
        .ppc-signature-rate {
            font-weight: 600;
            color: #10b981;
        }
        
        .ppc-signature-type {
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
        }
        </style>
        <?php
    }
    
    public function detect_bot() {
        if (is_admin()) return;
        
        // Check if detection is enabled
        if (!get_option('paypercrawl_detection_enabled', true)) return;
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (empty($user_agent)) return;
        
        // Use our enhanced bot signatures
        foreach ($this->bot_signatures as $bot => $info) {
            if (stripos($user_agent, $bot) !== false) {
                $this->log_bot_detection($bot, $user_agent, $ip, $info['rate'], $info);
                
                // Optional: Send real-time notification to PayPerCrawl.tech
                $this->notify_paypercrawl_api($bot, $info, $ip);
                break;
            }
        }
    }
    
    public function advanced_bot_detection() {
        // Additional detection methods beyond user agent
        if (is_admin()) return;
        
        $headers = apache_request_headers();
        $suspicious_patterns = [
            'X-Forwarded-For' => ['openai', 'anthropic', 'google-research'],
            'Accept' => ['application/json', 'text/plain'],
            'User-Agent' => ['bot', 'crawler', 'spider', 'scraper']
        ];
        
        foreach ($suspicious_patterns as $header => $patterns) {
            if (isset($headers[$header])) {
                foreach ($patterns as $pattern) {
                    if (stripos($headers[$header], $pattern) !== false) {
                        // Log as potential AI bot
                        $this->log_bot_detection('UnknownAI', $headers[$header], $_SERVER['REMOTE_ADDR'] ?? '', 0.01, ['type' => 'detected', 'company' => 'Unknown']);
                        break 2;
                    }
                }
            }
        }
    }
    
    private function notify_paypercrawl_api($bot_type, $bot_info, $ip) {
        // Only send if API is configured AND not in standalone mode
        if (defined('PAYPERCRAWL_STANDALONE_MODE') && PAYPERCRAWL_STANDALONE_MODE) {
            // Skip API calls in standalone mode
            return;
        }
        
        if (!$this->is_api_connected()) return;
        
        $api_key = get_option('paypercrawl_api_key');
        $data = [
            'bot_type' => $bot_type,
            'company' => $bot_info['company'],
            'rate' => $bot_info['rate'],
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'ip_hash' => md5($ip) // Don't send actual IP for privacy
        ];
        
        // Async API call to avoid slowing down the site
        wp_remote_post(PAYPERCRAWL_API_URL . 'bot-detection', [
            'timeout' => 5,
            'blocking' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);
    }
    
    private function log_bot_detection($bot_type, $user_agent, $ip, $revenue, $bot_info = []) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'paypercrawl_logs',
            array(
                'bot_type' => $bot_type,
                'user_agent' => substr($user_agent, 0, 500),
                'ip_address' => $ip,
                'revenue' => $revenue,
                'company' => $bot_info['company'] ?? 'Unknown',
                'bot_category' => $bot_info['type'] ?? 'other',
                'page_url' => $_SERVER['REQUEST_URI'] ?? '',
                'detected_at' => current_time('mysql')
            )
        );
        
        // Update daily statistics
        $this->update_daily_stats($bot_type, $revenue);
    }
    }
    
    private function update_daily_stats($bot_type, $revenue) {
        // Update cached daily statistics for performance
        $today = date('Y-m-d');
        $stats_key = 'paypercrawl_stats_' . $today;
        $stats = get_transient($stats_key) ?: ['count' => 0, 'revenue' => 0, 'bots' => []];
        
        $stats['count']++;
        $stats['revenue'] += $revenue;
        $stats['bots'][$bot_type] = ($stats['bots'][$bot_type] ?? 0) + 1;
        
        set_transient($stats_key, $stats, DAY_IN_SECONDS);
    }
    
    private function get_bot_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}paypercrawl_logs WHERE DATE(detected_at) = CURDATE()") ?: 0;
    }
    
    private function get_total_bot_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}paypercrawl_logs") ?: 0;
    }
    
    private function get_revenue_potential() {
        global $wpdb;
        return $wpdb->get_var("SELECT SUM(revenue) FROM {$wpdb->prefix}paypercrawl_logs WHERE DATE(detected_at) = CURDATE()") ?: 0;
    }
    
    // New admin page functions for different sections
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1>📈 Pay Per Crawl Analytics</h1>
            <div class="ppc-analytics-container">
                <div class="ppc-card">
                    <div class="ppc-card-header">
                        <h3>Revenue Trends</h3>
                    </div>
                    <div class="ppc-chart-container">
                        <canvas id="ppc-revenue-trend-chart"></canvas>
                    </div>
                </div>
                
                <div class="ppc-card">
                    <div class="ppc-card-header">
                        <h3>Bot Distribution</h3>
                    </div>
                    <div class="ppc-chart-container">
                        <canvas id="ppc-bot-distribution-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function bots_page() {
        ?>
        <div class="wrap">
            <h1>🤖 Bot Detection Management</h1>
            <div class="ppc-bots-container">
                <div class="ppc-card">
                    <div class="ppc-card-header">
                        <h3>Detected Bot Types</h3>
                    </div>
                    <div class="ppc-bot-signatures-list">
                        <?php $this->display_bot_signatures(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function revenue_page() {
        ?>
        <div class="wrap">
            <h1>💰 Revenue Settings</h1>
            <div class="ppc-revenue-container">
                <div class="ppc-card">
                    <div class="ppc-card-header">
                        <h3>Payment Configuration</h3>
                    </div>
                    <div style="padding: 20px;">
                        <p>Configure your payment settings to start earning from AI bot visits.</p>
                        <a href="https://paypercrawl.tech/setup" target="_blank" class="button button-primary">Configure Payment Settings</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function support_page() {
        ?>
        <div class="wrap">
            <h1>🆘 Pay Per Crawl Support</h1>
            <div class="ppc-support-container">
                <div class="ppc-card">
                    <div class="ppc-card-header">
                        <h3>Get Help & Support</h3>
                    </div>
                    <div style="padding: 20px;">
                        <h4>🌐 Official Website</h4>
                        <p><a href="https://paypercrawl.tech" target="_blank">Visit PayPerCrawl.tech</a> for documentation, tutorials, and updates.</p>
                        
                        <h4>📧 Contact Support</h4>
                        <p>Email us at <a href="mailto:support@paypercrawl.tech">support@paypercrawl.tech</a> for technical assistance.</p>
                        
                        <h4>📚 Resources</h4>
                        <ul>
                            <li><a href="https://paypercrawl.tech/docs" target="_blank">Documentation</a></li>
                            <li><a href="https://paypercrawl.tech/api" target="_blank">API Reference</a></li>
                            <li><a href="https://paypercrawl.tech/blog" target="_blank">Blog & Updates</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function display_bot_signatures() {
        echo '<div class="ppc-signatures-grid">';
        foreach ($this->bot_signatures as $bot => $info) {
            echo '<div class="ppc-signature-card ' . esc_attr($info['type']) . '">';
            echo '<div class="ppc-signature-header">';
            echo '<span class="ppc-signature-icon">' . $this->get_bot_icon($info['type']) . '</span>';
            echo '<h4>' . esc_html($bot) . '</h4>';
            echo '</div>';
            echo '<div class="ppc-signature-details">';
            echo '<span class="ppc-signature-company">' . esc_html($info['company']) . '</span>';
            echo '<span class="ppc-signature-rate">$' . number_format($info['rate'], 3) . ' per visit</span>';
            echo '<span class="ppc-signature-type">' . ucfirst($info['type']) . ' tier</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    private function display_recent_activity() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}crawlguard_logs'");
        if (!$table_exists) {
            echo '<p>🤖 Bot detection is active. Activity will appear here as AI bots visit your site.</p>';
            return;
        }
        
        $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}crawlguard_logs ORDER BY detected_at DESC LIMIT 10");
        
        if (empty($logs)) {
            echo '<div class="activity-item">';
            echo '<span>🤖 No bot activity detected yet. The system is actively monitoring...</span>';
            echo '</div>';
            echo '<div class="activity-item">';
            echo '<span>💡 <strong>Tip:</strong> AI bots like ChatGPT and Claude will automatically be detected when they visit your site.</span>';
            echo '</div>';
            return;
        }
        
        foreach ($logs as $log) {
            echo '<div class="activity-item">';
            echo '<div>';
            echo '<strong>' . esc_html($log->bot_type) . '</strong> detected ';
            echo '<small style="color: #666;">from ' . esc_html(substr($log->ip_address, 0, 10)) . '...</small>';
            echo '</div>';
            echo '<div>';
            echo '<span class="revenue">+$' . number_format($log->revenue, 3) . '</span> ';
            echo '<small>' . human_time_diff(strtotime($log->detected_at)) . ' ago</small>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    private function is_stripe_configured() {
        return defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY);
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
            company varchar(100) DEFAULT 'Unknown',
            bot_category varchar(50) DEFAULT 'other',
            page_url text,
            detected_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bot_type (bot_type),
            KEY detected_at (detected_at),
            KEY company (company),
            KEY bot_category (bot_category)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create daily stats table for performance
        $stats_table = $wpdb->prefix . 'paypercrawl_daily_stats';
        $stats_sql = "CREATE TABLE $stats_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            stat_date date NOT NULL,
            bot_count int(11) DEFAULT 0,
            total_revenue decimal(10,4) DEFAULT 0,
            unique_bots int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY stat_date (stat_date)
        ) $charset_collate;";
        
        dbDelta($stats_sql);
        
        // Migrate old data if exists
        $this->migrate_old_data();
    }
    
    private function migrate_old_data() {
        global $wpdb;
        
        $old_table = $wpdb->prefix . 'crawlguard_logs';
        $new_table = $wpdb->prefix . 'paypercrawl_logs';
        
        // Check if old table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") === $old_table) {
            // Migrate data
            $wpdb->query("
                INSERT IGNORE INTO $new_table 
                (bot_type, user_agent, ip_address, revenue, page_url, detected_at, company, bot_category)
                SELECT 
                    bot_type, 
                    user_agent, 
                    ip_address, 
                    revenue, 
                    page_url, 
                    detected_at,
                    'Unknown' as company,
                    'other' as bot_category
                FROM $old_table
            ");
            
            // Optionally drop old table after successful migration
            // $wpdb->query("DROP TABLE IF EXISTS $old_table");
        }
    }
    
    public function activate() {
        $this->create_tables();
        
        // Set default options for Pay Per Crawl
        add_option('paypercrawl_detection_enabled', true);
        add_option('paypercrawl_monetization_enabled', true);
        add_option('paypercrawl_version', PAYPERCRAWL_VERSION);
        add_option('paypercrawl_installed_date', current_time('mysql'));
        
        // Clean up old options
        delete_option('crawlguard_detection_enabled');
        delete_option('crawlguard_monetization_enabled');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation redirect
        add_option('paypercrawl_activation_redirect', true);
    }
    
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('paypercrawl_daily_cleanup');
        wp_clear_scheduled_hook('paypercrawl_send_stats');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    // AJAX Handlers
    public function ajax_dashboard_stats() {
        check_ajax_referer('paypercrawl_dashboard', '_ajax_nonce');
        
        $stats = [
            'today_bots' => $this->get_bot_count(),
            'today_revenue' => $this->get_revenue_potential(),
            'total_bots' => $this->get_total_bot_count(),
            'total_revenue' => $this->get_total_revenue(),
            'active_crawlers' => $this->get_active_crawlers_count()
        ];
        
        wp_send_json_success($stats);
    }
    
    public function ajax_bot_activity() {
        check_ajax_referer('paypercrawl_activity', '_ajax_nonce');
        
        ob_start();
        $this->display_live_activity();
        $activity_html = ob_get_clean();
        
        wp_send_json_success($activity_html);
    }
    
    public function admin_enqueue_scripts($hook) {
        // Only load scripts on our plugin pages
        if (strpos($hook, 'paypercrawl') === false) return;
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
        
        // Localize script for AJAX
        wp_localize_script('jquery', 'paypercrawl_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'dashboard_nonce' => wp_create_nonce('paypercrawl_dashboard'),
            'activity_nonce' => wp_create_nonce('paypercrawl_activity')
        ]);
    }
    
    public function enqueue_scripts() {
        // Frontend bot detection script (optional)
        if (get_option('paypercrawl_detection_enabled', true)) {
            wp_enqueue_script('paypercrawl-frontend', PAYPERCRAWL_PLUGIN_URL . 'assets/paypercrawl-frontend.js', [], PAYPERCRAWL_VERSION, true);
        }
    }
    
    // Settings section callback
    public function settings_section_callback() {
        echo '<p>Configure your Pay Per Crawl settings below.</p>';
    }
    
    // Helper function to check if old CrawlGuard data exists
    private function has_legacy_data() {
        global $wpdb;
        $old_table = $wpdb->prefix . 'crawlguard_logs';
        return $wpdb->get_var("SHOW TABLES LIKE '$old_table'") === $old_table;
    }
}

// Initialize the Pay Per Crawl plugin
PayPerCrawl::get_instance();

// Add activation redirect
add_action('admin_init', function() {
    if (get_option('paypercrawl_activation_redirect', false)) {
        delete_option('paypercrawl_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            wp_redirect(admin_url('admin.php?page=paypercrawl-dashboard&welcome=1'));
            exit;
        }
    }
});

// Add admin notices for welcome or important information
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'paypercrawl-dashboard' && isset($_GET['welcome'])) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<h2>🎉 Welcome to Pay Per Crawl!</h2>';
        echo '<p><strong>Your AI bot detection system is now active!</strong> Start earning revenue from every AI bot that visits your site.</p>';
        echo '<p><a href="https://paypercrawl.tech/getting-started" target="_blank" class="button button-primary">View Getting Started Guide</a> ';
        echo '<a href="' . admin_url('admin.php?page=paypercrawl-settings') . '" class="button">Configure Settings</a></p>';
        echo '</div>';
    }
});
?>
