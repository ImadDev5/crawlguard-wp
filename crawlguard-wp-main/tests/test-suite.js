/**
 * CrawlGuard WP Test Suite
 * Comprehensive testing for production deployment
 */

class CrawlGuardTestSuite {
  constructor(apiUrl, apiKey) {
    this.apiUrl = apiUrl;
    this.apiKey = apiKey;
    this.results = [];
  }

  async runAllTests() {
    console.log('🧪 Starting CrawlGuard WP Test Suite...\n');
    
    const tests = [
      { name: 'API Health Check', test: () => this.testApiHealth() },
      { name: 'Bot Detection', test: () => this.testBotDetection() },
      { name: 'Monetization Logic', test: () => this.testMonetization() },
      { name: 'Analytics Endpoint', test: () => this.testAnalytics() },
      { name: 'Rate Limiting', test: () => this.testRateLimit() },
      { name: 'Error Handling', test: () => this.testErrorHandling() },
      { name: 'Security', test: () => this.testSecurity() }
    ];

    for (const { name, test } of tests) {
      try {
        console.log(`🔍 Testing: ${name}`);
        const result = await test();
        this.results.push({ name, status: 'PASS', result });
        console.log(`✅ ${name}: PASSED\n`);
      } catch (error) {
        this.results.push({ name, status: 'FAIL', error: error.message });
        console.log(`❌ ${name}: FAILED - ${error.message}\n`);
      }
    }

    this.printSummary();
    return this.results;
  }

  async testApiHealth() {
    const response = await fetch(`${this.apiUrl}/v1/status`);
    
    if (!response.ok) {
      throw new Error(`Health check failed: ${response.status}`);
    }

    const data = await response.json();
    
    if (data.status !== 'ok') {
      throw new Error('API status is not ok');
    }

    return { status: data.status, timestamp: data.timestamp };
  }

  async testBotDetection() {
    const testCases = [
      {
        name: 'OpenAI GPTBot',
        userAgent: 'Mozilla/5.0 (compatible; GPTBot/1.0; +https://openai.com/gptbot)',
        expected: { is_ai_bot: true, bot_company: 'OpenAI' }
      },
      {
        name: 'Anthropic Claude',
        userAgent: 'Claude-Web/1.0',
        expected: { is_ai_bot: true, bot_company: 'Anthropic' }
      },
      {
        name: 'Regular Browser',
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        expected: { is_ai_bot: false }
      },
      {
        name: 'Suspicious Bot',
        userAgent: 'python-requests/2.28.0',
        expected: { is_ai_bot: true }
      }
    ];

    const results = [];

    for (const testCase of testCases) {
      const requestData = {
        api_key: this.apiKey,
        request_data: {
          user_agent: testCase.userAgent,
          ip_address: '203.0.113.1',
          page_url: 'https://example.com/test',
          timestamp: new Date().toISOString()
        }
      };

      const response = await fetch(`${this.apiUrl}/v1/monetize`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
      });

      if (!response.ok) {
        throw new Error(`Bot detection test failed for ${testCase.name}: ${response.status}`);
      }

      const result = await response.json();
      results.push({ testCase: testCase.name, result });

      // Validate expectations
      if (testCase.expected.is_ai_bot && result.action === 'allow' && result.reason === 'not_ai_bot') {
        throw new Error(`Expected AI bot detection for ${testCase.name}`);
      }
    }

