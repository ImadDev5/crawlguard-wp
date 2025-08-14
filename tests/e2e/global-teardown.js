/**
 * Global Teardown for Playwright E2E Tests
 * Runs once after all tests
 */

const fs = require('fs').promises;
const path = require('path');

module.exports = async () => {
  console.log('Running global teardown...');
  
  try {
    // Clean up test data if needed
    if (process.env.CLEANUP_TEST_DATA === 'true') {
      console.log('Cleaning up test data...');
      // Add cleanup logic here
    }
    
    // Generate test summary
    const testSummary = {
      runId: process.env.TEST_RUN_ID,
      timestamp: process.env.TEST_TIMESTAMP,
      endTime: new Date().toISOString(),
      environment: process.env.ENVIRONMENT || 'test',
      apiUrl: process.env.API_BASE_URL,
      siteUrl: process.env.TEST_SITE_URL,
    };
    
    // Save test summary
    const summaryPath = path.join(__dirname, '../logs', `test-summary-${process.env.TEST_RUN_ID}.json`);
    await fs.writeFile(summaryPath, JSON.stringify(testSummary, null, 2));
    console.log(`Test summary saved to: ${summaryPath}`);
    
    // Clean up temporary files
    const tempDir = path.join(__dirname, '../temp');
    try {
      await fs.rmdir(tempDir, { recursive: true });
      console.log('Temporary files cleaned up');
    } catch (error) {
      // Directory might not exist
    }
    
  } catch (error) {
    console.error('Error in global teardown:', error);
  }
  
  console.log('Global teardown completed');
};
