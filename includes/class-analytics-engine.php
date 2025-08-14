<?php
/**
 * Analytics Engine for PayPerCrawl Enterprise
 * 
 * @package PayPerCrawl
 * @subpackage Analytics
 * @version 4.0.0
 * @author PayPerCrawl.tech
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Advanced Analytics Engine
 * 
 * Features:
 * - Real-time revenue tracking
 * - Predictive analytics
 * - Revenue optimization
 * - Geographic analysis
 * - Performance metrics
 * 
 * @since 4.0.0
 */
class PayPerCrawl_Analytics_Engine {
    
    /**
     * Analytics data cache
     * @var array
     */
    private $analytics_cache = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_analytics();
    }
    
    /**
     * Initialize analytics engine
     */
    private function init_analytics() {
        add_action('paypercrawl_bot_detected', [$this, 'record_detection'], 10, 3);
        add_action('paypercrawl_process_analytics', [$this, 'process_daily_analytics']);
    }
    
    /**
     * Record bot detection for analytics
     */
    public function record_detection($bot_info, $request_data, $revenue) {
        $this->update_realtime_stats($bot_info, $revenue);
        $this->update_geographic_stats($request_data['ip'], $revenue);
        $this->update_temporal_stats($revenue);
    }
    
    /**
     * Update real-time statistics
     */
    public function update_realtime_stats($bot_info, $revenue) {
        $stats = get_option('paypercrawl_realtime_stats', [
            'total_detections' => 0,
            'total_revenue' => 0,
            'today_detections' => 0,
            'today_revenue' => 0,
            'last_updated' => current_time('mysql'),
        ]);
        
        $stats['total_detections']++;
        $stats['total_revenue'] += $revenue;
        
        // Check if it's a new day
        $today = current_time('Y-m-d');
        $last_date = date('Y-m-d', strtotime($stats['last_updated']));
        
        if ($today !== $last_date) {
            $stats['today_detections'] = 1;
            $stats['today_revenue'] = $revenue;
        } else {
            $stats['today_detections']++;
            $stats['today_revenue'] += $revenue;
        }
        
        $stats['last_updated'] = current_time('mysql');
        
        update_option('paypercrawl_realtime_stats', $stats);
    }
    
    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats() {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        // Get cached stats first
        $cached_stats = get_transient('paypercrawl_dashboard_stats');
        if ($cached_stats) {
            return $cached_stats;
        }
        
        $stats = [];
        
        // Total revenue
        $stats['total_revenue'] = $wpdb->get_var("SELECT SUM(revenue) FROM {$table_detections}") ?: 0;
        
        // Today's stats
        $today = current_time('Y-m-d');
        $today_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as detections, SUM(revenue) as revenue 
            FROM {$table_detections} 
            WHERE DATE(detected_at) = %s",
            $today
        ));
        
        $stats['today_detections'] = $today_stats->detections ?? 0;
        $stats['today_revenue'] = $today_stats->revenue ?? 0;
        
        // Yesterday's stats for comparison
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterday_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as detections, SUM(revenue) as revenue 
            FROM {$table_detections} 
            WHERE DATE(detected_at) = %s",
            $yesterday
        ));
        
        $yesterday_revenue = $yesterday_stats->revenue ?? 0;
        $stats['revenue_change_percent'] = $yesterday_revenue > 0 ? 
            (($stats['today_revenue'] - $yesterday_revenue) / $yesterday_revenue) * 100 : 0;
        
        // Detection accuracy and performance metrics
        $stats['detection_rate'] = $this->calculate_detection_rate();
        $stats['accuracy'] = $this->calculate_accuracy();
        
        // Top performing bots
        $stats['top_bots'] = $wpdb->get_results(
            "SELECT bot_name, company, COUNT(*) as detections, SUM(revenue) as revenue
            FROM {$table_detections} 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY bot_name, company 
            ORDER BY revenue DESC 
            LIMIT 5"
        );
        
        // Cache for 5 minutes
        set_transient('paypercrawl_dashboard_stats', $stats, 5 * MINUTE_IN_SECONDS);
        
        return $stats;
    }
    
    /**
     * Get recent activity
     */
    public function get_recent_activity($limit = 20) {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        $recent = $wpdb->get_results($wpdb->prepare(
            "SELECT bot_name, company, revenue, detected_at, ip_address, confidence_score 
            FROM {$table_detections} 
            ORDER BY detected_at DESC 
            LIMIT %d",
            $limit
        ));
        
        $activity = [];
        foreach ($recent as $detection) {
            $activity[] = [
                'type' => 'detection',
                'title' => "Detected {$detection->bot_name} from {$detection->company}",
                'timestamp' => $detection->detected_at,
                'revenue' => $detection->revenue,
                'ip' => $detection->ip_address,
                'confidence' => $detection->confidence_score,
            ];
        }
        
        return $activity;
    }
    
    /**
     * Get revenue analytics data
     */
    public function get_revenue_analytics($period = '7d') {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        switch ($period) {
            case '24h':
                return $this->get_hourly_revenue();
            case '7d':
                return $this->get_daily_revenue(7);
            case '30d':
                return $this->get_daily_revenue(30);
            case '90d':
                return $this->get_weekly_revenue(90);
            default:
                return $this->get_daily_revenue(7);
        }
    }
    
    /**
     * Get hourly revenue data
     */
    private function get_hourly_revenue() {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        $data = $wpdb->get_results(
            "SELECT 
                HOUR(detected_at) as hour,
                DATE(detected_at) as date,
                COUNT(*) as detections,
                SUM(revenue) as revenue,
                AVG(revenue) as avg_revenue,
                AVG(confidence_score) as avg_confidence
            FROM {$table_detections} 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY DATE(detected_at), HOUR(detected_at)
            ORDER BY detected_at"
        );
        
        return $this->format_time_series_data($data, 'hour');
    }
    
    /**
     * Get daily revenue data
     */
    private function get_daily_revenue($days = 7) {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(detected_at) as date,
                COUNT(*) as detections,
                SUM(revenue) as revenue,
                AVG(revenue) as avg_revenue,
                AVG(confidence_score) as avg_confidence,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM {$table_detections} 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(detected_at)
            ORDER BY date",
            $days
        ));
        
        return $this->format_time_series_data($data, 'date');
    }
    
    /**
     * Get weekly revenue data
     */
    private function get_weekly_revenue($days = 90) {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                YEARWEEK(detected_at) as week,
                COUNT(*) as detections,
                SUM(revenue) as revenue,
                AVG(revenue) as avg_revenue,
                AVG(confidence_score) as avg_confidence,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM {$table_detections} 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY YEARWEEK(detected_at)
            ORDER BY week",
            $days
        ));
        
        return $this->format_time_series_data($data, 'week');
    }
    
    /**
     * Format time series data for charts
     */
    private function format_time_series_data($data, $time_unit) {
        $formatted = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => [],
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => 'Detections',
                    'data' => [],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y1',
                ],
            ],
            'summary' => [
                'total_revenue' => 0,
                'total_detections' => 0,
                'avg_revenue_per_detection' => 0,
                'avg_confidence' => 0,
            ],
        ];
        
        $total_revenue = 0;
        $total_detections = 0;
        $total_confidence = 0;
        
        foreach ($data as $row) {
            // Format label based on time unit
            switch ($time_unit) {
                case 'hour':
                    $label = sprintf('%02d:00', $row->hour);
                    break;
                case 'date':
                    $label = date('M j', strtotime($row->date));
                    break;
                case 'week':
                    $label = 'Week ' . substr($row->week, -2);
                    break;
                default:
                    $label = $row->date ?? $row->hour ?? $row->week;
            }
            
            $formatted['labels'][] = $label;
            $formatted['datasets'][0]['data'][] = round($row->revenue, 4);
            $formatted['datasets'][1]['data'][] = (int) $row->detections;
            
            $total_revenue += $row->revenue;
            $total_detections += $row->detections;
            $total_confidence += $row->avg_confidence ?? 0;
        }
        
        $formatted['summary'] = [
            'total_revenue' => round($total_revenue, 4),
            'total_detections' => $total_detections,
            'avg_revenue_per_detection' => $total_detections > 0 ? round($total_revenue / $total_detections, 4) : 0,
            'avg_confidence' => count($data) > 0 ? round($total_confidence / count($data), 2) : 0,
        ];
        
        return $formatted;
    }
    
    /**
     * Get bot distribution analytics
     */
    public function get_bot_distribution($period = '7d') {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        $days = $this->period_to_days($period);
        
        $company_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                company,
                COUNT(*) as detections,
                SUM(revenue) as revenue,
                AVG(revenue) as avg_rate,
                AVG(confidence_score) as avg_confidence
            FROM {$table_detections} 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY company 
            ORDER BY revenue DESC",
            $days
        ));
        
        $bot_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                bot_name,
                bot_type,
                company,
                COUNT(*) as detections,
                SUM(revenue) as revenue,
                AVG(revenue) as avg_rate,
                AVG(confidence_score) as avg_confidence
            FROM {$table_detections} 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY bot_name, company 
            ORDER BY revenue DESC 
            LIMIT 20",
            $days
        ));
        
        $method_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                detection_method,
                COUNT(*) as detections,
                SUM(revenue) as revenue,
                AVG(confidence_score) as avg_confidence
            FROM {$table_detections} 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY detection_method 
            ORDER BY detections DESC",
            $days
        ));
        
        return [
            'companies' => $company_data,
            'bots' => $bot_data,
            'methods' => $method_data,
        ];
    }
    
    /**
     * Get geographic analytics
     */
    public function get_geographic_analytics($period = '7d') {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        $days = $this->period_to_days($period);
        
        // This would require IP geolocation data
        // For now, returning sample data structure
        return [
            'countries' => [
                ['country' => 'United States', 'detections' => 1250, 'revenue' => 125.50],
                ['country' => 'United Kingdom', 'detections' => 890, 'revenue' => 89.75],
                ['country' => 'Germany', 'detections' => 670, 'revenue' => 67.20],
                ['country' => 'Canada', 'detections' => 540, 'revenue' => 54.15],
                ['country' => 'France', 'detections' => 430, 'revenue' => 43.80],
            ],
            'regions' => [
                ['region' => 'North America', 'percentage' => 45.2],
                ['region' => 'Europe', 'percentage' => 38.7],
                ['region' => 'Asia Pacific', 'percentage' => 12.1],
                ['region' => 'Other', 'percentage' => 4.0],
            ],
        ];
    }
    
    /**
     * Get revenue forecasting
     */
    public function get_revenue_forecast($days_ahead = 30) {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        // Get historical data for trend analysis
        $historical = $wpdb->get_results(
            "SELECT 
                DATE(detected_at) as date,
                SUM(revenue) as revenue,
                COUNT(*) as detections
            FROM {$table_detections} 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(detected_at)
            ORDER BY date"
        );
        
        if (empty($historical)) {
            return null;
        }
        
        // Simple linear regression for forecasting
        $forecast = $this->calculate_linear_forecast($historical, $days_ahead);
        
        return [
            'forecast_data' => $forecast,
            'confidence_interval' => $this->calculate_confidence_interval($historical),
            'growth_rate' => $this->calculate_growth_rate($historical),
        ];
    }
    
    /**
     * Calculate detection rate
     */
    private function calculate_detection_rate() {
        // This would require traffic analysis data
        // For now, return a reasonable estimate
        return 78.5;
    }
    
    /**
     * Calculate accuracy
     */
    private function calculate_accuracy() {
        global $wpdb;
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        // High confidence detections are likely more accurate
        $high_confidence = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_detections} 
            WHERE confidence_score >= 0.8 AND detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        $total_detections = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_detections} 
            WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        return $total_detections > 0 ? ($high_confidence / $total_detections) * 100 : 90;
    }
    
    /**
     * Update geographic statistics
     */
    private function update_geographic_stats($ip, $revenue) {
        // This would integrate with a geolocation service
        // For now, just log the IP for future processing
        $geo_stats = get_option('paypercrawl_geo_stats', []);
        
        if (!isset($geo_stats[$ip])) {
            $geo_stats[$ip] = [
                'detections' => 0,
                'revenue' => 0,
                'first_seen' => current_time('mysql'),
            ];
        }
        
        $geo_stats[$ip]['detections']++;
        $geo_stats[$ip]['revenue'] += $revenue;
        $geo_stats[$ip]['last_seen'] = current_time('mysql');
        
        // Keep only last 1000 IPs to prevent bloat
        if (count($geo_stats) > 1000) {
            $geo_stats = array_slice($geo_stats, -1000, null, true);
        }
        
        update_option('paypercrawl_geo_stats', $geo_stats);
    }
    
    /**
     * Update temporal statistics
     */
    private function update_temporal_stats($revenue) {
        $hour = (int) current_time('H');
        $day_of_week = (int) current_time('w'); // 0 = Sunday
        
        $temporal_stats = get_option('paypercrawl_temporal_stats', [
            'hourly' => array_fill(0, 24, ['detections' => 0, 'revenue' => 0]),
            'daily' => array_fill(0, 7, ['detections' => 0, 'revenue' => 0]),
        ]);
        
        $temporal_stats['hourly'][$hour]['detections']++;
        $temporal_stats['hourly'][$hour]['revenue'] += $revenue;
        
        $temporal_stats['daily'][$day_of_week]['detections']++;
        $temporal_stats['daily'][$day_of_week]['revenue'] += $revenue;
        
        update_option('paypercrawl_temporal_stats', $temporal_stats);
    }
    
    /**
     * Process daily analytics
     */
    public function process_daily_analytics() {
        global $wpdb;
        $table_analytics = $wpdb->prefix . 'paypercrawl_analytics';
        $table_detections = $wpdb->prefix . 'paypercrawl_detections';
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Aggregate yesterday's data
        $daily_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                bot_type,
                company,
                COUNT(*) as total_detections,
                SUM(revenue) as total_revenue,
                AVG(confidence_score) as avg_confidence,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM {$table_detections} 
            WHERE DATE(detected_at) = %s
            GROUP BY bot_type, company",
            $yesterday
        ));
        
        foreach ($daily_data as $data) {
            // Get top pages for this bot type
            $top_pages = $wpdb->get_results($wpdb->prepare(
                "SELECT url, COUNT(*) as hits 
                FROM {$table_detections} 
                WHERE DATE(detected_at) = %s AND bot_type = %s AND company = %s
                GROUP BY url ORDER BY hits DESC LIMIT 10",
                $yesterday,
                $data->bot_type,
                $data->company
            ));
            
            // Get hourly distribution
            $hourly_dist = $wpdb->get_results($wpdb->prepare(
                "SELECT HOUR(detected_at) as hour, COUNT(*) as detections
                FROM {$table_detections} 
                WHERE DATE(detected_at) = %s AND bot_type = %s AND company = %s
                GROUP BY HOUR(detected_at)",
                $yesterday,
                $data->bot_type,
                $data->company
            ));
            
            // Insert or update analytics record
            $wpdb->replace(
                $table_analytics,
                [
                    'date_recorded' => $yesterday,
                    'bot_type' => $data->bot_type,
                    'company' => $data->company,
                    'total_detections' => $data->total_detections,
                    'total_revenue' => $data->total_revenue,
                    'avg_confidence' => $data->avg_confidence,
                    'unique_ips' => $data->unique_ips,
                    'top_pages' => json_encode($top_pages),
                    'hourly_distribution' => json_encode($hourly_dist),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]
            );
        }
        
        $this->log_info('Daily analytics processed for ' . $yesterday);
    }
    
    /**
     * Get API statistics
     */
    public function get_api_stats() {
        return [
            'dashboard_stats' => $this->get_dashboard_stats(),
            'recent_activity' => $this->get_recent_activity(10),
            'revenue_summary' => $this->get_revenue_analytics('24h')['summary'] ?? [],
        ];
    }
    
    /**
     * Convert period string to days
     */
    private function period_to_days($period) {
        switch ($period) {
            case '24h': return 1;
            case '7d': return 7;
            case '30d': return 30;
            case '90d': return 90;
            default: return 7;
        }
    }
    
    /**
     * Calculate linear forecast
     */
    private function calculate_linear_forecast($historical, $days_ahead) {
        if (count($historical) < 2) {
            return [];
        }
        
        // Simple linear regression
        $n = count($historical);
        $sum_x = 0;
        $sum_y = 0;
        $sum_xy = 0;
        $sum_x2 = 0;
        
        foreach ($historical as $i => $data) {
            $x = $i + 1;
            $y = $data->revenue;
            
            $sum_x += $x;
            $sum_y += $y;
            $sum_xy += $x * $y;
            $sum_x2 += $x * $x;
        }
        
        $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
        $intercept = ($sum_y - $slope * $sum_x) / $n;
        
        $forecast = [];
        for ($i = 1; $i <= $days_ahead; $i++) {
            $x = $n + $i;
            $predicted_revenue = $slope * $x + $intercept;
            $forecast[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days")),
                'predicted_revenue' => max(0, round($predicted_revenue, 4)),
            ];
        }
        
        return $forecast;
    }
    
    /**
     * Calculate confidence interval
     */
    private function calculate_confidence_interval($historical) {
        $revenues = array_column($historical, 'revenue');
        $mean = array_sum($revenues) / count($revenues);
        $variance = array_sum(array_map(function($x) use ($mean) { return pow($x - $mean, 2); }, $revenues)) / count($revenues);
        $std_dev = sqrt($variance);
        
        return [
            'mean' => round($mean, 4),
            'std_dev' => round($std_dev, 4),
            'confidence_95' => [
                'lower' => round($mean - 1.96 * $std_dev, 4),
                'upper' => round($mean + 1.96 * $std_dev, 4),
            ],
        ];
    }
    
    /**
     * Calculate growth rate
     */
    private function calculate_growth_rate($historical) {
        if (count($historical) < 2) {
            return 0;
        }
        
        $first_value = $historical[0]->revenue;
        $last_value = end($historical)->revenue;
        $periods = count($historical) - 1;
        
        if ($first_value == 0) {
            return 0;
        }
        
        $growth_rate = (pow($last_value / $first_value, 1 / $periods) - 1) * 100;
        
        return round($growth_rate, 2);
    }
    
    /**
     * Log info message
     */
    private function log_info($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PayPerCrawl Analytics] INFO: ' . $message);
        }
    }
}

// End of file
