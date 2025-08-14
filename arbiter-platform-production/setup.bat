@echo off
echo 🚀 Setting up Arbiter Platform Production Environment...
echo ======================================================

REM Check if we're in the right directory
if not exist package.json (
    echo ❌ Error: package.json not found. Are you in the project root?
    exit /b 1
)

echo 📦 Installing dependencies...
call npm install
if %errorlevel% neq 0 (
    echo ❌ Failed to install dependencies
    exit /b 1
)

echo 📦 Installing workspace dependencies...
call npm install --workspaces

echo 🔧 Setting up environment variables...
if not exist .env (
    copy .env.example .env
    echo ✅ Created .env file from .env.example
    echo ⚠️  Please update the .env file with your actual values
) else (
    echo ✅ .env file already exists
)

echo 🐳 Starting Docker services...
where docker-compose >nul 2>nul
if %errorlevel% equ 0 (
    docker-compose up -d postgres redis elasticsearch minio mailhog
    echo ✅ Docker services started
) else (
    echo ⚠️  Docker Compose not found. Please install Docker and Docker Compose
    echo    Services needed: PostgreSQL, Redis, Elasticsearch, MinIO, Mailhog
)

echo ⏳ Waiting for services to be ready...
timeout /t 10 /nobreak >nul

echo 🔄 Generating Prisma client...
cd packages\database
call npx prisma generate
cd ..\..

echo 🗄️  Pushing database schema...
cd packages\database
call npx prisma db push
cd ..\..

echo 🏗️  Building packages...
call npm run build

echo.
echo 🎉 Arbiter Platform Production Setup Complete!
echo ==============================================
echo.
echo 📋 Next Steps:
echo 1. Update .env with your actual values
echo 2. Start the development servers:
echo    npm run dev
echo.
echo 🌐 Services will be available at:
echo    • Web App: http://localhost:3000
echo    • API Server: http://localhost:4000
echo    • Database GUI: http://localhost:5555
echo    • Email Testing: http://localhost:8025
echo    • File Storage: http://localhost:9001
echo.
echo 🔧 Development Tools:
echo    • Prisma Studio: npm run db:studio
echo    • Database Migrations: npm run db:migrate
echo    • Linting: npm run lint
echo    • Type Checking: npm run type-check
echo.
echo 🚀 Ready to build the future of content licensing!
pause
