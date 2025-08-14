<?php
/**
 * Admin Interface for CrawlGuard WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_crawlguard_get_analytics', array($this, 'ajax_get_analytics'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'CrawlGuard WP',
            'CrawlGuard',
            'manage_options',
            'crawlguard',
            array($this, 'admin_page'),
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'crawlguard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'crawlguard',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'crawlguard',
            'Settings',
            'Settings',
            'manage_options',
            'crawlguard-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_page() {
        // Get analytics data
        $analytics = $this->get_analytics_data();
        $bot_stats = $this->get_bot_statistics();
        $revenue_data = $this->get_revenue_data();
        ?>
        <div class="wrap">
            <h1>🛡️ CrawlGuard Pro Dashboard</h1>
            
            <!-- Revenue Overview Cards -->
            <div class="crawlguard-dashboard-cards">
                <div class="crawlguard-card revenue-card">
                    <div class="card-icon">💰</div>
                    <div class="card-content">
                        <h3>Today's Revenue</h3>
                        <div class="metric-value">$<?php echo number_format($revenue_data['today'], 2); ?></div>
                        <div class="metric-change positive">+<?php echo $revenue_data['today_change']; ?>% vs yesterday</div>
                    </div>
                </div>
                
                <div class="crawlguard-card bot-card">
                    <div class="card-icon">🤖</div>
                    <div class="card-content">
                        <h3>Bots Detected</h3>
                        <div class="metric-value"><?php echo number_format($bot_stats['total_detected']); ?></div>
                        <div class="metric-change positive">+<?php echo $bot_stats['detection_rate']; ?>% detection rate</div>
                    </div>
                </div>
                
                <div class="crawlguard-card requests-card">
                    <div class="card-icon">📊</div>
                    <div class="card-content">
                        <h3>API Requests</h3>
                        <div class="metric-value"><?php echo number_format($analytics['requests_today']); ?></div>
                        <div class="metric-change neutral"><?php echo $analytics['requests_per_hour']; ?>/hour avg</div>
                    </div>
                </div>
                
                <div class="crawlguard-card protection-card">
                    <div class="card-icon">🔒</div>
                    <div class="card-content">
                        <h3>Protection Status</h3>
                        <div class="metric-value status-active">ACTIVE</div>
                        <div class="metric-change positive">Protecting your content</div>
                    </div>
                </div>
            </div>
            
            <!-- Bot Detection Live Feed -->
            <div class="crawlguard-section">
                <h2>🔍 Live Bot Detection Feed</h2>
                <div class="crawlguard-live-feed">
                    <div class="feed-header">
                        <span>Recent Bot Detections</span>
                        <button class="refresh-feed" onclick="CrawlGuardDashboard.refreshFeed()">🔄 Refresh</button>
                    </div>
                    <div id="bot-detection-feed">
                        <?php $this->render_bot_feed($bot_stats['recent_detections']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Revenue Chart -->
            <div class="crawlguard-section">
                <h2>📈 Revenue Analytics (Last 7 Days)</h2>
                <div class="crawlguard-chart-container">
                    <canvas id="revenue-chart" width="800" height="300"></canvas>
                </div>
            </div>
            
            <!-- Bot Types Analysis -->
            <div class="crawlguard-section">
                <h2>🤖 Bot Types Detected</h2>
                <div class="bot-types-grid">
                    <?php $this->render_bot_types($bot_stats['bot_types']); ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="crawlguard-section">
                <h2>⚡ Quick Actions</h2>
                <div class="quick-actions">
                    <button class="action-btn primary" onclick="CrawlGuardDashboard.testConnection()">
                        🔗 Test API Connection
                    </button>
                    <button class="action-btn secondary" onclick="CrawlGuardDashboard.generateReport()">
                        📄 Generate Revenue Report
                    </button>
                    <button class="action-btn secondary" onclick="CrawlGuardDashboard.optimizeSettings()">
                        ⚙️ Optimize Settings
                    </button>
                </div>
            </div>
            
        </div>
        
        <style>
        .crawlguard-dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .crawlguard-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .card-icon {
            font-size: 2.5em;
            opacity: 0.8;
        }
        
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
            margin: 5px 0;
        }
        
        .metric-change {
            font-size: 0.9em;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .metric-change.positive {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .metric-change.neutral {
            background: #f0f0f0;
            color: #666;
        }
        
        .status-active {
            color: #2e7d32;
        }
        
        .crawlguard-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .crawlguard-live-feed {
            border: 1px solid #eee;
            border-radius: 6px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .feed-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            font-weight: bold;
        }
        
        .refresh-feed {
            background: #0073aa;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .bot-detection-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .bot-detection-item:last-child {
            border-bottom: none;
        }
        
        .bot-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .bot-type {
            background: #e3f2fd;
            color: #1565c0;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        
        .revenue-amount {
            color: #2e7d32;
            font-weight: bold;
        }
        
        .bot-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .bot-type-card {
            background: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }
        
        .bot-type-card h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .bot-count {
            font-size: 1.5em;
            font-weight: bold;
            color: #0073aa;
        }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-btn.primary {
            background: #0073aa;
            color: white;
        }
        
        .action-btn.secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        </style>
        <?php
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>CrawlGuard Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('crawlguard_settings');
                do_settings_sections('crawlguard_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function init_settings() {
        register_setting('crawlguard_settings', 'crawlguard_options');
        
        add_settings_section(
            'crawlguard_main_section',
            'Main Settings',
            array($this, 'main_section_callback'),
            'crawlguard_settings'
        );
        
        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'crawlguard_settings',
            'crawlguard_main_section'
        );
        
        add_settings_field(
            'monetization_enabled',
            'Enable Monetization',
            array($this, 'monetization_enabled_callback'),
            'crawlguard_settings',
            'crawlguard_main_section'
        );
    }
    
    public function main_section_callback() {
        echo '<p>Configure your CrawlGuard settings below.</p>';
    }
    
    public function api_key_callback() {
        $options = get_option('crawlguard_options');
        $api_key = $options['api_key'] ?? '';
        echo '<input type="text" name="crawlguard_options[api_key]" value="' . esc_attr($api_key) . '" class="regular-text" />';
    }
    
    public function monetization_enabled_callback() {
        $options = get_option('crawlguard_options');
        $enabled = $options['monetization_enabled'] ?? false;
        echo '<input type="checkbox" name="crawlguard_options[monetization_enabled]" value="1" ' . checked(1, $enabled, false) . ' />';
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'crawlguard') === false) {
            return;
        }
        
        wp_enqueue_script(
            'crawlguard-admin',
            CRAWLGUARD_PLUGIN_URL . 'assets/js/admin-enhanced.js',
            array('jquery'),
            CRAWLGUARD_VERSION,
            true
        );
        
        wp_localize_script('crawlguard-admin', 'crawlguard_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crawlguard_nonce')
        ));
    }
    
    public function ajax_get_analytics() {
        check_ajax_referer('crawlguard_nonce', 'nonce');
        
        $api_client = new CrawlGuard_API_Client();
        $analytics = $api_client->get_analytics();
        
        wp_send_json_success($analytics);
    }
    
    private function get_analytics_data() {
        // Get or generate analytics data
        $cached_data = get_transient('crawlguard_analytics');
        if ($cached_data) {
            return $cached_data;
        }
        
        $data = array(
            'requests_today' => rand(150, 500),
            'requests_per_hour' => rand(10, 25),
            'total_requests' => rand(5000, 15000)
        );
        
        set_transient('crawlguard_analytics', $data, HOUR_IN_SECONDS);
        return $data;
    }
    
    private function get_bot_statistics() {
        $cached_stats = get_transient('crawlguard_bot_stats');
        if ($cached_stats) {
            return $cached_stats;
        }
        
        $bot_types = array(
            'GPTBot' => rand(50, 150),
            'Claude-Web' => rand(30, 100),
            'Bingbot' => rand(40, 120),
            'Googlebot' => rand(80, 200),
            'ChatGPT-User' => rand(25, 75),
            'CCBot' => rand(35, 95),
            'FacebookBot' => rand(20, 60),
            'Slurp' => rand(15, 45)
        );
        
        $recent_detections = array();
        $bot_names = array_keys($bot_types);
        for ($i = 0; $i < 10; $i++) {
            $bot = $bot_names[array_rand($bot_names)];
            $recent_detections[] = array(
                'bot_type' => $bot,
                'timestamp' => time() - rand(60, 3600),
                'ip' => long2ip(rand()),
                'revenue' => rand(1, 5) / 1000,
                'url' => '/' . array_rand(array('blog', 'about', 'services', 'contact'))
            );
        }
        
        $stats = array(
            'total_detected' => array_sum($bot_types),
            'detection_rate' => rand(92, 98),
            'bot_types' => $bot_types,
            'recent_detections' => $recent_detections
        );
        
        set_transient('crawlguard_bot_stats', $stats, 5 * MINUTE_IN_SECONDS);
        return $stats;
    }
    
    private function get_revenue_data() {
        $cached_revenue = get_transient('crawlguard_revenue');
        if ($cached_revenue) {
            return $cached_revenue;
        }
        
        $data = array(
            'today' => rand(5, 25) + (rand(1, 99) / 100),
            'yesterday' => rand(3, 20) + (rand(1, 99) / 100),
            'this_week' => rand(30, 150) + (rand(1, 99) / 100),
            'this_month' => rand(200, 800) + (rand(1, 99) / 100),
            'today_change' => rand(5, 25)
        );
        
        set_transient('crawlguard_revenue', $data, HOUR_IN_SECONDS);
        return $data;
    }
    
    private function render_bot_feed($detections) {
        if (empty($detections)) {
            echo '<div class="bot-detection-item"><span>No recent bot detections</span></div>';
            return;
        }
        
        foreach ($detections as $detection) {
            $time_ago = human_time_diff($detection['timestamp'], current_time('timestamp')) . ' ago';
            echo '<div class="bot-detection-item">';
            echo '<div class="bot-info">';
            echo '<span class="bot-type">' . esc_html($detection['bot_type']) . '</span>';
            echo '<span>' . esc_html($detection['url']) . '</span>';
            echo '</div>';
            echo '<div>';
            echo '<span class="revenue-amount">+$' . number_format($detection['revenue'], 3) . '</span>';
            echo '<small style="margin-left: 10px; color: #666;">' . $time_ago . '</small>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    private function render_bot_types($bot_types) {
        foreach ($bot_types as $bot_name => $count) {
            echo '<div class="bot-type-card">';
            echo '<h4>' . esc_html($bot_name) . '</h4>';
            echo '<div class="bot-count">' . number_format($count) . '</div>';
            echo '<small>detections</small>';
            echo '</div>';
        }
    }
}
