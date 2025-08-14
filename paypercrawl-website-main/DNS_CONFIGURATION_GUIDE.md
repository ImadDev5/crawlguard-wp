# 🌐 DNS Configuration Guide for PayPerCrawl

## 🎯 **Domain Setup: paypercrawl.tech → Hostinger**

### **Current Setup:**
- **Domain**: `paypercrawl.tech` 
- **DNS Provider**: Cloudflare
- **Zone ID**: `1e5c368316301faae33913263306b47f`
- **Account ID**: `eb2e0a0f169c14046bc5f6b9946ce4e2`
- **Target**: Hostinger Premium Hosting

---

## 📋 **DNS Configuration Steps**

### **Step 1: Get Hostinger Server IP (5 minutes)**

#### 1.1 Access Hostinger Control Panel
1. Go to [https://hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Login to your account
3. Select your hosting plan
4. Go to "Websites" section

#### 1.2 Find Your Server IP
1. **Look for**: "Server Information" or "Hosting Details"
2. **Find**: Your server's IP address (e.g., `185.xxx.xxx.xxx`)
3. **Note it down**: You'll need this for DNS configuration

**Alternative method:**
- In hosting control panel, look for "DNS Zone" or "DNS Management"
- Your server IP will be listed there

---

### **Step 2: Configure Cloudflare DNS (10 minutes)**

#### 2.1 Access Cloudflare Dashboard
1. Go to [https://dash.cloudflare.com](https://dash.cloudflare.com)
2. Login to your account
3. Select domain: `paypercrawl.tech`
4. Go to "DNS" tab

#### 2.2 Update A Records
**Add/Update these DNS records:**

```
Type: A
Name: @
Content: [YOUR_HOSTINGER_IP]
Proxy status: 🟠 Proxied (recommended)
TTL: Auto

Type: A  
Name: www
Content: [YOUR_HOSTINGER_IP]
Proxy status: 🟠 Proxied (recommended)
TTL: Auto
```

#### 2.3 Optional: Add CNAME for subdomains
```
Type: CNAME
Name: admin
Content: paypercrawl.tech
Proxy status: 🟠 Proxied
TTL: Auto
```

---

### **Step 3: SSL Configuration (5 minutes)**

#### 3.1 Cloudflare SSL Settings
1. **Go to**: SSL/TLS tab in Cloudflare
2. **Set Mode**: "Full (strict)" or "Full"
3. **Enable**: "Always Use HTTPS"
4. **Enable**: "Automatic HTTPS Rewrites"

#### 3.2 Hostinger SSL Settings
1. **In Hostinger Panel**: Look for "SSL Certificates"
2. **Enable**: Free SSL certificate for your domain
3. **Force HTTPS**: Enable redirect from HTTP to HTTPS

---

### **Step 4: Verification and Testing (5 minutes)**

#### 4.1 DNS Propagation Check
1. **Use tool**: [https://dnschecker.org](https://dnschecker.org)
2. **Enter**: `paypercrawl.tech`
3. **Check**: A records point to your Hostinger IP
4. **Wait**: Up to 24 hours for full propagation (usually 1-2 hours)

#### 4.2 SSL Certificate Check
1. **Use tool**: [https://www.ssllabs.com/ssltest/](https://www.ssllabs.com/ssltest/)
2. **Enter**: `paypercrawl.tech`
3. **Verify**: SSL certificate is valid and secure

---

## 🔧 **Advanced DNS Configuration**

### **Email Records (Optional)**
If you want professional email (admin@paypercrawl.tech):

```
Type: MX
Name: @
Content: mail.paypercrawl.tech
Priority: 10
TTL: Auto
```

### **Security Records (Recommended)**
```
Type: TXT
Name: @
Content: "v=spf1 include:_spf.hostinger.com ~all"
TTL: Auto

Type: TXT  
Name: _dmarc
Content: "v=DMARC1; p=quarantine; rua=mailto:admin@paypercrawl.tech"
TTL: Auto
```

---

## 🚨 **Troubleshooting Common Issues**

### **Issue 1: Site Not Loading**
**Symptoms**: Domain doesn't resolve or shows error
**Solutions**:
1. Check DNS propagation (use dnschecker.org)
2. Verify Hostinger IP address is correct
3. Wait 24 hours for full propagation
4. Clear browser cache and DNS cache

### **Issue 2: SSL Certificate Errors**
**Symptoms**: "Not Secure" warning or SSL errors
**Solutions**:
1. Check Cloudflare SSL mode (use "Full" not "Flexible")
2. Enable SSL in Hostinger control panel
3. Wait for certificate generation (up to 24 hours)
4. Force HTTPS redirect in both Cloudflare and Hostinger

### **Issue 3: Mixed Content Warnings**
**Symptoms**: Some resources load over HTTP
**Solutions**:
1. Enable "Automatic HTTPS Rewrites" in Cloudflare
2. Update your .env file: `NEXT_PUBLIC_APP_URL="https://paypercrawl.tech"`
3. Check all API calls use HTTPS

---

## 📊 **DNS Configuration Checklist**

- [ ] **Hostinger IP obtained** from control panel
- [ ] **A record for @** pointing to Hostinger IP
- [ ] **A record for www** pointing to Hostinger IP  
- [ ] **Cloudflare proxy enabled** (orange cloud)
- [ ] **SSL mode set** to "Full" or "Full (strict)"
- [ ] **Always Use HTTPS** enabled in Cloudflare
- [ ] **SSL certificate** enabled in Hostinger
- [ ] **DNS propagation** completed (check with dnschecker.org)
- [ ] **SSL certificate** valid (check with ssllabs.com)
- [ ] **Website loading** correctly at https://paypercrawl.tech
- [ ] **Admin dashboard** accessible at https://paypercrawl.tech/admin

---

## ⏱️ **Timeline Expectations**

- **DNS Changes**: 1-2 hours (up to 24 hours)
- **SSL Certificate**: 15 minutes to 24 hours
- **Full Propagation**: 24-48 hours globally
- **Cloudflare Cache**: Clear if needed

---

## 🎉 **Success Verification**

**Your DNS is correctly configured when:**
1. ✅ `https://paypercrawl.tech` loads your website
2. ✅ `https://www.paypercrawl.tech` redirects to main domain
3. ✅ SSL certificate shows as valid (green lock)
4. ✅ Admin dashboard works at `/admin`
5. ✅ All forms and APIs function correctly
6. ✅ Email service works (test with waitlist form)

---

## 📞 **Support Resources**

- **Cloudflare Support**: [https://support.cloudflare.com](https://support.cloudflare.com)
- **Hostinger Support**: 24/7 live chat in control panel
- **DNS Checker**: [https://dnschecker.org](https://dnschecker.org)
- **SSL Checker**: [https://www.ssllabs.com/ssltest/](https://www.ssllabs.com/ssltest/)

**Your PayPerCrawl domain will be live and secure! 🚀**
