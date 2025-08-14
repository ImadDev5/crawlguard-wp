<?php
/**
 * Stripe Checkout Flow
 * 
 * Handles the checkout process for subscriptions
 * 
 * @package CrawlGuard
 * @since 1.0.0
 */

namespace CrawlGuard;

if (!defined('ABSPATH')) {
    exit;
}

class Stripe_Checkout {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Stripe Integration instance
     */
    private $stripe;
    
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Shortcodes for checkout forms
        add_shortcode('crawlguard_checkout', [$this, 'render_checkout_form']);
        add_shortcode('crawlguard_pricing_table', [$this, 'render_pricing_table']);
        
        // AJAX handlers
        add_action('wp_ajax_crawlguard_create_checkout_session', [$this, 'handle_create_checkout_session']);
        add_action('wp_ajax_crawlguard_confirm_payment', [$this, 'handle_confirm_payment']);
        add_action('wp_ajax_crawlguard_update_payment_method', [$this, 'handle_update_payment_method']);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Enqueue Stripe scripts
     */
    public function enqueue_scripts() {
        if (is_page('pricing') || is_page('checkout') || is_account_page()) {
            // Stripe.js
            wp_enqueue_script(
                'stripe-js',
                'https://js.stripe.com/v3/',
                [],
                null,
                true
            );
            
            // Our checkout script
            wp_enqueue_script(
                'crawlguard-checkout',
                CRAWLGUARD_PLUGIN_URL . 'assets/js/checkout.js',
                ['jquery', 'stripe-js'],
                CRAWLGUARD_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('crawlguard-checkout', 'crawlguard_checkout', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('crawlguard_checkout'),
                'stripe_public_key' => get_option('crawlguard_stripe_public_key'),
                'currency' => 'usd',
                'locale' => get_locale(),
            ]);
            
            // Checkout styles
            wp_enqueue_style(
                'crawlguard-checkout',
                CRAWLGUARD_PLUGIN_URL . 'assets/css/checkout.css',
                [],
                CRAWLGUARD_VERSION
            );
        }
    }
    
