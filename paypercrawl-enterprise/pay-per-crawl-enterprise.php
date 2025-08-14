<?php
/**
 * Plugin Name: PayPerCrawl Pro - Enterprise AI Bot Monetization
 * Plugin URI: https://paypercrawl.tech
 * Description: Enterprise-grade AI Bot Detection & Revenue Platform with Cloudflare Integration - Turn Every AI Crawl Into Revenue
 * Version: 4.0.0
 * Author: PayPerCrawl.tech
 * Author URI: https://paypercrawl.tech
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: paypercrawl
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package PayPerCrawl
 * @version 4.0.0
 * @author PayPerCrawl.tech
 * @copyright 2025 PayPerCrawl.tech
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Plugin security check
if (!function_exists('add_action')) {
    exit('WordPress not detected.');
}

/**
 * Plugin Constants
 */
define('PAYPERCRAWL_VERSION', '4.0.0');
define('PAYPERCRAWL_PLUGIN_FILE', __FILE__);
define('PAYPERCRAWL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYPERCRAWL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PAYPERCRAWL_PLUGIN_BASENAME', plugin_basename(__FILE__));

// API Endpoints
define('PAYPERCRAWL_API_BASE', 'https://api.paypercrawl.tech/v1/');
define('PAYPERCRAWL_CLOUDFLARE_API', 'https://api.cloudflare.com/client/v4/');
define('PAYPERCRAWL_WORKER_API', 'https://crawlguard-api-prod.crawlguard-api.workers.dev/');

// Feature flags
define('PAYPERCRAWL_ENTERPRISE_MODE', true);
define('PAYPERCRAWL_CLOUDFLARE_INTEGRATION', true);
define('PAYPERCRAWL_ML_DETECTION', true);
define('PAYPERCRAWL_REALTIME_UPDATES', true);

/**
 * Main Plugin Class - Enterprise PayPerCrawl
 * 
 * @since 4.0.0
 */
final class PayPerCrawl_Enterprise {
    
    /**
     * Plugin instance
     * @var PayPerCrawl_Enterprise
     */
    private static $instance = null;
    
    /**
     * Plugin components
     */
    private $bot_detector;
    private $analytics;
    private $cloudflare;
    private $dashboard;
    private $api_client;
    private $error_handler;
    
    /**
     * Plugin configuration
     */
    private $config = [];
    private $credentials = [];
    private $bot_signatures = [];
    
    /**
     * Singleton instance
     * 
     * @return PayPerCrawl_Enterprise
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Initialize the plugin
     */
    private function __construct() {
        $this->init_error_handling();
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize comprehensive error handling
     */
    private function init_error_handling() {
        // Set custom error handler for plugin
        set_error_handler([$this, 'handle_php_error'], E_ALL);
        
        // Register shutdown function for fatal errors
        register_shutdown_function([$this, 'handle_fatal_error']);
        
        // Enable WordPress debug logging if in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            ini_set('log_errors', 1);
            ini_set('error_log', WP_CONTENT_DIR . '/debug.log');
        }
    }
    
    /**
     * Load all plugin dependencies
     */
    private function load_dependencies() {
        $includes_dir = PAYPERCRAWL_PLUGIN_DIR . 'includes/';
        
        $dependencies = [
            'class-error-handler.php',
            'class-config-manager.php',
            'class-credential-manager.php',
            'class-bot-detector-enterprise.php',
            'class-analytics-engine.php',
            'class-cloudflare-integration.php',
            'class-dashboard-pro.php',
            'class-api-client.php',
            'class-revenue-optimizer.php',
            'class-ml-engine.php',
            'class-security-manager.php'
        ];
        
        foreach ($dependencies as $file) {
            $filepath = $includes_dir . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            } else {
                $this->log_error("Missing dependency: {$file}");
            }
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(PAYPERCRAWL_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(PAYPERCRAWL_PLUGIN_FILE, [$this, 'deactivate']);
        
        // WordPress initialization
        add_action('init', [$this, 'init'], 0);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'frontend_enqueue_scripts']);
        add_action('template_redirect', [$this, 'detect_and_process_bots'], 1);
        add_action('wp_head', [$this, 'add_tracking_code'], 1);
        
        // AJAX hooks
        add_action('wp_ajax_paypercrawl_dashboard_stats', [$this, 'ajax_dashboard_stats']);
        add_action('wp_ajax_paypercrawl_bot_activity', [$this, 'ajax_bot_activity']);
        add_action('wp_ajax_paypercrawl_update_settings', [$this, 'ajax_update_settings']);
        add_action('wp_ajax_paypercrawl_test_credentials', [$this, 'ajax_test_credentials']);
        
