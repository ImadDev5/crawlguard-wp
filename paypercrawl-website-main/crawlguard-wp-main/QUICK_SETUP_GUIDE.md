# 🚀 CrawlGuard WP - Quick Setup Guide

**Complete these tasks in order for fastest deployment**

---

## ✅ **COMPLETED**
- [x] Cloudflare account setup
- [x] Domain configuration (creativeinteriorsstudio.com)
- [x] AI bot settings (Do not block)

---

## 🎯 **PHASE 1: CORE INFRASTRUCTURE (30 minutes)**

### **Task 1: Database Setup (10 minutes)**

**Option A: Neon (Recommended - Easiest)**
1. Go to [neon.tech](https://neon.tech)
2. Sign up with Google/GitHub (fastest)
3. Create project: "CrawlGuard WP"
4. Copy connection string (looks like: `postgresql://user:pass@host/database`)
5. Save it - you'll need it in Task 3

**Option B: Supabase (Alternative)**
1. Go to [supabase.com](https://supabase.com)
2. Create new project: "CrawlGuard WP"
3. Go to Settings → Database
4. Copy connection string

### **Task 2: Install Dependencies (5 minutes)**
```bash
# In your project folder
cd C:\Users\ADMIN\OneDrive\Desktop\plugin

# Install Wrangler CLI
npm install -g wrangler

# Install project dependencies
npm install
```

### **Task 3: Deploy Cloudflare Worker (10 minutes)**
```bash
# Login to Cloudflare
wrangler login

# Set database URL secret
wrangler secret put DATABASE_URL --env production
# Paste your database connection string when prompted

# Deploy the worker
wrangler publish --env production
```

### **Task 4: Setup API Subdomain (5 minutes)**
1. Go to Cloudflare Dashboard
2. Select your domain: creativeinteriorsstudio.com
3. Go to DNS tab
4. Add CNAME record:
   - Name: `api`
   - Content: `crawlguard-api-prod.your-username.workers.dev` (from step 3 output)
   - Proxy: Enabled (orange cloud)

---

## 🎯 **PHASE 2: DATABASE SCHEMA (15 minutes)**

### **Task 5: Run Database Schema**

**If using Neon:**
1. Go to Neon dashboard
2. Click "SQL Editor"
3. Copy-paste the entire content from `database/schema.sql`
4. Click "Run"

**If using Supabase:**
1. Go to Supabase dashboard
2. Click "SQL Editor"
3. Copy-paste the entire content from `database/schema.sql`
4. Click "Run"

### **Task 6: Test API Connection**
```bash
# Test your API
curl https://api.creativeinteriorsstudio.com/v1/status

# Expected response:
# {"status":"ok","timestamp":...}
```

---

## 🎯 **PHASE 3: STRIPE SETUP (Can Do Later)**

### **Task 7: Create Stripe Account**
1. Go to [stripe.com](https://stripe.com)
2. Create business account
3. Complete business verification
4. Get API keys from Dashboard

### **Task 8: Configure Stripe Secrets**
```bash
# Set Stripe secrets
wrangler secret put STRIPE_SECRET_KEY --env production
wrangler secret put STRIPE_WEBHOOK_SECRET --env production

# Generate JWT secret
wrangler secret put JWT_SECRET --env production
# Use a random 32-character string
```

---

## 🎯 **PHASE 4: WORDPRESS PLUGIN TESTING**

### **Task 9: Install Plugin**
1. Zip your plugin folder
2. Upload to WordPress admin
3. Activate plugin
4. Go to CrawlGuard settings

### **Task 10: Test Bot Detection**
1. Visit your site with different user agents
2. Check CrawlGuard dashboard
3. Verify bot detection is working

---

## 🚨 **PRIORITY ORDER**

### **CRITICAL (Do First):**
1. ✅ Database Setup (Task 1)
2. ✅ Deploy Worker (Tasks 2-3)
3. ✅ Setup API Subdomain (Task 4)
4. ✅ Run Database Schema (Task 5)

### **IMPORTANT (Do Next):**
5. ✅ Test API (Task 6)
6. ✅ Install WordPress Plugin (Task 9)
7. ✅ Test Bot Detection (Task 10)

### **CAN DEFER:**
8. ⏳ Stripe Account (Task 7) - Only needed for monetization
9. ⏳ Stripe Integration (Task 8) - Can do after testing

---

## 🎯 **SUCCESS CHECKPOINTS**

### **Checkpoint 1: API Working**
- ✅ `curl https://api.creativeinteriorsstudio.com/v1/status` returns success
- ✅ Database connection established
- ✅ No errors in Cloudflare Workers logs

### **Checkpoint 2: Plugin Working**
- ✅ WordPress plugin activated
- ✅ Settings page loads
- ✅ Bot detection logs appear

### **Checkpoint 3: Full System**
- ✅ Bots detected and logged
- ✅ Analytics dashboard shows data
- ✅ Ready for monetization

---

## 🚀 **ESTIMATED TIMELINE**

- **Phase 1**: 30 minutes (Core infrastructure)
- **Phase 2**: 15 minutes (Database schema)
- **Phase 3**: 30 minutes (Stripe - can defer)
- **Phase 4**: 15 minutes (WordPress testing)

**Total: 1.5 hours to basic functionality**
**Full setup: 2 hours including Stripe**

---

## 🆘 **IF YOU GET STUCK**

### **Common Issues:**
1. **Worker deployment fails**: Check wrangler login
2. **Database connection fails**: Verify connection string
3. **API subdomain not working**: Check DNS propagation (wait 5-15 minutes)
4. **Plugin errors**: Check WordPress error logs

### **Quick Fixes:**
- **Restart**: `wrangler logout` then `wrangler login`
- **Check logs**: `wrangler tail --env production`
- **Test locally**: `wrangler dev --env development`

---

## 🎯 **NEXT: START WITH TASK 1**

**Ready to begin? Start with the database setup!**

Choose Neon or Supabase and get that connection string. That's your first milestone! 🚀
