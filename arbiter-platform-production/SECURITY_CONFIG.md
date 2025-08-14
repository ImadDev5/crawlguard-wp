# 🔐 Security Configuration for Arbiter Platform

## Environment-Specific Configurations

### Development (.env.development)
```bash
# Copy this for local development
JWT_SECRET="dev_secret_change_in_production_32_chars"
DATABASE_URL="postgresql://postgres:password@localhost:5432/arbiter_dev"
REDIS_URL="redis://localhost:6379"
NODE_ENV="development"
LOG_LEVEL="debug"
```

### Production (.env.production)
```bash
# Use strong secrets in production
JWT_SECRET="your_production_jwt_secret_32_characters"
DATABASE_URL="postgresql://user:pass@prod-host:5432/arbiter_prod"
REDIS_URL="rediss://user:pass@prod-redis:6380"
NODE_ENV="production"
LOG_LEVEL="info"
```

## Secret Management Best Practices

### 1. Local Development
- Use `.env` file (already gitignored)
- Keep secrets simple but unique
- Don't use production secrets locally

### 2. Production Deployment
- Use environment variables on hosting platform
- Consider using secret management service:
  - AWS Secrets Manager
  - Azure Key Vault
  - Google Secret Manager
  - HashiCorp Vault

### 3. Secret Rotation
- Rotate JWT secrets monthly
- Rotate API keys quarterly
- Update database passwords annually

## Required vs Optional Secrets

### 🚨 Critical (Platform won't start without these)
```bash
JWT_SECRET=              # For user authentication
DATABASE_URL=            # Database connection
GOOGLE_CLIENT_ID=        # User login
GOOGLE_CLIENT_SECRET=    # User login
```

### 🔶 Important (Core features need these)
```bash
STRIPE_SECRET_KEY=       # Payment processing
SENDGRID_API_KEY=       # Email notifications
REDIS_URL=              # Performance & caching
```

### 💡 Optional (Advanced features)
```bash
AWS_ACCESS_KEY_ID=      # File uploads
GITHUB_CLIENT_ID=       # GitHub OAuth
KAFKA_BROKERS=          # Event streaming
ELASTICSEARCH_URL=      # Advanced search
```

## Quick Setup for Different Scenarios

### Scenario 1: Just Testing the Platform
```bash
# Minimal setup - copy these to .env
JWT_SECRET="test_secret_for_development_only"
DATABASE_URL="postgresql://postgres:password@localhost:5432/arbiter_test"
GOOGLE_CLIENT_ID="fake_client_id_for_testing"
GOOGLE_CLIENT_SECRET="fake_secret_for_testing"
```

### Scenario 2: Full Development Setup
- Follow complete CREDENTIALS_SETUP.md guide
- Set up all integrations
- Use real API keys for testing

### Scenario 3: Production Deployment
- Use hosting platform's environment variables
- Set up monitoring and alerting
- Implement secret rotation

## Validation Script
Run this to check your setup:
```bash
npm run check-credentials
```

## Emergency Access
If you lose access to credentials:
1. JWT_SECRET: Generate new one, users need to re-login
2. Database: Check hosting provider dashboard
3. OAuth keys: Regenerate in respective platforms
4. API keys: Regenerate in service dashboards
