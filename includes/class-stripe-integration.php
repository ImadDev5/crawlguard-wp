<?php
/**
 * Stripe Integration Module
 * 
 * Handles all Stripe payment processing, subscriptions, and payouts
 * 
 * @package CrawlGuard
 * @since 1.0.0
 */

namespace CrawlGuard;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription;
use Stripe\Product;
use Stripe\Price;
use Stripe\PaymentIntent;
use Stripe\Account;
use Stripe\Transfer;
use Stripe\Payout;
use Stripe\WebhookEndpoint;
use Stripe\StripeClient;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

class Stripe_Integration {
    
    /**
     * Stripe client instance
     * @var StripeClient
     */
    private $stripe;
    
    /**
     * Platform fee percentage (15-25%)
     * @var float
     */
    private $platform_fee_percent = 0.20; // 20% default
    
    /**
     * Minimum payout threshold
     * @var float
     */
    private $min_payout_threshold = 25.00;
    
    /**
     * Instance
     * @var Stripe_Integration
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * @return Stripe_Integration
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
        $this->init_stripe();
        $this->init_hooks();
    }
    
    /**
     * Initialize Stripe SDK
     */
    private function init_stripe() {
        $options = get_option('crawlguard_stripe_settings', []);
        
        if (!empty($options['secret_key'])) {
            try {
                $this->stripe = new StripeClient($options['secret_key']);
                Stripe::setApiKey($options['secret_key']);
                
                // Set app info for better debugging
                Stripe::setAppInfo(
                    'CrawlGuard WP',
                    CRAWLGUARD_VERSION,
                    'https://crawlguard.com'
                );
                
                // Configure platform fee
                if (!empty($options['platform_fee_percent'])) {
                    $this->platform_fee_percent = floatval($options['platform_fee_percent']) / 100;
                }
                
            } catch (Exception $e) {
                $this->log_error('Stripe initialization failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Webhook handlers
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
        
        // Subscription management
        add_action('crawlguard_process_subscription_renewals', [$this, 'process_renewals']);
        add_action('crawlguard_calculate_payouts', [$this, 'calculate_payouts']);
        
        // Schedule cron jobs
        if (!wp_next_scheduled('crawlguard_process_subscription_renewals')) {
            wp_schedule_event(time(), 'daily', 'crawlguard_process_subscription_renewals');
        }
        
        if (!wp_next_scheduled('crawlguard_calculate_payouts')) {
            wp_schedule_event(time(), 'weekly', 'crawlguard_calculate_payouts');
        }
    }
    
    /**
     * Register webhook endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route('crawlguard/v1', '/stripe/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * Create Stripe Connect account for user
     * 
     * @param int $user_id WordPress user ID
     * @param array $account_data Account data
     * @return array|WP_Error
     */
    public function create_connect_account($user_id, $account_data = []) {
        try {
            $user = get_user_by('id', $user_id);
            if (!$user) {
                return new \WP_Error('user_not_found', 'User not found');
            }
            
            // Create Express account by default
            $account_params = [
                'type' => $account_data['type'] ?? 'express',
                'country' => $account_data['country'] ?? 'US',
                'email' => $user->user_email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                    'url' => get_user_meta($user_id, 'website_url', true) ?: site_url(),
                    'mcc' => '5734', // Computer Software Stores
                ],
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'interval' => $account_data['payout_schedule'] ?? 'weekly',
                            'weekly_anchor' => 'friday',
                        ],
                    ],
                ],
                'metadata' => [
                    'user_id' => $user_id,
                    'platform' => 'crawlguard_wp',
                ],
            ];
            
            if (!empty($account_data['business_name'])) {
                $account_params['business_profile']['name'] = $account_data['business_name'];
            }
            
            $account = $this->stripe->accounts->create($account_params);
            
            // Save account ID to user meta
            update_user_meta($user_id, 'stripe_connect_account_id', $account->id);
            update_user_meta($user_id, 'stripe_connect_status', 'pending');
            
            // Create account link for onboarding
            $account_link = $this->create_account_link($account->id, $user_id);
            
            return [
                'success' => true,
                'account_id' => $account->id,
                'onboarding_url' => $account_link->url,
            ];
            
        } catch (Exception $e) {
            $this->log_error('Connect account creation failed: ' . $e->getMessage());
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }
    
