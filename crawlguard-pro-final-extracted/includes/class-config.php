<?php
/**
 * Configuration Management for CrawlGuard Pro
 * 
 * Handles environment variables and configuration settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Config {
    
    private static $instance = null;
    private $config = [];
    private $env_file_loaded = false;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_env_file();
        $this->load_config();
    }
    
    /**
     * Load .env file if it exists
     */
    private function load_env_file() {
        $env_file = CRAWLGUARD_PLUGIN_PATH . '.env';
        
        if (file_exists($env_file)) {
            $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parse key=value
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    $value = trim($value, '"\'');
                    
                    // Set as environment variable
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
            
            $this->env_file_loaded = true;
        }
    }
    
    /**
     * Load configuration from various sources
     */
    private function load_config() {
        // Default configuration
        $defaults = [
            'api_key' => 'cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx',
            'api_url' => 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1',
            'cloudflare_worker_route' => '',
            'stripe_publishable_key' => '',
            'stripe_secret_key' => '',
            'platform_fee_percentage' => 15,
            'publisher_revenue_share' => 85,
            'ml_api_endpoint' => 'https://ml.crawlguard.com/v1/predict',
            'websocket_server_url' => 'wss://realtime.crawlguard.com',
            'environment' => 'production',
            'debug_mode' => false,
            'enable_ml_detection' => true,
            'enable_real_time_analytics' => true,
            'enable_payment_processing' => true,
            'enable_cloudflare_workers' => true
        ];
        
        // Load from WordPress options
        $saved_options = get_option('crawlguard_options', []);
        
        // Merge configurations (priority: ENV > WordPress options > defaults)
        foreach ($defaults as $key => $default_value) {
            $env_key = strtoupper($key);
            
            if (getenv($env_key) !== false) {
                $this->config[$key] = $this->parse_value(getenv($env_key));
            } elseif (isset($saved_options[$key])) {
                $this->config[$key] = $saved_options[$key];
            } else {
                $this->config[$key] = $default_value;
            }
        }
        
        // Special handling for Stripe keys
        if (getenv('STRIPE_SECRET_KEY')) {
            $this->config['stripe_secret_key'] = getenv('STRIPE_SECRET_KEY');
        }
        
        if (getenv('STRIPE_PUBLISHABLE_KEY')) {
            $this->config['stripe_publishable_key'] = getenv('STRIPE_PUBLISHABLE_KEY');
        }
    }
    
    /**
     * Parse configuration values
     */
    private function parse_value($value) {
        // Convert string booleans
        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;
        
        // Convert numeric strings
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        return $value;
    }
    
    /**
     * Get configuration value
     */
    public function get($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    /**
     * Set configuration value (runtime only)
     */
    public function set($key, $value) {
        $this->config[$key] = $value;
    }
    
    /**
     * Get all configuration
     */
    public function get_all() {
        return $this->config;
    }
    
    /**
     * Check if feature is enabled
     */
    public function is_feature_enabled($feature) {
        return $this->get('enable_' . $feature, false);
    }
    
    /**
     * Get API headers with authentication
     */
    public function get_api_headers() {
        return [
            'Authorization' => 'Bearer ' . $this->get('api_key'),
            'Content-Type' => 'application/json',
            'X-Site-ID' => $this->get_site_id(),
            'X-Plugin-Version' => CRAWLGUARD_VERSION
        ];
    }
    
    /**
     * Get or generate site ID
     */
    public function get_site_id() {
        $site_id = get_option('crawlguard_site_id');
        
        if (!$site_id) {
            $site_id = 'site_' . substr(md5(get_site_url() . wp_generate_password()), 0, 12);
            update_option('crawlguard_site_id', $site_id);
        }
        
        return $site_id;
    }
    
    /**
     * Check if running in production
     */
    public function is_production() {
        return $this->get('environment') === 'production';
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function is_debug() {
        return $this->get('debug_mode', false) || (defined('WP_DEBUG') && WP_DEBUG);
    }
    
    /**
     * Get Stripe configuration
     */
    public function get_stripe_config() {
        return [
            'publishable_key' => $this->get('stripe_publishable_key'),
            'secret_key' => $this->get('stripe_secret_key'),
            'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
            'connect_client_id' => getenv('STRIPE_CONNECT_CLIENT_ID') ?: ''
        ];
    }
    
    /**
     * Get revenue sharing configuration
     */
    public function get_revenue_config() {
        return [
            'platform_fee' => $this->get('platform_fee_percentage', 15),
            'publisher_share' => $this->get('publisher_revenue_share', 85)
        ];
    }
    
    /**
     * Validate configuration
     */
    public function validate() {
        $errors = [];
        
        // Check required configurations
        if (!$this->get('api_key')) {
            $errors[] = 'API key is not configured';
        }
        
        if ($this->is_feature_enabled('payment_processing') && !$this->get('stripe_secret_key')) {
            $errors[] = 'Stripe secret key is required for payment processing';
        }
        
        if ($this->is_feature_enabled('ml_detection') && !$this->get('ml_api_endpoint')) {
            $errors[] = 'ML API endpoint is required for AI detection';
        }
        
        return $errors;
    }
}
