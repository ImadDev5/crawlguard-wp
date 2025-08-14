<?php
/**
 * Plugin Name: CrawlGuard Simple
 * Plugin URI: https://creativeinteriorsstudio.com
 * Description: A simple test plugin for CrawlGuard
 * Version: 1.0.0
 * Author: CrawlGuard Team
 * Author URI: https://creativeinteriorsstudio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: crawlguard-simple
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add a simple admin notice to confirm the plugin is working
add_action('admin_notices', 'crawlguard_simple_admin_notice');
function crawlguard_simple_admin_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('CrawlGuard Simple is active!', 'crawlguard-simple'); ?></p>
    </div>
    <?php
}