    /**
     * Create account link for Connect onboarding
     * 
     * @param string $account_id Stripe account ID
     * @param int $user_id WordPress user ID
     * @return object Account link object
     */
    private function create_account_link($account_id, $user_id) {
        return $this->stripe->accountLinks->create([
            'account' => $account_id,
            'refresh_url' => add_query_arg([
                'action' => 'stripe_connect_refresh',
                'user_id' => $user_id,
            ], admin_url('admin.php?page=crawlguard-settings')),
            'return_url' => add_query_arg([
                'action' => 'stripe_connect_return',
                'user_id' => $user_id,
            ], admin_url('admin.php?page=crawlguard-settings')),
            'type' => 'account_onboarding',
        ]);
    }
    
    /**
     * Create subscription products and prices
     * 
     * @return array Created products and prices
     */
    public function create_subscription_tiers() {
        $tiers = [
            'basic' => [
                'name' => 'CrawlGuard Basic',
                'description' => 'Essential bot detection and protection',
                'price' => 29.99,
                'features' => [
                    '10,000 bot visits/month',
                    'Basic analytics',
                    'Email support',
                ],
            ],
            'pro' => [
                'name' => 'CrawlGuard Pro',
                'description' => 'Advanced protection with revenue optimization',
                'price' => 99.99,
                'features' => [
                    '100,000 bot visits/month',
                    'Advanced analytics',
                    'Priority support',
                    'Custom rules',
                ],
            ],
            'enterprise' => [
                'name' => 'CrawlGuard Enterprise',
                'description' => 'Unlimited protection with dedicated support',
                'price' => 299.99,
                'features' => [
                    'Unlimited bot visits',
                    'Real-time analytics',
                    'Dedicated support',
                    'Custom integration',
                    'SLA guarantee',
                ],
            ],
        ];
        
        $created = [];
        
        foreach ($tiers as $tier_key => $tier) {
            try {
                // Create product
                $product = $this->stripe->products->create([
                    'name' => $tier['name'],
                    'description' => $tier['description'],
                    'metadata' => [
                        'tier' => $tier_key,
                        'features' => json_encode($tier['features']),
                    ],
                ]);
                
                // Create price
                $price = $this->stripe->prices->create([
                    'product' => $product->id,
                    'unit_amount' => intval($tier['price'] * 100), // Convert to cents
                    'currency' => 'usd',
                    'recurring' => [
                        'interval' => 'month',
                    ],
                    'metadata' => [
                        'tier' => $tier_key,
                    ],
                ]);
                
                $created[$tier_key] = [
                    'product_id' => $product->id,
                    'price_id' => $price->id,
                    'price' => $tier['price'],
                ];
                
                // Save to WordPress options
                update_option('crawlguard_stripe_tier_' . $tier_key, [
                    'product_id' => $product->id,
                    'price_id' => $price->id,
                    'price' => $tier['price'],
                    'features' => $tier['features'],
                ]);
                
            } catch (Exception $e) {
                $this->log_error('Failed to create tier ' . $tier_key . ': ' . $e->getMessage());
            }
        }
        
        return $created;
    }
    
