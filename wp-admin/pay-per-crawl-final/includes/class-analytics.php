<?php
/**
 * Analytics Handler
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PayPerCrawl_Analytics {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
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
        // Constructor intentionally empty
    }
    
    /**
     * Get dashboard stats
     */
    public function get_dashboard_stats() {
        // Use transient caching (5 minutes)
        $cache_key = 'paypercrawl_dashboard_stats';
        $stats = get_transient($cache_key);
        
        if ($stats === false) {
            if (class_exists('PayPerCrawl_DB')) {
                $db = PayPerCrawl_DB::get_instance();
                $stats = $db->get_stats();
                set_transient($cache_key, $stats, 5 * MINUTE_IN_SECONDS);
            } else {
                $stats = $this->get_default_stats();
            }
        }
        
        return $stats;
    }
    
    /**
     * Get chart data for dashboard
     */
    public function get_chart_data() {
        // Use transient caching (5 minutes)
        $cache_key = 'paypercrawl_chart_data';
        $data = get_transient($cache_key);
        
        if ($data === false) {
            if (class_exists('PayPerCrawl_DB')) {
                $db = PayPerCrawl_DB::get_instance();
                $data = $db->get_chart_data(30);
                set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);
            } else {
                $data = $this->get_default_chart_data();
            }
        }
        
        return $data;
    }
    
    /**
     * Calculate potential earnings
     */
    public function calculate_potential_earnings($detections_count) {
        // Example rates per detection type
        $rates = array(
            'premium' => 0.10, // OpenAI, Anthropic, etc.
            'standard' => 0.05, // Google, Microsoft, etc.
            'basic' => 0.02    // Generic bots
        );
        
        // For now, use average rate
        $average_rate = 0.05;
        return $detections_count * $average_rate;
    }
    
    /**
     * Get export data
     */
    public function get_export_data($days = 30) {
        if (class_exists('PayPerCrawl_DB')) {
            $db = PayPerCrawl_DB::get_instance();
            return $db->get_export_data($days);
        }
        
        return array();
    }
    
    /**
     * Generate CSV export
     */
    public function generate_csv_export($days = 30) {
        $data = $this->get_export_data($days);
        
        if (empty($data)) {
            return false;
        }
        
        $filename = 'paypercrawl-export-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Timestamp',
            'IP Address',
            'User Agent',
            'Bot Company',
            'Confidence Score',
            'Action Taken',
            'URL'
        ));
        
        // CSV data
        foreach ($data as $row) {
            fputcsv($output, array(
                $row->timestamp,
                $row->ip_address,
                $row->user_agent,
                $row->bot_company,
                $row->confidence_score,
                $row->action_taken,
                $row->url
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get recent detections for dashboard
     */
    public function get_recent_detections($limit = 5) {
        if (class_exists('PayPerCrawl_DB')) {
            $db = PayPerCrawl_DB::get_instance();
            return $db->get_recent_detections($limit);
        }
        
        return array();
    }
    
    /**
     * Default stats for when no data exists
     */
    private function get_default_stats() {
        return array(
            'today' => (object) array(
                'count' => 0,
                'unique_ips' => 0,
                'unique_bots' => 0
            ),
            'total' => (object) array(
                'count' => 0,
                'unique_ips' => 0,
                'unique_bots' => 0
            ),
            'top_bots' => array()
        );
    }
    
    /**
     * Default chart data for when no data exists
     */
    private function get_default_chart_data() {
        $labels = array();
        $detections = array();
        $unique_ips = array();
        
        // Generate 30 days of empty data
        for ($i = 29; $i >= 0; $i--) {
            $date = date('M j', strtotime('-' . $i . ' days'));
            $labels[] = $date;
            $detections[] = 0;
            $unique_ips[] = 0;
        }
        
        return array(
            'labels' => $labels,
            'detections' => $detections,
            'unique_ips' => $unique_ips
        );
    }
    
    /**
     * Get analytics summary for API
     */
    public function get_analytics_summary() {
        $stats = $this->get_dashboard_stats();
        $chart_data = $this->get_chart_data();
        
        return array(
            'today_detections' => $stats['today']->count,
            'total_detections' => $stats['total']->count,
            'unique_ips_today' => $stats['today']->unique_ips,
            'unique_bots_today' => $stats['today']->unique_bots,
            'potential_earnings_today' => $this->calculate_potential_earnings($stats['today']->count),
            'potential_earnings_total' => $this->calculate_potential_earnings($stats['total']->count),
            'top_bot_companies' => $stats['top_bots'],
            'chart_data' => $chart_data
        );
    }
    
    /**
     * Track plugin usage (opt-in telemetry)
     */
    public function track_usage() {
        // Only if user has opted in
        if (get_option('paypercrawl_allow_tracking', '0') !== '1') {
            return;
        }
        
        $stats = $this->get_dashboard_stats();
        
        $telemetry_data = array(
            'site_url' => get_site_url(),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => PAYPERCRAWL_VERSION,
            'php_version' => PHP_VERSION,
            'total_detections' => $stats['total']->count,
            'active_since' => get_option('paypercrawl_activated_date', date('Y-m-d'))
        );
        
        // Send to PayPerCrawl API (when available)
        $api_url = get_option('paypercrawl_api_url', '');
        if (!empty($api_url)) {
            wp_remote_post($api_url . '/telemetry', array(
                'body' => json_encode($telemetry_data),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . get_option('paypercrawl_api_key', '')
                )
            ));
        }
    }
}
