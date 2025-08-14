<?php
/**
 * Plugin Name: PayPerCrawl Integration Module
 * Description: Connects WordPress sites to PayPerCrawl API for bot detection and monetization
 * Version: 1.0.0
 * Author: PayPerCrawl
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PPC_INTEGRATION_VERSION', '1.0.0');
define('PPC_API_BASE', 'https://api.paypercrawl.tech/api/v1');
define('PPC_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main PayPerCrawl Integration Class
 */
class PayPerCrawl_Integration {
    
    private static $instance = null;
    private $api_key;
    private $site_id;
    private $settings;
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->loadSettings();
        $this->initHooks();
    }
    
    /**
     * Load settings from database
     */
    private function loadSettings() {
        $this->settings = get_option('paypercrawl_settings', [
            'api_key' => '',
            'site_id' => '',
            'enabled' => true,
            'detection_mode' => 'moderate', // aggressive, moderate, passive
            'bot_action' => 'monitor', // monitor, challenge, block
            'cache_ttl' => 300,
            'enable_analytics' => true,
        ]);
        
        $this->api_key = $this->settings['api_key'];
        $this->site_id = $this->settings['site_id'];
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks() {
        // Admin hooks
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        
        // Frontend detection
        add_action('init', [$this, 'detectBot'], 1);
        add_action('template_redirect', [$this, 'handleBotAction'], 2);
        
        // API hooks
        add_action('rest_api_init', [$this, 'registerApiEndpoints']);
        
        // AJAX handlers
        add_action('wp_ajax_ppc_verify_api', [$this, 'ajaxVerifyApi']);
        add_action('wp_ajax_ppc_get_analytics', [$this, 'ajaxGetAnalytics']);
        
        // Cron jobs
        add_action('ppc_sync_analytics', [$this, 'syncAnalytics']);
        
        // Activation/Deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    /**
     * Detect bot on page load
     */
    public function detectBot() {
        if (!$this->settings['enabled'] || !$this->api_key) {
            return;
        }
        
        // Skip admin and logged-in users
        if (is_admin() || is_user_logged_in()) {
            return;
        }
        
        $request_data = $this->collectRequestData();
        $detection_result = $this->callDetectionApi($request_data);
        
        if ($detection_result) {
            // Store in session for action handling
            if (!session_id()) {
                session_start();
            }
            $_SESSION['ppc_detection'] = $detection_result;
            
            // Log locally for analytics
            $this->logDetection($detection_result);
        }
    }
    
    /**
     * Collect request data for detection
     */
    private function collectRequestData() {
        return [
            'ip' => $this->getClientIp(),
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'],
            'path' => $_SERVER['REQUEST_URI'],
            'headers' => $this->getRequestHeaders(),
            'siteId' => $this->site_id,
            'timestamp' => time(),
        ];
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get request headers
     */
    private function getRequestHeaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($header)] = $value;
            }
        }
        return $headers;
    }
    
    /**
     * Call PayPerCrawl Detection API
     */
    private function callDetectionApi($data) {
        $cache_key = 'ppc_detection_' . md5(json_encode($data));
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_post(PPC_API_BASE . '/detections/log', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
                'X-Signature' => $this->generateSignature($data),
                'X-Timestamp' => time(),
            ],
            'body' => json_encode($data),
            'timeout' => 5,
        ]);
        
        if (is_wp_error($response)) {
            error_log('PayPerCrawl API Error: ' . $response->get_error_message());
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if ($result && isset($result['data'])) {
            set_transient($cache_key, $result['data'], $this->settings['cache_ttl']);
            return $result['data'];
        }
        
        return null;
    }
    
    /**
     * Generate HMAC signature
     */
    private function generateSignature($data) {
        $payload = json_encode($data);
        return hash_hmac('sha256', $payload, $this->api_key);
    }
    
    /**
     * Handle bot action based on detection
     */
    public function handleBotAction() {
        if (!isset($_SESSION['ppc_detection'])) {
            return;
        }
        
        $detection = $_SESSION['ppc_detection'];
        
        if (!$detection['isBot']) {
            return;
        }
        
        switch ($detection['action']) {
            case 'BLOCK':
                $this->blockBot($detection);
                break;
                
            case 'CHALLENGE':
                $this->challengeBot($detection);
                break;
                
            case 'MONITOR':
                // Just log, no action
                break;
        }
    }
    
    /**
     * Block bot access
     */
    private function blockBot($detection) {
        header('HTTP/1.1 403 Forbidden');
        wp_die(
            '<h1>Access Denied</h1>' .
            '<p>Your request has been blocked by PayPerCrawl Bot Protection.</p>' .
            '<p>If you believe this is an error, please contact the site administrator.</p>' .
            '<p>Detection ID: ' . esc_html($detection['id'] ?? 'N/A') . '</p>',
            'Access Denied',
            ['response' => 403]
        );
    }
    
    /**
     * Challenge bot with proof of work
     */
    private function challengeBot($detection) {
        // Get challenge from API
        $response = wp_remote_post(PPC_API_BASE . '/detections/challenge', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
            ],
            'body' => json_encode(['ip' => $this->getClientIp()]),
        ]);
        
        if (!is_wp_error($response)) {
            $challenge = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($challenge && isset($challenge['data'])) {
                // Display challenge page
                include PPC_PLUGIN_PATH . 'templates/challenge.php';
                exit;
            }
        }
        
        // Fallback to block if challenge fails
        $this->blockBot($detection);
    }
    
    /**
     * Log detection locally
     */
    private function logDetection($detection) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ppc_detections';
        
        $wpdb->insert($table_name, [
            'ip' => $detection['ip'] ?? '',
            'user_agent' => $detection['userAgent'] ?? '',
            'is_bot' => $detection['isBot'] ? 1 : 0,
            'bot_type' => $detection['botType'] ?? null,
            'confidence' => $detection['confidence'] ?? 0,
            'action' => $detection['action'] ?? 'ALLOW',
            'timestamp' => current_time('mysql'),
        ]);
    }
    
    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        add_menu_page(
            'PayPerCrawl',
            'PayPerCrawl',
            'manage_options',
            'paypercrawl',
            [$this, 'renderDashboard'],
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'paypercrawl',
            'Settings',
            'Settings',
            'manage_options',
            'paypercrawl-settings',
            [$this, 'renderSettings']
        );
        
        add_submenu_page(
            'paypercrawl',
            'Analytics',
            'Analytics',
            'manage_options',
            'paypercrawl-analytics',
            [$this, 'renderAnalytics']
        );
    }
    
    /**
     * Render dashboard page
     */
    public function renderDashboard() {
        include PPC_PLUGIN_PATH . 'templates/dashboard.php';
    }
    
    /**
     * Render settings page
     */
    public function renderSettings() {
        include PPC_PLUGIN_PATH . 'templates/settings.php';
    }
    
    /**
     * Render analytics page
     */
    public function renderAnalytics() {
        include PPC_PLUGIN_PATH . 'templates/analytics.php';
    }
    
    /**
     * Register API endpoints
     */
    public function registerApiEndpoints() {
        register_rest_route('paypercrawl/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handleWebhook'],
            'permission_callback' => [$this, 'verifyWebhookSignature'],
        ]);
        
        register_rest_route('paypercrawl/v1', '/verify', [
            'methods' => 'GET',
            'callback' => [$this, 'verifySite'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * Handle webhook from PayPerCrawl API
     */
    public function handleWebhook($request) {
        $body = $request->get_json_params();
        
        // Process webhook based on event type
        switch ($body['event'] ?? '') {
            case 'detection.blocked':
                // Handle blocked bot event
                do_action('ppc_bot_blocked', $body['data']);
                break;
                
            case 'subscription.updated':
                // Update local subscription status
                update_option('ppc_subscription', $body['data']);
                break;
                
            case 'settings.updated':
                // Update local settings
                $this->updateSettings($body['data']);
                break;
        }
        
        return ['success' => true];
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($request) {
        $signature = $request->get_header('X-Signature');
        $timestamp = $request->get_header('X-Timestamp');
        
        if (!$signature || !$timestamp) {
            return false;
        }
        
        // Verify timestamp is recent (within 5 minutes)
        if (abs(time() - intval($timestamp)) > 300) {
            return false;
        }
        
        $body = $request->get_body();
        $expected = hash_hmac('sha256', $timestamp . '.' . $body, $this->api_key);
        
        return hash_equals($expected, $signature);
    }
    
    /**
     * Verify site ownership
     */
    public function verifySite() {
        $token = get_option('ppc_verify_token');
        
        if (!$token) {
            $token = wp_generate_password(32, false);
            update_option('ppc_verify_token', $token);
        }
        
        return [
            'success' => true,
            'token' => $token,
            'site_url' => home_url(),
        ];
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database table
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ppc_detections';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            ip varchar(45) NOT NULL,
            user_agent text,
            is_bot tinyint(1) DEFAULT 0,
            bot_type varchar(100),
            confidence float DEFAULT 0,
            action varchar(20),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ip_index (ip),
            KEY timestamp_index (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Schedule cron jobs
        if (!wp_next_scheduled('ppc_sync_analytics')) {
            wp_schedule_event(time(), 'hourly', 'ppc_sync_analytics');
        }
        
        // Set default options
        add_option('paypercrawl_settings', [
            'api_key' => '',
            'site_id' => '',
            'enabled' => false,
            'detection_mode' => 'moderate',
            'bot_action' => 'monitor',
            'cache_ttl' => 300,
            'enable_analytics' => true,
        ]);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('ppc_sync_analytics');
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ppc_%'");
    }
}

// Initialize plugin
PayPerCrawl_Integration::getInstance();
