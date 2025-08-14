<?php
/**
 * Admin Interface Handler
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PayPerCrawl_Admin {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // AJAX handlers
        add_action('wp_ajax_paypercrawl_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_paypercrawl_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_paypercrawl_generate_api_key', array($this, 'ajax_generate_api_key'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'PayPerCrawl',
            'PayPerCrawl',
            'manage_options',
            'paypercrawl',
            array($this, 'dashboard_page'),
            'dashicons-money-alt',
            30
        );
        
        add_submenu_page(
            'paypercrawl',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'paypercrawl',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'paypercrawl',
            'Analytics',
            'Analytics',
            'manage_options',
            'paypercrawl-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'paypercrawl',
            'Settings',
            'Settings',
            'manage_options',
            'paypercrawl-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'paypercrawl') === false) {
            return;
        }
        
        wp_enqueue_style(
            'paypercrawl-admin',
            PAYPERCRAWL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PAYPERCRAWL_VERSION
        );
        
        wp_enqueue_script(
            'paypercrawl-admin',
            PAYPERCRAWL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PAYPERCRAWL_VERSION,
            true
        );
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_localize_script('paypercrawl-admin', 'paypercrawl_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('paypercrawl_nonce')
        ));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('paypercrawl_settings', 'paypercrawl_options', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }
        
        if (isset($input['api_url'])) {
            $sanitized['api_url'] = esc_url_raw($input['api_url']);
        }
        
        if (isset($input['worker_url'])) {
            $sanitized['worker_url'] = esc_url_raw($input['worker_url']);
        }
        
        if (isset($input['bot_action'])) {
            $sanitized['bot_action'] = in_array($input['bot_action'], array('block', 'allow')) ? 
                $input['bot_action'] : 'block';
        }
        
        if (isset($input['js_detection'])) {
            $sanitized['js_detection'] = (bool) $input['js_detection'];
        }
        
        return $sanitized;
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        $missing_credentials = $this->audit_credentials();
        
        if (!empty($missing_credentials) && $this->is_paypercrawl_page()) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>⚠️ Missing credentials detected:</strong> ' . implode(', ', $missing_credentials) . '</p>';
            echo '<p>Head to <a href="' . admin_url('admin.php?page=paypercrawl-settings') . '">Settings → PayPerCrawl</a> to enter them.</p>';
            echo '</div>';
        }
        
        // Early access banner
        if ($this->is_paypercrawl_page()) {
            echo '<div class="notice notice-success" style="border-left-color: #16a34a;">';
            echo '<p><strong>🎉 Early Access Beta:</strong> You keep 100% of your revenue! No fees during beta period.</p>';
            echo '</div>';
        }
    }
    
    /**
     * Check if current page is PayPerCrawl page
     */
    private function is_paypercrawl_page() {
        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'paypercrawl') !== false;
    }
    
    /**
     * Audit credentials
     */
    public function audit_credentials() {
        $needed = array('api_key', 'worker_url');
        $missing = array();

        $options = get_option('paypercrawl_options', array());

        // Auto-populate from .env if available
        if (empty($options['api_key'])) {
            $env_api_key = $this->get_env_value('API_BASE_URL');
            if ($env_api_key) {
                $options['api_key'] = 'ppc_' . wp_generate_password(32, false);
                $options['api_url'] = $env_api_key;
                update_option('paypercrawl_options', $options);
            } else {
                // Fallback to new domain
                $options['api_url'] = 'https://api.paypercrawl.tech/v1';
            }
        }

        if (empty($options['worker_url'])) {
            $env_worker_url = $this->get_env_value('CLOUDFLARE_WORKER_URL');
            if ($env_worker_url) {
                $options['worker_url'] = $env_worker_url;
                update_option('paypercrawl_options', $options);
            } else {
                // Fallback to production worker
                $options['worker_url'] = 'https://crawlguard-api-prod.crawlguard-api.workers.dev';
            }
        }

        foreach ($needed as $key) {
            if (empty($options[$key])) {
                $missing[] = 'paypercrawl_' . $key;
            }
        }

        return $missing;
    }

    /**
     * Get value from .env file
     */
    private function get_env_value($key) {
        $env_file = ABSPATH . '../.env';
        if (!file_exists($env_file)) {
            $env_file = ABSPATH . '.env';
        }

        if (file_exists($env_file)) {
            $env_content = file_get_contents($env_file);
            if (preg_match('/^' . preg_quote($key) . '=(.*)$/m', $env_content, $matches)) {
                return trim($matches[1]);
            }
        }

        return false;
    }

    /**
     * Check if feature is enabled from environment
     */
    private function is_feature_enabled($feature) {
        $env_value = $this->get_env_value('FEATURE_' . strtoupper($feature));
        return $env_value === 'true';
    }

    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include PAYPERCRAWL_PLUGIN_DIR . 'templates/dashboard.php';
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        include PAYPERCRAWL_PLUGIN_DIR . 'templates/analytics.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include PAYPERCRAWL_PLUGIN_DIR . 'templates/settings.php';
    }
    
    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $options = get_option('paypercrawl_options', array());
        $api_url = $options['api_url'] ?? 'https://api.creativeinteriorsstudio.com/v1';
        
        $response = wp_remote_get($api_url . '/status', array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'PayPerCrawl-WP/' . PAYPERCRAWL_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
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
    
    /**
     * AJAX: Get analytics
     */
    public function ajax_get_analytics() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $analytics = PayPerCrawl_Analytics::get_instance();
        $data = $analytics->get_dashboard_data();
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Generate API key
     */
    public function ajax_generate_api_key() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $api_key = 'ppc_' . wp_generate_password(32, false);
        
        $options = get_option('paypercrawl_options', array());
        $options['api_key'] = $api_key;
        update_option('paypercrawl_options', $options);
        
        wp_send_json_success(array(
            'api_key' => $api_key
        ));
    }
}
