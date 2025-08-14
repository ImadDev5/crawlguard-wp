<?php
/**
 * Admin Interface for CrawlGuard WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Admin {

    private $database;

    public function __construct() {
        $this->database = new CrawlGuard_Database();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_crawlguard_get_analytics', array($this, 'ajax_get_analytics'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'PayPerCrawl Dashboard',
            'PayPerCrawl',
            'manage_options',
            'paypercrawl',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'crawlguard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'crawlguard',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'crawlguard',
            'Settings',
            'Settings',
            'manage_options',
            'crawlguard-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>CrawlGuard WP Dashboard</h1>
            <div id="crawlguard-dashboard">
                <div class="crawlguard-loading">Loading dashboard...</div>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>CrawlGuard Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('crawlguard_settings');
                do_settings_sections('crawlguard_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function init_settings() {
        register_setting('crawlguard_settings', 'crawlguard_options');
        
        add_settings_section(
            'crawlguard_main_section',
            'Main Settings',
            array($this, 'main_section_callback'),
            'crawlguard_settings'
        );
        
        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'crawlguard_settings',
            'crawlguard_main_section'
        );
        
        add_settings_field(
            'monetization_enabled',
            'Enable Monetization',
            array($this, 'monetization_enabled_callback'),
            'crawlguard_settings',
            'crawlguard_main_section'
        );
    }
    
    public function main_section_callback() {
        echo '<p>Configure your CrawlGuard settings below.</p>';
    }
    
    public function api_key_callback() {
        $options = get_option('crawlguard_options');
        $api_key = $options['api_key'] ?? '';
        echo '<input type="text" name="crawlguard_options[api_key]" value="' . esc_attr($api_key) . '" class="regular-text" />';
    }
    
    public function monetization_enabled_callback() {
        $options = get_option('crawlguard_options');
        $enabled = $options['monetization_enabled'] ?? false;
        echo '<input type="checkbox" name="crawlguard_options[monetization_enabled]" value="1" ' . checked(1, $enabled, false) . ' />';
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'crawlguard') === false) {
            return;
        }
        
        wp_enqueue_script(
            'crawlguard-admin',
            CRAWLGUARD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            CRAWLGUARD_VERSION,
            true
        );
        
        wp_localize_script('crawlguard-admin', 'crawlguard_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crawlguard_nonce')
        ));
    }
    
    public function ajax_get_analytics() {
        check_ajax_referer('crawlguard_nonce', 'nonce');
        
        $api_client = new CrawlGuard_API_Client();
        $analytics = $api_client->get_analytics();
        
        wp_send_json_success($analytics);
    }
}
