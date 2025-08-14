<?php
/**
 * Bot Detection Engine
 * 
 * @package PayPerCrawl
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PayPerCrawl_Bot_Detector {
    
    private $bot_signatures = [];
    private $advanced_patterns = [];
    
    public function __construct() {
        $this->load_bot_signatures();
        $this->load_advanced_patterns();
    }
    
    public function get_bot_signatures() {
        return $this->bot_signatures;
    }
    
    /**
     * Load comprehensive bot signatures
     */
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
    
    /**
     * Load advanced detection patterns
     */
    private function load_advanced_patterns() {
        $this->advanced_patterns = [
            'ip_ranges' => [
                'openai' => ['20.171.0.0/16', '52.230.0.0/15'],
                'google' => ['66.249.64.0/19', '216.239.32.0/19'],
                'microsoft' => ['40.76.0.0/14', '65.52.0.0/14'],
            ],
            'behavioral_patterns' => [
                'rapid_crawling' => ['threshold' => 10, 'window' => 60], // 10 requests in 60 seconds
                'deep_crawling' => ['depth' => 5], // Following links 5 levels deep
                'pattern_crawling' => ['similarity' => 0.8], // Similar URL patterns
            ],
            'header_signatures' => [
                'accept' => ['*/*', 'text/html,application/xhtml+xml'],
                'cache_control' => ['no-cache', 'max-age=0'],
            ]
        ];
    }
    
    /**
     * Detect bot from request
     */
    public function detect_bot($user_agent = null, $ip = null, $headers = []) {
        if (!$user_agent) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        if (!$ip) {
            $ip = $this->get_client_ip();
        }
        
        if (empty($headers)) {
            $headers = $this->get_request_headers();
        }
        
        // Check user agent
        $bot_info = $this->check_user_agent($user_agent);
        if ($bot_info) {
            return $bot_info;
        }
        
        // Check IP ranges
        $bot_info = $this->check_ip_ranges($ip);
        if ($bot_info) {
            return $bot_info;
        }
        
        // Check behavioral patterns
        $bot_info = $this->check_behavioral_patterns($ip);
        if ($bot_info) {
            return $bot_info;
        }
        
        // Check header signatures
        $bot_info = $this->check_header_signatures($headers);
        if ($bot_info) {
            return $bot_info;
        }
        
        return false;
    }
    
    /**
     * Check user agent for bot signatures
     */
    private function check_user_agent($user_agent) {
        foreach ($this->bot_signatures as $bot_name => $info) {
            if (stripos($user_agent, $bot_name) !== false) {
                return array_merge(['name' => $bot_name], $info);
            }
        }
        return false;
    }
    
    /**
     * Check IP against known bot ranges
     */
    private function check_ip_ranges($ip) {
        foreach ($this->advanced_patterns['ip_ranges'] as $company => $ranges) {
            foreach ($ranges as $range) {
                if ($this->ip_in_range($ip, $range)) {
                    return [
                        'name' => ucfirst($company) . 'Bot',
                        'company' => ucfirst($company),
                        'type' => 'standard',
                        'rate' => 0.05
                    ];
                }
            }
        }
        return false;
    }
    
    /**
     * Check behavioral patterns
     */
    private function check_behavioral_patterns($ip) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_requests';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            return false;
        }
        
        // Check rapid crawling
        $rapid = $this->advanced_patterns['behavioral_patterns']['rapid_crawling'];
        $time_window = time() - $rapid['window'];
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE ip_address = %s AND request_time > %d",
            $ip, $time_window
        ));
        
        if ($count >= $rapid['threshold']) {
            return [
                'name' => 'UnidentifiedBot',
                'company' => 'Unknown',
                'type' => 'behavioral',
                'rate' => 0.02
            ];
        }
        
        return false;
    }
    
    /**
     * Check header signatures
     */
    private function check_header_signatures($headers) {
        $suspicious_headers = 0;
        
        foreach ($this->advanced_patterns['header_signatures'] as $header => $values) {
            if (isset($headers[$header])) {
                foreach ($values as $value) {
                    if (stripos($headers[$header], $value) !== false) {
                        $suspicious_headers++;
                    }
                }
            }
        }
        
        if ($suspicious_headers >= 2) {
            return [
                'name' => 'SuspiciousBot',
                'company' => 'Unknown',
                'type' => 'headers',
                'rate' => 0.01
            ];
        }
        
        return false;
    }
    
    /**
     * Get client IP address
     */
    public function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get request headers
     */
    private function get_request_headers() {
        $headers = [];
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Check if IP is in range
     */
    private function ip_in_range($ip, $range) {
        if (strpos($range, '/') === false) {
            $range .= '/32';
        }
        
        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
    
    /**
     * Log bot detection
     */
    public function log_detection($bot_info, $url, $ip) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $wpdb->insert(
            $table_name,
            [
                'bot_type' => $bot_info['name'],
                'company' => $bot_info['company'],
                'revenue' => $bot_info['rate'],
                'url' => $url,
                'ip_address' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'detected_at' => current_time('mysql')
            ]
        );
        
        return $wpdb->insert_id;
    }
}
