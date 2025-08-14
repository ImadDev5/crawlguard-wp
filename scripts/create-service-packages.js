#!/usr/bin/env node

/**
 * Automated Setup Script for Arbiter Platform
 * Creates all service package.json files and sets up the development environment
 */

const fs = require('fs');
const path = require('path');

const services = [
  { name: 'api-gateway', port: 3000 },
  { name: 'bot-detection', port: 3001, dir: 'services/bot-detection' },
  { name: 'pricing-engine', port: 3002, dir: 'services/pricing-engine' },
  { name: 'content-licensing', port: 3003, dir: 'services/content-licensing' },
  { name: 'workflow-engine', port: 3004, dir: 'services/workflow-engine' },
  { name: 'payment-processing', port: 3005, dir: 'services/payment-processing' },
  { name: 'analytics', port: 3006, dir: 'services/analytics' },
  { name: 'notification', port: 3007, dir: 'services/notification' }
];

function createServicePackage(service) {
  const dir = service.dir || service.name;
  const fullPath = path.join('arbiter-platform', dir);
  
  // Create directory structure
  fs.mkdirSync(fullPath, { recursive: true });
  fs.mkdirSync(path.join(fullPath, 'src'), { recursive: true });
  fs.mkdirSync(path.join(fullPath, 'dist'), { recursive: true });
  
  const packageJson = {
    name: `@arbiter/service-${service.name}`,
    version: '1.0.0',
    description: `Arbiter Platform - ${service.name.charAt(0).toUpperCase() + service.name.slice(1)} Service`,
    main: 'dist/index.js',
    scripts: {
      dev: 'nodemon --exec ts-node src/index.ts',
      build: 'tsc',
      start: 'node dist/index.js',
      test: 'jest',
      'test:watch': 'jest --watch'
    },
    dependencies: {
      express: '^4.18.2',
      cors: '^2.8.5',
      helmet: '^7.1.0',
      dotenv: '^16.3.1',
      winston: '^3.11.0',
      joi: '^17.11.0'
    },
    devDependencies: {
      '@types/node': '^20.10.0',
      '@types/express': '^4.17.21',
      '@types/cors': '^2.8.17',
      '@types/jest': '^29.5.8',
      typescript: '^5.3.2',
      'ts-node': '^10.9.1',
      nodemon: '^3.0.2',
      jest: '^29.7.0',
      'ts-jest': '^29.1.1'
    }
  };

  // Add service-specific dependencies
  switch (service.name) {
    case 'api-gateway':
      packageJson.dependencies = {
        ...packageJson.dependencies,
        '@apollo/server': '^4.9.5',
        'graphql': '^16.8.1',
        'jsonwebtoken': '^9.0.2',
        'bcryptjs': '^2.4.3',
        'redis': '^4.6.11',
        'express-rate-limit': '^7.1.5'
      };
      break;
    case 'bot-detection':
      packageJson.dependencies = {
        ...packageJson.dependencies,
        '@tensorflow/tfjs-node': '^4.15.0',
        'redis': '^4.6.11',
        'axios': '^1.6.2'
      };
      break;
    case 'payment-processing':
      packageJson.dependencies = {
        ...packageJson.dependencies,
        'stripe': '^14.7.0',
        'pg': '^8.11.3'
      };
      break;
    case 'analytics':
      packageJson.dependencies = {
        ...packageJson.dependencies,
        'pg': '^8.11.3',
        'redis': '^4.6.11'
      };
      break;
    case 'notification':
      packageJson.dependencies = {
        ...packageJson.dependencies,
        'nodemailer': '^6.9.7',
        'socket.io': '^4.7.4'
      };
      break;
  }

  fs.writeFileSync(
    path.join(fullPath, 'package.json'),
    JSON.stringify(packageJson, null, 2)
  );

  // Create TypeScript config
  const tsConfig = {
    compilerOptions: {
      target: 'ES2020',
      module: 'commonjs',
      lib: ['ES2020'],
      outDir: './dist',
      rootDir: './src',
      strict: true,
      esModuleInterop: true,
      skipLibCheck: true,
      forceConsistentCasingInFileNames: true,
      resolveJsonModule: true,
      declaration: true,
      declarationMap: true,
      sourceMap: true
    },
    include: ['src/**/*'],
    exclude: ['node_modules', 'dist']
  };

  fs.writeFileSync(
    path.join(fullPath, 'tsconfig.json'),
    JSON.stringify(tsConfig, null, 2)
  );

  // Create basic server file
  const serverCode = `import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import { config } from 'dotenv';

config();

const app = express();
const PORT = process.env.PORT || ${service.port};

// Middleware
app.use(helmet());
app.use(cors());
app.use(express.json());

// Health check
app.get('/health', (req, res) => {
  res.json({ 
    service: '${service.name}',
    status: 'healthy',
    timestamp: new Date().toISOString()
  });
});

// Routes
app.get('/', (req, res) => {
  res.json({
    service: '${service.name}',
    version: '1.0.0',
    description: 'Arbiter Platform ${service.name.charAt(0).toUpperCase() + service.name.slice(1)} Service'
  });
});

app.listen(PORT, () => {
  console.log(\`🚀 \${service.name} service running on port \${PORT}\`);
});
`;

  fs.writeFileSync(path.join(fullPath, 'src', 'index.ts'), serverCode);

  console.log(`✅ Created ${service.name} service package`);
}

