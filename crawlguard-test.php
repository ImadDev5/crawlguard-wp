<?php
/**
 * Plugin Name: CrawlGuard WP Test
 * Plugin URI: https://crawlguard.com
 * Description: Test version of CrawlGuard plugin for debugging activation issues.
 * Version: 1.0.0-test
 * Author: CrawlGuard Team
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Add admin notice on activation to confirm it's working
register_activation_hook(__FILE__, 'crawlguard_test_activation');
add_action('admin_notices', 'crawlguard_test_admin_notice');

function crawlguard_test_activation() {
    add_option('crawlguard_test_activated', true);
    
    // Test API connection on activation
    $api_url = 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/status';
    $response = wp_remote_get($api_url, array('timeout' => 10));
    
    if (is_wp_error($response)) {
        add_option('crawlguard_test_api_error', $response->get_error_message());
    } else {
        $body = wp_remote_retrieve_body($response);
        add_option('crawlguard_test_api_response', $body);
    }
}

function crawlguard_test_admin_notice() {
    if (get_option('crawlguard_test_activated')) {
        $api_response = get_option('crawlguard_test_api_response');
        $api_error = get_option('crawlguard_test_api_error');
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>CrawlGuard Test Plugin Activated Successfully!</strong></p>';
        
        if ($api_error) {
            echo '<p><strong>API Error:</strong> ' . esc_html($api_error) . '</p>';
        } elseif ($api_response) {
            echo '<p><strong>API Response:</strong> ' . esc_html($api_response) . '</p>';
        }
        
        echo '</div>';
        
        // Remove the notice after showing it once
        delete_option('crawlguard_test_activated');
    }
}

// Add simple admin menu for testing
add_action('admin_menu', 'crawlguard_test_menu');

function crawlguard_test_menu() {
    add_menu_page(
        'CrawlGuard Test',
        'CrawlGuard Test',
        'manage_options',
        'crawlguard-test',
        'crawlguard_test_page',
        'dashicons-shield-alt',
        30
    );
}

function crawlguard_test_page() {
    echo '<div class="wrap">';
    echo '<h1>CrawlGuard Test Plugin</h1>';
    echo '<p>This is a test version to debug activation issues.</p>';
    
    // Test API connection
    echo '<h2>API Connection Test</h2>';
    $api_url = 'https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/status';
    $response = wp_remote_get($api_url, array('timeout' => 10));
    
    if (is_wp_error($response)) {
        echo '<div class="notice notice-error"><p>API Error: ' . esc_html($response->get_error_message()) . '</p></div>';
    } else {
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        echo '<div class="notice notice-success"><p>API Response (Code: ' . $code . '): <br><code>' . esc_html($body) . '</code></p></div>';
    }
    
    // PHP version info
    echo '<h2>System Information</h2>';
    echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
    echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
    echo '<p><strong>Plugin Directory:</strong> ' . plugin_dir_path(__FILE__) . '</p>';
    
    echo '</div>';
}
