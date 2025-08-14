# CrawlGuard WP - Production Deployment Guide

## 🎯 **Deployment Overview**

This guide provides comprehensive instructions for deploying CrawlGuard WP to production environments, including infrastructure setup, security configuration, and monitoring implementation.

## 🏗️ **Infrastructure Requirements**

### **Minimum System Requirements**

#### **WordPress Environment**
- **WordPress**: 5.0+ (recommended: 6.3+)
- **PHP**: 7.4+ (recommended: 8.1+)
- **MySQL/MariaDB**: 5.7+ / 10.3+
- **Memory**: 256MB+ (recommended: 512MB+)
- **SSL Certificate**: Required for payment processing

#### **Backend Infrastructure**
- **Cloudflare Workers**: Paid plan ($5/month minimum)
- **PostgreSQL Database**: 1GB+ storage (recommended: Neon/Supabase)
- **Stripe Account**: Business account for Connect platform
- **Domain**: Custom domain with DNS management access

### **Recommended Production Setup**

#### **High-Availability Configuration**
```yaml
WordPress:
  - Load Balancer: Cloudflare or AWS ALB
  - Web Servers: 2+ instances (auto-scaling)
  - Database: MySQL cluster or managed service
  - Cache: Redis/Memcached cluster
  - CDN: Cloudflare Pro plan

Backend:
  - Cloudflare Workers: Global deployment
  - Database: PostgreSQL with read replicas
  - Monitoring: Datadog/New Relic integration
  - Backup: Automated daily backups
```

## 🚀 **Step-by-Step Deployment**

### **Phase 1: Infrastructure Setup**

#### **1. Cloudflare Account Configuration**
```bash
# Install Wrangler CLI
npm install -g wrangler

# Login to Cloudflare
wrangler login

# Verify account access
wrangler whoami
```

#### **2. Database Setup (Neon)**
```bash
# Create Neon account at https://neon.tech
# Create new project: "CrawlGuard Production"
# Copy connection string for later use

# Example connection string:
# postgresql://user:password@host.neon.tech/database?sslmode=require
```

#### **3. Domain Configuration**
```bash
# Add domain to Cloudflare
# Update nameservers at domain registrar
# Configure DNS records:

# A record: @ -> your-server-ip
# CNAME: api -> crawlguard-api-prod.your-subdomain.workers.dev
# CNAME: www -> @ (if needed)
```

### **Phase 2: Backend Deployment**

#### **1. Environment Configuration**
```bash
# Clone repository
git clone https://github.com/crawlguard/crawlguard-wp.git
cd crawlguard-wp

# Install dependencies
npm install

# Configure environment variables
cp .env.example .env.production
```

#### **2. Environment Variables Setup**
```bash
# Set production secrets
wrangler secret put DATABASE_URL --env production
# Paste your Neon connection string

wrangler secret put STRIPE_SECRET_KEY --env production
# Paste your Stripe secret key

wrangler secret put STRIPE_WEBHOOK_SECRET --env production
# Paste your Stripe webhook secret

wrangler secret put JWT_SECRET --env production
# Generate: openssl rand -base64 32
```

#### **3. Database Schema Deployment**
```sql
-- Connect to your production database
-- Run the following schema files in order:

-- 1. Core tables
\i database/schema-step1-tables.sql

-- 2. Indexes and performance
\i database/schema-step2-indexes.sql

-- 3. Functions and sample data
\i database/schema-step3-functions.sql
```

#### **4. Cloudflare Workers Deployment**
```bash
# Deploy to production
wrangler deploy --env production

# Verify deployment
curl https://your-api-domain.com/v1/status
```

### **Phase 3: WordPress Plugin Deployment**

#### **1. Plugin Preparation**
```bash
# Build production assets
npm run build

# Create plugin package
npm run zip

# Verify package contents
unzip -l crawlguard-wp.zip
```

#### **2. WordPress Installation**
```bash
# Method 1: WordPress Admin Upload
# 1. Go to Plugins → Add New → Upload Plugin
# 2. Upload crawlguard-wp.zip
# 3. Activate plugin

# Method 2: Manual Installation
# 1. Extract plugin to wp-content/plugins/
# 2. Set proper file permissions (644 for files, 755 for directories)
# 3. Activate via WordPress admin
```

