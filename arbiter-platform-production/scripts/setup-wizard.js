#!/usr/bin/env node

/**
 * Arbiter Platform Setup Wizard
 * Interactive setup for first-time users
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function setupWizard() {
  console.log('🏛️  Welcome to Arbiter Platform Setup!\n');
  
  console.log('This wizard will help you get started quickly.\n');
  
  // Check if .env exists
  const envPath = path.join(__dirname, '..', '.env');
  const envExamplePath = path.join(__dirname, '..', '.env.example');
  
  if (!fs.existsSync(envPath)) {
    console.log('📋 Creating .env file from template...');
    
    if (fs.existsSync(envExamplePath)) {
      fs.copyFileSync(envExamplePath, envPath);
      console.log('✅ .env file created successfully!\n');
    } else {
      console.log('❌ .env.example not found. Creating basic .env file...');
      createBasicEnvFile(envPath);
    }
  } else {
    console.log('✅ .env file already exists\n');
  }
  
  console.log('🔐 NEXT STEPS:\n');
  console.log('1. 📖 Read the credential setup guide:');
  console.log('   👉 Open CREDENTIALS_SETUP.md\n');
  
  console.log('2. 🔑 Get your API keys and credentials:');
  console.log('   • JWT_SECRET: Generate a secure 32-character secret');
  console.log('   • DATABASE_URL: Set up PostgreSQL (local or Supabase)');
  console.log('   • GOOGLE_CLIENT_ID/SECRET: Set up Google OAuth');
  console.log('   • STRIPE_SECRET_KEY: Set up Stripe for payments');
  console.log('   • SENDGRID_API_KEY: Set up SendGrid for emails\n');
  
  console.log('3. ✅ Validate your setup:');
  console.log('   npm run check-credentials\n');
  
  console.log('4. 🚀 Start the platform:');
  console.log('   npm run dev\n');
  
  console.log('💡 Quick testing setup:');
  console.log('   - The current .env has basic values for testing');
  console.log('   - Replace with real credentials for full functionality');
  console.log('   - See CREDENTIALS_SETUP.md for detailed instructions\n');
  
  console.log('🆘 Need help?');
  console.log('   - Check README.md for overview');
  console.log('   - Read CREDENTIALS_SETUP.md for setup details');
  console.log('   - Run "npm run check-credentials" to validate\n');
  
  console.log('🎉 Setup complete! Ready to start building!\n');
}

function createBasicEnvFile(envPath) {
  const basicEnv = `# Arbiter Platform Environment Variables
# Replace these with real values - see CREDENTIALS_SETUP.md

JWT_SECRET="dev_test_secret_change_this_32_chars"
DATABASE_URL="postgresql://postgres:password@localhost:5432/arbiter_dev"
GOOGLE_CLIENT_ID="your_google_client_id"
GOOGLE_CLIENT_SECRET="your_google_client_secret"
REDIS_URL="redis://localhost:6379"
NODE_ENV="development"
RULES_ENGINE_PORT="3020"
API_GATEWAY_PORT="3000"
FRONTEND_URL="http://localhost:3008"
`;
  
  fs.writeFileSync(envPath, basicEnv);
  console.log('✅ Basic .env file created\n');
}

// Run the setup wizard
setupWizard();
