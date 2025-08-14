<?php
/**
 * Analytics Engine - Rebuilt for Maximum Stability
 * 
 * @package PayPerCrawl
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * PayPerCrawl Analytics Class
 */
class PayPerCrawl_Analytics {
    
    /**
     * Cache duration in seconds
     */
    const CACHE_DURATION = 300; // 5 minutes
    
    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'paypercrawl_analytics_';
    
    /**
     * Initialization flag
     */
    private $initialized = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize analytics
     */
    private function init() {
        if ($this->initialized) {
            return;
        }
        
        try {
            $this->initialized = true;
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Initialization failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Get comprehensive analytics dashboard data
     */
    public function get_dashboard_data() {
        if (!$this->initialized) {
            $this->init();
        }
        
        $cache_key = self::CACHE_PREFIX . 'dashboard_data';
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        try {
            $data = array(
                'summary' => $this->get_summary_stats(),
                'daily_revenue' => $this->get_daily_revenue(30),
                'top_bots' => $this->get_top_bots(10),
                'top_companies' => $this->get_top_companies(10),
                'recent_detections' => $this->get_recent_detections(20),
                'bot_trends' => $this->get_bot_trends(7),
                'hourly_activity' => $this->get_hourly_activity(),
                'revenue_breakdown' => $this->get_revenue_breakdown()
            );
            
            set_transient($cache_key, $data, self::CACHE_DURATION);
            
            return $data;
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Dashboard data error - ' . $e->getMessage());
            return $this->get_fallback_data();
        }
    }
    
    /**
     * Get summary statistics
     */
    public function get_summary_stats() {
        global $wpdb;
        
        if (!$wpdb) {
            return $this->get_fallback_summary();
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            return $this->get_fallback_summary();
        }
        
        try {
            // Total revenue
            $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM {$table_name}");
            $total_revenue = $total_revenue ? floatval($total_revenue) : 0.0;
            
            // Total detections
            $total_detections = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            $total_detections = $total_detections ? intval($total_detections) : 0;
            
            // Today's revenue
            $today_revenue = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(revenue) FROM {$table_name} WHERE DATE(detected_at) = %s",
                current_time('Y-m-d')
            ));
            $today_revenue = $today_revenue ? floatval($today_revenue) : 0.0;
            
            // Today's detections
            $today_detections = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE DATE(detected_at) = %s",
                current_time('Y-m-d')
            ));
            $today_detections = $today_detections ? intval($today_detections) : 0;
            
