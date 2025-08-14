# CrawlGuard WP - System Design Document

## 📋 **Document Overview**

**Document Version**: 1.0  
**Last Updated**: 2025-07-11  
**Author**: CTO, CrawlGuard Team  
**Status**: Production Ready  

## 🎯 **Executive Summary**

CrawlGuard WP implements a headless architecture that separates WordPress plugin functionality from heavy processing, ensuring zero performance impact on client websites while providing enterprise-grade AI bot detection and monetization capabilities.

## 🏗️ **System Architecture**

### **High-Level Architecture**

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   WordPress     │    │   Cloudflare     │    │   PostgreSQL    │
│     Plugin      │◄──►│    Workers       │◄──►│    Database     │
│  (Lightweight)  │    │  (Processing)    │    │ (Financial Data)│
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                        │                        │
         ▼                        ▼                        ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ React Dashboard │    │ Stripe Connect   │    │ Analytics Engine│
│   (Frontend)    │    │   (Payments)     │    │  (Aggregation)  │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

### **Component Breakdown**

#### **1. WordPress Plugin Layer**
- **Purpose**: Lightweight client-side integration
- **Responsibilities**:
  - Bot detection triggers
  - User interface rendering
  - API communication
  - Settings management
- **Technology**: PHP 7.4+, JavaScript ES6+, React 18

#### **2. Cloudflare Workers Layer**
- **Purpose**: Edge computing and API processing
- **Responsibilities**:
  - AI bot detection algorithms
  - Monetization logic
  - Rate limiting and security
  - Database operations
- **Technology**: JavaScript ES2022, Cloudflare Workers Runtime

#### **3. Database Layer**
- **Purpose**: ACID-compliant data persistence
- **Responsibilities**:
  - Site registration and management
  - Bot request logging
  - Financial transaction records
  - Analytics aggregation
- **Technology**: PostgreSQL 14+, ACID transactions

#### **4. Payment Processing Layer**
- **Purpose**: Secure financial transactions
- **Responsibilities**:
  - Payment intent creation
  - Stripe Connect integration
  - Payout management
  - Fee calculation
- **Technology**: Stripe Connect, Webhook handling

## 🔄 **Data Flow Architecture**

### **Bot Detection Flow**

```
1. User visits WordPress site
2. Plugin detects request and extracts metadata
3. Async API call to Cloudflare Workers
4. Worker analyzes user agent and IP patterns
5. Database lookup for bot signatures
6. Confidence scoring algorithm
7. Monetization decision logic
8. Response sent back to plugin
9. Action executed (allow/block/monetize)
10. Event logged to database
```

### **Monetization Flow**

```
1. Bot detected with high confidence
2. Content access pricing calculated
3. Stripe payment intent created
4. AI company charged via Stripe Connect
5. Platform fee deducted (15-25%)
6. Creator payout scheduled
7. Transaction logged with ACID compliance
8. Analytics updated in real-time
```

## 🗄️ **Database Schema Design**

### **Core Tables**

