# PayPerCrawl Production API Backend

Enterprise-grade AI Bot Detection & Monetization Platform API

## 🚀 Features

### Core Functionality
- **Advanced Bot Detection**: Multi-layer detection using AI/ML, IP intelligence, TLS/HTTP fingerprinting
- **Real-time Analytics**: Comprehensive dashboards and reporting
- **License Management**: Flexible licensing system for WordPress plugins
- **Subscription Billing**: Stripe integration for recurring payments
- **Payout System**: Automated revenue sharing for content creators
- **WordPress Integration**: Seamless plugin connectivity

### Security Features
- JWT authentication with refresh tokens
- API key authentication for plugins
- Rate limiting (Redis-based)
- HMAC-SHA256 request signing
- IP whitelisting for critical endpoints
- CORS configuration
- Proof-of-work challenges for suspicious traffic

### Bot Detection Layers
1. **User Agent Analysis**: AI bot signatures, search engines, scrapers
2. **IP Intelligence**: Datacenter detection, VPN/proxy identification
3. **Header Analysis**: Missing browser headers, automation tools
4. **Behavioral Analysis**: Request patterns, rate limiting
5. **TLS/HTTP Fingerprinting**: Advanced bot fingerprinting

## 📋 Prerequisites

- Node.js 18+ and npm/yarn
- PostgreSQL 14+ (or Supabase account)
- Redis 6+
- Stripe account (for payments)
- Cloudflare account (optional)
- MaxMind/IPinfo account (for IP intelligence)

## 🛠 Installation

### 1. Clone the repository
```bash
git clone https://github.com/yourusername/paypercrawl-backend.git
cd paypercrawl-backend
```

### 2. Install dependencies
```bash
npm install
```

### 3. Set up environment variables
```bash
cp .env.example .env
# Edit .env with your configuration
```

### 4. Set up the database
```bash
# Run Prisma migrations
npm run db:generate
npm run db:migrate

# Seed initial data (optional)
npm run db:seed
```

### 5. Start the development server
```bash
npm run dev
```

## 🚀 Deployment

### Using Docker
```bash
# Build the image
docker build -t paypercrawl-api .

# Run with docker-compose
docker-compose up -d
```

### Using PM2
```bash
# Build the project
npm run build

# Start with PM2
pm2 start dist/server.js --name paypercrawl-api
pm2 save
pm2 startup
```

### Deploy to Vercel/Railway/Render
```bash
# Install CLI
npm i -g @railway/cli

# Deploy
railway up
```

## 🔌 WordPress Plugin Integration

### 1. Install the WordPress Plugin
```bash
# Copy plugin to WordPress
cp -r paypercrawl-wp-integration /path/to/wordpress/wp-content/plugins/

# Or download from WordPress admin
```

### 2. Configure the Plugin
1. Activate the plugin in WordPress admin
2. Go to PayPerCrawl → Settings
3. Enter your API key from the dashboard
4. Configure detection settings

### 3. Verify Site Connection
```php
// The plugin will automatically verify via
GET https://your-site.com/wp-json/paypercrawl/v1/verify
```

### 4. Testing Detection
```bash
# Test with curl (should be detected as bot)
curl -X GET https://your-wordpress-site.com

# Test with proper headers (should pass)
curl -X GET https://your-wordpress-site.com \
  -H "User-Agent: Mozilla/5.0..." \
  -H "Accept: text/html,application/xhtml+xml..." \
  -H "Accept-Language: en-US,en;q=0.9"
```

## 📊 API Documentation

### Swagger/OpenAPI
Access the interactive API documentation:
- Development: http://localhost:3000/api-docs
- Production: https://api.paypercrawl.tech/api-docs

### Postman Collection
Import the Postman collection:
```bash
npm run postman
# Find collection at docs/paypercrawl.postman_collection.json
```

### Key Endpoints

#### Authentication
```bash
# Register (Beta - requires invite)
POST /api/v1/auth/register
{
  "email": "user@example.com",
  "password": "SecurePass123!",
  "firstName": "John",
  "lastName": "Doe",
  "inviteCode": "BETA2024"
}

# Login
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "SecurePass123!"
}
```

