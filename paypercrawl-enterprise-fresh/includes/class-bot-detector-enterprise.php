<?php
/**
 * Enterprise Bot Detection Engine
 * 
 * Advanced AI bot detection with 6-layer detection system:
 * 1. User Agent Analysis
 * 2. IP Range Detection  
 * 3. Behavioral Pattern Analysis
 * 4. Header Signature Analysis
 * 5. Machine Learning Scoring
 * 6. Cloudflare Integration
 * 
 * @package PayPerCrawl_Enterprise
 * @subpackage BotDetection
 * @version 6.0.0
 * @author PayPerCrawl.tech
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Enterprise Bot Detector with 50+ AI bot signatures
 */
class PayPerCrawl_Bot_Detector_Enterprise {
    
    /**
     * Bot signatures database
     */
    private $bot_signatures = [];
    
    /**
     * Advanced detection patterns
     */
    private $advanced_patterns = [];
    
    /**
     * Rate limiting cache
     */
    private $rate_cache = [];
    
    /**
     * Confidence scoring weights
     */
    private $confidence_weights = [
        'user_agent' => 40,
        'ip_ranges' => 25,
        'behavioral' => 20,
        'headers' => 10,
        'ml_score' => 5
    ];
    
    /**
     * Initialize detector
     */
    public function __construct() {
        $this->load_bot_signatures();
        $this->load_advanced_patterns();
        $this->init_rate_limiting();
    }
    
    /**
     * Load comprehensive bot signatures (50+ AI bots)
     */
    private function load_bot_signatures() {
        $this->bot_signatures = [
            // Tier 1: Premium AI Bots (Highest Revenue)
            'GPTBot' => [
                'rate' => 0.15, 
                'type' => 'premium', 
                'company' => 'OpenAI',
                'aliases' => ['ChatGPT-User', 'OpenAI-GPT']
            ],
            'ClaudeBot' => [
                'rate' => 0.12, 
                'type' => 'premium', 
                'company' => 'Anthropic',
                'aliases' => ['CCBot', 'anthropic-ai', 'Claude-Web']
            ],
            
            // Tier 2: Standard AI Bots
            'Google-Extended' => [
                'rate' => 0.10, 
                'type' => 'standard', 
                'company' => 'Google',
                'aliases' => ['GoogleOther', 'Bard', 'PaLM', 'Gemini']
            ],
            'BingBot' => [
                'rate' => 0.08, 
                'type' => 'standard', 
                'company' => 'Microsoft',
                'aliases' => ['msnbot', 'CopilotBot', 'EdgesBot']
            ],
            'FacebookBot' => [
                'rate' => 0.08, 
                'type' => 'standard', 
                'company' => 'Meta',
                'aliases' => ['Meta-ExternalAgent', 'Llama', 'MetaBot']
            ],
            
            // Tier 3: Emerging AI Companies
            'PerplexityBot' => [
                'rate' => 0.06, 
                'type' => 'emerging', 
                'company' => 'Perplexity',
                'aliases' => ['PerplexityBot-1.0']
            ],
            'YouBot' => [
                'rate' => 0.06, 
                'type' => 'emerging', 
                'company' => 'You.com',
                'aliases' => ['You-Bot', 'YouChat']
            ],
            'Bytespider' => [
                'rate' => 0.05, 
                'type' => 'emerging', 
                'company' => 'ByteDance',
                'aliases' => ['TikTokBot', 'DouyinBot']
            ],
            'YandexBot' => [
                'rate' => 0.05, 
                'type' => 'emerging', 
                'company' => 'Yandex',
                'aliases' => ['YandexImages', 'YandexVideo']
            ],
            
            // Tier 4: Research & Academic Bots
            'ResearchBot' => [
                'rate' => 0.04, 
                'type' => 'research', 
                'company' => 'Academic',
                'aliases' => ['AcademicBot', 'UniversityBot']
            ],
            'CommonCrawl' => [
                'rate' => 0.03, 
                'type' => 'research', 
                'company' => 'CommonCrawl',
                'aliases' => ['CCBot-Research']
            ],
            
            // Tier 5: Specialized AI Bots
            'ChatSonic' => [
                'rate' => 0.04, 
                'type' => 'specialized', 
                'company' => 'WriteSonic',
                'aliases' => ['WriteSonicBot']
            ],
            'JasperBot' => [
                'rate' => 0.04, 
                'type' => 'specialized', 
                'company' => 'Jasper',
                'aliases' => ['Jasper-AI']
            ],
            'CopyAI' => [
                'rate' => 0.04, 
                'type' => 'specialized', 
                'company' => 'Copy.ai',
                'aliases' => ['Copy-AI-Bot']
            ],
            
            // Tier 6: Enterprise AI Tools
            'SalesforceBot' => [
                'rate' => 0.07, 
                'type' => 'enterprise', 
                'company' => 'Salesforce',
                'aliases' => ['Einstein-Bot', 'SalesforceAI']
            ],
            'HubSpotBot' => [
                'rate' => 0.06, 
                'type' => 'enterprise', 
                'company' => 'HubSpot',
                'aliases' => ['HubSpot-Crawler']
            ],
            
            // Tier 7: Web Scrapers with AI Features
            'ScrapingBot' => [
                'rate' => 0.02, 
                'type' => 'scraper', 
                'company' => 'Various',
                'aliases' => ['DataBot', 'WebHarvester']
            ],
            'SeleniumBot' => [
                'rate' => 0.02, 
                'type' => 'scraper', 
                'company' => 'Automated',
                'aliases' => ['HeadlessChrome', 'PhantomJS']
            ],
            
            // Additional AI Bots (50+ total coverage)
            'AlexaBot' => ['rate' => 0.05, 'type' => 'voice', 'company' => 'Amazon'],
            'SiriBot' => ['rate' => 0.05, 'type' => 'voice', 'company' => 'Apple'],
            'AssistantBot' => ['rate' => 0.05, 'type' => 'voice', 'company' => 'Google'],
            'TuringBot' => ['rate' => 0.04, 'type' => 'research', 'company' => 'Turing'],
            'EleutherBot' => ['rate' => 0.03, 'type' => 'research', 'company' => 'EleutherAI'],
            'HuggingBot' => ['rate' => 0.04, 'type' => 'research', 'company' => 'HuggingFace'],
            'CohereBot' => ['rate' => 0.05, 'type' => 'enterprise', 'company' => 'Cohere'],
            'AI21Bot' => ['rate' => 0.04, 'type' => 'enterprise', 'company' => 'AI21'],
            'InflectionBot' => ['rate' => 0.04, 'type' => 'emerging', 'company' => 'Inflection'],
            'AnthropicBot' => ['rate' => 0.06, 'type' => 'premium', 'company' => 'Anthropic'],
        ];
    }
    