#### **3. Plugin Configuration**
```php
// WordPress wp-config.php additions (optional)
define('CRAWLGUARD_API_URL', 'https://api.yourdomain.com/v1/');
define('CRAWLGUARD_DEBUG', false);
define('CRAWLGUARD_CACHE_TTL', 3600);
```

### **Phase 4: Stripe Configuration**

#### **1. Stripe Account Setup**
```bash
# 1. Create Stripe account at https://stripe.com
# 2. Complete business verification
# 3. Enable Stripe Connect platform
# 4. Configure webhook endpoints
```

#### **2. Webhook Configuration**
```bash
# Stripe Dashboard → Webhooks → Add endpoint
# URL: https://api.yourdomain.com/v1/webhooks/stripe
# Events to send:
# - payment_intent.succeeded
# - payment_intent.payment_failed
# - account.updated
# - payout.paid
```

#### **3. Connect Platform Setup**
```javascript
// Stripe Connect configuration
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);

// Create Express account for site owner
const account = await stripe.accounts.create({
  type: 'express',
  country: 'US',
  email: 'site-owner@example.com',
  capabilities: {
    card_payments: { requested: true },
    transfers: { requested: true }
  }
});
```

## 🔒 **Security Configuration**

### **SSL/TLS Setup**
```bash
# Cloudflare SSL configuration
# 1. Set SSL mode to "Full (strict)"
# 2. Enable "Always Use HTTPS"
# 3. Configure HSTS headers
# 4. Enable TLS 1.3

# WordPress SSL configuration
# Add to wp-config.php:
define('FORCE_SSL_ADMIN', true);
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}
```

### **API Security**
```javascript
// Rate limiting configuration
const RATE_LIMITS = {
  free: 100,      // requests per hour
  pro: 1000,      // requests per hour
  business: 5000  // requests per hour
};

// CORS configuration
const corsHeaders = {
  'Access-Control-Allow-Origin': 'https://yourdomain.com',
  'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-API-Key',
  'Access-Control-Max-Age': '86400'
};
```

### **Database Security**
```sql
-- Create dedicated database user
CREATE USER crawlguard_app WITH PASSWORD 'secure_random_password';

-- Grant minimal required permissions
GRANT SELECT, INSERT, UPDATE ON sites TO crawlguard_app;
GRANT SELECT, INSERT, UPDATE ON bot_requests TO crawlguard_app;
GRANT SELECT, INSERT, UPDATE ON payments TO crawlguard_app;

-- Revoke unnecessary permissions
REVOKE ALL ON SCHEMA public FROM PUBLIC;
```

## 📊 **Monitoring & Observability**

### **Health Monitoring Setup**
```javascript
// Cloudflare Workers health check
addEventListener('scheduled', event => {
  event.waitUntil(performHealthChecks());
});

async function performHealthChecks() {
  const checks = [
    checkDatabaseConnection(),
    checkStripeConnectivity(),
    checkAPIResponseTimes()
  ];
  
  const results = await Promise.allSettled(checks);
  
  // Send alerts if any checks fail
  if (results.some(r => r.status === 'rejected')) {
    await sendAlert('Health check failed', results);
  }
}
```

### **Logging Configuration**
```javascript
// Structured logging setup
function logEvent(level, message, context = {}) {
  const logEntry = {
    timestamp: new Date().toISOString(),
    level,
    message,
    context,
    environment: 'production',
    service: 'crawlguard-api'
  };
  
  console.log(JSON.stringify(logEntry));
  
  // Send to external logging service
  if (level === 'error' || level === 'warn') {
    sendToLoggingService(logEntry);
  }
}
```