        // Cron hooks
        add_action('paypercrawl_update_signatures', [$this, 'update_bot_signatures']);
        add_action('paypercrawl_process_analytics', [$this, 'process_analytics']);
        add_action('paypercrawl_cleanup_logs', [$this, 'cleanup_old_logs']);
        
        // API hooks
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        try {
            // Initialize core components
            if (class_exists('PayPerCrawl_Error_Handler')) {
                $this->error_handler = new PayPerCrawl_Error_Handler();
            }
            
            if (class_exists('PayPerCrawl_Bot_Detector_Enterprise')) {
                $this->bot_detector = new PayPerCrawl_Bot_Detector_Enterprise();
            }
            
            if (class_exists('PayPerCrawl_Analytics_Engine')) {
                $this->analytics = new PayPerCrawl_Analytics_Engine();
            }
            
            if (class_exists('PayPerCrawl_Cloudflare_Integration')) {
                $this->cloudflare = new PayPerCrawl_Cloudflare_Integration();
            }
            
            if (class_exists('PayPerCrawl_Dashboard_Pro')) {
                $this->dashboard = new PayPerCrawl_Dashboard_Pro();
            }
            
            if (class_exists('PayPerCrawl_API_Client')) {
                $this->api_client = new PayPerCrawl_API_Client();
            }
            
            // Load configuration
            $this->load_configuration();
            
        } catch (Exception $e) {
            $this->log_error('Component initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        try {
            // Create database tables
            $this->create_database_tables();
            
            // Set default options
            $this->set_default_options();
            
            // Schedule cron jobs
            $this->schedule_cron_jobs();
            
            // Create upload directories
            $this->create_directories();
            
            // Initialize bot signatures
            $this->initialize_bot_signatures();
            
            // Log activation
            $this->log_info('PayPerCrawl Enterprise activated successfully');
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            $this->log_error('Activation failed: ' . $e->getMessage());
            wp_die('PayPerCrawl activation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        try {
            // Clear scheduled cron jobs
            wp_clear_scheduled_hook('paypercrawl_update_signatures');
            wp_clear_scheduled_hook('paypercrawl_process_analytics');
            wp_clear_scheduled_hook('paypercrawl_cleanup_logs');
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            $this->log_info('PayPerCrawl Enterprise deactivated');
            
        } catch (Exception $e) {
            $this->log_error('Deactivation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        try {
            // Load configuration
            $this->load_configuration();
            
            // Initialize bot detection if enabled
            if ($this->is_bot_detection_enabled()) {
                $this->init_bot_detection();
            }
            
            // Initialize real-time updates
            if (PAYPERCRAWL_REALTIME_UPDATES) {
                $this->init_realtime_updates();
            }
            
        } catch (Exception $e) {
            $this->log_error('Initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'paypercrawl',
            false,
            dirname(PAYPERCRAWL_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bot detections table
        $table_logs = $wpdb->prefix . 'paypercrawl_detections';
        $sql_logs = "CREATE TABLE {$table_logs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bot_type varchar(100) NOT NULL,
            bot_name varchar(100) NOT NULL,
            company varchar(100) NOT NULL,
            detection_method varchar(50) NOT NULL,
            confidence_score decimal(3,2) DEFAULT 0.00,
            revenue decimal(10,4) NOT NULL DEFAULT 0.0000,
            url text NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            headers longtext,
            cloudflare_data longtext,
            detected_at datetime NOT NULL,
            processed_at datetime DEFAULT NULL,
            status enum('pending','processed','disputed') DEFAULT 'pending',
            INDEX idx_bot_type (bot_type),
            INDEX idx_company (company),
            INDEX idx_detected_at (detected_at),
            INDEX idx_ip_address (ip_address),
            INDEX idx_status (status),
            PRIMARY KEY (id)
        ) {$charset_collate};";
        
        dbDelta($sql_logs);
        
        // Analytics table
        $table_analytics = $wpdb->prefix . 'paypercrawl_analytics';
        $sql_analytics = "CREATE TABLE {$table_analytics} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            date_recorded date NOT NULL,
            bot_type varchar(100) NOT NULL,
            company varchar(100) NOT NULL,
            total_detections int(11) DEFAULT 0,
            total_revenue decimal(10,4) DEFAULT 0.0000,
            avg_confidence decimal(3,2) DEFAULT 0.00,
            unique_ips int(11) DEFAULT 0,
            top_pages longtext,
            hourly_distribution longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            UNIQUE KEY unique_date_bot (date_recorded, bot_type),
            INDEX idx_date_recorded (date_recorded),
            INDEX idx_bot_type (bot_type),
            INDEX idx_company (company),
            PRIMARY KEY (id)
        ) {$charset_collate};";
        
        dbDelta($sql_analytics);
        
        // Configuration table
        $table_config = $wpdb->prefix . 'paypercrawl_config';
        $sql_config = "CREATE TABLE {$table_config} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            config_key varchar(100) NOT NULL,
            config_value longtext,
            config_type varchar(20) DEFAULT 'string',
            is_encrypted tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            UNIQUE KEY unique_config_key (config_key),
            PRIMARY KEY (id)
        ) {$charset_collate};";
        
        dbDelta($sql_config);
        
        // Request tracking table (for behavioral analysis)
        $table_requests = $wpdb->prefix . 'paypercrawl_requests';
        $sql_requests = "CREATE TABLE {$table_requests} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            url text NOT NULL,
            method varchar(10) DEFAULT 'GET',
            headers longtext,
            request_time bigint(20) NOT NULL,
            response_code int(11) DEFAULT 200,
            processing_time decimal(8,3) DEFAULT 0.000,
            INDEX idx_ip_address (ip_address),
            INDEX idx_request_time (request_time),
            INDEX idx_response_code (response_code),
            PRIMARY KEY (id)
        ) {$charset_collate};";
        
        dbDelta($sql_requests);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $default_options = [
            'paypercrawl_version' => PAYPERCRAWL_VERSION,
            'paypercrawl_bot_detection_enabled' => true,
            'paypercrawl_cloudflare_enabled' => false,
            'paypercrawl_ml_detection_enabled' => true,
            'paypercrawl_realtime_updates' => true,
            'paypercrawl_revenue_tracking' => true,
            'paypercrawl_analytics_enabled' => true,
            'paypercrawl_log_retention_days' => 90,
            'paypercrawl_api_rate_limit' => 1000,
            'paypercrawl_security_level' => 'medium',
            'paypercrawl_dashboard_theme' => 'dark',
            'paypercrawl_notification_email' => get_option('admin_email'),
            'paypercrawl_currency' => 'USD',
            'paypercrawl_timezone' => get_option('timezone_string', 'UTC'),
        ];
        
        foreach ($default_options as $option_name => $default_value) {
            if (!get_option($option_name)) {
                add_option($option_name, $default_value);
            }
        }
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        // Update bot signatures every 6 hours
        if (!wp_next_scheduled('paypercrawl_update_signatures')) {
            wp_schedule_event(time(), 'paypercrawl_6hourly', 'paypercrawl_update_signatures');
        }
        
        // Process analytics daily
        if (!wp_next_scheduled('paypercrawl_process_analytics')) {
            wp_schedule_event(time(), 'daily', 'paypercrawl_process_analytics');
        }
        
        // Cleanup old logs weekly
        if (!wp_next_scheduled('paypercrawl_cleanup_logs')) {
            wp_schedule_event(time(), 'weekly', 'paypercrawl_cleanup_logs');
        }
        
        // Add custom cron intervals
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_cron_intervals($schedules) {
        $schedules['paypercrawl_6hourly'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 Hours', 'paypercrawl')
        ];
        
        return $schedules;
    }
    
    /**
     * Create required directories
     */
    private function create_directories() {
        $upload_dir = wp_upload_dir();
        $paypercrawl_dir = $upload_dir['basedir'] . '/paypercrawl/';
        
        $directories = [
            $paypercrawl_dir,
            $paypercrawl_dir . 'logs/',
            $paypercrawl_dir . 'cache/',
            $paypercrawl_dir . 'reports/',
            $paypercrawl_dir . 'exports/',
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                
                // Add index.php for security
                file_put_contents($dir . 'index.php', '<?php // Silence is golden');
                
                // Add .htaccess for additional security
                file_put_contents($dir . '.htaccess', 'deny from all');
            }
        }
    }
    
    /**
     * Initialize comprehensive bot signatures
     */
    private function initialize_bot_signatures() {
        $signatures = [
            // OpenAI Family - Premium Tier
            'GPTBot' => [
                'rate' => 0.15,
                'type' => 'premium',
                'company' => 'OpenAI',
                'category' => 'ai_crawler',
                'priority' => 'high',
                'patterns' => ['GPTBot', 'ChatGPT-User', 'OpenAI-GPT'],
                'ip_ranges' => ['20.171.0.0/16', '52.230.0.0/15'],
                'headers' => ['User-Agent' => '/GPTBot/i']
            ],
            
            // Anthropic Claude Family - Premium Tier
            'ClaudeBot' => [
                'rate' => 0.12,
                'type' => 'premium',
                'company' => 'Anthropic',
                'category' => 'ai_crawler',
                'priority' => 'high',
                'patterns' => ['ClaudeBot', 'Claude-Web', 'anthropic-ai', 'CCBot'],
                'ip_ranges' => ['52.84.0.0/15', '54.230.0.0/16'],
                'headers' => ['User-Agent' => '/Claude|anthropic/i']
            ],
            
            // Google AI Family - Standard Tier
            'Google-Extended' => [
                'rate' => 0.10,
                'type' => 'standard',
                'company' => 'Google',
                'category' => 'ai_crawler',
                'priority' => 'high',
                'patterns' => ['Google-Extended', 'GoogleOther', 'Bard', 'PaLM', 'Gemini'],
                'ip_ranges' => ['66.249.64.0/19', '216.239.32.0/19'],
                'headers' => ['User-Agent' => '/Google.*Extended|Bard|Gemini/i']
            ],
            
            // Microsoft AI Family - Standard Tier
            'BingBot' => [
                'rate' => 0.08,
                'type' => 'standard',
                'company' => 'Microsoft',
                'category' => 'ai_crawler',
                'priority' => 'medium',
                'patterns' => ['bingbot', 'BingPreview', 'msnbot', 'CopilotBot'],
                'ip_ranges' => ['40.76.0.0/14', '65.52.0.0/14'],
                'headers' => ['User-Agent' => '/bingbot|msnbot|CopilotBot/i']
            ],
            
            // Meta AI Family - Standard Tier
            'Meta-ExternalAgent' => [
                'rate' => 0.08,
                'type' => 'standard',
                'company' => 'Meta',
                'category' => 'ai_crawler',
                'priority' => 'medium',
                'patterns' => ['Meta-ExternalAgent', 'FacebookBot', 'Meta-ExternalFetcher'],
                'ip_ranges' => ['31.13.24.0/21', '66.220.144.0/20'],
                'headers' => ['User-Agent' => '/Meta.*Agent|FacebookBot/i']
            ],
            
            // Emerging AI Companies
            'PerplexityBot' => [
                'rate' => 0.06,
                'type' => 'emerging',
                'company' => 'Perplexity',
                'category' => 'ai_crawler',
                'priority' => 'medium',
                'patterns' => ['PerplexityBot'],
                'headers' => ['User-Agent' => '/PerplexityBot/i']
            ],
            
            'YouBot' => [
                'rate' => 0.05,
                'type' => 'emerging',
                'company' => 'You.com',
                'category' => 'ai_crawler',
                'priority' => 'low',
                'patterns' => ['YouBot'],
                'headers' => ['User-Agent' => '/YouBot/i']
            ],
            
            'Bytespider' => [
                'rate' => 0.04,
                'type' => 'emerging',
                'company' => 'ByteDance',
                'category' => 'ai_crawler',
                'priority' => 'low',
                'patterns' => ['Bytespider'],
                'headers' => ['User-Agent' => '/Bytespider/i']
            ],
        ];
        
        update_option('paypercrawl_bot_signatures', $signatures);
        $this->bot_signatures = $signatures;
    }
    
    /**
     * Load plugin configuration
     */
    private function load_configuration() {
        $this->config = [
            'bot_detection_enabled' => get_option('paypercrawl_bot_detection_enabled', true),
            'cloudflare_enabled' => get_option('paypercrawl_cloudflare_enabled', false),
            'ml_detection_enabled' => get_option('paypercrawl_ml_detection_enabled', true),
            'realtime_updates' => get_option('paypercrawl_realtime_updates', true),
            'revenue_tracking' => get_option('paypercrawl_revenue_tracking', true),
            'analytics_enabled' => get_option('paypercrawl_analytics_enabled', true),
        ];
        
        // Load bot signatures
        $this->bot_signatures = get_option('paypercrawl_bot_signatures', []);
    }
    
    /**
     * Check if bot detection is enabled
     */
    private function is_bot_detection_enabled() {
        return !empty($this->config['bot_detection_enabled']);
    }
    
    /**
     * Initialize bot detection
     */
    private function init_bot_detection() {
        if ($this->bot_detector) {
            $this->bot_detector->init($this->bot_signatures);
        }
    }
    
    /**
     * Initialize real-time updates
     */
    private function init_realtime_updates() {
        // WebSocket or Server-Sent Events for real-time dashboard updates
        if (is_admin()) {
            add_action('admin_footer', [$this, 'add_realtime_script']);
        }
    }
    
    /**
     * Bot detection and processing
     */
    public function detect_and_process_bots() {
        if (!$this->is_bot_detection_enabled() || !$this->bot_detector) {
            return;
        }
        
        try {
            // Detect bot
            $detection_result = $this->bot_detector->detect();
            
            if ($detection_result && !empty($detection_result['bot_info'])) {
                // Process the detection
                $this->process_bot_detection($detection_result);
                
                // Handle Cloudflare integration
                if (PAYPERCRAWL_CLOUDFLARE_INTEGRATION && $this->cloudflare) {
                    $this->cloudflare->process_detection($detection_result);
                }
                
                // Real-time updates
                if (PAYPERCRAWL_REALTIME_UPDATES) {
                    $this->send_realtime_update($detection_result);
                }
            }
            
        } catch (Exception $e) {
            $this->log_error('Bot detection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Process bot detection
     */
    private function process_bot_detection($detection_result) {
        global $wpdb;
        
        $bot_info = $detection_result['bot_info'];
        $request_data = $detection_result['request_data'];
        
        // Calculate revenue
        $revenue = $this->calculate_revenue($bot_info, $request_data);
        
        // Insert detection record
        $wpdb->insert(
            $wpdb->prefix . 'paypercrawl_detections',
            [
                'bot_type' => $bot_info['type'],
                'bot_name' => $bot_info['name'],
                'company' => $bot_info['company'],
                'detection_method' => $detection_result['method'],
                'confidence_score' => $detection_result['confidence'],
                'revenue' => $revenue,
                'url' => $request_data['url'],
                'ip_address' => $request_data['ip'],
                'user_agent' => $request_data['user_agent'],
                'headers' => json_encode($request_data['headers']),
                'cloudflare_data' => json_encode($request_data['cloudflare'] ?? []),
                'detected_at' => current_time('mysql'),
                'status' => 'pending'
            ]
        );
        
        // Update analytics if enabled
        if ($this->config['analytics_enabled'] && $this->analytics) {
            $this->analytics->update_realtime_stats($bot_info, $revenue);
        }
        
        // Trigger hooks for extensibility
        do_action('paypercrawl_bot_detected', $bot_info, $request_data, $revenue);
    }
    
    /**
     * Calculate revenue for detection
     */
    private function calculate_revenue($bot_info, $request_data) {
        $base_rate = $bot_info['rate'] ?? 0.01;
        
        // Apply multipliers based on various factors
        $multipliers = [
            'time_of_day' => $this->get_time_multiplier(),
            'page_value' => $this->get_page_value_multiplier($request_data['url']),
            'bot_priority' => $this->get_priority_multiplier($bot_info['priority'] ?? 'low'),
            'geographic' => $this->get_geographic_multiplier($request_data['ip']),
        ];
        
        $final_rate = $base_rate;
        foreach ($multipliers as $multiplier) {
            $final_rate *= $multiplier;
        }
        
        return round($final_rate, 4);
    }
    
    /**
     * Get time-based multiplier
     */
    private function get_time_multiplier() {
        $hour = (int) current_time('H');
        
        // Peak hours (9 AM - 5 PM) get higher rates
        if ($hour >= 9 && $hour <= 17) {
            return 1.2;
        }
        
        // Night hours get standard rates
        return 1.0;
    }
    
    /**
     * Get page value multiplier
     */
    private function get_page_value_multiplier($url) {
        // High-value pages get higher rates
        $high_value_patterns = [
            '/\/product\//i',
            '/\/service\//i',
            '/\/pricing\//i',
            '/\/buy\//i',
            '/\/purchase\//i',
        ];
        
        foreach ($high_value_patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return 1.5;
            }
        }
        
        return 1.0;
    }
    
    /**
     * Get priority multiplier
     */
    private function get_priority_multiplier($priority) {
        $multipliers = [
            'high' => 1.5,
            'medium' => 1.2,
            'low' => 1.0,
        ];
        
        return $multipliers[$priority] ?? 1.0;
    }
    
    /**
     * Get geographic multiplier
     */
    private function get_geographic_multiplier($ip) {
        // This would integrate with a geolocation service
        // For now, return standard multiplier
        return 1.0;
    }
    
    /**
     * Send real-time update
     */
    private function send_realtime_update($detection_result) {
        // This would send updates via WebSocket or Server-Sent Events
        // For now, we'll use a simple AJAX polling system
        update_option('paypercrawl_last_detection', [
            'timestamp' => time(),
            'data' => $detection_result
        ]);
    }
    
    /**
     * Admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('PayPerCrawl Pro', 'paypercrawl'),
            __('PayPerCrawl Pro', 'paypercrawl'),
            'manage_options',
            'paypercrawl',
            [$this, 'admin_dashboard'],
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>'),
            30
        );
        
        // Submenu pages
        add_submenu_page('paypercrawl', __('Dashboard', 'paypercrawl'), '📊 ' . __('Dashboard', 'paypercrawl'), 'manage_options', 'paypercrawl', [$this, 'admin_dashboard']);
        add_submenu_page('paypercrawl', __('Analytics', 'paypercrawl'), '📈 ' . __('Analytics', 'paypercrawl'), 'manage_options', 'paypercrawl-analytics', [$this, 'admin_analytics']);
        add_submenu_page('paypercrawl', __('Bot Detection', 'paypercrawl'), '🤖 ' . __('Bot Detection', 'paypercrawl'), 'manage_options', 'paypercrawl-bots', [$this, 'admin_bots']);
        add_submenu_page('paypercrawl', __('Revenue', 'paypercrawl'), '💰 ' . __('Revenue', 'paypercrawl'), 'manage_options', 'paypercrawl-revenue', [$this, 'admin_revenue']);
        add_submenu_page('paypercrawl', __('Cloudflare', 'paypercrawl'), '☁️ ' . __('Cloudflare', 'paypercrawl'), 'manage_options', 'paypercrawl-cloudflare', [$this, 'admin_cloudflare']);
        add_submenu_page('paypercrawl', __('Settings', 'paypercrawl'), '⚙️ ' . __('Settings', 'paypercrawl'), 'manage_options', 'paypercrawl-settings', [$this, 'admin_settings']);
    }
    
    /**
     * Admin dashboard
     */
    public function admin_dashboard() {
        if ($this->dashboard) {
            $this->dashboard->render();
        } else {
            echo '<div class="wrap"><h1>PayPerCrawl Pro Dashboard</h1><p>Loading...</p></div>';
        }
    }
    
    /**
     * Analytics page
     */
    public function admin_analytics() {
        echo '<div class="wrap"><h1>' . __('Analytics', 'paypercrawl') . '</h1>';
        echo '<p>' . __('Advanced analytics and reporting coming soon.', 'paypercrawl') . '</p></div>';
    }
    
    /**
     * Bot detection page
     */
    public function admin_bots() {
        echo '<div class="wrap"><h1>' . __('Bot Detection', 'paypercrawl') . '</h1>';
        echo '<p>' . __('Bot detection management coming soon.', 'paypercrawl') . '</p></div>';
    }
    
    /**
     * Revenue page
     */
    public function admin_revenue() {
        echo '<div class="wrap"><h1>' . __('Revenue', 'paypercrawl') . '</h1>';
        echo '<p>' . __('Revenue optimization coming soon.', 'paypercrawl') . '</p></div>';
    }
    
    /**
     * Cloudflare page
     */
    public function admin_cloudflare() {
        echo '<div class="wrap"><h1>' . __('Cloudflare Integration', 'paypercrawl') . '</h1>';
        echo '<p>' . __('Cloudflare AI bot blocking integration.', 'paypercrawl') . '</p></div>';
    }
    
    /**
     * Settings page
     */
    public function admin_settings() {
        echo '<div class="wrap"><h1>' . __('Settings', 'paypercrawl') . '</h1>';
        echo '<p>' . __('Plugin settings coming soon.', 'paypercrawl') . '</p></div>';
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Register settings
        register_setting('paypercrawl_settings', 'paypercrawl_options');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'paypercrawl') === false) {
            return;
        }
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
        wp_enqueue_script('paypercrawl-admin', PAYPERCRAWL_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'chart-js'], PAYPERCRAWL_VERSION, true);
        
        wp_localize_script('paypercrawl-admin', 'paypercrawl', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('paypercrawl_nonce'),
            'plugin_url' => PAYPERCRAWL_PLUGIN_URL,
            'version' => PAYPERCRAWL_VERSION,
        ]);
        
