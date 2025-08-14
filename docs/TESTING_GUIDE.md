# CrawlGuard WP - Comprehensive Testing Guide

## 🎯 **Testing Overview**

This guide provides step-by-step instructions for testing CrawlGuard WP from installation to production validation. Follow these procedures to ensure your plugin is working correctly.

## ⚡ **Quick Start Testing (5 Minutes)**

### **Step 1: Plugin Installation Test**
```bash
# 1. Upload plugin to WordPress
# Go to: WordPress Admin → Plugins → Add New → Upload Plugin
# Upload: crawlguard-wp.zip

# 2. Activate plugin
# Click "Activate Plugin"

# 3. Verify activation
# Check: Plugins → Installed Plugins
# Status should show "Active"
```

### **Step 2: Basic Functionality Test**
```bash
# 1. Access plugin settings
# Go to: WordPress Admin → CrawlGuard

# 2. Check API connection
# Look for green "Connected" status indicator
# API URL should show: https://api.creativeinteriorsstudio.com/v1

# 3. Generate API key
# Click "Generate New API Key"
# Copy the generated key for testing
```

### **Step 3: Bot Detection Test**
```bash
# Test with curl (replace YOUR_SITE_URL)
curl -H "User-Agent: GPTBot/1.0" https://YOUR_SITE_URL/

# Check WordPress admin dashboard
# Go to: CrawlGuard → Analytics
# Should show 1 bot detection with high confidence
```

## 🔧 **Detailed Installation Testing**

### **Prerequisites Verification**

#### **Server Requirements Check**
```php
// Add this to a test file to check requirements
<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "MySQL Version: " . $wpdb->db_version() . "\n";
echo "cURL Available: " . (function_exists('curl_init') ? 'Yes' : 'No') . "\n";
echo "JSON Available: " . (function_exists('json_encode') ? 'Yes' : 'No') . "\n";
?>
```

**Expected Results:**
- PHP Version: 7.4+ ✅
- WordPress Version: 5.0+ ✅
- MySQL Version: 5.6+ ✅
- cURL Available: Yes ✅
- JSON Available: Yes ✅

#### **WordPress Environment Check**
```bash
# Check WordPress debug mode
# In wp-config.php, verify:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# Check error logs after plugin activation
tail -f /path/to/wordpress/wp-content/debug.log
```

### **Plugin Installation Process**

#### **Method 1: WordPress Admin Upload**
```bash
1. Create plugin zip file:
   - Ensure all files are in crawlguard-wp/ folder
   - Zip the entire folder
   - File should be named: crawlguard-wp.zip

2. Upload via WordPress Admin:
   - Go to: Plugins → Add New → Upload Plugin
   - Choose file: crawlguard-wp.zip
   - Click "Install Now"
   - Click "Activate Plugin"

3. Verify installation:
   - Check: Plugins → Installed Plugins
   - Look for "CrawlGuard WP" with "Active" status
   - Check for any error messages
```

#### **Method 2: FTP Upload**
```bash
1. Extract plugin files
2. Upload crawlguard-wp/ folder to:
   /wp-content/plugins/crawlguard-wp/
3. Go to WordPress Admin → Plugins
4. Find "CrawlGuard WP" and click "Activate"
```

#### **Method 3: WP-CLI Installation**
```bash
# If you have WP-CLI installed
wp plugin install /path/to/crawlguard-wp.zip --activate
wp plugin status crawlguard-wp
```

### **Post-Installation Verification**

#### **Database Table Creation**
```sql
-- Check if plugin table was created
SHOW TABLES LIKE 'wp_crawlguard_logs';

-- Verify table structure
DESCRIBE wp_crawlguard_logs;

-- Expected columns:
-- id, timestamp, ip_address, user_agent, bot_detected, bot_type, action_taken, revenue_generated
```

#### **WordPress Options Check**
```php
// Check if plugin options were created
$options = get_option('crawlguard_options');
var_dump($options);

// Expected structure:
array(
    'api_url' => 'https://api.creativeinteriorsstudio.com/v1',
    'api_key' => '',
    'monetization_enabled' => false,
    'detection_sensitivity' => 'medium',
    'allowed_bots' => array('googlebot', 'bingbot'),
    'pricing_per_request' => 0.001
)
```

## 🤖 **Bot Detection Testing**

### **Manual Bot Simulation**

#### **Test 1: Known AI Bots**
```bash
# OpenAI GPTBot
curl -H "User-Agent: GPTBot/1.0" https://yoursite.com/
curl -H "User-Agent: ChatGPT-User/1.0" https://yoursite.com/

# Anthropic Claude
curl -H "User-Agent: Claude-Web/1.0" https://yoursite.com/
curl -H "User-Agent: anthropic-ai" https://yoursite.com/

# Google Bard
curl -H "User-Agent: Bard/1.0" https://yoursite.com/
curl -H "User-Agent: Google-Extended/1.0" https://yoursite.com/

# Common Crawl
curl -H "User-Agent: CCBot/2.0" https://yoursite.com/

# Perplexity
curl -H "User-Agent: PerplexityBot/1.0" https://yoursite.com/
```

