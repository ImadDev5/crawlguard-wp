<?php
/**
 * CrawlGuard WP Plugin Production Readiness Test
 * Run this script to verify all plugin components are working correctly
 */

// Test configuration
$tests_passed = 0;
$tests_failed = 0;
$test_results = [];

function run_test($name, $test_function) {
    global $tests_passed, $tests_failed, $test_results;
    
    echo "Testing: $name... ";
    try {
        $result = $test_function();
        if ($result === true) {
            echo "✅ PASSED\n";
            $tests_passed++;
            $test_results[$name] = 'PASSED';
        } else {
            echo "❌ FAILED: $result\n";
            $tests_failed++;
            $test_results[$name] = "FAILED: $result";
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $tests_failed++;
        $test_results[$name] = "ERROR: " . $e->getMessage();
    }
}

echo "\n=================================================\n";
echo "   CrawlGuard WP Production Readiness Tests\n";
echo "=================================================\n\n";

// Test 1: Check plugin files exist
run_test("Plugin files structure", function() {
    $required_files = [
        'crawlguard-wp-main/crawlguard-wp.php',
        'crawlguard-wp-main/includes/class-admin.php',
        'crawlguard-wp-main/includes/class-api-client.php',
        'crawlguard-wp-main/includes/class-bot-detector.php',
        'crawlguard-wp-main/includes/class-config.php',
        'crawlguard-wp-main/includes/class-frontend.php',
        'crawlguard-wp-main/assets/css/admin.css',
        'crawlguard-wp-main/assets/js/admin.js'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            return "Missing file: $file";
        }
    }
    return true;
});

// Test 2: Check PHP syntax
run_test("PHP syntax validation", function() {
    $php_files = [
        'crawlguard-wp-main/crawlguard-wp.php',
        'crawlguard-wp-main/includes/class-admin.php',
        'crawlguard-wp-main/includes/class-api-client.php',
        'crawlguard-wp-main/includes/class-bot-detector.php',
        'crawlguard-wp-main/includes/class-config.php',
        'crawlguard-wp-main/includes/class-frontend.php'
    ];
    
    foreach ($php_files as $file) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            return "Syntax error in $file: $output";
        }
    }
    return true;
});

// Test 3: Check configuration values
run_test("Configuration values", function() {
    require_once 'crawlguard-wp-main/includes/class-config.php';
    
    $config = CrawlGuard_Config::get_cloudflare_config();
    
    if (empty($config['api_base_url'])) {
        return "API base URL not configured";
    }
    
    if (empty($config['worker_url'])) {
        return "Worker URL not configured";
    }
    
    if ($config['api_base_url'] !== 'https://api.creativeinteriorsstudio.com/v1') {
        return "API base URL incorrect: " . $config['api_base_url'];
    }
    
    return true;
});

// Test 4: Check API endpoints configuration
run_test("API endpoints configuration", function() {
    require_once 'crawlguard-wp-main/includes/class-config.php';
    
    $endpoints = CrawlGuard_Config::get_api_endpoints();
    $required_endpoints = ['validate', 'monetize', 'analytics', 'register', 'settings', 'payments', 'beacon', 'status', 'emergency'];
    
    foreach ($required_endpoints as $endpoint) {
        if (!isset($endpoints[$endpoint])) {
            return "Missing endpoint: $endpoint";
        }
        
        // Check for double /v1
        if (strpos($endpoints[$endpoint], '/v1/v1') !== false) {
            return "Double /v1 in endpoint $endpoint: " . $endpoints[$endpoint];
        }
    }
    
    return true;
});

// Test 5: Test API connectivity
run_test("API connectivity", function() {
    $api_url = 'https://api.creativeinteriorsstudio.com/v1/status';
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if (!empty($error)) {
        return "CURL error: $error";
    }
    
    if ($http_code !== 200) {
        return "API returned HTTP $http_code";
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['status'])) {
        return "Invalid API response format";
    }
    
    return true;
});

