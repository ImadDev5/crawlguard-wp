<?php
/**
 * Enterprise Bot Detector with AI/ML Capabilities
 * 
 * @package PayPerCrawl_Enterprise
 * @subpackage BotDetection
 * @version 5.0.0
 * @author PayPerCrawl.tech
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Advanced Bot Detection Engine - Enterprise Grade
 * 
 * Features:
 * - Multi-layered detection algorithms
 * - Machine learning pattern recognition
 * - 60+ AI bot signatures
 * - Cloudflare integration
 * - Real-time scoring system
 */
class PayPerCrawl_Bot_Detector {
    
    /**
     * Bot detection confidence levels
     */
    const CONFIDENCE_LOW = 25;
    const CONFIDENCE_MEDIUM = 50;
    const CONFIDENCE_HIGH = 75;
    const CONFIDENCE_CERTAIN = 95;
    
    /**
     * Revenue tiers per confidence level
     */
    const REVENUE_TIERS = array(
        self::CONFIDENCE_CERTAIN => 5.00,
        self::CONFIDENCE_HIGH => 3.50,
        self::CONFIDENCE_MEDIUM => 2.00,
        self::CONFIDENCE_LOW => 1.00
    );
    
    /**
     * Rate limiting
     */
    private $rate_limit;
    private $requests_count = array();
    
    /**
     * Error handler
     */
    private $error_handler;
    
