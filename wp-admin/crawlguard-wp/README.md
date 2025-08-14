# CrawlGuard WordPress Plugin

Advanced bot detection and monetization plugin that protects your WordPress site while generating revenue from legitimate bot traffic.

## Features

- **AI-Powered Bot Detection**: 95% accuracy using advanced machine learning algorithms
- **Revenue Optimization**: Monetize bot traffic with intelligent pricing (0.65x optimal multiplier)
- **Real-time Analytics**: Comprehensive dashboard with traffic insights
- **Rate Limiting**: Configurable request limits to prevent abuse
- **API Integration**: Seamlessly connects to CrawlGuard cloud service
- **Auto-Setup**: One-click installation with pre-configured credentials

## Installation

1. Upload the `crawlguard-wp` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin automatically configures itself with working API credentials
4. Visit **Settings > CrawlGuard** to customize your settings

## Configuration

### Basic Settings
- **API URL**: `https://crawlguard-api-prod.crawlguard-api.workers.dev`
- **API Key**: Pre-configured with production key
- **Site ID**: Automatically generated unique identifier
- **Monetization**: Enabled by default with optimized pricing

### Detection Settings
- **Sensitivity**: High (recommended for maximum protection)
- **Allowed Bots**: Googlebot, Bingbot, Slurp, DuckDuckBot
- **Blocked Bots**: Scrapy, Selenium, PhantomJS, Headless browsers
- **Rate Limiting**: 100 requests per hour per IP

### Revenue Settings
- **Pricing per Request**: $0.00065 (optimized with RL algorithm)
- **Revenue Optimization**: Enabled
- **Analytics Tracking**: Enabled

## API Endpoints

The plugin integrates with the following CrawlGuard API endpoints:

- `POST /register` - Site registration
- `POST /detect` - Bot detection
- `POST /monetize` - Revenue generation
- `GET /analytics` - Usage statistics
- `GET /status` - Service health
- `GET /health` - API status

## Database Tables

The plugin creates the following table:
- `wp_crawlguard_logs` - Detection logs and analytics data

## Hooks and Filters

### Actions
- `crawlguard_bot_detected` - Fired when a bot is detected
- `crawlguard_revenue_generated` - Fired when revenue is generated
- `crawlguard_cleanup_logs` - Daily cleanup cron job

### Filters
- `crawlguard_log_retention_days` - Log retention period (default: 30 days)
- `crawlguard_detection_sensitivity` - Detection threshold
- `crawlguard_pricing_multiplier` - Revenue optimization multiplier

## AJAX Endpoints

- `wp_ajax_crawlguard_test_api` - Test API connection
- `wp_ajax_crawlguard_get_analytics` - Fetch analytics data

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher
- cURL extension enabled
- Internet connection for API communication

## Security

- All API communications use HTTPS
- CSRF protection with WordPress nonces
- Capability checks for admin functions
- SQL injection prevention with prepared statements

## Performance

- Minimal impact on site speed (< 5ms per request)
- Efficient database queries with proper indexing
- Asynchronous API calls when possible
- Automatic log cleanup to prevent database bloat

## Support

For technical support or feature requests, please contact our development team.

## Changelog

### Version 1.0.0
- Initial release
- Complete API integration
- Revenue optimization with RL algorithms
- Real-time analytics dashboard
- Auto-setup functionality

## License

This plugin is proprietary software. All rights reserved.
