<?php
/**
 * License Manager Class
 * Handles license validation, activation, and feature management
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PayPerCrawl_License_Manager {
    
    /**
     * API endpoint base URL
     */
    private $api_base_url;
    
    /**
     * License cache duration in seconds
     */
    private $cache_duration = 3600; // 1 hour
    
    /**
     * Grace period for network failures (days)
     */
    private $grace_period_days = 7;
    
    /**
     * Option keys for storing license data
     */
    const OPTION_LICENSE_KEY = 'paypercrawl_license_key';
    const OPTION_LICENSE_DATA = 'paypercrawl_license_data';
    const OPTION_ACTIVATION_TOKEN = 'paypercrawl_activation_token';
    const OPTION_LICENSE_CACHE = 'paypercrawl_license_cache';
    const OPTION_PUBLIC_KEY = 'paypercrawl_public_key';
    const OPTION_LAST_HEARTBEAT = 'paypercrawl_last_heartbeat';
    const OPTION_OFFLINE_VALIDATION_COUNT = 'paypercrawl_offline_count';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_base_url = defined('PAYPERCRAWL_API_URL') 
            ? PAYPERCRAWL_API_URL 
            : 'https://api.crawlguard.com';
        
        // Schedule daily heartbeat
        $this->schedule_heartbeat();
        
        // Hook into admin notices
        add_action('admin_notices', array($this, 'display_license_notices'));
    }
    
    /**
     * Activate a license key
     */
    public function activate_license($license_key) {
        try {
            // Validate license key format
            if (!$this->validate_license_format($license_key)) {
                return array(
                    'success' => false,
                    'error' => __('Invalid license key format', 'paypercrawl')
                );
            }
            
            // Store license key
            update_option(self::OPTION_LICENSE_KEY, sanitize_text_field($license_key));
            
            // Prepare activation data
            $activation_data = array(
                'licenseKey' => $license_key,
                'siteUrl' => get_site_url(),
                'machineId' => $this->get_machine_id(),
                'wordpressVersion' => get_bloginfo('version'),
                'pluginVersion' => PAYPERCRAWL_VERSION,
                'phpVersion' => PHP_VERSION
            );
            
            // Call activation API
            $response = $this->api_request('/licenses/activate', 'POST', $activation_data);
            
            if ($response && isset($response['success']) && $response['success']) {
                // Store activation token
                update_option(self::OPTION_ACTIVATION_TOKEN, $response['activationToken']);
                
                // Validate and cache license data
                $validation = $this->validate_license($license_key);
                
                if ($validation['valid']) {
                    // Store license data
                    update_option(self::OPTION_LICENSE_DATA, $validation);
                    
                    // Download and store public key for offline validation
                    $this->update_public_key();
                    
                    // Log activation
                    $this->log_activation($license_key, true);
                    
                    return array(
                        'success' => true,
                        'message' => __('License activated successfully', 'paypercrawl'),
                        'tier' => $validation['tier'],
                        'features' => $validation['features']
                    );
                }
            }
            
            return array(
                'success' => false,
                'error' => isset($response['error']) 
                    ? $response['error'] 
                    : __('Failed to activate license', 'paypercrawl')
            );
            
        } catch (Exception $e) {
            error_log('License activation error: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => __('An error occurred during activation', 'paypercrawl')
            );
        }
    }
    
    /**
     * Deactivate the current license
     */
    public function deactivate_license() {
        $license_key = get_option(self::OPTION_LICENSE_KEY);
        
        if (!$license_key) {
            return array(
                'success' => false,
                'error' => __('No active license found', 'paypercrawl')
            );
        }
        
        try {
            // Call deactivation API
            $response = $this->api_request('/licenses/deactivate', 'POST', array(
                'licenseKey' => $license_key,
                'siteUrl' => get_site_url()
            ));
            
            // Clear local license data regardless of API response
            $this->clear_license_data();
            
            return array(
                'success' => true,
                'message' => __('License deactivated successfully', 'paypercrawl')
            );
            
        } catch (Exception $e) {
            // Still clear local data even if API call fails
            $this->clear_license_data();
            
            return array(
                'success' => true,
                'message' => __('License deactivated locally', 'paypercrawl')
            );
        }
    }
    
    /**
     * Validate current license
     */
    public function validate_license($license_key = null, $force_refresh = false) {
        if (!$license_key) {
            $license_key = get_option(self::OPTION_LICENSE_KEY);
        }
        
        if (!$license_key) {
            return array(
                'valid' => false,
                'status' => 'no_license',
                'message' => __('No license key found', 'paypercrawl')
            );
        }
        
        // Check cache first unless force refresh
        if (!$force_refresh) {
            $cached = $this->get_cached_license_data();
            if ($cached !== false) {
                return $cached;
            }
        }
        
        try {
            // Try online validation first
            $response = $this->api_request('/licenses/validate', 'POST', array(
                'licenseKey' => $license_key,
                'siteUrl' => get_site_url(),
                'wordpressVersion' => get_bloginfo('version'),
                'pluginVersion' => PAYPERCRAWL_VERSION,
                'phpVersion' => PHP_VERSION
            ));
            
            if ($response) {
                // Reset offline validation count on successful online validation
                update_option(self::OPTION_OFFLINE_VALIDATION_COUNT, 0);
                
                // Cache the response
                $this->cache_license_data($response);
                
                return $response;
            }
            
        } catch (Exception $e) {
            // Fall back to offline validation
            return $this->validate_offline($license_key);
        }
        
        // If all validation methods fail, check grace period
        return $this->check_grace_period();
    }
    
    /**
     * Offline license validation using RSA signature
     */
    private function validate_offline($license_key) {
        try {
            $public_key = get_option(self::OPTION_PUBLIC_KEY);
            $license_data = get_option(self::OPTION_LICENSE_DATA);
            
            if (!$public_key || !$license_data) {
                return $this->check_grace_period();
            }
            
            // Increment offline validation count
            $offline_count = get_option(self::OPTION_OFFLINE_VALIDATION_COUNT, 0);
            update_option(self::OPTION_OFFLINE_VALIDATION_COUNT, $offline_count + 1);
            
            // If too many offline validations, require online check
            if ($offline_count > 30) { // 30 days of offline validation
                return array(
                    'valid' => false,
                    'status' => 'requires_online',
                    'message' => __('Online validation required', 'paypercrawl')
                );
            }
            
            // Verify signature if available
            if (isset($license_data['signature'])) {
                $data_to_verify = json_encode(array(
                    'key' => $license_key,
                    'email' => $license_data['customer_email'],
                    'tier' => $license_data['tier'],
                    'validUntil' => $license_data['validUntil'],
                    'features' => $license_data['features']
                ));
                
                $signature_valid = openssl_verify(
                    $data_to_verify,
                    base64_decode($license_data['signature']),
                    $public_key,
                    OPENSSL_ALGO_SHA256
                );
                
                if ($signature_valid !== 1) {
                    return array(
                        'valid' => false,
                        'status' => 'invalid_signature',
                        'message' => __('License signature verification failed', 'paypercrawl')
                    );
                }
            }
            
            // Check expiration
            if (isset($license_data['validUntil'])) {
                $valid_until = strtotime($license_data['validUntil']);
                if ($valid_until && $valid_until < time()) {
                    return $this->check_grace_period();
                }
            }
            
            return array(
                'valid' => true,
                'status' => 'offline_valid',
                'message' => __('License validated offline', 'paypercrawl'),
                'tier' => $license_data['tier'],
                'features' => $license_data['features']
            );
            
        } catch (Exception $e) {
            error_log('Offline validation error: ' . $e->getMessage());
            return $this->check_grace_period();
        }
    }
    
    /**
     * Check if license is in grace period
     */
    private function check_grace_period() {
        $license_data = get_option(self::OPTION_LICENSE_DATA);
        
        if (!$license_data) {
            return array(
                'valid' => false,
                'status' => 'no_data',
                'message' => __('No license data available', 'paypercrawl')
            );
        }
        
        // Check if we're within grace period
        $last_valid = isset($license_data['last_valid_check']) 
            ? $license_data['last_valid_check'] 
            : 0;
        
        $grace_period_end = $last_valid + ($this->grace_period_days * 24 * 60 * 60);
        
        if (time() < $grace_period_end) {
            return array(
                'valid' => true,
                'status' => 'grace_period',
                'message' => sprintf(
                    __('License in grace period (expires %s)', 'paypercrawl'),
                    date_i18n(get_option('date_format'), $grace_period_end)
                ),
                'tier' => $license_data['tier'],
                'features' => $license_data['features']
            );
        }
        
        return array(
            'valid' => false,
            'status' => 'expired',
            'message' => __('License expired and grace period ended', 'paypercrawl')
        );
    }
    
    /**
     * Perform license heartbeat
     */
    public function heartbeat() {
        $activation_token = get_option(self::OPTION_ACTIVATION_TOKEN);
        
        if (!$activation_token) {
            return false;
        }
        
        try {
            $response = $this->api_request('/licenses/heartbeat', 'POST', array(
                'activationToken' => $activation_token
            ));
            
            if ($response && isset($response['success']) && $response['success']) {
                update_option(self::OPTION_LAST_HEARTBEAT, time());
                
                // Re-validate license after heartbeat
                $this->validate_license(null, true);
                
                return true;
            }
            
        } catch (Exception $e) {
            error_log('Heartbeat error: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Schedule daily heartbeat
     */
    private function schedule_heartbeat() {
        if (!wp_next_scheduled('paypercrawl_license_heartbeat')) {
            wp_schedule_event(time(), 'daily', 'paypercrawl_license_heartbeat');
        }
        
        add_action('paypercrawl_license_heartbeat', array($this, 'heartbeat'));
    }
    
    /**
     * Check if a feature is enabled for current license
     */
    public function has_feature($feature_key) {
        $license_data = $this->get_cached_license_data();
        
        if (!$license_data || !$license_data['valid']) {
            return false;
        }
        
        if (!isset($license_data['features'][$feature_key])) {
            return false;
        }
        
        $feature = $license_data['features'][$feature_key];
        
        // Check if feature is enabled
        if (is_array($feature)) {
            return isset($feature['enabled']) && $feature['enabled'];
        }
        
        return (bool) $feature;
    }
    
    /**
     * Get feature limit
     */
    public function get_feature_limit($feature_key) {
        $license_data = $this->get_cached_license_data();
        
        if (!$license_data || !$license_data['valid']) {
            return 0;
        }
        
        if (!isset($license_data['features'][$feature_key])) {
            return 0;
        }
        
        $feature = $license_data['features'][$feature_key];
        
        if (is_array($feature) && isset($feature['limit'])) {
            return (int) $feature['limit'];
        }
        
        return PHP_INT_MAX; // Unlimited
    }
    
    /**
     * Get current license tier
     */
    public function get_tier() {
        $license_data = $this->get_cached_license_data();
        
        if (!$license_data || !$license_data['valid']) {
            return 'free';
        }
        
        return isset($license_data['tier']) ? $license_data['tier'] : 'free';
    }
    
    /**
     * Display admin notices for license status
     */
    public function display_license_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $license_data = $this->get_cached_license_data();
        
        if (!$license_data || !$license_data['valid']) {
            $message = __('PayPerCrawl: No valid license found. Please activate your license to enable premium features.', 'paypercrawl');
            echo '<div class="notice notice-warning"><p>' . esc_html($message) . ' ';
            echo '<a href="' . esc_url(admin_url('admin.php?page=paypercrawl-license')) . '">';
            echo __('Activate License', 'paypercrawl') . '</a></p></div>';
            return;
        }
        
        // Check for grace period warning
        if (isset($license_data['status']) && $license_data['status'] === 'grace_period') {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html($license_data['message']);
            echo '</p></div>';
        }
        
        // Check for expiration warning
        if (isset($license_data['validUntil'])) {
            $valid_until = strtotime($license_data['validUntil']);
            $days_until_expiry = ($valid_until - time()) / (24 * 60 * 60);
            
            if ($days_until_expiry > 0 && $days_until_expiry <= 7) {
                $message = sprintf(
                    __('PayPerCrawl: Your license expires in %d days. Please renew to continue using premium features.', 'paypercrawl'),
                    (int) $days_until_expiry
                );
                echo '<div class="notice notice-warning"><p>' . esc_html($message) . '</p></div>';
            }
        }
    }
    
    /**
     * API request helper
     */
    private function api_request($endpoint, $method = 'GET', $data = null) {
        $url = $this->api_base_url . '/api' . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Plugin-Version' => PAYPERCRAWL_VERSION,
                'X-WordPress-Version' => get_bloginfo('version'),
                'X-Site-URL' => get_site_url()
            )
        );
        
        if ($data) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response');
        }
        
        return $decoded;
    }
    
    /**
     * Validate license key format
     */
    private function validate_license_format($license_key) {
        // Format: XXXX-XXXX-XXXX-XXXX
        return preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/i', $license_key);
    }
    
    /**
     * Get machine ID for hardware fingerprinting
     */
    private function get_machine_id() {
        $machine_id = get_option('paypercrawl_machine_id');
        
        if (!$machine_id) {
            $machine_id = wp_hash(
                get_site_url() . 
                ABSPATH . 
                (defined('DB_NAME') ? DB_NAME : '') .
                (defined('DB_HOST') ? DB_HOST : '')
            );
            update_option('paypercrawl_machine_id', $machine_id);
        }
        
        return $machine_id;
    }
    
    /**
     * Update public key for offline validation
     */
    private function update_public_key() {
        try {
            $response = $this->api_request('/licenses/public-key', 'GET');
            
            if ($response && isset($response['publicKey'])) {
                update_option(self::OPTION_PUBLIC_KEY, $response['publicKey']);
                return true;
            }
        } catch (Exception $e) {
            error_log('Failed to update public key: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Cache license data
     */
    private function cache_license_data($data) {
        $cached_data = array(
            'data' => $data,
            'timestamp' => time(),
            'last_valid_check' => $data['valid'] ? time() : get_option('paypercrawl_last_valid_check', 0)
        );
        
        update_option(self::OPTION_LICENSE_CACHE, $cached_data);
        
        if ($data['valid']) {
            update_option('paypercrawl_last_valid_check', time());
        }
    }
    
    /**
     * Get cached license data
     */
    private function get_cached_license_data() {
        $cached = get_option(self::OPTION_LICENSE_CACHE);
        
        if (!$cached || !isset($cached['timestamp'])) {
            return false;
        }
        
        // Check if cache is still valid
        if (time() - $cached['timestamp'] > $this->cache_duration) {
            return false;
        }
        
        return isset($cached['data']) ? $cached['data'] : false;
    }
    
    /**
     * Clear all license data
     */
    private function clear_license_data() {
        delete_option(self::OPTION_LICENSE_KEY);
        delete_option(self::OPTION_LICENSE_DATA);
        delete_option(self::OPTION_ACTIVATION_TOKEN);
        delete_option(self::OPTION_LICENSE_CACHE);
        delete_option(self::OPTION_LAST_HEARTBEAT);
        delete_option(self::OPTION_OFFLINE_VALIDATION_COUNT);
        delete_option('paypercrawl_last_valid_check');
        
        // Clear scheduled events
        wp_clear_scheduled_hook('paypercrawl_license_heartbeat');
    }
    
    /**
     * Log license activation
     */
    private function log_activation($license_key, $success) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'license_key' => substr($license_key, 0, 4) . '-****-****-' . substr($license_key, -4),
            'success' => $success,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        
        $log = get_option('paypercrawl_activation_log', array());
        array_unshift($log, $log_entry);
        
        // Keep only last 50 entries
        $log = array_slice($log, 0, 50);
        
        update_option('paypercrawl_activation_log', $log);
    }
}

// Initialize license manager
global $paypercrawl_license_manager;
$paypercrawl_license_manager = new PayPerCrawl_License_Manager();
