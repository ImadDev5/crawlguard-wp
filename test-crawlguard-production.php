<?php
/**
 * CrawlGuard Production Plugin Test Suite
 * 
 * This script tests all major functionality of the CrawlGuard plugin including:
 * - Plugin activation/deactivation hooks
 * - Bot detection with various user agents
 * - Cloudflare integration
 * - Dashboard functionality
 * - Database operations and caching
 * 
 * @package CrawlGuard_Test
 * @version 1.0.0
 */

// Load WordPress environment
require_once '../../../wp-load.php';

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

class CrawlGuard_Production_Tester {
    
    private $test_results = [];
    private $plugin_file = 'crawlguard-production/crawlguard-production.php';
    private $test_user_agents = [
        'GPTBot/1.0' => 'OpenAI GPTBot',
        'ChatGPT-User/1.0' => 'ChatGPT Browser',
        'Claude-Web/1.0' => 'Anthropic Claude',
        'Bard/1.0' => 'Google Bard',
        'Mozilla/5.0 (compatible; GPTBot/1.0; +https://openai.com/gptbot)' => 'GPTBot Full',
        'Mozilla/5.0 (compatible; Claude-Web/1.0)' => 'Claude Full',
        'Mozilla/5.0 (compatible; Bard/1.0; +https://bard.google.com)' => 'Bard Full',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' => 'Regular Browser',
        'Anthropic-AI/1.0' => 'Anthropic AI Bot',
        'Perplexitybot/1.0' => 'Perplexity Bot',
        'CCBot/2.0' => 'Common Crawl Bot',
        'facebookexternalhit/1.1' => 'Facebook Bot',
        'Mozilla/5.0 (compatible; Bytespider)' => 'ByteDance Spider'
    ];
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "<h1>CrawlGuard Production Plugin Test Suite</h1>";
        echo "<p>Testing Date: " . date('Y-m-d H:i:s') . "</p><hr>";
        
        // Test 1: Check if plugin exists
        $this->test_plugin_exists();
        
        // Test 2: Test activation/deactivation hooks
        $this->test_activation_deactivation();
        
        // Test 3: Test bot detection with various user agents
        $this->test_bot_detection();
        
        // Test 4: Test Cloudflare integration
        $this->test_cloudflare_integration();
        
        // Test 5: Test database operations
        $this->test_database_operations();
        
        // Test 6: Test caching functionality
        $this->test_caching();
        
        // Test 7: Test admin dashboard
        $this->test_admin_dashboard();
        
        // Test 8: Test API endpoints
        $this->test_api_endpoints();
        
        // Test 9: Test security features
        $this->test_security_features();
        
        // Test 10: Check for PHP errors/warnings
        $this->test_php_compatibility();
        
