<?php
/**
 * Bot Detection Engine
 * 
 * This class handles the core bot detection logic and monetization decisions
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Bot_Detector {
    
    private $ml_detector;
    private $payment_handler;
    private $logger;
    private $config;
    
    // Legacy bot list for backward compatibility
    private $known_ai_bots = array(
        'gptbot' => array('company' => 'OpenAI', 'rate' => 0.002, 'confidence' => 95),
        'chatgpt-user' => array('company' => 'OpenAI', 'rate' => 0.002, 'confidence' => 95),
        'anthropic-ai' => array('company' => 'Anthropic', 'rate' => 0.0015, 'confidence' => 95),
        'claude-web' => array('company' => 'Anthropic', 'rate' => 0.0015, 'confidence' => 95),
        'bard' => array('company' => 'Google', 'rate' => 0.001, 'confidence' => 90),
        'palm' => array('company' => 'Google', 'rate' => 0.001, 'confidence' => 90),
        'google-extended' => array('company' => 'Google', 'rate' => 0.001, 'confidence' => 90),
        'googlebot' => array('company' => 'Google', 'rate' => 0.0005, 'confidence' => 85),
        'ccbot' => array('company' => 'Common Crawl', 'rate' => 0.001, 'confidence' => 90),
        'facebookbot' => array('company' => 'Meta', 'rate' => 0.0008, 'confidence' => 88),
        'bingbot' => array('company' => 'Microsoft', 'rate' => 0.0007, 'confidence' => 87)
    );
    
    public function __construct() {
        // Initialize dependencies
        if (class_exists('CrawlGuard_ML_Bot_Detector')) {
            $this->ml_detector = new CrawlGuard_ML_Bot_Detector();
        }
        
        if (class_exists('CrawlGuard_Payment_Handler')) {
            $this->payment_handler = new CrawlGuard_Payment_Handler();
        }
        
        if (class_exists('CrawlGuard_Error_Logger')) {
            $this->logger = new CrawlGuard_Error_Logger();
        }
        
        if (class_exists('CrawlGuard_Config')) {
            $this->config = CrawlGuard_Config::get_instance();
        }
        
        // Run detection on every page load (non-admin)
        add_action('wp', array($this, 'process_request'));
        
        // Add AJAX handlers
        add_action('wp_ajax_crawlguard_get_detections', array($this, 'ajax_get_detections'));
        add_action('wp_ajax_nopriv_crawlguard_get_detections', array($this, 'ajax_get_detections'));
    }
    
    public function process_request() {
        // Skip admin pages and AJAX requests
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $this->get_client_ip();
        
        if (empty($user_agent)) {
            return;
        }
        
        $detection = $this->analyze_request($user_agent, $ip);
        
        if ($detection['is_bot']) {
            $this->log_detection($detection);
            $this->handle_bot_request($detection);
        }
    }
    
    private function analyze_request($user_agent, $ip) {
        $result = array(
            'is_bot' => false,
            'bot_type' => 'unknown',
            'company' => 'Unknown',
            'confidence' => 0,
            'rate' => 0,
            'user_agent' => $user_agent,
            'ip' => $ip,
            'url' => $_SERVER['REQUEST_URI'] ?? '/',
            'timestamp' => current_time('timestamp')
        );
        
        // Check against known AI bots
        $user_agent_lower = strtolower($user_agent);
        foreach ($this->known_ai_bots as $bot_name => $bot_info) {
            if (strpos($user_agent_lower, strtolower($bot_name)) !== false) {
                $result['is_bot'] = true;
                $result['bot_type'] = $bot_name;
                $result['company'] = $bot_info['company'];
                $result['confidence'] = $bot_info['confidence'];
                $result['rate'] = $bot_info['rate'];
                break;
            }
        }
        
        // Additional heuristic detection
        if (!$result['is_bot']) {
            $bot_indicators = array('bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python');
            foreach ($bot_indicators as $indicator) {
                if (strpos($user_agent_lower, $indicator) !== false) {
                    $result['is_bot'] = true;
                    $result['bot_type'] = 'generic_bot';
                    $result['company'] = 'Unknown';
                    $result['confidence'] = 75;
                    $result['rate'] = 0.0005;
                    break;
                }
            }
        }
        
        return $result;
    }
    
    private function log_detection($detection) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'timestamp' => current_time('mysql'),
                'ip_address' => $detection['ip'],
                'user_agent' => $detection['user_agent'],
                'bot_detected' => 1,
                'bot_type' => $detection['bot_type'],
                'action_taken' => 'blocked',
                'revenue' => $detection['rate']
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%f')
        );
        
        if ($result === false) {
            error_log('CrawlGuard: Failed to log detection - ' . $wpdb->last_error);
        }
    }
    
    private function handle_bot_request($detection) {
        // Process monetization if payment handler is available
        if ($this->payment_handler) {
            $payment_result = $this->payment_handler->process_bot_monetization(array(
                'bot_type' => $detection['bot_type'],
                'ip_address' => $detection['ip'],
                'user_agent' => $detection['user_agent'],
                'page_url' => $detection['url'],
                'confidence' => $detection['confidence'],
                'company' => $detection['company']
            ));
            
            if ($payment_result['success']) {
                $detection['revenue'] = $payment_result['revenue'];
                $detection['transaction_id'] = $payment_result['transaction_id'] ?? null;
                
                if ($this->logger) {
                    $this->logger->info('Bot monetization processed', array(
                        'bot_type' => $detection['bot_type'],
                        'revenue' => $payment_result['revenue'],
                        'payment_processed' => $payment_result['payment_processed'] ?? false
                    ));
                }
            }
        }
        
        // Send to API for additional processing
        if (class_exists('CrawlGuard_API_Client')) {
            $api_client = new CrawlGuard_API_Client();
            $api_result = $api_client->send_bot_detection($detection);
            
            if (!$api_result['success'] && $this->logger) {
                $this->logger->warning('API notification failed', array(
                    'error' => $api_result['error'] ?? 'Unknown error'
                ));
            }
        }
        
        // Update daily stats
        $this->update_daily_stats($detection);
        
        // Trigger action for other plugins
        do_action('crawlguard_bot_detected', $detection);
        
        // Send real-time notification if enabled
        $this->send_realtime_notification($detection);
    }
    
    /**
     * Update daily statistics
     */
    private function update_daily_stats($detection) {
        $today = date('Y-m-d');
        $stats = get_option('crawlguard_daily_stats', array());
        
        if (!isset($stats[$today])) {
            $stats[$today] = array(
                'requests' => 0, 
                'revenue' => 0,
                'bots_detected' => 0,
                'companies' => array()
            );
        }
        
        $stats[$today]['requests']++;
        $stats[$today]['bots_detected']++;
        $stats[$today]['revenue'] += $detection['revenue'] ?? $detection['rate'];
        
        // Track companies
        $company = $detection['company'] ?? 'Unknown';
        if (!isset($stats[$today]['companies'][$company])) {
            $stats[$today]['companies'][$company] = 0;
        }
        $stats[$today]['companies'][$company]++;
        
        update_option('crawlguard_daily_stats', $stats);
    }
    
    /**
     * Send real-time notification for dashboard updates
     */
    private function send_realtime_notification($detection) {
        // Set transient for real-time dashboard updates
        $notifications = get_transient('crawlguard_realtime_notifications') ?: array();
        
        $notifications[] = array(
            'type' => 'bot_detected',
            'bot_type' => $detection['bot_type'],
            'company' => $detection['company'],
            'revenue' => $detection['revenue'] ?? $detection['rate'],
            'timestamp' => current_time('c'),
            'confidence' => $detection['confidence']
        );
        
        // Keep only last 10 notifications
        $notifications = array_slice($notifications, -10);
        
        set_transient('crawlguard_realtime_notifications', $notifications, 300); // 5 minutes
    }
    
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public function ajax_get_detections() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        $results = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE bot_detected = 1 ORDER BY timestamp DESC LIMIT 10",
            ARRAY_A
        );
        
        wp_send_json_success($results);
    }
    
    public function get_recent_detections($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE bot_detected = 1 ORDER BY timestamp DESC LIMIT %d", $limit),
            ARRAY_A
        );
    }
    
    public function get_detection_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        // Get today's stats
        $today_stats = $wpdb->get_row(
            "SELECT COUNT(*) as total, SUM(revenue) as revenue FROM $table_name WHERE DATE(timestamp) = CURDATE() AND bot_detected = 1",
            ARRAY_A
        );
        
        // Get bot type breakdown
        $bot_types = $wpdb->get_results(
            "SELECT bot_type, COUNT(*) as count FROM $table_name WHERE bot_detected = 1 GROUP BY bot_type ORDER BY count DESC LIMIT 10",
            ARRAY_A
        );
        
        return array(
            'today_total' => $today_stats['total'] ?? 0,
            'today_revenue' => $today_stats['revenue'] ?? 0,
            'bot_types' => $bot_types
        );
    }
        
        // Other major AI companies
        'cohere-ai' => array('company' => 'Cohere', 'rate' => 0.0012, 'confidence' => 85),
        'ai2bot' => array('company' => 'Allen Institute', 'rate' => 0.001, 'confidence' => 80),
        'facebookexternalhit' => array('company' => 'Meta', 'rate' => 0.001, 'confidence' => 85),
        'meta-externalagent' => array('company' => 'Meta', 'rate' => 0.001, 'confidence' => 85),
        'bytespider' => array('company' => 'ByteDance', 'rate' => 0.001, 'confidence' => 85),
        'perplexitybot' => array('company' => 'Perplexity', 'rate' => 0.0015, 'confidence' => 90),
        'youbot' => array('company' => 'You.com', 'rate' => 0.001, 'confidence' => 85),
        'phindbot' => array('company' => 'Phind', 'rate' => 0.001, 'confidence' => 80),
        
        // Search engines with AI features
        'bingbot' => array('company' => 'Microsoft', 'rate' => 0.0012, 'confidence' => 85),
        'slurp' => array('company' => 'Yahoo', 'rate' => 0.001, 'confidence' => 80),
        'duckduckbot' => array('company' => 'DuckDuckGo', 'rate' => 0.001, 'confidence' => 75),
        'applebot' => array('company' => 'Apple', 'rate' => 0.001, 'confidence' => 80),
        'amazonbot' => array('company' => 'Amazon', 'rate' => 0.001, 'confidence' => 80)
    );
    
    private $suspicious_patterns = array(
        '/python-requests/',
        '/scrapy/',
        '/selenium/',
        '/headless/',
        '/crawler/',
        '/scraper/',
        '/bot.*ai/i',
        '/ai.*bot/i',
        '/gpt/i',
        '/llm/i',
        '/language.*model/i'
    );
    
    public function process_request() {
        // Skip admin and AJAX requests
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        $user_agent = $this->get_user_agent();
        $ip_address = $this->get_client_ip();
        
        // Detect if this is a bot
        $bot_info = $this->detect_bot($user_agent, $ip_address);
        
        if ($bot_info['is_bot']) {
            $this->handle_bot_request($bot_info, $user_agent, $ip_address);
        }
        
        // Log the request for analytics
        $this->log_request($user_agent, $ip_address, $bot_info);
    }
    
    private function detect_bot($user_agent, $ip_address) {
        $bot_info = array(
            'is_bot' => false,
            'bot_type' => null,
            'bot_name' => null,
            'confidence' => 0,
            'is_ai_bot' => false
        );
        
        // Check against known AI bots
        foreach ($this->known_ai_bots as $bot_signature => $bot_data) {
            if (stripos($user_agent, $bot_signature) !== false) {
                $bot_info['is_bot'] = true;
                $bot_info['is_ai_bot'] = true;
                $bot_info['bot_type'] = $bot_signature;
                $bot_info['bot_name'] = $bot_data['company'];
                $bot_info['confidence'] = $bot_data['confidence'];
                $bot_info['suggested_rate'] = $bot_data['rate'];
                break;
            }
        }
        
        // Check suspicious patterns if not already detected
        if (!$bot_info['is_bot']) {
            foreach ($this->suspicious_patterns as $pattern) {
                if (preg_match($pattern, $user_agent)) {
                    $bot_info['is_bot'] = true;
                    $bot_info['is_ai_bot'] = true;
                    $bot_info['bot_type'] = 'suspicious_pattern';
                    $bot_info['bot_name'] = 'Unknown AI Bot';
                    $bot_info['confidence'] = 70;
                    break;
                }
            }
        }
        
        // Additional heuristics
        if (!$bot_info['is_bot']) {
            $bot_info = $this->apply_heuristics($user_agent, $ip_address, $bot_info);
        }
        
        return $bot_info;
    }
    
    private function apply_heuristics($user_agent, $ip_address, $bot_info) {
        $suspicious_score = 0;
        
        // Check for missing common browser headers
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $suspicious_score += 20;
        }
        
        if (empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $suspicious_score += 15;
        }
        
        // Check user agent length and structure
        if (strlen($user_agent) < 20 || strlen($user_agent) > 500) {
            $suspicious_score += 25;
        }
        
        // Check for common bot indicators
        $bot_keywords = array('bot', 'crawler', 'spider', 'scraper', 'fetch', 'http', 'client', 'agent');
        foreach ($bot_keywords as $keyword) {
            if (stripos($user_agent, $keyword) !== false) {
                $suspicious_score += 10;
            }
        }
        
        // If suspicious score is high enough, flag as potential AI bot
        if ($suspicious_score >= 40) {
            $bot_info['is_bot'] = true;
            $bot_info['is_ai_bot'] = true;
            $bot_info['bot_type'] = 'heuristic_detection';
            $bot_info['bot_name'] = 'Potential AI Bot';
            $bot_info['confidence'] = min($suspicious_score, 85);
        }
        
        return $bot_info;
    }
    
    private function handle_bot_request($bot_info, $user_agent, $ip_address) {
        $options = get_option('crawlguard_options');
        
        // If monetization is not enabled, just log and continue
        if (!$options['monetization_enabled']) {
            return;
        }
        
        // Check if this bot is in the allowed list
        if (in_array(strtolower($bot_info['bot_type']), $options['allowed_bots'])) {
            return;
        }
        
        // For AI bots, implement monetization logic
        if ($bot_info['is_ai_bot']) {
            $this->monetize_request($bot_info, $user_agent, $ip_address);
        }
    }
    
    private function monetize_request($bot_info, $user_agent, $ip_address) {
        // Send beacon to our backend API
        $api_client = new CrawlGuard_API_Client();
        
        $request_data = array(
            'site_url' => get_site_url(),
            'page_url' => $this->get_current_url(),
            'user_agent' => $user_agent,
            'ip_address' => $ip_address,
            'bot_info' => $bot_info,
            'timestamp' => current_time('mysql'),
            'content_type' => $this->get_content_type(),
            'content_length' => $this->estimate_content_value()
        );
        
        // Send to backend for processing
        $response = $api_client->send_monetization_request($request_data);
        
        // Handle the response
        if ($response && isset($response['action'])) {
            switch ($response['action']) {
                case 'block':
                    $this->block_request($response['message'] ?? 'Access denied');
                    break;
                case 'paywall':
                    $this->show_paywall($response);
                    break;
                case 'allow':
                    // Continue normally but log the revenue
                    $this->log_revenue($response['revenue'] ?? 0);
                    break;
            }
        }
    }
    
    private function block_request($message = 'Access denied') {
        status_header(402); // Payment Required
        wp_die($message, 'Payment Required', array('response' => 402));
    }
    
    private function show_paywall($response) {
        // Implement paywall logic
        $payment_url = $response['payment_url'] ?? '';
        $amount = $response['amount'] ?? 0;
        
        $paywall_html = $this->generate_paywall_html($payment_url, $amount);
        
        status_header(402);
        echo $paywall_html;
        exit;
    }
    
    private function generate_paywall_html($payment_url, $amount) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Content Access - CrawlGuard</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .paywall { max-width: 500px; margin: 0 auto; }
                .amount { font-size: 24px; color: #2271b1; font-weight: bold; }
                .pay-button { background: #2271b1; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
            </style>
        </head>
        <body>
            <div class="paywall">
                <h1>Content Access Required</h1>
                <p>This content requires payment for AI/bot access.</p>
                <div class="amount">$<?php echo number_format($amount, 4); ?></div>
                <p>Click below to pay and access this content:</p>
                <a href="<?php echo esc_url($payment_url); ?>" class="pay-button">Pay & Access Content</a>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function log_request($user_agent, $ip_address, $bot_info) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'bot_detected' => $bot_info['is_bot'] ? 1 : 0,
                'bot_type' => $bot_info['bot_type'],
                'action_taken' => 'logged'
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
    }
    
    private function log_revenue($amount) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        // Update the last log entry with revenue
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET revenue_generated = %f WHERE id = (SELECT MAX(id) FROM $table_name)",
            $amount
        ));
    }
    
    private function get_user_agent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    private function get_current_url() {
        return (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    private function get_content_type() {
        if (is_single()) return 'post';
        if (is_page()) return 'page';
        if (is_category()) return 'category';
        if (is_tag()) return 'tag';
        if (is_home()) return 'home';
        return 'other';
    }
    
    private function estimate_content_value() {
        // Simple content value estimation based on word count
        $content = get_the_content();
        $word_count = str_word_count(strip_tags($content));
        
        // Base value: $0.001 per 100 words
        return max(0.001, ($word_count / 100) * 0.001);
    }
}
