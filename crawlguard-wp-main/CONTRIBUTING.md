# Contributing to CrawlGuard WP

Thank you for your interest in contributing to CrawlGuard WP! This document provides guidelines and information for contributors.

## 🎯 **How to Contribute**

### **Types of Contributions**

We welcome several types of contributions:

- **Bug Reports**: Help us identify and fix issues
- **Feature Requests**: Suggest new functionality
- **Code Contributions**: Submit bug fixes and new features
- **Documentation**: Improve our documentation
- **Testing**: Help test new features and releases
- **Security**: Report security vulnerabilities responsibly

## 🚀 **Getting Started**

### **Development Setup**

1. **Fork the Repository**
   ```bash
   git clone https://github.com/your-username/crawlguard-wp.git
   cd crawlguard-wp
   ```

2. **Install Dependencies**
   ```bash
   npm install
   ```

3. **Set Up Local Environment**
   ```bash
   # Copy environment template
   cp .env.example .env.local
   
   # Configure your local settings
   # - Database connection string
   # - API keys for testing
   # - Cloudflare Workers configuration
   ```

4. **Run Development Server**
   ```bash
   npm run dev
   ```

### **WordPress Development Environment**

1. **Local WordPress Setup**
   ```bash
   # Using Local by Flywheel, XAMPP, or Docker
   # Link plugin to WordPress installation
   ln -s /path/to/crawlguard-wp /path/to/wordpress/wp-content/plugins/
   ```