    /**
     * Render pricing table
     */
    public function render_pricing_table($atts = []) {
        $atts = shortcode_atts([
            'highlight' => 'pro',
            'show_features' => 'yes',
        ], $atts);
        
        $tiers = [
            'basic' => get_option('crawlguard_stripe_tier_basic'),
            'pro' => get_option('crawlguard_stripe_tier_pro'),
            'enterprise' => get_option('crawlguard_stripe_tier_enterprise'),
        ];
        
        ob_start();
        ?>
        <div class="crawlguard-pricing-table">
            <div class="pricing-grid">
                <?php foreach ($tiers as $tier_key => $tier_data): ?>
                    <?php if (!$tier_data) continue; ?>
                    <div class="pricing-card <?php echo $tier_key === $atts['highlight'] ? 'highlighted' : ''; ?>">
                        <?php if ($tier_key === $atts['highlight']): ?>
                            <div class="popular-badge">Most Popular</div>
                        <?php endif; ?>
                        
                        <h3 class="tier-name"><?php echo ucfirst($tier_key); ?></h3>
                        <div class="price">
                            <span class="currency">$</span>
                            <span class="amount"><?php echo number_format($tier_data['price'], 2); ?></span>
                            <span class="period">/month</span>
                        </div>
                        
                        <?php if ($atts['show_features'] === 'yes' && !empty($tier_data['features'])): ?>
                            <ul class="features">
                                <?php foreach ($tier_data['features'] as $feature): ?>
                                    <li>
                                        <svg class="checkmark" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <?php echo esc_html($feature); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <button class="select-plan-btn" 
                                data-tier="<?php echo esc_attr($tier_key); ?>"
                                data-price-id="<?php echo esc_attr($tier_data['price_id']); ?>">
                            <?php echo $tier_key === 'enterprise' ? 'Contact Sales' : 'Get Started'; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render checkout form
     */
    public function render_checkout_form($atts = []) {
        $atts = shortcode_atts([
            'tier' => '',
            'show_summary' => 'yes',
        ], $atts);
        
        // Get tier from URL parameter if not specified
        $tier = !empty($atts['tier']) ? $atts['tier'] : (isset($_GET['tier']) ? sanitize_text_field($_GET['tier']) : 'pro');
        $tier_data = get_option('crawlguard_stripe_tier_' . $tier);
        
        if (!$tier_data) {
            return '<p>Invalid subscription tier selected.</p>';
        }
        
        ob_start();
        ?>
        <div class="crawlguard-checkout-form" data-tier="<?php echo esc_attr($tier); ?>">
            <?php if ($atts['show_summary'] === 'yes'): ?>
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-item">
                        <span class="item-name">CrawlGuard <?php echo ucfirst($tier); ?></span>
                        <span class="item-price">$<?php echo number_format($tier_data['price'], 2); ?>/mo</span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="total-amount">$<?php echo number_format($tier_data['price'], 2); ?>/mo</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <form id="payment-form">
                <div class="form-section">
                    <h3>Payment Information</h3>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="card-element">Card Information</label>
                        <div id="card-element" class="stripe-element">
                            <!-- Stripe Elements will be inserted here -->
                        </div>
                        <div id="card-errors" class="error-message"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Cardholder Name</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Billing Address</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country" required>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="AU">Australia</option>
                                <!-- Add more countries as needed -->
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="postal_code">ZIP/Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <label class="checkbox-label">
                        <input type="checkbox" name="save_payment_method" checked>
                        Save payment method for future purchases
                    </label>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="agree_terms" required>
                        I agree to the <a href="/terms" target="_blank">Terms of Service</a> 
                        and <a href="/privacy" target="_blank">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" id="submit-payment" class="submit-button">
                    <span class="button-text">Subscribe Now</span>
                    <span class="spinner" style="display: none;">Processing...</span>
                </button>
                
                <div class="secure-badge">
                    <svg class="lock-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    Secured by Stripe
                </div>
                
                <input type="hidden" name="tier" value="<?php echo esc_attr($tier); ?>">
                <input type="hidden" name="price_id" value="<?php echo esc_attr($tier_data['price_id']); ?>">
                <?php wp_nonce_field('crawlguard_checkout', 'checkout_nonce'); ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle checkout session creation
     */
    public function handle_create_checkout_session() {
        check_ajax_referer('crawlguard_checkout', 'nonce');
        
        $tier = sanitize_text_field($_POST['tier'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $payment_method_id = sanitize_text_field($_POST['payment_method_id'] ?? '');
        
        if (!$tier || !$email || !$payment_method_id) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        // Get or create user
        $user = get_user_by('email', $email);
        if (!$user) {
            // Create user account
            $user_id = wp_create_user(
                $email,
                wp_generate_password(),
                $email
            );
            
            if (is_wp_error($user_id)) {
                wp_send_json_error(['message' => 'Failed to create user account']);
            }
            
            $user = get_user_by('id', $user_id);
            
            // Send welcome email with password reset link
            wp_new_user_notification($user_id, null, 'both');
        }
        
        // Create subscription
        $result = $this->stripe->create_subscription(
            $user->ID,
            $tier,
            $payment_method_id
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Handle payment confirmation
     */
    public function handle_confirm_payment() {
        check_ajax_referer('crawlguard_checkout', 'nonce');
        
        $payment_intent_id = sanitize_text_field($_POST['payment_intent_id'] ?? '');
        
        if (!$payment_intent_id) {
            wp_send_json_error(['message' => 'Missing payment intent ID']);
        }
        
        // Verify payment status with Stripe
        try {
            $stripe_client = new \Stripe\StripeClient(get_option('crawlguard_stripe_secret_key'));
            $payment_intent = $stripe_client->paymentIntents->retrieve($payment_intent_id);
            
            if ($payment_intent->status === 'succeeded') {
                // Payment successful
                wp_send_json_success([
                    'redirect_url' => home_url('/account/subscription-confirmed/'),
                ]);
            } else {
                wp_send_json_error(['message' => 'Payment not completed']);
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle payment method update
     */
    public function handle_update_payment_method() {
        check_ajax_referer('crawlguard_checkout', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in']);
        }
        
        $payment_method_id = sanitize_text_field($_POST['payment_method_id'] ?? '');
        
        if (!$payment_method_id) {
            wp_send_json_error(['message' => 'Missing payment method ID']);
        }
        
        $user_id = get_current_user_id();
        $customer_id = get_user_meta($user_id, 'stripe_customer_id', true);
        
        if (!$customer_id) {
            wp_send_json_error(['message' => 'No Stripe customer found']);
        }
        
        try {
            $stripe_client = new \Stripe\StripeClient(get_option('crawlguard_stripe_secret_key'));
            
            // Attach payment method to customer
            $stripe_client->paymentMethods->attach($payment_method_id, [
                'customer' => $customer_id,
            ]);
            
            // Set as default payment method
            $stripe_client->customers->update($customer_id, [
                'invoice_settings' => [
                    'default_payment_method' => $payment_method_id,
                ],
            ]);
            
            wp_send_json_success(['message' => 'Payment method updated successfully']);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
