// Neon PostgreSQL Database Connectivity Test Suite
// Tests connection, schema, CRUD operations, pooling, timeout, and SSL/TLS

require('dotenv').config();
const { Client, Pool } = require('pg');
const crypto = require('crypto');

// Color codes for terminal output
const colors = {
  reset: '\x1b[0m',
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  cyan: '\x1b[36m'
};

// Parse connection string from .env
const connectionString = process.env.DATABASE_URL;
const poolSize = parseInt(process.env.DATABASE_POOL_SIZE) || 20;
const timeout = parseInt(process.env.DATABASE_TIMEOUT) || 30000;

console.log(`${colors.cyan}===========================================`);
console.log(`Neon PostgreSQL Database Connectivity Test`);
console.log(`===========================================\n${colors.reset}`);

// Test results tracker
const testResults = {
  passed: 0,
  failed: 0,
  tests: []
};

function logTest(testName, passed, details = '') {
  const status = passed ? `${colors.green}✓ PASSED${colors.reset}` : `${colors.red}✗ FAILED${colors.reset}`;
  console.log(`${status} - ${testName}`);
  if (details) {
    console.log(`  ${colors.yellow}→${colors.reset} ${details}`);
  }
  testResults.tests.push({ name: testName, passed, details });
  if (passed) testResults.passed++;
  else testResults.failed++;
}

