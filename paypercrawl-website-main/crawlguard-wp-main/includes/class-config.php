<?php
/**
 * Configuration class for CrawlGuard WP
 * 
 * Manages all plugin configuration including Cloudflare settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Config {
    
    /**
     * Get Cloudflare API configuration
     */
    public static function get_cloudflare_config() {
        // Use worker URL directly until custom domain is configured
        // To switch to custom domain, replace api_base_url with:
        // 'api_base_url' => 'https://api.creativeinteriorsstudio.com/v1',
        return array(
            'worker_url' => 'https://crawlguard-api-prod.crawlguard-api.workers.dev',
            'custom_domain' => 'https://api.creativeinteriorsstudio.com',
            'api_base_url' => 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1',
            'account_id' => 'eb2e0a0f169c14046bc5f6b9946ce4e2',
            'zone_id' => '20142c7575d785e9d93b316eb9fbbd46'
        );
    }
    
    /**
     * Get API endpoints
     */
    public static function get_api_endpoints() {
        $base_url = self::get_cloudflare_config()['api_base_url'];
        
        return array(
            'validate' => $base_url . '/auth/validate',
            'monetize' => $base_url . '/monetize',
            'analytics' => $base_url . '/analytics',
            'register' => $base_url . '/sites/register',
            'settings' => $base_url . '/sites/settings',
            'payments' => $base_url . '/payments',
            'beacon' => $base_url . '/beacon',
            'status' => $base_url . '/status',
            'emergency' => $base_url . '/emergency-status'
        );
    }
    
    /**
     * Get WordPress site configuration
     */
    public static function get_site_config() {
        return array(
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'admin_email' => get_option('admin_email'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'plugin_version' => CRAWLGUARD_VERSION
        );
    }
    
    /**
     * Get debug configuration
     */
    public static function get_debug_config() {
        return array(
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'debug_display' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY,
            'environment' => defined('WP_ENVIRONMENT') ? WP_ENVIRONMENT : 'production'
        );
    }
    
    /**
     * Check if plugin is in development mode
     */
    public static function is_development() {
        $debug = self::get_debug_config();
        return $debug['environment'] === 'development' || $debug['debug_mode'];
    }
    
    /**
     * Get timeout settings for API calls
     */
    public static function get_timeout_settings() {
        return array(
            'default' => 5,
            'analytics' => 10,
            'beacon' => 1,
            'validation' => 3
        );
    }
    
    /**
     * Get rate limiting settings
     */
    public static function get_rate_limits() {
        return array(
            'api_calls_per_minute' => 60,
            'api_calls_per_hour' => 1000,
            'beacon_calls_per_minute' => 120
        );
    }
}