2. **Enable Debug Mode**
   ```php
   // Add to wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

## 📝 **Contribution Guidelines**

### **Code Standards**

#### **PHP (WordPress Plugin)**
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use WordPress hooks and filters appropriately
- Ensure compatibility with WordPress 5.0+
- Include proper sanitization and validation

```php
// Example: Proper WordPress coding style
function crawlguard_sanitize_settings($input) {
    $sanitized = array();
    
    if (isset($input['api_url'])) {
        $sanitized['api_url'] = esc_url_raw($input['api_url']);
    }
    
    if (isset($input['enable_logging'])) {
        $sanitized['enable_logging'] = (bool) $input['enable_logging'];
    }
    
    return $sanitized;
}
```

#### **JavaScript (Frontend & Backend)**
- Use ESLint configuration provided in the project
- Follow modern JavaScript (ES2022) standards
- Use async/await for asynchronous operations
- Include proper error handling

```javascript
// Example: Proper async function with error handling
async function detectBot(requestData) {
  try {
    const response = await fetch('/api/v1/detect', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey
      },
      body: JSON.stringify(requestData)
    });
    
    if (!response.ok) {
      throw new Error(`API request failed: ${response.status}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error('Bot detection failed:', error);
    throw error;
  }
}
```

#### **SQL (Database)**
- Use parameterized queries only
- Follow PostgreSQL best practices
- Include proper indexing for performance
- Document complex queries

```sql
-- Example: Proper parameterized query
SELECT 
  br.id,
  br.bot_detected,
  br.confidence_score,
  s.site_name
FROM bot_requests br
JOIN sites s ON br.site_id = s.id
WHERE s.api_key = $1
  AND br.created_at >= $2
ORDER BY br.created_at DESC
LIMIT $3;
```

### **Testing Requirements**

#### **Unit Tests**
```javascript
// Example: Jest unit test
describe('BotDetector', () => {
  test('should detect GPTBot with high confidence', () => {
    const userAgent = 'GPTBot/1.0';
    const result = BotDetector.analyze(userAgent);
    
    expect(result.botDetected).toBe(true);
    expect(result.botName).toBe('GPTBot');
    expect(result.confidence).toBeGreaterThan(90);
  });
});
```

#### **Integration Tests**
```javascript
// Example: API integration test
describe('API Endpoints', () => {
  test('POST /v1/detect should return bot detection result', async () => {
    const response = await request(app)
      .post('/v1/detect')
      .set('X-API-Key', testApiKey)
      .send({
        user_agent: 'GPTBot/1.0',
        ip_address: '192.168.1.1',
        page_url: 'https://example.com/test'
      });
    
    expect(response.status).toBe(200);
    expect(response.body.bot_detected).toBe(true);
  });
});
```

#### **WordPress Tests**
```php
// Example: WordPress unit test
class CrawlGuard_Test extends WP_UnitTestCase {
    public function test_plugin_activation() {
        $this->assertTrue(is_plugin_active('crawlguard-wp/crawlguard-wp.php'));
    }
    
    public function test_settings_sanitization() {
        $input = array(
            'api_url' => 'javascript:alert("xss")',
            'enable_logging' => '1'
        );
        
        $sanitized = crawlguard_sanitize_settings($input);
        
        $this->assertNotContains('javascript:', $sanitized['api_url']);
        $this->assertTrue($sanitized['enable_logging']);
    }
}
```

### **Documentation Standards**

- Use clear, concise language
- Include code examples for complex features
- Update relevant documentation with code changes
- Follow markdown formatting standards

## 🐛 **Bug Reports**

### **Before Submitting**

1. **Search existing issues** to avoid duplicates
2. **Test with latest version** to ensure bug still exists
3. **Gather system information**:
   - WordPress version
   - PHP version
   - Plugin version
   - Browser (for frontend issues)
   - Error messages and logs

### **Bug Report Template**

```markdown
## Bug Description
Brief description of the issue

## Steps to Reproduce
1. Go to...
2. Click on...
3. See error

## Expected Behavior
What should happen

## Actual Behavior
What actually happens

## Environment
- WordPress Version: 6.3.1
- PHP Version: 8.1.0
- Plugin Version: 1.0.0
- Browser: Chrome 118.0.0.0

## Additional Context
Any other relevant information
```

## 💡 **Feature Requests**

### **Feature Request Template**

```markdown
## Feature Description
Clear description of the proposed feature

## Use Case
Why is this feature needed? What problem does it solve?

## Proposed Solution
How should this feature work?

## Alternatives Considered
Other approaches you've considered

## Additional Context
Any other relevant information
```

## 🔒 **Security Vulnerabilities**

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, email us at: security@crawlguard.com

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

We will respond within 24 hours and work with you to address the issue.

## 📋 **Pull Request Process**

### **Before Submitting**

1. **Create an issue** to discuss the change (for significant features)
2. **Fork the repository** and create a feature branch
3. **Write tests** for your changes
4. **Update documentation** as needed
5. **Run the test suite** to ensure nothing breaks

### **Pull Request Template**

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] Tests added/updated
```

### **Review Process**

1. **Automated checks** must pass (tests, linting, security scans)
2. **Code review** by maintainers
3. **Testing** in development environment
4. **Approval** and merge by maintainers

## 🏷️ **Coding Conventions**

### **Naming Conventions**

- **PHP Functions**: `crawlguard_function_name()`
- **PHP Classes**: `CrawlGuard_Class_Name`
- **JavaScript Functions**: `camelCase`
- **JavaScript Classes**: `PascalCase`
- **Database Tables**: `snake_case`
- **CSS Classes**: `kebab-case`

### **File Organization**

```
crawlguard-wp/
├── includes/           # PHP classes and functions
├── assets/            # CSS, JS, images
├── backend/           # Cloudflare Workers code
├── database/          # SQL schema and migrations
├── tests/             # Test files
├── docs/              # Documentation
└── dist/              # Built assets
```

### **Commit Messages**

Use conventional commit format:

```
type(scope): description

feat(api): add bot detection endpoint
fix(plugin): resolve settings page error
docs(readme): update installation instructions
test(unit): add bot detector tests
```

## 🎉 **Recognition**

Contributors will be recognized in:
- CHANGELOG.md for each release
- Contributors section in README.md
- Annual contributor appreciation post

## 📞 **Getting Help**

- **GitHub Discussions**: For questions and community support
- **Discord**: Join our developer community
- **Email**: developers@crawlguard.com for direct support

## 📄 **License**

By contributing to CrawlGuard WP, you agree that your contributions will be licensed under the GPL v2 or later license.

---

**Thank you for contributing to CrawlGuard WP! Together, we're building the future of AI content monetization.**