// Test 6: Check plugin activation behavior
run_test("Plugin activation defaults", function() {
    $file_content = file_get_contents('crawlguard-wp-main/crawlguard-wp.php');
    
    // Check if update_option is used in set_default_options
    if (strpos($file_content, 'update_option(\'crawlguard_options\'') === false) {
        return "Plugin may not properly reset options on activation";
    }
    
    // Check default values
    if (strpos($file_content, "'api_key' => ''") === false) {
        return "Default API key not set to empty";
    }
    
    if (strpos($file_content, "'api_key_valid' => false") === false) {
        return "Default API key valid status not set to false";
    }
    
    return true;
});

// Test 7: Check for debug code
run_test("No debug code in production", function() {
    $php_files = [
        'crawlguard-wp-main/crawlguard-wp.php',
        'crawlguard-wp-main/includes/class-admin.php',
        'crawlguard-wp-main/includes/class-api-client.php',
        'crawlguard-wp-main/includes/class-bot-detector.php',
        'crawlguard-wp-main/includes/class-frontend.php'
    ];
    
    foreach ($php_files as $file) {
        $content = file_get_contents($file);
        
        // Check for var_dump, print_r, die(), exit() (except in proper contexts)
        if (preg_match('/\b(var_dump|print_r|debug_backtrace)\s*\(/i', $content)) {
            return "Debug function found in $file";
        }
        
        // Check for console.log in JS files would go here if needed
    }
    
    return true;
});

// Test 8: Check error handling
run_test("Error handling implementation", function() {
    $api_client_content = file_get_contents('crawlguard-wp-main/includes/class-api-client.php');
    
    // Check for proper error handling
    if (strpos($api_client_content, 'is_wp_error') === false) {
        return "Missing WordPress error handling";
    }
    
    if (strpos($api_client_content, 'error_log') === false) {
        return "Missing error logging";
    }
    
    return true;
});

// Test 9: Security checks
run_test("Security implementation", function() {
    $php_files = glob('crawlguard-wp-main/includes/*.php');
    
    foreach ($php_files as $file) {
        $content = file_get_contents($file);
        
        // Check for ABSPATH check
        if (strpos($content, "defined('ABSPATH')") === false) {
            return "Missing ABSPATH check in " . basename($file);
        }
        
        // Check for nonce verification in AJAX handlers
        if (strpos($content, 'ajax_') !== false && strpos($content, 'check_ajax_referer') === false) {
            // Only flag if it's actually an AJAX handler
            if (preg_match('/function\s+ajax_/', $content)) {
                return "Missing nonce verification in AJAX handler in " . basename($file);
            }
        }
    }
    
    return true;
});

// Test 10: Version consistency
run_test("Version consistency", function() {
    $main_file = file_get_contents('crawlguard-wp-main/crawlguard-wp.php');
    
    // Extract version from plugin header
    preg_match('/\* Version:\s*(.+)/', $main_file, $header_match);
    $header_version = isset($header_match[1]) ? trim($header_match[1]) : null;
    
    // Extract version from constant
    preg_match("/define\('CRAWLGUARD_VERSION',\s*'(.+)'\)/", $main_file, $const_match);
    $const_version = isset($const_match[1]) ? $const_match[1] : null;
    
    if ($header_version !== $const_version) {
        return "Version mismatch: Header=$header_version, Constant=$const_version";
    }
    
    if ($header_version !== '2.0.0') {
        return "Version should be 2.0.0, found: $header_version";
    }
    
    return true;
});

// Summary
echo "\n=================================================\n";
echo "                TEST SUMMARY\n";
echo "=================================================\n";
echo "✅ Tests Passed: $tests_passed\n";
echo "❌ Tests Failed: $tests_failed\n";
echo "Total Tests: " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed > 0) {
    echo "\n⚠️  FAILED TESTS:\n";
    foreach ($test_results as $test => $result) {
        if (strpos($result, 'FAILED') !== false || strpos($result, 'ERROR') !== false) {
            echo "  - $test: $result\n";
        }
    }
    echo "\n❌ PLUGIN IS NOT PRODUCTION READY\n";
} else {
    echo "\n✅ ALL TESTS PASSED - PLUGIN IS PRODUCTION READY!\n";
}

echo "\n=================================================\n";