**Expected Results:**
- Each request should be detected as a bot
- Confidence score should be 90%+
- Bot company should be correctly identified
- Requests should appear in WordPress admin dashboard

#### **Test 2: Browser User Agents (Should NOT be detected)**
```bash
# Chrome
curl -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36" https://yoursite.com/

# Firefox
curl -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0" https://yoursite.com/

# Safari
curl -H "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15" https://yoursite.com/
```

**Expected Results:**
- These should NOT be detected as bots
- Confidence score should be <50%
- Should not appear in bot detection logs

#### **Test 3: Suspicious Patterns**
```bash
# Python requests
curl -H "User-Agent: python-requests/2.25.1" https://yoursite.com/

# Scrapy framework
curl -H "User-Agent: Scrapy/2.5.0" https://yoursite.com/

# Selenium automation
curl -H "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/91.0.4472.124 Safari/537.36" https://yoursite.com/

# Generic bot
curl -H "User-Agent: MyBot/1.0" https://yoursite.com/
```

**Expected Results:**
- Should be detected with medium to high confidence
- Should be flagged as suspicious patterns

### **Browser-Based Testing**

#### **Developer Tools Method**
```javascript
// 1. Open your website in Chrome/Firefox
// 2. Open Developer Tools (F12)
// 3. Go to Network tab
// 4. Open Console and run:

// Change user agent to GPTBot
Object.defineProperty(navigator, 'userAgent', {
    get: function() { return 'GPTBot/1.0'; }
});

// Reload the page
location.reload();

// 5. Check CrawlGuard dashboard for detection
```

#### **Browser Extension Method**
```bash
# Install "User-Agent Switcher" extension
# Set user agent to: GPTBot/1.0
# Visit your website
# Check plugin dashboard for detection
```

### **API Endpoint Testing**

#### **Health Check Test**
```bash
curl https://api.creativeinteriorsstudio.com/v1/status

# Expected response:
{
  "status": "ok",
  "timestamp": 1699123456789,
  "version": "1.0.0",
  "environment": "production"
}
```

#### **Bot Detection API Test**
```bash
curl -X POST https://api.creativeinteriorsstudio.com/v1/detect \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "user_agent": "GPTBot/1.0",
    "ip_address": "192.168.1.1",
    "page_url": "https://yoursite.com/test-page",
    "content_type": "article"
  }'

# Expected response:
{
  "bot_detected": true,
  "bot_name": "GPTBot",
  "bot_company": "OpenAI",
  "confidence": 95,
  "action": "monetize",
  "pricing": {
    "amount": 0.002,
    "currency": "USD"
  }
}
```

#### **Analytics API Test**
```bash
curl -X GET https://api.creativeinteriorsstudio.com/v1/analytics \
  -H "X-API-Key: YOUR_API_KEY" \
  -G -d "timeframe=24h"

# Expected response:
{
  "total_requests": 150,
  "bot_requests": 25,
  "revenue_generated": 0.05,
  "top_bots": [
    {"name": "GPTBot", "count": 10},
    {"name": "Claude-Web", "count": 8}
  ]
}
```

## 📊 **Dashboard Testing**

### **WordPress Admin Dashboard**

#### **Main Dashboard Page**
```bash
# Navigate to: WordPress Admin → CrawlGuard
# Verify the following elements are present and functional:

1. API Connection Status
   - Should show green "Connected" indicator
   - API URL should be displayed correctly

2. Recent Bot Detections
   - Should show list of recent bot requests
   - Each entry should have: timestamp, bot name, confidence, action

3. Analytics Summary
   - Total requests today
   - Bot requests today
   - Revenue generated
   - Detection accuracy

4. Quick Actions
   - Generate API Key button
   - Test Connection button
   - View Full Analytics link
```

#### **Settings Page**
```bash
# Navigate to: CrawlGuard → Settings
# Test the following:

1. API Configuration
   - API URL field (should be pre-filled)
   - API Key field (should be empty initially)
   - Test Connection button functionality

2. Detection Settings
   - Sensitivity slider (Low/Medium/High)
   - Allowed bots list
   - Monetization toggle

3. Pricing Settings
   - Default pricing per request
   - Company-specific pricing rules

4. Save Settings
   - Click "Save Changes"
   - Should show success message
   - Settings should persist after page reload
```

#### **Analytics Page**
```bash
# Navigate to: CrawlGuard → Analytics
# Verify the following charts and data:

1. Bot Detection Chart
   - Shows bot detections over time
   - Interactive chart with hover details

2. Revenue Chart
   - Shows revenue generated over time
   - Displays cumulative and daily revenue

3. Top Bots Table
   - Lists most frequent bot visitors
   - Shows bot name, company, count, revenue

4. Export Functionality
   - Export to CSV button
   - Download should work correctly
```

## 🔒 **Security Testing**

### **Input Validation Testing**

#### **SQL Injection Tests**
```bash
# Test malicious user agents
curl -H "User-Agent: '; DROP TABLE wp_crawlguard_logs; --" https://yoursite.com/

# Test malicious IP addresses
curl -H "X-Forwarded-For: 127.0.0.1'; DROP TABLE wp_users; --" https://yoursite.com/

# Expected: No database errors, requests handled safely
```

