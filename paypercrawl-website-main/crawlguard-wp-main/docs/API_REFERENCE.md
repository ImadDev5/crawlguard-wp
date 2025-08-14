# CrawlGuard WP - API Reference

## 📋 **API Overview**

**Base URL**: `https://api.creativeinteriorsstudio.com/v1/`  
**Authentication**: API Key via `X-API-Key` header  
**Content Type**: `application/json`  
**Rate Limiting**: Tier-based (Free: 100/hour, Pro: 1000/hour, Business: 5000/hour)

## 🔐 **Authentication**

All API requests require authentication via API key in the request header:

```bash
curl -H "X-API-Key: your-api-key-here" \
     -H "Content-Type: application/json" \
     https://api.creativeinteriorsstudio.com/v1/status
```

### **API Key Management**

API keys are generated automatically when a WordPress site is registered. Each key is:
- 64 characters long
- SHA-256 hashed for storage
- Tied to a specific WordPress site
- Rate-limited based on subscription tier

## 🏥 **Health & Status**

### **GET /v1/status**

Check API health and service status.

#### **Request**
```bash
curl -X GET https://api.creativeinteriorsstudio.com/v1/status
```

#### **Response**
```json
{
  "status": "ok",
  "timestamp": 1699123456789,
  "version": "1.0.0",
  "environment": "production",
  "services": {
    "database": "connected",
    "stripe": "configured",
    "storage": "ready"
  },
  "uptime": 99.98,
  "response_time_ms": 45
}
```

#### **Response Fields**
- `status`: Overall system status (`ok`, `degraded`, `down`)
- `timestamp`: Unix timestamp of response
- `version`: API version
- `environment`: Deployment environment
- `services`: Individual service health status
- `uptime`: System uptime percentage (last 30 days)
- `response_time_ms`: Average response time

## 🏢 **Site Management**

### **POST /v1/sites/register**

Register a new WordPress site with CrawlGuard.

#### **Request**
```bash
curl -X POST https://api.creativeinteriorsstudio.com/v1/sites/register \
  -H "Content-Type: application/json" \
  -d '{
    "site_url": "https://example.com",
    "site_name": "My WordPress Site",
    "admin_email": "admin@example.com",
    "wordpress_version": "6.3.1",
    "plugin_version": "1.0.0"
  }'
```

#### **Request Body**
```json
{
  "site_url": "https://example.com",
  "site_name": "My WordPress Site",
  "admin_email": "admin@example.com",
  "wordpress_version": "6.3.1",
  "plugin_version": "1.0.0"
}
```

#### **Response**
```json
{
  "success": true,
  "api_key": "your_generated_api_key_here",
  "site_id": 12345,
  "subscription_tier": "free",
  "message": "Site registered successfully"
}
```

### **GET /v1/sites/info**

Get information about the authenticated site.

#### **Request**
```bash
curl -X GET https://api.creativeinteriorsstudio.com/v1/sites/info \
  -H "X-API-Key: your-api-key"
```

#### **Response**
```json
{
  "site_id": 12345,
  "site_url": "https://example.com",
  "site_name": "My WordPress Site",
  "subscription_tier": "pro",
  "monetization_enabled": true,
  "pricing_per_request": 0.001,
  "total_revenue": 125.50,
  "created_at": "2023-10-01T12:00:00Z",
  "last_activity": "2023-11-01T15:30:00Z"
}
```

## 🤖 **Bot Detection**

### **POST /v1/detect**

Analyze a request to determine if it's from an AI bot.

#### **Request**
```bash
curl -X POST https://api.creativeinteriorsstudio.com/v1/detect \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "user_agent": "GPTBot/1.0",
    "ip_address": "192.168.1.100",
    "page_url": "https://example.com/article/ai-trends",
    "content_type": "article",
    "content_length": 5000,
    "referrer": "https://google.com"
  }'
```

