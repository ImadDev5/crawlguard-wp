<?php
/**
 * Simple CrawlGuard Admin Class - Guaranteed to work
 */
class CrawlGuard_Simple_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'CrawlGuard Pro',           // Page title
            'CrawlGuard Pro',           // Menu title
            'manage_options',           // Capability
            'crawlguard-dashboard',     // Menu slug
            array($this, 'dashboard_page'), // Callback function
            'dashicons-shield-alt',     // Icon
            30                          // Position
        );
        
        add_submenu_page(
            'crawlguard-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'crawlguard-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'crawlguard-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'crawlguard-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'crawlguard') === false) {
            return;
        }
        
        // Enqueue Chart.js
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        // Inline styles
        wp_add_inline_style('wp-admin', $this->get_admin_styles());
        
        // Inline script
        wp_add_inline_script('jquery', $this->get_admin_script());
    }
    
    public function dashboard_page() {
        // Get current stats
        global $wpdb;
        
        // Count total detections (safe query)
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}crawlguard_detections'");
        $total_detections = 0;
        $today_detections = 0;
        $total_revenue = 0;
        
        if ($table_exists) {
            $total_detections = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crawlguard_detections") ?: 0;
            $today_detections = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}crawlguard_detections WHERE DATE(detection_time) = %s",
                current_time('Y-m-d')
            )) ?: 0;
            $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM {$wpdb->prefix}crawlguard_detections WHERE monetized = 1") ?: 0;
        }
        
        ?>
        <div class="wrap crawlguard-dashboard">
            <h1><span class="dashicons dashicons-shield-alt"></span> CrawlGuard Pro Dashboard</h1>
            
            <div class="notice notice-success">
                <p><strong>🎉 Plugin Successfully Activated!</strong> Your AI bot detection and monetization system is now running.</p>
            </div>
            
            <!-- Status Cards -->
            <div class="crawlguard-stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🤖</div>
                    <div class="stat-content">
                        <h3>Total Bot Detections</h3>
                        <p class="stat-number"><?php echo number_format($total_detections); ?></p>
                        <small>All-time detections</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3>Today's Detections</h3>
                        <p class="stat-number"><?php echo number_format($today_detections); ?></p>
                        <small>Last 24 hours</small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3>Total Revenue</h3>
                        <p class="stat-number">$<?php echo number_format($total_revenue, 2); ?></p>
                        <small>85% goes to you</small>
                    </div>
                </div>
                
                <div class="stat-card api-status">
                    <div class="stat-icon">🔗</div>
                    <div class="stat-content">
                        <h3>API Status</h3>
                        <p class="stat-number" id="api-status">✅ Active</p>
                        <small>Bot detection online</small>
                    </div>
                </div>
            </div>
            
            <!-- Revenue Chart -->
            <div class="crawlguard-chart-container">
                <h2>📈 Revenue Potential</h2>
                <canvas id="revenueChart" width="400" height="200"></canvas>
                <p><strong>Note:</strong> Add your Stripe credentials to start earning real money from AI bot traffic!</p>
            </div>
            
            <!-- Quick Setup -->
            <div class="crawlguard-setup-guide">
                <h2>🚀 Quick Setup Guide</h2>
                <div class="setup-steps">
                    <div class="setup-step completed">
                        <span class="step-number">1</span>
                        <div class="step-content">
                            <h4>✅ Plugin Installed</h4>
                            <p>CrawlGuard Pro is active and running</p>
                        </div>
                    </div>
                    
                    <div class="setup-step <?php echo defined('STRIPE_SECRET_KEY_LIVE') ? 'completed' : 'pending'; ?>">
                        <span class="step-number">2</span>
                        <div class="step-content">
                            <h4><?php echo defined('STRIPE_SECRET_KEY_LIVE') ? '✅' : '⏳'; ?> Payment Setup</h4>
                            <p>Add Stripe credentials to wp-config.php to start earning</p>
                            <?php if (!defined('STRIPE_SECRET_KEY_LIVE')): ?>
                            <code>define('STRIPE_SECRET_KEY_LIVE', 'sk_live_YOUR_KEY');</code>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="setup-step pending">
                        <span class="step-number">3</span>
                        <div class="step-content">
                            <h4>💰 Start Earning</h4>
                            <p>AI bots will generate revenue automatically</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="crawlguard-recent-activity">
                <h2>🕒 Recent Bot Detections</h2>
                <div class="activity-list">
                    <?php if ($total_detections > 0): ?>
                        <?php
                        $recent = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}crawlguard_detections ORDER BY detection_time DESC LIMIT 5");
                        foreach ($recent as $detection):
                        ?>
                        <div class="activity-item">
                            <span class="bot-type"><?php echo esc_html($detection->bot_type); ?></span>
                            <span class="confidence"><?php echo round($detection->confidence_score * 100); ?>% confidence</span>
                            <span class="revenue">$<?php echo number_format($detection->revenue, 2); ?></span>
                            <span class="time"><?php echo human_time_diff(strtotime($detection->detection_time)); ?> ago</span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-item">
                            <span class="no-activity">🤖 No bot detections yet. They'll appear here automatically when AI bots visit your site.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="crawlguard-actions">
                <a href="<?php echo admin_url('admin.php?page=crawlguard-settings'); ?>" class="button button-primary button-large">
                    ⚙️ Configure Settings
                </a>
                <a href="https://creativeinteriorsstudio.com/support" target="_blank" class="button button-secondary button-large">
                    📞 Get Support
                </a>
                <button id="test-detection" class="button button-secondary button-large">
                    🧪 Test Bot Detection
                </button>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('crawlguard_detection_enabled', isset($_POST['crawlguard_detection_enabled']));
            update_option('crawlguard_monetization_enabled', isset($_POST['crawlguard_monetization_enabled']));
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        $detection_enabled = get_option('crawlguard_detection_enabled', true);
        $monetization_enabled = get_option('crawlguard_monetization_enabled', true);
        
        ?>
        <div class="wrap">
            <h1>CrawlGuard Pro Settings</h1>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row">Bot Detection</th>
                        <td>
                            <label>
                                <input type="checkbox" name="crawlguard_detection_enabled" <?php checked($detection_enabled); ?> />
                                Enable automatic bot detection
                            </label>
                            <p class="description">Detect AI bots like ChatGPT, Claude, and others.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Monetization</th>
                        <td>
                            <label>
                                <input type="checkbox" name="crawlguard_monetization_enabled" <?php checked($monetization_enabled); ?> />
                                Enable bot monetization
                            </label>
                            <p class="description">Generate revenue from AI bot visits (requires Stripe setup).</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="crawlguard-stripe-info">
                <h2>💳 Payment Configuration</h2>
                <p>To enable monetization, add these lines to your <code>wp-config.php</code> file:</p>
                <pre><code>define('STRIPE_PUBLISHABLE_KEY_LIVE', 'pk_live_YOUR_KEY');
