@echo off
REM PayPerCrawl Hostinger Deployment Script for Windows
REM Run this script on your Hostinger server (if Windows)

echo 🚀 Starting PayPerCrawl deployment on Hostinger...

REM Check if Node.js is installed
node -v >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Node.js is not installed. Please install Node.js first.
    pause
    exit /b 1
)

REM Display Node.js version
echo ✅ Node.js version:
node -v

REM Check if npm is installed
npm -v >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ npm is not installed. Please install npm first.
    pause
    exit /b 1
)

REM Install dependencies
echo 📦 Installing dependencies...
npm install

REM Generate Prisma client
echo 🗄️ Generating Prisma client...
npx prisma generate

REM Build the application
echo 🔨 Building PayPerCrawl application...
npm run build

REM Check if build was successful
if %errorlevel% neq 0 (
    echo ❌ Build failed. Please check the errors above.
    pause
    exit /b 1
)

echo ✅ Build successful!

REM Start the application
echo 🚀 Starting PayPerCrawl application...
echo 📍 Application will be available at: https://paypercrawl.tech
echo 🔧 Admin dashboard: https://paypercrawl.tech/admin

REM Start the application
echo 🔄 Starting with npm...
npm run start:next

echo 🎉 PayPerCrawl deployment completed!
echo 📊 Check your application at: https://paypercrawl.tech
pause
