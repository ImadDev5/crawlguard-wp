# CrawlGuard Pro - AI Content Protection & Monetization

## Overview

CrawlGuard Pro is a WordPress plugin that detects and monetizes AI bot traffic using Cloudflare's AI crawler infrastructure. Turn your content into a revenue stream by charging AI companies for access while protecting your valuable content.

## Features

### 🛡️ Advanced AI Bot Detection
- **ML-Powered Detection**: Uses machine learning to detect known and unknown AI bots
- **Cloudflare Integration**: Leverages Cloudflare's AI crawler patterns
- **95%+ Accuracy**: Detects bots from OpenAI, Anthropic, Google, Meta, and more
- **Real-time Updates**: Continuously updated bot patterns

### 💰 Content Monetization
- **Dynamic Pricing**: Charge different rates based on bot type and content value
- **Stripe Integration**: Payment processing ready (optional)
- **Revenue Analytics**: Track earnings in real-time
- **Revenue Sharing**: 85% to publishers, 15% platform fee

### 📊 Analytics Dashboard
- **Real-time Monitoring**: Live bot activity feed
- **Revenue Tracking**: Daily, weekly, and monthly earnings
- **Company Breakdown**: See which AI companies access your content
- **Performance Metrics**: Detection rates and content protection stats

### 🔧 Technical Features
- **Edge Computing**: Cloudflare Workers for low latency
- **Fallback Systems**: Works even if APIs are down
- **Error Logging**: Comprehensive logging with remote monitoring
- **Compatibility Checks**: Ensures proper environment setup

## Installation

1. **Upload Plugin**
   - Upload the `crawlguard-pro-final-extracted` folder to `/wp-content/plugins/`
   - Or install via WordPress admin panel

2. **Activate Plugin**
   - Go to Plugins page in WordPress admin
   - Find "CrawlGuard WP Pro" and click "Activate"

3. **Configure Settings** (Optional)
   - Copy `.env.example` to `.env` in the plugin folder
   - Add your Stripe credentials when ready
   - Configure other settings as needed

## Configuration

### Environment Variables (.env)

The plugin works out of the box, but you can customize settings:

```env
# Stripe Configuration (Optional)
STRIPE_PUBLISHABLE_KEY=your_key_here
STRIPE_SECRET_KEY=your_secret_here

# Revenue Sharing
PLATFORM_FEE_PERCENTAGE=15
PUBLISHER_REVENUE_SHARE=85

# Feature Flags
ENABLE_ML_DETECTION=true
ENABLE_REAL_TIME_ANALYTICS=true
ENABLE_PAYMENT_PROCESSING=true
```

### Default Configuration

The plugin includes working defaults:
- API Key: Pre-configured for Cloudflare Workers
- Bot Detection: Enabled with high accuracy mode
- Monetization: Tracking enabled (payment gateway optional)

## Usage

### Dashboard Access
1. Go to WordPress Admin
2. Click "CrawlGuard Pro" in the menu
3. View real-time analytics and bot activity

### Monetization Options
- **Protection Mode**: Block AI bots (default)
- **Monetization Mode**: Charge for access (Stripe required)
- **Analytics Mode**: Track without blocking

### Bot Detection

The plugin automatically detects:
- OpenAI (GPTBot, ChatGPT-User)
- Anthropic (Claude-Web, ClaudeBot)
- Google (Bard, PaLM, Google-Extended)
- Meta (FacebookBot, Meta-ExternalAgent)
- Microsoft (BingBot)
- Others (Perplexity, You.com, Common Crawl)

## API Integration

### Cloudflare Workers
The plugin integrates with Cloudflare Workers at:
```
https://crawlguard-api-prod.crawlguard-api.workers.dev/v1
```

### Endpoints
- `/status` - Check API connection
- `/detect` - Report bot detection
- `/analytics` - Get analytics data

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- SSL certificate (recommended)

## Support

For issues or questions:
- Email: support@crawlguard.com
- Documentation: https://docs.crawlguard.com
- GitHub: https://github.com/crawlguard/wordpress-plugin

## License

GPL v2 or later. See LICENSE file for details.

## Changelog

### Version 2.0.0
- ML-powered bot detection
- Cloudflare AI crawler integration
- Payment handler (Stripe-ready)
- Real-time analytics dashboard
- Enhanced error logging
- Compatibility checker

## Credits

Developed by the CrawlGuard Team
Using Cloudflare's AI crawler infrastructure