function createFrontendApp() {
  const frontendPath = path.join('arbiter-platform', 'frontend');
  
  fs.mkdirSync(frontendPath, { recursive: true });
  fs.mkdirSync(path.join(frontendPath, 'src'), { recursive: true });
  fs.mkdirSync(path.join(frontendPath, 'public'), { recursive: true });

  const packageJson = {
    name: '@arbiter/frontend',
    version: '1.0.0',
    description: 'Arbiter Platform Frontend Applications',
    scripts: {
      dev: 'vite',
      build: 'vite build',
      preview: 'vite preview',
      test: 'vitest'
    },
    dependencies: {
      react: '^18.2.0',
      'react-dom': '^18.2.0',
      'react-router-dom': '^6.20.1',
      axios: '^1.6.2',
      '@heroicons/react': '^2.0.18',
      'tailwindcss': '^3.3.0'
    },
    devDependencies: {
      '@types/react': '^18.2.37',
      '@types/react-dom': '^18.2.15',
      '@vitejs/plugin-react': '^4.1.1',
      vite: '^4.5.0',
      vitest: '^0.34.6',
      autoprefixer: '^10.4.16',
      postcss: '^8.4.31'
    }
  };

  fs.writeFileSync(
    path.join(frontendPath, 'package.json'),
    JSON.stringify(packageJson, null, 2)
  );

  // Create Vite config
  const viteConfig = `import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000,
    host: true
  },
  build: {
    outDir: 'dist'
  }
})
`;

  fs.writeFileSync(path.join(frontendPath, 'vite.config.ts'), viteConfig);

  // Create index.html
  const indexHtml = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Arbiter Platform</title>
  <style>
    body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  </style>
</head>
<body>
  <div id="root"></div>
  <script type="module" src="/src/main.tsx"></script>
</body>
</html>`;

  fs.writeFileSync(path.join(frontendPath, 'index.html'), indexHtml);

  // Create React app
  const mainTsx = `import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
)`;

  fs.writeFileSync(path.join(frontendPath, 'src', 'main.tsx'), mainTsx);

  const appTsx = `import React from 'react'
import { BrowserRouter as Router, Routes, Route, Link } from 'react-router-dom'

const Dashboard = () => (
  <div className="p-8">
    <h1 className="text-3xl font-bold mb-6 text-blue-600">Arbiter Platform Dashboard</h1>
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div className="bg-white p-6 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-2">Total Revenue</h3>
        <p className="text-3xl font-bold text-green-600">$12,543</p>
        <p className="text-sm text-gray-500">+15% from last month</p>
      </div>
      <div className="bg-white p-6 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-2">Active Licenses</h3>
        <p className="text-3xl font-bold text-blue-600">1,247</p>
        <p className="text-sm text-gray-500">+8% from last month</p>
      </div>
      <div className="bg-white p-6 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-2">API Calls</h3>
        <p className="text-3xl font-bold text-purple-600">89,432</p>
        <p className="text-sm text-gray-500">+23% from last month</p>
      </div>
    </div>
  </div>
)

const Publishers = () => (
  <div className="p-8">
    <h1 className="text-3xl font-bold mb-6">Publisher Portal</h1>
    <div className="bg-white p-6 rounded-lg shadow">
      <h2 className="text-xl font-semibold mb-4">Upload Content</h2>
      <p className="text-gray-600 mb-4">Upload your content to start monetizing AI usage</p>
      <button className="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
        Upload Content
      </button>
    </div>
  </div>
)

const AICompanies = () => (
  <div className="p-8">
    <h1 className="text-3xl font-bold mb-6">AI Company Portal</h1>
    <div className="bg-white p-6 rounded-lg shadow">
      <h2 className="text-xl font-semibold mb-4">Browse Content</h2>
      <p className="text-gray-600 mb-4">Find and license content for your AI training</p>
      <button className="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
        Browse Content
      </button>
    </div>
  </div>
)