#### **Request Body**
```json
{
  "user_agent": "GPTBot/1.0",
  "ip_address": "192.168.1.100",
  "page_url": "https://example.com/article/ai-trends",
  "content_type": "article",
  "content_length": 5000,
  "referrer": "https://google.com"
}
```

#### **Response**
```json
{
  "bot_detected": true,
  "bot_name": "GPTBot",
  "bot_company": "OpenAI",
  "confidence": 95,
  "action": "monetize",
  "pricing": {
    "amount": 0.002,
    "currency": "USD",
    "reasoning": "High-value article content from known AI bot"
  },
  "request_id": "req_1234567890abcdef",
  "timestamp": "2023-11-01T15:30:00Z"
}
```

#### **Response Fields**
- `bot_detected`: Boolean indicating if request is from a bot
- `bot_name`: Identified bot name (if known)
- `bot_company`: Company operating the bot
- `confidence`: Detection confidence score (0-100)
- `action`: Recommended action (`allow`, `block`, `monetize`)
- `pricing`: Pricing information for monetization
- `request_id`: Unique identifier for this detection request

### **POST /v1/monetize**

Process monetization for a detected AI bot request.

#### **Request**
```bash
curl -X POST https://api.creativeinteriorsstudio.com/v1/monetize \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "request_id": "req_1234567890abcdef",
    "bot_name": "GPTBot",
    "content_value": 0.002,
    "payment_method": "stripe_connect"
  }'
```

#### **Response**
```json
{
  "success": true,
  "payment_intent_id": "pi_1234567890abcdef",
  "amount_charged": 0.002,
  "platform_fee": 0.0003,
  "creator_payout": 0.0017,
  "status": "succeeded",
  "transaction_id": "txn_1234567890abcdef"
}
```

## 📊 **Analytics**

### **GET /v1/analytics**

Retrieve analytics data for the authenticated site.

#### **Request**
```bash
curl -X GET "https://api.creativeinteriorsstudio.com/v1/analytics?range=30d&metrics=revenue,requests,bots" \
  -H "X-API-Key: your-api-key"
```

#### **Query Parameters**
- `range`: Time range (`1d`, `7d`, `30d`, `90d`, `1y`)
- `metrics`: Comma-separated metrics (`revenue`, `requests`, `bots`, `conversion`)
- `granularity`: Data granularity (`hour`, `day`, `week`, `month`)

#### **Response**
```json
{
  "period": {
    "start": "2023-10-01T00:00:00Z",
    "end": "2023-10-31T23:59:59Z",
    "range": "30d"
  },
  "summary": {
    "total_revenue": 125.50,
    "total_requests": 15420,
    "bot_requests": 1250,
    "monetized_requests": 890,
    "conversion_rate": 0.712
  },
  "daily_data": [
    {
      "date": "2023-10-01",
      "revenue": 4.25,
      "requests": 520,
      "bot_requests": 45,
      "monetized_requests": 32
    }
  ],
  "top_bots": [
    {
      "name": "GPTBot",
      "company": "OpenAI",
      "requests": 450,
      "revenue": 45.50
    },
    {
      "name": "Claude-Web",
      "company": "Anthropic",
      "requests": 320,
      "revenue": 32.10
    }
  ]
}
```

### **GET /v1/analytics/revenue**

Get detailed revenue analytics.

#### **Response**
```json
{
  "total_revenue": 125.50,
  "revenue_by_bot": {
    "GPTBot": 45.50,
    "Claude-Web": 32.10,
    "Bard": 28.90,
    "Other": 19.00
  },
  "revenue_by_content_type": {
    "article": 75.30,
    "tutorial": 35.20,
    "code": 15.00
  },
  "monthly_growth": 15.2,
  "projected_annual": 1506.00
}
```

## 💳 **Payments**

### **GET /v1/payments**

Retrieve payment history for the authenticated site.

#### **Request**
```bash
curl -X GET "https://api.creativeinteriorsstudio.com/v1/payments?limit=50&status=succeeded" \
  -H "X-API-Key: your-api-key"
```

