<?php
/**
 * CrawlGuard WP Configuration
 * Complete transparency - all settings visible for collaboration
 */

// API Configuration
define('CRAWLGUARD_API_BASE_URL', 'https://api.creativeinteriorsstudio.com/v1');
define('CRAWLGUARD_API_VERSION', 'v1');

// Database Configuration
define('CRAWLGUARD_DB_HOST', 'localhost');
define('CRAWLGUARD_DB_NAME', 'crawlguard_production');
define('CRAWLGUARD_DB_USER', 'crawlguard_user');
define('CRAWLGUARD_DB_PASS', 'secure_password_123');
define('CRAWLGUARD_DB_PORT', 5432);

// Cloudflare Configuration
define('CLOUDFLARE_ACCOUNT_ID', 'your_cloudflare_account_id');
define('CLOUDFLARE_API_TOKEN', 'your_cloudflare_api_token');
define('CLOUDFLARE_ZONE_ID', 'your_zone_id');

// Stripe Configuration
define('STRIPE_PUBLISHABLE_KEY', 'pk_live_your_publishable_key');
define('STRIPE_SECRET_KEY', 'sk_live_your_secret_key');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_webhook_secret');

// Development Settings
define('CRAWLGUARD_DEBUG_MODE', true);
define('CRAWLGUARD_LOG_LEVEL', 'debug');
define('CRAWLGUARD_CACHE_ENABLED', true);

// WordPress Configuration
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

?>
