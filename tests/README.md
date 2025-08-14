# CrawlGuard WP Testing Suite

## 📋 Overview

Comprehensive testing environment for CrawlGuard WP plugin, including unit tests, integration tests, E2E tests, and API testing.

## 🚀 Quick Start

### Prerequisites

1. **Node.js & npm** - Already installed ✅
2. **PHP 8.1+** - Already installed ✅
3. **Composer** - Need to install for PHP testing
4. **Playwright browsers** - Will be installed automatically

### Installation

```bash
# Install Composer (for PHP testing)
# Download from: https://getcomposer.org/download/
# Or use Chocolatey: choco install composer

# Install PHP dependencies (after Composer is installed)
composer install

# Install Playwright browsers
npm run playwright:install
```

## 🧪 Test Structure

```
tests/
├── unit/                   # Unit tests
│   ├── botDetection.test.js
│   └── ...
├── integration/            # Integration tests
│   └── ...
├── e2e/                   # End-to-end tests
│   ├── global-setup.js
│   ├── global-teardown.js
│   └── ...
├── api/                   # API tests
│   └── ...
├── database/              # Database tests
│   └── ...
├── wp-plugin/             # WordPress plugin specific tests
│   └── ...
├── coverage/              # Test coverage reports
├── logs/                  # Test logs
├── __mocks__/            # Mock files for Jest
├── jest.config.js        # Jest configuration
├── playwright.config.js  # Playwright configuration
├── phpunit.xml          # PHPUnit configuration
├── .env.test            # Test environment variables
└── run-tests.js         # Main test runner

```

## 🏃 Running Tests

### All Tests
```bash
# Run all test suites
npm test

# Or use the test runner directly
node tests/run-tests.js
```

### JavaScript Tests
```bash
# Unit tests only
npm run test:unit

# Integration tests only
npm run test:integration

# API tests
npm run test:api

# Watch mode for development
npm run test:watch

# With coverage report
npm run test:coverage
```

### E2E Tests
```bash
# Run E2E tests
npm run test:e2e

# Run in headed mode (see browser)
npm run test:e2e:headed

# Debug mode
npm run test:e2e:debug

# View test report
npm run playwright:report
```

### PHP Tests (requires Composer)
```bash
# All PHP tests
npm run test:php

# Unit tests only
npm run test:php:unit

# Integration tests only
npm run test:php:integration

# With coverage
npm run test:php:coverage
```

## ⚙️ Configuration

### Environment Variables

The test suite uses `.env.test` for configuration. Key variables:

- `API_BASE_URL` - API endpoint (uses production by default)
- `TEST_DB_*` - Test database configuration
- `WP_TEST_URL` - WordPress site URL for testing
- `TEST_BOT_USER_AGENTS` - Bot user agents for testing
- `FEATURE_*` - Feature flags

### Test Credentials

Using existing production credentials from `.env`:
- **API**: `https://api.paypercrawl.tech/v1`
- **Cloudflare Worker**: `https://crawlguard-api-prod.crawlguard-api.workers.dev`
- **Database**: Neon PostgreSQL (read-only for tests)

## 📊 Coverage Reports

Coverage reports are generated in multiple formats:

- **HTML**: `tests/coverage/jest/index.html` (JavaScript)
- **HTML**: `tests/coverage/phpunit/index.html` (PHP)
- **JSON**: `tests/coverage/test-report-*.json` (Combined)
- **Console**: Displayed after each test run

### Coverage Thresholds

- Statements: 80%
- Branches: 75%
- Functions: 80%
- Lines: 80%

## 🤖 Bot Detection Testing

Special test suite for AI bot detection:

```javascript
// Test different bot user agents
const testBots = [
  'GPTBot/1.0',
  'ChatGPT-User/1.0',
  'Claude-Web/1.0',
  'Bard/1.0'
];
```

Run bot detection tests:
```bash
npm run test:e2e -- --project=bot-detection
```

## 🔍 Debugging Tests

### JavaScript Tests
```bash
# Run with Node debugger
node --inspect-brk node_modules/.bin/jest --runInBand

# VSCode debugging - Add to launch.json:
{
  "type": "node",
  "request": "launch",
  "name": "Jest Debug",
  "program": "${workspaceFolder}/node_modules/.bin/jest",
  "args": ["--runInBand"],
  "console": "integratedTerminal"
}
```

### E2E Tests
```bash
# Debug mode with Playwright Inspector
npm run test:e2e:debug

# Generate trace for debugging
PWDEBUG=1 npm run test:e2e
```

## 📝 Writing Tests

### JavaScript Test Example
```javascript
describe('Feature Name', () => {
  test('should do something', () => {
    // Arrange
    const input = 'test';
    
    // Act
    const result = myFunction(input);
    
    // Assert
    expect(result).toBe('expected');
  });
});
```

### E2E Test Example
```javascript
const { test, expect } = require('@playwright/test');

test('user can detect bot', async ({ page }) => {
  await page.goto('/');
  await page.setExtraHTTPHeaders({
    'User-Agent': 'GPTBot/1.0'
  });
  
  const response = await page.request.get('/api/detect');
  expect(response.headers()['x-bot-detected']).toBe('true');
});
```

## 🚨 CI/CD Integration

For GitHub Actions:
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: windows-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
      - run: npm ci
      - run: npm test
```

## 🐛 Troubleshooting

### Common Issues

1. **Composer not found**
   - Install from: https://getcomposer.org/
   - Or use: `choco install composer`

2. **Playwright browsers not installed**
   - Run: `npm run playwright:install`

3. **Test database not accessible**
   - Check `.env.test` configuration
   - Ensure local database is running

4. **Permission errors**
   - Run terminal as Administrator
   - Check file permissions

## 📚 Additional Resources

- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Playwright Documentation](https://playwright.dev/docs/intro)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Testing Guide](https://make.wordpress.org/core/handbook/testing/)

## 🤝 Contributing

1. Write tests for new features
2. Ensure all tests pass before committing
3. Maintain > 80% code coverage
4. Update this documentation for new test patterns

## 📞 Support

For testing issues or questions:
- Email: admin@paypercrawl.tech
- Documentation: https://paypercrawl.tech/docs

---

**Note**: Some PHP testing features require Composer to be installed. JavaScript and E2E tests work without additional setup.
