<?php
/**
 * PayPerCrawl Debug Helper
 * Upload this file to wp-content/plugins/ and activate it FIRST
 * This will help diagnose any remaining issues
 */

/*
Plugin Name: PayPerCrawl Debug Helper
Description: Helps diagnose PayPerCrawl plugin issues
Version: 1.0
*/

if (!defined('ABSPATH')) {
    exit;
}

// Enable WordPress debug logging
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

register_activation_hook(__FILE__, 'paypercrawl_debug_activation');

function paypercrawl_debug_activation() {
    $debug_info = [];
    
    // Check PHP version
    $debug_info[] = 'PHP Version: ' . PHP_VERSION;
    
    // Check WordPress version
    global $wp_version;
    $debug_info[] = 'WordPress Version: ' . $wp_version;
    
    // Check if required functions exist
    $required_functions = [
        'wp_create_nonce',
        'add_action', 
        'register_activation_hook',
        'wp_enqueue_script',
        'admin_url',
        'current_time',
        'esc_html',
        'human_time_diff'
    ];
    
    foreach ($required_functions as $func) {
        $debug_info[] = "Function $func: " . (function_exists($func) ? 'EXISTS' : 'MISSING');
    }
    
    // Check database
    global $wpdb;
    $debug_info[] = 'Database Connection: ' . ($wpdb ? 'OK' : 'FAILED');
    
    // Check file permissions
    $uploads_dir = wp_upload_dir();
    $debug_info[] = 'Uploads Dir Writable: ' . (is_writable($uploads_dir['basedir']) ? 'YES' : 'NO');
    
    // Save debug info
    update_option('paypercrawl_debug_info', $debug_info);
    update_option('paypercrawl_debug_time', current_time('mysql'));
}

add_action('admin_notices', function() {
    $debug_info = get_option('paypercrawl_debug_info', []);
    if (!empty($debug_info)) {
        echo '<div class="notice notice-info"><h3>PayPerCrawl Debug Info:</h3>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        foreach ($debug_info as $info) {
            echo '<li>' . esc_html($info) . '</li>';
        }
        echo '</ul>';
        echo '<p><strong>Next Step:</strong> If all checks pass, try activating the main PayPerCrawl plugin.</p></div>';
    }
});

// Error handler to catch any PHP errors
function paypercrawl_error_handler($errno, $errstr, $errfile, $errline) {
    $error_info = "PHP Error: [$errno] $errstr in $errfile on line $errline";
    error_log($error_info);
    update_option('paypercrawl_last_error', $error_info);
    return false;
}

set_error_handler('paypercrawl_error_handler');

// Log any fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && $error['type'] === E_ERROR) {
        $error_info = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
        update_option('paypercrawl_fatal_error', $error_info);
    }
});
