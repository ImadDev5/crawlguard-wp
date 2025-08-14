<?php
/**
 * Database Handler
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class PayPerCrawl_DB {
    
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
     * Create database tables using dbDelta
     */
    public function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Main logs table
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            bot_company varchar(100) DEFAULT '',
            confidence_score int(3) DEFAULT 0,
            action_taken varchar(20) DEFAULT 'logged',
            url text DEFAULT '',
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY timestamp (timestamp),
            KEY bot_company (bot_company)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Meta table for future use
        $meta_table = $wpdb->prefix . 'paypercrawl_meta';
        
        $meta_sql = "CREATE TABLE $meta_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            meta_key varchar(255) NOT NULL,
            meta_value longtext DEFAULT '',
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY meta_key (meta_key)
        ) $charset_collate;";
        
        dbDelta($meta_sql);
    }
    
    /**
     * Log bot detection
     */
    public function log_detection($detection_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'ip_address' => sanitize_text_field($detection_data['ip']),
                'user_agent' => sanitize_text_field($detection_data['user_agent']),
                'bot_company' => sanitize_text_field($detection_data['bot_company']),
                'confidence_score' => intval($detection_data['confidence']),
                'action_taken' => sanitize_text_field($detection_data['action']),
                'url' => esc_url_raw($detection_data['url'])
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        return $result;
    }
    
    /**
     * Get recent detections
     */
    public function get_recent_detections($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d",
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get detection stats
     */
    public function get_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        // Today's stats
        $today = $wpdb->get_row("
            SELECT 
                COUNT(*) as count,
                COUNT(DISTINCT ip_address) as unique_ips,
                COUNT(DISTINCT bot_company) as unique_bots
            FROM $table_name 
            WHERE DATE(timestamp) = CURDATE()
        ");
        
        // Total stats
        $total = $wpdb->get_row("
            SELECT 
                COUNT(*) as count,
                COUNT(DISTINCT ip_address) as unique_ips,
                COUNT(DISTINCT bot_company) as unique_bots
            FROM $table_name
        ");
        
        // Top bot companies
        $top_bots = $wpdb->get_results("
            SELECT 
                bot_company,
                COUNT(*) as count
            FROM $table_name 
            WHERE bot_company != ''
            GROUP BY bot_company 
            ORDER BY count DESC 
            LIMIT 5
        ");
        
        return array(
            'today' => $today,
            'total' => $total,
            'top_bots' => $top_bots
        );
    }
    
    /**
     * Get chart data for analytics
     */
    public function get_chart_data($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(timestamp) as date,
                COUNT(*) as detections,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM $table_name 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC
        ", $days));
        
        $labels = array();
        $detections = array();
        $unique_ips = array();
        
        foreach ($results as $row) {
            $labels[] = date('M j', strtotime($row->date));
            $detections[] = intval($row->detections);
            $unique_ips[] = intval($row->unique_ips);
        }
        
        return array(
            'labels' => $labels,
            'detections' => $detections,
            'unique_ips' => $unique_ips
        );
    }
    
    /**
     * Get export data for CSV
     */
    public function get_export_data($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                timestamp,
                ip_address,
                user_agent,
                bot_company,
                confidence_score,
                action_taken,
                url
            FROM $table_name 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
            ORDER BY timestamp DESC
        ", $days));
        
        return $results;
    }
    
    /**
     * Clean old logs (optional cleanup)
     */
    public function cleanup_old_logs($days = 90) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'paypercrawl_logs';
        
        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM $table_name 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        return $deleted;
    }
}
