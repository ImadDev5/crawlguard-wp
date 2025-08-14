// Diagnostic script for Neon PostgreSQL connection issues
require('dotenv').config();
const { Client, Pool } = require('pg');

const connectionString = process.env.DATABASE_URL;

async function diagnoseSSL() {
  console.log('\n=== SSL/TLS Diagnostic ===\n');
  
  try {
    const client = new Client({ 
      connectionString,
      ssl: {
        rejectUnauthorized: false // For testing, we'll accept any certificate
      }
    });
    
    await client.connect();
    console.log('✓ Connected successfully with SSL configuration');
    
    // Try alternative SSL query
    try {
      const sslQuery = await client.query(`
        SELECT 
          ssl_is_used() as ssl_in_use,
          current_setting('ssl', true) as ssl_setting
      `);
      console.log('SSL Status (method 1):', sslQuery.rows[0]);
    } catch (e) {
      console.log('Method 1 failed:', e.message);
    }
    
    // Try another approach
    try {
      const connInfo = await client.query(`
        SELECT 
          pg_backend_pid() as pid,
          current_database() as database,
          current_user as user,
          inet_client_addr() as client_addr,
          inet_server_addr() as server_addr
      `);
      console.log('\nConnection Info:', connInfo.rows[0]);
      
      // Check if SSL info is available
      const sslInfo = await client.query(`
        SELECT * FROM pg_stat_ssl WHERE pid = pg_backend_pid()
      `);
      
      if (sslInfo.rows.length > 0) {
        console.log('\nSSL Details:', sslInfo.rows[0]);
      } else {
        console.log('\nNo SSL information available in pg_stat_ssl');
        
        // Check connection parameters
        const params = await client.query(`SHOW ALL`);
        const sslParams = params.rows.filter(r => r.name.includes('ssl'));
        console.log('\nSSL-related parameters:');
        sslParams.forEach(p => console.log(`  ${p.name}: ${p.setting}`));
      }
    } catch (e) {
      console.log('Detailed check failed:', e.message);
    }
    
    await client.end();
  } catch (error) {
    console.error('SSL connection failed:', error.message);
  }
}

async function diagnosePooling() {
  console.log('\n=== Connection Pooling Diagnostic ===\n');
  
  // Try with smaller pool first
  const testSizes = [1, 5, 10, 20];
  
  for (const size of testSizes) {
    console.log(`\nTesting pool size: ${size}`);
    
    try {
      const pool = new Pool({
        connectionString,
        max: size,
        idleTimeoutMillis: 30000,
        connectionTimeoutMillis: 10000, // Increased timeout
        ssl: {
          rejectUnauthorized: false
        }
      });
      
      // Test single connection first
      try {
        const testResult = await pool.query('SELECT 1 as test');
        console.log(`  ✓ Single connection successful`);
      } catch (e) {
        console.log(`  ✗ Single connection failed: ${e.message}`);
        await pool.end();
        continue;
      }
      
      // Try concurrent connections
      const promises = [];
      for (let i = 0; i < size; i++) {
        promises.push(
          pool.query(`SELECT ${i + 1} as num, pg_backend_pid() as pid`)
            .then(r => ({ success: true, ...r.rows[0] }))
            .catch(e => ({ success: false, error: e.message }))
        );
      }
      
      const results = await Promise.all(promises);
      const successful = results.filter(r => r.success).length;
      const failed = results.filter(r => !r.success).length;
      
      console.log(`  Results: ${successful} successful, ${failed} failed`);
      
      if (failed > 0) {
        const errors = results.filter(r => !r.success);
        console.log(`  First error: ${errors[0].error}`);
      }
      
      // Check pool stats
      console.log(`  Pool stats: Total=${pool.totalCount}, Idle=${pool.idleCount}, Waiting=${pool.waitingCount}`);
      
      await pool.end();
      
      if (successful === size) {
        console.log(`  ✓ Pool size ${size} works perfectly!`);
      }
    } catch (error) {
      console.log(`  ✗ Pool creation failed: ${error.message}`);
    }
  }
}

async function checkNeonLimits() {
  console.log('\n=== Neon Database Limits ===\n');
  
  try {
    const client = new Client({ 
      connectionString,
      ssl: { rejectUnauthorized: false }
    });
    await client.connect();
    
    // Check connection limits
    const limits = await client.query(`
      SELECT 
        setting as max_connections
      FROM pg_settings 
      WHERE name = 'max_connections'
    `);
    console.log('Max connections allowed:', limits.rows[0].max_connections);
    
    // Check current connections
    const current = await client.query(`
      SELECT 
        count(*) as active_connections,
        datname as database
      FROM pg_stat_activity 
      WHERE datname = current_database()
      GROUP BY datname
    `);
    console.log('Current active connections:', current.rows[0]);
    
    // Check for connection slots
    const slots = await client.query(`
      SELECT 
        count(*) as total_connections,
        count(*) FILTER (WHERE state = 'active') as active,
        count(*) FILTER (WHERE state = 'idle') as idle,
        count(*) FILTER (WHERE state = 'idle in transaction') as idle_in_transaction
      FROM pg_stat_activity
      WHERE datname = current_database()
    `);
    console.log('Connection breakdown:', slots.rows[0]);
    
    await client.end();
  } catch (error) {
    console.error('Failed to check limits:', error.message);
  }
}

async function testDirectConnection() {
  console.log('\n=== Testing Direct vs Pooler Connection ===\n');
  
  // Parse the connection string to check if it's using pooler
  const isPooler = connectionString.includes('-pooler');
  console.log(`Current connection: ${isPooler ? 'Using Pooler' : 'Direct'}`);
  
  if (isPooler) {
    // Try direct connection (without pooler)
    const directUrl = connectionString.replace('-pooler', '');
    console.log('\nTrying direct connection (without pooler)...');
    
    try {
      const directClient = new Client({ 
        connectionString: directUrl,
        ssl: { rejectUnauthorized: false }
      });
      await directClient.connect();
      
      const result = await directClient.query('SELECT 1 as test');
      console.log('✓ Direct connection successful');
      
      // Check SSL on direct connection
      const sslCheck = await directClient.query(`
        SELECT ssl_is_used() as ssl_enabled
      `);
      console.log('Direct connection SSL:', sslCheck.rows[0].ssl_enabled ? 'Enabled' : 'Disabled');
      
      await directClient.end();
    } catch (error) {
      console.log('✗ Direct connection failed:', error.message);
    }
  }
}

async function runDiagnostics() {
  console.log('Starting Neon PostgreSQL Diagnostics...');
  console.log('Connection String (masked):', connectionString.replace(/:[^@]+@/, ':****@'));
  
  await diagnoseSSL();
  await checkNeonLimits();
  await testDirectConnection();
  await diagnosePooling();
  
  console.log('\n=== Diagnostics Complete ===\n');
}

runDiagnostics().catch(error => {
  console.error('Fatal error:', error);
  process.exit(1);
});
