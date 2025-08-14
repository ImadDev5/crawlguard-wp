<?php
/**
 * API Client for CrawlGuard Backend
 * 
 * Handles communication with our Cloudflare Workers backend
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_API_Client {
    
    private $api_base_url = 'https://paypercrawl.tech/api/v1'; // Updated API endpoint
    private $api_key;
    private $timeout = 5; // 5 second timeout to avoid slowing down sites
    
    public function __construct() {
        $options = get_option('crawlguard_options');
        $this->api_key = $options['api_key'] ?? '';
    }
    
    /**
     * Send monetization request to backend
     */
    public function send_monetization_request($request_data) {
        if (empty($this->api_key)) {
            return false;
        }
        
        $endpoint = $this->api_base_url . '/v1/monetize';
        
        $payload = array(
            'api_key' => $this->api_key,
            'request_data' => $request_data
        );
        
        return $this->make_request('POST', $endpoint, $payload);
    }
    
    /**
     * Register site with backend
     */
    public function register_site($site_data) {
        $endpoint = $this->api_base_url . '/v1/sites/register';
        
        $payload = array(
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'admin_email' => get_option('admin_email'),
            'plugin_version' => CRAWLGUARD_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'site_data' => $site_data
        );
        
        return $this->make_request('POST', $endpoint, $payload);
    }
    
    /**
     * Get analytics data
     */
    public function get_analytics($date_range = '30d') {
        if (empty($this->api_key)) {
            return false;
        }
        
        $endpoint = $this->api_base_url . '/v1/analytics';
        
        $params = array(
            'api_key' => $this->api_key,
            'site_url' => get_site_url(),
            'range' => $date_range
        );
        
        return $this->make_request('GET', $endpoint, $params);
    }
    
    /**
     * Update site settings
     */
    public function update_site_settings($settings) {
        if (empty($this->api_key)) {
            return false;
        }
        
        $endpoint = $this->api_base_url . '/v1/sites/settings';
        
        $payload = array(
            'api_key' => $this->api_key,
            'site_url' => get_site_url(),
            'settings' => $settings
        );
        
        return $this->make_request('PUT', $endpoint, $payload);
    }
    
    /**
     * Get payment status
     */
    public function get_payment_status($payment_id) {
        if (empty($this->api_key)) {
            return false;
        }
        
        $endpoint = $this->api_base_url . '/v1/payments/' . $payment_id;
        
        $params = array(
            'api_key' => $this->api_key
        );
        
        return $this->make_request('GET', $endpoint, $params);
    }
    
    /**
     * Validate API key
     */
    public function validate_api_key($api_key = null) {
        $key_to_validate = $api_key ?? $this->api_key;
        
        if (empty($key_to_validate)) {
            return false;
        }
        
        $endpoint = $this->api_base_url . '/v1/auth/validate';
        
        $payload = array(
            'api_key' => $key_to_validate,
            'site_url' => get_site_url()
        );
        
        $response = $this->make_request('POST', $endpoint, $payload);
        
        return $response && isset($response['valid']) && $response['valid'] === true;
    }
    
    /**
     * Send beacon (lightweight tracking)
     */
    public function send_beacon($data) {
        // Use a very short timeout for beacons to avoid impacting site performance
        $endpoint = $this->api_base_url . '/v1/beacon';
        
        $payload = array(
            'api_key' => $this->api_key,
            'data' => $data
        );
        
        // Fire and forget - don't wait for response
        $this->make_async_request('POST', $endpoint, $payload);
    }
    
    /**
     * Make HTTP request to API
     */
    private function make_request($method, $url, $data = array()) {
        $args = array(
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION,
                'X-Site-URL' => get_site_url()
            )
        );
        
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        } elseif ($method !== 'GET' && !empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            error_log('CrawlGuard API Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            $decoded = json_decode($response_body, true);
            return $decoded !== null ? $decoded : $response_body;
        }
        
        error_log('CrawlGuard API Error: HTTP ' . $response_code . ' - ' . $response_body);
        return false;
    }
    
    /**
     * Make async request (fire and forget)
     */
    private function make_async_request($method, $url, $data = array()) {
        $args = array(
            'method' => $method,
            'timeout' => 1, // Very short timeout
            'blocking' => false, // Non-blocking request
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION,
                'X-Site-URL' => get_site_url()
            )
        );
        
        if ($method !== 'GET' && !empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        wp_remote_request($url, $args);
    }
    
    /**
     * Get API status
     */
    public function get_api_status() {
        $endpoint = $this->api_base_url . '/v1/status';
        
        $response = $this->make_request('GET', $endpoint);
        
        return $response && isset($response['status']) && $response['status'] === 'ok';
    }
    
    /**
     * Emergency disable - allows backend to disable plugin functionality
     */
    public function check_emergency_disable() {
        if (empty($this->api_key)) {
            return false;
        }
        
        // Check cached status first
        $cached_status = get_transient('crawlguard_emergency_status');
        if ($cached_status !== false) {
            return $cached_status === 'disabled';
        }
        
        $endpoint = $this->api_base_url . '/v1/emergency-status';
        
        $params = array(
            'api_key' => $this->api_key,
            'site_url' => get_site_url()
        );
        
        $response = $this->make_request('GET', $endpoint, $params);
        
        $is_disabled = $response && isset($response['disabled']) && $response['disabled'] === true;
        
        // Cache the result for 5 minutes
        set_transient('crawlguard_emergency_status', $is_disabled ? 'disabled' : 'enabled', 300);
        
        return $is_disabled;
    }
}
