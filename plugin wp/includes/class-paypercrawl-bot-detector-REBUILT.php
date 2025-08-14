<?php
/**
 * Bot Detection Engine - Rebuilt for Maximum Stability
 * 
 * @package PayPerCrawl
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * PayPerCrawl Bot Detector Class
 */
class PayPerCrawl_Bot_Detector {
    
    /**
     * Bot signatures array
     */
    private $bot_signatures = array();
    
    /**
     * Advanced detection patterns
     */
    private $advanced_patterns = array();
    
    /**
     * Initialization flag
     */
    private $initialized = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the detector
     */
    private function init() {
        if ($this->initialized) {
            return;
        }
        
        try {
            $this->load_bot_signatures();
            $this->load_advanced_patterns();
            $this->initialized = true;
        } catch (Exception $e) {
            error_log('PayPerCrawl Bot Detector: Initialization failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Get bot signatures
     */
    public function get_bot_signatures() {
        if (!$this->initialized) {
            $this->init();
        }
        return $this->bot_signatures;
    }
    
    /**
     * Load comprehensive bot signatures
     */
    private function load_bot_signatures() {
        $this->bot_signatures = array(
            // OpenAI & ChatGPT Family
            'GPTBot' => array('rate' => 0.12, 'type' => 'premium', 'company' => 'OpenAI'),
            'ChatGPT-User' => array('rate' => 0.12, 'type' => 'premium', 'company' => 'OpenAI'),
            'OpenAI' => array('rate' => 0.12, 'type' => 'premium', 'company' => 'OpenAI'),
            
            // Anthropic Claude Family
            'CCBot' => array('rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'),
            'anthropic-ai' => array('rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'),
            'Claude-Web' => array('rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'),
            'ClaudeBot' => array('rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'),
            
            // Google AI Family
            'Google-Extended' => array('rate' => 0.08, 'type' => 'standard', 'company' => 'Google'),
            'GoogleOther' => array('rate' => 0.08, 'type' => 'standard', 'company' => 'Google'),
            'Bard' => array('rate' => 0.08, 'type' => 'standard', 'company' => 'Google'),
            'PaLM' => array('rate' => 0.08, 'type' => 'standard', 'company' => 'Google'),
            'Gemini' => array('rate' => 0.08, 'type' => 'standard', 'company' => 'Google'),
            
            // Meta AI Family
            'FacebookBot' => array('rate' => 0.07, 'type' => 'standard', 'company' => 'Meta'),
            'Meta-ExternalAgent' => array('rate' => 0.07, 'type' => 'standard', 'company' => 'Meta'),
            'Llama' => array('rate' => 0.07, 'type' => 'standard', 'company' => 'Meta'),
            
            // Microsoft AI Family
            'BingBot' => array('rate' => 0.06, 'type' => 'standard', 'company' => 'Microsoft'),
            'msnbot' => array('rate' => 0.06, 'type' => 'standard', 'company' => 'Microsoft'),
            'CopilotBot' => array('rate' => 0.06, 'type' => 'standard', 'company' => 'Microsoft'),
            
            // Other AI Companies
            'PerplexityBot' => array('rate' => 0.05, 'type' => 'emerging', 'company' => 'Perplexity'),
            'YouBot' => array('rate' => 0.05, 'type' => 'emerging', 'company' => 'You.com'),
            'Bytespider' => array('rate' => 0.04, 'type' => 'emerging', 'company' => 'ByteDance'),
            'YandexBot' => array('rate' => 0.04, 'type' => 'emerging', 'company' => 'Yandex'),
            
            // Research & Academic Bots
            'ResearchBot' => array('rate' => 0.03, 'type' => 'research', 'company' => 'Various'),
            'AcademicBot' => array('rate' => 0.03, 'type' => 'research', 'company' => 'Various'),
            
            // Web Scrapers with AI Features
            'ScrapingBot' => array('rate' => 0.02, 'type' => 'scraper', 'company' => 'Various'),
            'DataBot' => array('rate' => 0.02, 'type' => 'scraper', 'company' => 'Various'),
        );
    }
    
    /**
     * Load advanced detection patterns
     */
    private function load_advanced_patterns() {
        $this->advanced_patterns = array(
            'ip_ranges' => array(
                'openai' => array('20.171.0.0/16', '52.230.0.0/15'),
                'google' => array('66.249.64.0/19', '216.239.32.0/19'),
                'microsoft' => array('40.76.0.0/14', '65.52.0.0/14'),
            ),
            'behavioral_patterns' => array(
                'rapid_crawling' => array('threshold' => 10, 'window' => 60),
                'deep_crawling' => array('depth' => 5),
                'pattern_crawling' => array('similarity' => 0.8),
            ),
            'header_signatures' => array(
                'accept' => array('*/*', 'text/html,application/xhtml+xml'),
                'cache_control' => array('no-cache', 'max-age=0'),
            )
        );
    }
    
    /**
     * Detect bot from request
     */
    public function detect_bot($user_agent = null, $ip = null, $headers = array()) {
        if (!$this->initialized) {
            $this->init();
        }
        
        try {
            // Get request data
            if (empty($user_agent)) {
                $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            }
            
            if (empty($ip)) {
                $ip = $this->get_client_ip();
            }
            
            if (empty($headers)) {
                $headers = $this->get_request_headers();
            }
            
            // Check user agent first (most reliable)
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
            
        } catch (Exception $e) {
            error_log('PayPerCrawl: Bot detection error - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check user agent for bot signatures
     */
    private function check_user_agent($user_agent) {
        if (empty($user_agent)) {
            return false;
        }
        
        foreach ($this->bot_signatures as $bot_name => $info) {
            if (stripos($user_agent, $bot_name) !== false) {
                return array_merge(array('name' => $bot_name), $info);
            }
        }
        
        return false;
    }
    
    /**
     * Check IP against known bot ranges
     */
    private function check_ip_ranges($ip) {
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        if (!isset($this->advanced_patterns['ip_ranges'])) {
            return false;
        }
        
        foreach ($this->advanced_patterns['ip_ranges'] as $company => $ranges) {
            foreach ($ranges as $range) {
                if ($this->ip_in_range($ip, $range)) {
                    return array(
                        'name' => ucfirst($company) . 'Bot',
                        'company' => ucfirst($company),
                        'type' => 'standard',
                        'rate' => 0.05
                    );
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check behavioral patterns
     */
    private function check_behavioral_patterns($ip) {
        if (empty($ip)) {
            return false;
        }
        
        global $wpdb;
        
        if (!$wpdb) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_requests';
        
        // Check if table exists
        if (!$this->table_exists($table_name)) {
            return false;
        }
        
        try {
            // Check rapid crawling
            if (isset($this->advanced_patterns['behavioral_patterns']['rapid_crawling'])) {
                $rapid = $this->advanced_patterns['behavioral_patterns']['rapid_crawling'];
                $time_window = time() - $rapid['window'];
                
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE ip_address = %s AND request_time > %d",
                    $ip,
                    $time_window
                ));
                
                if ($count && $count >= $rapid['threshold']) {
                    return array(
                        'name' => 'UnidentifiedBot',
                        'company' => 'Unknown',
                        'type' => 'behavioral',
                        'rate' => 0.02
                    );
                }
            }
            
        } catch (Exception $e) {
            error_log('PayPerCrawl: Behavioral pattern check error - ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Check header signatures
     */
    private function check_header_signatures($headers) {
        if (empty($headers) || !is_array($headers)) {
            return false;
        }
        
        if (!isset($this->advanced_patterns['header_signatures'])) {
            return false;
        }
        
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
            return array(
                'name' => 'SuspiciousBot',
                'company' => 'Unknown',
                'type' => 'headers',
                'rate' => 0.01
            );
        }
        
        return false;
    }
    
    /**
     * Get client IP address
     */
    public function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Get request headers
     */
    private function get_request_headers() {
        $headers = array();
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) === 'HTTP_') {
                    $header_name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$header_name] = $value;
                }
            }
        }
        
        return is_array($headers) ? $headers : array();
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
        
        if ($range_decimal === false || $ip_decimal === false) {
            return false;
        }
        
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        
        return (($ip_decimal & $netmask_decimal) === ($range_decimal & $netmask_decimal));
    }
    
    /**
     * Check if table exists
     */
    private function table_exists($table_name) {
        global $wpdb;
        
        if (!$wpdb) {
            return false;
        }
        
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
    
    /**
     * Log bot detection
     */
    public function log_detection($bot_info, $url, $ip) {
        if (empty($bot_info) || !is_array($bot_info)) {
            return false;
        }
        
        global $wpdb;
        
        if (!$wpdb) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        // Check if table exists
        if (!$this->table_exists($table_name)) {
            return false;
        }
        
        try {
            $result = $wpdb->insert(
                $table_name,
                array(
                    'bot_type' => isset($bot_info['name']) ? $bot_info['name'] : 'Unknown',
                    'company' => isset($bot_info['company']) ? $bot_info['company'] : 'Unknown',
                    'revenue' => isset($bot_info['rate']) ? $bot_info['rate'] : 0.0,
                    'url' => $url ? $url : '',
                    'ip_address' => $ip ? $ip : '',
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                    'detected_at' => current_time('mysql')
                ),
                array(
                    '%s',  // bot_type
                    '%s',  // company
                    '%f',  // revenue
                    '%s',  // url
                    '%s',  // ip_address
                    '%s',  // user_agent
                    '%s'   // detected_at
                )
            );
            
            if ($result === false) {
                error_log('PayPerCrawl: Failed to log bot detection - ' . $wpdb->last_error);
                return false;
            }
            
            return $wpdb->insert_id;
            
        } catch (Exception $e) {
            error_log('PayPerCrawl: Log detection error - ' . $e->getMessage());
            return false;
        }
    }
}
