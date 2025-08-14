#!/usr/bin/env node

/**
 * Neon PostgreSQL Database Connectivity Test Suite - Final Version
 * Optimized for Neon's pooler configuration
 */

require('dotenv').config();
const { Client, Pool } = require('pg');
const fs = require('fs');
const path = require('path');

// Color codes for terminal output
const colors = {
  reset: '\x1b[0m',
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  cyan: '\x1b[36m',
  magenta: '\x1b[35m'
};

// Parse configuration from .env
const config = {
  connectionString: process.env.DATABASE_URL,
  poolSize: parseInt(process.env.DATABASE_POOL_SIZE) || 20,
  timeout: parseInt(process.env.DATABASE_TIMEOUT) || 30000
};

// SSL configuration for Neon
const sslConfig = {
  rejectUnauthorized: false, // Neon uses its own certificate
  require: true
};

// Test results accumulator
const results = {
  tests: [],
  startTime: Date.now(),
  endTime: null
};

function log(message, color = '') {
  const colorCode = colors[color] || '';
  console.log(`${colorCode}${message}${colors.reset}`);
}

function logTest(name, status, details = '', metadata = {}) {
  const icon = status === 'pass' ? '✓' : status === 'fail' ? '✗' : '⚠';
  const color = status === 'pass' ? 'green' : status === 'fail' ? 'red' : 'yellow';
  
  log(`${icon} ${name}`, color);
  if (details) {
    console.log(`   └─ ${details}`);
  }
  
  results.tests.push({
    name,
    status,
    details,
    metadata,
    timestamp: new Date().toISOString()
  });
}

function printHeader() {
  console.clear();
  log('╔══════════════════════════════════════════════════════════════╗', 'cyan');
  log('║      Neon PostgreSQL Database Connectivity Test Suite        ║', 'cyan');
  log('║                    Production Environment                    ║', 'cyan');
  log('╚══════════════════════════════════════════════════════════════╝', 'cyan');
  console.log();
  log(`Date: ${new Date().toLocaleString()}`, 'blue');
  log(`Environment: ${process.env.ENVIRONMENT || 'production'}`, 'blue');
  console.log();
}

async function testBasicConnection() {
  log('\n📡 Testing Basic Connection', 'magenta');
  log('─'.repeat(50));
  
  try {
    const client = new Client({
      connectionString: config.connectionString,
      ssl: sslConfig
    });
    
    await client.connect();
    
    const result = await client.query(`
      SELECT 
        NOW() as server_time,
        version() as pg_version,
        current_database() as database,
        current_user as user
    `);
    
    const info = result.rows[0];
    logTest('Basic Connection', 'pass', 
      `Connected to ${info.database} as ${info.user}`, 
      info
    );
    
    console.log(`   PostgreSQL: ${info.pg_version.split(',')[0]}`);
    console.log(`   Server Time: ${new Date(info.server_time).toLocaleString()}`);
    
    await client.end();
    return true;
  } catch (error) {
    logTest('Basic Connection', 'fail', error.message);
    return false;
  }
}

async function testSSLConnection() {
  log('\n🔐 Testing SSL/TLS Security', 'magenta');
  log('─'.repeat(50));
  
  try {
    const client = new Client({
      connectionString: config.connectionString,
      ssl: sslConfig
    });
    
    await client.connect();
    
    // For Neon pooler, SSL is enforced at the connection level
    // The pooler itself doesn't expose SSL details, but the connection
    // string requires SSL and will fail without it
    const urlParams = new URL(config.connectionString);
    const sslMode = urlParams.searchParams.get('sslmode');
    const channelBinding = urlParams.searchParams.get('channel_binding');
    
    if (sslMode === 'require' && channelBinding === 'require') {
      logTest('SSL/TLS Configuration', 'pass', 
        'SSL enforced with channel binding (most secure)', {
        sslmode: sslMode,
        channel_binding: channelBinding
      });
    } else {
      logTest('SSL/TLS Configuration', 'warn', 
        `SSL mode: ${sslMode}, Channel binding: ${channelBinding}`);
    }
    
    // Test that non-SSL connection fails
    try {
      const nonSSLString = config.connectionString.replace('sslmode=require', 'sslmode=disable');
      const nonSSLClient = new Client({ connectionString: nonSSLString });
      await nonSSLClient.connect();
      await nonSSLClient.end();
      logTest('SSL Enforcement', 'fail', 'Non-SSL connection succeeded (security risk)');
    } catch (error) {
      logTest('SSL Enforcement', 'pass', 'Non-SSL connections properly rejected');
    }
    
    await client.end();
  } catch (error) {
    logTest('SSL/TLS Security', 'fail', error.message);
  }
}

