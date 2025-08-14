#!/bin/bash

# Arbiter Platform - Quick Launch Script
# This script sets up and launches the enhanced prototype

echo "🚀 Launching Arbiter Platform Enhanced Prototype..."
echo "================================================="

# Check if we're in WSL or Windows
if grep -q Microsoft /proc/version 2>/dev/null; then
    echo "✅ Running in WSL environment"
    PLATFORM="wsl"
else
    echo "ℹ️  Running in Windows environment"
    PLATFORM="windows"
fi

# Navigate to the enhanced project directory
if [ "$PLATFORM" = "wsl" ]; then
    cd /home/vortex/arbiter-platform/frontend || {
        echo "❌ Enhanced project not found in WSL. Please complete WSL setup first."
        exit 1
    }
else
    # For Windows, we'll create a simple server
    echo "🔧 Setting up Windows demo environment..."
    
    # Check if Node.js is available
    if command -v node >/dev/null 2>&1; then
        echo "✅ Node.js found"
        
        # Create a simple package.json if it doesn't exist
        if [ ! -f "package.json" ]; then
            echo "📦 Creating package.json..."
            cat > package.json << 'EOF'
{
  "name": "arbiter-platform-demo",
  "version": "1.0.0",
  "description": "Arbiter Platform Enhanced Prototype Demo",
  "main": "server.js",
  "scripts": {
    "start": "node server.js",
    "demo": "start demo.html"
  },
  "dependencies": {
    "express": "^4.18.2",
    "cors": "^2.8.5"
  }
}
EOF
        fi
        
        # Create a simple Express server
        cat > server.js << 'EOF'
const express = require('express');
const path = require('path');
const cors = require('cors');

const app = express();
const PORT = 3000;

app.use(cors());
app.use(express.static('.'));

// Serve the demo HTML
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'demo.html'));
});

// API endpoints for demo data
app.get('/api/creator/stats', (req, res) => {
    res.json({
        totalRevenue: 12543,
        contentItems: 156,
        licensesSold: 1247,
        successRate: 89,
        recentUploads: [
            { name: "AI Training Dataset #1", revenue: 2500, licenses: 45 },
            { name: "Voice Synthesis Pack", revenue: 1800, licenses: 32 },
            { name: "Image Recognition Set", revenue: 3200, licenses: 67 }
        ]
    });
});

app.get('/api/ai/stats', (req, res) => {
    res.json({
        totalSpent: 48750,
        licensesOwned: 342,
        apiCalls: 156432,
        activeProjects: 12,
        recentLicenses: [
            { name: "Speech Dataset Pro", cost: 5000, usage: "Active" },
            { name: "Vision Training Pack", cost: 3500, usage: "Completed" },
            { name: "NLP Corpus Premium", cost: 7200, usage: "In Progress" }
        ]
    });
});

app.listen(PORT, () => {
    console.log(`🚀 Arbiter Platform Demo Server running at http://localhost:${PORT}`);
    console.log(`📊 Creator Dashboard: http://localhost:${PORT}#creator`);
    console.log(`🤖 AI Dashboard: http://localhost:${PORT}#ai`);
});
EOF
        
        echo "🔧 Installing dependencies..."
        npm install express cors
        
        echo "🌐 Starting demo server..."
        echo "📱 Opening demo in browser..."
        
        # Start the server and open browser
        node server.js &
        SERVER_PID=$!
        
        sleep 2
        
        # Try to open in default browser
        if command -v start >/dev/null 2>&1; then
            start http://localhost:3000
        elif command -v xdg-open >/dev/null 2>&1; then
            xdg-open http://localhost:3000
        else
            echo "🌐 Open http://localhost:3000 in your browser"
        fi
        
        echo "🎉 Demo is running! Press Ctrl+C to stop."
        wait $SERVER_PID
        
    else
        echo "ℹ️  Node.js not found. Opening HTML demo directly..."
        if command -v start >/dev/null 2>&1; then
            start demo.html
        else
            echo "📱 Please open demo.html in your browser"
        fi
    fi
fi

# If in WSL, launch the full React app
if [ "$PLATFORM" = "wsl" ]; then
    echo "🔧 Installing dependencies..."
    npm install
    
    echo "🚀 Starting React development server..."
    echo "📊 Creator Portal: http://localhost:3000/creator"
    echo "🤖 AI Portal: http://localhost:3000/ai"
    
    npm start
fi

echo "🎯 Arbiter Platform Demo Ready!"
echo "================================"
echo "Creator Dashboard: Enhanced revenue tracking and content management"
echo "AI Dashboard: Comprehensive license management and usage analytics"
echo "Tech Stack: React 18 + TypeScript + Node.js + PostgreSQL"
echo "Architecture: 8 microservices with Docker orchestration"
