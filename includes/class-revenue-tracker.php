<?php
/**
 * Revenue Tracker and Payout Engine
 * 
 * Tracks bot visits, calculates revenue, and manages payouts
 * 
 * @package CrawlGuard
 * @since 1.0.0
 */

namespace CrawlGuard;

if (!defined('ABSPATH')) {
    exit;
}

class Revenue_Tracker {
    
    /**
     * Revenue per bot visit (default)
     * @var float
     */
    private $revenue_per_visit = 0.001; // $0.001 per visit
    
    /**
     * Revenue rates by bot type
     * @var array
     */
    private $bot_revenue_rates = [
        'googlebot' => 0.002,
        'bingbot' => 0.0015,
        'openai' => 0.005,
        'anthropic' => 0.005,
        'perplexity' => 0.004,
        'chatgpt' => 0.005,
        'claude' => 0.005,
        'bard' => 0.003,
        'facebook' => 0.001,
        'twitter' => 0.001,
        'linkedin' => 0.0015,
        'other' => 0.001,
    ];
    
    /**
     * Instance
     */
    private static $instance = null;
    
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
        $this->init_hooks();
        $this->create_tables();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Track bot visits
        add_action('crawlguard_bot_detected', [$this, 'track_bot_visit'], 10, 2);
        
        // Cron jobs for calculations
        add_action('crawlguard_calculate_daily_revenue', [$this, 'calculate_daily_revenue']);
        add_action('crawlguard_process_weekly_payouts', [$this, 'process_weekly_payouts']);
        add_action('crawlguard_process_monthly_payouts', [$this, 'process_monthly_payouts']);
        
        // Schedule cron jobs
        if (!wp_next_scheduled('crawlguard_calculate_daily_revenue')) {
            wp_schedule_event(time(), 'daily', 'crawlguard_calculate_daily_revenue');
        }
        
        if (!wp_next_scheduled('crawlguard_process_weekly_payouts')) {
            wp_schedule_event(strtotime('next Friday 00:00:00'), 'weekly', 'crawlguard_process_weekly_payouts');
        }
        