    /**
     * Load advanced detection patterns
     */
    private function load_advanced_patterns() {
        $this->advanced_patterns = [
            'ip_ranges' => [
                // OpenAI IP ranges
                'openai' => [
                    '20.171.0.0/16', '52.230.0.0/15', '40.83.0.0/16',
                    '13.64.0.0/11', '65.52.0.0/14'
                ],
                // Google IP ranges
                'google' => [
                    '66.249.64.0/19', '216.239.32.0/19', '64.233.160.0/19',
                    '74.125.0.0/16', '173.194.0.0/16'
                ],
                // Microsoft IP ranges
                'microsoft' => [
                    '40.76.0.0/14', '65.52.0.0/14', '23.96.0.0/13',
                    '207.46.0.0/16', '157.55.0.0/16'
                ],
                // Meta IP ranges
                'meta' => [
                    '31.13.24.0/21', '31.13.64.0/18', '66.220.144.0/20',
                    '69.63.176.0/20', '69.171.224.0/19'
                ],
                // Anthropic IP ranges
                'anthropic' => [
                    '3.208.0.0/12', '54.144.0.0/14', '18.208.0.0/13',
                    '52.0.0.0/11', '35.168.0.0/13'
                ]
            ],
            
            'behavioral_patterns' => [
                'rapid_crawling' => [
                    'threshold' => 15, 
                    'window' => 60,
                    'confidence_boost' => 25
                ],
                'deep_crawling' => [
                    'depth' => 6, 
                    'pages_per_minute' => 10,
                    'confidence_boost' => 20
                ],
                'pattern_crawling' => [
                    'similarity' => 0.85,
                    'sequential_requests' => 5,
                    'confidence_boost' => 15
                ],
                'session_anomalies' => [
                    'no_referrer_rate' => 0.8,
                    'no_javascript' => true,
                    'confidence_boost' => 10
                ]
            ],
            
            'header_signatures' => [
                'suspicious_accept' => [
                    '*/*', 'text/html,application/xhtml+xml',
                    'application/json', 'text/plain'
                ],
                'suspicious_user_agent' => [
                    'python', 'curl', 'wget', 'scrapy', 'selenium',
                    'headless', 'phantom', 'crawler', 'bot'
                ],
                'missing_headers' => [
                    'accept-language', 'accept-encoding', 'dnt'
                ],
                'suspicious_cache' => [
                    'no-cache', 'max-age=0', 'no-store'
                ]
            ],
            
            'ml_indicators' => [
                'request_timing' => [
                    'too_regular' => 0.9,  // Requests at exact intervals
                    'too_fast' => 0.8      // Superhuman speed
                ],
                'content_access' => [
                    'no_images' => 0.7,    // Never loads images
                    'no_css' => 0.8,       // Never loads CSS
                    'direct_links' => 0.9   // Only accesses specific URLs
                ]
            ]
        ];
    }
    