    /**
     * AI Bot Signatures Database (60+ bots)
     */
    private $ai_bot_signatures = array(
        // OpenAI/ChatGPT
        'ChatGPT-User' => array('type' => 'chatgpt', 'confidence' => 95, 'revenue' => 5.00),
        'GPTBot' => array('type' => 'openai', 'confidence' => 95, 'revenue' => 5.00),
        'OpenAI-Bot' => array('type' => 'openai', 'confidence' => 95, 'revenue' => 5.00),
        'OpenAI' => array('type' => 'openai', 'confidence' => 90, 'revenue' => 4.50),
        
        // Google Bard/Gemini
        'Bard' => array('type' => 'google-bard', 'confidence' => 95, 'revenue' => 4.80),
        'Google-Extended' => array('type' => 'google-ai', 'confidence' => 90, 'revenue' => 4.20),
        'Gemini' => array('type' => 'google-gemini', 'confidence' => 95, 'revenue' => 4.80),
        'GoogleBot-AI' => array('type' => 'google-ai', 'confidence' => 85, 'revenue' => 3.80),
        
        // Anthropic Claude
        'Claude-Bot' => array('type' => 'anthropic', 'confidence' => 95, 'revenue' => 4.50),
        'Anthropic-AI' => array('type' => 'anthropic', 'confidence' => 90, 'revenue' => 4.00),
        'ClaudeBot' => array('type' => 'anthropic', 'confidence' => 95, 'revenue' => 4.50),
        
        // Microsoft AI
        'BingBot' => array('type' => 'microsoft-ai', 'confidence' => 85, 'revenue' => 3.50),
        'msnbot-ai' => array('type' => 'microsoft-ai', 'confidence' => 80, 'revenue' => 3.20),
        'Microsoft-AI' => array('type' => 'microsoft-ai', 'confidence' => 85, 'revenue' => 3.50),
        
        // Meta AI
        'Meta-ExternalAgent' => array('type' => 'meta-ai', 'confidence' => 85, 'revenue' => 3.30),
        'FacebookBot-AI' => array('type' => 'meta-ai', 'confidence' => 80, 'revenue' => 3.00),
        'Meta-AI' => array('type' => 'meta-ai', 'confidence' => 85, 'revenue' => 3.30),
        
        // Perplexity AI
        'PerplexityBot' => array('type' => 'perplexity', 'confidence' => 90, 'revenue' => 3.80),
        'Perplexity-AI' => array('type' => 'perplexity', 'confidence' => 85, 'revenue' => 3.50),
        
        // Character.AI
        'Character-AI' => array('type' => 'character-ai', 'confidence' => 85, 'revenue' => 3.20),
        'CharacterBot' => array('type' => 'character-ai', 'confidence' => 80, 'revenue' => 3.00),
        
        // AI Research Bots
        'AI-Research-Bot' => array('type' => 'research', 'confidence' => 75, 'revenue' => 2.80),
        'Research-AI' => array('type' => 'research', 'confidence' => 70, 'revenue' => 2.50),
        
        // Commercial AI Tools
        'Jasper-AI' => array('type' => 'commercial-ai', 'confidence' => 80, 'revenue' => 3.10),
        'Copy.ai' => array('type' => 'commercial-ai', 'confidence' => 75, 'revenue' => 2.90),
        'Writesonic' => array('type' => 'commercial-ai', 'confidence' => 75, 'revenue' => 2.90),
        'ContentBot' => array('type' => 'commercial-ai', 'confidence' => 70, 'revenue' => 2.60),
        
        // AI Training Data Collectors
        'Common Crawl' => array('type' => 'data-collector', 'confidence' => 85, 'revenue' => 3.40),
        'CCBot' => array('type' => 'data-collector', 'confidence' => 90, 'revenue' => 3.80),
        'AI-Data-Collector' => array('type' => 'data-collector', 'confidence' => 80, 'revenue' => 3.20),
        'DataBot' => array('type' => 'data-collector', 'confidence' => 75, 'revenue' => 2.90),
        
        // Search Engine AI
        'DuckDuckBot-AI' => array('type' => 'search-ai', 'confidence' => 70, 'revenue' => 2.70),
        'YandexBot-AI' => array('type' => 'search-ai', 'confidence' => 70, 'revenue' => 2.70),
        'Baiduspider-AI' => array('type' => 'search-ai', 'confidence' => 70, 'revenue' => 2.70),
        
        // Enterprise AI
        'IBM-Watson' => array('type' => 'enterprise-ai', 'confidence' => 85, 'revenue' => 3.60),
        'AWS-AI-Bot' => array('type' => 'enterprise-ai', 'confidence' => 80, 'revenue' => 3.40),
        'Azure-AI' => array('type' => 'enterprise-ai', 'confidence' => 80, 'revenue' => 3.40),
        'Oracle-AI' => array('type' => 'enterprise-ai', 'confidence' => 75, 'revenue' => 3.10),
        
        // Academic AI
        'Academic-AI' => array('type' => 'academic', 'confidence' => 70, 'revenue' => 2.50),
        'University-Bot' => array('type' => 'academic', 'confidence' => 65, 'revenue' => 2.30),
        'Research-Bot' => array('type' => 'academic', 'confidence' => 70, 'revenue' => 2.50),
        
        // AI Crawlers
        'AI-Crawler' => array('type' => 'ai-crawler', 'confidence' => 80, 'revenue' => 3.20),
        'ML-Bot' => array('type' => 'ml-bot', 'confidence' => 75, 'revenue' => 2.90),
        'NLP-Bot' => array('type' => 'nlp-bot', 'confidence' => 75, 'revenue' => 2.90),
        
        // Generic AI patterns
        'AI-Agent' => array('type' => 'generic-ai', 'confidence' => 70, 'revenue' => 2.60),
        'Artificial-Intelligence' => array('type' => 'generic-ai', 'confidence' => 75, 'revenue' => 2.80),
        'Machine-Learning' => array('type' => 'generic-ai', 'confidence' => 70, 'revenue' => 2.60),
        'Neural-Network' => array('type' => 'generic-ai', 'confidence' => 70, 'revenue' => 2.60),
        
        // New AI Services (2024+)
        'Mistral-AI' => array('type' => 'mistral', 'confidence' => 90, 'revenue' => 4.20),
        'Cohere-AI' => array('type' => 'cohere', 'confidence' => 85, 'revenue' => 3.80),
        'Stability-AI' => array('type' => 'stability', 'confidence' => 80, 'revenue' => 3.50),
        'Hugging-Face' => array('type' => 'huggingface', 'confidence' => 80, 'revenue' => 3.50),
        'Replicate-AI' => array('type' => 'replicate', 'confidence' => 75, 'revenue' => 3.20),
        
        // AI Image Generators
        'DALL-E' => array('type' => 'image-ai', 'confidence' => 85, 'revenue' => 3.60),
        'Midjourney' => array('type' => 'image-ai', 'confidence' => 80, 'revenue' => 3.40),
        'Stable-Diffusion' => array('type' => 'image-ai', 'confidence' => 75, 'revenue' => 3.10),
        
        // Voice AI
        'ElevenLabs' => array('type' => 'voice-ai', 'confidence' => 80, 'revenue' => 3.30),
        'Murf-AI' => array('type' => 'voice-ai', 'confidence' => 75, 'revenue' => 3.00),
        
        // AI Code Assistants
        'GitHub-Copilot' => array('type' => 'code-ai', 'confidence' => 85, 'revenue' => 3.70),
        'Replit-AI' => array('type' => 'code-ai', 'confidence' => 80, 'revenue' => 3.40),
        'CodeT5' => array('type' => 'code-ai', 'confidence' => 75, 'revenue' => 3.10),
        
        // Specialized AI
        'Legal-AI' => array('type' => 'specialized-ai', 'confidence' => 75, 'revenue' => 3.20),
        'Medical-AI' => array('type' => 'specialized-ai', 'confidence' => 80, 'revenue' => 3.50),
        'Finance-AI' => array('type' => 'specialized-ai', 'confidence' => 80, 'revenue' => 3.50),
    );
    
