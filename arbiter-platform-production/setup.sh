#!/bin/bash

# Arbiter Platform Production Setup Script
# This script sets up the complete production-ready platform

echo "🚀 Setting up Arbiter Platform Production Environment..."
echo "======================================================"

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    echo "❌ Error: package.json not found. Are you in the project root?"
    exit 1
fi

# Install dependencies
echo "📦 Installing dependencies..."
npm install

# Install workspace dependencies
echo "📦 Installing workspace dependencies..."
npm install --workspaces

# Set up environment variables
echo "🔧 Setting up environment variables..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "✅ Created .env file from .env.example"
    echo "⚠️  Please update the .env file with your actual values"
else
    echo "✅ .env file already exists"
fi

# Start Docker services
echo "🐳 Starting Docker services..."
if command -v docker-compose >/dev/null 2>&1; then
    docker-compose up -d postgres redis elasticsearch minio mailhog
    echo "✅ Docker services started"
else
    echo "⚠️  Docker Compose not found. Please install Docker and Docker Compose"
    echo "   Services needed: PostgreSQL, Redis, Elasticsearch, MinIO, Mailhog"
fi

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Generate Prisma client
echo "🔄 Generating Prisma client..."
cd packages/database
npx prisma generate
cd ../..

# Push database schema
echo "🗄️  Pushing database schema..."
cd packages/database
npx prisma db push
cd ../..

# Build packages
echo "🏗️  Building packages..."
npm run build

echo ""
echo "🎉 Arbiter Platform Production Setup Complete!"
echo "=============================================="
echo ""
echo "📋 Next Steps:"
echo "1. Update .env with your actual values"
echo "2. Start the development servers:"
echo "   npm run dev"
echo ""
echo "🌐 Services will be available at:"
echo "   • Web App: http://localhost:3000"
echo "   • API Server: http://localhost:4000"
echo "   • Database GUI: http://localhost:5555"
echo "   • Email Testing: http://localhost:8025"
echo "   • File Storage: http://localhost:9001"
echo ""
echo "🔧 Development Tools:"
echo "   • Prisma Studio: npm run db:studio"
echo "   • Database Migrations: npm run db:migrate"
echo "   • Linting: npm run lint"
echo "   • Type Checking: npm run type-check"
echo ""
echo "🚀 Ready to build the future of content licensing!"
