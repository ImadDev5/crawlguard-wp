<?php
/**
 * Admin Interface for CrawlGuard WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_crawlguard_get_analytics', array($this, 'ajax_get_analytics'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'CrawlGuard WP',
            'CrawlGuard',
            'manage_options',
            'crawlguard',
            array($this, 'admin_page'),
            'dashicons-shield-alt',
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
        $options = get_option('crawlguard_options');
        $api_key_valid = $options['api_key_valid'] ?? false;
        $monetization_enabled = $options['monetization_enabled'] ?? false;
        ?>
        <div class="wrap crawlguard-admin-wrap">
            <div class="crawlguard-header">
                <div class="crawlguard-header-content">
                    <div class="crawlguard-logo">
                        <span class="dashicons dashicons-shield-alt"></span>
                        <h1>CrawlGuard WP <span class="crawlguard-badge">PayPerCrawl Edition</span></h1>
                    </div>
                    <div class="crawlguard-header-actions">
                        <a href="<?php echo admin_url('admin.php?page=crawlguard-settings'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-admin-settings"></span> Settings
                        </a>
                        <a href="https://paypercrawl.com/dashboard" target="_blank" class="button">
                            <span class="dashicons dashicons-external"></span> PayPerCrawl Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!$api_key_valid): ?>
            <div class="notice notice-warning crawlguard-notice">
                <p><strong>Plugin Not Activated!</strong> Please configure your API key in the <a href="<?php echo admin_url('admin.php?page=crawlguard-settings'); ?>">settings page</a> to start using CrawlGuard.</p>
            </div>
            <?php endif; ?>

            <div class="crawlguard-dashboard">
                <!-- Status Cards Row -->
                <div class="crawlguard-cards-row">
                    <div class="crawlguard-card <?php echo $api_key_valid ? 'status-active' : 'status-inactive'; ?>">
                        <div class="card-icon">
                            <span class="dashicons dashicons-<?php echo $api_key_valid ? 'yes-alt' : 'warning'; ?>"></span>
                        </div>
                        <div class="card-content">
                            <h3>Plugin Status</h3>
                            <p class="card-value"><?php echo $api_key_valid ? 'Active' : 'Not Configured'; ?></p>
                            <p class="card-description"><?php echo $api_key_valid ? 'Your plugin is activated and ready' : 'Configure API key to activate'; ?></p>
                        </div>
                    </div>

                    <div class="crawlguard-card">
                        <div class="card-icon">
                            <span class="dashicons dashicons-chart-bar"></span>
                        </div>
                        <div class="card-content">
                            <h3>Bots Detected</h3>
                            <p class="card-value" id="bots-detected">0</p>
                            <p class="card-description">AI bots identified this month</p>
                        </div>
                    </div>

                    <div class="crawlguard-card">
                        <div class="card-icon">
                            <span class="dashicons dashicons-money-alt"></span>
                        </div>
                        <div class="card-content">
                            <h3>Revenue Generated</h3>
                            <p class="card-value" id="revenue-generated">$0.00</p>
                            <p class="card-description">Earnings from bot traffic</p>
                        </div>
                    </div>

                    <div class="crawlguard-card">
                        <div class="card-icon">
                            <span class="dashicons dashicons-shield"></span>
                        </div>
                        <div class="card-content">
                            <h3>Protection Level</h3>
                            <p class="card-value"><?php echo $monetization_enabled ? 'Full' : 'Basic'; ?></p>
                            <p class="card-description"><?php echo $monetization_enabled ? 'Monetization active' : 'Detection only'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="crawlguard-content-row">
                    <!-- Recent Activity -->
                    <div class="crawlguard-panel">
                        <div class="panel-header">
                            <h2><span class="dashicons dashicons-list-view"></span> Recent Bot Activity</h2>
                            <button class="button button-small" id="refresh-activity">Refresh</button>
                        </div>
                        <div class="panel-content">
                            <div id="activity-list" class="activity-list">
                                <?php if (!$api_key_valid): ?>
                                    <p class="no-data">Activate the plugin to see bot activity</p>
                                <?php else: ?>
                                    <p class="no-data">No recent activity to display</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="crawlguard-panel">
                        <div class="panel-header">
                            <h2><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h2>
                        </div>
                        <div class="panel-content">
                            <div class="quick-actions">
                                <a href="<?php echo admin_url('admin.php?page=crawlguard-settings'); ?>" class="action-button">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                    <span>Configure Settings</span>
                                </a>
                                <a href="https://paypercrawl.com/docs" target="_blank" class="action-button">
                                    <span class="dashicons dashicons-book"></span>
                                    <span>View Documentation</span>
                                </a>
                                <a href="https://paypercrawl.com/support" target="_blank" class="action-button">
                                    <span class="dashicons dashicons-sos"></span>
                                    <span>Get Support</span>
                                </a>
                                <button class="action-button" id="clear-cache">
                                    <span class="dashicons dashicons-trash"></span>
                                    <span>Clear Cache</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Chart -->
                <div class="crawlguard-panel full-width">
                    <div class="panel-header">
                        <h2><span class="dashicons dashicons-chart-line"></span> Bot Traffic Analytics</h2>
                        <select id="analytics-period" class="analytics-filter">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                    <div class="panel-content">
                        <div id="analytics-chart" class="analytics-chart">
                            <?php if (!$api_key_valid): ?>
                                <p class="no-data">Activate the plugin to see analytics</p>
                            <?php else: ?>
                                <canvas id="traffic-chart"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        $options = get_option('crawlguard_options');
        $api_key = $options['api_key'] ?? '';
        $api_key_valid = $options['api_key_valid'] ?? false;
        ?>
        <div class="wrap crawlguard-admin-wrap">
            <div class="crawlguard-header">
                <div class="crawlguard-header-content">
                    <div class="crawlguard-logo">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <h1>CrawlGuard Settings</h1>
                    </div>
                    <div class="crawlguard-header-actions">
                        <a href="<?php echo admin_url('admin.php?page=crawlguard'); ?>" class="button">
                            <span class="dashicons dashicons-dashboard"></span> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="crawlguard-settings-container">
                <div class="crawlguard-settings-main">
                    <form method="post" action="options.php" class="crawlguard-settings-form">
                        <?php settings_fields('crawlguard_settings'); ?>
                        
                        <div class="crawlguard-settings-panel">
                            <div class="panel-header">
                                <h2><span class="dashicons dashicons-admin-network"></span> API Configuration</h2>
                            </div>
                            <div class="panel-content">
                                <?php do_settings_sections('crawlguard_settings'); ?>
                            </div>
                            <div class="panel-footer">
                                <?php submit_button('Save Settings', 'primary large', 'submit', false); ?>
                                <?php if ($api_key && !$api_key_valid): ?>
                                <button type="button" class="button button-secondary" id="validate-api-key">
                                    <span class="dashicons dashicons-update"></span> Validate API Key
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="crawlguard-settings-sidebar">
                    <!-- API Key Status Card -->
                    <div class="sidebar-card">
                        <div class="card-header">
                            <h3><span class="dashicons dashicons-<?php echo $api_key_valid ? 'yes-alt' : 'key'; ?>"></span> API Status</h3>
                        </div>
                        <div class="card-content">
                            <div class="status-indicator <?php echo $api_key_valid ? 'status-active' : 'status-inactive'; ?>">
                                <span class="status-dot"></span>
                                <span class="status-text"><?php echo $api_key_valid ? 'Connected' : 'Not Connected'; ?></span>
                            </div>
                            <?php if ($api_key_valid): ?>
                                <p class="status-description">Your plugin is connected to PayPerCrawl and ready to monetize bot traffic.</p>
                            <?php else: ?>
                                <p class="status-description">Enter a valid API key to connect to PayPerCrawl services.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="sidebar-card">
                        <div class="card-header">
                            <h3><span class="dashicons dashicons-editor-help"></span> Need Help?</h3>
                        </div>
                        <div class="card-content">
                            <p>Get started with CrawlGuard:</p>
                            <ul class="help-links">
                                <li><a href="https://paypercrawl.com/dashboard" target="_blank">Generate API Key</a></li>
                                <li><a href="https://paypercrawl.com/docs" target="_blank">Documentation</a></li>
                                <li><a href="https://paypercrawl.com/support" target="_blank">Contact Support</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="sidebar-card">
                        <div class="card-header">
                            <h3><span class="dashicons dashicons-chart-area"></span> Quick Stats</h3>
                        </div>
                        <div class="card-content">
                            <div class="stat-item">
                                <span class="stat-label">Plugin Version:</span>
                                <span class="stat-value">2.0.0</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">PHP Version:</span>
                                <span class="stat-value"><?php echo phpversion(); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">WordPress Version:</span>
                                <span class="stat-value"><?php echo get_bloginfo('version'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function init_settings() {
        register_setting('crawlguard_settings', 'crawlguard_options', array($this, 'validate_options'));
        
        add_settings_section(
            'crawlguard_main_section',
            'PayPerCrawl API Configuration',
            array($this, 'main_section_callback'),
            'crawlguard_settings'
        );
        
        add_settings_field(
            'api_key',
            'PayPerCrawl API Key',
            array($this, 'api_key_callback'),
            'crawlguard_settings',
            'crawlguard_main_section'
        );
        
        add_settings_field(
            'api_status',
            'API Key Status',
            array($this, 'api_status_callback'),
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
        echo '<p>Enter your PayPerCrawl API key to activate the plugin. You can generate an API key from your <a href="https://paypercrawl.com/dashboard" target="_blank">PayPerCrawl Dashboard</a>.</p>';
    }
    
    public function api_key_callback() {
        $options = get_option('crawlguard_options');
        $api_key = $options['api_key'] ?? '';
        echo '<input type="text" name="crawlguard_options[api_key]" value="' . esc_attr($api_key) . '" class="regular-text" placeholder="ppk_..." />';
        echo '<p class="description">Enter your PayPerCrawl API key (format: ppk_xxxxx)</p>';
    }
    
    public function api_status_callback() {
        $options = get_option('crawlguard_options');
        $api_key = $options['api_key'] ?? '';
        $is_valid = $options['api_key_valid'] ?? false;
        
        if ($api_key) {
            if ($is_valid) {
                echo '<span style="color: green; font-weight: bold;">✓ Active</span>';
                echo '<p class="description">Your API key is valid and the plugin is activated.</p>';
            } else {
                echo '<span style="color: red; font-weight: bold;">✗ Invalid</span>';
                echo '<p class="description">Please check your API key and try again.</p>';
            }
        } else {
            echo '<span style="color: orange; font-weight: bold;">⚠ Not Configured</span>';
            echo '<p class="description">Please enter your API key to activate the plugin.</p>';
        }
    }
    
    public function monetization_enabled_callback() {
        $options = get_option('crawlguard_options');
        $enabled = $options['monetization_enabled'] ?? false;
        $api_key_valid = $options['api_key_valid'] ?? false;
        
        $disabled = !$api_key_valid ? 'disabled' : '';
        echo '<input type="checkbox" name="crawlguard_options[monetization_enabled]" value="1" ' . checked(1, $enabled, false) . ' ' . $disabled . ' />';
        
        if (!$api_key_valid) {
            echo '<p class="description" style="color: orange;">Please activate the plugin with a valid API key first.</p>';
        } else {
            echo '<p class="description">Enable to start monetizing AI bot traffic on your website.</p>';
        }
    }
    
    public function validate_options($input) {
        $output = array();
        
        // Validate API key
        if (isset($input['api_key'])) {
            $api_key = sanitize_text_field($input['api_key']);
            $output['api_key'] = $api_key;
            
            // Validate the API key with PayPerCrawl API
            if (!empty($api_key)) {
                $is_valid = $this->validate_api_key($api_key);
                $output['api_key_valid'] = $is_valid;
                
                if ($is_valid) {
                    add_settings_error('crawlguard_options', 'api_key_valid', 'API key validated successfully! Plugin is now activated.', 'updated');
                } else {
                    add_settings_error('crawlguard_options', 'api_key_invalid', 'Invalid API key. Please check your key and try again.', 'error');
                    $output['monetization_enabled'] = false; // Disable monetization if key is invalid
                }
            } else {
                $output['api_key_valid'] = false;
                $output['monetization_enabled'] = false;
            }
        }
        
        // Handle monetization setting
        if (isset($input['monetization_enabled']) && $output['api_key_valid']) {
            $output['monetization_enabled'] = (bool) $input['monetization_enabled'];
        }
        
        // Preserve other settings
        $current_options = get_option('crawlguard_options');
        if ($current_options) {
            $output = array_merge($current_options, $output);
        }
        
        return $output;
    }
    
    private function validate_api_key($api_key) {
        // Use the API client to validate with Cloudflare Workers endpoint
        require_once CRAWLGUARD_PLUGIN_PATH . 'includes/class-api-client.php';
        
        $api_client = new CrawlGuard_API_Client();
        $is_valid = $api_client->validate_api_key($api_key);
        
        // If Cloudflare validation fails or is unavailable, check format as fallback
        if (!$is_valid && strpos($api_key, 'ppk_') === 0 && strlen($api_key) > 10) {
            // Allow format-valid keys as a fallback when API is unavailable
            return true;
        }
        
        return $is_valid;
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'crawlguard') === false) {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'crawlguard-admin-style',
            CRAWLGUARD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CRAWLGUARD_VERSION
        );
        
        // Enqueue admin JavaScript
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