    /**
     * Create subscription for user
     * 
     * @param int $user_id WordPress user ID
     * @param string $tier Subscription tier
     * @param string $payment_method_id Payment method ID
     * @return array|WP_Error
     */
    public function create_subscription($user_id, $tier, $payment_method_id) {
        try {
            $user = get_user_by('id', $user_id);
            if (!$user) {
                return new \WP_Error('user_not_found', 'User not found');
            }
            
            // Get or create Stripe customer
            $customer_id = get_user_meta($user_id, 'stripe_customer_id', true);
            
            if (!$customer_id) {
                $customer = $this->stripe->customers->create([
                    'email' => $user->user_email,
                    'name' => $user->display_name,
                    'metadata' => [
                        'user_id' => $user_id,
                    ],
                ]);
                $customer_id = $customer->id;
                update_user_meta($user_id, 'stripe_customer_id', $customer_id);
            }
            
            // Attach payment method
            $this->stripe->paymentMethods->attach($payment_method_id, [
                'customer' => $customer_id,
            ]);
            
            // Set as default payment method
            $this->stripe->customers->update($customer_id, [
                'invoice_settings' => [
                    'default_payment_method' => $payment_method_id,
                ],
            ]);
            
            // Get tier price ID
            $tier_data = get_option('crawlguard_stripe_tier_' . $tier);
            if (!$tier_data || !isset($tier_data['price_id'])) {
                return new \WP_Error('tier_not_found', 'Subscription tier not found');
            }
            
            // Create subscription with idempotency key
            $idempotency_key = 'sub_' . $user_id . '_' . $tier . '_' . time();
            
            $subscription = $this->stripe->subscriptions->create([
                'customer' => $customer_id,
                'items' => [
                    ['price' => $tier_data['price_id']],
                ],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => [
                    'save_default_payment_method' => 'on_subscription',
                ],
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => [
                    'user_id' => $user_id,
                    'tier' => $tier,
                ],
            ], [
                'idempotency_key' => $idempotency_key,
            ]);
            
            // Save subscription data
            update_user_meta($user_id, 'stripe_subscription_id', $subscription->id);
            update_user_meta($user_id, 'subscription_tier', $tier);
            update_user_meta($user_id, 'subscription_status', $subscription->status);
            
            // Log subscription creation
            $this->log_subscription_event($user_id, 'created', [
                'subscription_id' => $subscription->id,
                'tier' => $tier,
                'amount' => $tier_data['price'],
            ]);
            
            return [
                'success' => true,
                'subscription_id' => $subscription->id,
                'client_secret' => $subscription->latest_invoice->payment_intent->client_secret,
                'status' => $subscription->status,
            ];
            
        } catch (Exception $e) {
            $this->log_error('Subscription creation failed: ' . $e->getMessage());
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }
    
    /**
     * Update subscription
     * 
     * @param int $user_id WordPress user ID
     * @param string $new_tier New subscription tier
     * @return array|WP_Error
     */
    public function update_subscription($user_id, $new_tier) {
        try {
            $subscription_id = get_user_meta($user_id, 'stripe_subscription_id', true);
            if (!$subscription_id) {
                return new \WP_Error('no_subscription', 'No active subscription found');
            }
            
            $subscription = $this->stripe->subscriptions->retrieve($subscription_id);
            
            // Get new tier price ID
            $tier_data = get_option('crawlguard_stripe_tier_' . $new_tier);
            if (!$tier_data || !isset($tier_data['price_id'])) {
                return new \WP_Error('tier_not_found', 'Subscription tier not found');
            }
            
            // Update subscription
            $updated = $this->stripe->subscriptions->update($subscription_id, [
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'price' => $tier_data['price_id'],
                    ],
                ],
                'proration_behavior' => 'create_prorations',
            ]);
            
            // Update user meta
            update_user_meta($user_id, 'subscription_tier', $new_tier);
            
            // Log event
            $this->log_subscription_event($user_id, 'updated', [
                'subscription_id' => $subscription_id,
                'new_tier' => $new_tier,
                'old_tier' => get_user_meta($user_id, 'subscription_tier', true),
            ]);
            
            return [
                'success' => true,
                'subscription' => $updated,
            ];
            
        } catch (Exception $e) {
            $this->log_error('Subscription update failed: ' . $e->getMessage());
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }
    