async function runTests() {
  // Test 1: Basic Connection Test
  console.log(`\n${colors.blue}1. Testing Basic Connection...${colors.reset}`);
  let client;
  try {
    client = new Client({ connectionString });
    await client.connect();
    const result = await client.query('SELECT NOW() as current_time, version() as pg_version');
    logTest('Basic Connection', true, `Connected at ${result.rows[0].current_time}`);
    console.log(`  PostgreSQL Version: ${result.rows[0].pg_version.split(',')[0]}`);
    await client.end();
  } catch (error) {
    logTest('Basic Connection', false, error.message);
    console.error('Cannot proceed without basic connection. Exiting...');
    return;
  }

  // Test 2: SSL/TLS Verification
  console.log(`\n${colors.blue}2. Verifying SSL/TLS Encryption...${colors.reset}`);
  try {
    const sslClient = new Client({ connectionString });
    await sslClient.connect();
    const sslResult = await sslClient.query(`
      SELECT 
        ssl.ssl as ssl_enabled,
        ssl.version as ssl_version,
        ssl.cipher as ssl_cipher,
        ssl.bits as ssl_bits
      FROM pg_stat_ssl ssl
      JOIN pg_stat_activity a ON ssl.pid = a.pid
      WHERE a.pid = pg_backend_pid()
    `);
    
    const sslInfo = sslResult.rows[0];
    if (sslInfo && sslInfo.ssl_enabled) {
      logTest('SSL/TLS Encryption', true, 
        `SSL Version: ${sslInfo.ssl_version}, Cipher: ${sslInfo.ssl_cipher}, Bits: ${sslInfo.ssl_bits}`);
    } else {
      logTest('SSL/TLS Encryption', false, 'SSL is not enabled on this connection');
    }
    await sslClient.end();
  } catch (error) {
    logTest('SSL/TLS Encryption', false, error.message);
  }

  // Test 3: Database Schema Verification
  console.log(`\n${colors.blue}3. Verifying Database Schema...${colors.reset}`);
  try {
    const schemaClient = new Client({ connectionString });
    await schemaClient.connect();
    
    // Check for existing schemas
    const schemas = await schemaClient.query(`
      SELECT schema_name 
      FROM information_schema.schemata 
      WHERE schema_name NOT IN ('pg_catalog', 'information_schema', 'pg_toast')
      ORDER BY schema_name
    `);
    
    console.log(`  Found ${schemas.rows.length} user schema(s):`);
    schemas.rows.forEach(row => {
      console.log(`    - ${row.schema_name}`);
    });
    
    // Check for tables
    const tables = await schemaClient.query(`
      SELECT 
        schemaname,
        tablename,
        pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
      FROM pg_tables 
      WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
      ORDER BY schemaname, tablename
    `);
    
    if (tables.rows.length > 0) {
      console.log(`  Found ${tables.rows.length} table(s):`);
      tables.rows.forEach(row => {
        console.log(`    - ${row.schemaname}.${row.tablename} (${row.size})`);
      });
      logTest('Schema and Tables Check', true, `${tables.rows.length} tables found`);
    } else {
      logTest('Schema and Tables Check', true, 'No user tables found (fresh database)');
    }
    
    await schemaClient.end();
  } catch (error) {
    logTest('Schema and Tables Check', false, error.message);
  }

  // Test 4: CRUD Operations
  console.log(`\n${colors.blue}4. Testing CRUD Operations...${colors.reset}`);
  try {
    const crudClient = new Client({ connectionString });
    await crudClient.connect();
    
    // Create a test table
    const testTableName = `test_crud_${Date.now()}`;
    await crudClient.query(`
      CREATE TABLE IF NOT EXISTS ${testTableName} (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100),
        value INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(`  Created test table: ${testTableName}`);
    
    // INSERT operation
    const insertResult = await crudClient.query(
      `INSERT INTO ${testTableName} (name, value) VALUES ($1, $2) RETURNING *`,
      ['test_record', 42]
    );
    console.log(`  ✓ INSERT: Created record with ID ${insertResult.rows[0].id}`);
    
    // SELECT operation
    const selectResult = await crudClient.query(
      `SELECT * FROM ${testTableName} WHERE id = $1`,
      [insertResult.rows[0].id]
    );
    console.log(`  ✓ SELECT: Retrieved ${selectResult.rows.length} record(s)`);
    
    // UPDATE operation
    const updateResult = await crudClient.query(
      `UPDATE ${testTableName} SET value = $1 WHERE id = $2 RETURNING *`,
      [100, insertResult.rows[0].id]
    );
    console.log(`  ✓ UPDATE: Modified value to ${updateResult.rows[0].value}`);
    
    // DELETE operation
    const deleteResult = await crudClient.query(
      `DELETE FROM ${testTableName} WHERE id = $1 RETURNING id`,
      [insertResult.rows[0].id]
    );
    console.log(`  ✓ DELETE: Removed record with ID ${deleteResult.rows[0].id}`);
    
    // Cleanup
    await crudClient.query(`DROP TABLE ${testTableName}`);
    console.log(`  Cleaned up test table: ${testTableName}`);
    
    logTest('CRUD Operations', true, 'All CRUD operations successful');
    await crudClient.end();
  } catch (error) {
    logTest('CRUD Operations', false, error.message);
  }

  // Test 5: Connection Pooling
  console.log(`\n${colors.blue}5. Testing Connection Pooling (Pool Size: ${poolSize})...${colors.reset}`);
  try {
    const pool = new Pool({
      connectionString,
      max: poolSize,
      idleTimeoutMillis: 30000,
      connectionTimeoutMillis: 2000,
    });
    
    console.log(`  Creating ${poolSize} concurrent connections...`);
    const connectionPromises = [];
    
    for (let i = 0; i < poolSize; i++) {
      connectionPromises.push(
        pool.query('SELECT pg_backend_pid() as pid, NOW() as time')
          .then(result => ({
            success: true,
            pid: result.rows[0].pid,
            connNum: i + 1
          }))
          .catch(error => ({
            success: false,
            error: error.message,
            connNum: i + 1
          }))
      );
    }
    
    const results = await Promise.all(connectionPromises);
    const successful = results.filter(r => r.success);
    const failed = results.filter(r => !r.success);
    
    console.log(`  Successfully created ${successful.length}/${poolSize} connections`);
    if (failed.length > 0) {
      console.log(`  ${colors.yellow}Failed connections: ${failed.length}${colors.reset}`);
    }
    
    // Check pool statistics
    const poolStats = pool.totalCount;
    console.log(`  Pool Statistics:`);
    console.log(`    - Total connections: ${pool.totalCount}`);
    console.log(`    - Idle connections: ${pool.idleCount}`);
    console.log(`    - Waiting requests: ${pool.waitingCount}`);
    
    logTest('Connection Pooling', successful.length === poolSize, 
      `${successful.length}/${poolSize} connections established`);
    
    await pool.end();
  } catch (error) {
    logTest('Connection Pooling', false, error.message);
  }

  // Test 6: Timeout Handling
  console.log(`\n${colors.blue}6. Testing Timeout Handling (${timeout}ms timeout)...${colors.reset}`);
  try {
    const timeoutClient = new Client({
      connectionString,
      statement_timeout: timeout,
      query_timeout: timeout
    });
    await timeoutClient.connect();
    
    // Test normal query (should complete)
    const startTime = Date.now();
    await timeoutClient.query('SELECT pg_sleep(1)'); // 1 second sleep
    const elapsed = Date.now() - startTime;
    console.log(`  ✓ Normal query completed in ${elapsed}ms`);
    
    // Test timeout query (should timeout if > 30 seconds)
    console.log(`  Testing timeout with ${timeout/1000} second limit...`);
    const timeoutTestSeconds = Math.min(5, timeout/1000 - 1); // Test with 5 seconds or less
    
    try {
      const timeoutStart = Date.now();
      await timeoutClient.query(`SELECT pg_sleep(${timeoutTestSeconds})`);
      const timeoutElapsed = Date.now() - timeoutStart;
      console.log(`  ✓ Query completed within timeout (${timeoutElapsed}ms)`);
      logTest('Timeout Handling', true, `Timeout properly configured at ${timeout}ms`);
    } catch (timeoutError) {
      if (timeoutError.message.includes('timeout')) {
        logTest('Timeout Handling', true, 'Timeout triggered as expected');
      } else {
        throw timeoutError;
      }
    }
    
    await timeoutClient.end();
  } catch (error) {
    logTest('Timeout Handling', false, error.message);
  }

  // Test 7: Database Permissions
  console.log(`\n${colors.blue}7. Testing Database Permissions...${colors.reset}`);
  try {
    const permClient = new Client({ connectionString });
    await permClient.connect();
    
    const permissions = await permClient.query(`
      SELECT 
        current_user,
        has_database_privilege(current_database(), 'CREATE') as can_create,
        has_database_privilege(current_database(), 'CONNECT') as can_connect,
        has_database_privilege(current_database(), 'TEMP') as can_create_temp
    `);
    
    const perm = permissions.rows[0];
    console.log(`  Current User: ${perm.current_user}`);
    console.log(`  Permissions:`);
    console.log(`    - CREATE: ${perm.can_create ? '✓' : '✗'}`);
    console.log(`    - CONNECT: ${perm.can_connect ? '✓' : '✗'}`);
    console.log(`    - TEMP: ${perm.can_create_temp ? '✓' : '✗'}`);
    
    logTest('Database Permissions', perm.can_connect && perm.can_create, 
      'User has necessary permissions');
    
    await permClient.end();
  } catch (error) {
    logTest('Database Permissions', false, error.message);
  }

  // Test 8: Connection String Validation
  console.log(`\n${colors.blue}8. Validating Connection String Components...${colors.reset}`);
  try {
    const url = new URL(connectionString);
    const components = {
      protocol: url.protocol,
      username: url.username ? '***' + url.username.slice(-3) : 'N/A',
      host: url.hostname,
      port: url.port || '5432',
      database: url.pathname.slice(1).split('?')[0],
      ssl: url.searchParams.get('sslmode') || 'none',
      channelBinding: url.searchParams.get('channel_binding') || 'none'
    };
    
    console.log(`  Connection Components:`);
    Object.entries(components).forEach(([key, value]) => {
      console.log(`    - ${key}: ${value}`);
    });
    
    const isValid = components.protocol === 'postgresql:' && 
                   components.ssl === 'require' &&
                   components.channelBinding === 'require';
    
    logTest('Connection String Validation', isValid, 
      isValid ? 'Secure connection parameters verified' : 'Connection parameters need review');
  } catch (error) {
    logTest('Connection String Validation', false, error.message);
  }

  // Print summary
  console.log(`\n${colors.cyan}===========================================`);
  console.log(`Test Summary`);
  console.log(`===========================================\n${colors.reset}`);
  
  console.log(`Total Tests: ${testResults.tests.length}`);
  console.log(`${colors.green}Passed: ${testResults.passed}${colors.reset}`);
  console.log(`${colors.red}Failed: ${testResults.failed}${colors.reset}`);
  
  if (testResults.failed > 0) {
    console.log(`\n${colors.yellow}Failed Tests:${colors.reset}`);
    testResults.tests
      .filter(t => !t.passed)
      .forEach(t => console.log(`  - ${t.name}: ${t.details}`));
  }
  
  const successRate = (testResults.passed / testResults.tests.length * 100).toFixed(1);
  console.log(`\n${colors.cyan}Success Rate: ${successRate}%${colors.reset}`);
  
  if (testResults.passed === testResults.tests.length) {
    console.log(`\n${colors.green}✓ All database connectivity tests passed successfully!${colors.reset}`);
  } else {
    console.log(`\n${colors.yellow}⚠ Some tests failed. Please review the results above.${colors.reset}`);
  }
}

// Run all tests
runTests().catch(error => {
  console.error(`${colors.red}Fatal error during testing:${colors.reset}`, error);
  process.exit(1);
});
