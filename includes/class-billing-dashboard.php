<?php
/**
 * Billing Dashboard UI
 * 
 * Provides interface for subscription management, revenue tracking, and payout history
 * 
 * @package CrawlGuard
 * @since 1.0.0
 */

namespace CrawlGuard;

if (!defined('ABSPATH')) {
    exit;
}

class Billing_Dashboard {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Stripe Integration
     */
    private $stripe;
    
    /**
     * Revenue Tracker
     */
    private $revenue_tracker;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->stripe = Stripe_Integration::get_instance();
        $this->revenue_tracker = Revenue_Tracker::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add menu items
        add_action('admin_menu', [$this, 'add_menu_pages']);
        
        // Shortcodes for frontend dashboard
        add_shortcode('crawlguard_billing_dashboard', [$this, 'render_dashboard']);
        add_shortcode('crawlguard_revenue_chart', [$this, 'render_revenue_chart']);
        add_shortcode('crawlguard_payout_history', [$this, 'render_payout_history']);
        
        // AJAX handlers
        add_action('wp_ajax_crawlguard_get_billing_data', [$this, 'ajax_get_billing_data']);
        add_action('wp_ajax_crawlguard_update_payout_settings', [$this, 'ajax_update_payout_settings']);
        add_action('wp_ajax_crawlguard_download_invoice', [$this, 'ajax_download_invoice']);
        add_action('wp_ajax_crawlguard_download_tax_document', [$this, 'ajax_download_tax_document']);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }
    
    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        // Main billing menu
        add_menu_page(
            'CrawlGuard Billing',
            'CrawlGuard Billing',
            'manage_options',
            'crawlguard-billing',
            [$this, 'render_admin_dashboard'],
            'dashicons-chart-area',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'crawlguard-billing',
            'Revenue Analytics',
            'Revenue',
            'manage_options',
            'crawlguard-revenue',
            [$this, 'render_revenue_page']
        );
        
        add_submenu_page(
            'crawlguard-billing',
            'Payout History',
            'Payouts',
            'manage_options',
            'crawlguard-payouts',
            [$this, 'render_payouts_page']
        );
        
        add_submenu_page(
            'crawlguard-billing',
            'Subscription Settings',
            'Subscription',
            'manage_options',
            'crawlguard-subscription',
            [$this, 'render_subscription_page']
        );
        
        add_submenu_page(
            'crawlguard-billing',
            'Tax Documents',
            'Tax Documents',
            'manage_options',
            'crawlguard-tax-docs',
            [$this, 'render_tax_docs_page']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'crawlguard') === false) {
            return;
        }
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js',
            [],
            '4.3.0',
            true
        );
        
        // Our admin scripts
        wp_enqueue_script(
            'crawlguard-billing-admin',
            CRAWLGUARD_PLUGIN_URL . 'assets/js/billing-admin.js',
            ['jquery', 'chartjs'],
            CRAWLGUARD_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('crawlguard-billing-admin', 'crawlguard_billing', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crawlguard_billing'),
            'currency' => 'USD',
        ]);
        
        // Admin styles
        wp_enqueue_style(
            'crawlguard-billing-admin',
            CRAWLGUARD_PLUGIN_URL . 'assets/css/billing-admin.css',
            [],
            CRAWLGUARD_VERSION
        );
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_account_page() && !has_shortcode(get_post()->post_content, 'crawlguard_billing_dashboard')) {
            return;
        }
        
        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js',
            [],
            '4.3.0',
            true
        );
        
        // Frontend scripts
        wp_enqueue_script(
            'crawlguard-billing-frontend',
            CRAWLGUARD_PLUGIN_URL . 'assets/js/billing-frontend.js',
            ['jquery', 'chartjs'],
            CRAWLGUARD_VERSION,
            true
        );
        
        // Frontend styles
        wp_enqueue_style(
            'crawlguard-billing-frontend',
            CRAWLGUARD_PLUGIN_URL . 'assets/css/billing-frontend.css',
            [],
            CRAWLGUARD_VERSION
        );
    }
    
    /**
     * Render admin dashboard
     */
    public function render_admin_dashboard() {
        $user_id = get_current_user_id();
        $stats = $this->revenue_tracker->get_revenue_stats($user_id, 'month');
        $subscription = $this->get_subscription_info($user_id);
        $recent_payouts = $this->revenue_tracker->get_payout_history($user_id, 5);
        
        ?>
        <div class="wrap crawlguard-billing-dashboard">
            <h1>CrawlGuard Billing Dashboard</h1>
            
            <!-- Overview Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Current Plan</h3>
                    <div class="card-value"><?php echo ucfirst($subscription['tier'] ?? 'Free'); ?></div>
                    <div class="card-meta">
                        <?php if ($subscription['status'] === 'active'): ?>
                            <span class="status-badge active">Active</span>
                        <?php else: ?>
                            <span class="status-badge inactive"><?php echo ucfirst($subscription['status'] ?? 'Inactive'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Revenue This Month</h3>
                    <div class="card-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="card-meta">
                        <?php echo number_format($stats['billable_visits']); ?> billable visits
                    </div>
                </div>
                
                <div class="card">
                    <h3>Pending Payout</h3>
                    <div class="card-value">$<?php echo number_format($this->get_pending_payout($user_id), 2); ?></div>
                    <div class="card-meta">
                        Next payout: <?php echo $this->get_next_payout_date($user_id); ?>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Active Sites</h3>
                    <div class="card-value"><?php echo $stats['sites_active']; ?></div>
                    <div class="card-meta">
                        <?php echo $stats['days_active']; ?> days active
                    </div>
                </div>
            </div>
            
            <!-- Revenue Chart -->
            <div class="dashboard-section">
                <h2>Revenue Trend</h2>
                <div class="chart-container">
                    <canvas id="revenue-chart"></canvas>
                </div>
            </div>
            
            <!-- Recent Payouts -->
            <div class="dashboard-section">
                <h2>Recent Payouts</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Period</th>
                            <th>Gross Amount</th>
                            <th>Platform Fee</th>
                            <th>Net Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payouts as $payout): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($payout->created_at)); ?></td>
                            <td><?php echo date('M j', strtotime($payout->period_start)) . ' - ' . date('M j', strtotime($payout->period_end)); ?></td>
                            <td>$<?php echo number_format($payout->gross_amount, 2); ?></td>
                            <td>$<?php echo number_format($payout->platform_fee, 2); ?></td>
                            <td><strong>$<?php echo number_format($payout->net_amount, 2); ?></strong></td>
                            <td>
                                <span class="status-badge <?php echo $payout->status; ?>">
                                    <?php echo ucfirst($payout->status); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-section">
                <h2>Quick Actions</h2>
                <div class="quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=crawlguard-subscription'); ?>" class="button button-primary">
                        Manage Subscription
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=crawlguard-payouts'); ?>" class="button">
                        View All Payouts
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=crawlguard-tax-docs'); ?>" class="button">
                        Tax Documents
                    </a>
                    <button class="button" id="connect-stripe">
                        <?php echo get_user_meta($user_id, 'stripe_connect_account_id', true) ? 'Stripe Connected ✓' : 'Connect Stripe'; ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render frontend dashboard
     */
    public function render_dashboard($atts = []) {
        if (!is_user_logged_in()) {
            return '<p>Please login to view your billing dashboard.</p>';
        }
        
        $user_id = get_current_user_id();
        $stats = $this->revenue_tracker->get_revenue_stats($user_id, 'month');
        $subscription = $this->get_subscription_info($user_id);
        
        ob_start();
        ?>
        <div class="crawlguard-billing-dashboard-frontend">
            <!-- Subscription Status -->
            <div class="subscription-status">
                <h3>Your Subscription</h3>
                <div class="subscription-info">
                    <div class="plan-name"><?php echo ucfirst($subscription['tier'] ?? 'Free'); ?> Plan</div>
                    <div class="plan-price">$<?php echo number_format($subscription['price'] ?? 0, 2); ?>/month</div>
                    <div class="plan-status">
                        <?php if ($subscription['status'] === 'active'): ?>
                            <span class="badge success">Active</span>
                        <?php elseif ($subscription['status'] === 'trialing'): ?>
                            <span class="badge info">Trial</span>
                        <?php else: ?>
                            <span class="badge warning"><?php echo ucfirst($subscription['status'] ?? 'Inactive'); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($subscription['cancel_at']): ?>
                        <div class="cancellation-notice">
                            Will cancel on <?php echo date('F j, Y', $subscription['cancel_at']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="subscription-actions">
                    <?php if ($subscription['status'] === 'active'): ?>
                        <button class="btn btn-secondary" id="update-payment-method">Update Payment Method</button>
                        <button class="btn btn-secondary" id="change-plan">Change Plan</button>
                        <button class="btn btn-danger" id="cancel-subscription">Cancel Subscription</button>
                    <?php else: ?>
                        <a href="<?php echo home_url('/pricing'); ?>" class="btn btn-primary">Subscribe Now</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Revenue Summary -->
            <div class="revenue-summary">
                <h3>Revenue Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>This Month</label>
                        <value>$<?php echo number_format($stats['total_revenue'], 2); ?></value>
                    </div>
                    <div class="summary-item">
                        <label>Bot Visits</label>
                        <value><?php echo number_format($stats['total_visits']); ?></value>
                    </div>
                    <div class="summary-item">
                        <label>Conversion Rate</label>
                        <value><?php echo number_format($stats['conversion_rate'], 1); ?>%</value>
                    </div>
                    <div class="summary-item">
                        <label>Avg Daily</label>
                        <value>$<?php echo number_format($stats['avg_daily_revenue'], 2); ?></value>
                    </div>
                </div>
            </div>
            
            <!-- Payout Settings -->
            <div class="payout-settings">
                <h3>Payout Settings</h3>
                <form id="payout-settings-form">
                    <div class="form-group">
                        <label>Payout Schedule</label>
                        <select name="payout_schedule" id="payout_schedule">
                            <option value="weekly" <?php selected(get_user_meta($user_id, 'payout_schedule', true), 'weekly'); ?>>
                                Weekly (Fridays)
                            </option>
                            <option value="monthly" <?php selected(get_user_meta($user_id, 'payout_schedule', true), 'monthly'); ?>>
                                Monthly (1st of month)
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Minimum Payout Threshold</label>
                        <input type="number" 
                               name="min_payout" 
                               id="min_payout" 
                               min="25" 
                               step="5" 
                               value="<?php echo get_user_meta($user_id, 'min_payout_threshold', true) ?: 25; ?>">
                        <small>Minimum: $25.00</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Tax Information</label>
                        <?php if (get_user_meta($user_id, 'tax_id', true)): ?>
                            <div class="tax-status">
                                <span class="badge success">Tax Info Provided ✓</span>
                                <button type="button" class="btn-link" id="update-tax-info">Update</button>
                            </div>
                        <?php else: ?>
                            <div class="tax-status">
                                <span class="badge warning">Tax Info Required</span>
                                <button type="button" class="btn btn-secondary" id="add-tax-info">Add Tax Information</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
            
            <!-- Billing History -->
            <div class="billing-history">
                <h3>Billing History</h3>
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody id="billing-history-tbody">
                        <!-- Populated via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Payment Method Modal -->
        <div id="payment-method-modal" class="modal" style="display:none;">
            <div class="modal-content">
                <h3>Update Payment Method</h3>
                <form id="update-payment-form">
                    <div id="card-element-update"></div>
                    <div id="card-errors-update"></div>
                    <button type="submit" class="btn btn-primary">Update Payment Method</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get subscription info
     * 
     * @param int $user_id
     * @return array
     */
    private function get_subscription_info($user_id) {
        $subscription_id = get_user_meta($user_id, 'stripe_subscription_id', true);
        $tier = get_user_meta($user_id, 'subscription_tier', true);
        $status = get_user_meta($user_id, 'subscription_status', true);
        $cancel_at = get_user_meta($user_id, 'subscription_cancel_at', true);
        
        $tier_data = get_option('crawlguard_stripe_tier_' . $tier);
        
        return [
            'subscription_id' => $subscription_id,
            'tier' => $tier,
            'status' => $status,
            'price' => $tier_data['price'] ?? 0,
            'features' => $tier_data['features'] ?? [],
            'cancel_at' => $cancel_at,
        ];
    }
    
    /**
     * Get pending payout amount
     * 
     * @param int $user_id
     * @return float
     */
    private function get_pending_payout($user_id) {
        global $wpdb;
        
        $pending = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}crawlguard_revenue
             WHERE user_id = %d AND status = 'pending'",
            $user_id
        ));
        
        return floatval($pending) * 0.80; // After 20% platform fee
    }
    
    /**
     * Get next payout date
     * 
     * @param int $user_id
     * @return string
     */
    private function get_next_payout_date($user_id) {
        $schedule = get_user_meta($user_id, 'payout_schedule', true) ?: 'weekly';
        
        if ($schedule === 'weekly') {
            return date('F j', strtotime('next Friday'));
        } else {
            return date('F j', strtotime('first day of next month'));
        }
    }
    
    /**
     * AJAX: Get billing data
     */
    public function ajax_get_billing_data() {
        check_ajax_referer('crawlguard_billing', 'nonce');
        
        $user_id = get_current_user_id();
        $period = sanitize_text_field($_POST['period'] ?? 'month');
        
        $data = [
            'revenue' => $this->get_revenue_chart_data($user_id, $period),
            'stats' => $this->revenue_tracker->get_revenue_stats($user_id, $period),
            'payouts' => $this->revenue_tracker->get_payout_history($user_id, 10),
        ];
        
        wp_send_json_success($data);
    }
    
    /**
     * Get revenue chart data
     * 
     * @param int $user_id
     * @param string $period
     * @return array
     */
    private function get_revenue_chart_data($user_id, $period) {
        global $wpdb;
        
        $days = $period === 'week' ? 7 : 30;
        
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(date) as date,
                SUM(amount) as revenue,
                SUM(bot_visits) as visits
             FROM {$wpdb->prefix}crawlguard_revenue
             WHERE user_id = %d
             AND date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             GROUP BY DATE(date)
             ORDER BY date ASC",
            $user_id,
            $days
        ));
        
        return [
            'labels' => array_map(function($row) {
                return date('M j', strtotime($row->date));
            }, $data),
            'revenue' => array_map(function($row) {
                return floatval($row->revenue);
            }, $data),
            'visits' => array_map(function($row) {
                return intval($row->visits);
            }, $data),
        ];
    }
}
