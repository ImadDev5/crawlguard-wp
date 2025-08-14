<?php
/**
 * License Admin Page
 * WordPress admin interface for license management
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * License Admin Page Class
 */
class PayPerCrawl_License_Admin_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_license_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_paypercrawl_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_paypercrawl_deactivate_license', array($this, 'ajax_deactivate_license'));
        add_action('wp_ajax_paypercrawl_validate_license', array($this, 'ajax_validate_license'));
    }
    
    /**
     * Add license menu to WordPress admin
     */
    public function add_license_menu() {
        add_submenu_page(
            'paypercrawl-dashboard',
            __('License Management', 'paypercrawl'),
            __('License', 'paypercrawl'),
            'manage_options',
            'paypercrawl-license',
            array($this, 'render_license_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'paypercrawl_page_paypercrawl-license') {
            return;
        }
        
        wp_enqueue_style(
            'paypercrawl-license-admin',
            plugin_dir_url(__FILE__) . 'css/license-admin.css',
            array(),
            PAYPERCRAWL_VERSION
        );
        
        wp_enqueue_script(
            'paypercrawl-license-admin',
            plugin_dir_url(__FILE__) . 'js/license-admin.js',
            array('jquery'),
            PAYPERCRAWL_VERSION,
            true
        );
        
        wp_localize_script('paypercrawl-license-admin', 'paypercrawl_license', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('paypercrawl_license_nonce'),
            'strings' => array(
                'activating' => __('Activating...', 'paypercrawl'),
                'deactivating' => __('Deactivating...', 'paypercrawl'),
                'validating' => __('Validating...', 'paypercrawl'),
                'error' => __('An error occurred. Please try again.', 'paypercrawl'),
                'confirm_deactivate' => __('Are you sure you want to deactivate this license?', 'paypercrawl')
            )
        ));
    }
    
    /**
     * Render the license management page
     */
    public function render_license_page() {
        global $paypercrawl_license_manager;
        
        // Get current license data
        $license_key = get_option('paypercrawl_license_key', '');
        $license_data = $paypercrawl_license_manager->validate_license();
        
        ?>
        <div class="wrap paypercrawl-license-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php $this->render_license_status_card($license_data); ?>
            
            <div class="paypercrawl-license-container">
                <div class="paypercrawl-license-form-section">
                    <h2><?php _e('License Activation', 'paypercrawl'); ?></h2>
                    
                    <form id="paypercrawl-license-form" method="post">
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="license_key"><?php _e('License Key', 'paypercrawl'); ?></label>
                                    </th>
                                    <td>
                                        <input 
                                            type="text" 
                                            id="license_key" 
                                            name="license_key" 
                                            value="<?php echo esc_attr($license_key); ?>"
                                            class="regular-text"
                                            placeholder="XXXX-XXXX-XXXX-XXXX"
                                            <?php echo $license_data['valid'] ? 'readonly' : ''; ?>
                                        />
                                        <p class="description">
                                            <?php _e('Enter your license key to activate premium features.', 'paypercrawl'); ?>
                                        </p>
                                    </td>
                                </tr>
                                
                                <?php if ($license_data['valid']): ?>
                                <tr>
                                    <th scope="row"><?php _e('Status', 'paypercrawl'); ?></th>
                                    <td>
                                        <span class="license-status license-status-active">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php _e('Active', 'paypercrawl'); ?>
                                        </span>
                                        <?php if (isset($license_data['status']) && $license_data['status'] === 'grace_period'): ?>
                                            <span class="license-status license-status-warning">
                                                <?php _e('Grace Period', 'paypercrawl'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('License Tier', 'paypercrawl'); ?></th>
                                    <td>
                                        <strong><?php echo esc_html(ucfirst($license_data['tier'])); ?></strong>
                                    </td>
                                </tr>
                                
                                <?php if (isset($license_data['validUntil'])): ?>
                                <tr>
                                    <th scope="row"><?php _e('Expires', 'paypercrawl'); ?></th>
                                    <td>
                                        <?php 
                                        $expires = strtotime($license_data['validUntil']);
                                        echo esc_html(date_i18n(get_option('date_format'), $expires));
                                        
                                        $days_left = ($expires - time()) / (24 * 60 * 60);
                                        if ($days_left > 0 && $days_left <= 30) {
                                            echo ' <span class="license-expiry-warning">';
                                            printf(__('(%d days remaining)', 'paypercrawl'), (int)$days_left);
                                            echo '</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php else: ?>
                                <tr>
                                    <th scope="row"><?php _e('Status', 'paypercrawl'); ?></th>
                                    <td>
                                        <span class="license-status license-status-inactive">
                                            <span class="dashicons dashicons-warning"></span>
                                            <?php _e('Inactive', 'paypercrawl'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <div class="license-actions">
                            <?php if ($license_data['valid']): ?>
                                <button type="button" id="deactivate-license" class="button button-secondary">
                                    <?php _e('Deactivate License', 'paypercrawl'); ?>
                                </button>
                                <button type="button" id="validate-license" class="button button-secondary">
                                    <?php _e('Revalidate License', 'paypercrawl'); ?>
                                </button>
                            <?php else: ?>
                                <button type="button" id="activate-license" class="button button-primary">
                                    <?php _e('Activate License', 'paypercrawl'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div id="license-message" class="license-message" style="display: none;"></div>
                    </form>
                </div>
                
                <div class="paypercrawl-license-info-section">
                    <?php $this->render_features_card($license_data); ?>
                    <?php $this->render_upgrade_card($license_data); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render license status card
     */
    private function render_license_status_card($license_data) {
        ?>
        <div class="paypercrawl-status-card <?php echo $license_data['valid'] ? 'status-active' : 'status-inactive'; ?>">
            <div class="status-icon">
                <?php if ($license_data['valid']): ?>
                    <span class="dashicons dashicons-shield-alt"></span>
                <?php else: ?>
                    <span class="dashicons dashicons-lock"></span>
                <?php endif; ?>
            </div>
            <div class="status-content">
                <h2>
                    <?php 
                    if ($license_data['valid']) {
                        printf(__('License Active - %s Plan', 'paypercrawl'), ucfirst($license_data['tier']));
                    } else {
                        _e('License Inactive', 'paypercrawl');
                    }
                    ?>
                </h2>
                <p><?php echo esc_html($license_data['message']); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render features card
     */
    private function render_features_card($license_data) {
        $tier = $license_data['valid'] ? $license_data['tier'] : 'free';
        $features = isset($license_data['features']) ? $license_data['features'] : array();
        
        ?>
        <div class="paypercrawl-card">
            <h3><?php _e('Your Features', 'paypercrawl'); ?></h3>
            
            <ul class="feature-list">
                <?php if ($tier === 'free'): ?>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Bot Detection', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Analytics Dashboard', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('1,000 API Calls/Day', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-no"></span> <?php _e('Monetization Engine', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-no"></span> <?php _e('Advanced Rules', 'paypercrawl'); ?></li>
                    
                <?php elseif ($tier === 'pro'): ?>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Bot Detection', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Analytics Dashboard', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Full Monetization', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Stripe Integration', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Advanced Rules', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('10,000 API Calls/Day', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Priority Support', 'paypercrawl'); ?></li>
                    
                <?php elseif ($tier === 'business'): ?>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Everything in Pro', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Multi-site Support (5 sites)', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('100,000 API Calls/Day', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Custom Pricing Rules', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('White Label Options', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Advanced Reporting', 'paypercrawl'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Phone & Email Support', 'paypercrawl'); ?></li>
                <?php endif; ?>
            </ul>
            
            <?php if (isset($features['api_calls'])): ?>
                <div class="usage-meter">
                    <h4><?php _e('API Usage Today', 'paypercrawl'); ?></h4>
                    <?php
                    $limit = isset($features['api_calls']['limit']) ? $features['api_calls']['limit'] : 1000;
                    $used = 0; // TODO: Get from usage tracking
                    $percentage = ($used / $limit) * 100;
                    ?>
                    <div class="meter-bar">
                        <div class="meter-fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                    </div>
                    <p class="meter-text"><?php printf(__('%d / %d calls', 'paypercrawl'), $used, $limit); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render upgrade card
     */
    private function render_upgrade_card($license_data) {
        $tier = $license_data['valid'] ? $license_data['tier'] : 'free';
        
        if ($tier === 'business') {
            return; // Already on highest tier
        }
        
        ?>
        <div class="paypercrawl-card upgrade-card">
            <h3>
                <?php 
                if ($tier === 'free') {
                    _e('Upgrade to Pro', 'paypercrawl');
                } else {
                    _e('Upgrade to Business', 'paypercrawl');
                }
                ?>
            </h3>
            
            <?php if ($tier === 'free'): ?>
                <p><?php _e('Unlock the full potential of PayPerCrawl with Pro features:', 'paypercrawl'); ?></p>
                <ul>
                    <li><?php _e('✅ Full monetization engine', 'paypercrawl'); ?></li>
                    <li><?php _e('✅ Automated Stripe payouts', 'paypercrawl'); ?></li>
                    <li><?php _e('✅ Advanced bot rules', 'paypercrawl'); ?></li>
                    <li><?php _e('✅ 10x more API calls', 'paypercrawl'); ?></li>
                    <li><?php _e('✅ Priority support', 'paypercrawl'); ?></li>
                </ul>
                <div class="upgrade-price">
                    <span class="price">$15</span>
                    <span class="period"><?php _e('/month', 'paypercrawl'); ?></span>
                </div>
            <?php else: ?>
                <p><?php _e('Scale your business with advanced features:', 'paypercrawl'); ?></p>
                <ul>
                    <li><?php _e('✅ Manage up to 5 sites', 'paypercrawl'); ?></li>
                    <li><?php _e('✅ 100,000 API calls/day', 'paypercrawl'); ?></li>
                    <li><?php _e('✅ Custom pricing rules', 'paypercrawl'); ?></li>
                    <li><?php _e('✅ White label options', 'paypercrawl'); ?></li>
                    <li><?php _e('✅ Phone support', 'paypercrawl'); ?></li>
                </ul>
                <div class="upgrade-price">
                    <span class="price">$50</span>
                    <span class="period"><?php _e('/month', 'paypercrawl'); ?></span>
                </div>
            <?php endif; ?>
            
            <a href="https://crawlguard.com/pricing" target="_blank" class="button button-primary button-hero">
                <?php _e('Upgrade Now', 'paypercrawl'); ?>
            </a>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for license activation
     */
    public function ajax_activate_license() {
        check_ajax_referer('paypercrawl_license_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'paypercrawl'));
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        
        if (empty($license_key)) {
            wp_send_json_error(array(
                'message' => __('Please enter a license key', 'paypercrawl')
            ));
        }
        
        global $paypercrawl_license_manager;
        $result = $paypercrawl_license_manager->activate_license($license_key);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for license deactivation
     */
    public function ajax_deactivate_license() {
        check_ajax_referer('paypercrawl_license_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'paypercrawl'));
        }
        
        global $paypercrawl_license_manager;
        $result = $paypercrawl_license_manager->deactivate_license();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for license validation
     */
    public function ajax_validate_license() {
        check_ajax_referer('paypercrawl_license_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'paypercrawl'));
        }
        
        global $paypercrawl_license_manager;
        $result = $paypercrawl_license_manager->validate_license(null, true);
        
        if ($result['valid']) {
            wp_send_json_success(array(
                'message' => __('License validated successfully', 'paypercrawl'),
                'data' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message'],
                'data' => $result
            ));
        }
    }
}

// Initialize the admin page
new PayPerCrawl_License_Admin_Page();
