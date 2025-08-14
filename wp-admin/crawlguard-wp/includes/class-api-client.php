<?php

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_API_Client {
    
    private $api_base_url = 'https://api.creativeinteriorsstudio.com/v1';
    private $api_key;
    private $timeout = 5;
    
    public function __construct() {
        $options = get_option('crawlguard_options');
        $this->api_key = $options['api_key'] ?? '';
        
        if (isset($options['api_url']) && !empty($options['api_url'])) {
            $this->api_base_url = rtrim($options['api_url'], '/');
        }
    }
    
    public function send_monetization_request($request_data) {
        if (empty($this->api_key)) {
            return false;
        }
        
        $response = wp_remote_post($this->api_base_url . '/monetize', array(
            'body' => json_encode($request_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            ),
            'timeout' => $this->timeout,
            'blocking' => false
        ));
        
        if (is_wp_error($response)) {
            error_log('CrawlGuard API Error: ' . $response->get_error_message());
            return false;
        }
        
        return true;
    }
    
    public function test_connection() {
        $response = wp_remote_get($this->api_base_url . '/status', array(
            'headers' => array(
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            ),
            'timeout' => $this->timeout
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $data = json_decode($body, true);
            return array(
                'success' => true,
                'message' => 'Connection successful',
                'data' => $data
            );
        } else {
            return array(
                'success' => false,
                'message' => 'API returned status code: ' . $status_code
            );
        }
    }
    
    public function register_site() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key is required'
            );
        }
        
        $site_data = array(
            'site_url' => home_url(),
            'admin_email' => get_option('admin_email'),
            'site_name' => get_bloginfo('name'),
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => CRAWLGUARD_VERSION,
            'php_version' => PHP_VERSION,
            'timestamp' => time()
        );
        
        $response = wp_remote_post($this->api_base_url . '/sites/register', array(
            'body' => json_encode($site_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            ),
            'timeout' => $this->timeout
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $data = json_decode($body, true);
            return array(
                'success' => true,
                'message' => 'Site registered successfully',
                'data' => $data
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Registration failed with status code: ' . $status_code,
                'body' => $body
            );
        }
    }
    
    public function get_analytics($timeframe = '24h') {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key is required'
            );
        }
        
        $response = wp_remote_get($this->api_base_url . '/analytics?' . http_build_query(array(
            'timeframe' => $timeframe,
            'site_url' => home_url()
        )), array(
            'headers' => array(
                'X-API-Key' => $this->api_key,
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            ),
            'timeout' => $this->timeout
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $data = json_decode($body, true);
            return array(
                'success' => true,
                'data' => $data
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to fetch analytics: ' . $status_code
            );
        }
    }
    
    public function send_bot_detection($detection_data) {
        if (empty($this->api_key)) {
            return false;
        }
        
        $response = wp_remote_post($this->api_base_url . '/detect', array(
            'body' => json_encode($detection_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            ),
            'timeout' => $this->timeout,
            'blocking' => false
        ));
        
        if (is_wp_error($response)) {
            error_log('CrawlGuard Bot Detection API Error: ' . $response->get_error_message());
            return false;
        }
        
        return true;
    }
    
    public function get_site_info() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key is required'
            );
        }
        
        $response = wp_remote_get($this->api_base_url . '/sites/info?' . http_build_query(array(
            'site_url' => home_url()
        )), array(
            'headers' => array(
                'X-API-Key' => $this->api_key,
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            ),
            'timeout' => $this->timeout
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $data = json_decode($body, true);
            return array(
                'success' => true,
                'data' => $data
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to fetch site info: ' . $status_code
            );
        }
    }
    
    public function update_settings($settings) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key is required'
            );
        }
        
        $settings['site_url'] = home_url();
        
        $response = wp_remote_request($this->api_base_url . '/settings', array(
            'method' => 'PUT',
            'body' => json_encode($settings),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            ),
            'timeout' => $this->timeout
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => 'Settings updated successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to update settings: ' . $status_code
            );
        }
    }
    
    public function get_payment_history($limit = 50) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key is required'
            );
        }
        
        $response = wp_remote_get($this->api_base_url . '/payments?' . http_build_query(array(
            'site_url' => home_url(),
            'limit' => $limit
        )), array(
            'headers' => array(
                'X-API-Key' => $this->api_key,
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            ),
            'timeout' => $this->timeout
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $data = json_decode($body, true);
            return array(
                'success' => true,
                'data' => $data
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to fetch payment history: ' . $status_code
            );
        }
    }
}
