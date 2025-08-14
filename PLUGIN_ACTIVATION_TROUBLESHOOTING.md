# CrawlGuard WordPress Plugin - Activation Troubleshooting Guide

## 🚨 PROBLEM IDENTIFIED AND FIXED

### Issue Found:
**Class Name Mismatch** - The plugin had inconsistent class names (`CrawlGuardWP` vs `CrawlGuard_WP`) which prevented proper activation.

### ✅ Fix Applied:
- Updated class reference from `CrawlGuardWP` to `CrawlGuard_WP`
- Fixed uninstall hook reference
- Created corrected plugin package

## 📦 Updated Files Available:

### 1. Fixed Plugin Package:
- **File**: `crawlguard-wp-fixed.zip`
- **Status**: Ready for installation with activation fix

### 2. Test Plugin:
- **File**: `crawlguard-test.php` 
- **Purpose**: Simple test version to verify WordPress compatibility

## 🔧 Troubleshooting Steps:

### Step 1: Try the Test Plugin First
1. Upload `crawlguard-test.php` to `/wp-content/plugins/`
2. Activate it in WordPress admin
3. Check if it shows success message and menu item
4. This will verify basic WordPress compatibility

### Step 2: Install Fixed Plugin
1. Delete the old CrawlGuard plugin from WordPress
2. Upload `crawlguard-wp-fixed.zip` 
3. Extract and activate
4. Should now activate without errors

### Step 3: Check for Common Issues

#### A. PHP Version:
- **Required**: PHP 7.4 or higher
- **Check**: Go to WordPress admin → Tools → Site Health

#### B. WordPress Version:
- **Required**: WordPress 5.0 or higher  
- **Check**: WordPress admin → Dashboard

#### C. File Permissions:
- Ensure plugins folder is writable
- Check with hosting provider if needed

#### D. Plugin Conflicts:
- Temporarily deactivate other plugins
- Try activating CrawlGuard alone

#### E. Memory Limits:
- WordPress needs adequate memory
- Contact hosting if you see memory errors

## 🩺 Diagnostic Information

### What the Test Plugin Will Show:
- ✅ Activation success message
- 📊 API connection status  
- 🔧 PHP/WordPress version info
- 🌐 API response from CrawlGuard servers

### Expected API Response:
```json
{
  "success": true,
  "status": "operational",
  "version": "1.0.0",
  "environment": "production"
}
```

## 🚀 Next Steps:

1. **Upload Test Plugin**: Try `crawlguard-test.php` first
2. **Check Results**: Look for success message and API response
3. **Install Fixed Version**: Use `crawlguard-wp-fixed.zip`
4. **Report Back**: Let me know what happens with activation

## 🔍 Additional Help:

If activation still fails, please provide:
- WordPress version
- PHP version  
- Any error messages
- Results from the test plugin

The main issue (class name mismatch) has been fixed, so the plugin should now activate properly! 🎯
