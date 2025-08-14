/**
 * Global Setup for Playwright E2E Tests
 * Runs once before all tests
 */

const { chromium } = require('@playwright/test');
const path = require('path');
const fs = require('fs').promises;

module.exports = async config => {
  // Create auth directory if it doesn't exist
  const authDir = path.join(__dirname, 'auth');
  await fs.mkdir(authDir, { recursive: true });
  
  // Launch browser for authentication
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  try {
    // Login to WordPress Admin (if needed)
    if (process.env.WP_ADMIN_USER && process.env.WP_ADMIN_PASS) {
      await page.goto(`${process.env.WORDPRESS_SITE_URL}/wp-login.php`);
      await page.fill('#user_login', process.env.WP_ADMIN_USER);
      await page.fill('#user_pass', process.env.WP_ADMIN_PASS);
      await page.click('#wp-submit');
      await page.waitForURL('**/wp-admin/**');
      
      // Save authentication state
      await page.context().storageState({ 
        path: path.join(authDir, 'admin-storage.json') 
      });
      
      console.log('✓ WordPress admin authentication saved');
    }
    
    // Setup test database (if needed)
    if (process.env.TEST_DB_SETUP === 'true') {
      console.log('Setting up test database...');
      // Add database setup logic here
    }
    
    // Verify API connectivity
    const apiResponse = await page.request.get(`${process.env.API_BASE_URL}/status`);
    if (apiResponse.ok()) {
      console.log('✓ API is accessible');
    } else {
      console.warn('⚠ API might not be accessible:', apiResponse.status());
    }
    
  } catch (error) {
    console.error('Error in global setup:', error);
    throw error;
  } finally {
    await browser.close();
  }
  
  // Set global test metadata
  process.env.TEST_RUN_ID = Date.now().toString();
  process.env.TEST_TIMESTAMP = new Date().toISOString();
  
  console.log('Global setup completed');
};
