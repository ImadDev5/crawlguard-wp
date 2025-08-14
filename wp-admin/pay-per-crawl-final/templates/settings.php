<?php
/**
 * Settings Template
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$api_key = get_option('paypercrawl_api_key', '');
$worker_url = get_option('paypercrawl_worker_url', '');
$bot_action = get_option('paypercrawl_bot_action', 'allow');
$js_detection = get_option('paypercrawl_js_detection', '0');
?>

<div class="wrap paypercrawl-settings">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        PayPerCrawl Settings
    </h1>
    
    <div class="settings-container">
        <form method="post" action="">
            <?php wp_nonce_field('paypercrawl_settings', 'paypercrawl_nonce'); ?>
            
            <!-- API Configuration -->
            <div class="settings-section">
                <h2>API Configuration</h2>
                <p class="description">Connect to PayPerCrawl API for advanced features and revenue tracking.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_key">API Key</label>
                        </th>
                        <td>
                            <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                            <p class="description">Your PayPerCrawl API key. <a href="https://paypercrawl.com/api" target="_blank">Get your API key</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="worker_url">Cloudflare Worker URL</label>
                        </th>
                        <td>
                            <input type="url" id="worker_url" name="worker_url" value="<?php echo esc_attr($worker_url); ?>" class="regular-text" />
                            <p class="description">Your Cloudflare Worker URL for edge detection. <a href="https://paypercrawl.com/cloudflare" target="_blank">Setup guide</a></p>
                        </td>
                    </tr>
                </table>
                
                <?php if (!empty($api_key)): ?>
                <div class="api-status">
                    <button type="button" class="button button-secondary" id="test-api">Test API Connection</button>
                    <div id="api-test-result"></div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Detection Settings -->
            <div class="settings-section">
                <h2>Detection Settings</h2>
                <p class="description">Configure how PayPerCrawl handles detected bots.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Bot Action</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="bot_action" value="allow" <?php checked($bot_action, 'allow'); ?> />
                                    <strong>Allow</strong> - Log bots but allow access (recommended for beta)
                                </label><br />
                                <label>
                                    <input type="radio" name="bot_action" value="block" <?php checked($bot_action, 'block'); ?> />
                                    <strong>Block</strong> - Block detected bots with 403 status
                                </label><br />
                                <p class="description">Note: Monetization options will be available in the full version.</p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="js_detection">JavaScript Detection</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="js_detection" name="js_detection" value="1" <?php checked($js_detection, '1'); ?> />
                                Enable advanced JavaScript-based bot detection
                            </label>
                            <p class="description">Adds extra detection layer using JavaScript challenges (may slow down page load slightly).</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Data & Privacy -->
            <div class="settings-section">
                <h2>Data & Privacy</h2>
                <p class="description">Control data collection and privacy settings.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Data Retention</th>
                        <td>
                            <select name="data_retention">
                                <option value="30">30 days</option>
                                <option value="90" selected>90 days</option>
                                <option value="365">1 year</option>
                                <option value="0">Forever</option>
                            </select>
                            <p class="description">How long to keep detection logs. Older data will be automatically deleted.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Allow Tracking</th>
                        <td>
                            <label>
                                <input type="checkbox" name="allow_tracking" value="1" <?php checked(get_option('paypercrawl_allow_tracking', '0'), '1'); ?> />
                                Send anonymous usage statistics to help improve PayPerCrawl
                            </label>
                            <p class="description">No personal data is collected. Only plugin version, detection counts, and basic site info.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Bot Signatures -->
            <div class="settings-section">
                <h2>Bot Signatures</h2>
                <p class="description">Current bot detection signatures loaded in the system.</p>
                
                <?php
                $detector = PayPerCrawl_Detector::get_instance();
                $signatures = $detector->get_bot_signatures();
                ?>
                
                <div class="bot-signatures-list">
                    <?php foreach ($signatures as $bot => $info): ?>
                    <div class="signature-item">
                        <span class="bot-name"><?php echo esc_html($bot); ?></span>
                        <span class="bot-company"><?php echo esc_html($info['company']); ?></span>
                        <span class="confidence-badge confidence-<?php echo $info['confidence'] >= 80 ? 'high' : ($info['confidence'] >= 60 ? 'medium' : 'low'); ?>">
                            <?php echo $info['confidence']; ?>%
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <p class="description">
                    <strong><?php echo count($signatures); ?> bot signatures</strong> loaded. 
                    Signatures are automatically updated with plugin updates.
                </p>
            </div>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings" />
            </p>
        </form>
        
        <!-- Help Section -->
        <div class="settings-section help-section">
            <h2>Need Help?</h2>
            <div class="help-grid">
                <div class="help-item">
                    <h3>📚 Documentation</h3>
                    <p>Complete setup and configuration guide.</p>
                    <a href="https://paypercrawl.com/docs" target="_blank" class="button button-secondary">View Docs</a>
                </div>
                <div class="help-item">
                    <h3>💬 Support</h3>
                    <p>Get help from our support team.</p>
                    <a href="https://paypercrawl.com/support" target="_blank" class="button button-secondary">Contact Support</a>
                </div>
                <div class="help-item">
                    <h3>🚀 API Access</h3>
                    <p>Get your API key for advanced features.</p>
                    <a href="https://paypercrawl.com/api" target="_blank" class="button button-secondary">Get API Key</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test API Connection
    document.getElementById('test-api')?.addEventListener('click', function() {
        const button = this;
        const result = document.getElementById('api-test-result');
        
        button.textContent = 'Testing...';
        button.disabled = true;
        
        jQuery.post(paypercrawl_ajax.ajax_url, {
            action: 'paypercrawl_test_api',
            nonce: paypercrawl_ajax.nonce
        }, function(response) {
            if (response.success) {
                result.innerHTML = '<div class="notice notice-success inline"><p>✅ API connection successful!</p></div>';
            } else {
                result.innerHTML = '<div class="notice notice-error inline"><p>❌ API connection failed: ' + response.data.message + '</p></div>';
            }
            
            button.textContent = 'Test API Connection';
            button.disabled = false;
        });
    });
    
    // Show/hide API key
    const apiKeyField = document.getElementById('api_key');
    if (apiKeyField) {
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'button button-secondary';
        toggleButton.textContent = 'Show';
        toggleButton.style.marginLeft = '10px';
        
        toggleButton.addEventListener('click', function() {
            if (apiKeyField.type === 'password') {
                apiKeyField.type = 'text';
                toggleButton.textContent = 'Hide';
            } else {
                apiKeyField.type = 'password';
                toggleButton.textContent = 'Show';
            }
        });
        
        apiKeyField.parentNode.appendChild(toggleButton);
    }
});
</script>
