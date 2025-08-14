# CrawlGuard WordPress Plugin - Testing & Validation

## Complete Integration Test Plan

### Phase 1: API Connection Tests

Test our deployed CrawlGuard API with the WordPress plugin integration.

#### Test 1: API Health Check
```bash
# Test the deployed Cloudflare Worker API
curl -X GET "https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/health" \
  -H "Content-Type: application/json"

# Expected Response:
# {
#   "status": "healthy",
#   "timestamp": "2024-01-XX",
#   "version": "1.0.0"
# }
```

#### Test 2: Site Registration
```bash
# Test site registration with API key
curl -X POST "https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/register" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx" \
  -d '{
    "site_url": "https://test-wordpress-site.com",
    "site_name": "Test WordPress Site"
  }'

# Expected Response:
# {
#   "success": true,
#   "site_id": "site_XXXXXXX",
#   "api_key": "cg_prod_XXXXXXX"
# }
```

#### Test 3: Bot Detection API
```bash
# Test bot detection endpoint
curl -X POST "https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/detect" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx" \
  -d '{
    "site_id": "site_oUSRqI213k8E",
    "ip_address": "192.168.1.1",
    "user_agent": "GPTBot/1.0 (+https://openai.com/gptbot)",
    "request_url": "/test-page",
    "request_method": "GET"
  }'

# Expected Response:
# {
#   "is_bot": true,
#   "confidence": 0.95,
#   "bot_type": "ai_crawler",
#   "company": "OpenAI",
#   "action": "allow_with_charge"
# }
```

### Phase 2: WordPress Plugin Tests

#### Test 4: Plugin Activation
1. Upload plugin to WordPress site
2. Activate through admin panel
3. Verify no PHP errors in debug log
4. Check database table creation:
   ```sql
   SHOW TABLES LIKE 'wp_crawlguard_logs';
   SELECT COUNT(*) FROM wp_crawlguard_logs;
   ```

#### Test 5: Auto-Setup Verification
1. Go to WordPress Admin → Settings → CrawlGuard
2. Verify API settings are pre-populated:
   - API URL: `https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev`
   - API Key: `cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx`
   - Site ID: `site_oUSRqI213k8E`
3. Click "Test API Connection" button
4. Verify green "Connected" status

#### Test 6: Bot Detection Integration
```bash
# Test WordPress site with various user agents
curl -H "User-Agent: GPTBot/1.0" https://your-wordpress-site.com/
curl -H "User-Agent: Anthropic-AI" https://your-wordpress-site.com/
curl -H "User-Agent: Googlebot/2.1" https://your-wordpress-site.com/
curl -H "User-Agent: Mozilla/5.0 Chrome/91.0" https://your-wordpress-site.com/

# Check WordPress logs:
# Go to: CrawlGuard → Analytics
# Verify bot detections are logged with correct confidence scores
```

### Phase 3: Revenue Generation Tests

#### Test 7: Monetization API
```bash
# Test monetization endpoint
curl -X POST "https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/monetize" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx" \
  -d '{
    "site_id": "site_oUSRqI213k8E",
    "bot_type": "ai_crawler",
    "company": "OpenAI",
    "request_count": 1,
    "content_type": "article"
  }'

# Expected Response:
# {
#   "success": true,
#   "charge_amount": 0.00065,
#   "currency": "USD",
#   "transaction_id": "txn_XXXXXXX"
# }
```

#### Test 8: Analytics Integration
```bash
# Test analytics endpoint
curl -X GET "https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/analytics?site_id=site_oUSRqI213k8E" \
  -H "X-API-Key: cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx"

# Expected Response:
# {
#   "total_requests": 1000,
#   "bot_requests": 250,
#   "revenue_generated": 162.50,
#   "top_bots": [...]
# }
```

### Phase 4: Performance Tests

#### Test 9: Load Testing
```bash
# Test multiple concurrent requests
for i in {1..10}; do
  curl -H "User-Agent: GPTBot/1.0" https://your-wordpress-site.com/ &
done
wait

# Check response times and server load
# Verify no performance degradation
```

#### Test 10: Database Performance
```sql
-- Test query performance
EXPLAIN SELECT * FROM wp_crawlguard_logs 
WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Verify indexes are working
SHOW INDEX FROM wp_crawlguard_logs;
```

### Phase 5: Security Tests

#### Test 11: Authentication
```bash
# Test with invalid API key
curl -X POST "https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/detect" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: invalid_key" \
  -d '{"site_id": "test"}'

# Expected Response: 401 Unauthorized
```

#### Test 12: Input Validation
```bash
# Test with malicious input
curl -X POST "https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/detect" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: cg_prod_9c4j1kwQaabRvYu2owwh6fLyGffOty1zx" \
  -d '{
    "site_id": "<script>alert(1)</script>",
    "user_agent": "'; DROP TABLE users; --"
  }'

# Expected: Input sanitization and rejection
```

### Test Results Validation

#### Success Criteria
- ✅ All API endpoints return expected responses
- ✅ WordPress plugin activates without errors
- ✅ Bot detection accuracy > 95% for known bots
- ✅ Revenue calculations use 0.65x optimal multiplier
- ✅ Response times < 100ms for detection
- ✅ Database queries use proper indexes
- ✅ Security validations pass all tests

#### Expected Metrics
- **Bot Detection Accuracy**: 95%+
- **API Response Time**: < 100ms
- **WordPress Impact**: < 5ms additional load time
- **Revenue per Bot Request**: $0.00065
- **Database Query Time**: < 10ms

### Troubleshooting Common Issues

#### Issue 1: API Connection Failure
```bash
# Debug API connectivity
curl -v https://production-worker-icy-cloud-8df3.katiesdogwalking.workers.dev/health

# Check PHP cURL
php -m | grep curl
```

#### Issue 2: Bot Detection Not Working
```php
// Enable WordPress debug logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check debug.log for errors
tail -f /wp-content/debug.log
```

#### Issue 3: Revenue Not Tracking
```sql
-- Check database logs
SELECT * FROM wp_crawlguard_logs 
WHERE revenue_generated > 0 
ORDER BY timestamp DESC LIMIT 10;
```

### Final Validation Checklist

- [ ] CrawlGuard API deployed and operational
- [ ] WordPress plugin installed and activated
- [ ] Auto-setup completed successfully
- [ ] Bot detection working with 95% accuracy
- [ ] Revenue generation tracking properly
- [ ] Analytics dashboard displaying data
- [ ] All security tests passed
- [ ] Performance meets requirements
- [ ] Documentation complete and accurate

## Production Deployment

Once all tests pass:

1. **Package Plugin**: Create final .zip for distribution
2. **Update Documentation**: Ensure all guides are current
3. **Deploy to WordPress.org**: Submit for review (if applicable)
4. **Monitor Performance**: Set up ongoing monitoring
5. **Customer Support**: Prepare support resources

## Continuous Monitoring

Set up monitoring for:
- API endpoint availability
- Response time performance
- Error rates and failures
- Revenue generation trends
- Database performance metrics
