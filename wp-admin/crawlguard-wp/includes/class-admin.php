<?php

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_crawlguard_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_crawlguard_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_crawlguard_generate_api_key', array($this, 'ajax_generate_api_key'));
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
            'Analytics',
            'Analytics',
            'manage_options',
            'crawlguard-analytics',
            array($this, 'analytics_page')
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
    
    public function init_settings() {
        register_setting('crawlguard_options', 'crawlguard_options', array($this, 'sanitize_options'));
        
        add_settings_section(
            'crawlguard_main',
            'Main Settings',
            array($this, 'main_section_callback'),
            'crawlguard'
        );
        
        add_settings_field(
            'api_url',
            'API URL',
            array($this, 'api_url_callback'),
            'crawlguard',
            'crawlguard_main'
        );
        
        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'crawlguard',
            'crawlguard_main'
        );
        
        add_settings_field(
            'monetization_enabled',
            'Enable Monetization',
            array($this, 'monetization_callback'),
            'crawlguard',
            'crawlguard_main'
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'crawlguard') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_enqueue_script(
            'crawlguard-admin',
            CRAWLGUARD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chart-js'),
            CRAWLGUARD_VERSION,
            true
        );
        
        wp_localize_script('crawlguard-admin', 'crawlguard_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crawlguard_nonce')
        ));
        
        wp_enqueue_style(
            'crawlguard-admin',
            CRAWLGUARD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CRAWLGUARD_VERSION
        );
    }
    
    public function admin_page() {
        $options = get_option('crawlguard_options');
        ?>
        <div class="wrap">
            <h1>CrawlGuard WP Dashboard</h1>
            
            <div class="crawlguard-dashboard">
                <div class="crawlguard-card">
                    <h2>API Connection Status</h2>
                    <div id="connection-status">
                        <span class="status-indicator" id="status-dot"></span>
                        <span id="status-text">Checking...</span>
                        <button type="button" class="button" id="test-connection">Test Connection</button>
                    </div>
                </div>
                
                <div class="crawlguard-card">
                    <h2>Recent Bot Detections</h2>
                    <div id="recent-detections">
                        <p>Loading recent detections...</p>
                    </div>
                </div>
                
                <div class="crawlguard-card">
                    <h2>Analytics Summary</h2>
                    <div class="analytics-grid">
                        <div class="metric">
                            <h3>Total Requests Today</h3>
                            <span class="metric-value" id="total-requests">-</span>
                        </div>
                        <div class="metric">
                            <h3>Bot Requests Today</h3>
                            <span class="metric-value" id="bot-requests">-</span>
                        </div>
                        <div class="metric">
                            <h3>Revenue Generated</h3>
                            <span class="metric-value" id="revenue-generated">$0.00</span>
                        </div>
                        <div class="metric">
                            <h3>Detection Accuracy</h3>
                            <span class="metric-value" id="detection-accuracy">95%</span>
                        </div>
                    </div>
                </div>
                
                <div class="crawlguard-card">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions">
                        <button type="button" class="button button-primary" id="generate-api-key">Generate API Key</button>
                        <a href="<?php echo admin_url('admin.php?page=crawlguard-analytics'); ?>" class="button">View Full Analytics</a>
                        <a href="<?php echo admin_url('admin.php?page=crawlguard-settings'); ?>" class="button">Settings</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1>CrawlGuard Analytics</h1>
            
            <div class="crawlguard-analytics">
                <div class="crawlguard-card">
                    <h2>Bot Detection Chart</h2>
                    <canvas id="bot-detection-chart" width="400" height="200"></canvas>
                </div>
                
                <div class="crawlguard-card">
                    <h2>Revenue Chart</h2>
                    <canvas id="revenue-chart" width="400" height="200"></canvas>
                </div>
                
                <div class="crawlguard-card">
                    <h2>Top Bots</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Bot Name</th>
                                <th>Company</th>
                                <th>Requests</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody id="top-bots-table">
                            <tr>
                                <td colspan="4">Loading data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>CrawlGuard Settings</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('crawlguard_options');
                do_settings_sections('crawlguard');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function main_section_callback() {
        echo '<p>Configure your CrawlGuard WP settings below.</p>';
    }
    
    public function api_url_callback() {
        $options = get_option('crawlguard_options');
        $value = isset($options['api_url']) ? $options['api_url'] : 'https://api.creativeinteriorsstudio.com/v1';
        echo '<input type="url" name="crawlguard_options[api_url]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    public function api_key_callback() {
        $options = get_option('crawlguard_options');
        $value = isset($options['api_key']) ? $options['api_key'] : '';
        echo '<input type="text" name="crawlguard_options[api_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your API key for connecting to CrawlGuard services.</p>';
    }
    
    public function monetization_callback() {
        $options = get_option('crawlguard_options');
        $value = isset($options['monetization_enabled']) ? $options['monetization_enabled'] : false;
        echo '<input type="checkbox" name="crawlguard_options[monetization_enabled]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label>Enable monetization of bot traffic</label>';
    }
    
    public function sanitize_options($input) {
        $sanitized = array();
        
        if (isset($input['api_url'])) {
            $sanitized['api_url'] = esc_url_raw($input['api_url']);
        }
        
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }
        
        if (isset($input['monetization_enabled'])) {
            $sanitized['monetization_enabled'] = (bool) $input['monetization_enabled'];
        }
        
        return $sanitized;
    }
    
    public function ajax_get_analytics() {
        check_ajax_referer('crawlguard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        $today = date('Y-m-d');
        $total_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = %s",
            $today
        ));
        
        $bot_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = %s AND bot_detected = 1",
            $today
        ));
        
        $revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(revenue_generated) FROM $table_name WHERE DATE(timestamp) = %s",
            $today
        ));
        
        wp_send_json_success(array(
            'total_requests' => intval($total_requests),
            'bot_requests' => intval($bot_requests),
            'revenue_generated' => floatval($revenue ?: 0)
        ));
    }
    
    public function ajax_test_connection() {
        check_ajax_referer('crawlguard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $options = get_option('crawlguard_options');
        $api_url = $options['api_url'] ?? 'https://api.creativeinteriorsstudio.com/v1';
        
        $response = wp_remote_get($api_url . '/status', array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Connection failed: ' . $response->get_error_message()
            ));
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            wp_send_json_success(array(
                'message' => 'Connection successful'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'API returned status code: ' . $status_code
            ));
        }
    }
    
    public function ajax_generate_api_key() {
        check_ajax_referer('crawlguard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $api_key = 'cg_' . wp_generate_password(32, false);
        
        $options = get_option('crawlguard_options');
        $options['api_key'] = $api_key;
        update_option('crawlguard_options', $options);
        
        wp_send_json_success(array(
            'api_key' => $api_key,
            'message' => 'API key generated successfully'
        ));
    }
}
