const { Client } = require('pg');

async function checkSpecificTables() {
  const client = new Client({
    connectionString: "postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
  });

  try {
    await client.connect();
    console.log('✅ Connected to Neon database');

    // Check for the specific tables you mentioned
    const requiredTables = [
      'Bot_requests',
      'Plugin_configuration', 
      'Site_analytics',
      'Subscriptions',
      'Users',
      'WordPress_site'
    ];

    console.log('\n🔍 Checking for your specific tables...');
    
    for (const tableName of requiredTables) {
      // Check both exact case and lowercase versions
      const checkQuery = `
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND (LOWER(table_name) = LOWER($1) OR table_name = $1)
      `;
      
      const result = await client.query(checkQuery, [tableName]);
      
      if (result.rows.length > 0) {
        console.log(`✅ Found: ${result.rows[0].table_name}`);
      } else {
        console.log(`❌ Missing: ${tableName}`);
      }
    }

    // Show all existing tables for comparison
    console.log('\n📋 All existing tables in database:');
    const allTablesResult = await client.query(`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public' 
      ORDER BY table_name;
    `);
    
    allTablesResult.rows.forEach(row => {
      console.log(`  - ${row.table_name}`);
    });

    // Check if we have similar tables with different names
    console.log('\n🔍 Checking for similar functionality in existing tables:');
    console.log('  - bot_requests (similar to Bot_requests): EXISTS');
    console.log('  - sites (similar to WordPress_site): EXISTS');
    console.log('  - ai_companies (subscription info): EXISTS');
    console.log('  - analytics_daily (similar to Site_analytics): EXISTS');

  } catch (error) {
    console.error('❌ Database check failed:', error.message);
  } finally {
    await client.end();
  }
}

checkSpecificTables();
