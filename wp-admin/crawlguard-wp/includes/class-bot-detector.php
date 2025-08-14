<?php

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Bot_Detector {
    
    private $known_ai_bots = array(
        'gptbot' => array('company' => 'OpenAI', 'rate' => 0.002, 'confidence' => 95),
        'chatgpt-user' => array('company' => 'OpenAI', 'rate' => 0.002, 'confidence' => 95),
        'anthropic-ai' => array('company' => 'Anthropic', 'rate' => 0.0015, 'confidence' => 95),
        'claude-web' => array('company' => 'Anthropic', 'rate' => 0.0015, 'confidence' => 95),
        'bard' => array('company' => 'Google', 'rate' => 0.001, 'confidence' => 90),
        'palm' => array('company' => 'Google', 'rate' => 0.001, 'confidence' => 90),
        'google-extended' => array('company' => 'Google', 'rate' => 0.001, 'confidence' => 90),
        'ccbot' => array('company' => 'Common Crawl', 'rate' => 0.001, 'confidence' => 90),
        'perplexitybot' => array('company' => 'Perplexity', 'rate' => 0.001, 'confidence' => 90),
        'bytespider' => array('company' => 'ByteDance', 'rate' => 0.001, 'confidence' => 85)
    );
    
    private $suspicious_patterns = array(
        '/python-requests/i',
        '/curl\/[\d\.]+/i',
        '/wget/i',
        '/scrapy/i',
        '/selenium/i',
        '/headless/i',
        '/bot|crawler|spider/i'
    );
    
    private $allowed_bots = array(
        'googlebot',
        'bingbot',
        'slurp',
        'duckduckbot',
        'baiduspider',
        'yandexbot',
        'facebookexternalhit',
        'twitterbot',
        'linkedinbot'
    );
    
    public function __construct() {
        $options = get_option('crawlguard_options');
        if (isset($options['allowed_bots']) && is_array($options['allowed_bots'])) {
            $this->allowed_bots = array_merge($this->allowed_bots, $options['allowed_bots']);
        }
    }
    
    public function process_request() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip_address = $this->get_client_ip();
        $page_url = $this->get_current_url();
        
        if (empty($user_agent)) {
            return;
        }
        
        $detection_result = $this->analyze_request($user_agent, $ip_address, $page_url);
        
        if ($detection_result['bot_detected']) {
            $this->log_detection($detection_result);
            
            if ($detection_result['confidence'] >= 90) {
                $this->handle_monetization($detection_result);
            }
        }
    }
    
    private function analyze_request($user_agent, $ip_address, $page_url) {
        $result = array(
            'user_agent' => $user_agent,
            'ip_address' => $ip_address,
            'page_url' => $page_url,
            'bot_detected' => false,
            'bot_name' => '',
            'bot_company' => '',
            'confidence' => 0,
            'action' => 'allowed',
            'revenue_amount' => 0
        );
        
        $user_agent_lower = strtolower($user_agent);
        
        if ($this->is_allowed_bot($user_agent_lower)) {
            return $result;
        }
        
        $ai_bot_match = $this->check_ai_bot_signatures($user_agent_lower);
        if ($ai_bot_match) {
            $result['bot_detected'] = true;
            $result['bot_name'] = $ai_bot_match['name'];
            $result['bot_company'] = $ai_bot_match['company'];
            $result['confidence'] = $ai_bot_match['confidence'];
            $result['action'] = 'monetize';
            $result['revenue_amount'] = $ai_bot_match['rate'];
            return $result;
        }
        
        $suspicious_score = $this->check_suspicious_patterns($user_agent);
        if ($suspicious_score > 70) {
            $result['bot_detected'] = true;
            $result['bot_name'] = 'Unknown Bot';
            $result['bot_company'] = 'Unknown';
            $result['confidence'] = $suspicious_score;
            $result['action'] = 'monitor';
            return $result;
        }
        
        return $result;
    }
    
    private function is_allowed_bot($user_agent) {
        foreach ($this->allowed_bots as $allowed_bot) {
            if (strpos($user_agent, strtolower($allowed_bot)) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private function check_ai_bot_signatures($user_agent) {
        foreach ($this->known_ai_bots as $bot_signature => $bot_data) {
            if (strpos($user_agent, $bot_signature) !== false) {
                return array(
                    'name' => ucfirst($bot_signature),
                    'company' => $bot_data['company'],
                    'confidence' => $bot_data['confidence'],
                    'rate' => $bot_data['rate']
                );
            }
        }
        return false;
    }
    
    private function check_suspicious_patterns($user_agent) {
        $score = 0;
        
        foreach ($this->suspicious_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                $score += 30;
            }
        }
        
        if (strlen($user_agent) < 20) {
            $score += 20;
        }
        
        if (!preg_match('/mozilla|webkit|gecko/i', $user_agent)) {
            $score += 25;
        }
        
        if (preg_match('/^[a-zA-Z]+\/[\d\.]+$/', $user_agent)) {
            $score += 35;
        }
        
        return min($score, 100);
    }
    
    private function log_detection($detection_result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => current_time('mysql'),
                'ip_address' => $detection_result['ip_address'],
                'user_agent' => $detection_result['user_agent'],
                'bot_detected' => $detection_result['bot_detected'] ? 1 : 0,
                'bot_type' => $detection_result['bot_name'],
                'action_taken' => $detection_result['action'],
                'revenue_generated' => $detection_result['revenue_amount']
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%f')
        );
    }
    
    private function handle_monetization($detection_result) {
        $options = get_option('crawlguard_options');
        
        if (!isset($options['monetization_enabled']) || !$options['monetization_enabled']) {
            return;
        }
        
        if (empty($options['api_key'])) {
            return;
        }
        
        $this->send_monetization_request($detection_result, $options);
    }
    
    private function send_monetization_request($detection_result, $options) {
        $api_url = $options['api_url'] ?? 'https://api.creativeinteriorsstudio.com/v1';
        
        $request_data = array(
            'user_agent' => $detection_result['user_agent'],
            'ip_address' => $detection_result['ip_address'],
            'page_url' => $detection_result['page_url'],
            'bot_name' => $detection_result['bot_name'],
            'bot_company' => $detection_result['bot_company'],
            'confidence' => $detection_result['confidence'],
            'revenue_amount' => $detection_result['revenue_amount'],
            'timestamp' => time(),
            'site_url' => home_url()
        );
        
        wp_remote_post($api_url . '/monetize', array(
            'body' => json_encode($request_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $options['api_key'],
                'User-Agent' => 'CrawlGuard-WP/' . CRAWLGUARD_VERSION
            ),
            'timeout' => 5,
            'blocking' => false
        ));
    }
    
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    private function get_current_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        return $protocol . '://' . $host . $uri;
    }
    
    public function get_analytics_data($timeframe = '24h') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        switch ($timeframe) {
            case '7d':
                $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30d':
                $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            default:
                $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        }
        
        $total_requests = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $date_condition");
        $bot_requests = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $date_condition AND bot_detected = 1");
        $revenue = $wpdb->get_var("SELECT SUM(revenue_generated) FROM $table_name WHERE $date_condition");
        
        $top_bots = $wpdb->get_results(
            "SELECT bot_type, COUNT(*) as count, SUM(revenue_generated) as revenue 
             FROM $table_name 
             WHERE $date_condition AND bot_detected = 1 
             GROUP BY bot_type 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        return array(
            'total_requests' => intval($total_requests),
            'bot_requests' => intval($bot_requests),
            'revenue_generated' => floatval($revenue ?: 0),
            'top_bots' => $top_bots
        );
    }
}