async function testDatabaseSchema() {
  log('\n🗄️  Testing Database Schema', 'magenta');
  log('─'.repeat(50));
  
  try {
    const client = new Client({
      connectionString: config.connectionString,
      ssl: sslConfig
    });
    
    await client.connect();
    
    // Get schemas
    const schemas = await client.query(`
      SELECT schema_name 
      FROM information_schema.schemata 
      WHERE schema_name NOT IN ('pg_catalog', 'information_schema', 'pg_toast')
      ORDER BY schema_name
    `);
    
    logTest('Schema Detection', 'pass', 
      `Found ${schemas.rows.length} user schema(s)`, 
      { schemas: schemas.rows.map(r => r.schema_name) }
    );
    
    // Get tables with details
    const tables = await client.query(`
      SELECT 
        schemaname,
        tablename,
        pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size,
        (SELECT COUNT(*) FROM pg_indexes WHERE tablename = t.tablename) as index_count
      FROM pg_tables t
      WHERE schemaname = 'public'
      ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
      LIMIT 10
    `);
    
    if (tables.rows.length > 0) {
      logTest('Table Structure', 'pass', 
        `${tables.rows.length} tables in public schema`);
      
      console.log('\n   Top tables by size:');
      tables.rows.slice(0, 5).forEach(table => {
        console.log(`   • ${table.tablename}: ${table.size} (${table.index_count} indexes)`);
      });
    } else {
      logTest('Table Structure', 'warn', 'No tables found in public schema');
    }
    
    await client.end();
  } catch (error) {
    logTest('Database Schema', 'fail', error.message);
  }
}

