# CrawlGuard WP - Security Documentation

## 🛡️ **Security Overview**

CrawlGuard WP implements enterprise-grade security measures to protect user data, financial transactions, and system integrity. This document outlines our comprehensive security architecture, threat model, and compliance measures.

## 🎯 **Security Principles**

### **Defense in Depth**
Multiple layers of security controls to prevent, detect, and respond to threats:
- **Perimeter Security**: Cloudflare DDoS protection and WAF
- **Application Security**: Input validation, authentication, authorization
- **Data Security**: Encryption at rest and in transit
- **Infrastructure Security**: Secure deployment and configuration management

### **Zero Trust Architecture**
- **Verify Everything**: No implicit trust based on network location
- **Least Privilege**: Minimal access rights for users and systems
- **Continuous Monitoring**: Real-time security monitoring and alerting
- **Assume Breach**: Design systems to limit blast radius of potential breaches

## 🔐 **Authentication & Authorization**

### **API Authentication**
```javascript
// API Key Authentication
class APIKeyAuth {
  static async validateKey(apiKey) {
    // Hash the provided key
    const hashedKey = await crypto.subtle.digest(
      'SHA-256',
      new TextEncoder().encode(apiKey)
    );
    
    // Lookup in database with rate limiting
    const site = await db.query(`
      SELECT id, subscription_tier, active, rate_limit_remaining
      FROM sites 
      WHERE api_key_hash = $1 AND active = true
    `, [hashedKey]);
    
    if (!site) {
      throw new AuthenticationError('Invalid API key');
    }
    
    // Check rate limits
    if (site.rate_limit_remaining <= 0) {
      throw new RateLimitError('Rate limit exceeded');
    }
    
    return site;
  }
}
```

### **WordPress Integration Security**
```php
// WordPress nonce verification
function crawlguard_verify_nonce($action) {
    if (!wp_verify_nonce($_POST['_wpnonce'], $action)) {
        wp_die('Security check failed');
    }
}

// Capability checks
function crawlguard_check_permissions() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
}

// Sanitize all inputs
function crawlguard_sanitize_input($input, $type = 'text') {
    switch ($type) {
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'text':
        default:
            return sanitize_text_field($input);
    }
}
```

### **Role-Based Access Control (RBAC)**
```javascript
const PERMISSIONS = {
  'site_admin': ['read_analytics', 'manage_settings', 'view_payments'],
  'site_editor': ['read_analytics', 'view_settings'],
  'site_viewer': ['read_analytics']
};

function checkPermission(userRole, requiredPermission) {
  const userPermissions = PERMISSIONS[userRole] || [];
  return userPermissions.includes(requiredPermission);
}
```

## 🔒 **Data Protection**

### **Encryption Standards**

#### **Data in Transit**
- **TLS 1.3**: All API communications encrypted
- **Certificate Pinning**: Prevent man-in-the-middle attacks
- **HSTS**: Force HTTPS connections
- **Perfect Forward Secrecy**: Ephemeral key exchange

```javascript
// TLS configuration
const tlsConfig = {
  minVersion: 'TLSv1.3',
  ciphers: [
    'TLS_AES_256_GCM_SHA384',
    'TLS_CHACHA20_POLY1305_SHA256',
    'TLS_AES_128_GCM_SHA256'
  ],
  honorCipherOrder: true,
  secureProtocol: 'TLSv1_3_method'
};
```

#### **Data at Rest**
- **Database Encryption**: AES-256 encryption for sensitive data
- **Key Management**: Separate key storage and rotation
- **Backup Encryption**: Encrypted backups with separate keys

```sql
-- Encrypted sensitive data storage
CREATE TABLE encrypted_data (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id),
    encrypted_value BYTEA NOT NULL,
    encryption_key_id VARCHAR(64) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Function to encrypt sensitive data
CREATE OR REPLACE FUNCTION encrypt_sensitive_data(
    plain_text TEXT,
    key_id VARCHAR(64)
) RETURNS BYTEA AS $$
BEGIN
    -- Use pgcrypto extension for encryption
    RETURN pgp_sym_encrypt(plain_text, get_encryption_key(key_id));
END;
$$ LANGUAGE plpgsql;
```

### **Personal Data Handling**

