<?php
/**
 * ML-Powered Bot Detection System
 * 
 * Enhanced detection using Cloudflare's AI crawler patterns
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_ML_Bot_Detector {
    
    // Cloudflare's AI crawler patterns (2024 updated list)
    private $ai_crawler_patterns = [
        // OpenAI crawlers
        'gptbot' => ['pattern' => '/GPTBot\/[\d\.]+/i', 'company' => 'OpenAI', 'rate' => 0.002],
        'chatgpt-user' => ['pattern' => '/ChatGPT-User\/[\d\.]+/i', 'company' => 'OpenAI', 'rate' => 0.002],
        'openai-crawler' => ['pattern' => '/OpenAI[- ]?Crawler/i', 'company' => 'OpenAI', 'rate' => 0.002],
        
        // Anthropic crawlers
        'claude-web' => ['pattern' => '/Claude-Web\/[\d\.]+/i', 'company' => 'Anthropic', 'rate' => 0.0015],
        'anthropic-ai' => ['pattern' => '/Anthropic-AI/i', 'company' => 'Anthropic', 'rate' => 0.0015],
        'claude-bot' => ['pattern' => '/ClaudeBot/i', 'company' => 'Anthropic', 'rate' => 0.0015],
        
        // Google AI crawlers
        'google-extended' => ['pattern' => '/Google-Extended/i', 'company' => 'Google', 'rate' => 0.001],
        'bard' => ['pattern' => '/Bard/i', 'company' => 'Google', 'rate' => 0.001],
        'palm' => ['pattern' => '/PaLM/i', 'company' => 'Google', 'rate' => 0.001],
        'vertex-ai' => ['pattern' => '/Vertex[- ]?AI/i', 'company' => 'Google', 'rate' => 0.001],
        
        // Meta AI crawlers
        'meta-externalagent' => ['pattern' => '/Meta-ExternalAgent/i', 'company' => 'Meta', 'rate' => 0.001],
        'facebookbot' => ['pattern' => '/facebookexternalhit|facebookbot/i', 'company' => 'Meta', 'rate' => 0.0008],
        
        // Microsoft AI crawlers
        'bingbot' => ['pattern' => '/bingbot/i', 'company' => 'Microsoft', 'rate' => 0.0008],
        'msnbot-media' => ['pattern' => '/msnbot-media/i', 'company' => 'Microsoft', 'rate' => 0.0008],
        
        // Other AI companies
        'perplexitybot' => ['pattern' => '/PerplexityBot/i', 'company' => 'Perplexity', 'rate' => 0.001],
        'youbot' => ['pattern' => '/YouBot/i', 'company' => 'You.com', 'rate' => 0.001],
        'ccbot' => ['pattern' => '/CCBot/i', 'company' => 'Common Crawl', 'rate' => 0.0005],
        'amazonbot' => ['pattern' => '/Amazonbot/i', 'company' => 'Amazon', 'rate' => 0.001],
        'bytespider' => ['pattern' => '/Bytespider/i', 'company' => 'ByteDance', 'rate' => 0.001]
    ];
    
    private $logger;
    private $api_client;
    
    public function __construct() {
        if (class_exists('CrawlGuard_Error_Logger')) {
            $this->logger = new CrawlGuard_Error_Logger();
        }
        
        if (class_exists('CrawlGuard_API_Client')) {
            $this->api_client = new CrawlGuard_API_Client();
        }
    }
    
    /**
     * Detect bot using ML-enhanced pattern matching
     */
    public function detect($user_agent, $ip, $headers = []) {
        $detection_result = [
            'is_ai_bot' => false,
            'bot_type' => null,
            'company' => null,
            'confidence' => 0,
            'rate' => 0,
            'detection_method' => null,
            'features' => []
        ];
        
        // First, check against known AI crawler patterns
        $pattern_match = $this->check_ai_patterns($user_agent);
        if ($pattern_match) {
            $detection_result = array_merge($detection_result, $pattern_match);
            $detection_result['is_ai_bot'] = true;
            $detection_result['detection_method'] = 'pattern_match';
        }
        
        // ML-based feature extraction for unknown bots
        $features = $this->extract_features($user_agent, $headers);
        $ml_score = $this->calculate_ml_score($features);
        
        // If no pattern match but high ML score, flag as potential AI bot
        if (!$detection_result['is_ai_bot'] && $ml_score > 0.7) {
            $detection_result['is_ai_bot'] = true;
            $detection_result['bot_type'] = 'unknown_ai_bot';
            $detection_result['company'] = 'Unknown';
            $detection_result['confidence'] = $ml_score * 100;
            $detection_result['rate'] = 0.001; // Default rate for unknown bots
            $detection_result['detection_method'] = 'ml_detection';
        }
        
        $detection_result['features'] = $features;
        $detection_result['ml_score'] = $ml_score;
        
        // Log detection for analysis
        if ($this->logger && $detection_result['is_ai_bot']) {
            $this->logger->info('AI Bot Detected', [
                'bot_type' => $detection_result['bot_type'],
                'company' => $detection_result['company'],
                'confidence' => $detection_result['confidence'],
                'user_agent' => $user_agent,
                'ip' => $ip
            ]);
        }
        
        return $detection_result;
    }
    
    /**
     * Check against known AI crawler patterns
     */
    private function check_ai_patterns($user_agent) {
        foreach ($this->ai_crawler_patterns as $bot_type => $info) {
            if (preg_match($info['pattern'], $user_agent)) {
                return [
                    'bot_type' => $bot_type,
                    'company' => $info['company'],
                    'rate' => $info['rate'],
                    'confidence' => 95 // High confidence for pattern matches
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Extract ML features from request
     */
    private function extract_features($user_agent, $headers) {
        $features = [];
        
        // User agent features
        $features['ua_length'] = strlen($user_agent);
        $features['ua_has_bot_keyword'] = preg_match('/bot|crawler|spider|scraper|agent/i', $user_agent) ? 1 : 0;
        $features['ua_has_version'] = preg_match('/\/[\d\.]+/', $user_agent) ? 1 : 0;
        $features['ua_has_url'] = preg_match('/https?:\/\//', $user_agent) ? 1 : 0;
        $features['ua_capital_ratio'] = $this->calculate_capital_ratio($user_agent);
        
        // Header features
        $features['has_accept_language'] = isset($headers['Accept-Language']) ? 1 : 0;
        $features['has_accept_encoding'] = isset($headers['Accept-Encoding']) ? 1 : 0;
        $features['has_cache_control'] = isset($headers['Cache-Control']) ? 1 : 0;
        $features['has_dnt'] = isset($headers['DNT']) ? 1 : 0;
        $features['header_count'] = count($headers);
        
        // Request pattern features
        $features['request_interval'] = $this->get_request_interval();
        $features['crawl_depth'] = $this->get_crawl_depth();
        
        return $features;
    }
    
    /**
     * Calculate ML score based on features
     */
    private function calculate_ml_score($features) {
        // Simple weighted scoring (can be replaced with actual ML model)
        $weights = [
            'ua_has_bot_keyword' => 0.3,
            'ua_has_version' => 0.1,
            'ua_has_url' => 0.15,
            'ua_capital_ratio' => 0.1,
            'has_accept_language' => -0.2, // Bots often lack this
            'has_accept_encoding' => -0.1,
            'header_count' => -0.05 // Fewer headers = more likely bot
        ];
        
        $score = 0.5; // Base score
        
        foreach ($weights as $feature => $weight) {
            if (isset($features[$feature])) {
                $score += $features[$feature] * $weight;
            }
        }
        
        // Normalize score between 0 and 1
        return max(0, min(1, $score));
    }
    
    /**
     * Calculate capital letter ratio in user agent
     */
    private function calculate_capital_ratio($string) {
        $total = strlen($string);
        if ($total == 0) return 0;
        
        $capitals = preg_match_all('/[A-Z]/', $string);
        return $capitals / $total;
    }
    
    /**
     * Get request interval for current IP (stub)
     */
    private function get_request_interval() {
        // TODO: Implement actual request interval tracking
        return 1.0;
    }
    
    /**
     * Get crawl depth for current session (stub)
     */
    private function get_crawl_depth() {
        // TODO: Implement actual crawl depth tracking
        return 1;
    }
    
    /**
     * Get list of all known AI companies
     */
    public function get_ai_companies() {
        $companies = [];
        foreach ($this->ai_crawler_patterns as $bot => $info) {
            $companies[$info['company']] = true;
        }
        return array_keys($companies);
    }
    
    /**
     * Update bot patterns from Cloudflare (future feature)
     */
    public function update_patterns_from_cloudflare() {
        // TODO: Implement pattern updates from Cloudflare API
        // This would fetch latest AI crawler patterns from Cloudflare
    }
}
