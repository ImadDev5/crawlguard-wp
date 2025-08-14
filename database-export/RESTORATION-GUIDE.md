# DATABASE RESTORATION GUIDE

## Your Complete Database Export

### 🔑 NEON DATABASE CREDENTIALS:
- Host: ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech
- Database: neondb
- User: neondb_owner
- Password: npg_nf1TKzFajLV2
- Connection: postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require

### 📋 TABLES IN YOUR DATABASE:
- ai_companies (11 columns)
- analytics_daily (10 columns)
- api_keys (10 columns)
- beta_applications (12 columns)
- bot_requests (16 columns)
- config_registry (10 columns)
- contact_submissions (8 columns)
- email_logs (7 columns)
- headers_config (9 columns)
- payments (14 columns)
- plugin_config (8 columns)
- sites (15 columns)
- system_config (8 columns)
- waitlist_entries (11 columns)
- webhooks (9 columns)

### ⚙️ SYSTEM CONFIGURATION:
- allowed_origins: ["https://paypercrawl.tech","https://creativeinteriorsstudio.com"]
- api_base_url: "https://paypercrawl.tech/api"
- api_version: "v1"
- bot_detection_config: {"enabled_types":["ChatGPT","Claude","Gemini"],"confidence_threshold":80}
- default_pricing: {"currency":"USD","per_request":0.001}
- payment_config: {"stripe_fee":0.029,"platform_fee":0.05}
- rate_limits: {"default":1000,"premium":5000,"enterprise":10000}
- webhook_retry_config: {"retry_delay":300,"max_attempts":3}

### 🏢 YOUR SITES:
- https://darkslategrey-grouse-900069.hostingersite.com (pro) - API Key: SET
- https://creativeinteriorsstudio.com (free) - API Key: SET
- https://blogging-website-s.netlify.app (free) - API Key: SET

### 🤖 AI COMPANIES:
- Anthropic: $0.001500/request
- Google AI: $0.001000/request
- Meta AI: $0.001000/request
- Microsoft AI: $0.001200/request
- OpenAI: $0.002000/request

## 🔄 TO RESTORE TO YOUR OLD SETUP:

### Option 1: Use pg_dump (Recommended)
```bash
# Full database dump
pg_dump "postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require" > complete_backup.sql

# Restore to your old database
psql -h your_old_host -U your_old_user -d your_old_database < complete_backup.sql
```

### Option 2: Use Essential Config
```bash
# Run the essential configuration
psql -h your_old_host -U your_old_user -d your_old_database < essential-config.sql
```

### Option 3: Manual Migration
1. Copy the connection string above
2. Connect to Neon database using any PostgreSQL client
3. Export specific tables you need
4. Import to your old setup

## 🎯 WHAT YOU HAVE IN NEON:
- ✅ Complete subscription tracking system
- ✅ Bot detection and monetization
- ✅ Payment processing setup
- ✅ Analytics and reporting
- ✅ Configuration management
- ✅ Headers and API setup
- ✅ Registry system
- ✅ All your website data (waitlist, emails, etc.)

Your Neon database is a COMPLETE, WORKING system with all functionality!
