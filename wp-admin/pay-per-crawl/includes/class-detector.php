<?php
/**
 * Bot Detection Engine
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PayPerCrawl_Detector {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Bot signatures
     */
    private $bot_signatures = array();
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_bot_signatures();
    }
    
    /**
     * Load bot signatures
     */
    private function load_bot_signatures() {
        $this->bot_signatures = array(
            // AI Bots (High Value - Premium Tier)
            'gptbot' => array('company' => 'OpenAI', 'confidence' => 95),
            'chatgpt-user' => array('company' => 'OpenAI', 'confidence' => 95),
            'openai' => array('company' => 'OpenAI', 'confidence' => 95),
            'ccbot' => array('company' => 'Anthropic', 'confidence' => 95),
            'claudebot' => array('company' => 'Anthropic', 'confidence' => 95),
            'claude-web' => array('company' => 'Anthropic', 'confidence' => 95),
            'google-extended' => array('company' => 'Google', 'confidence' => 90),
            'bard' => array('company' => 'Google', 'confidence' => 90),
            'gemini' => array('company' => 'Google', 'confidence' => 90),
            'palm' => array('company' => 'Google', 'confidence' => 90),
            'bingbot' => array('company' => 'Microsoft', 'confidence' => 85),
            'copilot' => array('company' => 'Microsoft', 'confidence' => 85),
            'sydney' => array('company' => 'Microsoft', 'confidence' => 85),
            'facebookbot' => array('company' => 'Meta', 'confidence' => 85),
            'meta-ai' => array('company' => 'Meta', 'confidence' => 85),
            'llama' => array('company' => 'Meta', 'confidence' => 85),
            'perplexitybot' => array('company' => 'Perplexity', 'confidence' => 90),
            'perplexity' => array('company' => 'Perplexity', 'confidence' => 90),
            'you-bot' => array('company' => 'You.com', 'confidence' => 85),
            'character.ai' => array('company' => 'Character.AI', 'confidence' => 85),
            'jasper' => array('company' => 'Jasper', 'confidence' => 80),
            'writesonic' => array('company' => 'Writesonic', 'confidence' => 80),
            'copy.ai' => array('company' => 'Copy.ai', 'confidence' => 80),
            'cohere' => array('company' => 'Cohere', 'confidence' => 85),
            'huggingface' => array('company' => 'HuggingFace', 'confidence' => 85),
            'replicate' => array('company' => 'Replicate', 'confidence' => 80),
            'stability' => array('company' => 'Stability AI', 'confidence' => 80),
            'midjourney' => array('company' => 'Midjourney', 'confidence' => 80),
            'dalle' => array('company' => 'OpenAI', 'confidence' => 90),
            'runway' => array('company' => 'Runway', 'confidence' => 80),

            // Search Engine Bots (Standard Tier)
            'googlebot' => array('company' => 'Google', 'confidence' => 80),
            'slurp' => array('company' => 'Yahoo', 'confidence' => 75),
            'duckduckbot' => array('company' => 'DuckDuckGo', 'confidence' => 75),
            'yandexbot' => array('company' => 'Yandex', 'confidence' => 75),
            'baidubot' => array('company' => 'Baidu', 'confidence' => 75),
            'sogou' => array('company' => 'Sogou', 'confidence' => 70),
            'naver' => array('company' => 'Naver', 'confidence' => 70),
            'applebot' => array('company' => 'Apple', 'confidence' => 75),

            // Social Media Bots
            'twitterbot' => array('company' => 'Twitter', 'confidence' => 70),
            'linkedinbot' => array('company' => 'LinkedIn', 'confidence' => 70),
            'whatsapp' => array('company' => 'WhatsApp', 'confidence' => 70),
            'telegrambot' => array('company' => 'Telegram', 'confidence' => 70),
            'discordbot' => array('company' => 'Discord', 'confidence' => 70),
            'slackbot' => array('company' => 'Slack', 'confidence' => 70),
            'pinterestbot' => array('company' => 'Pinterest', 'confidence' => 70),
            'redditbot' => array('company' => 'Reddit', 'confidence' => 70),
            'snapchat' => array('company' => 'Snapchat', 'confidence' => 70),
            'tiktok' => array('company' => 'TikTok', 'confidence' => 70),

            // Research/Academic Bots
            'ia_archiver' => array('company' => 'Internet Archive', 'confidence' => 60),
            'archive.org_bot' => array('company' => 'Internet Archive', 'confidence' => 60),
            'researchbot' => array('company' => 'Research', 'confidence' => 50),
            'academicbot' => array('company' => 'Academic', 'confidence' => 50),
            'semanticscholar' => array('company' => 'Semantic Scholar', 'confidence' => 60),
            'arxiv' => array('company' => 'ArXiv', 'confidence' => 60),
            'pubmed' => array('company' => 'PubMed', 'confidence' => 60),
            'crossref' => array('company' => 'Crossref', 'confidence' => 60),
            'datacite' => array('company' => 'DataCite', 'confidence' => 60),

            // SEO/Marketing Bots
            'ahrefsbot' => array('company' => 'Ahrefs', 'confidence' => 65),
            'semrushbot' => array('company' => 'SEMrush', 'confidence' => 65),
            'mj12bot' => array('company' => 'Majestic', 'confidence' => 65),
            'dotbot' => array('company' => 'Moz', 'confidence' => 65),
            'screaming frog' => array('company' => 'Screaming Frog', 'confidence' => 60),
            'spyfu' => array('company' => 'SpyFu', 'confidence' => 60),
            'serpstat' => array('company' => 'Serpstat', 'confidence' => 60),

            // Security/Monitoring Bots
            'shodan' => array('company' => 'Shodan', 'confidence' => 70),
            'censys' => array('company' => 'Censys', 'confidence' => 70),
            'masscan' => array('company' => 'Security Scanner', 'confidence' => 65),
            'nmap' => array('company' => 'Security Scanner', 'confidence' => 65),
            'nuclei' => array('company' => 'Security Scanner', 'confidence' => 65),
            'qualys' => array('company' => 'Qualys', 'confidence' => 70),
            'rapid7' => array('company' => 'Rapid7', 'confidence' => 70),

            // Generic Patterns (Lower confidence)
            'bot' => array('company' => 'Generic Bot', 'confidence' => 40),
            'crawler' => array('company' => 'Generic Crawler', 'confidence' => 40),
            'spider' => array('company' => 'Generic Spider', 'confidence' => 40),
            'scraper' => array('company' => 'Generic Scraper', 'confidence' => 45),
            'curl' => array('company' => 'cURL', 'confidence' => 50),
            'wget' => array('company' => 'Wget', 'confidence' => 50),
            'python-requests' => array('company' => 'Python Requests', 'confidence' => 55),
            'node-fetch' => array('company' => 'Node.js', 'confidence' => 55),
            'axios' => array('company' => 'Axios', 'confidence' => 55),
            'postman' => array('company' => 'Postman', 'confidence' => 60),
            'insomnia' => array('company' => 'Insomnia', 'confidence' => 60),
        );
    }
    
    /**
     * Main detection method
     */
    public function detect() {
        // Get request data
        $user_agent = $this->get_user_agent();
        $ip = $this->get_client_ip();
        $url = $this->get_current_url();
        
        // Skip admin and login pages
        if (is_admin() || strpos($url, 'wp-login') !== false) {
            return false;
        }
        
        // Check if already detected by Cloudflare Worker
        if (isset($_SERVER['HTTP_X_PPC_DETECTED']) && $_SERVER['HTTP_X_PPC_DETECTED'] === 'true') {
            return array(
                'bot' => 'CloudflareDetected',
                'company' => 'Edge Detection',
                'confidence' => 95,
                'ip' => $ip,
                'user_agent' => $user_agent,
                'url' => $url,
                'action' => 'logged'
            );
        }
        
        // PHP-based detection
        $detection = $this->detect_from_user_agent($user_agent);
        
        if ($detection) {
            $detection['ip'] = $ip;
            $detection['user_agent'] = $user_agent;
            $detection['url'] = $url;
            $detection['action'] = 'logged';
            
            return $detection;
        }
        
        // Advanced detection (optional)
        if (get_option('paypercrawl_js_detection') === '1') {
            $js_detection = $this->detect_with_js();
            if ($js_detection) {
                return $js_detection;
            }
        }
        
        return false;
    }
    
    /**
     * Detect bot from user agent
     */
    private function detect_from_user_agent($user_agent) {
        $user_agent_lower = strtolower($user_agent);
        
        foreach ($this->bot_signatures as $bot_pattern => $info) {
            if (strpos($user_agent_lower, $bot_pattern) !== false) {
                return array(
                    'bot' => $bot_pattern,
                    'company' => $info['company'],
                    'confidence' => $info['confidence']
                );
            }
        }
        
        // Generic bot patterns
        $generic_patterns = array('bot', 'crawler', 'spider', 'scraper', 'crawl');
        
        foreach ($generic_patterns as $pattern) {
            if (strpos($user_agent_lower, $pattern) !== false) {
                return array(
                    'bot' => 'GenericBot',
                    'company' => 'Unknown',
                    'confidence' => 30
                );
            }
        }
        
        return false;
    }
    
    /**
     * JS-based detection (stub for future)
     */
    private function detect_with_js() {
        // This would implement JavaScript challenge detection
        // For now, return false
        return false;
    }
    
    /**
     * Get user agent
     */
    private function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
    
    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
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
     * Get current URL
     */
    private function get_current_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Get bot signatures for display
     */
    public function get_bot_signatures() {
        return $this->bot_signatures;
    }
    
    /**
     * Rate limiting check
     */
    private function check_rate_limit($ip) {
        $transient_key = 'paypercrawl_rate_' . md5($ip);
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            $requests = 1;
        } else {
            $requests++;
        }
        
        set_transient($transient_key, $requests, 60); // 1 minute window
        
        // If more than 10 requests per minute, it's likely a bot
        if ($requests > 10) {
            return array(
                'bot' => 'RapidRequests',
                'company' => 'Rate Limited',
                'confidence' => 80
            );
        }
        
        return false;
    }
}
