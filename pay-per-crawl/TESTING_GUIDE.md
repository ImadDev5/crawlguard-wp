# PayPerCrawl WordPress Plugin - Testing Guide

## Pre-Installation Checklist

### System Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Admin access to WordPress dashboard

### Environment Setup
1. Ensure `.env` file is properly configured with:
   - `API_BASE_URL=https://api.creativeinteriorsstudio.com/v1`
   - `CLOUDFLARE_WORKER_URL=https://crawlguard-api-prod.crawlguard-api.workers.dev`
   - `DATABASE_URL=postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require`

## Installation Testing

### Step 1: Plugin Upload
1. Compress the `pay-per-crawl` folder into `pay-per-crawl.zip`
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the zip file
4. **Expected Result**: Plugin uploads without errors

### Step 2: Plugin Activation
1. Click "Activate Plugin"
2. **Expected Result**: 
   - No fatal errors or white screen
   - Plugin activates successfully
   - Database table `wp_paypercrawl_logs` is created
   - PayPerCrawl menu appears in WordPress admin sidebar

### Step 3: Admin Dashboard Access
1. Navigate to PayPerCrawl → Dashboard
2. **Expected Result**:
   - Dashboard loads without errors
   - Early access banner displays: "You keep 100% revenue"
   - Stats cards show (may be 0 initially)
   - Chart.js loads properly
   - No PHP errors in browser console

## Functionality Testing

### Bot Detection Testing

#### Test 1: User-Agent Detection
1. Use browser developer tools or curl to send requests with bot user agents:
   ```bash
   curl -H "User-Agent: GPTBot/1.0" https://yoursite.com/
   curl -H "User-Agent: ChatGPT-User/1.0" https://yoursite.com/
   curl -H "User-Agent: Claude-Web/1.0" https://yoursite.com/
   ```
2. **Expected Result**: Detections appear in PayPerCrawl → Analytics

#### Test 2: Database Logging
1. After bot detection tests, check PayPerCrawl → Analytics
2. **Expected Result**:
   - Recent detections table shows logged bots
   - Correct bot company names (OpenAI, Anthropic, etc.)
   - Confidence scores between 80-95%
   - IP addresses and timestamps recorded

### Admin Interface Testing

#### Test 3: Settings Page
1. Navigate to PayPerCrawl → Settings
2. Test API connection (if credentials configured)
3. Change bot action from "Allow" to "Block"
4. Save settings
5. **Expected Result**:
   - Settings save successfully
   - No PHP errors
   - Success message displays

#### Test 4: Analytics Page
1. Navigate to PayPerCrawl → Analytics
2. Test CSV export functionality
3. Check chart rendering
4. **Expected Result**:
   - Charts render using Chart.js
   - CSV export downloads properly
   - No JavaScript errors in console

## Error Testing

### Test 5: Missing Dependencies
1. Temporarily rename `includes/class-db.php` to test error handling
2. Visit dashboard
3. **Expected Result**: Graceful fallback, no fatal errors

### Test 6: Database Connection
1. Test with invalid database credentials
2. **Expected Result**: Plugin continues to function with fallback data

## Performance Testing

### Test 7: Page Load Impact
1. Test site speed before and after plugin activation
2. **Expected Result**: Minimal impact on page load times (<50ms)

### Test 8: Bot Detection Speed
1. Measure time for bot detection on each page load
2. **Expected Result**: Detection completes in <10ms

## Security Testing

### Test 9: AJAX Security
1. Test AJAX endpoints without proper nonces
2. **Expected Result**: Requests rejected with security errors

### Test 10: Capability Checks
1. Test admin pages with non-admin user
2. **Expected Result**: Access denied appropriately

## Browser Compatibility

### Test 11: Cross-Browser Testing
Test in:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

**Expected Result**: Admin interface works in all browsers

## Troubleshooting Common Issues

### Issue: Plugin Won't Activate
- Check PHP version (must be 7.4+)
- Check WordPress version (must be 5.0+)
- Review error logs for specific issues

### Issue: Dashboard Shows Errors
- Verify all class files exist in `includes/` folder
- Check file permissions
- Ensure database connection is working

### Issue: Bot Detection Not Working
- Verify user agent signatures in `class-detector.php`
- Check if requests are reaching the detection method
- Review database logging functionality

### Issue: Charts Not Loading
- Verify Chart.js is loading properly
- Check browser console for JavaScript errors
- Ensure AJAX endpoints are responding

## Success Criteria

✅ Plugin activates without fatal errors
✅ Database table created successfully
✅ Admin dashboard loads and displays properly
✅ Bot detection works for major AI bots (GPTBot, Claude, etc.)
✅ Analytics page shows detection data
✅ Settings can be saved and retrieved
✅ Charts render using Chart.js
✅ CSV export functionality works
✅ No security vulnerabilities
✅ Minimal performance impact

## Final Validation

Before marking as production-ready:
1. All tests pass
2. No PHP errors in logs
3. No JavaScript errors in console
4. Plugin follows WordPress coding standards
5. Database operations use prepared statements
6. All user inputs are properly sanitized
7. Capability checks are in place
8. Nonce verification works correctly

## Support

If any tests fail, check:
1. WordPress error logs
2. Browser developer console
3. Plugin file permissions
4. Database connectivity
5. PHP version compatibility

For additional support, contact the development team with specific error messages and test results.
