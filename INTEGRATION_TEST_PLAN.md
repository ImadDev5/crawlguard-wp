# 🧪 **CrawlGuard WordPress Plugin Integration Test**

## **Test Plan for WordPress Plugin + API Integration**

### **Test Environment Setup**
- **API Endpoint**: `https://crawlguard-api-prod.crawlguard-api.workers.dev/v1`
- **WordPress Plugin**: CrawlGuard WP
- **Test Site**: `https://creativeinteriorsstudio.com`

---

## **Step 1: Register Test Site with API**

### **PowerShell Command:**
```powershell
$registration = Invoke-WebRequest -Uri "https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/register" -Method POST -Body '{"site_url":"https://creativeinteriorsstudio.com","email":"admin@creativeinteriorsstudio.com","site_name":"Creative Interiors Studio","plugin_version":"1.0.0","wordpress_version":"6.4"}' -ContentType "application/json" -UseBasicParsing

# Display registration result
$registration.Content | ConvertFrom-Json | ConvertTo-Json -Depth 3
```

### **Expected Response:**
```json
{
  "success": true,
  "site_id": "site_xxxxx",
  "api_key": "cg_prod_xxxxx",
  "site_url": "https://creativeinteriorsstudio.com",
  "email": "admin@creativeinteriorsstudio.com",
  "plan": "free",
  "features": {
    "bot_detection": true,
    "monetization": false,
    "analytics": true,
    "rate_limit": 1000
  }
}
```

---

## **Step 2: Test Bot Detection**

### **Test Various User Agents:**

```powershell
# Test 1: GPT Bot
$gptTest = Invoke-WebRequest -Uri "https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/detect" -Method POST -Body '{"user_agent":"GPTBot/1.0 (+https://openai.com/gptbot)"}' -ContentType "application/json" -UseBasicParsing

# Test 2: ChatGPT User
$chatgptTest = Invoke-WebRequest -Uri "https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/detect" -Method POST -Body '{"user_agent":"Mozilla/5.0 (compatible; ChatGPT-User/1.0; +https://openai.com/bot)"}' -ContentType "application/json" -UseBasicParsing

# Test 3: Claude Bot
$claudeTest = Invoke-WebRequest -Uri "https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/detect" -Method POST -Body '{"user_agent":"Claude-Web/1.0"}' -ContentType "application/json" -UseBasicParsing

# Test 4: Regular Browser
$browserTest = Invoke-WebRequest -Uri "https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/detect" -Method POST -Body '{"user_agent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"}' -ContentType "application/json" -UseBasicParsing

# Display results
Write-Host "GPT Bot Detection:" -ForegroundColor Yellow
$gptTest.Content | ConvertFrom-Json | ConvertTo-Json -Depth 3

Write-Host "ChatGPT Detection:" -ForegroundColor Yellow  
$chatgptTest.Content | ConvertFrom-Json | ConvertTo-Json -Depth 3

Write-Host "Claude Detection:" -ForegroundColor Yellow
$claudeTest.Content | ConvertFrom-Json | ConvertTo-Json -Depth 3

Write-Host "Browser Detection:" -ForegroundColor Green
$browserTest.Content | ConvertFrom-Json | ConvertTo-Json -Depth 3
```

---

## **Step 3: Test Monetization Flow**

### **Test with API Key from Registration:**

```powershell
# Use API key from Step 1 registration
$apiKey = "YOUR_API_KEY_FROM_REGISTRATION"

# Test monetization request
$monetizeTest = Invoke-WebRequest -Uri "https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/monetize" -Method POST -Body "{`"api_key`":`"$apiKey`",`"request_data`":{`"user_agent`":`"GPTBot/1.0`",`"ip_address`":`"192.168.1.100`",`"content_length`":2500,`"page_url`":`"https://creativeinteriorsstudio.com/test-page`",`"timestamp`":`"$(Get-Date -Format 'yyyy-MM-ddTHH:mm:ssZ')`"}}" -ContentType "application/json" -UseBasicParsing

Write-Host "Monetization Response:" -ForegroundColor Yellow
$monetizeTest.Content | ConvertFrom-Json | ConvertTo-Json -Depth 3
```

### **Expected Response for AI Bot:**
```json
{
  "action": "paywall",
  "amount": 0.0025,
  "currency": "USD", 
  "payment_url": "https://checkout.stripe.com/pay/cs_test_xxxxx",
  "payment_id": "pi_xxxxx",
  "expires_at": 1642680000000,
  "bot_detected": {
    "type": "ai_bot",
    "confidence": 95,
    "user_agent": "GPTBot/1.0"
  }
}
```

---

## **Step 4: Test Analytics**

```powershell
# Get analytics data
$analyticsTest = Invoke-WebRequest -Uri "https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/analytics?api_key=$apiKey&range=7d" -UseBasicParsing

Write-Host "Analytics Data:" -ForegroundColor Cyan
$analyticsTest.Content | ConvertFrom-Json | ConvertTo-Json -Depth 4
```

---

## **Step 5: WordPress Plugin Configuration**

### **Update Plugin Settings (Manual):**
1. **Go to WordPress Admin**: `/wp-admin`
2. **Navigate to**: CrawlGuard Settings  
3. **Configure**:
   - API URL: `https://crawlguard-api-prod.crawlguard-api.workers.dev/v1`
   - API Key: `[FROM STEP 1 REGISTRATION]`
   - Enable Monetization: `Yes`
   - Block Unknown Bots: `Yes`

### **Test Plugin Integration:**
```php
// Add to WordPress functions.php for testing
add_action('wp_head', function() {
    if (isset($_GET['test_crawlguard'])) {
        $crawlguard = new CrawlGuard_API_Client();
        $result = $crawlguard->detect_bot_request();
        wp_die('<pre>' . print_r($result, true) . '</pre>');
    }
});
```

---

## **Step 6: End-to-End Flow Test**

### **Simulate AI Bot Visit:**
```powershell
# Simulate bot request to WordPress site
$botRequest = Invoke-WebRequest -Uri "https://creativeinteriorsstudio.com/?test_crawlguard=1" -UserAgent "GPTBot/1.0 (+https://openai.com/gptbot)" -UseBasicParsing

Write-Host "WordPress Response:" -ForegroundColor Magenta
$botRequest.Content
```

---

## **Success Criteria**

### **✅ Registration Success:**
- API returns valid site_id and api_key
- Response time < 1 second

### **✅ Bot Detection Success:**  
- AI bots detected with >90% confidence
- Regular browsers allowed through
- Response time < 500ms

### **✅ Monetization Success:**
- AI bots trigger paywall
- Payment URL generated
- Proper pricing calculation

### **✅ WordPress Integration:**
- Plugin connects to API
- Bot requests intercepted  
- Proper response handling

---

## **Troubleshooting**

### **Common Issues:**
1. **API Key Invalid**: Re-register site
2. **CORS Errors**: Check plugin CORS headers
3. **Timeout**: Increase plugin timeout settings
4. **Wrong Response**: Verify API endpoint URL

### **Debug Commands:**
```powershell
# Check API status
Invoke-WebRequest -Uri "https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/status" -UseBasicParsing

# Check health  
Invoke-WebRequest -Uri "https://crawlguard-api-prod.crawlguard-api.workers.dev/v1/health" -UseBasicParsing
```

---

**Run these tests in sequence to validate complete integration!** 🚀