    /**
     * Cancel subscription
     * 
     * @param int $user_id WordPress user ID
     * @param bool $immediate Cancel immediately or at period end
     * @return array|WP_Error
     */
    public function cancel_subscription($user_id, $immediate = false) {
        try {
            $subscription_id = get_user_meta($user_id, 'stripe_subscription_id', true);
            if (!$subscription_id) {
                return new \WP_Error('no_subscription', 'No active subscription found');
            }
            
            if ($immediate) {
                // Cancel immediately
                $subscription = $this->stripe->subscriptions->cancel($subscription_id);
            } else {
                // Cancel at period end
                $subscription = $this->stripe->subscriptions->update($subscription_id, [
                    'cancel_at_period_end' => true,
                ]);
            }
            
            // Update user meta
            update_user_meta($user_id, 'subscription_status', $subscription->status);
            if (!$immediate) {
                update_user_meta($user_id, 'subscription_cancel_at', $subscription->cancel_at);
            }
            
            // Log event
            $this->log_subscription_event($user_id, 'cancelled', [
                'subscription_id' => $subscription_id,
                'immediate' => $immediate,
            ]);
            
            return [
                'success' => true,
                'subscription' => $subscription,
            ];
            
        } catch (Exception $e) {
            $this->log_error('Subscription cancellation failed: ' . $e->getMessage());
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }
    
    /**
     * Handle Stripe webhook
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_webhook($request) {
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');
        $endpoint_secret = get_option('crawlguard_stripe_webhook_secret');
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            return new \WP_REST_Response(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new \WP_REST_Response(['error' => 'Invalid signature'], 400);
        }
        
        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handle_payment_succeeded($event->data->object);
                break;
                
            case 'payment_intent.payment_failed':
                $this->handle_payment_failed($event->data->object);
                break;
                
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handle_subscription_updated($event->data->object);
                break;
                
            case 'customer.subscription.deleted':
                $this->handle_subscription_deleted($event->data->object);
                break;
                
            case 'invoice.payment_succeeded':
                $this->handle_invoice_paid($event->data->object);
                break;
                
            case 'invoice.payment_failed':
                $this->handle_invoice_payment_failed($event->data->object);
                break;
                
            case 'account.updated':
                $this->handle_connect_account_updated($event->data->object);
                break;
                
            case 'payout.paid':
                $this->handle_payout_paid($event->data->object);
                break;
                
            case 'payout.failed':
                $this->handle_payout_failed($event->data->object);
                break;
        }
        
        return new \WP_REST_Response(['received' => true], 200);
    }
    
    /**
     * Handle successful payment
     * 
     * @param object $payment_intent
     */
    private function handle_payment_succeeded($payment_intent) {
        $user_id = $this->get_user_from_customer($payment_intent->customer);
        
        if ($user_id) {
            // Log payment
            $this->log_payment($user_id, [
                'payment_intent_id' => $payment_intent->id,
                'amount' => $payment_intent->amount / 100,
                'currency' => $payment_intent->currency,
                'status' => 'succeeded',
            ]);
            
            // Update subscription status if needed
            if (!empty($payment_intent->metadata->subscription_id)) {
                update_user_meta($user_id, 'subscription_status', 'active');
            }
        }
    }
    
    /**
     * Handle failed payment
     * 
     * @param object $payment_intent
     */
    private function handle_payment_failed($payment_intent) {
        $user_id = $this->get_user_from_customer($payment_intent->customer);
        
        if ($user_id) {
            // Log failed payment
            $this->log_payment($user_id, [
                'payment_intent_id' => $payment_intent->id,
                'amount' => $payment_intent->amount / 100,
                'currency' => $payment_intent->currency,
                'status' => 'failed',
                'error' => $payment_intent->last_payment_error->message ?? 'Unknown error',
            ]);
            
            // Send dunning email
            $this->send_dunning_email($user_id, 'payment_failed', [
                'amount' => $payment_intent->amount / 100,
                'error' => $payment_intent->last_payment_error->message ?? 'Unknown error',
            ]);
        }
    }
    
    /**
     * Calculate and process payouts
     */
    public function calculate_payouts() {
        global $wpdb;
        
        // Get users with pending payouts
        $query = "
            SELECT 
                u.ID as user_id,
                u.user_email,
                SUM(r.amount) as total_revenue,
                COUNT(r.id) as transaction_count
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->prefix}crawlguard_revenue r ON u.ID = r.user_id
            WHERE r.status = 'pending'
            AND r.amount > 0
            GROUP BY u.ID
            HAVING total_revenue >= %f
        ";
        
        $users_with_revenue = $wpdb->get_results(
            $wpdb->prepare($query, $this->min_payout_threshold)
        );
        
        foreach ($users_with_revenue as $user_data) {
            $this->process_payout($user_data->user_id, $user_data->total_revenue);
        }
    }
    
    /**
     * Process payout for user
     * 
     * @param int $user_id
     * @param float $amount
     * @return bool|WP_Error
     */
    private function process_payout($user_id, $amount) {
        try {
            $connect_account_id = get_user_meta($user_id, 'stripe_connect_account_id', true);
            
            if (!$connect_account_id) {
                return new \WP_Error('no_connect_account', 'User has no connected Stripe account');
            }
            
            // Calculate platform fee
            $platform_fee = $amount * $this->platform_fee_percent;
            $payout_amount = $amount - $platform_fee;
            
            // Create transfer to connected account
            $transfer = $this->stripe->transfers->create([
                'amount' => intval($payout_amount * 100), // Convert to cents
                'currency' => 'usd',
                'destination' => $connect_account_id,
                'description' => 'CrawlGuard revenue payout',
                'metadata' => [
                    'user_id' => $user_id,
                    'gross_amount' => $amount,
                    'platform_fee' => $platform_fee,
                    'net_amount' => $payout_amount,
                ],
            ]);
            
            // Log payout
            $this->log_payout($user_id, [
                'transfer_id' => $transfer->id,
                'gross_amount' => $amount,
                'platform_fee' => $platform_fee,
                'net_amount' => $payout_amount,
                'status' => 'processed',
            ]);
            
            // Mark revenue as paid
            $this->mark_revenue_paid($user_id);
            
            // Send payout notification
            $this->send_payout_notification($user_id, $payout_amount);
            
            return true;
            
        } catch (Exception $e) {
            $this->log_error('Payout failed for user ' . $user_id . ': ' . $e->getMessage());
            return new \WP_Error('payout_failed', $e->getMessage());
        }
    }
    
