<?php
/**
 * Enterprise Bot Detector with AI/ML Capabilities
 * 
 * @package PayPerCrawl
 * @subpackage BotDetection
 * @version 4.0.0
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
 * - Behavioral analysis
 * - Real-time signature updates
 * - Cloudflare integration
 * - IP range verification
 * - Header fingerprinting
 * - Request pattern analysis
 * 
 * @since 4.0.0
 */
class PayPerCrawl_Bot_Detector_Enterprise {
    
    /**
     * Bot signatures database
     * @var array
     */
    private $signatures = [];
    
    /**
     * Detection confidence threshold
     * @var float
     */
    private $confidence_threshold = 0.7;
    
    /**
     * Machine learning model data
     * @var array
     */
    private $ml_model = [];
    
    /**
     * Request analysis cache
     * @var array
     */
    private $request_cache = [];
    
    /**
     * Cloudflare data
     * @var array
     */
    private $cloudflare_data = [];
    
    /**
     * Current request data
     * @var array
     */
    private $current_request = [];
    
    /**
     * Initialize the bot detector
     * 
     * @param array $signatures Bot signatures configuration
     */
    public function init($signatures = []) {
        $this->signatures = $signatures;
        $this->load_ml_model();
        $this->capture_request_data();
        $this->extract_cloudflare_data();
    }
    
