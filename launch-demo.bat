@echo off
echo 🚀 Arbiter Platform - Enhanced Prototype Demo
echo ============================================

:: Check if Node.js is installed
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo ℹ️  Node.js not found. Opening HTML demo directly...
    start demo.html
    goto :end
)

echo ✅ Node.js found - Setting up demo server...

:: Create package.json if it doesn't exist
if not exist package.json (
    echo 📦 Creating package.json...
    echo { > package.json
    echo   "name": "arbiter-platform-demo", >> package.json
    echo   "version": "1.0.0", >> package.json
    echo   "description": "Arbiter Platform Enhanced Prototype Demo", >> package.json
    echo   "main": "server.js", >> package.json
    echo   "scripts": { >> package.json
    echo     "start": "node server.js" >> package.json
    echo   }, >> package.json
    echo   "dependencies": { >> package.json
    echo     "express": "^4.18.2", >> package.json
    echo     "cors": "^2.8.5" >> package.json
    echo   } >> package.json
    echo } >> package.json
)

:: Create Express server if it doesn't exist
if not exist server.js (
    echo 🔧 Creating demo server...
    echo const express = require('express'^); > server.js
    echo const path = require('path'^); >> server.js
    echo const cors = require('cors'^); >> server.js
    echo. >> server.js
    echo const app = express(^); >> server.js
    echo const PORT = 3000; >> server.js
    echo. >> server.js
    echo app.use(cors(^^)^); >> server.js
    echo app.use(express.static('.'^)^); >> server.js
    echo. >> server.js
    echo app.get('/', (req, res^^) =^^> { >> server.js
    echo     res.sendFile(path.join(__dirname, 'demo.html'^)^); >> server.js
    echo }^^); >> server.js
    echo. >> server.js
    echo app.get('/api/creator/stats', (req, res^^) =^^> { >> server.js
    echo     res.json({ >> server.js
    echo         totalRevenue: 12543, >> server.js
    echo         contentItems: 156, >> server.js
    echo         licensesSold: 1247, >> server.js
    echo         successRate: 89 >> server.js
    echo     }^^); >> server.js
    echo }^^); >> server.js
    echo. >> server.js
    echo app.get('/api/ai/stats', (req, res^^) =^^> { >> server.js
    echo     res.json({ >> server.js
    echo         totalSpent: 48750, >> server.js
    echo         licensesOwned: 342, >> server.js
    echo         apiCalls: 156432, >> server.js
    echo         activeProjects: 12 >> server.js
    echo     }^^); >> server.js
    echo }^^); >> server.js
    echo. >> server.js
    echo app.listen(PORT, (^^) =^^> { >> server.js
    echo     console.log(`Arbiter Platform Demo running at http://localhost:${PORT}`^^); >> server.js
    echo     console.log(`Creator Dashboard available`^^); >> server.js
    echo     console.log(`AI Dashboard available`^^); >> server.js
    echo }^^); >> server.js
)

:: Install dependencies if needed
if not exist node_modules (
    echo 📦 Installing dependencies...
    npm install express cors
)

echo 🌐 Starting demo server...
echo 📱 Opening in browser...

:: Start server and open browser
start /B node server.js
timeout /t 3 /nobreak >nul
start http://localhost:3000

echo.
echo 🎉 Arbiter Platform Demo is now running!
echo ========================================
echo 📊 Creator Dashboard: Enhanced revenue tracking
echo 🤖 AI Dashboard: License management system
echo 🌐 URL: http://localhost:3000
echo.
echo Press any key to stop the demo...
pause >nul

:: Kill the Node.js process
taskkill /F /IM node.exe >nul 2>nul

:end
echo 🎯 Demo complete! Thank you for testing Arbiter Platform.
pause
