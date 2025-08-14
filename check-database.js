const { Client } = require('pg');

async function checkDatabase() {
  const client = new Client({
    connectionString: "postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
  });

  try {
    await client.connect();
    console.log('✅ Connected to Neon database');

    // Check what tables exist
    console.log('\n📊 Checking all tables in database...');
    const tablesResult = await client.query(`
      SELECT table_name, table_schema
      FROM information_schema.tables 
      WHERE table_schema = 'public' 
      ORDER BY table_name;
    `);
    
    if (tablesResult.rows.length === 0) {
      console.log('❌ NO TABLES FOUND in the database!');
    } else {
      console.log(`📋 Found ${tablesResult.rows.length} tables:`);
      tablesResult.rows.forEach(row => {
        console.log(`  - ${row.table_name}`);
      });
    }

    // Check for any data in existing tables
    if (tablesResult.rows.length > 0) {
      console.log('\n📊 Checking data in tables...');
      for (const table of tablesResult.rows) {
        try {
          const countResult = await client.query(`SELECT COUNT(*) FROM ${table.table_name}`);
          console.log(`  - ${table.table_name}: ${countResult.rows[0].count} rows`);
        } catch (error) {
          console.log(`  - ${table.table_name}: Error checking data - ${error.message}`);
        }
      }
    }

    // Check database connection info
    console.log('\n🔍 Database connection info:');
    const dbInfo = await client.query('SELECT current_database(), current_user, version()');
    console.log(`  - Database: ${dbInfo.rows[0].current_database}`);
    console.log(`  - User: ${dbInfo.rows[0].current_user}`);
    console.log(`  - Version: ${dbInfo.rows[0].version.split(' ')[0]} ${dbInfo.rows[0].version.split(' ')[1]}`);

  } catch (error) {
    console.error('❌ Database check failed:', error.message);
  } finally {
    await client.end();
  }
}

checkDatabase();