    /**
     * Advanced pattern detection
     */
    private $suspicious_patterns = array(
        'headless' => array('confidence' => 60, 'indicators' => array('HeadlessChrome', 'PhantomJS', 'Selenium')),
        'automation' => array('confidence' => 70, 'indicators' => array('WebDriver', 'Automation', 'Robot')),
        'scraping' => array('confidence' => 65, 'indicators' => array('Scraper', 'Spider', 'Crawler')),
        'ai_keywords' => array('confidence' => 80, 'indicators' => array('AI', 'Bot', 'Agent', 'Assistant', 'GPT', 'LLM')),
        'research' => array('confidence' => 55, 'indicators' => array('Research', 'Academic', 'Study', 'Analysis')),
        'suspicious_headers' => array('confidence' => 75, 'indicators' => array('python-requests', 'curl', 'wget')),
    );
    
    /**
     * AI company IP ranges (simplified for demo)
     */
    private $ai_ip_ranges = array(
        'openai' => array('20.171.0.0/16', '40.83.0.0/16'),
        'google' => array('8.8.0.0/16', '35.0.0.0/8'),
        'microsoft' => array('13.0.0.0/8', '20.0.0.0/8'),
        'anthropic' => array('54.0.0.0/8', '52.0.0.0/8'),
        'meta' => array('31.13.0.0/16', '157.240.0.0/16'),
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->rate_limit = get_option('paypercrawl_rate_limit', 100);
        $this->error_handler = PayPerCrawl_Error_Handler::get_instance();
        
        // Add AJAX handlers
        add_action('wp_ajax_paypercrawl_track', array($this, 'handle_tracking'));
        add_action('wp_ajax_nopriv_paypercrawl_track', array($this, 'handle_tracking'));
    }
    
    /**
     * Process incoming request for bot detection
     */
    public function process_request() {
        try {
            // Get request data
            $user_agent = $this->get_user_agent();
            $ip_address = $this->get_client_ip();
            $page_url = $this->get_current_url();
            
            // Rate limiting check
            if (!$this->check_rate_limit($ip_address)) {
                return false;
            }
            
            // Multi-layer detection
            $detection_result = $this->detect_bot($user_agent, $ip_address, $page_url);
            
            // Process if bot detected
            if ($detection_result['is_bot'] && $detection_result['confidence'] >= get_option('paypercrawl_confidence_threshold', 75)) {
                $this->process_bot_detection($detection_result);
            }
            
            return $detection_result;
            
        } catch (Exception $e) {
            $this->error_handler->log('error', 'Bot detection failed: ' . $e->getMessage(), array(), 'BOT_DETECTOR');
            return false;
        }
    }
    
