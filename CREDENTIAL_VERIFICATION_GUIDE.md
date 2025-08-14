# 🔍 **CrawlGuard Credentials Verification Guide**

## 📍 **WHERE TO FIND YOUR CURRENT CREDENTIALS**

### **1. WordPress Plugin Credentials**

**Location 1: Setup Class (Main Configuration)**
```
File: c:\Users\ADMIN\OneDrive\Desktop\plugin\wp-admin\crawlguard-wp\includes\class-setup.php
Lines: 17-21
```

**Current values set:**
```php
'api_url' => 'https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/v1',
'api_key' => 'cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx',
'site_id' => 'site_oUSRqI213k8E',
```

**Location 2: API Client (Fallback Configuration)**
```
File: c:\Users\ADMIN\OneDrive\Desktop\plugin\wp-admin\crawlguard-wp\includes\class-api-client.php
Lines: 9, 16-17
```

---

## 🔍 **STEP-BY-STEP VERIFICATION PROCESS**

### **Step 1: Check WordPress Plugin Credentials**

1. **Open this file:**
   ```
   c:\Users\ADMIN\OneDrive\Desktop\plugin\wp-admin\crawlguard-wp\includes\class-setup.php
   ```

2. **Look for these lines (around line 17-21):**
   ```php
   'api_url' => 'https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/v1',
   'api_key' => 'cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx',
   'site_id' => 'site_oUSRqI213k8E',
   ```

3. **Verify these match what you expect**

### **Step 2: Check API Client Credentials**

1. **Open this file:**
   ```
   c:\Users\ADMIN\OneDrive\Desktop\plugin\wp-admin\crawlguard-wp\includes\class-api-client.php
   ```

2. **Look for these lines (around line 9 and 16-17):**
   ```php
   private $api_base_url = 'https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/v1';
   // ...
   $this->api_key = $options['api_key'] ?? 'cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx';
   $this->site_id = $options['site_id'] ?? 'site_oUSRqI213k8E';
   ```

### **Step 3: Check Cloudflare Worker Credentials**

1. **Go to your Cloudflare Dashboard:**
   - Login to: https://dash.cloudflare.com
   - Navigate to: Workers & Pages → Overview

2. **Look for a worker named something like:**
   - `production-worker-icy-cloud-8df3` 
   - Or similar with the domain `katiesdogwalking.workers.dev`

3. **Click on the worker and verify:**
   - It's deployed and running
   - The URL matches: `https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev`

### **Step 4: Test API Connection**

**Option A: Browser Test**
```
Open in browser: https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/v1/status
```
**Expected response:** JSON with status information

**Option B: PowerShell Test**
```powershell
Invoke-RestMethod -Uri "https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/v1/status" -Method GET
```

---

## 🔐 **CURRENT CREDENTIAL SUMMARY**

### **WordPress Plugin Configuration:**
- **API URL:** `https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/v1`
- **API Key:** `cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx`
- **Site ID:** `site_oUSRqI213k8E`

### **Cloudflare Worker:**
- **Domain:** `katiesdogwalking.workers.dev`
- **Subdomain:** `production-worker-icy-cloud-8df3`
- **Full URL:** `https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev`

---

## ✅ **VERIFICATION CHECKLIST**

### **Check these items:**

1. **[ ] WordPress Plugin Files**
   - [ ] class-setup.php has correct API URL
   - [ ] class-setup.php has correct API key
   - [ ] class-setup.php has correct site ID
   - [ ] class-api-client.php has matching credentials

2. **[ ] Cloudflare Worker**
   - [ ] Worker exists and is deployed
   - [ ] Worker responds to /v1/status endpoint
   - [ ] Worker URL matches plugin configuration

3. **[ ] API Connection**
   - [ ] Status endpoint returns valid JSON
   - [ ] No SSL/certificate errors
   - [ ] Response time is reasonable (< 5 seconds)

---

## 🚨 **WHAT TO DO IF CREDENTIALS DON'T MATCH**

### **If API URL is wrong:**
1. Update in `class-setup.php` line ~19
2. Update in `class-api-client.php` line ~9

### **If API Key is wrong:**
1. Update in `class-setup.php` line ~20
2. Update in `class-api-client.php` line ~16

### **If Site ID is wrong:**
1. Update in `class-setup.php` line ~21
2. Update in `class-api-client.php` line ~17

### **If Cloudflare Worker doesn't exist:**
1. Deploy the worker from: `c:\Users\ADMIN\OneDrive\Desktop\plugin\backend\production-worker.js`
2. Or create a new worker with the code

---

## 📞 **QUICK VERIFICATION COMMANDS**

**Test API Status:**
```powershell
# Run this in PowerShell to test the API
$response = Invoke-RestMethod -Uri "https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/v1/status" -Method GET -ErrorAction SilentlyContinue
if ($response) {
    Write-Host "✅ API is working!" -ForegroundColor Green
    $response | ConvertTo-Json
} else {
    Write-Host "❌ API is not responding" -ForegroundColor Red
}
```

**Check File Credentials:**
```powershell
# Check if files exist and show credential lines
Get-Content "c:\Users\ADMIN\OneDrive\Desktop\plugin\wp-admin\crawlguard-wp\includes\class-setup.php" | Select-String "api_url|api_key|site_id"
```

---

## 🎯 **NEXT STEPS AFTER VERIFICATION**

1. **If credentials are correct:** ✅ Create the clean plugin folder
2. **If credentials need updates:** ⚠️ Fix them first, then create folder  
3. **If API is not working:** 🔧 Deploy/fix the Cloudflare Worker first

**Let me know what you find and I'll help you proceed!**
