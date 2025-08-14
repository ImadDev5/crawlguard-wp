# 🚀 PayPerCrawl Hostinger Deployment Guide

## 🎯 **SMART CHOICE: Using Your Hostinger Premium Hosting**

**Why Hostinger is perfect for PayPerCrawl:**
- ✅ You already own it (cost savings until 2025-12-11)
- ✅ Full control over your startup infrastructure
- ✅ Professional setup for investors
- ✅ Node.js and Next.js support
- ✅ 5 websites capacity for future expansion

---

## 📋 **DEPLOYMENT PLAN (45 minutes total)**

### **Phase 1: GitHub Setup (10 minutes)**
1. Create GitHub repository
2. Push PayPerCrawl code
3. Verify repository structure

### **Phase 2: Hostinger Configuration (20 minutes)**
1. Access Hostinger control panel
2. Set up Node.js environment
3. Configure domain and SSL
4. Set up file manager access

### **Phase 3: Deployment (10 minutes)**
1. Upload and build application
2. Configure environment variables
3. Start the application

### **Phase 4: Testing (5 minutes)**
1. Test all functionality
2. Verify email service
3. Check admin dashboard

---

## 🔧 **STEP-BY-STEP DEPLOYMENT**

### **STEP 1: GitHub Repository Setup (10 minutes)**

#### 1.1 Create GitHub Repository
1. Go to [https://github.com](https://github.com)
2. Click "+" → "New repository"
3. **Repository name**: `paypercrawl-website`
4. **Description**: `PayPerCrawl - AI Content Monetization Platform`
5. Set to **Private**
6. **DO NOT** initialize with README
7. Click "Create repository"

#### 1.2 Push Your Code
In your terminal, run these commands (replace YOUR_USERNAME):

```bash
# Navigate to your website directory
cd C:\Users\ADMIN\OneDrive\Desktop\plugin\website

# Add GitHub remote
git remote add origin https://github.com/YOUR_USERNAME/paypercrawl-website.git

# Push to GitHub
git branch -M main
git push -u origin main
```

#### 1.3 Verify Upload
- Check that all files are visible on GitHub
- Ensure package.json, src/, and all components are there

---

### **STEP 2: Hostinger Control Panel Setup (20 minutes)**

#### 2.1 Access Hostinger Panel
1. Go to [https://hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Log in to your account
3. Select your hosting plan
4. Go to "Websites" section

#### 2.2 Domain Configuration
1. **Add Domain**: Click "Add Domain"
2. **Enter**: `paypercrawl.tech`
3. **DNS Settings**: Point to your hosting
4. **SSL Certificate**: Enable free SSL (should be automatic)

#### 2.3 Node.js Setup
1. **Find Node.js**: In control panel, look for "Node.js" or "Advanced" section
2. **Enable Node.js**: Turn on Node.js support
3. **Select Version**: Choose Node.js 18+ or latest available
4. **Set Document Root**: Usually `/public_html` or `/public_html/paypercrawl.tech`

#### 2.4 File Manager Access
1. **Open File Manager**: In control panel
2. **Navigate to**: Your domain folder (`/public_html/paypercrawl.tech/`)
3. **Clear folder**: Remove any default files

---

### **STEP 3: Application Deployment (10 minutes)**

#### 3.1 Upload Method Options

**Option A: Git Clone (Recommended)**
If Hostinger supports Git:
```bash
# In Hostinger terminal or SSH
git clone https://github.com/YOUR_USERNAME/paypercrawl-website.git
cd paypercrawl-website
npm install
npm run build
```

**Option B: File Upload**
If no Git support:
1. **Zip your project**: Create zip of website folder
2. **Upload via File Manager**: Upload and extract
3. **Install dependencies**: Use terminal to run `npm install`

#### 3.2 Environment Variables Setup
1. **Create .env file** in your project root:
```env
DATABASE_URL="postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
RESEND_API_KEY="re_XoKutthW_7c2446bUYzVSuf9hYLqvJmpd"
NEXT_PUBLIC_APP_URL="https://paypercrawl.tech"
ADMIN_API_KEY="paypercrawl_admin_2025_secure_key"
NODE_ENV="production"
```

#### 3.3 Build and Start
```bash
# Build the application
npm run build

# Start the application
npm start
```

#### 3.4 Configure Port and Process
- **Default Port**: Next.js runs on port 3000
- **Hostinger Port**: Check what port Hostinger expects
- **Process Manager**: Set up to keep app running

---

### **STEP 4: DNS Configuration (5 minutes)**

#### 4.1 Cloudflare DNS Update
1. **Go to Cloudflare**: [https://dash.cloudflare.com](https://dash.cloudflare.com)
2. **Select Domain**: paypercrawl.tech
3. **DNS Records**: Update A records to point to Hostinger IP
4. **Get Hostinger IP**: From your hosting control panel

#### 4.2 SSL Configuration
1. **Cloudflare SSL**: Set to "Flexible" or "Full"
2. **Hostinger SSL**: Ensure SSL is enabled
3. **Force HTTPS**: Enable redirect from HTTP to HTTPS

---

### **STEP 5: Testing and Validation (5 minutes)**

#### 5.1 Website Testing
1. **Visit**: https://paypercrawl.tech
2. **Check**: Homepage loads correctly
3. **Test Forms**: Try waitlist signup
4. **Admin Access**: Test /admin with your key

#### 5.2 Email Testing
1. **Submit Form**: Use waitlist form
2. **Check Email**: Verify email delivery
3. **Admin Functions**: Test sending invites

#### 5.3 Database Testing
1. **Form Submissions**: Verify data saves
2. **Admin Dashboard**: Check data displays
3. **API Endpoints**: Test all functionality

---

## 🔧 **HOSTINGER-SPECIFIC CONFIGURATIONS**

### **Node.js Configuration**
```javascript
// next.config.ts - Add if needed
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone', // For hosting compatibility
  trailingSlash: true,  // Some hosts prefer this
  images: {
    unoptimized: true   // If image optimization issues
  }
}

module.exports = nextConfig
```

### **Package.json Scripts**
```json
{
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start -p 3000",
    "lint": "next lint"
  }
}
```

### **Process Management**
- **PM2**: If available, use PM2 to keep app running
- **Forever**: Alternative process manager
- **Hostinger Tools**: Use built-in process management

---

## 🚨 **TROUBLESHOOTING**

### **Common Issues:**

1. **Port Issues**: 
   - Check what port Hostinger expects
   - Modify start script if needed

2. **Build Errors**:
   - Ensure Node.js version compatibility
   - Check for missing dependencies

3. **Database Connection**:
   - Verify Neon PostgreSQL connection
   - Check firewall settings

4. **Email Issues**:
   - Confirm Resend API key
   - Check domain verification

---

## 📞 **SUPPORT RESOURCES**

- **Hostinger Support**: 24/7 chat support
- **Documentation**: Hostinger knowledge base
- **Community**: Hostinger community forums

---

## 🎉 **SUCCESS CHECKLIST**

- [ ] GitHub repository created and code pushed
- [ ] Hostinger Node.js environment configured
- [ ] Domain paypercrawl.tech pointing to Hostinger
- [ ] SSL certificate active
- [ ] Application built and running
- [ ] Environment variables configured
- [ ] Database connection working
- [ ] Email service functional
- [ ] Admin dashboard accessible
- [ ] All forms working correctly

**Your PayPerCrawl website will be live on your own hosting! 🚀**
