#!/usr/bin/env node

/**
 * Credential Checker Script
 * Validates that all required environment variables are set
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const requiredCredentials = {
  // Critical - Platform won't work without these
  critical: [
    'JWT_SECRET',
    'DATABASE_URL',
    'GOOGLE_CLIENT_ID',
    'GOOGLE_CLIENT_SECRET'
  ],
  
  // Important - Core features need these
  important: [
    'STRIPE_SECRET_KEY',
    'STRIPE_PUBLISHABLE_KEY',
    'SENDGRID_API_KEY',
    'REDIS_URL'
  ],
  
  // Optional - Advanced features
  optional: [
    'AWS_ACCESS_KEY_ID',
    'AWS_SECRET_ACCESS_KEY',
    'KAFKA_BROKERS',
    'ELASTICSEARCH_URL',
    'GITHUB_CLIENT_ID',
    'GITHUB_CLIENT_SECRET'
  ]
};

function loadEnvFile() {
  const envPath = path.join(__dirname, '..', '.env');
  if (!fs.existsSync(envPath)) {
    return false;
  }

  const envContent = fs.readFileSync(envPath, 'utf8');
  const lines = envContent.split('\n');
  
  for (const line of lines) {
    const trimmedLine = line.trim();
    if (trimmedLine && !trimmedLine.startsWith('#')) {
      const [key, ...valueParts] = trimmedLine.split('=');
      if (key && valueParts.length > 0) {
        const value = valueParts.join('=').replace(/^["']|["']$/g, '');
        process.env[key] = value;
      }
    }
  }
  
  return true;
}

function checkCredentials() {
  console.log('🔍 Checking Arbiter Platform Credentials...\n');

  // Check if .env file exists and load it
  const envPath = path.join(__dirname, '..', '.env');
  if (!loadEnvFile()) {
    console.log('❌ .env file not found!');
    console.log('📋 Please copy .env.example to .env and fill in your credentials');
    console.log('📖 See CREDENTIALS_SETUP.md for detailed instructions\n');
    return false;
  }

  let allGood = true;
  let criticalMissing = [];
  let importantMissing = [];
  let optionalMissing = [];

  // Check critical credentials
  console.log('🚨 Critical Credentials (Platform won\'t work without these):');
  for (const cred of requiredCredentials.critical) {
    if (process.env[cred]) {
      console.log(`  ✅ ${cred}: Set`);
    } else {
      console.log(`  ❌ ${cred}: Missing`);
      criticalMissing.push(cred);
      allGood = false;
    }
  }

  // Check important credentials
  console.log('\n🔶 Important Credentials (Core features need these):');
  for (const cred of requiredCredentials.important) {
    if (process.env[cred]) {
      console.log(`  ✅ ${cred}: Set`);
    } else {
      console.log(`  ⚠️  ${cred}: Missing`);
      importantMissing.push(cred);
    }
  }

  // Check optional credentials
  console.log('\n🔹 Optional Credentials (Advanced features):');
  for (const cred of requiredCredentials.optional) {
    if (process.env[cred]) {
      console.log(`  ✅ ${cred}: Set`);
    } else {
      console.log(`  ℹ️  ${cred}: Not set`);
      optionalMissing.push(cred);
    }
  }

  // Summary
  console.log('\n' + '='.repeat(50));
  
  if (criticalMissing.length === 0) {
    console.log('🎉 All critical credentials are set!');
    console.log('🚀 Platform should start successfully');
  } else {
    console.log('🚨 CRITICAL CREDENTIALS MISSING:');
    criticalMissing.forEach(cred => {
      console.log(`   • ${cred}`);
    });
    console.log('\n📖 See CREDENTIALS_SETUP.md for setup instructions');
  }

  if (importantMissing.length > 0) {
    console.log('\n⚠️  Important features will be disabled:');
    importantMissing.forEach(cred => {
      const feature = getFeatureForCredential(cred);
      console.log(`   • ${cred} → ${feature}`);
    });
  }

  if (optionalMissing.length > 0) {
    console.log('\n💡 Optional features available to add:');
    optionalMissing.forEach(cred => {
      const feature = getFeatureForCredential(cred);
      console.log(`   • ${cred} → ${feature}`);
    });
  }

  // Test database connection
  if (process.env.DATABASE_URL) {
    console.log('\n🗄️  Testing database connection...');
    testDatabaseConnection();
  }

  // Test Redis connection
  if (process.env.REDIS_URL) {
    console.log('🟥 Testing Redis connection...');
    testRedisConnection();
  }

  console.log('\n📋 Next steps:');
  if (criticalMissing.length > 0) {
    console.log('1. Add missing critical credentials to .env');
    console.log('2. Run this script again to verify');
  } else {
    console.log('1. Start the platform: npm run dev');
    console.log('2. Open http://localhost:3000');
  }

  return allGood;
}

function getFeatureForCredential(cred) {
  const features = {
    'STRIPE_SECRET_KEY': 'Payment processing',
    'STRIPE_PUBLISHABLE_KEY': 'Payment UI',
    'SENDGRID_API_KEY': 'Email notifications',
    'REDIS_URL': 'Caching & performance',
    'AWS_ACCESS_KEY_ID': 'File uploads to S3',
    'AWS_SECRET_ACCESS_KEY': 'File uploads to S3',
    'KAFKA_BROKERS': 'Event streaming',
    'ELASTICSEARCH_URL': 'Advanced search',
    'GITHUB_CLIENT_ID': 'GitHub OAuth login',
    'GITHUB_CLIENT_SECRET': 'GitHub OAuth login',
    'GOOGLE_CLIENT_ID': 'Google OAuth login',
    'GOOGLE_CLIENT_SECRET': 'Google OAuth login'
  };
  return features[cred] || 'Unknown feature';
}

function testDatabaseConnection() {
  try {
    // Simple URL validation
    const url = new URL(process.env.DATABASE_URL);
    if (url.protocol === 'postgresql:' || url.protocol === 'postgres:') {
      console.log('  ✅ Database URL format looks correct');
    } else {
      console.log('  ⚠️  Database URL should start with postgresql://');
    }
  } catch (error) {
    console.log('  ❌ Invalid database URL format');
  }
}

function testRedisConnection() {
  try {
    const url = process.env.REDIS_URL;
    if (url.startsWith('redis://') || url.startsWith('rediss://')) {
      console.log('  ✅ Redis URL format looks correct');
    } else {
      console.log('  ⚠️  Redis URL should start with redis://');
    }
  } catch (error) {
    console.log('  ❌ Invalid Redis URL format');
  }
}

// Run the check
checkCredentials();
