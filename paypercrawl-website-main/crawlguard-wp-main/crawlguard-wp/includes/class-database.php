<?php
/**
 * Database Connection and Management
 * 
 * Handles connection to PayPerCrawl production database
 */

if (!defined('ABSPATH')) {
    exit;
}

class CrawlGuard_Database {
    
    private $connection;
    private $db_config;
    
    public function __construct() {
        $this->db_config = array(
            'host' => 'ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech',
            'database' => 'neondb',
            'username' => 'neondb_owner',
            'password' => 'npg_nf1TKzFajLV2',
            'port' => 5432,
            'sslmode' => 'require'
        );
    }
    
    /**
     * Get database connection
     */
    public function get_connection() {
        if ($this->connection) {
            return $this->connection;
        }
        
        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s",
                $this->db_config['host'],
                $this->db_config['port'],
                $this->db_config['database'],
                $this->db_config['sslmode']
            );
            
            $this->connection = new PDO(
                $dsn,
                $this->db_config['username'],
                $this->db_config['password'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 10
                )
            );
            
            return $this->connection;
        } catch (PDOException $e) {
            error_log('PayPerCrawl Database Connection Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Register site in database
     */
    public function register_site($site_data) {
        $conn = $this->get_connection();
        if (!$conn) return false;
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO sites (site_url, site_name, admin_email, api_key, plugin_version, wordpress_version)
                VALUES (:site_url, :site_name, :admin_email, :api_key, :plugin_version, :wordpress_version)
                ON CONFLICT (site_url) 
                DO UPDATE SET 
                    site_name = EXCLUDED.site_name,
                    admin_email = EXCLUDED.admin_email,
                    plugin_version = EXCLUDED.plugin_version,
                    wordpress_version = EXCLUDED.wordpress_version,
                    updated_at = CURRENT_TIMESTAMP
                RETURNING id
            ");
            
            $stmt->execute(array(
                ':site_url' => $site_data['site_url'],
                ':site_name' => $site_data['site_name'],
                ':admin_email' => $site_data['admin_email'],
                ':api_key' => $site_data['api_key'],
                ':plugin_version' => $site_data['plugin_version'],
                ':wordpress_version' => $site_data['wordpress_version']
            ));
            
            $result = $stmt->fetch();
            return $result ? $result['id'] : false;
        } catch (PDOException $e) {
            error_log('PayPerCrawl Site Registration Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log bot detection
     */
    public function log_bot_detection($detection_data) {
        $conn = $this->get_connection();
        if (!$conn) return false;
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO bot_requests (
                    site_id, ip_address, user_agent, bot_detected, bot_type, bot_name,
                    confidence_score, page_url, action_taken, revenue_amount
                ) VALUES (
                    :site_id, :ip_address, :user_agent, :bot_detected, :bot_type, :bot_name,
                    :confidence_score, :page_url, :action_taken, :revenue_amount
                )
            ");
            
            return $stmt->execute($detection_data);
        } catch (PDOException $e) {
            error_log('PayPerCrawl Bot Detection Log Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get site configuration
     */
    public function get_site_config($site_url) {
        $conn = $this->get_connection();
        if (!$conn) return false;
        
        try {
            $stmt = $conn->prepare("
                SELECT * FROM site_complete_config 
                WHERE site_url = :site_url AND active = true
            ");
            
            $stmt->execute(array(':site_url' => $site_url));
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('PayPerCrawl Site Config Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update site analytics
     */
    public function update_analytics($site_id) {
        $conn = $this->get_connection();
        if (!$conn) return false;
        
        try {
            $stmt = $conn->prepare("SELECT update_daily_analytics(:site_id, CURRENT_DATE)");
            $stmt->execute(array(':site_id' => $site_id));
            return true;
        } catch (PDOException $e) {
            error_log('PayPerCrawl Analytics Update Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate API key
     */
    public function generate_api_key() {
        $conn = $this->get_connection();
        if (!$conn) return false;
        
        try {
            $stmt = $conn->prepare("SELECT generate_api_key() as api_key");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ? $result['api_key'] : false;
        } catch (PDOException $e) {
            error_log('PayPerCrawl API Key Generation Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate authentication token
     */
    public function validate_auth_token($token) {
        $conn = $this->get_connection();
        if (!$conn) return false;
        
        try {
            $stmt = $conn->prepare("SELECT * FROM validate_auth_token(:token)");
            $stmt->execute(array(':token' => $token));
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('PayPerCrawl Token Validation Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get revenue summary
     */
    public function get_revenue_summary($site_url) {
        $conn = $this->get_connection();
        if (!$conn) return false;
        
        try {
            $stmt = $conn->prepare("
                SELECT * FROM site_revenue_summary 
                WHERE site_url = :site_url
            ");
            
            $stmt->execute(array(':site_url' => $site_url));
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('PayPerCrawl Revenue Summary Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Test database connection
     */
    public function test_connection() {
        $conn = $this->get_connection();
        if (!$conn) return false;
        
        try {
            $stmt = $conn->prepare("SELECT 1 as test");
            $stmt->execute();
            return $stmt->fetch() ? true : false;
        } catch (PDOException $e) {
            error_log('PayPerCrawl Database Test Error: ' . $e->getMessage());
            return false;
        }
    }
}