    /**
     * Multi-layered bot detection
     * 
     * @param string $user_agent
     * @param string $ip_address
     * @param string $page_url
     * @return array
     */
    private function detect_bot($user_agent, $ip_address, $page_url) {
        $confidence_scores = array();
        $bot_types = array();
        $detection_methods = array();
        
        // Layer 1: Direct signature matching
        $signature_result = $this->check_bot_signatures($user_agent);
        if ($signature_result['detected']) {
            $confidence_scores[] = $signature_result['confidence'];
            $bot_types[] = $signature_result['type'];
            $detection_methods[] = 'signature';
        }
        
        // Layer 2: Pattern analysis
        $pattern_result = $this->analyze_patterns($user_agent);
        if ($pattern_result['detected']) {
            $confidence_scores[] = $pattern_result['confidence'];
            $bot_types[] = $pattern_result['type'];
            $detection_methods[] = 'pattern';
        }
        
        // Layer 3: IP range checking
        $ip_result = $this->check_ip_ranges($ip_address);
        if ($ip_result['detected']) {
            $confidence_scores[] = $ip_result['confidence'];
            $bot_types[] = $ip_result['type'];
            $detection_methods[] = 'ip_range';
        }
        
        // Layer 4: Behavioral analysis
        $behavior_result = $this->analyze_behavior($ip_address, $user_agent);
        if ($behavior_result['detected']) {
            $confidence_scores[] = $behavior_result['confidence'];
            $bot_types[] = $behavior_result['type'];
            $detection_methods[] = 'behavior';
        }
        
        // Layer 5: Header analysis
        $header_result = $this->analyze_headers();
        if ($header_result['detected']) {
            $confidence_scores[] = $header_result['confidence'];
            $bot_types[] = $header_result['type'];
            $detection_methods[] = 'headers';
        }
        
        // Layer 6: Machine learning scoring
        $ml_result = $this->ml_scoring($user_agent, $ip_address);
        if ($ml_result['detected']) {
            $confidence_scores[] = $ml_result['confidence'];
            $bot_types[] = $ml_result['type'];
            $detection_methods[] = 'ml';
        }
        
        // Calculate final confidence
        $final_confidence = empty($confidence_scores) ? 0 : max($confidence_scores);
        $is_bot = $final_confidence >= 25; // Minimum threshold
        
        // Determine primary bot type
        $primary_type = empty($bot_types) ? 'unknown' : array_count_values($bot_types);
        $primary_type = array_keys($primary_type, max($primary_type))[0];
        
        // Calculate revenue potential
        $revenue = $this->calculate_revenue($final_confidence, $primary_type);
        
        return array(
            'is_bot' => $is_bot,
            'confidence' => $final_confidence,
            'bot_type' => $primary_type,
            'detection_methods' => $detection_methods,
            'revenue_potential' => $revenue,
            'user_agent' => $user_agent,
            'ip_address' => $ip_address,
            'page_url' => $page_url,
            'timestamp' => current_time('mysql'),
            'all_scores' => $confidence_scores,
            'all_types' => $bot_types
        );
    }
    
    /**
     * Check bot signatures
     * 
     * @param string $user_agent
     * @return array
     */
    private function check_bot_signatures($user_agent) {
        foreach ($this->ai_bot_signatures as $signature => $data) {
            if (stripos($user_agent, $signature) !== false) {
                return array(
                    'detected' => true,
                    'confidence' => $data['confidence'],
                    'type' => $data['type'],
                    'signature' => $signature
                );
            }
        }
        
        return array('detected' => false, 'confidence' => 0, 'type' => 'unknown');
    }
    
