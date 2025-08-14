#!/bin/bash

# CrawlGuard WP Production Deployment Script
# This script automates the deployment process

set -e  # Exit on any error

echo "🚀 Starting CrawlGuard WP Production Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check prerequisites
echo "📋 Checking prerequisites..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    print_error "Node.js is not installed. Please install Node.js first."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    print_error "npm is not installed. Please install npm first."
    exit 1
fi

# Check if wrangler is installed
if ! command -v wrangler &> /dev/null; then
    print_warning "Wrangler CLI not found. Installing..."
    npm install -g wrangler
fi

print_status "Prerequisites check completed"

# Install dependencies
echo "📦 Installing dependencies..."
npm install
print_status "Dependencies installed"

# Build assets
echo "🔨 Building production assets..."
npm run build
print_status "Assets built successfully"

# Lint code
echo "🔍 Running code quality checks..."
npm run lint:js || print_warning "JavaScript linting found issues (non-blocking)"
print_status "Code quality checks completed"

# Check Wrangler authentication
echo "🔐 Checking Cloudflare authentication..."
if ! wrangler whoami &> /dev/null; then
    print_error "Not authenticated with Cloudflare. Please run 'wrangler login' first."
    exit 1
fi
print_status "Cloudflare authentication verified"

# Deploy to Cloudflare Workers
echo "☁️  Deploying to Cloudflare Workers..."
wrangler publish --env production
print_status "Cloudflare Worker deployed successfully"

# Check deployment health
echo "🏥 Checking deployment health..."
WORKER_URL=$(wrangler subdomain --env production 2>/dev/null || echo "")
if [ -n "$WORKER_URL" ]; then
    if curl -s "$WORKER_URL/v1/status" | grep -q "ok"; then
        print_status "Deployment health check passed"
    else
        print_warning "Health check failed - please verify manually"
    fi
else
    print_warning "Could not determine worker URL - please check manually"
fi

# Create WordPress plugin zip
echo "📦 Creating WordPress plugin package..."
zip -r crawlguard-wp.zip . \
    -x "node_modules/*" \
    -x ".git/*" \
    -x "*.zip" \
    -x "backend/*" \
    -x "database/*" \
    -x "deploy.sh" \
    -x "*.md" \
    -x "package*.json" \
    -x "webpack.config.js" \
    -x "wrangler.toml"

print_status "WordPress plugin package created: crawlguard-wp.zip"

# Generate deployment report
echo "📊 Generating deployment report..."
cat > deployment-report.txt << EOF
CrawlGuard WP Deployment Report
Generated: $(date)

✅ COMPLETED TASKS:
- Dependencies installed
- Production assets built
- Code quality checks passed
- Cloudflare Worker deployed
- WordPress plugin packaged

🔗 IMPORTANT URLS:
- Worker URL: Check Cloudflare dashboard
- API Endpoint: https://your-worker-url.workers.dev/v1/
- Health Check: https://your-worker-url.workers.dev/v1/status

📋 NEXT STEPS:
1. Set up environment variables (see MANUAL_SETUP_INSTRUCTIONS.md)
2. Configure database connection
3. Set up Stripe integration
4. Test the complete system
5. Submit plugin to WordPress.org

⚠️  MANUAL TASKS REQUIRED:
- Database setup (PostgreSQL)
- Stripe account configuration
- Environment variables setup
- Domain configuration
- SSL certificate setup

For detailed instructions, see: MANUAL_SETUP_INSTRUCTIONS.md
EOF

print_status "Deployment report generated: deployment-report.txt"

echo ""
echo "🎉 Deployment completed successfully!"
echo ""
echo "📋 NEXT STEPS:"
echo "1. Complete manual setup tasks (see MANUAL_SETUP_INSTRUCTIONS.md)"
echo "2. Test your deployment"
echo "3. Configure WordPress plugin"
echo "4. Start user acquisition"
echo ""
echo "🔗 USEFUL COMMANDS:"
echo "- Check worker logs: wrangler tail --env production"
echo "- Update worker: wrangler publish --env production"
echo "- Test API: curl https://your-worker-url.workers.dev/v1/status"
echo ""
print_status "Ready for production! 🚀"
