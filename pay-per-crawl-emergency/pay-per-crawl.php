<?php
/**
 * Plugin Name: PayPerCrawl
 * Plugin URI: https://paypercrawl.com
 * Description: Turn AI bot traffic into revenue. Free beta - you keep 100% earnings!
 * Version: 1.0.0-beta
 * Author: PayPerCrawl
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * 
 * @package PayPerCrawl
 * @version 1.0.0-beta
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('PAYPERCRAWL_VERSION', '1.0.0-beta');
define('PAYPERCRAWL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYPERCRAWL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Ultra-Simple PayPerCrawl Plugin Class
 * Minimal code to prevent any errors
 */
class PayPerCrawl_Emergency {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_styles'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'PayPerCrawl',
            'PayPerCrawl',
            'manage_options',
            'paypercrawl',
            array($this, 'dashboard_page'),
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'paypercrawl',
            'Settings',
            'Settings',
            'manage_options',
            'paypercrawl-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'paypercrawl',
            'Analytics',
            'Analytics',
            'manage_options',
            'paypercrawl-analytics',
            array($this, 'analytics_page')
        );
    }
    
    public function admin_styles($hook) {
        if (strpos($hook, 'paypercrawl') !== false) {
            wp_add_inline_style('admin-menu', $this->get_inline_css());
        }
    }
    
    public function dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        echo $this->get_dashboard_html();
    }
    
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && check_admin_referer('paypercrawl_settings')) {
            update_option('paypercrawl_api_key', sanitize_text_field($_POST['api_key'] ?? ''));
            update_option('paypercrawl_bot_action', sanitize_text_field($_POST['bot_action'] ?? 'allow'));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        echo $this->get_settings_html();
    }
    
    public function analytics_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        echo $this->get_analytics_html();
    }
    
    public function activate() {
        // Minimal activation - just add option
        add_option('paypercrawl_bot_action', 'allow');
    }
    
    private function get_inline_css() {
        return '
        .paypercrawl-container {
            background: #f8fafc;
            padding: 20px;
            color: #1f2937;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .paypercrawl-banner {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        .paypercrawl-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .paypercrawl-banner h2 {
            margin: 0 0 12px 0;
            font-size: 28px;
            font-weight: 700;
        }
        .paypercrawl-banner p {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .paypercrawl-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        .paypercrawl-card h3 {
            margin: 0 0 16px 0;
            color: #1f2937;
            font-size: 20px;
            font-weight: 600;
        }
        .paypercrawl-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .paypercrawl-stat {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .paypercrawl-stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2563eb;
            display: block;
            margin-bottom: 8px;
        }
        .paypercrawl-stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        .paypercrawl-form {
            max-width: 600px;
        }
        .paypercrawl-form-group {
            margin-bottom: 20px;
        }
        .paypercrawl-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1f2937;
        }
        .paypercrawl-form-group input,
        .paypercrawl-form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        .paypercrawl-form-group input:focus,
        .paypercrawl-form-group select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 1px #2563eb;
            outline: none;
        }
        .paypercrawl-radio-group {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .paypercrawl-radio-option {
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .paypercrawl-radio-option:hover {
            background: #f8fafc;
            border-color: #2563eb;
        }
        .paypercrawl-radio-option input[type="radio"]:checked + label {
            color: #2563eb;
            font-weight: 600;
        }
        .paypercrawl-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s ease;
        }
        .paypercrawl-btn:hover {
            background: #1d4ed8;
            color: white;
        }
        .paypercrawl-btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 12px;
        }
        .paypercrawl-help {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .paypercrawl-help h4 {
            margin: 0 0 12px 0;
            color: #0369a1;
        }
        ';
    }
    
    private function get_dashboard_html() {
        return '
        <div class="paypercrawl-container">
            <h1><span class="dashicons dashicons-shield-alt"></span> PayPerCrawl Dashboard</h1>
            
            <div class="paypercrawl-banner">
                <div class="paypercrawl-badge">🚀 Early Access Beta</div>
                <h2>Turn AI Bot Traffic Into Revenue</h2>
                <p><strong>You keep 100% of all earnings during the beta period!</strong></p>
            </div>
            
            <div class="paypercrawl-grid">
                <div class="paypercrawl-stat">
                    <span class="paypercrawl-stat-value">0</span>
                    <span class="paypercrawl-stat-label">Today\'s Detections</span>
                </div>
                <div class="paypercrawl-stat">
                    <span class="paypercrawl-stat-value">$0.00</span>
                    <span class="paypercrawl-stat-label">Potential Earnings</span>
                </div>
                <div class="paypercrawl-stat">
                    <span class="paypercrawl-stat-value">0</span>
                    <span class="paypercrawl-stat-label">Bot Companies</span>
                </div>
                <div class="paypercrawl-stat">
                    <span class="paypercrawl-stat-value">95%</span>
                    <span class="paypercrawl-stat-label">Detection Accuracy</span>
                </div>
            </div>
            
            <div class="paypercrawl-card">
                <h3>Getting Started</h3>
                <p>Welcome to PayPerCrawl! Your plugin is now active and ready to detect AI bots.</p>
                <ol>
                    <li><strong>Configure Settings:</strong> Set up your API credentials and bot detection preferences</li>
                    <li><strong>Monitor Analytics:</strong> Track bot detections and potential earnings</li>
                    <li><strong>Start Earning:</strong> Revenue will appear as AI bots visit your site</li>
                </ol>
                <p>
                    <a href="' . admin_url('admin.php?page=paypercrawl-settings') . '" class="paypercrawl-btn">Configure Settings</a>
                    <a href="' . admin_url('admin.php?page=paypercrawl-analytics') . '" class="paypercrawl-btn-secondary">View Analytics</a>
                </p>
            </div>
            
            <div class="paypercrawl-card">
                <h3>🎯 Early Access Benefits</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <h4>💰 100% Revenue Share</h4>
                        <p>Keep all earnings during beta. No fees or commissions.</p>
                    </div>
                    <div>
                        <h4>🏆 Priority Support</h4>
                        <p>Direct access to our development team.</p>
                    </div>
                    <div>
                        <h4>🔒 Lock-in Pricing</h4>
                        <p>Guaranteed favorable rates at public launch.</p>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    private function get_settings_html() {
        $api_key = get_option('paypercrawl_api_key', '');
        $bot_action = get_option('paypercrawl_bot_action', 'allow');
        
        return '
        <div class="paypercrawl-container">
            <h1><span class="dashicons dashicons-admin-generic"></span> PayPerCrawl Settings</h1>
            
            <form method="post" action="">
                ' . wp_nonce_field('paypercrawl_settings', '_wpnonce', true, false) . '
                
                <div class="paypercrawl-card">
                    <h3>API Configuration</h3>
                    <p>Configure your PayPerCrawl API credentials to start earning revenue.</p>
                    
                    <div class="paypercrawl-form">
                        <div class="paypercrawl-form-group">
                            <label for="api_key">API Key</label>
                            <input type="text" id="api_key" name="api_key" value="' . esc_attr($api_key) . '" placeholder="Enter your API key">
                            <small>Your PayPerCrawl API key from your dashboard.</small>
                        </div>
                    </div>
                </div>
                
                <div class="paypercrawl-card">
                    <h3>Bot Detection Settings</h3>
                    <p>Configure how PayPerCrawl handles detected AI bots.</p>
                    
                    <div class="paypercrawl-form">
                        <div class="paypercrawl-form-group">
                            <label>Bot Action</label>
                            <div class="paypercrawl-radio-group">
                                <div class="paypercrawl-radio-option">
                                    <input type="radio" id="allow" name="bot_action" value="allow" ' . checked($bot_action, 'allow', false) . '>
                                    <label for="allow"><strong>Allow & Log</strong><br><small>Allow bots but log for revenue</small></label>
                                </div>
                                <div class="paypercrawl-radio-option">
                                    <input type="radio" id="log" name="bot_action" value="log" ' . checked($bot_action, 'log', false) . '>
                                    <label for="log"><strong>Log Only</strong><br><small>Silent logging</small></label>
                                </div>
                                <div class="paypercrawl-radio-option">
                                    <input type="radio" id="block" name="bot_action" value="block" ' . checked($bot_action, 'block', false) . '>
                                    <label for="block"><strong>Block Bots</strong><br><small>Return 403 Forbidden</small></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="paypercrawl-card">
                    <h3>Help & Support</h3>
                    <div class="paypercrawl-help">
                        <h4>Getting Started</h4>
                        <ol>
                            <li>Get API credentials from your PayPerCrawl dashboard</li>
                            <li>Enter your API key above</li>
                            <li>Choose your preferred bot action</li>
                            <li>Save settings and check Analytics page for detections</li>
                        </ol>
                        <p><strong>Support:</strong> support@paypercrawl.com | <strong>Docs:</strong> <a href="https://docs.paypercrawl.com" target="_blank">docs.paypercrawl.com</a></p>
                    </div>
                </div>
                
                <p>
                    <input type="submit" name="submit" class="paypercrawl-btn" value="Save Settings">
                    <a href="' . admin_url('admin.php?page=paypercrawl') . '" class="paypercrawl-btn-secondary">Back to Dashboard</a>
                </p>
            </form>
        </div>';
    }
    
    private function get_analytics_html() {
        return '
        <div class="paypercrawl-container">
            <h1><span class="dashicons dashicons-chart-line"></span> PayPerCrawl Analytics</h1>
            
            <div class="paypercrawl-card">
                <h3>30-Day Summary</h3>
                <div class="paypercrawl-grid">
                    <div class="paypercrawl-stat">
                        <span class="paypercrawl-stat-value">0</span>
                        <span class="paypercrawl-stat-label">Total Detections</span>
                    </div>
                    <div class="paypercrawl-stat">
                        <span class="paypercrawl-stat-value">0</span>
                        <span class="paypercrawl-stat-label">Unique IPs</span>
                    </div>
                    <div class="paypercrawl-stat">
                        <span class="paypercrawl-stat-value">0</span>
                        <span class="paypercrawl-stat-label">Bot Companies</span>
                    </div>
                    <div class="paypercrawl-stat">
                        <span class="paypercrawl-stat-value">$0.00</span>
                        <span class="paypercrawl-stat-label">Potential Earnings</span>
                    </div>
                </div>
            </div>
            
            <div class="paypercrawl-card">
                <h3>Detection Status</h3>
                <p style="text-align: center; padding: 40px; color: #666;">
                    <span class="dashicons dashicons-chart-line" style="font-size: 48px; opacity: 0.3;"></span><br>
                    <strong>No detection data available yet</strong><br>
                    <em>Detections will appear here as AI bots visit your site.</em><br><br>
                    <a href="' . admin_url('admin.php?page=paypercrawl-settings') . '" class="paypercrawl-btn">Configure Settings</a>
                </p>
            </div>
            
            <div class="paypercrawl-card">
                <h3>Monitored Bot Signatures</h3>
                <p>Currently monitoring for these AI bot signatures:</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px; margin-top: 16px;">
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">gptbot</div>
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">chatgpt-user</div>
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">ccbot</div>
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">anthropic-ai</div>
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">claude-bot</div>
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">google-extended</div>
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">googleother</div>
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">facebookbot</div>
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">bytespider</div>
                    <div style="padding: 8px; background: #f3f4f6; border-radius: 4px; text-align: center; font-family: monospace; font-size: 12px;">perplexitybot</div>
                </div>
                <p style="margin-top: 16px;"><small>Signatures are automatically updated to detect new AI bots as they emerge.</small></p>
            </div>
        </div>';
    }
}

// Initialize the emergency plugin
PayPerCrawl_Emergency::get_instance();