#### Bot Detection (WordPress Plugin)
```bash
# Log detection
POST /api/v1/detections/log
Headers:
  X-API-Key: your-api-key
  X-Signature: hmac-signature
Body:
{
  "ip": "192.168.1.1",
  "userAgent": "Mozilla/5.0...",
  "method": "GET",
  "path": "/page",
  "headers": {...}
}
```

## 🔧 Configuration

### Environment Variables
```env
# Required
DATABASE_URL=postgresql://...
JWT_ACCESS_SECRET=...
JWT_REFRESH_SECRET=...
API_KEY_SALT=...
HMAC_SECRET=...

# Optional but recommended
REDIS_URL=redis://localhost:6379
STRIPE_SECRET_KEY=sk_live_...
MAXMIND_LICENSE_KEY=...
IPINFO_TOKEN=...
```

### Rate Limiting
Configure in `.env`:
```env
RATE_LIMIT_WINDOW=15  # minutes
RATE_LIMIT_MAX=100    # requests per window
```

### CORS Origins
```env
CORS_ORIGIN=https://paypercrawl.tech,https://app.paypercrawl.tech
```

## 🧪 Testing

### Run tests
```bash
# Unit tests
npm test

# Integration tests
npm run test:integration

# E2E tests
npm run test:e2e
```

### Test bot detection
```bash
# Test with known bot user agent
curl -X POST http://localhost:3000/api/v1/detections/log \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "ip": "1.1.1.1",
    "userAgent": "GPTBot/1.0",
    "method": "GET",
    "path": "/",
    "headers": {}
  }'
```

## 📈 Monitoring

### Health Check
```bash
GET /health
```

### Metrics
- Request count by endpoint
- Bot detection accuracy
- Response times
- Error rates

### Logging
Logs are written to:
- Console (development)
- File: `logs/app.log` (production)
- Sentry (if configured)

## 🔐 Security Best Practices

1. **Always use HTTPS** in production
2. **Rotate API keys** regularly
3. **Enable rate limiting** on all endpoints
4. **Whitelist IPs** for admin endpoints
5. **Use strong JWT secrets** (min 32 characters)
6. **Enable 2FA** for admin accounts
7. **Regular security audits** with `npm audit`

## 🤝 WordPress Plugin Auto-Setup

### Easy Setup Script
```bash
# Run from WordPress root
curl -sSL https://paypercrawl.tech/setup.sh | bash
```

### Manual Setup
1. Download plugin from dashboard
2. Upload to WordPress
3. Activate and enter API key
4. Plugin auto-configures via API

## 📝 Database Migrations

### Create new migration
```bash
npx prisma migrate dev --name your_migration_name
```

### Deploy migrations
```bash
npm run db:deploy
```

### Reset database (dev only)
```bash
npx prisma migrate reset
```

## 🚨 Troubleshooting

### Common Issues

#### Redis connection failed
```bash
# Check Redis is running
redis-cli ping

# Start Redis
redis-server
```

#### Database connection error
```bash
# Test connection
npx prisma db pull

# Check DATABASE_URL format
postgresql://user:password@localhost:5432/dbname
```

#### API key not working
- Ensure API key starts with `ppc_`
- Check key hasn't expired
- Verify site domain is whitelisted

## 📚 Additional Resources

- [API Documentation](https://api.paypercrawl.tech/docs)
- [WordPress Plugin Guide](https://docs.paypercrawl.tech/wordpress)
- [Bot Detection Whitepaper](https://paypercrawl.tech/whitepaper)
- [Support Portal](https://support.paypercrawl.tech)

## 📄 License

Copyright © 2024 PayPerCrawl. All rights reserved.

## 🆘 Support

- Email: support@paypercrawl.tech
- Discord: [Join our server](https://discord.gg/paypercrawl)
- GitHub Issues: [Report bugs](https://github.com/paypercrawl/issues)
