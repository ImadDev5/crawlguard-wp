#!/usr/bin/env node

/**
 * Rules Engine Entry Point
 * Starts the Arbiter Platform Rules Engine microservice
 */

import 'dotenv/config';
import { RulesEngineServer } from './server';

// Handle unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
  console.error('Unhandled Promise Rejection at:', promise, 'reason:', reason);
  process.exit(1);
});

// Handle uncaught exceptions
process.on('uncaughtException', (error) => {
  console.error('Uncaught Exception:', error);
  process.exit(1);
});

// Start the server
async function main() {
  console.log('🚀 Starting Arbiter Platform Rules Engine...');
  console.log(`📊 Environment: ${process.env.NODE_ENV || 'development'}`);
  console.log(`🔗 Redis URL: ${process.env.REDIS_URL || 'redis://localhost:6379'}`);
  
  const server = new RulesEngineServer();
  await server.start();
}

main().catch((error) => {
  console.error('Failed to start Rules Engine:', error);
  process.exit(1);
});