        if (!wp_next_scheduled('crawlguard_process_monthly_payouts')) {
            wp_schedule_event(strtotime('first day of next month 00:00:00'), 'monthly', 'crawlguard_process_monthly_payouts');
        }
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bot visits table
        $visits_table = $wpdb->prefix . 'crawlguard_bot_visits';
        $sql_visits = "CREATE TABLE IF NOT EXISTS $visits_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            bot_type varchar(50) NOT NULL,
            bot_name varchar(100),
            ip_address varchar(45) NOT NULL,
            user_agent text,
            url varchar(255) NOT NULL,
            referer varchar(255),
            visit_time datetime DEFAULT CURRENT_TIMESTAMP,
            response_code int(3),
            bytes_sent bigint(20),
            processing_time float,
            revenue decimal(10,6) DEFAULT 0.000000,
            is_billable tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY site_id (site_id),
            KEY user_id (user_id),
            KEY bot_type (bot_type),
            KEY visit_time (visit_time),
            KEY is_billable (is_billable)
        ) $charset_collate;";
        
        // Revenue table
        $revenue_table = $wpdb->prefix . 'crawlguard_revenue';
        $sql_revenue = "CREATE TABLE IF NOT EXISTS $revenue_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            site_id bigint(20) UNSIGNED NOT NULL,
            date date NOT NULL,
            bot_visits int(11) DEFAULT 0,
            billable_visits int(11) DEFAULT 0,
            amount decimal(10,4) DEFAULT 0.0000,
            status enum('pending','processed','paid','cancelled') DEFAULT 'pending',
            payout_id bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_site_date (user_id, site_id, date),
            KEY user_id (user_id),
            KEY site_id (site_id),
            KEY status (status),
            KEY payout_id (payout_id)
        ) $charset_collate;";
        
        // Payouts table
        $payouts_table = $wpdb->prefix . 'crawlguard_payouts';
        $sql_payouts = "CREATE TABLE IF NOT EXISTS $payouts_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            transfer_id varchar(100),
            gross_amount decimal(10,2) DEFAULT 0.00,
            platform_fee decimal(10,2) DEFAULT 0.00,
            net_amount decimal(10,2) DEFAULT 0.00,
            currency varchar(3) DEFAULT 'USD',
            status enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
            payout_method enum('stripe','paypal','bank_transfer') DEFAULT 'stripe',
            payout_schedule enum('weekly','monthly','manual') DEFAULT 'weekly',
            period_start date,
            period_end date,
            processed_at datetime,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY transfer_id (transfer_id),
            KEY period_start (period_start),
            KEY period_end (period_end)
        ) $charset_collate;";
        
        // Sites table for multi-site tracking
        $sites_table = $wpdb->prefix . 'crawlguard_sites';
        $sql_sites = "CREATE TABLE IF NOT EXISTS $sites_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            domain varchar(255) NOT NULL,
            site_name varchar(255),
            is_active tinyint(1) DEFAULT 1,
            tier enum('basic','pro','enterprise') DEFAULT 'basic',
            custom_rate decimal(10,6),
            api_key varchar(64),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY domain (domain),
            KEY user_id (user_id),
            KEY is_active (is_active),
            KEY api_key (api_key)
        ) $charset_collate;";
        
        // Analytics summary table
        $analytics_table = $wpdb->prefix . 'crawlguard_analytics';
        $sql_analytics = "CREATE TABLE IF NOT EXISTS $analytics_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            site_id bigint(20) UNSIGNED,
            date date NOT NULL,
            metric_type enum('visits','revenue','bots','blocked') NOT NULL,
            metric_value decimal(15,4) DEFAULT 0.0000,
            dimension varchar(100),
            dimension_value varchar(255),
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY site_id (site_id),
            KEY date (date),
            KEY metric_type (metric_type),
            KEY dimension (dimension)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_visits);
        dbDelta($sql_revenue);
        dbDelta($sql_payouts);
        dbDelta($sql_sites);
        dbDelta($sql_analytics);
    }
    
    /**
     * Track bot visit
     * 
     * @param array $bot_data Bot detection data
     * @param int $site_id Site ID (optional)
     */
    public function track_bot_visit($bot_data, $site_id = null) {
        global $wpdb;
        
        // Get site ID if not provided
        if (!$site_id) {
            $site_id = $this->get_current_site_id();
        }
        
        // Get user ID from site
        $site = $this->get_site($site_id);
        if (!$site) {
            return false;
        }
        
        // Determine revenue rate
        $bot_type = strtolower($bot_data['type'] ?? 'other');
        $revenue_rate = $this->bot_revenue_rates[$bot_type] ?? $this->revenue_per_visit;
        
        // Apply custom rate if set for the site
        if ($site->custom_rate) {
            $revenue_rate = floatval($site->custom_rate);
        }
        
        // Check if visit is billable (not duplicate within 1 minute)
        $is_billable = $this->is_billable_visit(
            $site_id,
            $bot_data['ip'] ?? '',
            $bot_data['url'] ?? ''
        );
        
        // Calculate revenue
        $revenue = $is_billable ? $revenue_rate : 0;
        
        // Insert visit record
        $wpdb->insert(
            $wpdb->prefix . 'crawlguard_bot_visits',
            [
                'site_id' => $site_id,
                'user_id' => $site->user_id,
                'bot_type' => $bot_type,
                'bot_name' => $bot_data['name'] ?? null,
                'ip_address' => $bot_data['ip'] ?? '',
                'user_agent' => $bot_data['user_agent'] ?? '',
                'url' => $bot_data['url'] ?? '',
                'referer' => $bot_data['referer'] ?? null,
                'response_code' => $bot_data['response_code'] ?? 200,
                'bytes_sent' => $bot_data['bytes_sent'] ?? null,
                'processing_time' => $bot_data['processing_time'] ?? null,
                'revenue' => $revenue,
                'is_billable' => $is_billable ? 1 : 0,
            ]
        );
        
        // Update real-time analytics
        $this->update_analytics($site->user_id, $site_id, [
            'bot_type' => $bot_type,
            'revenue' => $revenue,
        ]);
        
        // Trigger webhook if configured
        if ($site->webhook_url) {
            $this->send_webhook_notification($site->webhook_url, [
                'event' => 'bot_visit',
                'site_id' => $site_id,
                'bot_type' => $bot_type,
                'revenue' => $revenue,
                'timestamp' => current_time('mysql'),
            ]);
        }
        
        return true;
    }
    
    /**
     * Check if visit is billable
     * 
     * @param int $site_id
     * @param string $ip
     * @param string $url
     * @return bool
     */
    private function is_billable_visit($site_id, $ip, $url) {
        global $wpdb;
        
        // Check for duplicate visit within last 60 seconds
        $recent_visit = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}crawlguard_bot_visits
             WHERE site_id = %d
             AND ip_address = %s
             AND url = %s
             AND visit_time > DATE_SUB(NOW(), INTERVAL 60 SECOND)",
            $site_id,
            $ip,
            $url
        ));
        
        return $recent_visit == 0;
    }
    
    /**
     * Calculate daily revenue
     */
    public function calculate_daily_revenue() {
        global $wpdb;
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Aggregate revenue by user and site
        $query = "
            INSERT INTO {$wpdb->prefix}crawlguard_revenue 
            (user_id, site_id, date, bot_visits, billable_visits, amount, status)
            SELECT 
                user_id,
                site_id,
                DATE(visit_time) as date,
                COUNT(*) as bot_visits,
                SUM(is_billable) as billable_visits,
                SUM(revenue) as amount,
                'pending' as status
            FROM {$wpdb->prefix}crawlguard_bot_visits
            WHERE DATE(visit_time) = %s
            GROUP BY user_id, site_id, DATE(visit_time)
            ON DUPLICATE KEY UPDATE
                bot_visits = VALUES(bot_visits),
                billable_visits = VALUES(billable_visits),
                amount = VALUES(amount)
        ";
        
        $wpdb->query($wpdb->prepare($query, $yesterday));
        
        // Update analytics summary
        $this->update_daily_analytics($yesterday);
    }
    
    /**
     * Process weekly payouts
     */
    public function process_weekly_payouts() {
        $this->process_payouts('weekly');
    }
    
    /**
     * Process monthly payouts
     */
    public function process_monthly_payouts() {
        $this->process_payouts('monthly');
    }
    
    /**
     * Process payouts
     * 
     * @param string $schedule 'weekly' or 'monthly'
     */
    private function process_payouts($schedule = 'weekly') {
        global $wpdb;
        
        // Get Stripe integration
        $stripe = Stripe_Integration::get_instance();
        
        // Determine period
        if ($schedule === 'weekly') {
            $period_start = date('Y-m-d', strtotime('-1 week'));
            $period_end = date('Y-m-d', strtotime('-1 day'));
        } else {
            $period_start = date('Y-m-01', strtotime('-1 month'));
            $period_end = date('Y-m-t', strtotime('-1 month'));
        }
        
        // Get users with pending revenue
        $query = "
            SELECT 
                r.user_id,
                u.user_email,
                SUM(r.amount) as total_revenue,
                COUNT(DISTINCT r.site_id) as site_count,
                COUNT(DISTINCT r.date) as days_active
            FROM {$wpdb->prefix}crawlguard_revenue r
            INNER JOIN {$wpdb->users} u ON r.user_id = u.ID
            WHERE r.status = 'pending'
            AND r.date BETWEEN %s AND %s
            GROUP BY r.user_id
            HAVING total_revenue >= 25.00
        ";
        
        $users = $wpdb->get_results($wpdb->prepare($query, $period_start, $period_end));
        
        foreach ($users as $user) {
            // Check user's payout schedule preference
            $user_schedule = get_user_meta($user->user_id, 'payout_schedule', true) ?: 'weekly';
            
            if ($user_schedule !== $schedule) {
                continue;
            }
            
            // Create payout record
            $payout_id = $wpdb->insert(
                $wpdb->prefix . 'crawlguard_payouts',
                [
                    'user_id' => $user->user_id,
                    'gross_amount' => $user->total_revenue,
                    'platform_fee' => $user->total_revenue * 0.20, // 20% platform fee
                    'net_amount' => $user->total_revenue * 0.80,
                    'status' => 'pending',
                    'payout_schedule' => $schedule,
                    'period_start' => $period_start,
                    'period_end' => $period_end,
                ]
            );
            
            if ($payout_id) {
                // Process payout through Stripe
                $result = $stripe->process_payout(
                    $user->user_id,
                    $user->total_revenue
                );
                
                if (!is_wp_error($result)) {
                    // Update payout status
                    $wpdb->update(
                        $wpdb->prefix . 'crawlguard_payouts',
                        [
                            'status' => 'processing',
                            'transfer_id' => $result['transfer_id'] ?? null,
                            'processed_at' => current_time('mysql'),
                        ],
                        ['id' => $wpdb->insert_id]
                    );
                    
                    // Mark revenue as processed
                    $wpdb->update(
                        $wpdb->prefix . 'crawlguard_revenue',
                        [
                            'status' => 'processed',
                            'payout_id' => $wpdb->insert_id,
                        ],
                        [
                            'user_id' => $user->user_id,
                            'status' => 'pending',
                            'date' => ['BETWEEN', $period_start, $period_end],
                        ]
                    );
                    
                    // Send notification
                    $this->send_payout_notification($user->user_id, [
                        'amount' => $user->total_revenue * 0.80,
                        'period' => "$period_start to $period_end",
                        'sites' => $user->site_count,
                    ]);
                }
            }
        }
    }
    
    /**
     * Get current site ID
     * 
     * @return int|null
     */
    private function get_current_site_id() {
        global $wpdb;
        
        $domain = parse_url(home_url(), PHP_URL_HOST);
        
        $site_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}crawlguard_sites
             WHERE domain = %s
             AND is_active = 1",
            $domain
        ));
        
        return $site_id;
    }
    
    /**
     * Get site by ID
     * 
     * @param int $site_id
     * @return object|null
     */
    private function get_site($site_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}crawlguard_sites
             WHERE id = %d",
            $site_id
        ));
    }
    
    /**
     * Update analytics
     * 
     * @param int $user_id
     * @param int $site_id
     * @param array $data
     */
    private function update_analytics($user_id, $site_id, $data) {
        global $wpdb;
        
        $date = current_time('Y-m-d');
        
        // Update visits metric
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}crawlguard_analytics
             (user_id, site_id, date, metric_type, metric_value, dimension, dimension_value)
             VALUES (%d, %d, %s, 'visits', 1, 'bot_type', %s)
             ON DUPLICATE KEY UPDATE metric_value = metric_value + 1",
            $user_id,
            $site_id,
            $date,
            $data['bot_type']
        ));
        
        // Update revenue metric
        if ($data['revenue'] > 0) {
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}crawlguard_analytics
                 (user_id, site_id, date, metric_type, metric_value)
                 VALUES (%d, %d, %s, 'revenue', %f)
                 ON DUPLICATE KEY UPDATE metric_value = metric_value + VALUES(metric_value)",
                $user_id,
                $site_id,
                $date,
                $data['revenue']
            ));
        }
    }
    
    /**
     * Send payout notification
     * 
     * @param int $user_id
     * @param array $data
     */
    private function send_payout_notification($user_id, $data) {
        $user = get_user_by('id', $user_id);
        if (!$user) return;
        
        $subject = 'Your CrawlGuard Payout is Processing';
        $message = sprintf(
            "Hi %s,\n\n" .
            "Great news! Your CrawlGuard payout of $%.2f is being processed.\n\n" .
            "Period: %s\n" .
            "Sites: %d\n\n" .
            "The funds should arrive in your account within 2-5 business days.\n\n" .
            "You can view your payout history at: %s\n\n" .
            "Best regards,\n" .
            "The CrawlGuard Team",
            $user->display_name,
            $data['amount'],
            $data['period'],
            $data['sites'],
            home_url('/account/payouts/')
        );
        
        wp_mail(
            $user->user_email,
            $subject,
            $message,
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }
    
    /**
     * Get revenue statistics
     * 
     * @param int $user_id
     * @param string $period
     * @return array
     */
    public function get_revenue_stats($user_id, $period = 'month') {
        global $wpdb;
        
        $date_condition = '';
        switch ($period) {
            case 'today':
                $date_condition = "DATE(date) = CURDATE()";
                break;
            case 'week':
                $date_condition = "date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $date_condition = "date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'year':
                $date_condition = "YEAR(date) = YEAR(CURDATE())";
                break;
        }
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT date) as days_active,
                COUNT(DISTINCT site_id) as sites_active,
                SUM(bot_visits) as total_visits,
                SUM(billable_visits) as billable_visits,
                SUM(amount) as total_revenue,
                AVG(amount) as avg_daily_revenue
             FROM {$wpdb->prefix}crawlguard_revenue
             WHERE user_id = %d
             AND $date_condition",
            $user_id
        ));
        
        return [
            'days_active' => $stats->days_active ?? 0,
            'sites_active' => $stats->sites_active ?? 0,
            'total_visits' => $stats->total_visits ?? 0,
            'billable_visits' => $stats->billable_visits ?? 0,
            'total_revenue' => $stats->total_revenue ?? 0,
            'avg_daily_revenue' => $stats->avg_daily_revenue ?? 0,
            'conversion_rate' => $stats->total_visits > 0 
                ? ($stats->billable_visits / $stats->total_visits * 100) 
                : 0,
        ];
    }
    
    /**
     * Get payout history
     * 
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function get_payout_history($user_id, $limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}crawlguard_payouts
             WHERE user_id = %d
             ORDER BY created_at DESC
             LIMIT %d",
            $user_id,
            $limit
        ));
    }
}
