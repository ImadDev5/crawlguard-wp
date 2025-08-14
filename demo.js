#!/usr/bin/env node

console.log('🚀 Starting Arbiter Platform Demo...');

// Install dependencies
console.log('📦 Installing dependencies...');
require('child_process').execSync('npm install', { stdio: 'inherit' });

// Start services
console.log('🏃 Starting all services...');
require('child_process').spawn('npm', ['run', 'dev'], { 
  stdio: 'inherit',
  shell: true 
});

console.log(`
🎉 Arbiter Platform is starting!

📊 Dashboard: http://localhost:3000
👥 Publishers: http://localhost:3000/publishers  
🤖 AI Companies: http://localhost:3000/ai-companies

🔧 API Gateway: http://localhost:3000/api
🛡️ Bot Detection: http://localhost:3001
💰 Pricing Engine: http://localhost:3002
📋 Licensing: http://localhost:3003
⚡ Workflow: http://localhost:3004
💳 Payments: http://localhost:3005
📈 Analytics: http://localhost:3006
🔔 Notifications: http://localhost:3007

Demo Credentials:
Publisher: demo-publisher@arbiter.ai / demo123
AI Company: demo-ai@arbiter.ai / demo123
Admin: admin@arbiter.ai / admin123
`);
