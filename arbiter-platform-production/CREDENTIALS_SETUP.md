# 🔐 Arbiter Platform - Credentials & API Keys Setup Guide

This guide will walk you through obtaining all the necessary credentials and API keys for the Arbiter Platform.

## 🚀 Quick Setup Checklist

### 🚨 **CRITICAL - NEED NOW** (Platform won't start)
- [x] JWT secrets ✅ 
- [x] Database credentials ✅
- [x] Google OAuth ✅

### 🔶 **IMPORTANT - NEED SOON** (Core features)
- [ ] Stripe payment keys (5 min setup)
- [ ] SendGrid email API (5 min setup)
- [x] Redis credentials ✅

### 💡 **OPTIONAL - ADD LATER** (Advanced features)  
- [ ] GitHub OAuth (3 min)
- [ ] AWS S3 storage (10 min)
- [ ] Kafka credentials (15 min)
- [ ] Elasticsearch credentials (10 min)

**✅ Status: Ready to start! Run `npm run dev`**

---

## 📋 Step-by-Step Setup Instructions

### 1. 🗄️ Database Setup (PostgreSQL)

**Local Development:**
```bash
# Install PostgreSQL locally
# Windows: Download from https://www.postgresql.org/download/windows/
# Create database
createdb arbiter_platform
createuser arbiter_user --createdb --superuser
```

**Production (Recommended - Supabase):**
1. Go to https://supabase.com
2. Create new project
3. Copy the connection string from Settings → Database
4. Format: `postgresql://postgres:[password]@[host]:5432/postgres`

**Where to put it:**
```
DATABASE_URL="your_connection_string_here"
```

### 2. 🟥 Redis Setup

**Local Development:**
```bash
# Windows: Download from https://github.com/microsoftarchive/redis/releases
# Or use Docker: docker run -p 6379:6379 redis
```

**Production (Recommended - Redis Cloud):**
1. Go to https://redis.com/redis-enterprise-cloud/
2. Create free account
3. Create database
4. Copy connection details

**Where to put it:**
```
REDIS_URL="redis://username:password@host:port"
```

### 3. 🔑 JWT & Security Secrets

**Generate secure secrets:**
```bash
# Use online generator or Node.js
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

**Where to put it:**
```
JWT_SECRET="your_32_character_secret_here"
ENCRYPTION_KEY="your_32_character_encryption_key"
SESSION_SECRET="your_session_secret"
```

### 4. 🔵 Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project or select existing
3. Enable Google+ API
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client ID"
5. Set authorized redirect URIs:
   - `http://localhost:3000/auth/google/callback` (development)
   - `https://yourdomain.com/auth/google/callback` (production)

**Where to put it:**
```
GOOGLE_CLIENT_ID="123456789.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="your_google_secret"
```

### 5. 🐙 GitHub OAuth Setup

1. Go to [GitHub Developer Settings](https://github.com/settings/developers)
2. Click "New OAuth App"
3. Fill in:
   - Application name: "Arbiter Platform"
   - Homepage URL: `http://localhost:3000`
   - Authorization callback URL: `http://localhost:3000/auth/github/callback`

**Where to put it:**
```
GITHUB_CLIENT_ID="your_github_client_id"
GITHUB_CLIENT_SECRET="your_github_secret"
```

### 6. 💳 Stripe Payment Setup

1. Go to [Stripe Dashboard](https://dashboard.stripe.com/)
2. Create account or login
3. Go to "Developers" → "API keys"
4. Copy Publishable key and Secret key
5. For webhooks: "Developers" → "Webhooks" → "Add endpoint"
   - URL: `https://yourdomain.com/api/webhooks/stripe`
   - Events: `payment_intent.succeeded`, `customer.subscription.updated`

**Where to put it:**
```
STRIPE_SECRET_KEY="sk_test_..."
STRIPE_PUBLISHABLE_KEY="pk_test_..."
STRIPE_WEBHOOK_SECRET="whsec_..."
```

### 7. 📧 SendGrid Email Setup

1. Go to [SendGrid](https://sendgrid.com/)
2. Create account
3. Go to "Settings" → "API Keys"
4. Create new API key with "Full Access"

**Where to put it:**
```
SENDGRID_API_KEY="SG.your_api_key"
FROM_EMAIL="noreply@yourdomain.com"
```

### 8. ☁️ AWS S3 Storage Setup

1. Go to [AWS Console](https://aws.amazon.com/console/)
2. Create IAM user with S3 permissions
3. Create S3 bucket
4. Generate access keys

**Where to put it:**
```
AWS_ACCESS_KEY_ID="your_access_key"
AWS_SECRET_ACCESS_KEY="your_secret_key"
AWS_S3_BUCKET="your_bucket_name"
```

### 9. 🔄 Kafka Setup (Optional - for production)

**Development:** Use local Docker
```bash
docker run -p 9092:9092 confluentinc/cp-kafka
```

**Production:** Use Confluent Cloud or AWS MSK

**Where to put it:**
```
KAFKA_BROKERS="localhost:9092"
```

### 10. 🔍 Elasticsearch Setup (Optional)

**Development:** Use Docker
```bash
docker run -p 9200:9200 elasticsearch:8.8.0
```

**Production:** Use Elastic Cloud

**Where to put it:**
```
ELASTICSEARCH_URL="http://localhost:9200"
```

---

## 📁 File Locations

### Create your `.env` file:
1. Copy `.env.example` to `.env`
2. Fill in all the values above
3. Never commit `.env` to git!

### File structure:
```
arbiter-platform-production/
├── .env                    # ← Put your credentials here
├── .env.example           # ← Template file
├── credentials-setup.md   # ← This guide
└── apps/
    ├── rules-engine/
    ├── auth-service/
    └── ...
```

---

## 🔒 Security Best Practices

1. **Never commit credentials to git**
2. **Use different keys for development/production**
3. **Rotate keys regularly**
4. **Use environment-specific `.env` files**
5. **Store production secrets in secure vault (AWS Secrets Manager, etc.)**

---

## 🆘 Need Help?

If you need help getting any of these credentials:

1. **Database issues:** Check if PostgreSQL is running
2. **OAuth not working:** Verify redirect URIs match exactly
3. **Stripe webhooks failing:** Use ngrok for local development
4. **Email not sending:** Check SendGrid domain verification

**Test your setup:**
```bash
# Run the credential checker
npm run check-credentials
```

---

## 🎯 Priority Order (Start with these)

### 🚨 **CRITICAL - DONE!** ✅
1. **JWT_SECRET** ✅ - Generate immediately 
2. **DATABASE_URL** ✅ - Set up PostgreSQL/Supabase
3. **GOOGLE_CLIENT_ID/SECRET** ✅ - For user authentication

### 🔶 **IMPORTANT - GET THESE NEXT** (5 min each)
4. **STRIPE keys** - For payment processing
5. **SENDGRID_API_KEY** - For email notifications

### 💡 **OPTIONAL - ADD WHEN NEEDED**
6. **GitHub OAuth** - Alternative login method
7. **AWS S3** - Large file uploads  
8. **Kafka** - Event streaming
9. **Elasticsearch** - Advanced search

**🚀 You can start the platform NOW with current credentials!**
