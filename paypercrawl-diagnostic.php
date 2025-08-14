<?php
/**
 * Plugin Activation Diagnostic
 * Place this in your wp-content/plugins/ folder and activate to test environment
 */

/*
Plugin Name: PayPerCrawl Diagnostic
Description: Test if your environment can run PayPerCrawl
Version: 1.0
*/

register_activation_hook(__FILE__, 'paypercrawl_diagnostic_test');

function paypercrawl_diagnostic_test() {
    $errors = [];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = 'PHP 7.4+ required. Current: ' . PHP_VERSION;
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, '5.0', '<')) {
        $errors[] = 'WordPress 5.0+ required. Current: ' . $wp_version;
    }
    
    // Check required functions
    $required_functions = ['wp_create_nonce', 'add_action', 'register_activation_hook'];
    foreach ($required_functions as $func) {
        if (!function_exists($func)) {
            $errors[] = "Required function missing: $func";
        }
    }
    
    // Check database access
    global $wpdb;
    if (!$wpdb) {
        $errors[] = 'Database connection failed';
    }
    
    if (!empty($errors)) {
        wp_die('PayPerCrawl Environment Check Failed:<br>' . implode('<br>', $errors));
    }
    
    // If we get here, environment is good
    add_option('paypercrawl_diagnostic_passed', current_time('mysql'));
}

add_action('admin_notices', function() {
    if (get_option('paypercrawl_diagnostic_passed')) {
        echo '<div class="notice notice-success"><p>✅ PayPerCrawl environment check passed! You can now install the main plugin.</p></div>';
    }
});