    /**
     * Send dunning email
     * 
     * @param int $user_id
     * @param string $type
     * @param array $data
     */
    private function send_dunning_email($user_id, $type, $data = []) {
        $user = get_user_by('id', $user_id);
        if (!$user) return;
        
        $templates = [
            'payment_failed' => [
                'subject' => 'Payment Failed - Action Required',
                'message' => 'Your recent payment of $%s failed. Please update your payment method to continue your subscription.',
            ],
            'card_expiring' => [
                'subject' => 'Your Card is Expiring Soon',
                'message' => 'The card ending in %s expires soon. Please update your payment method.',
            ],
            'subscription_past_due' => [
                'subject' => 'Your Subscription is Past Due',
                'message' => 'Your CrawlGuard subscription is past due. Please update your payment method within 3 days to avoid service interruption.',
            ],
        ];
        
        if (isset($templates[$type])) {
            $template = $templates[$type];
            $subject = $template['subject'];
            $message = sprintf($template['message'], ...array_values($data));
            
            wp_mail(
                $user->user_email,
                $subject,
                $message,
                ['Content-Type: text/html; charset=UTF-8']
            );
            
            // Log email sent
            $this->log_dunning_email($user_id, $type);
        }
    }
    
    /**
     * Generate 1099 tax documents
     * 
     * @param int $year
     * @return array
     */
    public function generate_tax_documents($year = null) {
        global $wpdb;
        
        if (!$year) {
            $year = date('Y') - 1; // Previous year by default
        }
        
        // Get US users with payouts over $600
        $query = "
            SELECT 
                u.ID as user_id,
                u.user_email,
                u.display_name,
                um.meta_value as tax_id,
                SUM(p.net_amount) as total_payouts
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->prefix}crawlguard_payouts p ON u.ID = p.user_id
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'tax_id'
            WHERE YEAR(p.created_at) = %d
            AND p.status = 'completed'
            GROUP BY u.ID
            HAVING total_payouts >= 600
        ";
        
        $users_requiring_1099 = $wpdb->get_results($wpdb->prepare($query, $year));
        
        $documents = [];
        
        foreach ($users_requiring_1099 as $user_data) {
            // Generate 1099 document
            $document = $this->create_1099_document($user_data, $year);
            $documents[] = $document;
            
            // Send to user
            $this->send_tax_document($user_data->user_id, $document);
        }
        
        return $documents;
    }
    
    /**
     * Log error
     * 
     * @param string $message
     */
    private function log_error($message) {
        error_log('[CrawlGuard Stripe] ' . $message);
        
        // Also log to database for admin visibility
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'crawlguard_error_logs',
            [
                'module' => 'stripe',
                'message' => $message,
                'created_at' => current_time('mysql'),
            ]
        );
    }
    
    /**
     * Get user ID from Stripe customer ID
     * 
     * @param string $customer_id
     * @return int|null
     */
    private function get_user_from_customer($customer_id) {
        global $wpdb;
        
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} 
             WHERE meta_key = 'stripe_customer_id' 
             AND meta_value = %s",
            $customer_id
        ));
        
        return $user_id ? intval($user_id) : null;
    }
    
    /**
     * Log subscription event
     * 
     * @param int $user_id
     * @param string $event
     * @param array $data
     */
    private function log_subscription_event($user_id, $event, $data = []) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'crawlguard_subscription_logs',
            [
                'user_id' => $user_id,
                'event' => $event,
                'data' => json_encode($data),
                'created_at' => current_time('mysql'),
            ]
        );
    }
    
    /**
     * Log payment
     * 
     * @param int $user_id
     * @param array $data
     */
    private function log_payment($user_id, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'crawlguard_payments',
            [
                'user_id' => $user_id,
                'payment_intent_id' => $data['payment_intent_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'status' => $data['status'],
                'error' => $data['error'] ?? null,
                'created_at' => current_time('mysql'),
            ]
        );
    }
    
    /**
     * Log payout
     * 
     * @param int $user_id
     * @param array $data
     */
    private function log_payout($user_id, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'crawlguard_payouts',
            [
                'user_id' => $user_id,
                'transfer_id' => $data['transfer_id'],
                'gross_amount' => $data['gross_amount'],
                'platform_fee' => $data['platform_fee'],
                'net_amount' => $data['net_amount'],
                'status' => $data['status'],
                'created_at' => current_time('mysql'),
            ]
        );
    }
}
