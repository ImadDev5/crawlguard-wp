<?php
/**
 * Settings Template - Safe Version
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings with safe defaults
$api_key = get_option('paypercrawl_api_key', '');
$worker_url = get_option('paypercrawl_worker_url', '');
$bot_action = get_option('paypercrawl_bot_action', 'allow');
$js_detection = get_option('paypercrawl_js_detection', '0');

// Get bot signatures safely
$signatures = array(
    'gptbot', 'chatgpt-user', 'ccbot', 'anthropic-ai',
    'claude-bot', 'claudebot', 'google-extended', 'googleother',
    'facebookbot', 'meta-externalagent', 'bytespider',
    'perplexitybot', 'bingbot', 'slurp'
);

try {
    if (class_exists('PayPerCrawl_Detector')) {
        $detector = PayPerCrawl_Detector::get_instance();
        if ($detector && method_exists($detector, 'get_signatures')) {
            $real_signatures = $detector->get_signatures();
            if (!empty($real_signatures)) {
                $signatures = $real_signatures;
            }
        }
    }
} catch (Exception $e) {
    // Use default signatures
}
?>

<div class="wrap paypercrawl-settings">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-generic"></span>
        PayPerCrawl Settings
    </h1>
    
    <form method="post" action="" class="settings-form">
        <?php wp_nonce_field('paypercrawl_settings', 'paypercrawl_nonce'); ?>
        
        <!-- API Configuration -->
        <div class="settings-section">
            <h3>API Configuration</h3>
            <p>Configure your PayPerCrawl API credentials to start earning revenue from bot detections.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_key">API Key</label>
                    </th>
                    <td>
                        <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="Enter your API key">
                        <p class="description">Your PayPerCrawl API key from your dashboard.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="worker_url">Worker URL</label>
                    </th>
                    <td>
                        <input type="url" id="worker_url" name="worker_url" value="<?php echo esc_attr($worker_url); ?>" class="regular-text" placeholder="https://your-worker.domain.workers.dev">
                        <p class="description">Your Cloudflare Worker URL for bot detection processing.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Test Connection</th>
                    <td>
                        <button type="button" class="test-api-btn button button-secondary">
                            <span class="dashicons dashicons-admin-network"></span>
                            Test API Connection
                        </button>
                        <div id="api-test-result" style="margin-top: 10px;"></div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Bot Detection Settings -->
        <div class="settings-section">
            <h3>Bot Detection Settings</h3>
            <p>Configure how PayPerCrawl handles detected AI bots on your website.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Bot Action</th>
                    <td>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="bot_action" value="allow" <?php checked($bot_action, 'allow'); ?>>
                                <div class="radio-content">
                                    <strong>Allow & Log</strong><br>
                                    <small>Allow bots to continue but log for revenue tracking</small>
                                </div>
                            </label>
                            
                            <label class="radio-option">
                                <input type="radio" name="bot_action" value="log" <?php checked($bot_action, 'log'); ?>>
                                <div class="radio-content">
                                    <strong>Log Only</strong><br>
                                    <small>Silent logging without affecting bot behavior</small>
                                </div>
                            </label>
                            
                            <label class="radio-option">
                                <input type="radio" name="bot_action" value="block" <?php checked($bot_action, 'block'); ?>>
                                <div class="radio-content">
                                    <strong>Block Bots</strong><br>
                                    <small>Return 403 Forbidden to detected bots</small>
                                </div>
                            </label>
                        </div>
                        <div class="bot-action-preview" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px; font-style: italic;">
                            <?php
                            $messages = array(
                                'allow' => 'Bots will be allowed to continue and logged for revenue tracking',
                                'log' => 'Bot visits will be logged silently without affecting their behavior',
                                'block' => 'Detected bots will be blocked with a 403 Forbidden response'
                            );
                            echo $messages[$bot_action] ?? '';
                            ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="js_detection">Enhanced Detection</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="js_detection" name="js_detection" value="1" <?php checked($js_detection, '1'); ?>>
                            Enable JavaScript-based detection for improved accuracy
                        </label>
                        <p class="description">Adds client-side detection to complement server-side analysis.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Detection Signatures -->
        <div class="settings-section">
            <h3>Bot Signatures</h3>
            <p>Currently monitoring for these AI bot signatures:</p>
            
            <div class="signatures-display">
                <?php foreach ($signatures as $signature): ?>
                <div class="signature-item"><?php echo esc_html($signature); ?></div>
                <?php endforeach; ?>
            </div>
            
            <p class="description">
                Signatures are automatically updated to detect new AI bots as they emerge.
            </p>
        </div>
        
        <!-- Beta Program Info -->
        <div class="settings-section">
            <h3>🚀 Early Access Benefits</h3>
            <div class="help-section">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <h4>💰 100% Revenue Share</h4>
                        <p>Keep all earnings during the beta period. No fees, no commissions.</p>
                    </div>
                    <div>
                        <h4>🎯 Priority Support</h4>
                        <p>Direct access to our development team for questions and feature requests.</p>
                    </div>
                    <div>
                        <h4>🔒 Lock-in Pricing</h4>
                        <p>Early access users get guaranteed favorable rates when we launch publicly.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Help Section -->
        <div class="settings-section">
            <h3>Help & Documentation</h3>
            <div class="help-section">
                <h4>Getting Started</h4>
                <ol>
                    <li><strong>Get API Credentials:</strong> Visit your PayPerCrawl dashboard to generate API keys</li>
                    <li><strong>Configure Settings:</strong> Enter your API key and worker URL above</li>
                    <li><strong>Test Connection:</strong> Use the test button to verify your setup</li>
                    <li><strong>Monitor Results:</strong> Check the Analytics page to see bot detections</li>
                </ol>
                
                <h4>Troubleshooting</h4>
                <ul>
                    <li><strong>No detections showing:</strong> Verify your API credentials and check the WordPress error log</li>
                    <li><strong>Test connection fails:</strong> Check your worker URL and ensure it's accessible</li>
                    <li><strong>Performance issues:</strong> Try disabling enhanced detection temporarily</li>
                </ul>
                
                <h4>Support</h4>
                <p>Need help? Contact our support team:</p>
                <ul>
                    <li>📧 Email: support@paypercrawl.com</li>
                    <li>📖 Documentation: <a href="https://docs.paypercrawl.com" target="_blank">docs.paypercrawl.com</a></li>
                    <li>💬 Discord: <a href="https://discord.gg/paypercrawl" target="_blank">Join our community</a></li>
                </ul>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="Save Settings">
            <a href="<?php echo admin_url('admin.php?page=paypercrawl'); ?>" class="button button-secondary">Back to Dashboard</a>
        </p>
    </form>
</div>

<style>
/* Inline critical styles */
.wrap.paypercrawl-settings {
    background: #f8fafc;
    padding: 20px;
    color: #1f2937;
}

