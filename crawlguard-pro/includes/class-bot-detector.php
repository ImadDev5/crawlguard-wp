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
    
    private $known_ai_bots = array(
        // OpenAI bots
        'gptbot' => array('company' => 'OpenAI', 'rate' => 0.002, 'confidence' => 95),
        'chatgpt-user' => array('company' => 'OpenAI', 'rate' => 0.002, 'confidence' => 95),
        
        // Anthropic bots
        'anthropic-ai' => array('company' => 'Anthropic', 'rate' => 0.0015, 'confidence' => 95),
        'claude-web' => array('company' => 'Anthropic', 'rate' => 0.0015, 'confidence' => 95),
        
        // Google bots
        'bard' => array('company' => 'Google', 'rate' => 0.001, 'confidence' => 90),
        'palm' => array('company' => 'Google', 'rate' => 0.001, 'confidence' => 90),
        'google-extended' => array('company' => 'Google', 'rate' => 0.001, 'confidence' => 90),
        'googlebot' => array('company' => 'Google', 'rate' => 0.0005, 'confidence' => 85),
        
        // Common Crawl
        'ccbot' => array('company' => 'Common Crawl', 'rate' => 0.001, 'confidence' => 90),
        
        // Meta/Facebook
        'facebookbot' => array('company' => 'Meta', 'rate' => 0.0008, 'confidence' => 88),
        
        // Microsoft
        'bingbot' => array('company' => 'Microsoft', 'rate' => 0.0007, 'confidence' => 87),
        
        // Other AI companies
        'perplexitybot' => array('company' => 'Perplexity', 'rate' => 0.0012, 'confidence' => 92),
        'youbot' => array('company' => 'You.com', 'rate' => 0.0010, 'confidence' => 90)
    );
    
    public function __construct() {
        add_action('wp', array($this, 'detect_and_monetize'));
        add_action('wp_head', array($this, 'inject_detection_script'));
    }
    
    public function detect_and_monetize() {
        if (is_admin()) {
            return;
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $this->get_client_ip();
        $detection_result = $this->analyze_request($user_agent, $ip);
        
        if ($detection_result['is_bot']) {
            $this->log_detection($detection_result);
            $this->process_monetization($detection_result);
        }
    }
    
    private function analyze_request($user_agent, $ip) {
        $result = array(
            'is_bot' => false,
            'bot_type' => null,
            'confidence' => 0,
            'rate' => 0,
            'company' => null,
            'timestamp' => current_time('timestamp'),
            'ip' => $ip,
            'user_agent' => $user_agent,
            'url' => $_SERVER['REQUEST_URI'] ?? '/'
        );
        
        // Check user agent against known bots
        foreach ($this->known_ai_bots as $bot_name => $bot_info) {
            if (stripos($user_agent, $bot_name) !== false) {
                $result['is_bot'] = true;
                $result['bot_type'] = $bot_name;
                $result['confidence'] = $bot_info['confidence'];
                $result['rate'] = $bot_info['rate'];
                $result['company'] = $bot_info['company'];
                break;
            }
        }
        
        // Additional heuristic checks
        if (!$result['is_bot']) {
            $result = $this->heuristic_detection($user_agent, $ip, $result);
        }
        
        return $result;
    }
    
    private function heuristic_detection($user_agent, $ip, $result) {
        $bot_indicators = array(
            'bot', 'crawler', 'spider', 'scraper', 'agent', 'robot',
            'curl', 'wget', 'python', 'requests', 'urllib',
            'httpclient', 'okhttp', 'go-http-client'
        );
        
        $ua_lower = strtolower($user_agent);
        $score = 0;
        
        foreach ($bot_indicators as $indicator) {
            if (strpos($ua_lower, $indicator) !== false) {
                $score += 10;
            }
        }
        
        // Check for missing common browser headers
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $score += 15;
        if (empty($_SERVER['HTTP_ACCEPT_ENCODING'])) $score += 10;
        if (empty($_SERVER['HTTP_ACCEPT'])) $score += 20;
        
        // Check for suspicious patterns
        if (strlen($user_agent) < 20) $score += 25;
        if (preg_match('/\b\d+\.\d+\b/', $user_agent)) $score += 10; // Version numbers
        
        if ($score >= 30) {
            $result['is_bot'] = true;
            $result['bot_type'] = 'unknown_bot';
            $result['confidence'] = min(95, $score);
            $result['rate'] = 0.0005; // Lower rate for unknown bots
            $result['company'] = 'Unknown';
        }
        
        return $result;
    }
    
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_CLIENT_IP',            // Proxy
            'REMOTE_ADDR'                // Standard
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
    
    private function log_detection($detection) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_detections';
        
        $wpdb->insert(
            $table_name,
            array(
                'bot_type' => $detection['bot_type'],
                'company' => $detection['company'],
                'confidence' => $detection['confidence'],
                'rate' => $detection['rate'],
                'ip_address' => $detection['ip'],
                'user_agent' => $detection['user_agent'],
                'request_url' => $detection['url'],
                'detected_at' => current_time('mysql'),
                'revenue' => $detection['rate']
            ),
            array('%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%f')
        );
    }
    
    private function process_monetization($detection) {
        $options = get_option('crawlguard_options', array());
        $monetization_enabled = $options['monetization_enabled'] ?? false;
        
        if (!$monetization_enabled) {
            return;
        }
        
        // Send to API for monetization processing
        $api_client = new CrawlGuard_API_Client();
        $api_client->send_monetization_request($detection);
        
        // Update revenue tracking
        $this->update_revenue_stats($detection['rate']);
    }
    
    private function update_revenue_stats($amount) {
        $today = date('Y-m-d');
        $revenue_data = get_option('crawlguard_revenue_' . $today, array(
            'date' => $today,
            'total' => 0,
            'requests' => 0
        ));
        
        $revenue_data['total'] += $amount;
        $revenue_data['requests'] += 1;
        
        update_option('crawlguard_revenue_' . $today, $revenue_data);
    }
    
    public function inject_detection_script() {
        if (is_admin()) {
            return;
        }
        
        ?>
        <script>
        // CrawlGuard Client-Side Detection
        (function() {
            const detectionData = {
                userAgent: navigator.userAgent,
                screen: screen.width + 'x' + screen.height,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                language: navigator.language,
                webgl: !!window.WebGLRenderingContext,
                timestamp: Date.now()
            };
            
            // Send detection data to backend
            if (window.fetch) {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=crawlguard_client_detection&data=' + encodeURIComponent(JSON.stringify(detectionData))
                }).catch(() => {}); // Silent fail
            }
        })();
        </script>
        <?php
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