        wp_enqueue_style('paypercrawl-admin', PAYPERCRAWL_PLUGIN_URL . 'assets/css/admin.css', [], PAYPERCRAWL_VERSION);
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function frontend_enqueue_scripts() {
        // Only load if tracking is enabled
        if ($this->config['revenue_tracking']) {
            wp_enqueue_script('paypercrawl-tracker', PAYPERCRAWL_PLUGIN_URL . 'assets/js/tracker.js', [], PAYPERCRAWL_VERSION, true);
        }
    }
    
    /**
     * Add tracking code to head
     */
    public function add_tracking_code() {
        if (!$this->config['revenue_tracking']) {
            return;
        }
        
        echo "<!-- PayPerCrawl Pro Tracking -->\n";
        echo "<script>\n";
        echo "window.PayPerCrawl = window.PayPerCrawl || {};\n";
        echo "window.PayPerCrawl.config = " . json_encode([
            'version' => PAYPERCRAWL_VERSION,
            'endpoint' => rest_url('paypercrawl/v1/track'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]) . ";\n";
        echo "</script>\n";
    }
    
    /**
     * AJAX: Dashboard stats
     */
    public function ajax_dashboard_stats() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        if ($this->analytics) {
            $stats = $this->analytics->get_dashboard_stats();
            wp_send_json_success($stats);
        } else {
            wp_send_json_error('Analytics not available');
        }
    }
    