#### **Data Minimization**
```javascript
// Collect only necessary data
const COLLECTED_DATA = {
  required: ['site_url', 'admin_email'],
  optional: ['site_name'],
  never_collect: ['passwords', 'personal_content', 'user_behavior']
};

function validateDataCollection(data) {
  const allowedFields = [...COLLECTED_DATA.required, ...COLLECTED_DATA.optional];
  const providedFields = Object.keys(data);
  
  // Remove any fields not in allowed list
  return Object.fromEntries(
    Object.entries(data).filter(([key]) => allowedFields.includes(key))
  );
}
```

#### **Data Retention Policy**
```javascript
// Automated data cleanup
class DataRetentionManager {
  static async cleanupExpiredData() {
    // Remove old bot request logs (keep 2 years)
    await db.query(`
      DELETE FROM bot_requests 
      WHERE created_at < NOW() - INTERVAL '2 years'
    `);
    
    // Remove old error logs (keep 90 days)
    await db.query(`
      DELETE FROM error_logs 
      WHERE created_at < NOW() - INTERVAL '90 days'
    `);
    
    // Anonymize old payment data (keep 7 years for compliance)
    await db.query(`
      UPDATE payments 
      SET metadata = jsonb_build_object('anonymized', true)
      WHERE created_at < NOW() - INTERVAL '7 years'
        AND metadata->>'anonymized' IS NULL
    `);
  }
}
```

## 🚨 **Threat Model**

### **Identified Threats**

#### **1. API Abuse & DDoS**
**Threat**: Malicious actors overwhelming API with requests
**Mitigation**:
```javascript
// Multi-layer rate limiting
class RateLimiter {
  constructor() {
    this.limits = {
      global: 10000,    // requests per minute globally
      perIP: 100,       // requests per minute per IP
      perKey: 1000      // requests per minute per API key
    };
  }
  
  async checkLimits(ip, apiKey) {
    const checks = [
      this.checkGlobalLimit(),
      this.checkIPLimit(ip),
      this.checkAPIKeyLimit(apiKey)
    ];
    
    const results = await Promise.all(checks);
    return results.every(result => result.allowed);
  }
}
```

#### **2. SQL Injection**
**Threat**: Malicious SQL code injection through user inputs
**Mitigation**:
```javascript
// Parameterized queries only
async function safeQuery(query, params) {
  // Validate query contains placeholders
  const placeholderCount = (query.match(/\$\d+/g) || []).length;
  if (placeholderCount !== params.length) {
    throw new Error('Parameter count mismatch');
  }
  
  // Use prepared statements
  return await db.query(query, params);
}

// Input validation
function validateInput(input, schema) {
  const validator = new JSONSchemaValidator(schema);
  const result = validator.validate(input);
  
  if (!result.valid) {
    throw new ValidationError('Invalid input', result.errors);
  }
  
  return result.sanitized;
}
```

#### **3. Payment Fraud**
**Threat**: Fraudulent payment attempts and chargebacks
**Mitigation**:
```javascript
// Fraud detection system
class FraudDetector {
  static async analyzePayment(paymentData) {
    const riskFactors = {
      velocityCheck: await this.checkPaymentVelocity(paymentData),
      geolocationCheck: await this.checkGeolocation(paymentData),
      deviceFingerprint: await this.analyzeDeviceFingerprint(paymentData),
      behaviorAnalysis: await this.analyzeBehavior(paymentData)
    };
    
    const riskScore = this.calculateRiskScore(riskFactors);
    
    if (riskScore > 0.8) {
      return { action: 'block', reason: 'High fraud risk' };
    } else if (riskScore > 0.5) {
      return { action: 'review', reason: 'Medium fraud risk' };
    }
    
    return { action: 'approve', riskScore };
  }
}
```

#### **4. Data Breaches**
**Threat**: Unauthorized access to sensitive data
**Mitigation**:
```javascript
// Data access logging
function logDataAccess(userId, resource, action) {
  const logEntry = {
    timestamp: new Date().toISOString(),
    userId,
    resource,
    action,
    ipAddress: getClientIP(),
    userAgent: getUserAgent(),
    sessionId: getSessionId()
  };
  
  // Log to secure audit trail
  auditLogger.log(logEntry);
  
  // Alert on suspicious patterns
  if (detectSuspiciousAccess(logEntry)) {
    securityAlerts.send('Suspicious data access detected', logEntry);
  }
}
```