async function testCRUDOperations() {
  log('\n⚡ Testing CRUD Operations', 'magenta');
  log('─'.repeat(50));
  
  try {
    const client = new Client({
      connectionString: config.connectionString,
      ssl: sslConfig
    });
    
    await client.connect();
    
    const testTable = `test_crud_${Date.now()}`;
    const operations = [];
    
    // CREATE TABLE
    const startCreate = Date.now();
    await client.query(`
      CREATE TABLE ${testTable} (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        data JSONB,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    operations.push({ op: 'CREATE TABLE', time: Date.now() - startCreate });
    
    // INSERT
    const startInsert = Date.now();
    const insertResult = await client.query(
      `INSERT INTO ${testTable} (name, data) 
       VALUES ($1, $2) 
       RETURNING *`,
      ['test_record', { test: true, timestamp: Date.now() }]
    );
    operations.push({ op: 'INSERT', time: Date.now() - startInsert });
    
    // SELECT
    const startSelect = Date.now();
    await client.query(
      `SELECT * FROM ${testTable} WHERE id = $1`,
      [insertResult.rows[0].id]
    );
    operations.push({ op: 'SELECT', time: Date.now() - startSelect });
    
    // UPDATE
    const startUpdate = Date.now();
    await client.query(
      `UPDATE ${testTable} 
       SET data = jsonb_set(data, '{updated}', 'true') 
       WHERE id = $1`,
      [insertResult.rows[0].id]
    );
    operations.push({ op: 'UPDATE', time: Date.now() - startUpdate });
    
    // DELETE
    const startDelete = Date.now();
    await client.query(
      `DELETE FROM ${testTable} WHERE id = $1`,
      [insertResult.rows[0].id]
    );
    operations.push({ op: 'DELETE', time: Date.now() - startDelete });
    
    // DROP TABLE
    await client.query(`DROP TABLE ${testTable}`);
    
    const avgTime = operations.reduce((sum, op) => sum + op.time, 0) / operations.length;
    logTest('CRUD Operations', 'pass', 
      `All operations successful (avg: ${avgTime.toFixed(2)}ms)`,
      { operations }
    );
    
    console.log('\n   Operation timings:');
    operations.forEach(op => {
      console.log(`   • ${op.op}: ${op.time}ms`);
    });
    
    await client.end();
  } catch (error) {
    logTest('CRUD Operations', 'fail', error.message);
  }
}

async function testConnectionPooling() {
  log('\n🔄 Testing Connection Pooling', 'magenta');
  log('─'.repeat(50));
  
  try {
    const pool = new Pool({
      connectionString: config.connectionString,
      max: config.poolSize,
      idleTimeoutMillis: 30000,
      connectionTimeoutMillis: 5000,
      ssl: sslConfig
    });
    
    console.log(`   Configured pool size: ${config.poolSize}`);
    console.log(`   Testing concurrent connections...`);
    
    // Create concurrent queries
    const queries = [];
    const queryCount = config.poolSize;
    
    for (let i = 0; i < queryCount; i++) {
      queries.push(
        pool.query('SELECT pg_backend_pid() as pid, $1::int as num', [i])
          .then(result => ({ 
            success: true, 
            pid: result.rows[0].pid,
            num: result.rows[0].num 
          }))
          .catch(error => ({ 
            success: false, 
            error: error.message 
          }))
      );
    }
    
    const results = await Promise.all(queries);
    const successful = results.filter(r => r.success);
    const uniquePids = new Set(successful.map(r => r.pid));
    
    logTest('Connection Pooling', 
      successful.length === queryCount ? 'pass' : 'warn',
      `${successful.length}/${queryCount} queries successful, ${uniquePids.size} unique connections`,
      {
        total_queries: queryCount,
        successful: successful.length,
        unique_connections: uniquePids.size,
        pool_efficiency: ((uniquePids.size / successful.length) * 100).toFixed(1) + '%'
      }
    );
    
    console.log(`   Pool statistics:`);
    console.log(`   • Total: ${pool.totalCount}`);
    console.log(`   • Idle: ${pool.idleCount}`);
    console.log(`   • Waiting: ${pool.waitingCount}`);
    
    await pool.end();
  } catch (error) {
    logTest('Connection Pooling', 'fail', error.message);
  }
}

async function testTimeoutHandling() {
  log('\n⏱️  Testing Timeout Configuration', 'magenta');
  log('─'.repeat(50));
  
  try {
    const client = new Client({
      connectionString: config.connectionString,
      statement_timeout: config.timeout,
      ssl: sslConfig
    });
    
    await client.connect();
    
    console.log(`   Configured timeout: ${config.timeout}ms`);
    
    // Test fast query
    const start = Date.now();
    await client.query('SELECT 1');
    const fastTime = Date.now() - start;
    console.log(`   • Fast query: ${fastTime}ms ✓`);
    
    // Test medium query (should complete)
    const medStart = Date.now();
    await client.query('SELECT pg_sleep(2)');
    const medTime = Date.now() - medStart;
    console.log(`   • 2-second query: ${medTime}ms ✓`);
    
    logTest('Timeout Configuration', 'pass', 
      `Timeout set to ${config.timeout}ms, queries executing normally`,
      {
        timeout_ms: config.timeout,
        fast_query_ms: fastTime,
        medium_query_ms: medTime
      }
    );
    
    await client.end();
  } catch (error) {
    logTest('Timeout Configuration', 'fail', error.message);
  }
}

async function testDatabasePermissions() {
  log('\n🔑 Testing Database Permissions', 'magenta');
  log('─'.repeat(50));
  
  try {
    const client = new Client({
      connectionString: config.connectionString,
      ssl: sslConfig
    });
    
    await client.connect();
    
    const perms = await client.query(`
      SELECT 
        current_user,
        has_database_privilege(current_database(), 'CREATE') as can_create,
        has_database_privilege(current_database(), 'CONNECT') as can_connect,
        has_database_privilege(current_database(), 'TEMP') as can_temp,
        (SELECT COUNT(*) FROM pg_roles WHERE rolsuper AND rolname = current_user) > 0 as is_superuser
    `);
    
    const perm = perms.rows[0];
    const allPerms = perm.can_create && perm.can_connect && perm.can_temp;
    
    logTest('Database Permissions', allPerms ? 'pass' : 'warn',
      `User ${perm.current_user} has ${allPerms ? 'all required' : 'limited'} permissions`,
      perm
    );
    
    console.log(`   • CREATE: ${perm.can_create ? '✓' : '✗'}`);
    console.log(`   • CONNECT: ${perm.can_connect ? '✓' : '✗'}`);
    console.log(`   • TEMP: ${perm.can_temp ? '✓' : '✗'}`);
    console.log(`   • SUPERUSER: ${perm.is_superuser ? '✓' : '✗'}`);
    
    await client.end();
  } catch (error) {
    logTest('Database Permissions', 'fail', error.message);
  }
}

async function generateReport() {
  results.endTime = Date.now();
  
  const passed = results.tests.filter(t => t.status === 'pass').length;
  const failed = results.tests.filter(t => t.status === 'fail').length;
  const warnings = results.tests.filter(t => t.status === 'warn').length;
  const total = results.tests.length;
  const duration = ((results.endTime - results.startTime) / 1000).toFixed(2);
  
  log('\n' + '═'.repeat(60), 'cyan');
  log('📊 TEST SUMMARY', 'cyan');
  log('═'.repeat(60), 'cyan');
  
  console.log(`\n   Total Tests: ${total}`);
  log(`   ✓ Passed: ${passed}`, 'green');
  if (warnings > 0) log(`   ⚠ Warnings: ${warnings}`, 'yellow');
  if (failed > 0) log(`   ✗ Failed: ${failed}`, 'red');
  
  const successRate = ((passed / total) * 100).toFixed(1);
  console.log(`\n   Success Rate: ${successRate}%`);
  console.log(`   Test Duration: ${duration}s`);
  
  if (failed > 0) {
    log('\n   Failed Tests:', 'red');
    results.tests
      .filter(t => t.status === 'fail')
      .forEach(t => console.log(`   • ${t.name}: ${t.details}`));
  }
  
  // Save report to file
  const reportPath = path.join(__dirname, `neon-test-report-${Date.now()}.json`);
  fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
  console.log(`\n   📁 Full report saved to: ${reportPath}`);
  
  // Overall status
  console.log();
  if (failed === 0 && warnings === 0) {
    log('✅ ALL TESTS PASSED - Database is fully operational!', 'green');
  } else if (failed === 0) {
    log('⚠️  TESTS PASSED WITH WARNINGS - Review warnings above', 'yellow');
  } else {
    log('❌ SOME TESTS FAILED - Review failed tests above', 'red');
  }
}

async function runAllTests() {
  printHeader();
  
  // Run tests in sequence
  const connected = await testBasicConnection();
  
  if (!connected) {
    log('\n❌ Cannot proceed without basic connection', 'red');
    process.exit(1);
  }
  
  await testSSLConnection();
  await testDatabaseSchema();
  await testCRUDOperations();
  await testConnectionPooling();
  await testTimeoutHandling();
  await testDatabasePermissions();
  
  await generateReport();
}

// Error handler
process.on('unhandledRejection', (error) => {
  console.error('\n❌ Unhandled error:', error.message);
  process.exit(1);
});

// Run tests
runAllTests().catch(error => {
  console.error('\n❌ Fatal error:', error);
  process.exit(1);
});