    /**
     * Analyze suspicious patterns
     * 
     * @param string $user_agent
     * @return array
     */
    private function analyze_patterns($user_agent) {
        $max_confidence = 0;
        $detected_type = 'unknown';
        
        foreach ($this->suspicious_patterns as $pattern_type => $pattern_data) {
            foreach ($pattern_data['indicators'] as $indicator) {
                if (stripos($user_agent, $indicator) !== false) {
                    if ($pattern_data['confidence'] > $max_confidence) {
                        $max_confidence = $pattern_data['confidence'];
                        $detected_type = $pattern_type;
                    }
                }
            }
        }
        
        return array(
            'detected' => $max_confidence > 0,
            'confidence' => $max_confidence,
            'type' => $detected_type
        );
    }
    
    /**
     * Check IP ranges
     * 
     * @param string $ip_address
     * @return array
     */
    private function check_ip_ranges($ip_address) {
        foreach ($this->ai_ip_ranges as $company => $ranges) {
            foreach ($ranges as $range) {
                if ($this->ip_in_range($ip_address, $range)) {
                    return array(
                        'detected' => true,
                        'confidence' => 80,
                        'type' => $company . '-ip'
                    );
                }
            }
        }
        
        return array('detected' => false, 'confidence' => 0, 'type' => 'unknown');
    }
    
    /**
     * Analyze request behavior
     * 
     * @param string $ip_address
     * @param string $user_agent
     * @return array
     */
    private function analyze_behavior($ip_address, $user_agent) {
        // Check request frequency
        $recent_requests = $this->get_recent_requests($ip_address);
        
        if (count($recent_requests) > 10) { // More than 10 requests in last minute
            return array(
                'detected' => true,
                'confidence' => 70,
                'type' => 'high-frequency'
            );
        }
        
        // Check for consistent user agent
        $ua_variations = array_unique(array_column($recent_requests, 'user_agent'));
        if (count($ua_variations) > 3) { // Multiple user agents from same IP
            return array(
                'detected' => true,
                'confidence' => 65,
                'type' => 'ua-rotation'
            );
        }
        
        return array('detected' => false, 'confidence' => 0, 'type' => 'unknown');
    }
    
    /**
     * Analyze request headers
     * 
     * @return array
     */
    private function analyze_headers() {
        $headers = getallheaders();
        $suspicious_indicators = 0;
        
        // Check for missing common headers
        if (!isset($headers['Accept-Language'])) $suspicious_indicators++;
        if (!isset($headers['Accept-Encoding'])) $suspicious_indicators++;
        if (!isset($headers['Cache-Control'])) $suspicious_indicators++;
        
        // Check for automation headers
        if (isset($headers['X-Requested-With']) && $headers['X-Requested-With'] === 'XMLHttpRequest') {
            // AJAX requests are less suspicious
            $suspicious_indicators -= 1;
        }
        
        $confidence = min($suspicious_indicators * 20, 80);
        
        return array(
            'detected' => $confidence > 40,
            'confidence' => $confidence,
            'type' => 'header-analysis'
        );
    }
    
    /**
     * Machine learning scoring (simplified)
     * 
     * @param string $user_agent
     * @param string $ip_address
     * @return array
     */
    private function ml_scoring($user_agent, $ip_address) {
        $score = 0;
        
        // Length analysis
        $ua_length = strlen($user_agent);
        if ($ua_length < 20 || $ua_length > 500) {
            $score += 20;
        }
        
        // Entropy analysis
        $entropy = $this->calculate_entropy($user_agent);
        if ($entropy < 3.0) { // Low entropy = suspicious
            $score += 25;
        }
        
        // Common word analysis
        $common_words = array('Mozilla', 'Chrome', 'Safari', 'Firefox', 'Edge');
        $word_count = 0;
        foreach ($common_words as $word) {
            if (stripos($user_agent, $word) !== false) {
                $word_count++;
            }
        }
        
        if ($word_count === 0) {
            $score += 30;
        }
        
        // Version pattern analysis
        if (!preg_match('/\d+\.\d+/', $user_agent)) {
            $score += 15;
        }
        
        return array(
            'detected' => $score > 50,
            'confidence' => min($score, 95),
            'type' => 'ml-analysis'
        );
    }
    
