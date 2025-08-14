<?php
/**
 * CrawlGuard WP - Configuration and Installation Helper
 * 
 * This file sets up the plugin with working API credentials
 * and provides easy installation commands.
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Setup {
    
    public static function install() {
        // Set default options with our working API configuration
        $default_options = array(
            'api_url' => 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1',
            'api_key' => 'cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx', // From our test registration
            'site_id' => 'site_oUSRqI213k8E', // From our test registration
            'enabled' => true,
            'monetization_enabled' => true,
            'block_unknown_bots' => false,
            'allow_search_engines' => true,
            'log_detections' => true,
            'notification_email' => get_option('admin_email'),
            'pricing_per_request' => 0.00065, // RL-optimized pricing
            'allowed_bots' => array(
                'googlebot',
                'bingbot',
                'slurp',
                'duckduckbot',
                'baiduspider',
                'yandexbot',
                'facebookexternalhit',
                'twitterbot'
            ),
            'blocked_bots' => array(
                'scrapy',
                'selenium',
                'phantomjs',
                'headless'
            ),
            'custom_rules' => array(),
            'analytics_enabled' => true,
            'setup_completed' => true,
            'rate_limit_enabled' => true,
            'rate_limit_requests' => 100,
            'rate_limit_window' => 3600,
            'revenue_optimization' => true
        );
        
        // Save options
        update_option('crawlguard_options', $default_options);
        
        // Create database tables for local logging
        self::create_tables();
        
        // Set installation timestamp
        update_option('crawlguard_installed', current_time('timestamp'));
        
        return true;
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_detections';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            detection_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            user_agent text NOT NULL,
            ip_address varchar(45) NOT NULL,
            page_url text NOT NULL,
            bot_detected tinyint(1) DEFAULT 0 NOT NULL,
            bot_name varchar(100) DEFAULT '' NOT NULL,
            bot_company varchar(100) DEFAULT '' NOT NULL,
            confidence_score int DEFAULT 0 NOT NULL,
            action_taken varchar(20) DEFAULT 'allowed' NOT NULL,
            revenue_amount decimal(10,6) DEFAULT 0.000000 NOT NULL,
            detection_method varchar(20) DEFAULT 'local' NOT NULL,
            PRIMARY KEY (id),
            KEY detection_time (detection_time),
            KEY bot_detected (bot_detected),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public static function test_api_connection() {
        $options = get_option('crawlguard_options');
        $api_url = $options['api_url'] ?? '';
        
        if (empty($api_url)) {
            return array(
                'success' => false,
                'message' => 'API URL not configured'
            );
        }
        
        // Test the status endpoint
        $response = wp_remote_get($api_url . '/status', array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json',
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'API returned status code: ' . $response_code
            );
        }
        
        $data = json_decode($response_body, true);
        
        if (!$data || !isset($data['success'])) {
            return array(
                'success' => false,
                'message' => 'Invalid API response'
            );
        }
        
        return array(
            'success' => true,
            'message' => 'API connection successful',
            'data' => $data
        );
    }
    
    public static function register_site_with_api() {
        $site_url = get_site_url();
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $registration_data = array(
            'site_url' => $site_url,
            'email' => $admin_email,
            'site_name' => $site_name,
            'plugin_version' => CRAWLGUARD_VERSION ?? '1.0.0',
            'wordpress_version' => get_bloginfo('version')
        );
        
        $api_url = 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1';
        
        $response = wp_remote_post($api_url . '/register', array(
            'body' => json_encode($registration_data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Registration failed: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'Registration failed with status code: ' . $response_code
            );
        }
        
        $data = json_decode($response_body, true);
        
        if (!$data || !$data['success']) {
            return array(
                'success' => false,
                'message' => 'Registration failed: ' . ($data['error'] ?? 'Unknown error')
            );
        }
        
        // Update options with new API key
        $options = get_option('crawlguard_options', array());
        $options['api_key'] = $data['api_key'];
        $options['site_id'] = $data['site_id'];
        update_option('crawlguard_options', $options);
        
        return array(
            'success' => true,
            'message' => 'Site registered successfully',
            'api_key' => $data['api_key'],
            'site_id' => $data['site_id']
        );
    }
}

// Auto-setup when plugin is activated
register_activation_hook(__FILE__, array('CrawlGuard_Setup', 'install'));
