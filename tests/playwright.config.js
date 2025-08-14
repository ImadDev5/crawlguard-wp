/**
 * Playwright Configuration for E2E Testing
 * End-to-end testing for website and WordPress plugin
 */

const { defineConfig, devices } = require('@playwright/test');
const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../.env') });

module.exports = defineConfig({
  // Test directory
  testDir: './e2e',
  
  // Test match pattern
  testMatch: '**/*.e2e.{js,ts}',
  
  // Timeout per test
  timeout: 30 * 1000,
  
  // Number of retries
  retries: process.env.CI ? 2 : 0,
  
  // Parallel execution
  workers: process.env.CI ? 1 : 4,
  
  // Reporter configuration
  reporter: [
    ['html', { outputFolder: 'coverage/playwright' }],
    ['json', { outputFile: 'coverage/playwright/results.json' }],
    ['list'],
  ],
  
  // Global setup and teardown
  globalSetup: './e2e/global-setup.js',
  globalTeardown: './e2e/global-teardown.js',
  
  // Shared settings for all projects
  use: {
    // Base URL from environment
    baseURL: process.env.TEST_SITE_URL || 'https://paypercrawl.tech',
    
    // Trace settings
    trace: 'on-first-retry',
    
    // Screenshot settings
    screenshot: 'only-on-failure',
    
    // Video settings
    video: 'retain-on-failure',
    
    // Viewport
    viewport: { width: 1280, height: 720 },
    
    // Headers with test credentials
    extraHTTPHeaders: {
      'X-Test-Mode': 'true',
      'X-API-Key': process.env.TEST_API_KEY || 'test_api_key_12345',
    },
  },
  
  // Projects for different browsers and scenarios
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'wordpress-admin',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: process.env.WORDPRESS_SITE_URL || 'https://paypercrawl.tech',
        storageState: './e2e/auth/admin-storage.json',
      },
    },
    {
      name: 'api-testing',
      use: {
        baseURL: process.env.API_BASE_URL || 'https://api.paypercrawl.tech/v1',
      },
    },
    {
      name: 'bot-detection',
      use: {
        ...devices['Desktop Chrome'],
        userAgent: 'GPTBot/1.0',
      },
    },
  ],
  
  // Output folder for test artifacts
  outputDir: './test-results',
  
  // Web server configuration for local testing
  webServer: process.env.CI ? undefined : {
    command: 'npm run dev',
    port: 3000,
    timeout: 120 * 1000,
    reuseExistingServer: !process.env.CI,
  },
});
