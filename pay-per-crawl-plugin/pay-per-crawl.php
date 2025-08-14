<?php
/**
 * Plugin Name: Pay Per Crawl
 * Plugin URI: https://paypercrawl.tech
 * Description: Advanced AI Bot Detection & Monetization Platform - Turn Every AI Crawl Into Revenue
 * Version: 3.0.0
 * Author: PayPerCrawl.tech
 * Author URI: https://paypercrawl.tech
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pay-per-crawl
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PAYPERCRAWL_VERSION', '3.0.0');
define('PAYPERCRAWL_PLUGIN_FILE', __FILE__);
define('PAYPERCRAWL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYPERCRAWL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PAYPERCRAWL_API_URL', 'https://crawlguard-api-prod.crawlguard-api.workers.dev/');
define('PAYPERCRAWL_STANDALONE_MODE', false); // Using existing backend infrastructure

class PayPerCrawl {
    private static $instance = null;
    private $api_key = null;
    private $bot_signatures = [];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Enhanced bot detection with multiple hooks
        add_action('wp', array($this, 'detect_bot'));
        add_action('template_redirect', array($this, 'advanced_bot_detection'));
        
        // AJAX handlers for real-time dashboard updates
        add_action('wp_ajax_paypercrawl_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_paypercrawl_bot_activity', array($this, 'ajax_bot_activity'));
        
        register_activation_hook(PAYPERCRAWL_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(PAYPERCRAWL_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Load bot signatures
        $this->load_bot_signatures();
    }
    
    public function admin_init() {
        // Register settings
        register_setting('paypercrawl_settings', 'paypercrawl_options');
        
        // Add settings sections
        add_settings_section(
            'paypercrawl_general',
            'General Settings',
            array($this, 'settings_section_callback'),
            'paypercrawl'
        );
    }
    
    private function load_bot_signatures() {
        $this->bot_signatures = [
            // OpenAI & ChatGPT Family
            'GPTBot' => ['rate' => 0.12, 'type' => 'premium', 'company' => 'OpenAI'],
            'ChatGPT-User' => ['rate' => 0.12, 'type' => 'premium', 'company' => 'OpenAI'],
            'OpenAI' => ['rate' => 0.12, 'type' => 'premium', 'company' => 'OpenAI'],
            
            // Anthropic Claude Family
            'CCBot' => ['rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'],
            'anthropic-ai' => ['rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'],
            'Claude-Web' => ['rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'],
            'ClaudeBot' => ['rate' => 0.10, 'type' => 'premium', 'company' => 'Anthropic'],
            
            // Google AI Family
            'Google-Extended' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            'GoogleOther' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            'Bard' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            'PaLM' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            'Gemini' => ['rate' => 0.08, 'type' => 'standard', 'company' => 'Google'],
            
            // Meta AI Family
            'FacebookBot' => ['rate' => 0.07, 'type' => 'standard', 'company' => 'Meta'],
            'Meta-ExternalAgent' => ['rate' => 0.07, 'type' => 'standard', 'company' => 'Meta'],
            'Llama' => ['rate' => 0.07, 'type' => 'standard', 'company' => 'Meta'],
            
            // Microsoft AI Family
            'BingBot' => ['rate' => 0.06, 'type' => 'standard', 'company' => 'Microsoft'],
            'msnbot' => ['rate' => 0.06, 'type' => 'standard', 'company' => 'Microsoft'],
            'CopilotBot' => ['rate' => 0.06, 'type' => 'standard', 'company' => 'Microsoft'],
            
            // Other AI Companies
            'PerplexityBot' => ['rate' => 0.05, 'type' => 'emerging', 'company' => 'Perplexity'],
            'YouBot' => ['rate' => 0.05, 'type' => 'emerging', 'company' => 'You.com'],
            'Bytespider' => ['rate' => 0.04, 'type' => 'emerging', 'company' => 'ByteDance'],
            'YandexBot' => ['rate' => 0.04, 'type' => 'emerging', 'company' => 'Yandex'],
            
            // Research & Academic Bots
            'ResearchBot' => ['rate' => 0.03, 'type' => 'research', 'company' => 'Various'],
            'AcademicBot' => ['rate' => 0.03, 'type' => 'research', 'company' => 'Various'],
            
            // Web Scrapers with AI Features
            'ScrapingBot' => ['rate' => 0.02, 'type' => 'scraper', 'company' => 'Various'],
            'DataBot' => ['rate' => 0.02, 'type' => 'scraper', 'company' => 'Various'],
        ];
    }
    
    public function init() {
        $this->create_tables();
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Pay Per Crawl',
            'Pay Per Crawl',
            'manage_options',
            'paypercrawl-dashboard',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Dashboard',
            '📊 Dashboard',
            'manage_options',
            'paypercrawl-dashboard',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Analytics',
            '📈 Analytics',
            'manage_options',
            'paypercrawl-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Bot Detection',
            '🤖 Bot Detection',
            'manage_options',
            'paypercrawl-bots',
            array($this, 'bots_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Revenue Settings',
            '💰 Revenue',
            'manage_options',
            'paypercrawl-revenue',
            array($this, 'revenue_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Settings',
            '⚙️ Settings',
            'manage_options',
            'paypercrawl-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'paypercrawl-dashboard',
            'Support',
            '🆘 Support',
            'manage_options',
            'paypercrawl-support',
            array($this, 'support_page')
        );
    }
    
    public function admin_page() {
        // Get real-time statistics
        $today_bots = $this->get_bot_count();
        $today_revenue = $this->get_revenue_potential();
        $total_bots = $this->get_total_bot_count();
        $total_revenue = $this->get_total_revenue();
        $pages_protected = wp_count_posts()->publish;
        $active_crawlers = $this->get_active_crawlers_count();
        
        ?>
        <div class="wrap paypercrawl-dashboard">
            <!-- Header Section -->
            <div class="ppc-header">
                <div class="ppc-header-content">
                    <div class="ppc-logo-section">
                        <h1>💰 Pay Per Crawl</h1>
                        <p class="ppc-tagline">Turn Every AI Crawl Into Revenue</p>
                    </div>
                    <div class="ppc-header-stats">
                        <div class="ppc-mini-stat">
                            <span class="ppc-mini-value">$<?php echo number_format($today_revenue, 2); ?></span>
                            <span class="ppc-mini-label">Today's Revenue</span>
                        </div>
                        <div class="ppc-mini-stat">
                            <span class="ppc-mini-value"><?php echo $today_bots; ?></span>
                            <span class="ppc-mini-label">Bots Detected</span>
                        </div>
                    </div>
                </div>
                <div class="ppc-status-indicator <?php echo $this->get_system_status(); ?>">
                    <span class="ppc-status-dot"></span>
                    <span class="ppc-status-text"><?php echo $this->get_system_status_text(); ?></span>
                </div>
            </div>

            <!-- Main Statistics Grid -->
            <div class="ppc-stats-grid">
                <div class="ppc-stat-card primary">
                    <div class="ppc-stat-header">
                        <span class="ppc-stat-icon">💰</span>
                        <h3>Today's Revenue</h3>
                    </div>
                    <div class="ppc-stat-value">$<?php echo number_format($today_revenue, 2); ?></div>
                    <div class="ppc-stat-change positive">
                        <span>↗️ +<?php echo $this->get_revenue_change(); ?>%</span>
                        <span>vs yesterday</span>
                    </div>
                </div>

                <div class="ppc-stat-card">
                    <div class="ppc-stat-header">
                        <span class="ppc-stat-icon">🤖</span>
                        <h3>AI Bots Today</h3>
                    </div>
                    <div class="ppc-stat-value"><?php echo $today_bots; ?></div>
                    <div class="ppc-stat-meta">
                        <span><?php echo $active_crawlers; ?> active crawler types</span>
                    </div>
                </div>

                <div class="ppc-stat-card">
                    <div class="ppc-stat-header">
                        <span class="ppc-stat-icon">�</span>
                        <h3>Total Revenue</h3>
                    </div>
                    <div class="ppc-stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="ppc-stat-meta">
                        <span>From <?php echo $total_bots; ?> total detections</span>
                    </div>
<?php
/**
 * Plugin Name: Pay Per Crawl
 * Plugin URI: https://paypercrawl.tech
 * Description: Advanced AI Bot Detection & Monetization Platform - Turn Every AI Crawl Into Revenue
 * Version: 3.0.0
 * Author: PayPerCrawl.tech
 * Author URI: https://paypercrawl.tech
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pay-per-crawl
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PAYPERCRAWL_VERSION', '3.0.0');
define('PAYPERCRAWL_PLUGIN_FILE', __FILE__);
define('PAYPERCRAWL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYPERCRAWL_PLUGIN_URL', plugin_dir_url(__FILE__));

class PayPerCrawl {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    public function init() {
        // Bot detection logic here
        if (get_option('paypercrawl_detection_enabled', true)) {
            $this->detect_bot();
        }
    }
    
    public function admin_menu() {
        add_menu_page(
            'Pay Per Crawl',
            'Pay Per Crawl',
            'manage_options',
            'paypercrawl-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-money-alt',
            30
        );
    }
    
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1>🤖 Pay Per Crawl Dashboard</h1>
            <div class="ppc-dashboard">
                <div class="ppc-card">
                    <h3>Bot Detection Active</h3>
                    <p>Your site is now monitoring for AI bot traffic and earning revenue!</p>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function detect_bot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $bot_signatures = array(
            'ChatGPT' => 'ChatGPT-User',
            'Claude' => 'Claude-Web',
            'GPTBot' => 'GPTBot',
            'Google-Extended' => 'Google-Extended',
            'CCBot' => 'CCBot',
            'anthropic-ai' => 'anthropic-ai',
            'ClaudeBot' => 'ClaudeBot'
        );
        
        foreach ($bot_signatures as $bot_name => $signature) {
            if (stripos($user_agent, $signature) !== false) {
                $this->log_bot_detection($bot_name, $user_agent);
                break;
            }
        }
    }
    
    private function log_bot_detection($bot_type, $user_agent) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'bot_type' => $bot_type,
                'user_agent' => $user_agent,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'revenue' => 0.05,
                'page_url' => $_SERVER['REQUEST_URI'] ?? '',
                'detected_at' => current_time('mysql')
            )
        );
    }
    
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bot_type varchar(100) NOT NULL,
            user_agent text,
            ip_address varchar(45) NOT NULL,
            revenue decimal(10,4) NOT NULL DEFAULT 0,
            page_url text,
            detected_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function activate() {
        $this->create_tables();
        add_option('paypercrawl_detection_enabled', true);
        add_option('paypercrawl_version', PAYPERCRAWL_VERSION);
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function enqueue_scripts() {
        // Frontend scripts if needed
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'paypercrawl') === false) return;
        
        wp_enqueue_style('paypercrawl-admin', PAYPERCRAWL_PLUGIN_URL . 'assets/admin.css', [], PAYPERCRAWL_VERSION);
    }
}

// Initialize the plugin
PayPerCrawl::get_instance();