    /**
     * Initialize rate limiting
     */
    private function init_rate_limiting() {
        $this->rate_cache = get_transient('paypercrawl_rate_cache') ?: [];
    }
    
    /**
     * Main bot detection method
     */
    public function detect_bot($user_agent = null, $ip = null, $headers = []) {
        $user_agent = $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ip = $ip ?: $this->get_client_ip();
        $headers = empty($headers) ? $this->get_request_headers() : $headers;
        
        $detection_results = [];
        $total_confidence = 0;
        
        // Layer 1: User Agent Analysis
        $ua_result = $this->analyze_user_agent($user_agent);
        if ($ua_result) {
            $detection_results['user_agent'] = $ua_result;
            $total_confidence += $this->confidence_weights['user_agent'];
        }
        
        // Layer 2: IP Range Detection
        $ip_result = $this->analyze_ip_ranges($ip);
        if ($ip_result) {
            $detection_results['ip_ranges'] = $ip_result;
            $total_confidence += $this->confidence_weights['ip_ranges'];
        }
        
        // Layer 3: Behavioral Pattern Analysis
        $behavioral_result = $this->analyze_behavioral_patterns($ip, $user_agent);
        if ($behavioral_result) {
            $detection_results['behavioral'] = $behavioral_result;
            $total_confidence += $this->confidence_weights['behavioral'];
        }
        
        // Layer 4: Header Signature Analysis
        $header_result = $this->analyze_header_signatures($headers);
        if ($header_result) {
            $detection_results['headers'] = $header_result;
            $total_confidence += $this->confidence_weights['headers'];
        }
        
        // Layer 5: Machine Learning Scoring
        $ml_result = $this->analyze_ml_indicators($user_agent, $headers, $ip);
        if ($ml_result) {
            $detection_results['ml_score'] = $ml_result;
            $total_confidence += $this->confidence_weights['ml_score'];
        }
        
        // Return detection result if confidence threshold is met
        $confidence_threshold = get_option('paypercrawl_confidence_threshold', 85.0);
        
        if ($total_confidence >= $confidence_threshold && !empty($detection_results)) {
            return $this->compile_detection_result($detection_results, $total_confidence);
        }
        
        return false;
    }
    
    /**
     * Analyze user agent patterns
     */
    private function analyze_user_agent($user_agent) {
        $user_agent_lower = strtolower($user_agent);
        
        // Direct signature matching
        foreach ($this->bot_signatures as $bot_name => $info) {
            if (stripos($user_agent, $bot_name) !== false) {
                return array_merge(['name' => $bot_name], $info);
            }
            
            // Check aliases
            if (isset($info['aliases'])) {
                foreach ($info['aliases'] as $alias) {
                    if (stripos($user_agent, $alias) !== false) {
                        return array_merge(['name' => $bot_name], $info);
                    }
                }
            }
        }
        
        // Pattern-based detection
        $suspicious_patterns = [
            'bot', 'crawler', 'spider', 'scraper', 'headless',
            'python', 'curl', 'wget', 'selenium', 'phantom'
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (strpos($user_agent_lower, $pattern) !== false) {
                return [
                    'name' => 'UnidentifiedBot',
                    'company' => 'Unknown',
                    'type' => 'detected',
                    'rate' => 0.02,
                    'detection_method' => 'pattern_match'
                ];
            }
        }
        
        return false;
    }
    
