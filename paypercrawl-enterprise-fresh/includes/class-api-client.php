<?php
/**
 * API Client for PayPerCrawl Enterprise
 * 
 * Handles all external API communications including Cloudflare Workers
 * 
 * @package PayPerCrawl_Enterprise
 * @subpackage API
 * @version 6.0.0
 * @author PayPerCrawl.tech
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * API Client with comprehensive error handling
 */
class PayPerCrawl_API_Client {
    
    /**
     * API endpoints
     */
    private $api_url;
    private $cloudflare_api_url;
    
    /**
     * API credentials
     */
    private $api_key;
    private $cloudflare_token;
    private $zone_id;
    
    /**
     * Request timeout settings
     */
    private $timeout = 30;
    private $retry_attempts = 3;
    
    /**
     * Initialize API client
     */
    public function __construct() {
        $this->api_url = PAYPERCRAWL_API_URL;
        $this->cloudflare_api_url = PAYPERCRAWL_CLOUDFLARE_API;
        
        $this->api_key = get_option('paypercrawl_api_key', '');
        $this->cloudflare_token = get_option('paypercrawl_cloudflare_api_token', '');
        $this->zone_id = get_option('paypercrawl_cloudflare_zone_id', '');
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key not configured'
            );
        }
        
        $response = $this->make_request('GET', 'health');
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) === 200) {
            return array(
                'success' => true,
                'message' => 'Connection successful',
                'data' => $data
            );
        }
        
        return array(
            'success' => false,
            'message' => $data['error'] ?? 'Unknown error'
        );
    }
    
    /**
     * Submit bot detection data
     */
    public function submit_detection($detection_data) {
        $payload = array(
            'bot_type' => $detection_data['name'],
            'confidence' => $detection_data['confidence_score'],
            'ip_address' => $detection_data['ip_address'] ?? '',
            'user_agent' => $detection_data['user_agent'] ?? '',
            'page_url' => $detection_data['page_url'] ?? '',
            'revenue' => $detection_data['rate'],
            'detection_method' => $detection_data['detection_method'] ?? 'multi_layer',
            'timestamp' => current_time('timestamp'),
            'site_url' => get_site_url(),
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => PAYPERCRAWL_ENTERPRISE_VERSION
        );
        
        return $this->make_request('POST', 'detections', $payload);
    }
    
    /**
     * Get bot signatures update
     */
    public function get_bot_signatures_update() {
        $current_version = get_option('paypercrawl_signatures_version', '1.0');
        
        $response = $this->make_request('GET', 'signatures', array(
            'current_version' => $current_version
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) === 200 && isset($data['signatures'])) {
            // Update local signatures cache
            update_option('paypercrawl_signatures_cache', $data['signatures']);
            update_option('paypercrawl_signatures_version', $data['version']);
            
            return $data;
        }
        
        return new WP_Error('api_error', 'Failed to fetch signatures update');
    }
    
    /**
     * Submit analytics data for processing
     */
    public function submit_analytics($analytics_data) {
        $payload = array(
            'site_id' => get_option('paypercrawl_site_id', md5(get_site_url())),
            'period' => $analytics_data['period'],
            'metrics' => $analytics_data['metrics'],
            'detections' => $analytics_data['detections'],
            'timestamp' => current_time('timestamp')
        );
        
        return $this->make_request('POST', 'analytics', $payload);
    }
    
    /**
     * Verify API credentials
     */
    public function verify_credentials() {
        $response = $this->make_request('GET', 'verify');
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($code === 200 && isset($data['valid']) && $data['valid']) {
            update_option('paypercrawl_credentials_verified', true);
            update_option('paypercrawl_account_tier', $data['tier'] ?? 'basic');
            return true;
        }
        
        update_option('paypercrawl_credentials_verified', false);
        return false;
    }
    
    /**
     * Make HTTP request with retry logic
     */
    private function make_request($method, $endpoint, $data = array()) {
        $url = rtrim($this->api_url, '/') . '/' . ltrim($endpoint, '/');
        
        $args = array(
            'method' => strtoupper($method),
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'PayPerCrawl-WP/' . PAYPERCRAWL_ENTERPRISE_VERSION,
                'X-Site-URL' => get_site_url(),
                'X-WordPress-Version' => get_bloginfo('version')
            )
        );
        
        if (!empty($data)) {
            if ($method === 'GET') {
                $url = add_query_arg($data, $url);
            } else {
                $args['body'] = wp_json_encode($data);
            }
        }
        
        // Attempt request with retry logic
        $attempts = 0;
        $last_error = null;
        
        while ($attempts < $this->retry_attempts) {
            $response = wp_remote_request($url, $args);
            
            if (!is_wp_error($response)) {
                $code = wp_remote_retrieve_response_code($response);
                
                // Success codes
                if ($code >= 200 && $code < 300) {
                    return $response;
                }
                
                // Rate limited - wait and retry
                if ($code === 429) {
                    $retry_after = wp_remote_retrieve_header($response, 'retry-after') ?: 1;
                    sleep(min($retry_after, 5)); // Max 5 second wait
                    $attempts++;
                    continue;
                }
                
                // Server errors - retry
                if ($code >= 500) {
                    $attempts++;
                    sleep(pow(2, $attempts)); // Exponential backoff
                    continue;
                }
                
                // Client errors - don't retry
                $body = wp_remote_retrieve_body($response);
                $error_data = json_decode($body, true);
                return new WP_Error('api_client_error', $error_data['message'] ?? 'Client error', array('code' => $code));
            }
            
            $last_error = $response;
            $attempts++;
            
            // Exponential backoff
            if ($attempts < $this->retry_attempts) {
                sleep(pow(2, $attempts));
            }
        }
        
        return $last_error ?: new WP_Error('api_error', 'Max retry attempts reached');
    }
    
    /**
     * Get account information
     */
    public function get_account_info() {
        $response = $this->make_request('GET', 'account');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Get usage statistics
     */
    public function get_usage_stats($period = '30d') {
        $response = $this->make_request('GET', 'usage', array('period' => $period));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Submit feedback or support request
     */
    public function submit_feedback($feedback_data) {
        $payload = array(
            'type' => $feedback_data['type'],
            'message' => $feedback_data['message'],
            'email' => $feedback_data['email'],
            'site_url' => get_site_url(),
            'plugin_version' => PAYPERCRAWL_ENTERPRISE_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'timestamp' => current_time('timestamp')
        );
        
        return $this->make_request('POST', 'feedback', $payload);
    }
    
    /**
     * Download premium bot signatures
     */
    public function download_premium_signatures() {
        if (!$this->verify_credentials()) {
            return new WP_Error('unauthorized', 'Invalid credentials');
        }
        
        $response = $this->make_request('GET', 'signatures/premium');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) === 200) {
            // Cache premium signatures
            update_option('paypercrawl_premium_signatures', $data['signatures']);
            update_option('paypercrawl_premium_signatures_updated', current_time('timestamp'));
            
            return $data;
        }
        
        return new WP_Error('download_failed', 'Failed to download premium signatures');
    }
    
    /**
     * Report false positive
     */
    public function report_false_positive($detection_id, $reason) {
        $payload = array(
            'detection_id' => $detection_id,
            'reason' => $reason,
            'site_url' => get_site_url(),
            'timestamp' => current_time('timestamp')
        );
        
        return $this->make_request('POST', 'false-positive', $payload);
    }
    
    /**
     * Get real-time threat intelligence
     */
    public function get_threat_intelligence($ip_address) {
        $response = $this->make_request('GET', 'threat-intel', array(
            'ip' => $ip_address
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Update configuration from server
     */
    public function sync_configuration() {
        $response = $this->make_request('GET', 'config');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $config = json_decode($body, true);
        
        if (wp_remote_retrieve_response_code($response) === 200 && isset($config['settings'])) {
            // Update local configuration
            foreach ($config['settings'] as $key => $value) {
                update_option('paypercrawl_' . $key, $value);
            }
            
            update_option('paypercrawl_config_synced', current_time('timestamp'));
            return true;
        }
        
        return false;
    }
    
    /**
     * Emergency disable (kill switch)
     */
    public function emergency_disable() {
        update_option('paypercrawl_detection_enabled', false);
        update_option('paypercrawl_emergency_disabled', true);
        update_option('paypercrawl_emergency_disabled_at', current_time('timestamp'));
        
        // Notify server
        $this->make_request('POST', 'emergency-disable', array(
            'site_url' => get_site_url(),
            'timestamp' => current_time('timestamp')
        ));
        
        return true;
    }
    
    /**
     * Health check
     */
    public function health_check() {
        $start_time = microtime(true);
        
        $response = $this->make_request('GET', 'health');
        
        $end_time = microtime(true);
        $response_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
        
        $health_data = array(
            'api_accessible' => !is_wp_error($response),
            'response_time_ms' => round($response_time, 2),
            'timestamp' => current_time('timestamp')
        );
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $health_data['status_code'] = $code;
            $health_data['healthy'] = ($code === 200);
            
            if ($code === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                $health_data['server_status'] = $data['status'] ?? 'unknown';
            }
        } else {
            $health_data['healthy'] = false;
            $health_data['error'] = $response->get_error_message();
        }
        
        // Store health check result
        update_option('paypercrawl_last_health_check', $health_data);
        
        return $health_data;
    }
    
    /**
     * Batch submit detections for performance
     */
    public function batch_submit_detections($detections) {
        if (empty($detections)) {
            return true;
        }
        
        $payload = array(
            'detections' => $detections,
            'site_url' => get_site_url(),
            'batch_size' => count($detections),
            'timestamp' => current_time('timestamp')
        );
        
        $response = $this->make_request('POST', 'detections/batch', $payload);
        
        if (is_wp_error($response)) {
            // Store failed batch for retry
            $failed_batches = get_option('paypercrawl_failed_batches', array());
            $failed_batches[] = array(
                'detections' => $detections,
                'failed_at' => current_time('timestamp'),
                'error' => $response->get_error_message()
            );
            
            // Keep only last 10 failed batches
            $failed_batches = array_slice($failed_batches, -10);
            update_option('paypercrawl_failed_batches', $failed_batches);
            
            return $response;
        }
        
        return true;
    }
    
    /**
     * Retry failed batches
     */
    public function retry_failed_batches() {
        $failed_batches = get_option('paypercrawl_failed_batches', array());
        
        if (empty($failed_batches)) {
            return true;
        }
        
        $successful_retries = 0;
        $remaining_failures = array();
        
        foreach ($failed_batches as $batch) {
            // Only retry batches that failed less than 24 hours ago
            if ((current_time('timestamp') - $batch['failed_at']) > DAY_IN_SECONDS) {
                continue;
            }
            
            $result = $this->batch_submit_detections($batch['detections']);
            
            if (!is_wp_error($result)) {
                $successful_retries++;
            } else {
                $remaining_failures[] = $batch;
            }
        }
        
        // Update failed batches list
        update_option('paypercrawl_failed_batches', $remaining_failures);
        
        return array(
            'successful_retries' => $successful_retries,
            'remaining_failures' => count($remaining_failures)
        );
    }
    
    /**
     * Get API status
     */
    public function get_api_status() {
        $last_health_check = get_option('paypercrawl_last_health_check', array());
        $credentials_verified = get_option('paypercrawl_credentials_verified', false);
        $last_successful_request = get_option('paypercrawl_last_successful_request', 0);
        
        return array(
            'connected' => !empty($last_health_check) && $last_health_check['healthy'],
            'credentials_valid' => $credentials_verified,
            'last_health_check' => $last_health_check,
            'last_successful_request' => $last_successful_request,
            'api_url' => $this->api_url,
            'has_api_key' => !empty($this->api_key)
        );
    }
}
