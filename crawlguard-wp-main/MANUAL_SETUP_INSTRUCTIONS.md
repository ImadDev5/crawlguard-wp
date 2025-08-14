# 🚀 CrawlGuard WP - Manual Setup Instructions

**Complete this checklist to deploy CrawlGuard WP to production. I've built everything that can be automated - these are the external services you need to set up manually.**

---

## 📋 **WEEK 1 & 2 MANUAL TASKS CHECKLIST**

### ✅ **Task 1: Create Cloudflare Account & Workers Setup**

**What you need to do:**
1. Go to [cloudflare.com](https://cloudflare.com) and create an account
2. Add a domain (you can use a free domain or buy one)
3. Enable Cloudflare Workers (you'll need the paid plan - $5/month)

**Step-by-step:**
```bash
# 1. Install Wrangler CLI on your computer
npm install -g wrangler

# 2. Login to Cloudflare
wrangler login
# (This will open a browser - click "Allow" to authorize)

# 3. Navigate to our project folder
cd C:\Users\ADMIN\OneDrive\Desktop\plugin

# 4. Deploy the worker
wrangler publish --env production
```

**Expected result:** You'll get a URL like `https://crawlguard-api-prod.your-subdomain.workers.dev`

---

### ✅ **Task 2: Set Up PostgreSQL Database**

**Recommended: Use Neon (easiest for beginners)**

1. Go to [neon.tech](https://neon.tech) and create a free account
2. Create a new project called "CrawlGuard"
3. Copy the connection string (looks like: `postgresql://user:pass@host/database`)

**Alternative options:**
- **Supabase**: [supabase.com](https://supabase.com) (free tier available)
- **PlanetScale**: [planetscale.com](https://planetscale.com) (MySQL-compatible)
- **AWS RDS**: More complex but very scalable

**Step-by-step for Neon:**
1. Sign up at neon.tech
2. Click "Create Project"
3. Name it "CrawlGuard Production"
4. Copy the connection string from the dashboard
5. Run our database schema:

```bash
# Connect to your database and run our schema
psql "your-connection-string-here" -f database/schema.sql
```

**Expected result:** Database with all tables created successfully

---

### ✅ **Task 3: Create Stripe Account**

**What you need to do:**
1. Go to [stripe.com](https://stripe.com) and create an account
2. Complete business verification (required for Connect)
3. Enable Stripe Connect
4. Get your API keys

**Step-by-step:**
1. Sign up at stripe.com
2. Go to Dashboard → Settings → Connect
3. Enable "Express accounts"
4. Go to Developers → API Keys
5. Copy your "Secret key" (starts with `sk_live_` or `sk_test_`)
6. Go to Developers → Webhooks
7. Add endpoint: `https://your-worker-url.workers.dev/v1/webhooks/stripe`
8. Select these events:
   - `payment_intent.succeeded`
   - `account.updated`
   - `payout.paid`
9. Copy the webhook signing secret

**Expected result:** Stripe account ready with Connect enabled

---

### ✅ **Task 4: Configure Environment Variables**

**What you need to do:**
Set up secrets in Cloudflare Workers using the connection details from above.

```bash
# Set your database connection string
wrangler secret put DATABASE_URL --env production
# Paste your PostgreSQL connection string when prompted

# Set your Stripe secret key
wrangler secret put STRIPE_SECRET_KEY --env production
# Paste your Stripe secret key when prompted

# Set your Stripe webhook secret
wrangler secret put STRIPE_WEBHOOK_SECRET --env production
# Paste your webhook signing secret when prompted

# Set a JWT secret (generate a random string)
wrangler secret put JWT_SECRET --env production
# Paste a random 32+ character string when prompted
```

**Generate JWT Secret:**
You can use this online tool: [allkeysgenerator.com](https://allkeysgenerator.com/Random/Security-Encryption-Key-Generator.aspx)
Or run this in your terminal:
```bash
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

---

### ✅ **Task 5: Test the Complete System**

**What you need to do:**
1. Test the API endpoint
2. Install the WordPress plugin
3. Configure the plugin
4. Test bot detection

**Step-by-step:**
```bash
# 1. Test API health
curl https://your-worker-url.workers.dev/v1/status

# Expected response:
# {"status":"ok","timestamp":1234567890,"version":"1.0.0"}
```

2. **Install WordPress Plugin:**
   - Copy the entire `plugin` folder to your WordPress site
   - Rename it to `crawlguard-wp`
   - Place in `/wp-content/plugins/crawlguard-wp/`
   - Activate in WordPress admin

3. **Configure Plugin:**
   - Go to WordPress Admin → CrawlGuard → Settings
   - Enter your API key (you'll get this from the registration process)
   - Enable monetization
   - Save settings

4. **Test Bot Detection:**
   - Visit your site with a bot-like user agent
   - Check the dashboard for detected bots

---

### ✅ **Task 6: Domain and SSL Setup**

**What you need to do:**
1. Point your domain to Cloudflare
2. Set up custom domain for the API
3. Enable SSL

**Step-by-step:**
1. In Cloudflare dashboard, go to Workers & Pages
2. Click on your worker
3. Go to Settings → Triggers
4. Add custom domain: `api.yourdomain.com`
5. Update the plugin's API URL to use your custom domain

---

## 🔧 **TROUBLESHOOTING GUIDE**

### **Problem: Worker deployment fails**
**Solution:**
```bash
# Check your wrangler.toml file
wrangler whoami
wrangler publish --dry-run --env production
```

### **Problem: Database connection fails**
**Solution:**
- Verify your connection string is correct
- Check if your database allows connections from Cloudflare IPs
- Test connection manually: `psql "your-connection-string"`

### **Problem: Stripe webhooks not working**
**Solution:**
- Verify webhook URL is correct
- Check webhook signing secret
- Test with Stripe CLI: `stripe listen --forward-to your-webhook-url`

### **Problem: WordPress plugin not detecting bots**
**Solution:**
- Check API key is set correctly
- Verify API endpoint is reachable
- Check WordPress error logs
- Enable WordPress debug mode

---

## 📊 **SUCCESS VERIFICATION**

**You'll know everything is working when:**

1. ✅ API health check returns `{"status":"ok"}`
2. ✅ WordPress plugin shows "Connected" status
3. ✅ Dashboard shows bot detection data
4. ✅ Test payment completes successfully
5. ✅ Webhook events appear in Stripe dashboard

---

## 🚀 **NEXT STEPS AFTER SETUP**

Once you complete these manual tasks:

1. **Test with real bot traffic**
2. **Submit plugin to WordPress.org**
3. **Start content marketing**
4. **Reach out to first AI companies**
5. **Monitor performance and optimize**

---

## 📞 **NEED HELP?**

**If you get stuck on any step:**

1. **Check the logs:**
   - Cloudflare Workers: Dashboard → Workers → Logs
   - WordPress: Enable WP_DEBUG in wp-config.php
   - Database: Check connection and query logs

2. **Common issues:**
   - CORS errors: Check domain configuration
   - 500 errors: Check environment variables
   - Database errors: Verify connection string
   - Stripe errors: Check API keys and webhook config

3. **Test each component:**
   - API: `curl https://your-api-url/v1/status`
   - Database: `psql "connection-string" -c "SELECT 1"`
   - Stripe: Test in Stripe dashboard
   - WordPress: Check plugin activation

---

## 💡 **ESTIMATED TIME TO COMPLETE**

- **Cloudflare setup**: 30 minutes
- **Database setup**: 45 minutes  
- **Stripe setup**: 60 minutes
- **Configuration**: 30 minutes
- **Testing**: 45 minutes

**Total: ~3.5 hours for complete setup**

---

**🎉 Once you complete these steps, CrawlGuard WP will be live and ready to monetize AI bot traffic!**

**Update me when you're done and I'll help you with the next phase: user acquisition and AI company outreach!**