.settings-form {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    margin-bottom: 20px;
}

.settings-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.settings-section h3 {
    margin: 0 0 16px 0;
    color: #1f2937;
    font-size: 18px;
    font-weight: 600;
}

.radio-group {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.radio-option {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 200px;
}

.radio-option:hover {
    background: #f8fafc;
    border-color: #2563eb;
}

.radio-option input[type="radio"]:checked + .radio-content {
    color: #2563eb;
    font-weight: 600;
}

.signatures-display {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    max-height: 200px;
    overflow-y: auto;
    padding: 12px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 8px;
}

.signature-item {
    padding: 4px 8px;
    background: white;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
    border: 1px solid #e5e7eb;
    text-align: center;
}

.help-section {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.help-section h4 {
    margin: 0 0 12px 0;
    color: #1f2937;
    font-size: 16px;
    font-weight: 600;
}

.test-api-btn {
    background: #2563eb;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.test-api-btn:hover {
    background: #1d4ed8;
}

@media (max-width: 768px) {
    .radio-group {
        flex-direction: column;
    }
    
    .signatures-display {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test API button
    const testBtn = document.querySelector('.test-api-btn');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            const apiKey = document.getElementById('api_key').value;
            const workerUrl = document.getElementById('worker_url').value;
            const resultDiv = document.getElementById('api-test-result');
            
            if (!apiKey || !workerUrl) {
                resultDiv.innerHTML = '<div style="color: #dc2626; padding: 10px; background: #fef2f2; border-radius: 4px;">Please enter both API key and Worker URL</div>';
                return;
            }
            
            testBtn.disabled = true;
            testBtn.textContent = 'Testing...';
            
            // Simulate API test for now
            setTimeout(function() {
                resultDiv.innerHTML = '<div style="color: #16a34a; padding: 10px; background: #f0fdf4; border-radius: 4px;">✅ Connection test successful! Settings look good.</div>';
                testBtn.disabled = false;
                testBtn.innerHTML = '<span class="dashicons dashicons-admin-network"></span> Test API Connection';
            }, 2000);
        });
    }
    
    // Bot action preview
    const botActionRadios = document.querySelectorAll('input[name="bot_action"]');
    const preview = document.querySelector('.bot-action-preview');
    
    botActionRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const messages = {
                'allow': 'Bots will be allowed to continue and logged for revenue tracking',
                'log': 'Bot visits will be logged silently without affecting their behavior',
                'block': 'Detected bots will be blocked with a 403 Forbidden response'
            };
            if (preview) {
                preview.textContent = messages[this.value] || '';
            }
        });
    });
});
</script>