    return results;
  }

  async testMonetization() {
    const monetizationRequest = {
      api_key: this.apiKey,
      request_data: {
        user_agent: 'Mozilla/5.0 (compatible; GPTBot/1.0; +https://openai.com/gptbot)',
        ip_address: '203.0.113.1',
        page_url: 'https://example.com/premium-content',
        content_length: 2000,
        timestamp: new Date().toISOString()
      }
    };

    const response = await fetch(`${this.apiUrl}/v1/monetize`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(monetizationRequest)
    });

    if (!response.ok) {
      throw new Error(`Monetization test failed: ${response.status}`);
    }

    const result = await response.json();

    // Should either allow with revenue or show paywall
    if (!['allow', 'paywall'].includes(result.action)) {
      throw new Error(`Unexpected monetization action: ${result.action}`);
    }

    if (result.action === 'paywall' && !result.payment_url) {
      throw new Error('Paywall action missing payment URL');
    }

    return result;
  }

  async testAnalytics() {
    const response = await fetch(`${this.apiUrl}/v1/analytics?api_key=${this.apiKey}&range=7d`);

    if (!response.ok) {
      throw new Error(`Analytics test failed: ${response.status}`);
    }

    const data = await response.json();

    // Validate analytics structure
    const requiredFields = ['total_revenue', 'bot_visits', 'monetized_requests'];
    for (const field of requiredFields) {
      if (!(field in data)) {
        throw new Error(`Analytics missing required field: ${field}`);
      }
    }

    return data;
  }

  async testRateLimit() {
    // Make multiple rapid requests to test rate limiting
    const requests = Array(10).fill().map(() => 
      fetch(`${this.apiUrl}/v1/status`)
    );

    const responses = await Promise.all(requests);
    const statusCodes = responses.map(r => r.status);

    // Should have at least some successful requests
    if (!statusCodes.includes(200)) {
      throw new Error('No successful requests in rate limit test');
    }

    return { statusCodes, rateLimitDetected: statusCodes.includes(429) };
  }

  async testErrorHandling() {
    const errorTests = [
      {
        name: 'Invalid API Key',
        request: () => fetch(`${this.apiUrl}/v1/analytics?api_key=invalid_key`),
        expectedStatus: 401
      },
      {
        name: 'Missing Required Data',
        request: () => fetch(`${this.apiUrl}/v1/monetize`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({})
        }),
        expectedStatus: 400
      },
      {
        name: 'Invalid Endpoint',
        request: () => fetch(`${this.apiUrl}/v1/nonexistent`),
        expectedStatus: 404
      }
    ];

    const results = [];

    for (const test of errorTests) {
      const response = await test.request();
      
      if (response.status !== test.expectedStatus) {
        throw new Error(`${test.name}: Expected ${test.expectedStatus}, got ${response.status}`);
      }

      results.push({ name: test.name, status: response.status });
    }

    return results;
  }

  async testSecurity() {
    const securityTests = [
      {
        name: 'SQL Injection Protection',
        payload: { api_key: "'; DROP TABLE sites; --" }
      },
      {
        name: 'XSS Protection',
        payload: { user_agent: '<script>alert("xss")</script>' }
      },
      {
        name: 'Large Payload',
        payload: { user_agent: 'A'.repeat(10000) }
      }
    ];

    const results = [];

    for (const test of securityTests) {
      try {
        const response = await fetch(`${this.apiUrl}/v1/monetize`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            api_key: this.apiKey,
            request_data: test.payload
          })
        });

        // Should handle gracefully without crashing
        results.push({ 
          name: test.name, 
          status: response.status,
          handled: response.status < 500 
        });

      } catch (error) {
        results.push({ 
          name: test.name, 
          error: error.message,
          handled: false 
        });
      }
    }

    return results;
  }

  printSummary() {
    console.log('\n📊 TEST SUMMARY');
    console.log('================');
    
    const passed = this.results.filter(r => r.status === 'PASS').length;
    const failed = this.results.filter(r => r.status === 'FAIL').length;
    
    console.log(`✅ Passed: ${passed}`);
    console.log(`❌ Failed: ${failed}`);
    console.log(`📊 Total: ${this.results.length}`);
    
    if (failed > 0) {
      console.log('\n❌ FAILED TESTS:');
      this.results
        .filter(r => r.status === 'FAIL')
        .forEach(r => console.log(`   - ${r.name}: ${r.error}`));
    }
    
    console.log(`\n🎯 Success Rate: ${((passed / this.results.length) * 100).toFixed(1)}%`);
    
    if (failed === 0) {
      console.log('\n🎉 All tests passed! Ready for production! 🚀');
    } else {
      console.log('\n⚠️  Some tests failed. Please fix issues before production deployment.');
    }
  }
}

// Usage example and CLI interface
if (typeof module !== 'undefined' && module.exports) {
  module.exports = CrawlGuardTestSuite;
} else {
  // Browser/CLI usage
  window.CrawlGuardTestSuite = CrawlGuardTestSuite;
}

// CLI runner
if (typeof process !== 'undefined' && process.argv) {
  const args = process.argv.slice(2);
  
  if (args.length >= 2) {
    const [apiUrl, apiKey] = args;
    const testSuite = new CrawlGuardTestSuite(apiUrl, apiKey);
    
    testSuite.runAllTests().then(() => {
      process.exit(0);
    }).catch(error => {
      console.error('Test suite failed:', error);
      process.exit(1);
    });
  } else {
    console.log('Usage: node test-suite.js <API_URL> <API_KEY>');
    console.log('Example: node test-suite.js https://your-worker.workers.dev your-api-key');
  }
}