### **Performance Monitoring**
```javascript
// Performance metrics collection
class MetricsCollector {
  static recordAPILatency(endpoint, duration) {
    // Send to monitoring service (Datadog, New Relic, etc.)
    fetch('https://api.datadoghq.com/api/v1/series', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'DD-API-KEY': process.env.DATADOG_API_KEY
      },
      body: JSON.stringify({
        series: [{
          metric: 'crawlguard.api.latency',
          points: [[Date.now() / 1000, duration]],
          tags: [`endpoint:${endpoint}`, 'env:production']
        }]
      })
    });
  }
}
```

## 🔄 **Backup & Disaster Recovery**

### **Database Backup Strategy**
```bash
# Automated daily backups
#!/bin/bash
# backup-database.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="crawlguard_backup_${DATE}.sql"

# Create backup
pg_dump $DATABASE_URL > $BACKUP_FILE

# Compress backup
gzip $BACKUP_FILE

# Upload to cloud storage (S3, GCS, etc.)
aws s3 cp ${BACKUP_FILE}.gz s3://crawlguard-backups/

# Cleanup local files older than 7 days
find . -name "crawlguard_backup_*.sql.gz" -mtime +7 -delete
```

### **Application Backup**
```bash
# WordPress backup script
#!/bin/bash
# backup-wordpress.sh

# Backup WordPress files
tar -czf wordpress_backup_$(date +%Y%m%d).tar.gz /path/to/wordpress/

# Backup WordPress database
mysqldump -u username -p database_name > wordpress_db_$(date +%Y%m%d).sql

# Upload to cloud storage
aws s3 sync /backup/path/ s3://crawlguard-wp-backups/
```

### **Disaster Recovery Plan**
```yaml
Recovery Time Objectives (RTO):
  - Critical services: 1 hour
  - Full functionality: 4 hours
  - Complete restoration: 24 hours

Recovery Point Objectives (RPO):
  - Database: 1 hour (continuous replication)
  - Application files: 24 hours (daily backups)
  - Configuration: 1 hour (version control)

Recovery Procedures:
  1. Assess damage and determine recovery scope
  2. Restore database from latest backup
  3. Deploy application from version control
  4. Restore configuration and secrets
  5. Verify functionality and performance
  6. Update DNS and routing as needed
```

## 🚀 **Scaling Considerations**

### **Horizontal Scaling**
```yaml
# Auto-scaling configuration
WordPress:
  min_instances: 2
  max_instances: 10
  cpu_threshold: 70%
  memory_threshold: 80%

Database:
  read_replicas: 2
  connection_pooling: enabled
  max_connections: 100

CDN:
  cache_ttl: 3600
  compression: enabled
  minification: enabled
```

### **Performance Optimization**
```javascript
// Database connection pooling
const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  max: 20,
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: 2000
});

// Caching strategy
const cache = new Map();
const CACHE_TTL = 3600; // 1 hour

async function getCachedData(key, fetchFunction) {
  if (cache.has(key)) {
    const { data, timestamp } = cache.get(key);
    if (Date.now() - timestamp < CACHE_TTL * 1000) {
      return data;
    }
  }
  
  const data = await fetchFunction();
  cache.set(key, { data, timestamp: Date.now() });
  return data;
}
```

## ✅ **Deployment Checklist**

### **Pre-Deployment**
- [ ] Infrastructure provisioned and configured
- [ ] Database schema deployed and tested
- [ ] Environment variables configured
- [ ] SSL certificates installed and verified
- [ ] Backup systems configured and tested

### **Deployment**
- [ ] Cloudflare Workers deployed successfully
- [ ] WordPress plugin installed and activated
- [ ] API endpoints responding correctly
- [ ] Database connections established
- [ ] Stripe integration configured and tested

### **Post-Deployment**
- [ ] Health checks passing
- [ ] Monitoring and alerting configured
- [ ] Performance metrics baseline established
- [ ] Security scans completed
- [ ] Documentation updated with production details

### **Go-Live**
- [ ] DNS records updated to production
- [ ] Load testing completed
- [ ] User acceptance testing passed
- [ ] Support team notified and prepared
- [ ] Rollback plan documented and tested

---

**This deployment guide ensures a secure, scalable, and maintainable production environment for CrawlGuard WP, following industry best practices for enterprise-grade applications.**
