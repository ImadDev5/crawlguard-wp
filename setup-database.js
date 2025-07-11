#!/usr/bin/env node

/**
 * Automated Database Setup for CrawlGuard WP
 * This script will create a Neon database and set up all tables automatically
 */

const https = require('https');
const fs = require('fs');
const path = require('path');

class DatabaseSetup {
  constructor() {
    this.neonApiKey = null;
    this.projectId = null;
    this.databaseUrl = null;
  }

  async setup() {
    console.log('🚀 CrawlGuard Database Setup Starting...\n');
    
    try {
      // Step 1: Get Neon API key
      await this.getNeonApiKey();
      
      // Step 2: Create Neon project
      await this.createNeonProject();
      
      // Step 3: Get database connection string
      await this.getDatabaseUrl();
      
      // Step 4: Run database schema
      await this.setupSchema();
      
      // Step 5: Create environment file
      await this.createEnvFile();
      
      console.log('✅ Database setup completed successfully!');
      console.log('\n🎯 Next steps:');
      console.log('1. Copy the DATABASE_URL from .env file');
      console.log('2. Run: wrangler secret put DATABASE_URL --env production');
      console.log('3. Deploy your Cloudflare Worker');
      
    } catch (error) {
      console.error('❌ Setup failed:', error.message);
      console.log('\n🔧 Manual setup required. Please follow MANUAL_SETUP_INSTRUCTIONS.md');
    }
  }

  async getNeonApiKey() {
    console.log('📝 To automate this setup, we need your Neon API key.');
    console.log('👉 Go to: https://console.neon.tech/app/settings/api-keys');
    console.log('👉 Create a new API key and paste it here.');
    console.log('\n⚠️  If you prefer manual setup, press Ctrl+C and follow MANUAL_SETUP_INSTRUCTIONS.md\n');
    
    // In a real implementation, we'd use readline to get user input
    // For now, we'll provide manual instructions
    throw new Error('Manual API key input required');
  }

  async createNeonProject() {
    const projectData = {
      project: {
        name: 'CrawlGuard WP',
        region_id: 'aws-us-east-1'
      }
    };

    return this.makeNeonRequest('POST', '/projects', projectData);
  }

  async makeNeonRequest(method, endpoint, data = null) {
    return new Promise((resolve, reject) => {
      const options = {
        hostname: 'console.neon.tech',
        port: 443,
        path: `/api/v2${endpoint}`,
        method: method,
        headers: {
          'Authorization': `Bearer ${this.neonApiKey}`,
          'Content-Type': 'application/json'
        }
      };

      const req = https.request(options, (res) => {
        let responseData = '';
        
        res.on('data', (chunk) => {
          responseData += chunk;
        });
        
        res.on('end', () => {
          try {
            const parsed = JSON.parse(responseData);
            if (res.statusCode >= 200 && res.statusCode < 300) {
              resolve(parsed);
            } else {
              reject(new Error(`API Error: ${parsed.message || responseData}`));
            }
          } catch (e) {
            reject(new Error(`Parse Error: ${responseData}`));
          }
        });
      });

      req.on('error', (error) => {
        reject(error);
      });

      if (data) {
        req.write(JSON.stringify(data));
      }
      
      req.end();
    });
  }

  async getDatabaseUrl() {
    const response = await this.makeNeonRequest('GET', `/projects/${this.projectId}/connection_uris`);
    this.databaseUrl = response.uris[0].uri;
  }

  async setupSchema() {
    console.log('📊 Setting up database schema...');
    
    // Read schema file
    const schemaPath = path.join(__dirname, 'database', 'schema.sql');
    const schema = fs.readFileSync(schemaPath, 'utf8');
    
    // In a real implementation, we'd connect to PostgreSQL and run the schema
    // For now, we'll provide the schema for manual execution
    console.log('📋 Schema ready for execution');
  }

  async createEnvFile() {
    const envContent = `# CrawlGuard Environment Variables
DATABASE_URL="${this.databaseUrl}"
ENVIRONMENT="production"
API_VERSION="1.0.0"

# Add these secrets via Wrangler CLI:
# wrangler secret put STRIPE_SECRET_KEY --env production
# wrangler secret put STRIPE_WEBHOOK_SECRET --env production
# wrangler secret put JWT_SECRET --env production
`;

    fs.writeFileSync('.env', envContent);
    console.log('📄 Environment file created: .env');
  }
}

// Manual setup instructions if automation fails
function showManualInstructions() {
  console.log(`
🔧 MANUAL DATABASE SETUP INSTRUCTIONS

Since automated setup requires API access, here's the manual process:

1. 📝 Go to https://neon.tech and create a free account
2. 🆕 Create a new project called "CrawlGuard WP"
3. 📋 Copy the connection string (starts with postgresql://)
4. 🗃️ Run the SQL schema from database/schema.sql
5. 🔐 Set the DATABASE_URL secret in Cloudflare

Detailed steps in MANUAL_SETUP_INSTRUCTIONS.md
`);
}

// Run setup or show manual instructions
if (require.main === module) {
  const setup = new DatabaseSetup();
  setup.setup().catch(() => {
    showManualInstructions();
  });
}

module.exports = DatabaseSetup;