            // This month's revenue
            $month_revenue = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(revenue) FROM {$table_name} WHERE YEAR(detected_at) = %d AND MONTH(detected_at) = %d",
                current_time('Y'),
                current_time('n')
            ));
            $month_revenue = $month_revenue ? floatval($month_revenue) : 0.0;
            
            // Unique bots count
            $unique_bots = $wpdb->get_var("SELECT COUNT(DISTINCT bot_type) FROM {$table_name}");
            $unique_bots = $unique_bots ? intval($unique_bots) : 0;
            
            // Average revenue per detection
            $avg_revenue = $total_detections > 0 ? $total_revenue / $total_detections : 0.0;
            
            return array(
                'total_revenue' => $total_revenue,
                'total_detections' => $total_detections,
                'today_revenue' => $today_revenue,
                'today_detections' => $today_detections,
                'month_revenue' => $month_revenue,
                'unique_bots' => $unique_bots,
                'avg_revenue' => $avg_revenue
            );
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Summary stats error - ' . $e->getMessage());
            return $this->get_fallback_summary();
        }
    }
    
    /**
     * Get daily revenue for specified days
     */
    public function get_daily_revenue($days = 30) {
        global $wpdb;
        
        if (!$wpdb) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        try {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(detected_at) as date, SUM(revenue) as daily_revenue, COUNT(*) as detections 
                FROM {$table_name} 
                WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY) 
                GROUP BY DATE(detected_at) 
                ORDER BY date ASC",
                $days
            ));
            
            $daily_data = array();
            
            if (!empty($results)) {
                foreach ($results as $row) {
                    $daily_data[] = array(
                        'date' => $row->date,
                        'revenue' => floatval($row->daily_revenue),
                        'detections' => intval($row->detections)
                    );
                }
            }
            
            return $daily_data;
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Daily revenue error - ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get top bots by detection count
     */
    public function get_top_bots($limit = 10) {
        global $wpdb;
        
        if (!$wpdb) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        try {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT bot_type, COUNT(*) as detections, SUM(revenue) as total_revenue 
                FROM {$table_name} 
                GROUP BY bot_type 
                ORDER BY detections DESC 
                LIMIT %d",
                $limit
            ));
            
            $top_bots = array();
            
            if (!empty($results)) {
                foreach ($results as $row) {
                    $top_bots[] = array(
                        'bot_type' => $row->bot_type,
                        'detections' => intval($row->detections),
                        'revenue' => floatval($row->total_revenue)
                    );
                }
            }
            
            return $top_bots;
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Top bots error - ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get top companies by revenue
     */
    public function get_top_companies($limit = 10) {
        global $wpdb;
        
        if (!$wpdb) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        try {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT company, COUNT(*) as detections, SUM(revenue) as total_revenue 
                FROM {$table_name} 
                GROUP BY company 
                ORDER BY total_revenue DESC 
                LIMIT %d",
                $limit
            ));
            
            $top_companies = array();
            
            if (!empty($results)) {
                foreach ($results as $row) {
                    $top_companies[] = array(
                        'company' => $row->company,
                        'detections' => intval($row->detections),
                        'revenue' => floatval($row->total_revenue)
                    );
                }
            }
            
            return $top_companies;
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Top companies error - ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get recent detections
     */
    public function get_recent_detections($limit = 20) {
        global $wpdb;
        
        if (!$wpdb) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        try {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT bot_type, company, revenue, url, ip_address, detected_at 
                FROM {$table_name} 
                ORDER BY detected_at DESC 
                LIMIT %d",
                $limit
            ));
            
            $recent = array();
            
            if (!empty($results)) {
                foreach ($results as $row) {
                    $recent[] = array(
                        'bot_type' => $row->bot_type,
                        'company' => $row->company,
                        'revenue' => floatval($row->revenue),
                        'url' => $row->url,
                        'ip_address' => $row->ip_address,
                        'detected_at' => $row->detected_at
                    );
                }
            }
            
            return $recent;
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Recent detections error - ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get bot trends for specified days
     */
    public function get_bot_trends($days = 7) {
        global $wpdb;
        
        if (!$wpdb) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        try {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(detected_at) as date, bot_type, COUNT(*) as detections 
                FROM {$table_name} 
                WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY) 
                GROUP BY DATE(detected_at), bot_type 
                ORDER BY date ASC, detections DESC",
                $days
            ));
            
            $trends = array();
            
            if (!empty($results)) {
                foreach ($results as $row) {
                    $date = $row->date;
                    
                    if (!isset($trends[$date])) {
                        $trends[$date] = array();
                    }
                    
                    $trends[$date][] = array(
                        'bot_type' => $row->bot_type,
                        'detections' => intval($row->detections)
                    );
                }
            }
            
            return $trends;
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Bot trends error - ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get hourly activity pattern
     */
    public function get_hourly_activity() {
        global $wpdb;
        
        if (!$wpdb) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        try {
            $results = $wpdb->get_results(
                "SELECT HOUR(detected_at) as hour, COUNT(*) as detections, SUM(revenue) as hourly_revenue 
                FROM {$table_name} 
                WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                GROUP BY HOUR(detected_at) 
                ORDER BY hour ASC"
            );
            
            $hourly = array();
            
            // Initialize all hours
            for ($i = 0; $i < 24; $i++) {
                $hourly[$i] = array(
                    'hour' => $i,
                    'detections' => 0,
                    'revenue' => 0.0
                );
            }
            
            if (!empty($results)) {
                foreach ($results as $row) {
                    $hour = intval($row->hour);
                    $hourly[$hour] = array(
                        'hour' => $hour,
                        'detections' => intval($row->detections),
                        'revenue' => floatval($row->hourly_revenue)
                    );
                }
            }
            
            return array_values($hourly);
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Hourly activity error - ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get revenue breakdown by bot type
     */
    public function get_revenue_breakdown() {
        global $wpdb;
        
        if (!$wpdb) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        try {
            $results = $wpdb->get_results(
                "SELECT 
                    CASE 
                        WHEN revenue >= 0.10 THEN 'Premium'
                        WHEN revenue >= 0.05 THEN 'Standard'
                        ELSE 'Basic'
                    END as category,
                    COUNT(*) as detections,
                    SUM(revenue) as total_revenue
                FROM {$table_name} 
                GROUP BY category 
                ORDER BY total_revenue DESC"
            );
            
            $breakdown = array();
            
            if (!empty($results)) {
                foreach ($results as $row) {
                    $breakdown[] = array(
                        'category' => $row->category,
                        'detections' => intval($row->detections),
                        'revenue' => floatval($row->total_revenue)
                    );
                }
            }
            
            return $breakdown;
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Revenue breakdown error - ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get analytics for specific date range
     */
    public function get_range_analytics($start_date, $end_date) {
        global $wpdb;
        
        if (!$wpdb) {
            return $this->get_fallback_range_data();
        }
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        if (!$this->table_exists($table_name)) {
            return $this->get_fallback_range_data();
        }
        
        try {
            // Validate dates
            if (!$this->is_valid_date($start_date) || !$this->is_valid_date($end_date)) {
                return $this->get_fallback_range_data();
            }
            
            $total_revenue = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(revenue) FROM {$table_name} WHERE DATE(detected_at) BETWEEN %s AND %s",
                $start_date,
                $end_date
            ));
            
            $total_detections = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE DATE(detected_at) BETWEEN %s AND %s",
                $start_date,
                $end_date
            ));
            
            return array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'total_revenue' => $total_revenue ? floatval($total_revenue) : 0.0,
                'total_detections' => $total_detections ? intval($total_detections) : 0
            );
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Range analytics error - ' . $e->getMessage());
            return $this->get_fallback_range_data();
        }
    }
    
    /**
     * Clear analytics cache
     */
    public function clear_cache() {
        try {
            global $wpdb;
            
            if ($wpdb) {
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_" . self::CACHE_PREFIX . "%'");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_" . self::CACHE_PREFIX . "%'");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('PayPerCrawl Analytics: Cache clear error - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if table exists
     */
    private function table_exists($table_name) {
        global $wpdb;
        
        if (!$wpdb) {
            return false;
        }
        
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
    
    /**
     * Validate date format
     */
    private function is_valid_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Get fallback summary data
     */
    private function get_fallback_summary() {
        return array(
            'total_revenue' => 0.0,
            'total_detections' => 0,
            'today_revenue' => 0.0,
            'today_detections' => 0,
            'month_revenue' => 0.0,
            'unique_bots' => 0,
            'avg_revenue' => 0.0
        );
    }
    
    /**
     * Get fallback dashboard data
     */
    private function get_fallback_data() {
        return array(
            'summary' => $this->get_fallback_summary(),
            'daily_revenue' => array(),
            'top_bots' => array(),
            'top_companies' => array(),
            'recent_detections' => array(),
            'bot_trends' => array(),
            'hourly_activity' => array(),
            'revenue_breakdown' => array()
        );
    }
    
    /**
     * Get fallback range data
     */
    private function get_fallback_range_data() {
        return array(
            'start_date' => '',
            'end_date' => '',
            'total_revenue' => 0.0,
            'total_detections' => 0
        );
    }
}
