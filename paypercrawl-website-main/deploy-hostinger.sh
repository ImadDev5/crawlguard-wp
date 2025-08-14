#!/bin/bash

# PayPerCrawl Hostinger Deployment Script
# Run this script on your Hostinger server

echo "🚀 Starting PayPerCrawl deployment on Hostinger..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    exit 1
fi

# Check Node.js version
NODE_VERSION=$(node -v)
echo "✅ Node.js version: $NODE_VERSION"

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

# Install dependencies
echo "📦 Installing dependencies..."
npm install

# Generate Prisma client
echo "🗄️ Generating Prisma client..."
npx prisma generate

# Build the application
echo "🔨 Building PayPerCrawl application..."
npm run build

# Check if build was successful
if [ $? -eq 0 ]; then
    echo "✅ Build successful!"
else
    echo "❌ Build failed. Please check the errors above."
    exit 1
fi

# Start the application
echo "🚀 Starting PayPerCrawl application..."
echo "📍 Application will be available at: https://paypercrawl.tech"
echo "🔧 Admin dashboard: https://paypercrawl.tech/admin"

# Start with PM2 if available, otherwise use npm start
if command -v pm2 &> /dev/null; then
    echo "🔄 Starting with PM2..."
    pm2 start npm --name "paypercrawl" -- run start:next
    pm2 save
    pm2 startup
else
    echo "🔄 Starting with npm..."
    npm run start:next
fi

echo "🎉 PayPerCrawl deployment completed!"
echo "📊 Check your application at: https://paypercrawl.tech"