    /**
     * Main bot detection method
     * 
     * @return array|false Detection result or false if no bot detected
     */
    public function detect() {
        try {
            // Multi-layer detection approach
            $detections = [];
            
            // Layer 1: User Agent Analysis
            $ua_detection = $this->detect_by_user_agent();
            if ($ua_detection) {
                $detections[] = $ua_detection;
            }
            
            // Layer 2: Header Fingerprinting
            $header_detection = $this->detect_by_headers();
            if ($header_detection) {
                $detections[] = $header_detection;
            }
            
            // Layer 3: IP Range Verification
            $ip_detection = $this->detect_by_ip_range();
            if ($ip_detection) {
                $detections[] = $ip_detection;
            }
            
            // Layer 4: Behavioral Pattern Analysis
            $behavior_detection = $this->detect_by_behavior();
            if ($behavior_detection) {
                $detections[] = $behavior_detection;
            }
            
            // Layer 5: Machine Learning Classification
            if (PAYPERCRAWL_ML_DETECTION) {
                $ml_detection = $this->detect_by_ml();
                if ($ml_detection) {
                    $detections[] = $ml_detection;
                }
            }
            
            // Layer 6: Cloudflare Bot Score
            if (!empty($this->cloudflare_data)) {
                $cf_detection = $this->detect_by_cloudflare();
                if ($cf_detection) {
                    $detections[] = $cf_detection;
                }
            }
            
            // Combine detection results
            if (!empty($detections)) {
                return $this->combine_detections($detections);
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->log_error('Detection failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Capture current request data
     */
    private function capture_request_data() {
        $this->current_request = [
            'ip' => $this->get_real_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'url' => $this->get_current_url(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'headers' => $this->get_all_headers(),
            'timestamp' => microtime(true),
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'accept' => $_SERVER['HTTP_ACCEPT'] ?? '',
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            'connection' => $_SERVER['HTTP_CONNECTION'] ?? '',
            'host' => $_SERVER['HTTP_HOST'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
        ];
    }
    
    /**
     * Extract Cloudflare data from headers
     */
    private function extract_cloudflare_data() {
        $this->cloudflare_data = [
            'cf_ray' => $_SERVER['HTTP_CF_RAY'] ?? null,
            'cf_ipcountry' => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null,
            'cf_visitor' => $_SERVER['HTTP_CF_VISITOR'] ?? null,
            'cf_connecting_ip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            'cf_bot_score' => $_SERVER['HTTP_CF_BOT_MANAGEMENT_SCORE'] ?? null,
            'cf_threat_score' => $_SERVER['HTTP_CF_THREAT_SCORE'] ?? null,
            'cf_is_bot' => $_SERVER['HTTP_CF_IS_BOT'] ?? null,
            'cf_verified_bot' => $_SERVER['HTTP_CF_VERIFIED_BOT'] ?? null,
        ];
        
        // Parse CF-Visitor JSON if present
        if (!empty($this->cloudflare_data['cf_visitor'])) {
            $visitor_data = json_decode($this->cloudflare_data['cf_visitor'], true);
            if ($visitor_data) {
                $this->cloudflare_data['cf_visitor_parsed'] = $visitor_data;
            }
        }
    }
    
    /**
     * Load machine learning model
     */
    private function load_ml_model() {
        // Load pre-trained ML model data
        $this->ml_model = get_option('paypercrawl_ml_model', [
            'features' => [
                'user_agent_length' => ['weight' => 0.15, 'threshold' => 50],
                'header_count' => ['weight' => 0.1, 'threshold' => 8],
                'request_frequency' => ['weight' => 0.25, 'threshold' => 5.0],
                'header_order_score' => ['weight' => 0.2, 'threshold' => 0.7],
                'content_type_preference' => ['weight' => 0.1, 'threshold' => 0.8],
                'javascript_support' => ['weight' => 0.2, 'threshold' => 0.5],
            ],
            'threshold' => 0.75,
            'version' => '1.0',
        ]);
    }
    
    /**
     * Detect bot by User Agent analysis
     * 
     * @return array|false Detection result
     */
    private function detect_by_user_agent() {
        $user_agent = $this->current_request['user_agent'];
        
        if (empty($user_agent)) {
            return [
                'method' => 'user_agent',
                'confidence' => 0.9,
                'bot_info' => [
                    'name' => 'Unknown',
                    'type' => 'suspicious',
                    'company' => 'Unknown',
                    'rate' => 0.01,
                    'priority' => 'medium',
                ],
                'evidence' => 'Empty user agent',
            ];
        }
        
        // Check against known bot signatures
        foreach ($this->signatures as $bot_name => $bot_config) {
            if (empty($bot_config['patterns'])) continue;
            
            foreach ($bot_config['patterns'] as $pattern) {
                if (stripos($user_agent, $pattern) !== false) {
                    return [
                        'method' => 'user_agent',
                        'confidence' => 0.95,
                        'bot_info' => [
                            'name' => $bot_name,
                            'type' => $bot_config['type'] ?? 'standard',
                            'company' => $bot_config['company'] ?? 'Unknown',
                            'rate' => $bot_config['rate'] ?? 0.01,
                            'priority' => $bot_config['priority'] ?? 'medium',
                        ],
                        'evidence' => "User agent contains: {$pattern}",
                    ];
                }
            }
            
            // Check regex patterns if defined
            if (!empty($bot_config['headers']['User-Agent'])) {
                $regex = $bot_config['headers']['User-Agent'];
                if (preg_match($regex, $user_agent)) {
                    return [
                        'method' => 'user_agent_regex',
                        'confidence' => 0.92,
                        'bot_info' => [
                            'name' => $bot_name,
                            'type' => $bot_config['type'] ?? 'standard',
                            'company' => $bot_config['company'] ?? 'Unknown',
                            'rate' => $bot_config['rate'] ?? 0.01,
                            'priority' => $bot_config['priority'] ?? 'medium',
                        ],
                        'evidence' => "User agent matches pattern: {$regex}",
                    ];
                }
            }
        }
        
        // Generic bot detection patterns
        $generic_patterns = [
            'bot', 'crawler', 'spider', 'scraper', 'wget', 'curl',
            'python', 'requests', 'httpx', 'aiohttp', 'urllib',
            'scrapy', 'beautifulsoup', 'selenium', 'phantomjs',
            'headless', 'automation', 'test', 'monitoring',
        ];
        
        $user_agent_lower = strtolower($user_agent);
        foreach ($generic_patterns as $pattern) {
            if (strpos($user_agent_lower, $pattern) !== false) {
                return [
                    'method' => 'user_agent_generic',
                    'confidence' => 0.8,
                    'bot_info' => [
                        'name' => 'Generic Bot',
                        'type' => 'generic',
                        'company' => 'Unknown',
                        'rate' => 0.005,
                        'priority' => 'low',
                    ],
                    'evidence' => "User agent contains bot indicator: {$pattern}",
                ];
            }
        }
        
        return false;
    }
    
    /**
     * Detect bot by header fingerprinting
     * 
     * @return array|false Detection result
     */
    private function detect_by_headers() {
        $headers = $this->current_request['headers'];
        
        // Suspicious header patterns
        $suspicious_indicators = [];
        
        // Missing common browser headers
        $required_headers = ['Accept', 'Accept-Language', 'Accept-Encoding'];
        $missing_headers = [];
        
        foreach ($required_headers as $header) {
            if (empty($headers[$header])) {
                $missing_headers[] = $header;
            }
        }
        
        if (count($missing_headers) >= 2) {
            $suspicious_indicators[] = 'Missing browser headers: ' . implode(', ', $missing_headers);
        }
        
        // Unusual header combinations
        if (!empty($headers['User-Agent']) && empty($headers['Accept'])) {
            $suspicious_indicators[] = 'User-Agent present but Accept header missing';
        }
        
        // Bot-specific headers
        $bot_headers = [
            'X-Forwarded-For', 'X-Real-IP', 'X-Forwarded-Proto',
            'X-Cluster-Client-IP', 'X-Original-Forwarded-For',
        ];
        
        $proxy_count = 0;
        foreach ($bot_headers as $header) {
            if (!empty($headers[$header])) {
                $proxy_count++;
            }
        }
        
        if ($proxy_count >= 3) {
            $suspicious_indicators[] = 'Multiple proxy headers detected';
        }
        
        // Automation headers
        $automation_headers = [
            'Chrome-Lighthouse', 'Selenium', 'PhantomJS', 'HeadlessChrome',
            'X-Requested-With', 'X-Automation', 'X-Test-Header',
        ];
        
        foreach ($automation_headers as $header) {
            if (!empty($headers[$header])) {
                $suspicious_indicators[] = "Automation header detected: {$header}";
            }
        }
        
        // Header order analysis (browsers send headers in predictable orders)
        $header_order_score = $this->analyze_header_order($headers);
        if ($header_order_score < 0.6) {
            $suspicious_indicators[] = 'Unusual header order pattern';
        }
        
        if (!empty($suspicious_indicators)) {
            $confidence = min(0.95, 0.5 + (count($suspicious_indicators) * 0.15));
            
            return [
                'method' => 'header_analysis',
                'confidence' => $confidence,
                'bot_info' => [
                    'name' => 'Header Analysis Bot',
                    'type' => 'suspicious',
                    'company' => 'Unknown',
                    'rate' => 0.01,
                    'priority' => 'medium',
                ],
                'evidence' => implode('; ', $suspicious_indicators),
            ];
        }
        
        return false;
    }
    
    /**
     * Detect bot by IP range verification
     * 
     * @return array|false Detection result
     */
    private function detect_by_ip_range() {
        $ip = $this->current_request['ip'];
        
        if (empty($ip)) {
            return false;
        }
        
        // Check against known bot IP ranges
        foreach ($this->signatures as $bot_name => $bot_config) {
            if (empty($bot_config['ip_ranges'])) continue;
            
            foreach ($bot_config['ip_ranges'] as $ip_range) {
                if ($this->ip_in_range($ip, $ip_range)) {
                    return [
                        'method' => 'ip_range',
                        'confidence' => 0.85,
                        'bot_info' => [
                            'name' => $bot_name,
                            'type' => $bot_config['type'] ?? 'standard',
                            'company' => $bot_config['company'] ?? 'Unknown',
                            'rate' => $bot_config['rate'] ?? 0.01,
                            'priority' => $bot_config['priority'] ?? 'medium',
                        ],
                        'evidence' => "IP {$ip} in range {$ip_range}",
                    ];
                }
            }
        }
        
        // Check against cloud provider ranges (often used by bots)
        $cloud_providers = [
            'AWS' => ['52.0.0.0/8', '54.0.0.0/8', '3.0.0.0/8'],
            'Google Cloud' => ['35.0.0.0/8', '34.0.0.0/8'],
            'Azure' => ['20.0.0.0/8', '40.76.0.0/14'],
            'DigitalOcean' => ['165.227.0.0/16', '167.71.0.0/16'],
        ];
        
        foreach ($cloud_providers as $provider => $ranges) {
            foreach ($ranges as $range) {
                if ($this->ip_in_range($ip, $range)) {
                    // Lower confidence for cloud IPs as they might be legitimate
                    return [
                        'method' => 'cloud_ip',
                        'confidence' => 0.3,
                        'bot_info' => [
                            'name' => 'Cloud Provider Bot',
                            'type' => 'cloud',
                            'company' => $provider,
                            'rate' => 0.002,
                            'priority' => 'low',
                        ],
                        'evidence' => "IP from {$provider} cloud range",
                    ];
                }
            }
        }
        
        return false;
    }
    
    /**
     * Detect bot by behavioral pattern analysis
     * 
     * @return array|false Detection result
     */
    private function detect_by_behavior() {
        global $wpdb;
        
        $ip = $this->current_request['ip'];
        $user_agent = $this->current_request['user_agent'];
        
        // Analyze recent requests from this IP
        $table_requests = $wpdb->prefix . 'paypercrawl_requests';
        
        // Record current request
        $wpdb->insert(
            $table_requests,
            [
                'ip_address' => $ip,
                'user_agent' => $user_agent,
                'url' => $this->current_request['url'],
                'method' => $this->current_request['method'],
                'headers' => json_encode($this->current_request['headers']),
                'request_time' => time(),
            ]
        );
        
        // Analyze patterns
        $recent_requests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_requests} WHERE ip_address = %s AND request_time > %d ORDER BY request_time DESC LIMIT 100",
            $ip,
            time() - 3600 // Last hour
        ));
        
        if (empty($recent_requests)) {
            return false;
        }
        
        $suspicious_behaviors = [];
        
        // High request frequency
        $request_count = count($recent_requests);
        if ($request_count > 50) {
            $suspicious_behaviors[] = "High request frequency: {$request_count} requests in last hour";
        }
        
        // Consistent timing patterns (typical of automated scripts)
        $intervals = [];
        for ($i = 1; $i < count($recent_requests); $i++) {
            $interval = $recent_requests[$i-1]->request_time - $recent_requests[$i]->request_time;
            $intervals[] = $interval;
        }
        
        if (count($intervals) > 5) {
            $avg_interval = array_sum($intervals) / count($intervals);
            $variance = $this->calculate_variance($intervals);
            
            // Low variance indicates automated timing
            if ($variance < 2 && $avg_interval < 10) {
                $suspicious_behaviors[] = "Consistent timing pattern (variance: {$variance})";
            }
        }
        
        // Sequential URL access patterns
        $urls = array_column($recent_requests, 'url');
        if ($this->detect_sequential_pattern($urls)) {
            $suspicious_behaviors[] = 'Sequential URL access pattern detected';
        }
        
        // No referrer patterns
        $no_referrer_count = 0;
        foreach ($recent_requests as $request) {
            $headers = json_decode($request->headers, true);
            if (empty($headers['Referer'])) {
                $no_referrer_count++;
            }
        }
        
        if ($no_referrer_count / $request_count > 0.8) {
            $suspicious_behaviors[] = 'High percentage of requests without referrer';
        }
        
        if (!empty($suspicious_behaviors)) {
            $confidence = min(0.9, 0.4 + (count($suspicious_behaviors) * 0.2));
            
            return [
                'method' => 'behavioral_analysis',
                'confidence' => $confidence,
                'bot_info' => [
                    'name' => 'Behavioral Pattern Bot',
                    'type' => 'behavioral',
                    'company' => 'Unknown',
                    'rate' => 0.01,
                    'priority' => 'medium',
                ],
                'evidence' => implode('; ', $suspicious_behaviors),
            ];
        }
        
        return false;
    }
    
    /**
     * Detect bot using machine learning classification
     * 
     * @return array|false Detection result
     */
    private function detect_by_ml() {
        $features = $this->extract_ml_features();
        $score = $this->calculate_ml_score($features);
        
        if ($score > $this->ml_model['threshold']) {
            return [
                'method' => 'machine_learning',
                'confidence' => $score,
                'bot_info' => [
                    'name' => 'ML Classified Bot',
                    'type' => 'ml_detected',
                    'company' => 'Unknown',
                    'rate' => 0.015,
                    'priority' => 'medium',
                ],
                'evidence' => "ML classification score: {$score}",
            ];
        }
        
        return false;
    }
    
    /**
     * Detect bot using Cloudflare bot score
     * 
     * @return array|false Detection result
     */
    private function detect_by_cloudflare() {
        $cf_data = $this->cloudflare_data;
        
        // Check if Cloudflare has already identified this as a bot
        if (!empty($cf_data['cf_is_bot']) && $cf_data['cf_is_bot'] === '1') {
            $is_verified = !empty($cf_data['cf_verified_bot']) && $cf_data['cf_verified_bot'] === '1';
            
            return [
                'method' => 'cloudflare',
                'confidence' => $is_verified ? 0.95 : 0.85,
                'bot_info' => [
                    'name' => $is_verified ? 'Verified Bot' : 'Cloudflare Detected Bot',
                    'type' => $is_verified ? 'verified' : 'detected',
                    'company' => 'Cloudflare',
                    'rate' => $is_verified ? 0.02 : 0.01,
                    'priority' => $is_verified ? 'high' : 'medium',
                ],
                'evidence' => 'Cloudflare bot detection',
            ];
        }
        
        // Check bot management score if available
        if (!empty($cf_data['cf_bot_score'])) {
            $bot_score = (int) $cf_data['cf_bot_score'];
            
            // Cloudflare bot scores: 1-30 = likely human, 31-65 = unclear, 66-99 = likely bot
            if ($bot_score >= 66) {
                return [
                    'method' => 'cloudflare_score',
                    'confidence' => min(0.95, $bot_score / 100),
                    'bot_info' => [
                        'name' => 'Cloudflare Scored Bot',
                        'type' => 'scored',
                        'company' => 'Cloudflare',
                        'rate' => 0.015,
                        'priority' => 'medium',
                    ],
                    'evidence' => "Cloudflare bot score: {$bot_score}",
                ];
            }
        }
        
        // Check threat score
        if (!empty($cf_data['cf_threat_score'])) {
            $threat_score = (int) $cf_data['cf_threat_score'];
            
            if ($threat_score > 50) {
                return [
                    'method' => 'cloudflare_threat',
                    'confidence' => 0.7,
                    'bot_info' => [
                        'name' => 'High Threat Score',
                        'type' => 'threat',
                        'company' => 'Cloudflare',
                        'rate' => 0.005,
                        'priority' => 'low',
                    ],
                    'evidence' => "Cloudflare threat score: {$threat_score}",
                ];
            }
        }
        
        return false;
    }
    
    /**
     * Combine multiple detection results
     * 
     * @param array $detections Array of detection results
     * @return array Combined detection result
     */
    private function combine_detections($detections) {
        // Sort by confidence descending
        usort($detections, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        $primary_detection = $detections[0];
        $combined_confidence = $primary_detection['confidence'];
        
        // Boost confidence if multiple methods agree
        if (count($detections) > 1) {
            $confidence_boost = min(0.1, (count($detections) - 1) * 0.05);
            $combined_confidence = min(0.99, $combined_confidence + $confidence_boost);
        }
        
        // Combine evidence
        $all_evidence = array_column($detections, 'evidence');
        $all_methods = array_column($detections, 'method');
        
        return [
            'bot_info' => $primary_detection['bot_info'],
            'request_data' => $this->current_request,
            'method' => implode(', ', array_unique($all_methods)),
            'confidence' => $combined_confidence,
            'evidence' => implode('; ', $all_evidence),
            'detections' => $detections,
            'cloudflare_data' => $this->cloudflare_data,
        ];
    }
    
    /**
     * Extract features for machine learning
     * 
     * @return array Feature vector
     */
    private function extract_ml_features() {
        $user_agent = $this->current_request['user_agent'];
        $headers = $this->current_request['headers'];
        
        return [
            'user_agent_length' => strlen($user_agent),
            'header_count' => count($headers),
            'request_frequency' => $this->get_request_frequency(),
            'header_order_score' => $this->analyze_header_order($headers),
            'content_type_preference' => $this->analyze_content_type_preference(),
            'javascript_support' => $this->detect_javascript_support(),
        ];
    }
    
    /**
     * Calculate ML classification score
     * 
     * @param array $features Feature vector
     * @return float Classification score
     */
    private function calculate_ml_score($features) {
        $score = 0;
        $total_weight = 0;
        
        foreach ($this->ml_model['features'] as $feature_name => $config) {
            if (!isset($features[$feature_name])) continue;
            
            $feature_value = $features[$feature_name];
            $threshold = $config['threshold'];
            $weight = $config['weight'];
            
            // Normalize feature value based on threshold
            $normalized = min(1.0, $feature_value / $threshold);
            $score += $normalized * $weight;
            $total_weight += $weight;
        }
        
        return $total_weight > 0 ? $score / $total_weight : 0;
    }
    
    /**
     * Analyze header order patterns
     * 
     * @param array $headers Request headers
     * @return float Order score (0-1)
     */
    private function analyze_header_order($headers) {
        // Common browser header order patterns
        $browser_order = [
            'Host', 'Connection', 'Accept', 'User-Agent', 'Accept-Language',
            'Accept-Encoding', 'Referer', 'Cookie', 'Cache-Control'
        ];
        
        $header_names = array_keys($headers);
        $matches = 0;
        $total = min(count($header_names), count($browser_order));
        
        for ($i = 0; $i < $total; $i++) {
            if (isset($header_names[$i]) && 
                isset($browser_order[$i]) && 
                strcasecmp($header_names[$i], $browser_order[$i]) === 0) {
                $matches++;
            }
        }
        
        return $total > 0 ? $matches / $total : 0;
    }
    
    /**
     * Analyze content type preferences
     * 
     * @return float Preference score
     */
    private function analyze_content_type_preference() {
        $accept = $this->current_request['accept'];
        
        if (empty($accept)) {
            return 0;
        }
        
        // Browsers typically prefer HTML
        if (strpos($accept, 'text/html') !== false) {
            return 0.9;
        }
        
        // APIs typically prefer JSON
        if (strpos($accept, 'application/json') !== false && 
            strpos($accept, 'text/html') === false) {
            return 0.1;
        }
        
        return 0.5;
    }
    
    /**
     * Detect JavaScript support indicators
     * 
     * @return float JavaScript support score
     */
    private function detect_javascript_support() {
        // This is simplified - in a real implementation, you'd analyze
        // various indicators of JavaScript execution capability
        $user_agent = $this->current_request['user_agent'];
        
        // Modern browsers typically support JavaScript
        $modern_browsers = ['Chrome/', 'Firefox/', 'Safari/', 'Edge/'];
        
        foreach ($modern_browsers as $browser) {
            if (strpos($user_agent, $browser) !== false) {
                return 0.9;
            }
        }
        
        // Headless browsers often don't execute JavaScript properly
        $headless_indicators = ['HeadlessChrome', 'PhantomJS', 'headless'];
        
        foreach ($headless_indicators as $indicator) {
            if (stripos($user_agent, $indicator) !== false) {
                return 0.2;
            }
        }
        
        return 0.5;
    }
    
    /**
     * Get request frequency for current IP
     * 
     * @return float Requests per minute
     */
    private function get_request_frequency() {
        global $wpdb;
        
        $ip = $this->current_request['ip'];
        $table_requests = $wpdb->prefix . 'paypercrawl_requests';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_requests} WHERE ip_address = %s AND request_time > %d",
            $ip,
            time() - 300 // Last 5 minutes
        ));
        
        return $count ? $count / 5 : 0; // Requests per minute
    }
    
    /**
     * Detect sequential URL access patterns
     * 
     * @param array $urls Array of URLs
     * @return bool True if sequential pattern detected
     */
    private function detect_sequential_pattern($urls) {
        if (count($urls) < 5) {
            return false;
        }
        
        // Look for numeric sequences in URLs
        $numeric_sequences = 0;
        for ($i = 1; $i < count($urls); $i++) {
            $prev_numbers = $this->extract_numbers($urls[$i-1]);
            $curr_numbers = $this->extract_numbers($urls[$i]);
            
            if (!empty($prev_numbers) && !empty($curr_numbers)) {
                $prev_num = end($prev_numbers);
                $curr_num = end($curr_numbers);
                
                if (is_numeric($prev_num) && is_numeric($curr_num) && 
                    ($curr_num == $prev_num + 1 || $curr_num == $prev_num - 1)) {
                    $numeric_sequences++;
                }
            }
        }
        
        // If more than 60% of transitions show sequential pattern
        return ($numeric_sequences / (count($urls) - 1)) > 0.6;
    }
    
    /**
     * Extract numbers from URL
     * 
     * @param string $url URL to analyze
     * @return array Array of numbers found
     */
    private function extract_numbers($url) {
        preg_match_all('/\d+/', $url, $matches);
        return $matches[0];
    }
    
    /**
     * Calculate variance of array
     * 
     * @param array $values Array of values
     * @return float Variance
     */
    private function calculate_variance($values) {
        if (count($values) < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / count($values);
        $squared_diffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        return array_sum($squared_diffs) / count($values);
    }
    
    /**
     * Check if IP is in range
     * 
     * @param string $ip IP address to check
     * @param string $range IP range in CIDR notation
     * @return bool True if IP is in range
     */
    private function ip_in_range($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $mask) = explode('/', $range);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->ipv4_in_range($ip, $subnet, $mask);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->ipv6_in_range($ip, $subnet, $mask);
        }
        
        return false;
    }
    