    /**
     * Calculate revenue based on confidence and bot type
     * 
     * @param int $confidence
     * @param string $bot_type
     * @return float
     */
    private function calculate_revenue($confidence, $bot_type) {
        // Base revenue by confidence
        foreach (self::REVENUE_TIERS as $threshold => $revenue) {
            if ($confidence >= $threshold) {
                $base_revenue = $revenue;
                break;
            }
        }
        
        if (!isset($base_revenue)) {
            $base_revenue = 0.50; // Minimum revenue
        }
        
        // Multiplier by bot type
        $type_multipliers = array(
            'openai' => 1.5,
            'google-ai' => 1.4,
            'anthropic' => 1.3,
            'microsoft-ai' => 1.2,
            'enterprise-ai' => 1.3,
            'commercial-ai' => 1.1,
            'data-collector' => 1.2,
            'generic-ai' => 1.0
        );
        
        $multiplier = isset($type_multipliers[$bot_type]) ? $type_multipliers[$bot_type] : 1.0;
        
        return round($base_revenue * $multiplier, 2);
    }
    
    /**
     * Process detected bot
     * 
     * @param array $detection_result
     */
    private function process_bot_detection($detection_result) {
        global $wpdb;
        
        try {
            // Log detection to database
            $table_name = $wpdb->prefix . PAYPERCRAWL_DETECTIONS_TABLE;
            
            $insert_result = $wpdb->insert(
                $table_name,
                array(
                    'user_agent' => $detection_result['user_agent'],
                    'ip_address' => $detection_result['ip_address'],
                    'bot_type' => $detection_result['bot_type'],
                    'confidence_score' => $detection_result['confidence'],
                    'page_url' => $detection_result['page_url'],
                    'detected_at' => $detection_result['timestamp'],
                    'revenue_generated' => $detection_result['revenue_potential'],
                    'status' => 'active',
                    'metadata' => wp_json_encode(array(
                        'detection_methods' => $detection_result['detection_methods'],
                        'all_scores' => $detection_result['all_scores'],
                        'all_types' => $detection_result['all_types']
                    ))
                ),
                array('%s', '%s', '%s', '%f', '%s', '%s', '%f', '%s', '%s')
            );
            
            if ($insert_result === false) {
                throw new Exception('Failed to insert detection record: ' . $wpdb->last_error);
            }
            
            // Update analytics
            $this->update_analytics($detection_result);
            
            // Send to API if configured
            $this->send_to_api($detection_result);
            
            // Log successful detection
            $this->error_handler->log('info', 'Bot detected successfully', array(
                'bot_type' => $detection_result['bot_type'],
                'confidence' => $detection_result['confidence'],
                'revenue' => $detection_result['revenue_potential']
            ), 'BOT_DETECTION');
            
        } catch (Exception $e) {
            $this->error_handler->log('error', 'Failed to process bot detection: ' . $e->getMessage(), array(
                'detection_result' => $detection_result
            ), 'BOT_DETECTION');
        }
    }
    
    /**
     * Update analytics data
     * 
     * @param array $detection_result
     */
    private function update_analytics($detection_result) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . PAYPERCRAWL_ANALYTICS_TABLE;
        $today = current_time('Y-m-d');
        