        // Display results
        $this->display_results();
    }
    
    /**
     * Test 1: Check if plugin exists and is loadable
     */
    private function test_plugin_exists() {
        $test_name = "Plugin Existence Check";
        
        $plugin_path = WP_PLUGIN_DIR . '/' . $this->plugin_file;
        
        if (file_exists($plugin_path)) {
            // Check if main class exists
            if (class_exists('CrawlGuard_Production')) {
                $this->add_result($test_name, 'PASS', 'Plugin file exists and main class is loaded');
            } else {
                $this->add_result($test_name, 'FAIL', 'Plugin file exists but main class not found');
            }
        } else {
            $this->add_result($test_name, 'FAIL', 'Plugin file not found at: ' . $plugin_path);
        }
    }
    
    /**
     * Test 2: Test activation and deactivation hooks
     */
    private function test_activation_deactivation() {
        $test_name = "Activation/Deactivation Hooks";
        
        try {
            // Check if plugin is active
            if (is_plugin_active($this->plugin_file)) {
                // Test deactivation
                deactivate_plugins($this->plugin_file);
                
                // Check if deactivated
                if (!is_plugin_active($this->plugin_file)) {
                    // Reactivate
                    $result = activate_plugin($this->plugin_file);
                    
                    if (is_wp_error($result)) {
                        $this->add_result($test_name, 'FAIL', 'Activation error: ' . $result->get_error_message());
                    } elseif (is_plugin_active($this->plugin_file)) {
                        // Check if database tables were created
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'crawlguard_detections';
                        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                        
                        if ($table_exists) {
                            $this->add_result($test_name, 'PASS', 'Activation/Deactivation hooks working correctly');
                        } else {
                            $this->add_result($test_name, 'WARNING', 'Plugin activated but database tables not created');
                        }
                    }
                }
            } else {
                // Activate plugin first
                $result = activate_plugin($this->plugin_file);
                if (is_wp_error($result)) {
                    $this->add_result($test_name, 'FAIL', 'Cannot activate plugin: ' . $result->get_error_message());
                } else {
                    $this->add_result($test_name, 'PASS', 'Plugin activated successfully');
                }
            }
        } catch (Exception $e) {
            $this->add_result($test_name, 'FAIL', 'Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test 3: Test bot detection with various user agents
     */
    private function test_bot_detection() {
        $test_name = "Bot Detection";
        
        if (!class_exists('CrawlGuard_Bot_Detector_Advanced')) {
            $this->add_result($test_name, 'SKIP', 'Bot detector class not loaded');
            return;
        }
        
        try {
            $detector = new CrawlGuard_Bot_Detector_Advanced();
            $passed_tests = 0;
            $total_tests = 0;
            $details = [];
            
            foreach ($this->test_user_agents as $ua => $description) {
                $total_tests++;
                
                // Simulate request with user agent
                $request_data = [
                    'user_agent' => $ua,
                    'ip_address' => '127.0.0.1',
                    'request_uri' => '/test',
                    'request_method' => 'GET',
                    'referer' => '',
                    'accept' => 'text/html',
                    'accept_language' => 'en-US',
                    'accept_encoding' => 'gzip, deflate',
                    'cookies' => false,
                    'timestamp' => time(),
                    'session_id' => 'test_session'
                ];
                
                $result = $detector->detect_bot($request_data);
                
                // Check if AI bots are properly detected
                $should_be_ai_bot = in_array($description, [
                    'OpenAI GPTBot', 'ChatGPT Browser', 'Anthropic Claude', 
                    'Google Bard', 'GPTBot Full', 'Claude Full', 'Bard Full',
                    'Anthropic AI Bot', 'Perplexity Bot'
                ]);
                
                if ($should_be_ai_bot && $result['is_ai_bot']) {
                    $passed_tests++;
                    $details[] = "✓ $description: Correctly detected as AI bot (confidence: {$result['confidence']}%)";
                } elseif (!$should_be_ai_bot && !$result['is_ai_bot']) {
                    $passed_tests++;
                    $details[] = "✓ $description: Correctly identified as non-AI bot";
                } else {
                    $details[] = "✗ $description: Detection mismatch (detected as " . 
                               ($result['is_ai_bot'] ? 'AI bot' : 'non-AI') . ")";
                }
            }
            
            $success_rate = ($passed_tests / $total_tests) * 100;
            
            if ($success_rate >= 80) {
                $this->add_result($test_name, 'PASS', 
                    "Bot detection working ({$passed_tests}/{$total_tests} tests passed - {$success_rate}%)",
                    $details);
            } elseif ($success_rate >= 60) {
                $this->add_result($test_name, 'WARNING', 
                    "Bot detection partially working ({$passed_tests}/{$total_tests} tests passed - {$success_rate}%)",
                    $details);
            } else {
                $this->add_result($test_name, 'FAIL', 
                    "Bot detection issues ({$passed_tests}/{$total_tests} tests passed - {$success_rate}%)",
                    $details);
            }
            
        } catch (Exception $e) {
            $this->add_result($test_name, 'FAIL', 'Exception during bot detection: ' . $e->getMessage());
        }
    }
    
    /**
     * Test 4: Test Cloudflare integration
     */
    private function test_cloudflare_integration() {
        $test_name = "Cloudflare Integration";
        
        if (!class_exists('CrawlGuard_Cloudflare_Worker')) {
            $this->add_result($test_name, 'SKIP', 'Cloudflare worker class not loaded');
            return;
        }
        
        try {
            $cf_worker = new CrawlGuard_Cloudflare_Worker();
            
            // Check if Cloudflare headers are present
            $cf_headers = [
                'HTTP_CF_RAY' => $_SERVER['HTTP_CF_RAY'] ?? null,
                'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
                'HTTP_CF_IPCOUNTRY' => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null,
            ];
            
            $has_cf_headers = array_filter($cf_headers);
            
            if (!empty($has_cf_headers)) {
                // Test bot score retrieval
                if (method_exists($cf_worker, 'get_bot_score')) {
                    $bot_score = $cf_worker->get_bot_score();
                    if ($bot_score !== null) {
                        $this->add_result($test_name, 'PASS', 
                            "Cloudflare integration active (Bot Score: $bot_score)");
                    } else {
                        $this->add_result($test_name, 'WARNING', 
                            'Cloudflare detected but bot score unavailable');
                    }
                } else {
                    $this->add_result($test_name, 'WARNING', 
                        'Cloudflare worker class exists but get_bot_score method not found');
                }
            } else {
                $this->add_result($test_name, 'INFO', 
                    'Cloudflare not detected on this server (normal for non-CF environments)');
            }
            
        } catch (Exception $e) {
            $this->add_result($test_name, 'FAIL', 'Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test 5: Test database operations
     */
    private function test_database_operations() {
        $test_name = "Database Operations";
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'crawlguard_detections';
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if (!$table_exists) {
                $this->add_result($test_name, 'FAIL', 'Database table does not exist');
                return;
            }
            
            // Test INSERT operation
            $test_data = [
                'user_agent' => 'TestBot/1.0',
                'ip_address' => '127.0.0.1',
                'bot_type' => 'test',
                'confidence' => 100,
                'action_taken' => 'logged',
                'created_at' => current_time('mysql')
            ];
            
            $insert_result = $wpdb->insert($table_name, $test_data);
            
            if ($insert_result === false) {
                $this->add_result($test_name, 'FAIL', 'Failed to insert test data: ' . $wpdb->last_error);
                return;
            }
            
            $insert_id = $wpdb->insert_id;
            
            // Test SELECT operation
            $select_result = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $insert_id)
            );
            
            if (!$select_result) {
                $this->add_result($test_name, 'FAIL', 'Failed to retrieve test data');
                return;
            }
            
            // Test DELETE operation
            $delete_result = $wpdb->delete($table_name, ['id' => $insert_id]);
            
            if ($delete_result === false) {
                $this->add_result($test_name, 'WARNING', 'Failed to delete test data');
            } else {
                $this->add_result($test_name, 'PASS', 'Database operations working correctly');
            }
            
        } catch (Exception $e) {
            $this->add_result($test_name, 'FAIL', 'Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test 6: Test caching functionality
     */
    private function test_caching() {
        $test_name = "Caching System";
        
        if (!class_exists('CrawlGuard_Cache_Manager')) {
            $this->add_result($test_name, 'SKIP', 'Cache manager class not loaded');
            return;
        }
        
        try {
            $cache = new CrawlGuard_Cache_Manager();
            
            // Test set operation
            $test_key = 'crawlguard_test_' . time();
            $test_value = ['test' => 'data', 'timestamp' => time()];
            
            $set_result = $cache->set($test_key, $test_value, 60);
            
            if (!$set_result) {
                $this->add_result($test_name, 'FAIL', 'Failed to set cache value');
                return;
            }
            
            // Test get operation
            $get_result = $cache->get($test_key);
            
            if ($get_result !== $test_value) {
                $this->add_result($test_name, 'FAIL', 'Cache value mismatch');
                return;
            }
            
            // Test delete operation
            $delete_result = $cache->delete($test_key);
            
            if (!$delete_result) {
                $this->add_result($test_name, 'WARNING', 'Failed to delete cache value');
            }
            
            // Verify deletion
            $verify_delete = $cache->get($test_key);
            
            if ($verify_delete === false) {
                $this->add_result($test_name, 'PASS', 'Caching system working correctly');
            } else {
                $this->add_result($test_name, 'WARNING', 'Cache deletion not verified');
            }
            
        } catch (Exception $e) {
            $this->add_result($test_name, 'FAIL', 'Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test 7: Test admin dashboard
     */
    private function test_admin_dashboard() {
        $test_name = "Admin Dashboard";
        
        try {
            // Check if admin menu is registered
            global $menu, $submenu;
            
            $menu_found = false;
            $menu_slug = 'crawlguard-production';
            
            if (is_array($menu)) {
                foreach ($menu as $menu_item) {
                    if (isset($menu_item[2]) && strpos($menu_item[2], $menu_slug) !== false) {
                        $menu_found = true;
                        break;
                    }
                }
            }
            
            if (!$menu_found && isset($submenu)) {
                foreach ($submenu as $parent => $items) {
                    foreach ($items as $item) {
                        if (isset($item[2]) && strpos($item[2], $menu_slug) !== false) {
                            $menu_found = true;
                            break 2;
                        }
                    }
                }
            }
            
            if ($menu_found) {
                // Check if admin assets are enqueued
                $admin_css = CRAWLGUARD_PROD_PLUGIN_URL . 'assets/css/admin.css';
                $admin_js = CRAWLGUARD_PROD_PLUGIN_URL . 'assets/js/admin.js';
                
                $css_exists = file_exists(str_replace(site_url(), ABSPATH, $admin_css));
                $js_exists = file_exists(str_replace(site_url(), ABSPATH, $admin_js));
                
                if ($css_exists && $js_exists) {
                    $this->add_result($test_name, 'PASS', 'Admin dashboard registered with assets');
                } else {
                    $this->add_result($test_name, 'WARNING', 'Admin dashboard registered but some assets missing');
                }
            } else {
                $this->add_result($test_name, 'WARNING', 'Admin menu not found in WordPress admin');
            }
            
        } catch (Exception $e) {
            $this->add_result($test_name, 'FAIL', 'Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test 8: Test API endpoints
     */
    private function test_api_endpoints() {
        $test_name = "API Endpoints";
        
        try {
            // Get REST routes
            $rest_server = rest_get_server();
            $routes = $rest_server->get_routes();
            
            $crawlguard_routes = [];
            foreach ($routes as $route => $data) {
                if (strpos($route, 'crawlguard') !== false) {
                    $crawlguard_routes[] = $route;
                }
            }
            
            if (!empty($crawlguard_routes)) {
                $route_list = implode(', ', $crawlguard_routes);
                $this->add_result($test_name, 'PASS', 
                    'API endpoints registered: ' . $route_list);
            } else {
                $this->add_result($test_name, 'INFO', 
                    'No REST API endpoints found (may be normal if not implemented)');
            }
            
        } catch (Exception $e) {
            $this->add_result($test_name, 'FAIL', 'Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test 9: Test security features
     */
    private function test_security_features() {
        $test_name = "Security Features";
        
        if (!class_exists('CrawlGuard_Security_Manager')) {
            $this->add_result($test_name, 'SKIP', 'Security manager class not loaded');
            return;
        }
        
        try {
            $security = CrawlGuard_Security_Manager::get_instance();
            $checks_passed = [];
            $checks_failed = [];
            
            // Test nonce verification
            if (method_exists($security, 'create_nonce')) {
                $nonce = $security->create_nonce('test_action');
                if (!empty($nonce)) {
                    $checks_passed[] = 'Nonce generation';
                } else {
                    $checks_failed[] = 'Nonce generation';
                }
            }
            
            // Test input sanitization
            if (method_exists($security, 'sanitize_input')) {
                $dirty_input = '<script>alert("xss")</script>';
                $clean_input = $security->sanitize_input($dirty_input);
                if ($clean_input !== $dirty_input && strpos($clean_input, '<script>') === false) {
                    $checks_passed[] = 'Input sanitization';
                } else {
                    $checks_failed[] = 'Input sanitization';
                }
            }
            
            // Test rate limiting
            if (method_exists($security, 'check_rate_limit')) {
                $ip = '127.0.0.1';
                $rate_limit_ok = $security->check_rate_limit($ip);
                if ($rate_limit_ok !== null) {
                    $checks_passed[] = 'Rate limiting';
                }
            }
            
            if (count($checks_passed) > count($checks_failed)) {
                $this->add_result($test_name, 'PASS', 
                    'Security features working: ' . implode(', ', $checks_passed));
            } else {
                $this->add_result($test_name, 'WARNING', 
                    'Some security features not working properly',
                    ['Passed' => $checks_passed, 'Failed' => $checks_failed]);
            }
            
        } catch (Exception $e) {
            $this->add_result($test_name, 'FAIL', 'Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test 10: Check PHP compatibility
     */
    private function test_php_compatibility() {
        $test_name = "PHP Compatibility";
        
        $php_version = phpversion();
        $required_version = '7.4';
        $issues = [];
        
        // Check PHP version
        if (version_compare($php_version, $required_version, '>=')) {
            $checks = "PHP $php_version (OK)";
        } else {
            $issues[] = "PHP version $php_version is below required $required_version";
        }
        
        // Check required extensions
        $required_extensions = ['curl', 'json', 'openssl'];
        $missing_extensions = [];
        
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }
        
        if (!empty($missing_extensions)) {
            $issues[] = "Missing PHP extensions: " . implode(', ', $missing_extensions);
        }
        
        // Check for deprecated functions
        $deprecated_functions = ['mysql_connect', 'ereg', 'split'];
        $plugin_dir = WP_PLUGIN_DIR . '/crawlguard-production/';
        
        if (is_dir($plugin_dir)) {
            foreach ($deprecated_functions as $func) {
                $grep_result = shell_exec("grep -r '$func(' $plugin_dir 2>/dev/null");
                if (!empty($grep_result)) {
                    $issues[] = "Deprecated function '$func' found in code";
                }
            }
        }
        
        if (empty($issues)) {
            $this->add_result($test_name, 'PASS', 
                "PHP compatibility check passed (PHP $php_version)");
        } else {
            $this->add_result($test_name, 'WARNING', 
                'PHP compatibility issues found',
                $issues);
        }
    }
    
    /**
     * Add test result
     */
    private function add_result($test_name, $status, $message, $details = []) {
        $this->test_results[] = [
            'test' => $test_name,
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
    }
    
    /**
     * Display test results
     */
    private function display_results() {
        $total_tests = count($this->test_results);
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        $skipped = 0;
        
        echo "<h2>Test Results Summary</h2>";
        echo "<table border='1' cellpadding='10' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Test Name</th>";
        echo "<th>Status</th>";
        echo "<th>Message</th>";
        echo "</tr>";
        
        foreach ($this->test_results as $result) {
            $status_color = $this->get_status_color($result['status']);
            
            echo "<tr>";
            echo "<td><strong>{$result['test']}</strong></td>";
            echo "<td style='background-color: $status_color; color: white; text-align: center;'>{$result['status']}</td>";
            echo "<td>{$result['message']}";
            
            if (!empty($result['details'])) {
                echo "<br><small><em>Details:</em><br>";
                if (is_array($result['details'])) {
                    echo "<ul>";
                    foreach ($result['details'] as $key => $detail) {
                        if (is_array($detail)) {
                            echo "<li>$key: " . implode(', ', $detail) . "</li>";
                        } else {
                            echo "<li>$detail</li>";
                        }
                    }
                    echo "</ul>";
                } else {
                    echo $result['details'];
                }
                echo "</small>";
            }
            
            echo "</td>";
            echo "</tr>";
            
            // Count results
            switch ($result['status']) {
                case 'PASS':
                    $passed++;
                    break;
                case 'FAIL':
                    $failed++;
                    break;
                case 'WARNING':
                    $warnings++;
                    break;
                case 'SKIP':
                case 'INFO':
                    $skipped++;
                    break;
            }
        }
        
        echo "</table>";
        
        // Summary
        echo "<h3>Summary</h3>";
        echo "<ul>";
        echo "<li>Total Tests: $total_tests</li>";
        echo "<li style='color: green;'>Passed: $passed</li>";
        echo "<li style='color: red;'>Failed: $failed</li>";
        echo "<li style='color: orange;'>Warnings: $warnings</li>";
        echo "<li style='color: gray;'>Skipped/Info: $skipped</li>";
        echo "</ul>";
        
        // Overall status
        if ($failed == 0 && $warnings <= 2) {
            echo "<h2 style='color: green;'>✓ Overall Status: GOOD</h2>";
            echo "<p>The CrawlGuard Production plugin is functioning properly.</p>";
        } elseif ($failed == 0) {
            echo "<h2 style='color: orange;'>⚠ Overall Status: NEEDS ATTENTION</h2>";
            echo "<p>The plugin is working but has some warnings that should be addressed.</p>";
        } else {
            echo "<h2 style='color: red;'>✗ Overall Status: CRITICAL ISSUES</h2>";
            echo "<p>The plugin has critical issues that need immediate attention.</p>";
        }
        
        // Recommendations
        echo "<h3>Recommendations</h3>";
        echo "<ul>";
        
        if ($failed > 0) {
            echo "<li>Address failed tests immediately to ensure proper functionality</li>";
        }
        
        if ($warnings > 0) {
            echo "<li>Review and fix warnings to improve plugin stability</li>";
        }
        
        echo "<li>Run PHPUnit tests if available: <code>vendor/bin/phpunit</code></li>";
        echo "<li>Check WordPress debug log for any additional errors</li>";
        echo "<li>Test with different WordPress themes for compatibility</li>";
        echo "<li>Verify plugin works with latest WordPress version</li>";
        echo "</ul>";
    }
    
    /**
     * Get status color for display
     */
    private function get_status_color($status) {
        switch ($status) {
            case 'PASS':
                return '#28a745';
            case 'FAIL':
                return '#dc3545';
            case 'WARNING':
                return '#ffc107';
            case 'SKIP':
            case 'INFO':
                return '#6c757d';
            default:
                return '#333';
        }
    }
}

// Run the tests
$tester = new CrawlGuard_Production_Tester();
$tester->run_all_tests();
