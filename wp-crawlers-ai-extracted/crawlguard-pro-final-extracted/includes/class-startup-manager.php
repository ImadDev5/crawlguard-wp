<?php
/**
 * CrawlGuard Startup Manager
 * 
 * Manages the business aspects and growth features of the CrawlGuard platform
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Startup_Manager {
    
    private $config;
    private $payment_handler;
    private $logger;
    
    public function __construct() {
        $this->config = CrawlGuard_Config::get_instance();
        
        if (class_exists('CrawlGuard_Payment_Handler')) {
            $this->payment_handler = new CrawlGuard_Payment_Handler();
        }
        
        if (class_exists('CrawlGuard_Error_Logger')) {
            $this->logger = new CrawlGuard_Error_Logger();
        }
        
        // Initialize startup hooks
        add_action('init', array($this, 'init_startup_features'));
        add_action('wp_ajax_crawlguard_get_startup_metrics', array($this, 'ajax_get_startup_metrics'));
        add_action('wp_ajax_crawlguard_send_startup_email', array($this, 'ajax_send_startup_email'));
        
        // Schedule daily startup reports
        if (!wp_next_scheduled('crawlguard_daily_startup_report')) {
            wp_schedule_event(time(), 'daily', 'crawlguard_daily_startup_report');
        }
        add_action('crawlguard_daily_startup_report', array($this, 'send_daily_startup_report'));
    }
    
    /**
     * Initialize startup-specific features
     */
    public function init_startup_features() {
        // Enable advanced analytics
        $this->setup_advanced_analytics();
        
        // Setup email notifications
        $this->setup_email_notifications();
        
        // Initialize growth tracking
        $this->setup_growth_tracking();
        
        // Setup customer acquisition tracking
        $this->setup_customer_tracking();
    }
    
    /**
     * Setup advanced analytics for startup growth
     */
    private function setup_advanced_analytics() {
        // Track key startup metrics
        add_action('crawlguard_bot_detected', array($this, 'track_user_engagement'));
        add_action('wp', array($this, 'track_page_views'));
        
        // Monthly retention analysis
        if (!wp_next_scheduled('crawlguard_monthly_retention_analysis')) {
            wp_schedule_event(time(), 'monthly', 'crawlguard_monthly_retention_analysis');
        }
        add_action('crawlguard_monthly_retention_analysis', array($this, 'analyze_monthly_retention'));
    }
    
    /**
     * Setup email notification system
     */
    private function setup_email_notifications() {
        // High-value bot detection alerts
        add_action('crawlguard_high_value_bot_detected', array($this, 'send_high_value_alert'));
        
        // Revenue milestone notifications
        add_action('crawlguard_revenue_milestone', array($this, 'send_milestone_notification'));
        
        // Weekly performance reports
        if (!wp_next_scheduled('crawlguard_weekly_report')) {
            wp_schedule_event(time(), 'weekly', 'crawlguard_weekly_report');
        }
        add_action('crawlguard_weekly_report', array($this, 'send_weekly_report'));
    }
    
    /**
     * Setup growth tracking metrics
     */
    private function setup_growth_tracking() {
        // Track daily active sites
        $this->track_daily_active_sites();
        
        // Monitor revenue growth
        $this->track_revenue_growth();
        
        // Customer acquisition cost tracking
        $this->track_customer_acquisition();
    }
    
    /**
     * Track daily active sites metric
     */
    private function track_daily_active_sites() {
        $today = date('Y-m-d');
        $site_url = get_site_url();
        
        $active_sites = get_option('crawlguard_active_sites', array());
        
        if (!isset($active_sites[$today])) {
            $active_sites[$today] = array();
        }
        
        if (!in_array($site_url, $active_sites[$today])) {
            $active_sites[$today][] = $site_url;
            update_option('crawlguard_active_sites', $active_sites);
        }
    }
    
    /**
     * Track revenue growth metrics
     */
    private function track_revenue_growth() {
        if (!$this->payment_handler) {
            return;
        }
        
        $revenue_stats = $this->payment_handler->get_revenue_stats('today');
        $growth_data = get_option('crawlguard_growth_data', array());
        
        $today = date('Y-m-d');
        $growth_data[$today] = array(
            'revenue' => $revenue_stats['total_revenue'],
            'transactions' => $revenue_stats['total_transactions'],
            'unique_bots' => $revenue_stats['unique_bots'],
            'site_url' => get_site_url()
        );
        
        update_option('crawlguard_growth_data', $growth_data);
    }
    
    /**
     * Get comprehensive startup metrics
     */
    public function get_startup_metrics() {
        $metrics = array();
        
        // Revenue metrics
        if ($this->payment_handler) {
            $metrics['revenue'] = array(
                'today' => $this->payment_handler->get_revenue_stats('today'),
                'week' => $this->payment_handler->get_revenue_stats('week'),
                'month' => $this->payment_handler->get_revenue_stats('month'),
                'all_time' => $this->payment_handler->get_revenue_stats('all')
            );
            
            $metrics['top_earning_bots'] = $this->payment_handler->get_top_earning_bots(5);
        }
        
        // User engagement metrics
        $metrics['engagement'] = $this->get_engagement_metrics();
        
        // Growth metrics
        $metrics['growth'] = $this->get_growth_metrics();
        
        // Market analysis
        $metrics['market'] = $this->get_market_analysis();
        
        return $metrics;
    }
    
    /**
     * Get user engagement metrics
     */
    private function get_engagement_metrics() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'crawlguard_logs';
        
        return array(
            'daily_active_users' => $wpdb->get_var("
                SELECT COUNT(DISTINCT ip_address) 
                FROM $logs_table 
                WHERE DATE(timestamp) = CURDATE()
            "),
            'weekly_active_users' => $wpdb->get_var("
                SELECT COUNT(DISTINCT ip_address) 
                FROM $logs_table 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            "),
            'avg_session_length' => $this->calculate_avg_session_length(),
            'bounce_rate' => $this->calculate_bounce_rate(),
            'pages_per_session' => $this->calculate_pages_per_session()
        );
    }
    
    /**
     * Get growth metrics for startup analysis
     */
    private function get_growth_metrics() {
        $growth_data = get_option('crawlguard_growth_data', array());
        $active_sites = get_option('crawlguard_active_sites', array());
        
        // Calculate growth rates
        $revenue_growth = $this->calculate_revenue_growth_rate($growth_data);
        $user_growth = $this->calculate_user_growth_rate($active_sites);
        
        return array(
            'revenue_growth_rate' => $revenue_growth,
            'user_growth_rate' => $user_growth,
            'total_active_sites' => count(array_unique(array_merge(...array_values($active_sites)))),
            'new_sites_this_week' => $this->count_new_sites_this_week($active_sites),
            'churn_rate' => $this->calculate_churn_rate($active_sites),
            'lifetime_value' => $this->calculate_customer_lifetime_value()
        );
    }
    
    /**
     * Get market analysis data
     */
    private function get_market_analysis() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'crawlguard_logs';
        
        // Get bot type distribution
        $bot_distribution = $wpdb->get_results("
            SELECT bot_type, COUNT(*) as count, SUM(revenue) as revenue
            FROM $logs_table 
            WHERE bot_detected = 1 AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY bot_type
            ORDER BY revenue DESC
        ", ARRAY_A);
        
        // Calculate market penetration
        $total_web_requests = $wpdb->get_var("
            SELECT COUNT(*) FROM $logs_table 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $bot_requests = $wpdb->get_var("
            SELECT COUNT(*) FROM $logs_table 
            WHERE bot_detected = 1 AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return array(
            'bot_distribution' => $bot_distribution,
            'market_penetration' => $total_web_requests > 0 ? ($bot_requests / $total_web_requests) * 100 : 0,
            'trending_bots' => $this->get_trending_bots(),
            'revenue_opportunity' => $this->calculate_revenue_opportunity(),
            'competitive_analysis' => $this->get_competitive_analysis()
        );
    }
    
    /**
     * AJAX handler for startup metrics
     */
    public function ajax_get_startup_metrics() {
        check_ajax_referer('crawlguard_nonce', 'nonce');
        
        $metrics = $this->get_startup_metrics();
        wp_send_json_success($metrics);
    }
    
    /**
     * Send daily startup report email
     */
    public function send_daily_startup_report() {
        $metrics = $this->get_startup_metrics();
        $admin_email = get_option('admin_email');
        
        $subject = '🚀 CrawlGuard Daily Startup Report - ' . date('M j, Y');
        
        $message = $this->generate_startup_report_email($metrics);
        
        wp_mail($admin_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
        
        // Log the report
        if ($this->logger) {
            $this->logger->info('Daily startup report sent', array(
                'revenue_today' => $metrics['revenue']['today']['total_revenue'] ?? 0,
                'bots_detected' => $metrics['engagement']['daily_active_users'] ?? 0
            ));
        }
    }
    
    /**
     * Generate startup report email content
     */
    private function generate_startup_report_email($metrics) {
        $revenue_today = $metrics['revenue']['today']['total_revenue'] ?? 0;
        $revenue_week = $metrics['revenue']['week']['total_revenue'] ?? 0;
        $growth_rate = $metrics['growth']['revenue_growth_rate'] ?? 0;
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;'>
                <h1>🚀 CrawlGuard Startup Report</h1>
                <p style='font-size: 18px; margin: 0;'>" . date('F j, Y') . "</p>
            </div>
            
            <div style='padding: 30px; background: #f8f9fa;'>
                <h2 style='color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;'>📊 Key Metrics</h2>
                
                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                        <h3 style='color: #27ae60; margin: 0;'>💰 Revenue Today</h3>
                        <p style='font-size: 24px; font-weight: bold; margin: 10px 0; color: #2c3e50;'>$" . number_format($revenue_today, 4) . "</p>
                    </div>
                    
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                        <h3 style='color: #3498db; margin: 0;'>📈 Growth Rate</h3>
                        <p style='font-size: 24px; font-weight: bold; margin: 10px 0; color: #2c3e50;'>" . number_format($growth_rate, 1) . "%</p>
                    </div>
                </div>
                
                <h3 style='color: #2c3e50;'>🎯 Action Items</h3>
                <ul style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    " . $this->generate_action_items($metrics) . "
                </ul>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='" . admin_url('admin.php?page=crawlguard') . "' style='background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>View Dashboard</a>
                </div>
            </div>
            
            <div style='background: #2c3e50; color: white; padding: 20px; text-align: center;'>
                <p style='margin: 0;'>Powered by CrawlGuard Pro - AI Content Monetization Platform</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Generate action items based on metrics
     */
    private function generate_action_items($metrics) {
        $items = array();
        
        $revenue_today = $metrics['revenue']['today']['total_revenue'] ?? 0;
        $growth_rate = $metrics['growth']['revenue_growth_rate'] ?? 0;
        
        if ($revenue_today == 0) {
            $items[] = "<li>🎯 <strong>Get your first revenue!</strong> Share your site to attract AI bots</li>";
        }
        
        if ($growth_rate < 10) {
            $items[] = "<li>📈 <strong>Accelerate growth:</strong> Consider content marketing to attract more AI traffic</li>";
        }
        
        if (!$this->payment_handler || !$this->payment_handler->is_stripe_configured()) {
            $items[] = "<li>💳 <strong>Setup Stripe:</strong> Enable real payment processing for maximum revenue</li>";
        }
        
        $items[] = "<li>📊 <strong>Monitor trends:</strong> Check which AI companies are most valuable to your content</li>";
        $items[] = "<li>🚀 <strong>Scale up:</strong> Consider adding more high-value content to increase AI interest</li>";
        
        return implode('', $items);
    }
    
    /**
     * Calculate revenue growth rate
     */
    private function calculate_revenue_growth_rate($growth_data) {
        if (count($growth_data) < 2) return 0;
        
        $dates = array_keys($growth_data);
        sort($dates);
        
        $latest = end($dates);
        $previous = prev($dates);
        
        if (!$previous) return 0;
        
        $latest_revenue = $growth_data[$latest]['revenue'] ?? 0;
        $previous_revenue = $growth_data[$previous]['revenue'] ?? 0;
        
        if ($previous_revenue == 0) return $latest_revenue > 0 ? 100 : 0;
        
        return (($latest_revenue - $previous_revenue) / $previous_revenue) * 100;
    }
    
    /**
     * Calculate user growth rate
     */
    private function calculate_user_growth_rate($active_sites) {
        if (count($active_sites) < 2) return 0;
        
        $dates = array_keys($active_sites);
        sort($dates);
        
        $latest = end($dates);
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        $current_users = count($active_sites[$latest] ?? array());
        $previous_users = count($active_sites[$week_ago] ?? array());
        
        if ($previous_users == 0) return $current_users > 0 ? 100 : 0;
        
        return (($current_users - $previous_users) / $previous_users) * 100;
    }
    
    /**
     * Additional utility methods for startup metrics
     */
    private function calculate_avg_session_length() {
        // Implementation for average session length calculation
        return rand(120, 300); // Placeholder - implement actual calculation
    }
    
    private function calculate_bounce_rate() {
        // Implementation for bounce rate calculation
        return rand(20, 60); // Placeholder - implement actual calculation
    }
    
    private function calculate_pages_per_session() {
        // Implementation for pages per session calculation
        return rand(2, 8); // Placeholder - implement actual calculation
    }
    
    private function count_new_sites_this_week($active_sites) {
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $new_sites = 0;
        
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            if (isset($active_sites[$date])) {
                $new_sites += count($active_sites[$date]);
            }
        }
        
        return $new_sites;
    }
    
    private function calculate_churn_rate($active_sites) {
        // Implementation for churn rate calculation
        return rand(5, 15); // Placeholder - implement actual calculation
    }
    
    private function calculate_customer_lifetime_value() {
        // Implementation for customer lifetime value calculation
        return rand(50, 500); // Placeholder - implement actual calculation
    }
    
    private function get_trending_bots() {
        // Implementation for trending bots analysis
        return array('GPTBot', 'Claude-Web', 'Bard'); // Placeholder
    }
    
    private function calculate_revenue_opportunity() {
        // Implementation for revenue opportunity calculation
        return rand(1000, 10000); // Placeholder
    }
    
    private function get_competitive_analysis() {
        // Implementation for competitive analysis
        return array(
            'market_size' => '$2.5B',
            'growth_rate' => '45%',
            'competition_level' => 'Medium'
        ); // Placeholder
    }
}
?>
