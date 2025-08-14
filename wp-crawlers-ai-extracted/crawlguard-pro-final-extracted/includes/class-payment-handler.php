<?php
/**
 * Payment Handler for CrawlGuard Pro
 * 
 * Handles monetization and payment processing
 * Stripe-ready but functional without payment gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Payment_Handler {
    
    private $config;
    private $logger;
    private $stripe_enabled = false;
    
    public function __construct() {
        $this->config = CrawlGuard_Config::get_instance();
        
        if (class_exists('CrawlGuard_Error_Logger')) {
            $this->logger = new CrawlGuard_Error_Logger();
        }
        
        // Check if Stripe is configured
        $stripe_config = $this->config->get_stripe_config();
        if (!empty($stripe_config['secret_key']) && $stripe_config['secret_key'] !== 'PENDING_SETUP_CREATE_STRIPE_ACCOUNT') {
            $this->stripe_enabled = true;
            $this->init_stripe();
        }
    }
    
    /**
     * Process payment for bot detection monetization
     */
    public function process_bot_monetization($bot_data) {
        $revenue = $this->calculate_revenue($bot_data);
        
        // Record the transaction regardless of Stripe status
        $transaction_id = $this->record_transaction($bot_data, $revenue);
        
        if ($this->stripe_enabled && $revenue > 0.001) { // Minimum $0.001 for Stripe
            return $this->process_stripe_payment($bot_data, $revenue, $transaction_id);
        }
        
        // Return successful tracking even without payment processing
        return array(
            'success' => true,
            'revenue' => $revenue,
            'transaction_id' => $transaction_id,
            'payment_processed' => false,
            'message' => 'Revenue tracked successfully'
        );
    }
    
    /**
     * Calculate revenue based on bot type and company
     */
    private function calculate_revenue($bot_data) {
        $base_rates = array(
            'gptbot' => 0.002,
            'chatgpt-user' => 0.002,
            'anthropic-ai' => 0.0015,
            'claude-web' => 0.0015,
            'bard' => 0.001,
            'palm' => 0.001,
            'google-extended' => 0.001,
            'googlebot' => 0.0005,
            'ccbot' => 0.001,
            'facebookbot' => 0.0008,
            'bingbot' => 0.0007
        );
        
        $bot_type = strtolower($bot_data['bot_type'] ?? 'unknown');
        $base_rate = $base_rates[$bot_type] ?? 0.0005; // Default rate
        
        // Apply multipliers based on content type or page value
        $multiplier = 1.0;
        
        // Premium content multiplier
        if (isset($bot_data['page_type']) && $bot_data['page_type'] === 'premium') {
            $multiplier *= 2.0;
        }
        
        // High-traffic site multiplier
        $daily_traffic = get_option('crawlguard_daily_traffic', 1000);
        if ($daily_traffic > 10000) {
            $multiplier *= 1.5;
        }
        
        return round($base_rate * $multiplier, 4);
    }
    
    /**
     * Record transaction in database
     */
    private function record_transaction($bot_data, $revenue) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_payments';
        
        // Create table if not exists
        $this->create_payments_table();
        
        $transaction_data = array(
            'bot_type' => $bot_data['bot_type'] ?? 'unknown',
            'ip_address' => $bot_data['ip_address'] ?? '',
            'user_agent' => $bot_data['user_agent'] ?? '',
            'page_url' => $bot_data['page_url'] ?? '',
            'revenue' => $revenue,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'site_url' => get_site_url()
        );
        
        $wpdb->insert($table_name, $transaction_data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Process payment via Stripe
     */
    private function process_stripe_payment($bot_data, $revenue, $transaction_id) {
        if (!$this->stripe_enabled) {
            return array('success' => false, 'error' => 'Stripe not configured');
        }
        
        try {
            // Create payment intent
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => intval($revenue * 100000), // Convert to smallest currency unit
                'currency' => 'usd',
                'description' => 'AI Bot Access Fee - ' . $bot_data['bot_type'],
                'metadata' => [
                    'transaction_id' => $transaction_id,
                    'bot_type' => $bot_data['bot_type'],
                    'site_url' => get_site_url(),
                    'plugin_version' => CRAWLGUARD_VERSION
                ]
            ]);
            
            // Update transaction with Stripe payment intent
            $this->update_transaction($transaction_id, array(
                'stripe_payment_intent' => $payment_intent->id,
                'status' => 'processing'
            ));
            
            return array(
                'success' => true,
                'payment_intent' => $payment_intent->id,
                'revenue' => $revenue,
                'transaction_id' => $transaction_id
            );
            
        } catch (\Stripe\Exception\CardException $e) {
            $this->logger->error('Stripe card error', ['error' => $e->getMessage()]);
            return array('success' => false, 'error' => 'Payment failed: ' . $e->getMessage());
            
        } catch (\Stripe\Exception\RateLimitException $e) {
            $this->logger->error('Stripe rate limit', ['error' => $e->getMessage()]);
            return array('success' => false, 'error' => 'Service temporarily unavailable');
            
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $this->logger->error('Stripe invalid request', ['error' => $e->getMessage()]);
            return array('success' => false, 'error' => 'Invalid payment request');
            
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $this->logger->error('Stripe auth error', ['error' => $e->getMessage()]);
            return array('success' => false, 'error' => 'Payment authentication failed');
            
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $this->logger->error('Stripe connection error', ['error' => $e->getMessage()]);
            return array('success' => false, 'error' => 'Payment service unavailable');
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logger->error('Stripe API error', ['error' => $e->getMessage()]);
            return array('success' => false, 'error' => 'Payment processing error');
            
        } catch (Exception $e) {
            $this->logger->error('General payment error', ['error' => $e->getMessage()]);
            return array('success' => false, 'error' => 'Unexpected payment error');
        }
    }
    
    /**
     * Create payments table
     */
    private function create_payments_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_payments';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bot_type varchar(50) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            page_url text,
            revenue decimal(10,4) NOT NULL DEFAULT 0.0000,
            status varchar(20) DEFAULT 'pending',
            stripe_payment_intent varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            site_url varchar(255),
            PRIMARY KEY (id),
            KEY bot_type (bot_type),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Update transaction record
     */
    private function update_transaction($transaction_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_payments';
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $transaction_id),
            null,
            array('%d')
        );
    }
    
    /**
     * Get revenue statistics
     */
    public function get_revenue_stats($period = 'today') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_payments';
        
        switch ($period) {
            case 'today':
                $where = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'all':
            default:
                $where = "1=1";
                break;
        }
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_transactions,
                SUM(revenue) as total_revenue,
                AVG(revenue) as avg_revenue,
                COUNT(DISTINCT bot_type) as unique_bots
            FROM $table_name 
            WHERE $where
        ", ARRAY_A);
        
        return $stats ?: array(
            'total_transactions' => 0,
            'total_revenue' => 0,
            'avg_revenue' => 0,
            'unique_bots' => 0
        );
    }
    
    /**
     * Get top earning bot types
     */
    public function get_top_earning_bots($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_payments';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                bot_type,
                COUNT(*) as request_count,
                SUM(revenue) as total_revenue,
                AVG(revenue) as avg_revenue
            FROM $table_name 
            WHERE revenue > 0
            GROUP BY bot_type
            ORDER BY total_revenue DESC
            LIMIT %d
        ", $limit), ARRAY_A);
    }
    
    /**
     * Handle Stripe webhook
     */
    public function handle_stripe_webhook($payload, $sig_header) {
        if (!$this->stripe_enabled) {
            return false;
        }
        
        $endpoint_secret = $this->config->get_stripe_config()['webhook_secret'];
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Invalid webhook payload', ['error' => $e->getMessage()]);
            return false;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->logger->error('Invalid webhook signature', ['error' => $e->getMessage()]);
            return false;
        }
        
        // Handle the event
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                $this->handle_payment_success($event['data']['object']);
                break;
            case 'payment_intent.payment_failed':
                $this->handle_payment_failure($event['data']['object']);
                break;
            default:
                $this->logger->info('Unhandled webhook event', ['type' => $event['type']]);
        }
        
        return true;
    }
    
    /**
     * Handle successful payment
     */
    private function handle_payment_success($payment_intent) {
        $transaction_id = $payment_intent['metadata']['transaction_id'] ?? null;
        
        if ($transaction_id) {
            $this->update_transaction($transaction_id, array(
                'status' => 'completed',
                'stripe_payment_intent' => $payment_intent['id']
            ));
            
            $this->logger->info('Payment completed', ['transaction_id' => $transaction_id]);
        }
    }
    
    /**
     * Handle failed payment
     */
    private function handle_payment_failure($payment_intent) {
        $transaction_id = $payment_intent['metadata']['transaction_id'] ?? null;
        
        if ($transaction_id) {
            $this->update_transaction($transaction_id, array(
                'status' => 'failed',
                'stripe_payment_intent' => $payment_intent['id']
            ));
            
            $this->logger->warning('Payment failed', ['transaction_id' => $transaction_id]);
        }
    }
    
    /**
     * Check if Stripe is properly configured
     */
    public function is_stripe_configured() {
        return $this->stripe_enabled;
    }
    
    /**
     * Get payment configuration status
     */
    public function get_payment_status() {
        return array(
            'stripe_enabled' => $this->stripe_enabled,
            'tracking_enabled' => true,
            'webhook_configured' => !empty($this->config->get_stripe_config()['webhook_secret']),
            'environment' => $this->config->get_stripe_config()['environment'] ?? 'test'
        );
    }
}
?>
    
    /**
     * Process bot access payment
     */
    public function process_bot_payment($bot_detection, $content_data) {
        $payment_data = [
            'bot_type' => $bot_detection['bot_type'],
            'company' => $bot_detection['company'],
            'rate' => $bot_detection['rate'],
            'content_url' => $content_data['url'],
            'content_type' => $content_data['type'],
            'timestamp' => current_time('timestamp'),
            'status' => 'pending'
        ];
        
        // Calculate payment amount
        $amount = $this->calculate_payment_amount($bot_detection, $content_data);
        $payment_data['amount'] = $amount;
        
        // If Stripe is enabled, create payment intent
        if ($this->stripe_enabled) {
            $payment_data = $this->create_stripe_payment($payment_data);
        } else {
            // Record as pending payment for future processing
            $payment_data['status'] = 'recorded';
            $payment_data['note'] = 'Payment gateway not configured';
        }
        
        // Store payment record
        $this->store_payment_record($payment_data);
        
        return $payment_data;
    }
    
    /**
     * Calculate payment amount based on content and bot type
     */
    private function calculate_payment_amount($bot_detection, $content_data) {
        $base_rate = $bot_detection['rate'];
        
        // Apply content-based multipliers
        $multiplier = 1.0;
        
        // Word count multiplier
        if (isset($content_data['word_count'])) {
            if ($content_data['word_count'] > 2000) {
                $multiplier *= 1.5;
            } elseif ($content_data['word_count'] > 1000) {
                $multiplier *= 1.2;
            }
        }
        
        // Content type multiplier
        if (isset($content_data['type'])) {
            switch ($content_data['type']) {
                case 'premium':
                    $multiplier *= 2.0;
                    break;
                case 'technical':
                    $multiplier *= 1.5;
                    break;
                case 'news':
                    $multiplier *= 1.2;
                    break;
            }
        }
        
        // Freshness multiplier
        if (isset($content_data['publish_date'])) {
            $age_days = (time() - strtotime($content_data['publish_date'])) / 86400;
            if ($age_days < 7) {
                $multiplier *= 1.3; // Fresh content premium
            }
        }
        
        return $base_rate * $multiplier;
    }
    
    /**
     * Create Stripe payment (when enabled)
     */
    private function create_stripe_payment($payment_data) {
        try {
            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => intval($payment_data['amount'] * 1000), // Convert to cents
                'currency' => 'usd',
                'metadata' => [
                    'bot_type' => $payment_data['bot_type'],
                    'company' => $payment_data['company'],
                    'content_url' => $payment_data['content_url'],
                    'site_id' => $this->config->get_site_id()
                ],
                'description' => sprintf('AI content access: %s (%s)', $payment_data['bot_type'], $payment_data['company'])
            ]);
            
            $payment_data['stripe_payment_intent_id'] = $payment_intent->id;
            $payment_data['status'] = 'created';
            
        } catch (Exception $e) {
            $this->logger->error('Stripe payment creation failed', [
                'error' => $e->getMessage(),
                'payment_data' => $payment_data
            ]);
            $payment_data['status'] = 'failed';
            $payment_data['error'] = $e->getMessage();
        }
        
        return $payment_data;
    }
    
    /**
     * Store payment record in database
     */
    private function store_payment_record($payment_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_payments';
        
        // Create table if not exists
        $this->create_payments_table();
        
        $result = $wpdb->insert(
            $table_name,
            [
                'bot_type' => $payment_data['bot_type'],
                'company' => $payment_data['company'],
                'amount' => $payment_data['amount'],
                'status' => $payment_data['status'],
                'content_url' => $payment_data['content_url'],
                'payment_data' => json_encode($payment_data),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%f', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            $this->logger->error('Failed to store payment record', [
                'error' => $wpdb->last_error,
                'data' => $payment_data
            ]);
        }
        
        return $result;
    }
    
    /**
     * Create payments table
     */
    private function create_payments_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_payments';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bot_type varchar(50) NOT NULL,
            company varchar(100) NOT NULL,
            amount decimal(10,4) NOT NULL,
            status varchar(20) NOT NULL,
            content_url text,
            payment_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY company (company),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get payment statistics
     */
    public function get_payment_stats($period = 'today') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'crawlguard_payments';
        
        $date_condition = '';
        switch ($period) {
            case 'today':
                $date_condition = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_requests,
                SUM(amount) as total_revenue,
                AVG(amount) as avg_payment,
                COUNT(DISTINCT company) as unique_companies
            FROM $table_name
            WHERE $date_condition",
            ARRAY_A
        );
        
        // Get company breakdown
        $company_stats = $wpdb->get_results(
            "SELECT 
                company,
                COUNT(*) as requests,
                SUM(amount) as revenue
            FROM $table_name
            WHERE $date_condition
            GROUP BY company
            ORDER BY revenue DESC",
            ARRAY_A
        );
        
        return [
            'summary' => $stats,
            'by_company' => $company_stats,
            'stripe_enabled' => $this->stripe_enabled
        ];
    }
    
    /**
     * Get revenue share calculation
     */
    public function calculate_revenue_share($amount) {
        $revenue_config = $this->config->get_revenue_config();
        
        $platform_fee = $amount * ($revenue_config['platform_fee'] / 100);
        $publisher_share = $amount * ($revenue_config['publisher_share'] / 100);
        
        return [
            'total' => $amount,
            'platform_fee' => $platform_fee,
            'publisher_share' => $publisher_share,
            'platform_percentage' => $revenue_config['platform_fee'],
            'publisher_percentage' => $revenue_config['publisher_share']
        ];
    }
}