## 🔍 **Security Monitoring**

### **Real-Time Monitoring**
```javascript
// Security event monitoring
class SecurityMonitor {
  static async monitorSecurityEvents() {
    const events = [
      'failed_authentication',
      'rate_limit_exceeded',
      'suspicious_payment',
      'data_access_anomaly',
      'api_abuse_detected'
    ];
    
    for (const event of events) {
      const recentEvents = await this.getRecentEvents(event, '5m');
      
      if (recentEvents.length > this.getThreshold(event)) {
        await this.triggerAlert(event, recentEvents);
      }
    }
  }
  
  static async triggerAlert(eventType, events) {
    const alert = {
      type: eventType,
      severity: this.getSeverity(eventType),
      count: events.length,
      timeframe: '5 minutes',
      details: events.slice(0, 10) // Include sample events
    };
    
    // Send to security team
    await this.sendSecurityAlert(alert);
    
    // Auto-remediation for certain events
    if (eventType === 'api_abuse_detected') {
      await this.temporaryIPBlock(events.map(e => e.ip));
    }
  }
}
```

### **Anomaly Detection**
```javascript
// Behavioral anomaly detection
class AnomalyDetector {
  static async detectAnomalies() {
    const metrics = await this.collectMetrics();
    
    const anomalies = [
      this.detectTrafficAnomalies(metrics.traffic),
      this.detectPaymentAnomalies(metrics.payments),
      this.detectErrorRateAnomalies(metrics.errors),
      this.detectLatencyAnomalies(metrics.latency)
    ];
    
    const detectedAnomalies = anomalies.filter(a => a.detected);
    
    if (detectedAnomalies.length > 0) {
      await this.reportAnomalies(detectedAnomalies);
    }
  }
  
  static detectTrafficAnomalies(trafficData) {
    const baseline = this.calculateBaseline(trafficData.historical);
    const current = trafficData.current;
    
    // Statistical anomaly detection (Z-score)
    const zScore = (current - baseline.mean) / baseline.stdDev;
    
    return {
      detected: Math.abs(zScore) > 3, // 3 standard deviations
      severity: Math.abs(zScore) > 4 ? 'high' : 'medium',
      details: { zScore, current, baseline }
    };
  }
}
```

## 📋 **Compliance & Standards**

### **GDPR Compliance**
```javascript
// GDPR data subject rights implementation
class GDPRCompliance {
  // Right to access
  static async exportUserData(userId) {
    const userData = await db.query(`
      SELECT site_url, admin_email, created_at, subscription_tier
      FROM sites WHERE id = $1
    `, [userId]);
    
    const botRequests = await db.query(`
      SELECT ip_address, user_agent, bot_detected, created_at
      FROM bot_requests WHERE site_id = $1
    `, [userId]);
    
    return {
      personal_data: userData,
      activity_data: botRequests,
      export_date: new Date().toISOString()
    };
  }
  
  // Right to erasure
  static async deleteUserData(userId) {
    await db.transaction(async (trx) => {
      // Delete in correct order due to foreign key constraints
      await trx.query('DELETE FROM bot_requests WHERE site_id = $1', [userId]);
      await trx.query('DELETE FROM payments WHERE site_id = $1', [userId]);
      await trx.query('DELETE FROM sites WHERE id = $1', [userId]);
    });
    
    // Log deletion for audit trail
    await this.logDataDeletion(userId);
  }
  
  // Data portability
  static async generateDataExport(userId, format = 'json') {
    const data = await this.exportUserData(userId);
    
    switch (format) {
      case 'json':
        return JSON.stringify(data, null, 2);
      case 'csv':
        return this.convertToCSV(data);
      default:
        throw new Error('Unsupported export format');
    }
  }
}
```

### **PCI DSS Compliance**
```javascript
// PCI DSS requirements implementation
class PCICompliance {
  // Requirement 3: Protect stored cardholder data
  static async handlePaymentData(paymentData) {
    // Never store full PAN, CVV, or PIN
    const sanitizedData = {
      last4: paymentData.card_number.slice(-4),
      brand: paymentData.card_brand,
      exp_month: paymentData.exp_month,
      exp_year: paymentData.exp_year
      // CVV and full PAN are never stored
    };
    
    return sanitizedData;
  }
  
  // Requirement 8: Identify and authenticate access
  static async enforceStrongAuthentication(user) {
    const requirements = {
      minPasswordLength: 12,
      requireSpecialChars: true,
      requireNumbers: true,
      requireUppercase: true,
      requireLowercase: true,
      maxPasswordAge: 90 // days
    };
    
    return this.validatePasswordRequirements(user.password, requirements);
  }
}
```

