<?php
/**
 * Simple Bot Detector Class
 */
class CrawlGuard_Bot_Detector {
    
    public function __construct() {
        add_action('init', array($this, 'detect_bot'));
    }
    
    public function detect_bot() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip_address = $this->get_client_ip();
        
        if (empty($user_agent)) {
            return;
        }
        
        $bot_info = $this->analyze_user_agent($user_agent);
        
        if ($bot_info['is_bot']) {
            $this->log_detection($user_agent, $ip_address, $bot_info);
        }
    }
    
    private function analyze_user_agent($user_agent) {
        $user_agent = strtolower($user_agent);
        
        // AI Bot patterns
        $ai_bots = array(
            'gpt' => array('type' => 'ChatGPT', 'revenue' => 0.10),
            'claude' => array('type' => 'Claude', 'revenue' => 0.10),
            'bard' => array('type' => 'Bard', 'revenue' => 0.10),
            'openai' => array('type' => 'OpenAI', 'revenue' => 0.10),
            'anthropic' => array('type' => 'Anthropic', 'revenue' => 0.10),
        );
        
        // Standard bot patterns
        $standard_bots = array(
            'googlebot' => array('type' => 'GoogleBot', 'revenue' => 0.05),
            'bingbot' => array('type' => 'BingBot', 'revenue' => 0.05),
            'crawler' => array('type' => 'Web Crawler', 'revenue' => 0.05),
            'spider' => array('type' => 'Spider', 'revenue' => 0.05),
            'scraper' => array('type' => 'Scraper', 'revenue' => 0.05),
        );
        
        // Check for AI bots first (higher value)
        foreach ($ai_bots as $pattern => $info) {
            if (strpos($user_agent, $pattern) !== false) {
                return array(
                    'is_bot' => true,
                    'bot_type' => $info['type'],
                    'confidence' => 0.95,
                    'revenue' => $info['revenue'],
                    'category' => 'ai'
                );
            }
        }
        
        // Check for standard bots
        foreach ($standard_bots as $pattern => $info) {
            if (strpos($user_agent, $pattern) !== false) {
                return array(
                    'is_bot' => true,
                    'bot_type' => $info['type'],
                    'confidence' => 0.85,
                    'revenue' => $info['revenue'],
                    'category' => 'standard'
                );
            }
        }
        
        return array(
            'is_bot' => false,
            'bot_type' => 'Human',
            'confidence' => 0,
            'revenue' => 0,
            'category' => 'human'
        );
    }
    
    private function log_detection($user_agent, $ip_address, $bot_info) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_detections';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_agent' => $user_agent,
                'ip_address' => $ip_address,
                'bot_type' => $bot_info['bot_type'],
                'confidence_score' => $bot_info['confidence'],
                'page_url' => $_SERVER['REQUEST_URI'] ?? '',
                'detection_time' => current_time('mysql'),
                'monetized' => 1,
                'revenue' => $bot_info['revenue']
            ),
            array(
                '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%f'
            )
        );
        
        if ($result && $bot_info['revenue'] > 0) {
            $this->process_revenue($wpdb->insert_id, $bot_info['revenue']);
        }
    }
    
    private function process_revenue($detection_id, $amount) {
        global $wpdb;
        
        $revenue_table = $wpdb->prefix . 'crawlguard_revenue';
        
        $wpdb->insert(
            $revenue_table,
            array(
                'detection_id' => $detection_id,
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'completed',
                'created_at' => current_time('mysql')
            ),
            array(
                '%d', '%f', '%s', '%s', '%s'
            )
        );
    }
    
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim($_SERVER[$key]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
