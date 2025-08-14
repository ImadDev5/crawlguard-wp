# CrawlGuard WP Deployment Guide

This guide covers the complete deployment process for CrawlGuard WP, from development to production.

## 🏗️ Architecture Overview

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   WordPress     │    │   Cloudflare     │    │   PostgreSQL    │
│   Plugin        │◄──►│   Workers        │◄──►│   Database      │
│                 │    │   (API Layer)    │    │   (ACID Safe)   │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       │                       │
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   React.js      │    │   Cloudflare     │    │   Stripe        │
│   Dashboard     │    │   R2 Storage     │    │   Connect       │
│                 │    │   (Logs/Assets)  │    │   (Payments)    │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 📋 Prerequisites

### Development Environment
- Node.js 18+
- PHP 7.4+
- WordPress 5.0+
- PostgreSQL 12+
- Cloudflare account
- Stripe account

### Production Requirements
- Cloudflare Workers (Paid plan for database access)
- PostgreSQL database (managed service recommended)
- Stripe Connect platform account
- SSL certificates

## 🚀 Phase 1: Local Development Setup

### 1. Clone and Setup
```bash
# Clone the repository
git clone https://github.com/crawlguard/crawlguard-wp.git
cd crawlguard-wp

# Install dependencies
npm install

# Build assets
npm run build
```

### 2. WordPress Plugin Setup
```bash
# Copy plugin to WordPress
cp -r . /path/to/wordpress/wp-content/plugins/crawlguard-wp/

# Or create symlink for development
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/crawlguard-wp
```

### 3. Database Setup
```bash
# Create PostgreSQL database
createdb crawlguard_dev

# Run schema
psql crawlguard_dev < database/schema.sql
```

### 4. Environment Configuration
Create `.env` file:
```env
# Database
DATABASE_URL=postgresql://user:pass@localhost:5432/crawlguard_dev

# Stripe
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...

# Cloudflare
CLOUDFLARE_API_TOKEN=your_token
CLOUDFLARE_ACCOUNT_ID=your_account_id

# JWT Secret
JWT_SECRET=your_jwt_secret_here
```

## 🌐 Phase 2: Cloudflare Workers Deployment

### 1. Install Wrangler CLI
```bash
npm install -g wrangler
wrangler login
```

### 2. Configure wrangler.toml
```toml
name = "crawlguard-api"
main = "backend/worker.js"
compatibility_date = "2023-10-01"

[env.production]
name = "crawlguard-api-prod"
vars = { ENVIRONMENT = "production" }

[[env.production.r2_buckets]]
binding = "LOGS_BUCKET"
bucket_name = "crawlguard-logs"

[env.production.d1_databases]
binding = "ANALYTICS_DB"
database_name = "crawlguard-analytics"
database_id = "your-d1-database-id"
```

### 3. Set Environment Variables
```bash
# Production secrets
wrangler secret put DATABASE_URL --env production
wrangler secret put STRIPE_SECRET_KEY --env production
wrangler secret put JWT_SECRET --env production
```

### 4. Deploy Worker
```bash
# Deploy to production
wrangler publish --env production

# Test deployment
curl https://crawlguard-api-prod.your-subdomain.workers.dev/v1/status
```

## 🗄️ Phase 3: Database Deployment

### 1. Choose Database Provider
Recommended managed PostgreSQL providers:
- **AWS RDS** (recommended for scale)
- **Google Cloud SQL**
- **DigitalOcean Managed Databases**
- **Heroku Postgres** (for quick start)

### 2. Database Setup
```bash
# Connect to production database
psql $PRODUCTION_DATABASE_URL

# Run schema
\i database/schema.sql

# Verify tables
\dt
```

### 3. Database Security
```sql
-- Create application user
CREATE USER crawlguard_app WITH PASSWORD 'secure_password';

-- Grant necessary permissions
GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA public TO crawlguard_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO crawlguard_app;

-- Revoke unnecessary permissions
REVOKE CREATE ON SCHEMA public FROM PUBLIC;
```

