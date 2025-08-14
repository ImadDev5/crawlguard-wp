#!/bin/bash

# 🚀 ARBITER PLATFORM - TECHNICAL SETUP SCRIPT
# This script sets up the foundation for transforming CrawlGuard into Arbiter Platform

set -e

echo "🚀 Starting Arbiter Platform Technical Setup..."
echo "=================================================="

# Create directories for new architecture
echo "📁 Creating Arbiter Platform directory structure..."

# Backend microservices
mkdir -p arbiter-platform/services/bot-detection
mkdir -p arbiter-platform/services/pricing-engine
mkdir -p arbiter-platform/services/content-licensing
mkdir -p arbiter-platform/services/workflow-engine
mkdir -p arbiter-platform/services/payment-processing
mkdir -p arbiter-platform/services/analytics
mkdir -p arbiter-platform/services/notification
mkdir -p arbiter-platform/services/integration

# Frontend applications
mkdir -p arbiter-platform/frontend/publisher-dashboard
mkdir -p arbiter-platform/frontend/ai-company-portal
mkdir -p arbiter-platform/frontend/enterprise-console
mkdir -p arbiter-platform/frontend/mobile-app
mkdir -p arbiter-platform/frontend/shared-components

# Infrastructure and DevOps
mkdir -p arbiter-platform/infrastructure/kubernetes
mkdir -p arbiter-platform/infrastructure/terraform
mkdir -p arbiter-platform/infrastructure/monitoring
mkdir -p arbiter-platform/infrastructure/security

# API Gateway and GraphQL
mkdir -p arbiter-platform/api-gateway
mkdir -p arbiter-platform/graphql-schema

# Database schemas and migrations
mkdir -p arbiter-platform/database/postgresql
mkdir -p arbiter-platform/database/elasticsearch
mkdir -p arbiter-platform/database/redis
mkdir -p arbiter-platform/database/clickhouse

# Documentation and guides
mkdir -p arbiter-platform/docs/api
mkdir -p arbiter-platform/docs/deployment
mkdir -p arbiter-platform/docs/architecture
mkdir -p arbiter-platform/docs/user-guides

# Testing and CI/CD
mkdir -p arbiter-platform/tests/unit
mkdir -p arbiter-platform/tests/integration
mkdir -p arbiter-platform/tests/e2e
mkdir -p arbiter-platform/cicd/github-actions
mkdir -p arbiter-platform/cicd/argocd

echo "✅ Directory structure created successfully!"

# Install required dependencies for development
echo "📦 Installing development dependencies..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js 18+ and run this script again."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm and run this script again."
    exit 1
fi

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker and run this script again."
    exit 1
fi

# Initialize package.json for the platform
cd arbiter-platform
npm init -y

# Install core dependencies
echo "📦 Installing platform dependencies..."
npm install --save \
    express \
    apollo-server-express \
    graphql \
    @apollo/federation \
    prisma \
    @prisma/client \
    redis \
    elasticsearch \
    stripe \
    jsonwebtoken \
    bcrypt \
    helmet \
    cors \
    winston \
    joi \
    dotenv

# Install development dependencies
npm install --save-dev \
    @types/node \
    @types/express \
    typescript \
    ts-node \
    nodemon \
    jest \
    @types/jest \
    supertest \
    eslint \
    prettier \
    husky \
    lint-staged

echo "✅ Dependencies installed successfully!"

# Create initial configuration files
echo "⚙️  Creating configuration files..."

# TypeScript configuration
cat > tsconfig.json << 'EOF'
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "commonjs",
    "lib": ["ES2020"],
    "outDir": "./dist",
    "rootDir": "./src",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "resolveJsonModule": true,
    "declaration": true,
    "declarationMap": true,
    "sourceMap": true,
    "experimentalDecorators": true,
    "emitDecoratorMetadata": true
  },
  "include": ["src/**/*"],
  "exclude": ["node_modules", "dist", "tests"]
}
EOF

# ESLint configuration
cat > .eslintrc.js << 'EOF'
module.exports = {
  parser: '@typescript-eslint/parser',
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: 'module',
  },
  extends: [
    '@typescript-eslint/recommended',
    'prettier',
  ],
  rules: {
    '@typescript-eslint/no-unused-vars': 'error',
    '@typescript-eslint/no-explicit-any': 'warn',
    '@typescript-eslint/explicit-function-return-type': 'off',
    '@typescript-eslint/explicit-module-boundary-types': 'off',
  },
};
EOF

# Prettier configuration
cat > .prettierrc << 'EOF'
{
  "semi": true,
  "trailingComma": "es5",
  "singleQuote": true,
  "printWidth": 100,
  "tabWidth": 2
}
EOF

# Git hooks setup
npx husky install
npx husky add .husky/pre-commit "lint-staged"

# Lint-staged configuration
cat > .lintstagedrc.json << 'EOF'
{
  "*.{ts,js}": ["eslint --fix", "prettier --write"],
  "*.{json,md}": ["prettier --write"]
}
EOF

# Docker Compose for local development
cat > docker-compose.yml << 'EOF'
version: '3.8'
services:
  postgres:
    image: postgres:14
    environment:
      POSTGRES_DB: arbiter_platform
      POSTGRES_USER: arbiter
      POSTGRES_PASSWORD: development_password
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  elasticsearch:
    image: elasticsearch:8.8.0
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
    ports:
      - "9200:9200"
      - "9300:9300"

  clickhouse:
    image: clickhouse/clickhouse-server:latest
    ports:
      - "8123:8123"
      - "9000:9000"

volumes:
  postgres_data:
EOF

echo "✅ Configuration files created successfully!"

# Create environment template
cat > .env.example << 'EOF'
# Arbiter Platform Environment Variables

# Database URLs
DATABASE_URL=postgresql://arbiter:development_password@localhost:5432/arbiter_platform
REDIS_URL=redis://localhost:6379
ELASTICSEARCH_URL=http://localhost:9200
CLICKHOUSE_URL=http://localhost:8123

# API Configuration
PORT=3000
API_VERSION=v1
JWT_SECRET=your-super-secret-jwt-key-here
CORS_ORIGIN=http://localhost:3000

# External Services
STRIPE_SECRET_KEY=sk_test_your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
GOOGLE_CLOUD_PROJECT_ID=arbiter-platform-dev

# Feature Flags
ENABLE_AI_DETECTION=true
ENABLE_DYNAMIC_PRICING=true
ENABLE_ENTERPRISE_FEATURES=false

# Monitoring
LOG_LEVEL=info
ENABLE_METRICS=true
SENTRY_DSN=https://your-sentry-dsn
EOF

echo "🔒 Environment template created. Copy .env.example to .env and update with your values."

# Return to original directory
cd ..

echo "🎉 Arbiter Platform technical setup completed!"
echo ""
echo "Next steps:"
echo "1. Copy .env.example to .env and configure your environment variables"
echo "2. Run 'docker-compose up -d' to start local development services"
echo "3. Begin implementing the microservices architecture"
echo "4. Set up your GCP project and Kubernetes cluster"
echo ""
echo "For detailed instructions, see MANUAL_ACTIONS_REQUIRED.md"