        // Get existing record for today
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE date_recorded = %s",
                $today
            ),
            ARRAY_A
        );
        
        if ($existing) {
            // Update existing record
            $wpdb->update(
                $table_name,
                array(
                    'total_detections' => $existing['total_detections'] + 1,
                    'revenue_generated' => $existing['revenue_generated'] + $detection_result['revenue_potential']
                ),
                array('date_recorded' => $today),
                array('%d', '%f'),
                array('%s')
            );
        } else {
            // Create new record
            $wpdb->insert(
                $table_name,
                array(
                    'date_recorded' => $today,
                    'total_detections' => 1,
                    'unique_bots' => 1,
                    'revenue_generated' => $detection_result['revenue_potential'],
                    'top_bot_types' => wp_json_encode(array($detection_result['bot_type'])),
                    'page_views' => 1,
                    'conversion_rate' => 100.0
                ),
                array('%s', '%d', '%d', '%f', '%s', '%d', '%f')
            );
        }
    }
    
    /**
     * Send detection to API
     * 
     * @param array $detection_result
     */
    private function send_to_api($detection_result) {
        $api_key = get_option('paypercrawl_api_key');
        
        if (empty($api_key)) {
            return;
        }
        
        $webhook_url = get_option('paypercrawl_webhook_url');
        
        if (!empty($webhook_url)) {
            wp_remote_post($webhook_url, array(
                'body' => wp_json_encode($detection_result),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key
                ),
                'timeout' => 5
            ));
        }
    }
    
    /**
     * Handle tracking AJAX requests
     */
    public function handle_tracking() {
        try {
            // Verify nonce for security
            if (!wp_verify_nonce($_GET['nonce'] ?? '', 'paypercrawl_nonce')) {
                wp_die('Security check failed');
            }
            
            // Process tracking
            $this->process_request();
            
            wp_send_json_success(array('tracked' => true));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Get recent requests from IP
     * 
     * @param string $ip_address
     * @return array
     */
    private function get_recent_requests($ip_address) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . PAYPERCRAWL_DETECTIONS_TABLE;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                 WHERE ip_address = %s 
                 AND detected_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                 ORDER BY detected_at DESC",
                $ip_address
            ),
            ARRAY_A
        );
    }
    
    /**
     * Rate limiting check
     * 
     * @param string $ip_address
     * @return bool
     */
    private function check_rate_limit($ip_address) {
        $key = 'rate_limit_' . md5($ip_address);
        $current_minute = floor(time() / 60);
        
        if (!isset($this->requests_count[$key])) {
            $this->requests_count[$key] = array('minute' => $current_minute, 'count' => 0);
        }
        
        if ($this->requests_count[$key]['minute'] !== $current_minute) {
            $this->requests_count[$key] = array('minute' => $current_minute, 'count' => 0);
        }
        
        $this->requests_count[$key]['count']++;
        
        return $this->requests_count[$key]['count'] <= $this->rate_limit;
    }
    
    /**
     * Calculate string entropy
     * 
     * @param string $string
     * @return float
     */
    private function calculate_entropy($string) {
        $chars = array_count_values(str_split($string));
        $length = strlen($string);
        $entropy = 0;
        
        foreach ($chars as $count) {
            $p = $count / $length;
            $entropy -= $p * log($p, 2);
        }
        
        return $entropy;
    }
    
    /**
     * Check if IP is in range
     * 
     * @param string $ip
     * @param string $range
     * @return bool
     */
    private function ip_in_range($ip, $range) {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }
    
    /**
     * Get user agent
     * 
     * @return string
     */
    private function get_user_agent() {
        return sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
    }
    
    /**
     * Get client IP
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
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
        
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }
    
    /**
     * Get current URL
     * 
     * @return string
     */
    private function get_current_url() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Get detection statistics
     * 
     * @return array
     */
    public function get_detection_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . PAYPERCRAWL_DETECTIONS_TABLE;
        
        $stats = array(
            'total_detections' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
            'total_revenue' => $wpdb->get_var("SELECT SUM(revenue_generated) FROM {$table_name}"),
            'detections_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE DATE(detected_at) = %s",
                current_time('Y-m-d')
            )),
            'revenue_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(revenue_generated) FROM {$table_name} WHERE DATE(detected_at) = %s",
                current_time('Y-m-d')
            )),
            'top_bots' => $wpdb->get_results(
                "SELECT bot_type, COUNT(*) as count, SUM(revenue_generated) as revenue 
                 FROM {$table_name} 
                 WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY bot_type 
                 ORDER BY count DESC 
                 LIMIT 10",
                ARRAY_A
            )
        );
        
        return $stats;
    }
}

// End of file