## 💳 Phase 4: Stripe Integration

### 1. Stripe Connect Setup
```bash
# Create Connect platform
# Go to Stripe Dashboard > Connect > Settings
# Enable Express accounts
# Set up webhooks
```

### 2. Webhook Configuration
Set up webhooks for:
- `payment_intent.succeeded`
- `account.updated`
- `payout.paid`

Webhook URL: `https://your-worker.workers.dev/v1/webhooks/stripe`

### 3. Test Stripe Integration
```bash
# Use Stripe CLI for testing
stripe listen --forward-to https://your-worker.workers.dev/v1/webhooks/stripe
```

## 📦 Phase 5: WordPress Plugin Distribution

### 1. Prepare for WordPress.org
```bash
# Create distribution package
npm run zip

# Validate plugin
# - Check WordPress coding standards
# - Test on multiple WP versions
# - Security review
```

### 2. WordPress.org Submission
1. Create developer account
2. Submit plugin for review
3. Address any feedback
4. Publish to repository

### 3. Premium Version Setup
```bash
# Set up licensing server
# Configure automatic updates
# Create customer portal
```

## 🔧 Phase 6: Monitoring & Analytics

### 1. Cloudflare Analytics
```javascript
// Add to worker
addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request));
  
  // Log metrics
  event.waitUntil(logMetrics(event.request));
});
```

### 2. Error Tracking
```bash
# Set up Sentry or similar
npm install @sentry/node
```

### 3. Performance Monitoring
- Cloudflare Analytics
- Database query monitoring
- API response times
- WordPress plugin performance

## 🚦 Phase 7: Go-Live Checklist

### Pre-Launch
- [ ] All tests passing
- [ ] Security audit completed
- [ ] Performance benchmarks met
- [ ] Database backups configured
- [ ] Monitoring alerts set up
- [ ] Documentation complete

### Launch Day
- [ ] Deploy to production
- [ ] Verify all integrations
- [ ] Monitor error rates
- [ ] Check payment processing
- [ ] Test user registration flow

### Post-Launch
- [ ] Monitor user feedback
- [ ] Track key metrics
- [ ] Plan first updates
- [ ] Scale infrastructure as needed

## 📊 Scaling Considerations

### Traffic Growth
- **10K sites**: Single worker, basic database
- **100K sites**: Multiple workers, read replicas
- **1M+ sites**: Worker scaling, database sharding

### Revenue Milestones
- **$1K MRR**: Basic monitoring
- **$10K MRR**: Advanced analytics, A/B testing
- **$100K MRR**: Dedicated infrastructure, 24/7 support

## 🔒 Security Checklist

### API Security
- [ ] Rate limiting implemented
- [ ] API key validation
- [ ] Input sanitization
- [ ] SQL injection prevention

### WordPress Security
- [ ] Nonce verification
- [ ] Capability checks
- [ ] Data validation
- [ ] XSS prevention

### Financial Security
- [ ] PCI compliance (via Stripe)
- [ ] Secure webhook handling
- [ ] Transaction logging
- [ ] Fraud detection

## 📞 Support Infrastructure

### Documentation
- User guides
- Developer documentation
- API reference
- Troubleshooting guides

### Support Channels
- WordPress.org forums (free users)
- Email support (paid users)
- Priority support (business users)
- Community Discord/Slack

## 🎯 Success Metrics

### Technical KPIs
- Plugin activation rate
- API response times < 200ms
- 99.9% uptime
- Zero data loss

### Business KPIs
- Monthly recurring revenue
- User acquisition cost
- Customer lifetime value
- Churn rate

---

**Ready to deploy? Let's make CrawlGuard WP the standard for AI content monetization!**

For deployment support: [support@crawlguard.com](mailto:support@crawlguard.com)
