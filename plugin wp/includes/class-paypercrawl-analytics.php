<?php
/**
 * Analytics Engine
 * 
 * @package PayPerCrawl
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PayPerCrawl_Analytics {
    
    /**
     * Get bot statistics for a period
     */
    public function get_bot_stats($start_date = null, $end_date = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                bot_type,
                company,
                COUNT(*) as crawl_count,
                SUM(revenue) as total_revenue,
                AVG(revenue) as avg_revenue,
                MIN(detected_at) as first_seen,
                MAX(detected_at) as last_seen
            FROM $table_name
            WHERE DATE(detected_at) BETWEEN %s AND %s
            GROUP BY bot_type, company
            ORDER BY total_revenue DESC",
            $start_date,
            $end_date
        ));
        
        return $results;
    }
    
    /**
     * Get revenue by day
     */
    public function get_revenue_by_day($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(detected_at) as date,
                COUNT(*) as crawl_count,
                SUM(revenue) as revenue,
                COUNT(DISTINCT bot_type) as unique_bots
            FROM $table_name
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(detected_at)
            ORDER BY date DESC",
            $days
        ));
        
        return $results;
    }
    
    /**
     * Get top crawled pages
     */
    public function get_top_pages($limit = 10, $days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                url,
                COUNT(*) as crawl_count,
                SUM(revenue) as total_revenue,
                COUNT(DISTINCT bot_type) as unique_bots
            FROM $table_name
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY url
            ORDER BY crawl_count DESC
            LIMIT %d",
            $days,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get bot activity timeline
     */
    public function get_bot_timeline($hours = 24) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(detected_at, '%%Y-%%m-%%d %%H:00:00') as hour,
                COUNT(*) as crawl_count,
                COUNT(DISTINCT bot_type) as unique_bots,
                SUM(revenue) as revenue
            FROM $table_name
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
            GROUP BY hour
            ORDER BY hour DESC",
            $hours
        ));
        
        return $results;
    }
    
    /**
     * Get company revenue breakdown
     */
    public function get_company_revenue($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                company,
                COUNT(*) as crawl_count,
                SUM(revenue) as total_revenue,
                AVG(revenue) as avg_rate,
                COUNT(DISTINCT bot_type) as bot_variants
            FROM $table_name
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY company
            ORDER BY total_revenue DESC",
            $days
        ));
        
        return $results;
    }
    
    /**
     * Get revenue forecast
     */
    public function get_revenue_forecast($days_ahead = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        // Get historical data for trend analysis
        $historical = $wpdb->get_results(
            "SELECT 
                DATE(detected_at) as date,
                SUM(revenue) as daily_revenue
            FROM $table_name
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY DATE(detected_at)
            ORDER BY date"
        );
        
        if (count($historical) < 7) {
            return [];
        }
        
        // Simple moving average forecast
        $forecast = [];
        $window = min(7, count($historical));
        $recent_avg = 0;
        
        for ($i = count($historical) - $window; $i < count($historical); $i++) {
            $recent_avg += $historical[$i]->daily_revenue;
        }
        $recent_avg /= $window;
        
        // Generate forecast
        $current_date = new DateTime();
        for ($i = 0; $i < $days_ahead; $i++) {
            $current_date->add(new DateInterval('P1D'));
            $forecast[] = [
                'date' => $current_date->format('Y-m-d'),
                'forecast_revenue' => $recent_avg * (1 + (rand(-10, 10) / 100)), // Add some variance
                'confidence' => max(0.7, 1 - ($i * 0.01)) // Confidence decreases over time
            ];
        }
        
        return $forecast;
    }
    
    /**
     * Get bot detection patterns
     */
    public function get_detection_patterns($bot_name = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $where = '';
        if ($bot_name) {
            $where = $wpdb->prepare(" WHERE bot_type = %s", $bot_name);
        }
        
        // Get hourly patterns
        $hourly = $wpdb->get_results(
            "SELECT 
                HOUR(detected_at) as hour,
                COUNT(*) as crawl_count,
                AVG(revenue) as avg_revenue
            FROM $table_name
            $where
            GROUP BY HOUR(detected_at)
            ORDER BY hour"
        );
        
        // Get day of week patterns
        $daily = $wpdb->get_results(
            "SELECT 
                DAYNAME(detected_at) as day,
                DAYOFWEEK(detected_at) as day_num,
                COUNT(*) as crawl_count,
                AVG(revenue) as avg_revenue
            FROM $table_name
            $where
            GROUP BY DAYOFWEEK(detected_at)
            ORDER BY day_num"
        );
        
        return [
            'hourly' => $hourly,
            'daily' => $daily
        ];
    }
    
    /**
     * Get geographic distribution
     */
    public function get_geographic_distribution($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        // This would require IP geolocation
        // For now, return mock data structure
        return [
            'countries' => [
                ['code' => 'US', 'name' => 'United States', 'crawls' => 1250, 'revenue' => 125.00],
                ['code' => 'GB', 'name' => 'United Kingdom', 'crawls' => 450, 'revenue' => 45.00],
                ['code' => 'DE', 'name' => 'Germany', 'crawls' => 320, 'revenue' => 32.00],
                ['code' => 'JP', 'name' => 'Japan', 'crawls' => 280, 'revenue' => 28.00],
                ['code' => 'CA', 'name' => 'Canada', 'crawls' => 200, 'revenue' => 20.00],
            ]
        ];
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        // Detection rate (crawls per day)
        $detection_rate = $wpdb->get_var(
            "SELECT COUNT(*) / COUNT(DISTINCT DATE(detected_at))
            FROM $table_name
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // Revenue per crawl
        $revenue_per_crawl = $wpdb->get_var(
            "SELECT AVG(revenue)
            FROM $table_name
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // Bot diversity
        $bot_diversity = $wpdb->get_var(
            "SELECT COUNT(DISTINCT bot_type)
            FROM $table_name
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // Peak hour
        $peak_hour = $wpdb->get_row(
            "SELECT 
                HOUR(detected_at) as hour,
                COUNT(*) as count
            FROM $table_name
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY HOUR(detected_at)
            ORDER BY count DESC
            LIMIT 1"
        );
        
        return [
            'detection_rate' => round($detection_rate, 2),
            'revenue_per_crawl' => round($revenue_per_crawl, 4),
            'bot_diversity' => $bot_diversity,
            'peak_hour' => $peak_hour ? $peak_hour->hour : 0,
            'peak_hour_count' => $peak_hour ? $peak_hour->count : 0
        ];
    }
}