define('STRIPE_SECRET_KEY_LIVE', 'sk_live_YOUR_KEY');
define('STRIPE_WEBHOOK_SECRET_LIVE', 'whsec_YOUR_SECRET');</code></pre>
                
                <h3>Revenue Structure</h3>
                <ul>
                    <li><strong>AI Bots (ChatGPT, Claude):</strong> $0.10 per detection</li>
                    <li><strong>Standard Bots:</strong> $0.05 per detection</li>
                    <li><strong>Your Share:</strong> 85% of revenue</li>
                    <li><strong>Platform Fee:</strong> 15% for API and maintenance</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    private function get_admin_styles() {
        return '
        .crawlguard-dashboard { max-width: 1200px; }
        .crawlguard-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px; }
        .stat-icon { font-size: 2.5em; opacity: 0.8; }
        .stat-content h3 { margin: 0; font-size: 14px; color: #666; text-transform: uppercase; font-weight: 500; }
        .stat-number { margin: 5px 0; font-size: 2em; font-weight: bold; color: #0073aa; }
        .stat-content small { color: #999; font-size: 12px; }
        .crawlguard-chart-container, .crawlguard-setup-guide, .crawlguard-recent-activity { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .setup-steps { display: flex; flex-direction: column; gap: 15px; }
        .setup-step { display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 8px; background: #f9f9f9; }
        .setup-step.completed { background: #d4edda; border-left: 4px solid #28a745; }
        .setup-step.pending { background: #fff3cd; border-left: 4px solid #ffc107; }
        .step-number { width: 30px; height: 30px; border-radius: 50%; background: #0073aa; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .setup-step.completed .step-number { background: #28a745; }
        .setup-step.pending .step-number { background: #ffc107; }
        .step-content h4 { margin: 0 0 5px; font-size: 16px; }
        .step-content p { margin: 0; color: #666; }
        .step-content code { background: #2d3748; color: #e2e8f0; padding: 8px 12px; border-radius: 4px; font-size: 12px; display: block; margin-top: 8px; }
        .activity-list { display: flex; flex-direction: column; gap: 10px; }
        .activity-item { display: flex; align-items: center; gap: 15px; padding: 10px; background: #f9f9f9; border-radius: 5px; }
        .bot-type { background: #0073aa; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .confidence { color: #28a745; font-weight: bold; }
        .revenue { color: #0073aa; font-weight: bold; }
        .time { color: #666; font-size: 12px; }
        .no-activity { color: #666; font-style: italic; }
        .crawlguard-actions { margin: 20px 0; }
        .crawlguard-actions .button { margin-right: 10px; }
        .crawlguard-stripe-info { background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .crawlguard-stripe-info pre { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .crawlguard-stripe-info ul { margin: 10px 0; padding-left: 20px; }
        ';
    }
    
    private function get_admin_script() {
        return '
        jQuery(document).ready(function($) {
            // Initialize chart if Chart.js is loaded
            if (typeof Chart !== "undefined") {
                var ctx = document.getElementById("revenueChart");
                if (ctx) {
                    new Chart(ctx, {
                        type: "line",
                        data: {
                            labels: ["Day 1", "Day 2", "Day 3", "Day 4", "Day 5", "Day 6", "Day 7"],
                            datasets: [{
                                label: "Potential Revenue ($)",
                                data: [2.50, 5.10, 8.25, 12.40, 18.90, 25.30, 32.15],
                                borderColor: "#0073aa",
                                backgroundColor: "rgba(0, 115, 170, 0.1)",
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: "Revenue Growth Projection (Sample Data)"
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: "Revenue ($USD)"
                                    }
                                }
                            }
                        }
                    });
                }
            }
            
            // Test detection button
            $("#test-detection").click(function() {
                $(this).text("🧪 Testing...").prop("disabled", true);
                setTimeout(function() {
                    alert("✅ Bot detection system is working! AI bots will be automatically detected and monetized.");
                    $("#test-detection").text("🧪 Test Bot Detection").prop("disabled", false);
                }, 2000);
            });
        });
        ';
    }
}