    /**
     * Check if IPv4 is in range
     */
    private function ipv4_in_range($ip, $subnet, $mask) {
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - $mask);
        
        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }
    
    /**
     * Check if IPv6 is in range
     */
    private function ipv6_in_range($ip, $subnet, $mask) {
        $ip_bin = inet_pton($ip);
        $subnet_bin = inet_pton($subnet);
        
        if ($ip_bin === false || $subnet_bin === false) {
            return false;
        }
        
        $mask_bytes = $mask / 8;
        $mask_bits = $mask % 8;
        
        // Compare full bytes
        if ($mask_bytes > 0) {
            if (substr($ip_bin, 0, $mask_bytes) !== substr($subnet_bin, 0, $mask_bytes)) {
                return false;
            }
        }
        
        // Compare partial byte if needed
        if ($mask_bits > 0) {
            $ip_byte = ord($ip_bin[$mask_bytes]);
            $subnet_byte = ord($subnet_bin[$mask_bytes]);
            $byte_mask = 0xFF << (8 - $mask_bits);
            
            return ($ip_byte & $byte_mask) === ($subnet_byte & $byte_mask);
        }
        
        return true;
    }
    
    /**
     * Get real IP address considering proxies and Cloudflare
     * 
     * @return string IP address
     */
    private function get_real_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Default
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get current URL
     * 
     * @return string Current URL
     */
    private function get_current_url() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                   $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
        
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Get all request headers
     * 
     * @return array Headers array
     */
    private function get_all_headers() {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $header_name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($name, 5)))));
                $headers[$header_name] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Log error message
     * 
     * @param string $message Error message
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PayPerCrawl Bot Detector] ERROR: ' . $message);
        }
    }
    
    /**
     * Get detection statistics
     * 
     * @return array Detection statistics
     */
    public function get_detection_stats() {
        global $wpdb;
        
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        return [
            'total_detections' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_detections}"),
            'today_detections' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_detections} WHERE DATE(detected_at) = %s",
                current_time('Y-m-d')
            )),
            'top_bots' => $wpdb->get_results(
                "SELECT bot_name, COUNT(*) as count FROM {$table_detections} 
                WHERE detected_at > DATE_SUB(NOW(), INTERVAL 7 DAY) 
                GROUP BY bot_name ORDER BY count DESC LIMIT 10"
            ),
            'detection_methods' => $wpdb->get_results(
                "SELECT detection_method, COUNT(*) as count FROM {$table_detections}
                WHERE detected_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY detection_method ORDER BY count DESC"
            ),
        ];
    }
    
    /**
     * Update bot signatures
     * 
     * @param array $new_signatures New bot signatures
     */
    public function update_signatures($new_signatures) {
        $this->signatures = $new_signatures;
        update_option('paypercrawl_bot_signatures', $new_signatures);
    }
    
    /**
     * Train ML model with new data
     * 
     * @param array $training_data Training data
     */
    public function train_ml_model($training_data) {
        // This would implement actual ML training
        // For now, we'll just update the model parameters
        
        $this->ml_model['last_trained'] = current_time('mysql');
        $this->ml_model['training_samples'] = count($training_data);
        
        update_option('paypercrawl_ml_model', $this->ml_model);
    }
}

// End of file