    /**
     * AJAX: Bot activity
     */
    public function ajax_bot_activity() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        if ($this->analytics) {
            $activity = $this->analytics->get_recent_activity();
            wp_send_json_success($activity);
        } else {
            wp_send_json_error('Analytics not available');
        }
    }
    
    /**
     * AJAX: Update settings
     */
    public function ajax_update_settings() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        // Process settings update
        wp_send_json_success(['message' => 'Settings updated']);
    }
    
    /**
     * AJAX: Test credentials
     */
    public function ajax_test_credentials() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $cloudflare_token = sanitize_text_field($_POST['cloudflare_token'] ?? '');
        
        $results = [];
        
        // Test API key
        if ($api_key && $this->api_client) {
            $results['api'] = $this->api_client->test_connection($api_key);
        }
        
        // Test Cloudflare
        if ($cloudflare_token && $this->cloudflare) {
            $results['cloudflare'] = $this->cloudflare->test_connection($cloudflare_token);
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_api_endpoints() {
        register_rest_route('paypercrawl/v1', '/track', [
            'methods' => 'POST',
            'callback' => [$this, 'api_track_request'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('paypercrawl/v1', '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_stats'],
            'permission_callback' => [$this, 'api_permission_check'],
        ]);
    }
    
    /**
     * API: Track request
     */
    public function api_track_request($request) {
        // Handle API tracking requests
        return new WP_REST_Response(['status' => 'tracked'], 200);
    }
    
    /**
     * API: Get stats
     */
    public function api_get_stats($request) {
        if ($this->analytics) {
            return new WP_REST_Response($this->analytics->get_api_stats(), 200);
        }
        
        return new WP_Error('no_analytics', 'Analytics not available', ['status' => 503]);
    }
    
    /**
     * API permission check
     */
    public function api_permission_check() {
        return current_user_can('manage_options');
    }
    
    /**
     * Update bot signatures
     */
    public function update_bot_signatures() {
        if ($this->api_client) {
            $new_signatures = $this->api_client->fetch_latest_signatures();
            if ($new_signatures) {
                update_option('paypercrawl_bot_signatures', $new_signatures);
                $this->bot_signatures = $new_signatures;
                $this->log_info('Bot signatures updated');
            }
        }
    }
    
    /**
     * Process analytics
     */
    public function process_analytics() {
        if ($this->analytics) {
            $this->analytics->process_daily_analytics();
            $this->log_info('Daily analytics processed');
        }
    }
    
    /**
     * Cleanup old logs
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $retention_days = get_option('paypercrawl_log_retention_days', 90);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}paypercrawl_detections WHERE detected_at < %s",
            $cutoff_date
        ));
        
        $this->log_info("Cleaned up {$deleted} old detection records");
    }
    
    /**
     * Add real-time script
     */
    public function add_realtime_script() {
        ?>
        <script>
        // Real-time updates for dashboard
        (function($) {
            let updateInterval;
            
            function startRealTimeUpdates() {
                updateInterval = setInterval(function() {
                    $.post(ajaxurl, {
                        action: 'paypercrawl_dashboard_stats',
                        nonce: paypercrawl.nonce
                    }, function(response) {
                        if (response.success) {
                            updateDashboardStats(response.data);
                        }
                    });
                }, 30000); // Update every 30 seconds
            }
            
            function updateDashboardStats(data) {
                // Update dashboard elements with new data
                if (data.total_revenue) {
                    $('.ppc-revenue-total').text('$' + data.total_revenue);
                }
                if (data.today_detections) {
                    $('.ppc-detections-today').text(data.today_detections);
                }
            }
            
            $(document).ready(function() {
                if (window.paynav && window.paypercrawl.page === 'dashboard') {
                    startRealTimeUpdates();
                }
            });
            
            $(window).on('beforeunload', function() {
                if (updateInterval) {
                    clearInterval(updateInterval);
                }
            });
        })(jQuery);
        </script>
        <?php
    }
    
    /**
     * PHP Error handler
     */
    public function handle_php_error($errno, $errstr, $errfile, $errline) {
        // Only log errors related to our plugin
        if (strpos($errfile, PAYPERCRAWL_PLUGIN_DIR) !== false) {
            $this->log_error("PHP Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}");
        }
        
        return false; // Don't interfere with normal error handling
    }
    
    /**
     * Fatal error handler
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if ($error && $error['type'] === E_ERROR) {
            if (strpos($error['file'], PAYPERCRAWL_PLUGIN_DIR) !== false) {
                $this->log_error("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
            }
        }
    }
    
    /**
     * Log error message
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PayPerCrawl] ERROR: ' . $message);
        }
        
        update_option('paypercrawl_last_error', [
            'message' => $message,
            'timestamp' => current_time('mysql'),
        ]);
    }
    
    /**
     * Log info message
     */
    private function log_info($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PayPerCrawl] INFO: ' . $message);
        }
    }
    
    /**
     * Get plugin instance
     */
    public function get_bot_detector() {
        return $this->bot_detector;
    }
    
    public function get_analytics() {
        return $this->analytics;
    }
    
    public function get_cloudflare() {
        return $this->cloudflare;
    }
    
    public function get_api_client() {
        return $this->api_client;
    }
    
    /**
     * Plugin version check and upgrade
     */
    public function check_version() {
        $installed_version = get_option('paypercrawl_version', '0.0.0');
        
        if (version_compare($installed_version, PAYPERCRAWL_VERSION, '<')) {
            $this->upgrade_plugin($installed_version, PAYPERCRAWL_VERSION);
            update_option('paypercrawl_version', PAYPERCRAWL_VERSION);
        }
    }
    
    /**
     * Upgrade plugin
     */
    private function upgrade_plugin($from_version, $to_version) {
        $this->log_info("Upgrading PayPerCrawl from {$from_version} to {$to_version}");
        
        // Run upgrade routines based on version
        if (version_compare($from_version, '4.0.0', '<')) {
            $this->upgrade_to_400();
        }
    }
    
    /**
     * Upgrade to version 4.0.0
     */
    private function upgrade_to_400() {
        // Migration logic for v4.0.0
        $this->create_database_tables(); // Ensure new tables exist
        $this->initialize_bot_signatures(); // Update signatures
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    PayPerCrawl_Enterprise::instance();
});

// Activation/Deactivation hooks (must be outside the class)
register_activation_hook(__FILE__, function() {
    PayPerCrawl_Enterprise::instance()->activate();
});

register_deactivation_hook(__FILE__, function() {
    PayPerCrawl_Enterprise::instance()->deactivate();
});

// Uninstall hook
register_uninstall_hook(__FILE__, function() {
    // Cleanup on uninstall
    global $wpdb;
    
    // Remove tables
    $tables = [
        $wpdb->prefix . 'paypercrawl_detections',
        $wpdb->prefix . 'paypercrawl_analytics',
        $wpdb->prefix . 'paypercrawl_config',
        $wpdb->prefix . 'paypercrawl_requests',
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // Remove options
    $options = [
        'paypercrawl_version',
        'paypercrawl_bot_detection_enabled',
        'paypercrawl_cloudflare_enabled',
        'paypercrawl_ml_detection_enabled',
        'paypercrawl_realtime_updates',
        'paypercrawl_revenue_tracking',
        'paypercrawl_analytics_enabled',
        'paypercrawl_bot_signatures',
        'paypercrawl_last_detection',
        'paypercrawl_last_error',
    ];
    
    foreach ($options as $option) {
        delete_option($option);
    }
});

// End of file
