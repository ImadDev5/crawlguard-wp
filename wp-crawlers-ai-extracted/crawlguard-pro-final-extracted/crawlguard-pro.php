<?php
/**
 * Plugin Name: CrawlGuard WP Pro
 * Plugin URI:     private function load_dependencies() {
        // Include all required files
        $includes = array(
            'class-config.php',
            'class-compatibility-checker.php',
            'class-error-logger.php',
            'class-ml-bot-detector.php',
            'class-payment-handler.php',
            'class-startup-manager.php',
            'class-admin.php',
            'class-api-client.php',
            'class-bot-detector.php',
            'class-frontend.php',
        );
        
        foreach ($includes as $filename) {
            $filepath = CRAWLGUARD_PLUGIN_PATH . 'includes/' . $filename;
            if (file_exists($filepath)) {
                require_once $filepath;teriorsstudio.com
 * Description: AI content monetization and bot detection for WordPress. Turn AI bot traffic into revenue with intelligent content protection and full features.
 * Version: 2.0.0
 * Author: CrawlGuard Team
 * Author URI: https://creativeinteriorsstudio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: crawlguard-wp
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('CRAWLGUARD_VERSION', '2.0.0');
define('CRAWLGUARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRAWLGUARD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRAWLGUARD_PLUGIN_FILE', __FILE__);

// Also define PRO constants for backward compatibility
define('CRAWLGUARD_PRO_VERSION', '2.0.0');
define('CRAWLGUARD_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRAWLGUARD_PRO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRAWLGUARD_PRO_PLUGIN_FILE', __FILE__);

// Main plugin class
class CrawlGuardWPPro {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('crawlguard-wp', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Include all required files
        $includes = array(
            'class-config.php',
            'class-compatibility-checker.php',
            'class-error-logger.php',
            'class-ml-bot-detector.php',
            'class-payment-handler.php',
            'class-admin.php',
            'class-api-client.php',
            'class-bot-detector.php',
            'class-frontend.php',
        );
        
        foreach ($includes as $filename) {
            $filepath = CRAWLGUARD_PLUGIN_PATH . 'includes/' . $filename;
            if (file_exists($filepath)) {
                require_once $filepath;
            }
        }
    }
    
    private function init_hooks() {
        // Initialize core systems first
        if (class_exists('CrawlGuard_Compatibility_Checker')) {
            $compatibility = new CrawlGuard_Compatibility_Checker();
            if (!$compatibility->check_requirements()) {
                return; // Stop initialization if requirements not met
            }
        }
        
        // Initialize startup manager for business metrics
        if (class_exists('CrawlGuard_Startup_Manager')) {
            new CrawlGuard_Startup_Manager();
        }
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Initialize admin interface
        if (is_admin() && class_exists('CrawlGuard_Admin')) {
            new CrawlGuard_Admin();
        }
        
        if (!is_admin() && class_exists('CrawlGuard_Frontend')) {
            new CrawlGuard_Frontend();
        }
        
        // Initialize bot detection
        if (class_exists('CrawlGuard_Bot_Detector')) {
            new CrawlGuard_Bot_Detector();
        }
        
        // Add AJAX handlers
        add_action('wp_ajax_crawlguard_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_crawlguard_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_crawlguard_get_analytics', array($this, 'ajax_get_analytics'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'CrawlGuard WP Pro',
            'CrawlGuard Pro',
            'manage_options',
            'crawlguard-pro',
            array($this, 'admin_page'),
            'dashicons-shield',
            30
        );
    }
    
    public function admin_page() {
        // Use enhanced admin class if available
        if (class_exists('CrawlGuard_Admin')) {
            $admin = new CrawlGuard_Admin();
            if (method_exists($admin, 'render_dashboard')) {
                $admin->render_dashboard();
                return;
            }
        }
        
        // Fallback basic dashboard
        ?>
        <div class="wrap">
            <h1>CrawlGuard WP Pro</h1>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0;">
                <h2>🛡️ AI Bot Protection Active</h2>
                <p>Your website is now protected against AI content scraping bots.</p>
                
                <h3>Quick Stats</h3>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'crawlguard_logs';
                $total_requests = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = CURDATE()");
                $bot_detections = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE bot_detected = 1 AND DATE(timestamp) = CURDATE()");
                $revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table_name WHERE DATE(timestamp) = CURDATE()");
                ?>
                
                <ul>
                    <li><strong>Total Requests Today:</strong> <?php echo intval($total_requests); ?></li>
                    <li><strong>AI Bots Detected:</strong> <?php echo intval($bot_detections); ?></li>
                    <li><strong>Revenue Generated:</strong> $<?php echo number_format(floatval($revenue), 4); ?></li>
                </ul>
                
                <h3>API Status</h3>
                <button id="test-connection" class="button button-primary">Test API Connection</button>
                <div id="connection-result"></div>
            </div>
        </div>
        
        <script>
        document.getElementById('test-connection').addEventListener('click', function() {
            const button = this;
            const result = document.getElementById('connection-result');
            
            button.disabled = true;
            button.textContent = 'Testing...';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=crawlguard_test_connection&nonce=' + '<?php echo wp_create_nonce("crawlguard_nonce"); ?>'
            })
            .then(response => response.json())
            .then(data => {
                result.innerHTML = '<p style="color: ' + (data.success ? 'green' : 'red') + ';">' + 
                    (data.success ? '✅ ' + data.data : '❌ ' + data.data) + '</p>';
                button.disabled = false;
                button.textContent = 'Test API Connection';
            });
        });
        </script>
        <?php
    }
    
    public function detect_bots() {
        if (class_exists('CrawlGuard_Bot_Detector')) {
            $detector = new CrawlGuard_Bot_Detector();
            // Bot detection will be handled by the class
        }
    }
    
    public function ajax_test_connection() {
        check_ajax_referer('crawlguard_nonce', 'nonce');
        
        $api_url = 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/status';
        $response = wp_remote_get($api_url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        } else {
            $body = wp_remote_retrieve_body($response);
            wp_send_json_success('API Connected Successfully! ' . $body);
        }
    }
    
    public function ajax_get_analytics() {
        check_ajax_referer('crawlguard_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        $analytics = array(
            'total_requests' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'bot_detections' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE bot_detected = 1"),
            'total_revenue' => $wpdb->get_var("SELECT SUM(revenue) FROM $table_name"),
            'recent_activity' => $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 10")
        );
        
        wp_send_json_success($analytics);
    }
    
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('crawlguard_cleanup');
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'crawlguard_logs';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            bot_detected tinyint(1) DEFAULT 0,
            bot_type varchar(50),
            action_taken varchar(20),
            revenue decimal(10,4) DEFAULT 0.0000,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        // Set options for the working system
        add_option('crawlguard_pro_options', array(
            'api_key' => 'cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx',
            'api_url' => 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1',
            'monetization_enabled' => true,
            'detection_level' => 'high',
            'price_per_request' => 0.001
        ));
        
        // Also set crawlguard_options for compatibility with admin class
        add_option('crawlguard_options', array(
            'api_key' => 'cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx',
            'site_id' => 'site_' . substr(md5(get_site_url()), 0, 12),
            'monetization_enabled' => true,
            'api_url' => 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1'
        ));
    }
}

// Initialize the plugin
CrawlGuardWPPro::get_instance();