#### **Query Parameters**
- `limit`: Number of payments to return (max 100)
- `status`: Filter by payment status (`pending`, `succeeded`, `failed`)
- `from`: Start date (ISO 8601)
- `to`: End date (ISO 8601)

#### **Response**
```json
{
  "payments": [
    {
      "id": "pay_1234567890abcdef",
      "amount": 0.002,
      "currency": "USD",
      "status": "succeeded",
      "bot_name": "GPTBot",
      "content_url": "https://example.com/article/ai-trends",
      "platform_fee": 0.0003,
      "creator_payout": 0.0017,
      "created_at": "2023-11-01T15:30:00Z"
    }
  ],
  "has_more": false,
  "total_count": 1250
}
```

### **POST /v1/payouts/request**

Request a payout of accumulated earnings.

#### **Request**
```bash
curl -X POST https://api.creativeinteriorsstudio.com/v1/payouts/request \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "method": "stripe_connect"
  }'
```

#### **Response**
```json
{
  "success": true,
  "payout_id": "po_1234567890abcdef",
  "amount": 100.00,
  "estimated_arrival": "2023-11-03T00:00:00Z",
  "status": "pending"
}
```

## ⚙️ **Configuration**

### **PUT /v1/settings**

Update site configuration settings.

#### **Request**
```bash
curl -X PUT https://api.creativeinteriorsstudio.com/v1/settings \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "monetization_enabled": true,
    "pricing_per_request": 0.0015,
    "allowed_bots": ["GPTBot", "Claude-Web"],
    "blocked_bots": ["BadBot"]
  }'
```

#### **Response**
```json
{
  "success": true,
  "settings": {
    "monetization_enabled": true,
    "pricing_per_request": 0.0015,
    "allowed_bots": ["GPTBot", "Claude-Web"],
    "blocked_bots": ["BadBot"],
    "updated_at": "2023-11-01T15:30:00Z"
  }
}
```

## 🚨 **Error Handling**

### **Error Response Format**
```json
{
  "success": false,
  "error": {
    "code": "INVALID_API_KEY",
    "message": "The provided API key is invalid or expired",
    "details": {
      "api_key_prefix": "your_key_prefix...",
      "timestamp": "2023-11-01T15:30:00Z"
    }
  },
  "request_id": "req_1234567890abcdef"
}
```

### **Common Error Codes**

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `INVALID_API_KEY` | 401 | API key is invalid or expired |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests for current tier |
| `INSUFFICIENT_PERMISSIONS` | 403 | API key lacks required permissions |
| `VALIDATION_ERROR` | 400 | Request data validation failed |
| `RESOURCE_NOT_FOUND` | 404 | Requested resource doesn't exist |
| `INTERNAL_ERROR` | 500 | Internal server error |
| `SERVICE_UNAVAILABLE` | 503 | Service temporarily unavailable |

### **Rate Limiting Headers**
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1699123456
X-RateLimit-Tier: pro
```

## 📝 **Webhooks**

### **Webhook Events**

CrawlGuard can send webhooks for important events:

#### **bot.detected**
```json
{
  "event": "bot.detected",
  "data": {
    "site_id": 12345,
    "bot_name": "GPTBot",
    "confidence": 95,
    "page_url": "https://example.com/article",
    "timestamp": "2023-11-01T15:30:00Z"
  }
}
```

#### **payment.succeeded**
```json
{
  "event": "payment.succeeded",
  "data": {
    "payment_id": "pay_1234567890abcdef",
    "amount": 0.002,
    "site_id": 12345,
    "bot_name": "GPTBot",
    "timestamp": "2023-11-01T15:30:00Z"
  }
}
```

### **Webhook Configuration**
```bash
curl -X POST https://api.creativeinteriorsstudio.com/v1/webhooks \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://yoursite.com/webhook-endpoint",
    "events": ["bot.detected", "payment.succeeded"],
    "secret": "your-webhook-secret"
  }'
```

---

**This API reference provides complete documentation for integrating with CrawlGuard WP's backend services, enabling developers to build custom integrations and extend the platform's functionality.**