const Navigation = () => (
  <nav className="bg-blue-600 text-white p-4">
    <div className="container mx-auto flex justify-between items-center">
      <h1 className="text-xl font-bold">Arbiter Platform</h1>
      <div className="space-x-4">
        <Link to="/" className="hover:underline">Dashboard</Link>
        <Link to="/publishers" className="hover:underline">Publishers</Link>
        <Link to="/ai-companies" className="hover:underline">AI Companies</Link>
      </div>
    </div>
  </nav>
)

function App() {
  return (
    <Router>
      <div className="min-h-screen bg-gray-100">
        <Navigation />
        <Routes>
          <Route path="/" element={<Dashboard />} />
          <Route path="/publishers" element={<Publishers />} />
          <Route path="/ai-companies" element={<AICompanies />} />
        </Routes>
      </div>
    </Router>
  )
}

export default App`;

  fs.writeFileSync(path.join(frontendPath, 'src', 'App.tsx'), appTsx);

  const indexCss = `@tailwind base;
@tailwind components;
@tailwind utilities;

body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
    sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}`;

  fs.writeFileSync(path.join(frontendPath, 'src', 'index.css'), indexCss);

  // Create Tailwind config
  const tailwindConfig = `module.exports = {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}`;

  fs.writeFileSync(path.join(frontendPath, 'tailwind.config.js'), tailwindConfig);

  console.log('✅ Created frontend application');
}

function createDockerFiles() {
  const dockerCompose = `version: '3.8'

services:
  # Database Services
  postgres:
    image: postgres:15
    environment:
      POSTGRES_DB: arbiter_platform
      POSTGRES_USER: arbiter
      POSTGRES_PASSWORD: arbiter123
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./database:/docker-entrypoint-initdb.d
    ports:
      - "5432:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U arbiter"]
      interval: 30s
      timeout: 10s
      retries: 3

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 30s
      timeout: 10s
      retries: 3

  # API Gateway
  api-gateway:
    build:
      context: ./arbiter-platform/api-gateway
      dockerfile: Dockerfile
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=development
      - PORT=3000
      - REDIS_URL=redis://redis:6379
      - DATABASE_URL=postgresql://arbiter:arbiter123@postgres:5432/arbiter_platform
    depends_on:
      - postgres
      - redis
    volumes:
      - ./arbiter-platform/api-gateway:/app
      - /app/node_modules

  # Microservices
  bot-detection:
    build:
      context: ./arbiter-platform/services/bot-detection
      dockerfile: Dockerfile
    ports:
      - "3001:3001"
    environment:
      - NODE_ENV=development
      - PORT=3001
      - REDIS_URL=redis://redis:6379
    depends_on:
      - redis
    volumes:
      - ./arbiter-platform/services/bot-detection:/app
      - /app/node_modules

  pricing-engine:
    build:
      context: ./arbiter-platform/services/pricing-engine
      dockerfile: Dockerfile
    ports:
      - "3002:3002"
    environment:
      - NODE_ENV=development
      - PORT=3002
    volumes:
      - ./arbiter-platform/services/pricing-engine:/app
      - /app/node_modules

  # Frontend
  frontend:
    build:
      context: ./arbiter-platform/frontend
      dockerfile: Dockerfile
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=development
    volumes:
      - ./arbiter-platform/frontend:/app
      - /app/node_modules
    depends_on:
      - api-gateway

volumes:
  postgres_data:
  redis_data:
`;

  fs.writeFileSync('docker-compose.yml', dockerCompose);

  // Create Dockerfiles for each service
  const dockerfile = `FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

EXPOSE 3000

CMD ["npm", "start"]
`;

  // Create dockerfiles for each service directory
  services.forEach(service => {
    const dir = service.dir || service.name;
    const dockerPath = path.join('arbiter-platform', dir, 'Dockerfile');
    fs.writeFileSync(dockerPath, dockerfile.replace('3000', service.port.toString()));
  });

  fs.writeFileSync(path.join('arbiter-platform', 'frontend', 'Dockerfile'), dockerfile);

  console.log('✅ Created Docker configuration');
}

function createDemoScript() {
  const demoScript = `#!/usr/bin/env node

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

console.log(\`
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
\`);
`;

  fs.writeFileSync('demo.js', demoScript);
  fs.chmodSync('demo.js', '755');

  console.log('✅ Created demo script');
}

// Main execution
console.log('🏗️  Setting up Arbiter Platform...\n');

// Create base directory
fs.mkdirSync('arbiter-platform', { recursive: true });

// Create all services
services.forEach(createServicePackage);

// Create frontend application
createFrontendApp();

// Create Docker configuration
createDockerFiles();

// Create demo script
createDemoScript();

console.log('\n✨ Arbiter Platform setup complete!');
console.log('\n🚀 To start the demo:');
console.log('   node demo.js');
console.log('\n🐳 Or with Docker:');
console.log('   docker-compose up');
console.log('\n📖 Visit: http://localhost:3000');