### **SOC 2 Type II Controls**
```javascript
// SOC 2 control implementation
class SOC2Controls {
  // Security control: Access controls
  static async enforceAccessControls(user, resource) {
    // Principle of least privilege
    const requiredPermissions = this.getRequiredPermissions(resource);
    const userPermissions = await this.getUserPermissions(user.id);
    
    const hasAccess = requiredPermissions.every(
      permission => userPermissions.includes(permission)
    );
    
    // Log access attempt
    await this.logAccessAttempt(user.id, resource, hasAccess);
    
    return hasAccess;
  }
  
  // Availability control: System monitoring
  static async monitorSystemAvailability() {
    const healthChecks = [
      this.checkDatabaseHealth(),
      this.checkAPIHealth(),
      this.checkPaymentSystemHealth()
    ];
    
    const results = await Promise.allSettled(healthChecks);
    const availability = results.filter(r => r.status === 'fulfilled').length / results.length;
    
    // Alert if availability drops below SLA
    if (availability < 0.999) { // 99.9% SLA
      await this.triggerAvailabilityAlert(availability, results);
    }
    
    return availability;
  }
}
```

## 🔧 **Security Configuration**

### **Cloudflare Security Settings**
```yaml
# Cloudflare security configuration
security:
  ssl_mode: "full_strict"
  min_tls_version: "1.3"
  hsts:
    enabled: true
    max_age: 31536000
    include_subdomains: true
    preload: true
  
  waf:
    enabled: true
    mode: "high"
    custom_rules:
      - block_known_bad_ips
      - rate_limit_api_endpoints
      - block_sql_injection_attempts
  
  ddos_protection:
    enabled: true
    sensitivity: "high"
    
  bot_management:
    enabled: true
    mode: "allow_verified_bots"
```

### **Database Security Configuration**
```sql
-- PostgreSQL security configuration
-- Enable row-level security
ALTER TABLE sites ENABLE ROW LEVEL SECURITY;
ALTER TABLE bot_requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE payments ENABLE ROW LEVEL SECURITY;

-- Create security policies
CREATE POLICY site_isolation ON sites
    FOR ALL TO crawlguard_app
    USING (id = current_setting('app.current_site_id')::INTEGER);

CREATE POLICY bot_requests_isolation ON bot_requests
    FOR ALL TO crawlguard_app
    USING (site_id = current_setting('app.current_site_id')::INTEGER);

-- Enable audit logging
CREATE EXTENSION IF NOT EXISTS pgaudit;
ALTER SYSTEM SET pgaudit.log = 'all';
ALTER SYSTEM SET pgaudit.log_catalog = 'off';
```

## 🚨 **Incident Response**

### **Security Incident Response Plan**
```javascript
// Incident response automation
class IncidentResponse {
  static async handleSecurityIncident(incident) {
    const severity = this.assessSeverity(incident);
    
    // Immediate response based on severity
    switch (severity) {
      case 'critical':
        await this.criticalIncidentResponse(incident);
        break;
      case 'high':
        await this.highSeverityResponse(incident);
        break;
      case 'medium':
        await this.mediumSeverityResponse(incident);
        break;
      default:
        await this.lowSeverityResponse(incident);
    }
    
    // Always log and notify
    await this.logIncident(incident);
    await this.notifySecurityTeam(incident, severity);
  }
  
  static async criticalIncidentResponse(incident) {
    // Immediate containment
    await this.enableEmergencyMode();
    await this.blockSuspiciousIPs(incident.indicators);
    await this.notifyExecutiveTeam(incident);
    
    // Evidence preservation
    await this.preserveEvidence(incident);
    
    // External notifications (if required)
    if (incident.type === 'data_breach') {
      await this.notifyRegulatoryBodies(incident);
    }
  }
}
```

---

**This security documentation provides comprehensive coverage of CrawlGuard WP's security architecture, ensuring enterprise-grade protection for user data, financial transactions, and system integrity while maintaining compliance with industry standards and regulations.**
