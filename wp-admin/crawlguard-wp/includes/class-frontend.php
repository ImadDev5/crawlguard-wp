<?php

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Frontend {
    
    public function __construct() {
        add_action('wp_head', array($this, 'add_meta_tags'));
        add_action('wp_footer', array($this, 'add_tracking_script'));
        add_action('template_redirect', array($this, 'handle_bot_requests'));
    }
    
    public function add_meta_tags() {
        $options = get_option('crawlguard_options');
        
        if (isset($options['monetization_enabled']) && $options['monetization_enabled']) {
            echo '<meta name="crawlguard-monetization" content="enabled" />' . "\n";
        }
        
        echo '<meta name="crawlguard-version" content="' . esc_attr(CRAWLGUARD_VERSION) . '" />' . "\n";
    }
    
    public function add_tracking_script() {
        $options = get_option('crawlguard_options');
        
        if (!isset($options['monetization_enabled']) || !$options['monetization_enabled']) {
            return;
        }
        
        if (empty($options['api_key'])) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        (function() {
            var cg = {
                apiUrl: '<?php echo esc_js($options['api_url'] ?? 'https://api.creativeinteriorsstudio.com/v1'); ?>',
                apiKey: '<?php echo esc_js($options['api_key']); ?>',
                siteUrl: '<?php echo esc_js(home_url()); ?>',
                pageUrl: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: Date.now()
            };
            
            if (window.fetch) {
                fetch(cg.apiUrl + '/detect', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': cg.apiKey
                    },
                    body: JSON.stringify({
                        user_agent: cg.userAgent,
                        page_url: cg.pageUrl,
                        site_url: cg.siteUrl,
                        timestamp: cg.timestamp,
                        source: 'frontend'
                    })
                }).catch(function(error) {
                    console.debug('CrawlGuard tracking error:', error);
                });
            }
        })();
        </script>
        <?php
    }
    
    public function handle_bot_requests() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (empty($user_agent)) {
            return;
        }
        
        $bot_detector = new CrawlGuard_Bot_Detector();
        $detection_result = $this->quick_bot_check($user_agent);
        
        if ($detection_result['is_bot'] && $detection_result['confidence'] >= 90) {
            $this->serve_monetized_content($detection_result);
        }
    }
    
    private function quick_bot_check($user_agent) {
        $user_agent_lower = strtolower($user_agent);
        
        $known_ai_bots = array(
            'gptbot' => array('company' => 'OpenAI', 'confidence' => 95),
            'chatgpt-user' => array('company' => 'OpenAI', 'confidence' => 95),
            'anthropic-ai' => array('company' => 'Anthropic', 'confidence' => 95),
            'claude-web' => array('company' => 'Anthropic', 'confidence' => 95),
            'bard' => array('company' => 'Google', 'confidence' => 90),
            'google-extended' => array('company' => 'Google', 'confidence' => 90),
            'ccbot' => array('company' => 'Common Crawl', 'confidence' => 90)
        );
        
        foreach ($known_ai_bots as $bot_signature => $bot_data) {
            if (strpos($user_agent_lower, $bot_signature) !== false) {
                return array(
                    'is_bot' => true,
                    'bot_name' => ucfirst($bot_signature),
                    'company' => $bot_data['company'],
                    'confidence' => $bot_data['confidence']
                );
            }
        }
        
        return array(
            'is_bot' => false,
            'confidence' => 0
        );
    }
    
    private function serve_monetized_content($detection_result) {
        $options = get_option('crawlguard_options');
        
        if (!isset($options['monetization_enabled']) || !$options['monetization_enabled']) {
            return;
        }
        
        $content_type = $this->get_content_type();
        $pricing = $this->calculate_pricing($detection_result, $content_type);
        
        if ($pricing['amount'] > 0) {
            $this->add_monetization_headers($pricing);
            $this->log_monetization_event($detection_result, $pricing);
        }
    }
    
    private function get_content_type() {
        if (is_single() || is_page()) {
            return 'article';
        } elseif (is_category() || is_tag() || is_archive()) {
            return 'archive';
        } elseif (is_home() || is_front_page()) {
            return 'homepage';
        } else {
            return 'other';
        }
    }
    
    private function calculate_pricing($detection_result, $content_type) {
        $base_rates = array(
            'OpenAI' => 0.002,
            'Anthropic' => 0.0015,
            'Google' => 0.001,
            'Common Crawl' => 0.001
        );
        
        $content_multipliers = array(
            'article' => 1.5,
            'archive' => 1.0,
            'homepage' => 1.2,
            'other' => 0.8
        );
        
        $base_rate = $base_rates[$detection_result['company']] ?? 0.001;
        $multiplier = $content_multipliers[$content_type] ?? 1.0;
        
        return array(
            'amount' => $base_rate * $multiplier,
            'currency' => 'USD',
            'content_type' => $content_type,
            'base_rate' => $base_rate,
            'multiplier' => $multiplier
        );
    }
    
    private function add_monetization_headers($pricing) {
        if (!headers_sent()) {
            header('X-CrawlGuard-Monetized: true');
            header('X-CrawlGuard-Amount: ' . $pricing['amount']);
            header('X-CrawlGuard-Currency: ' . $pricing['currency']);
            header('X-CrawlGuard-Content-Type: ' . $pricing['content_type']);
        }
    }
    
    private function log_monetization_event($detection_result, $pricing) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => current_time('mysql'),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'bot_detected' => 1,
                'bot_type' => $detection_result['bot_name'],
                'action_taken' => 'monetized',
                'revenue_generated' => $pricing['amount']
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%f')
        );
    }
    
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    public function add_robots_txt_rules() {
        $options = get_option('crawlguard_options');
        
        if (isset($options['monetization_enabled']) && $options['monetization_enabled']) {
            echo "# CrawlGuard WP - AI Bot Monetization\n";
            echo "User-agent: GPTBot\n";
            echo "Crawl-delay: 1\n";
            echo "User-agent: ChatGPT-User\n";
            echo "Crawl-delay: 1\n";
            echo "User-agent: Claude-Web\n";
            echo "Crawl-delay: 1\n";
            echo "User-agent: Bard\n";
            echo "Crawl-delay: 1\n";
            echo "User-agent: CCBot\n";
            echo "Crawl-delay: 1\n\n";
        }
    }
    
    public function handle_sitemap_requests() {
        if (strpos($_SERVER['REQUEST_URI'], 'sitemap') !== false) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $detection_result = $this->quick_bot_check($user_agent);
            
            if ($detection_result['is_bot'] && $detection_result['confidence'] >= 90) {
                $this->serve_monetized_content($detection_result);
            }
        }
    }
}