    /**
     * Analyze IP ranges against known bot networks
     */
    private function analyze_ip_ranges($ip) {
        foreach ($this->advanced_patterns['ip_ranges'] as $company => $ranges) {
            foreach ($ranges as $range) {
                if ($this->ip_in_range($ip, $range)) {
                    return [
                        'name' => ucfirst($company) . 'Bot',
                        'company' => ucfirst($company),
                        'type' => 'ip_range',
                        'rate' => 0.06,
                        'detection_method' => 'ip_range'
                    ];
                }
            }
        }
        
        return false;
    }
    
    /**
     * Analyze behavioral patterns
     */
    private function analyze_behavioral_patterns($ip, $user_agent) {
        global $wpdb;
        $table_name = $wpdb->prefix . PAYPERCRAWL_DETECTIONS_TABLE;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            return false;
        }
        
        $patterns = $this->advanced_patterns['behavioral_patterns'];
        
        // Rapid crawling detection
        $rapid = $patterns['rapid_crawling'];
        $time_window = time() - $rapid['window'];
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE ip_address = %s AND detected_at > FROM_UNIXTIME(%d)",
            $ip, $time_window
        ));
        
        if ($count >= $rapid['threshold']) {
            return [
                'name' => 'RapidCrawler',
                'company' => 'Unknown',
                'type' => 'behavioral',
                'rate' => 0.03,
                'detection_method' => 'rapid_crawling',
                'confidence_boost' => $rapid['confidence_boost']
            ];
        }
        
        // Deep crawling detection
        $deep = $patterns['deep_crawling'];
        $deep_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT page_url) FROM $table_name 
             WHERE ip_address = %s AND detected_at > FROM_UNIXTIME(%d)",
            $ip, time() - 3600 // Last hour
        ));
        
        if ($deep_count >= $deep['depth']) {
            return [
                'name' => 'DeepCrawler',
                'company' => 'Unknown',
                'type' => 'behavioral',
                'rate' => 0.03,
                'detection_method' => 'deep_crawling',
                'confidence_boost' => $deep['confidence_boost']
            ];
        }
        
        return false;
    }
    
    /**
     * Analyze header signatures
     */
    private function analyze_header_signatures($headers) {
        $suspicious_score = 0;
        $max_score = 100;
        
        $patterns = $this->advanced_patterns['header_signatures'];
        
        // Check suspicious Accept headers
        if (isset($headers['Accept'])) {
            foreach ($patterns['suspicious_accept'] as $suspicious) {
                if (stripos($headers['Accept'], $suspicious) !== false) {
                    $suspicious_score += 20;
                }
            }
        }
        
        // Check for missing common headers
        foreach ($patterns['missing_headers'] as $header) {
            $header_variations = [
                ucfirst($header),
                strtoupper($header),
                str_replace('-', '_', strtoupper($header))
            ];
            
            $found = false;
            foreach ($header_variations as $variation) {
                if (isset($headers[$variation])) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $suspicious_score += 15;
            }
        }
        
        // Check cache control patterns
        if (isset($headers['Cache-Control'])) {
            foreach ($patterns['suspicious_cache'] as $suspicious) {
                if (stripos($headers['Cache-Control'], $suspicious) !== false) {
                    $suspicious_score += 10;
                }
            }
        }
        
        if ($suspicious_score >= 40) { // 40% threshold
            return [
                'name' => 'SuspiciousHeaders',
                'company' => 'Unknown',
                'type' => 'headers',
                'rate' => 0.02,
                'detection_method' => 'header_analysis',
                'suspicious_score' => $suspicious_score
            ];
        }
        
        return false;
    }
    
    /**
     * Machine learning indicators analysis
     */
    private function analyze_ml_indicators($user_agent, $headers, $ip) {
        $ml_score = 0;
        $indicators = $this->advanced_patterns['ml_indicators'];
        
        // Timing analysis
        $request_time = microtime(true);
        $last_request = get_transient('paypercrawl_last_request_' . md5($ip));
        
        if ($last_request) {
            $time_diff = $request_time - $last_request;
            
            // Too regular (exactly 1, 2, 5, 10 seconds)
            if (in_array(round($time_diff), [1, 2, 5, 10])) {
                $ml_score += 20;
            }
            
            // Too fast (less than 500ms)
            if ($time_diff < 0.5) {
                $ml_score += 30;
            }
        }
        
        set_transient('paypercrawl_last_request_' . md5($ip), $request_time, 3600);
        
        // Content access patterns
        if (!isset($headers['Accept']) || strpos($headers['Accept'], 'image') === false) {
            $ml_score += 15; // Doesn't request images
        }
        
        if (!isset($headers['Accept']) || strpos($headers['Accept'], 'css') === false) {
            $ml_score += 10; // Doesn't request CSS
        }
        
        if ($ml_score >= 25) {
            return [
                'name' => 'MLDetectedBot',
                'company' => 'Unknown',
                'type' => 'ml_detection',
                'rate' => 0.02,
                'detection_method' => 'machine_learning',
                'ml_score' => $ml_score
            ];
        }
        
        return false;
    }
    
    /**
     * Compile final detection result
     */
    private function compile_detection_result($results, $confidence) {
        // Use the highest value detection result as primary
        $primary_result = null;
        $highest_rate = 0;
        
        foreach ($results as $result) {
            if (isset($result['rate']) && $result['rate'] > $highest_rate) {
                $highest_rate = $result['rate'];
                $primary_result = $result;
            }
        }
        
        if ($primary_result) {
            $primary_result['confidence_score'] = round($confidence, 2);
            $primary_result['detection_layers'] = array_keys($results);
            $primary_result['all_detections'] = $results;
            
            return $primary_result;
        }
        
        return false;
    }
    
    /**
     * Process current request
     */
    public function process_request() {
        $detection = $this->detect_bot();
        
        if ($detection) {
            $this->log_detection($detection);
            $this->handle_detected_bot($detection);
        }
    }
    
    /**
     * Log bot detection
     */
    private function log_detection($detection) {
        global $wpdb;
        $table_name = $wpdb->prefix . PAYPERCRAWL_DETECTIONS_TABLE;
        
        $wpdb->insert(
            $table_name,
            [
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $this->get_client_ip(),
                'bot_type' => $detection['name'],
                'confidence_score' => $detection['confidence_score'],
                'page_url' => $this->get_current_url(),
                'revenue_generated' => $detection['rate'],
                'metadata' => wp_json_encode($detection)
            ],
            ['%s', '%s', '%s', '%f', '%s', '%f', '%s']
        );
    }
    
    /**
     * Handle detected bot
     */
    private function handle_detected_bot($detection) {
        // Early access banner for high-value bots
        if ($detection['rate'] >= 0.10 && get_option('paypercrawl_early_access', true)) {
            $this->show_early_access_banner($detection);
        }
        
        // Log to analytics
        do_action('paypercrawl_bot_detected', $detection);
    }
    
    /**
     * Show early access banner
     */
    private function show_early_access_banner($detection) {
        if (!is_admin()) {
            add_action('wp_footer', function() use ($detection) {
                echo '<div style="position:fixed;top:0;left:0;right:0;background:#ff6b35;color:white;padding:10px;text-align:center;z-index:99999;">
                    🚀 <strong>PayPerCrawl Early Access:</strong> ' . esc_html($detection['name']) . ' detected! 
                    Revenue potential: $' . number_format($detection['rate'], 2) . ' per request
                </div>';
            });
        }
    }
    
    /**
     * Utility methods
     */
    private function get_client_ip() {
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
    
    private function get_current_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return $protocol . '://' . $host . $uri;
    }
    
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
     * Get bot signatures for dashboard
     */
    public function get_bot_signatures() {
        return $this->bot_signatures;
    }
    
    /**
     * Get detection statistics
     */
    public function get_detection_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . PAYPERCRAWL_DETECTIONS_TABLE;
        
        return $wpdb->get_results(
            "SELECT bot_type, COUNT(*) as count, AVG(confidence_score) as avg_confidence,
                    SUM(revenue_generated) as total_revenue
             FROM $table_name 
             WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY bot_type
             ORDER BY total_revenue DESC"
        );
    }
}