#### **XSS Testing**
```bash
# Test script injection in user agent
curl -H "User-Agent: <script>alert('xss')</script>" https://yoursite.com/

# Check WordPress admin dashboard
# Verify: No script execution, data properly escaped
```

#### **API Security Testing**
```bash
# Test without API key
curl -X POST https://api.creativeinteriorsstudio.com/v1/detect \
  -H "Content-Type: application/json" \
  -d '{"user_agent": "test"}'

# Expected: 401 Unauthorized

# Test with invalid API key
curl -X POST https://api.creativeinteriorsstudio.com/v1/detect \
  -H "Content-Type: application/json" \
  -H "X-API-Key: invalid_key" \
  -d '{"user_agent": "test"}'

# Expected: 401 Unauthorized

# Test rate limiting
for i in {1..200}; do
  curl -H "X-API-Key: YOUR_API_KEY" https://api.creativeinteriorsstudio.com/v1/status
done

# Expected: 429 Rate Limited after threshold
```

## ⚡ **Performance Testing**

### **Page Load Impact Test**

#### **Before/After Comparison**
```bash
# Test page load speed before plugin activation
# Use GTmetrix, PageSpeed Insights, or:
curl -w "@curl-format.txt" -o /dev/null -s https://yoursite.com/

# Activate plugin and test again
# Compare load times - should be <10ms difference
```

#### **Load Testing**
```bash
# Install Apache Bench (ab) or use online tools
# Test concurrent requests:
ab -n 100 -c 10 https://yoursite.com/

# Monitor:
# - Response times
# - Error rates
# - Server resource usage
```

### **Database Performance Test**

#### **Query Analysis**
```sql
-- Enable MySQL slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;

-- Generate bot traffic and check slow queries
-- Should not see excessive slow queries from plugin
```

#### **Table Size Monitoring**
```sql
-- Check table size growth
SELECT 
    table_name,
    round(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'your_database_name'
AND table_name = 'wp_crawlguard_logs';
```

## 🚨 **Troubleshooting Guide**

### **Common Issues & Solutions**

#### **Plugin Won't Activate**
```bash
# Check PHP error logs
tail -f /var/log/php_errors.log

# Common causes:
1. PHP version < 7.4
   Solution: Upgrade PHP

2. Missing required files
   Solution: Re-upload plugin files

3. Memory limit exceeded
   Solution: Increase PHP memory_limit

4. Plugin conflicts
   Solution: Deactivate other plugins temporarily
```

#### **API Connection Failed**
```bash
# Test API endpoint directly
curl https://api.creativeinteriorsstudio.com/v1/status

# Common causes:
1. DNS not propagated
   Solution: Wait 24-48 hours or use direct IP

2. SSL certificate issues
   Solution: Check Cloudflare SSL settings

3. Firewall blocking requests
   Solution: Whitelist API domain

4. Server cURL disabled
   Solution: Enable cURL in PHP
```

#### **Bot Detection Not Working**
```bash
# Check WordPress debug logs
tail -f wp-content/debug.log

# Common causes:
1. API key not set
   Solution: Generate and save API key

2. Caching plugin interference
   Solution: Exclude CrawlGuard from caching

3. CDN blocking API requests
   Solution: Whitelist API endpoints

4. Incorrect user agent testing
   Solution: Use exact bot signatures
```

#### **Dashboard Not Loading**
```bash
# Check browser console for errors
# Press F12 → Console tab

# Common causes:
1. JavaScript conflicts
   Solution: Check for plugin conflicts

2. Missing assets
   Solution: Re-upload plugin files

3. Permissions issues
   Solution: Check user capabilities

4. AJAX errors
   Solution: Check WordPress AJAX endpoints
```

## ✅ **Success Criteria Checklist**

### **Installation Success**
- [ ] Plugin activates without errors
- [ ] Database table created successfully
- [ ] WordPress options saved correctly
- [ ] Admin menu appears in WordPress
- [ ] No PHP errors in logs

### **Functionality Success**
- [ ] API connection test passes
- [ ] Known AI bots detected with 90%+ confidence
- [ ] Regular browsers not detected as bots
- [ ] Dashboard displays data correctly
- [ ] Settings save and persist

### **Performance Success**
- [ ] Page load impact <10ms
- [ ] API response time <200ms
- [ ] No database performance issues
- [ ] Memory usage reasonable
- [ ] No JavaScript errors

### **Security Success**
- [ ] Input validation working
- [ ] No SQL injection vulnerabilities
- [ ] XSS protection active
- [ ] API authentication required
- [ ] Rate limiting functional

---

## 🎉 **Testing Complete!**

If all tests pass, your CrawlGuard WP installation is ready for production use. You now have a fully functional AI content monetization platform!

### **Next Steps:**
1. Monitor bot detection accuracy
2. Track revenue generation
3. Optimize pricing strategies
4. Scale to handle increased traffic
5. Expand bot detection signatures

**Congratulations! You've successfully deployed and tested CrawlGuard WP! 🚀**