#### **sites**
```sql
CREATE TABLE sites (
    id SERIAL PRIMARY KEY,
    site_url VARCHAR(255) NOT NULL UNIQUE,
    site_name VARCHAR(255),
    admin_email VARCHAR(255) NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    subscription_tier VARCHAR(20) DEFAULT 'free',
    monetization_enabled BOOLEAN DEFAULT false,
    pricing_per_request DECIMAL(10,6) DEFAULT 0.001,
    stripe_account_id VARCHAR(255),
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

#### **bot_requests**
```sql
CREATE TABLE bot_requests (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    ip_address INET NOT NULL,
    user_agent TEXT NOT NULL,
    bot_detected BOOLEAN DEFAULT false,
    bot_type VARCHAR(100),
    bot_name VARCHAR(100),
    confidence_score INTEGER DEFAULT 0,
    page_url TEXT,
    action_taken VARCHAR(20) DEFAULT 'logged',
    revenue_amount DECIMAL(10,6) DEFAULT 0.00,
    payment_id VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

#### **payments**
```sql
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    payment_intent_id VARCHAR(255) NOT NULL UNIQUE,
    amount DECIMAL(10,6) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) NOT NULL,
    stripe_fee DECIMAL(10,6) DEFAULT 0.00,
    platform_fee DECIMAL(10,6) DEFAULT 0.00,
    creator_payout DECIMAL(10,6) DEFAULT 0.00,
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### **Performance Optimizations**

#### **Indexes**
```sql
-- High-frequency query optimization
CREATE INDEX idx_sites_api_key ON sites(api_key);
CREATE INDEX idx_bot_requests_site_id ON bot_requests(site_id);
CREATE INDEX idx_bot_requests_created_at ON bot_requests(created_at);
CREATE INDEX idx_payments_status ON payments(status);
```

#### **Partitioning Strategy**
- **bot_requests**: Partitioned by month for historical data management
- **analytics_daily**: Partitioned by year for long-term storage
- **payments**: Partitioned by quarter for financial reporting

## 🔌 **API Design**

### **RESTful Endpoints**

#### **Authentication**
```
Header: X-API-Key: {site_api_key}
Header: Content-Type: application/json
```

#### **Core Endpoints**

**Health Check**
```
GET /v1/status
Response: {
  "status": "ok",
  "timestamp": 1699123456789,
  "version": "1.0.0",
  "services": {
    "database": "connected",
    "stripe": "configured",
    "storage": "ready"
  }
}
```

**Bot Detection**
```
POST /v1/detect
Body: {
  "user_agent": "GPTBot/1.0",
  "ip_address": "192.168.1.1",
  "page_url": "https://site.com/article",
  "content_type": "text/html",
  "content_length": 5000
}
Response: {
  "bot_detected": true,
  "bot_name": "GPTBot",
  "confidence": 95,
  "action": "monetize",
  "pricing": 0.002
}
```

**Analytics**
```
GET /v1/analytics?range=30d&metrics=revenue,requests
Response: {
  "total_revenue": 125.50,
  "total_requests": 15420,
  "bot_requests": 1250,
  "monetized_requests": 890,
  "top_bots": ["GPTBot", "Claude-Web", "Bard"]
}
```

### **Rate Limiting**

```javascript
const RATE_LIMITS = {
  free: 100,      // requests per hour
  pro: 1000,      // requests per hour
  business: 5000  // requests per hour
};
```

## 🛡️ **Security Architecture**

### **Authentication & Authorization**
- **API Keys**: SHA-256 hashed, 64-character random strings
- **Rate Limiting**: Tier-based request limits
- **IP Whitelisting**: Optional IP restriction for enterprise
- **CORS**: Strict origin validation

### **Data Protection**
- **Encryption**: TLS 1.3 for all communications
- **PII Handling**: Minimal collection, GDPR compliant
- **Database**: Encrypted at rest, connection pooling
- **Secrets**: Environment variables, never in code

### **Financial Security**
- **Stripe Connect**: No direct fund handling
- **ACID Transactions**: Database consistency guaranteed
- **Audit Logging**: All financial operations logged
- **Fraud Detection**: Pattern analysis for suspicious activity

## ⚡ **Performance Architecture**

### **Scalability Design**
- **Horizontal Scaling**: Cloudflare Workers auto-scale
- **Database Optimization**: Connection pooling, prepared statements
- **Caching Strategy**: KV storage for frequently accessed data
- **CDN Integration**: Global asset delivery

### **Performance Targets**
- **API Response Time**: <200ms (95th percentile)
- **Plugin Impact**: <10ms additional page load
- **Database Queries**: <50ms average response
- **Uptime**: 99.9% availability SLA

### **Monitoring & Observability**
- **Health Checks**: Automated system monitoring
- **Error Tracking**: Comprehensive error logging
- **Performance Metrics**: Real-time performance dashboards
- **Alerting**: Automated issue detection and notification

## 🔄 **Technology Stack Decisions**

### **Frontend Choices**

**WordPress Plugin (PHP)**
- ✅ Native WordPress integration
- ✅ Familiar to WordPress developers
- ✅ Large ecosystem and community
- ❌ Performance limitations for heavy processing

**React Dashboard**
- ✅ Modern, responsive UI
- ✅ Real-time data updates
- ✅ Component reusability
- ❌ Additional build complexity

### **Backend Choices**

**Cloudflare Workers**
- ✅ Global edge deployment
- ✅ Automatic scaling
- ✅ Low latency worldwide
- ✅ Cost-effective for high volume
- ❌ Runtime limitations (CPU time, memory)

**PostgreSQL Database**
- ✅ ACID compliance for financial data
- ✅ Advanced indexing and performance
- ✅ JSON support for flexible schemas
- ✅ Mature ecosystem and tooling
- ❌ More complex than NoSQL alternatives

### **Payment Processing**

**Stripe Connect**
- ✅ Marketplace-ready platform
- ✅ Handles compliance and regulations
- ✅ Global payment support
- ✅ Robust API and webhooks
- ❌ Transaction fees (2.9% + 30¢)

## 🚀 **Deployment Architecture**

### **Environment Strategy**
- **Development**: Local WordPress + Cloudflare Workers dev
- **Staging**: Full production mirror for testing
- **Production**: Multi-region deployment with failover

### **CI/CD Pipeline**
```yaml
1. Code commit triggers GitHub Actions
2. Automated testing (unit, integration, security)
3. Build optimization and minification
4. Staging deployment and smoke tests
5. Production deployment with blue-green strategy
6. Post-deployment monitoring and rollback capability
```

### **Infrastructure as Code**
- **Cloudflare**: Terraform for Workers and DNS
- **Database**: Automated schema migrations
- **Monitoring**: Prometheus + Grafana stack
- **Secrets**: HashiCorp Vault integration

## 📊 **Analytics & Reporting**

### **Real-time Metrics**
- Bot detection accuracy rates
- Revenue generation per site
- API response times and errors
- User engagement with dashboard

### **Business Intelligence**
- Monthly recurring revenue (MRR) tracking
- Customer lifetime value (CLV) analysis
- Churn rate and retention metrics
- Market penetration and growth rates

---

**This system design provides the foundation for a scalable, secure, and performant AI content monetization platform that can serve millions of WordPress sites while maintaining enterprise-grade reliability and compliance.**
