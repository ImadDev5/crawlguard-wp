const { Client } = require('pg');
const fs = require('fs');
const path = require('path');

async function setupDatabase() {
  const client = new Client({
    connectionString: "postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
  });

  try {
    await client.connect();
    console.log('✅ Connected to Neon database');

    // Read and execute schema files in order
    const schemaFiles = [
      '../database/schema-step1-tables.sql',
      '../database/schema-step2-indexes.sql',
      '../database/schema-step3-functions.sql'
    ];

    for (const filePath of schemaFiles) {
      console.log(`\n📄 Executing ${filePath}...`);
      
      const sql = fs.readFileSync(filePath, 'utf8');
      
      try {
        await client.query(sql);
        console.log(`✅ Successfully executed ${filePath}`);
      } catch (error) {
        if (error.message.includes('already exists')) {
          console.log(`⚠️  Some objects in ${filePath} already exist (skipping)`);
        } else {
          console.error(`❌ Error executing ${filePath}:`, error.message);
          throw error;
        }
      }
    }

    // Check what tables we now have
    console.log('\n📊 Checking database tables...');
    const result = await client.query(`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public' 
      ORDER BY table_name;
    `);
    
    console.log('📋 Tables in database:');
    result.rows.forEach(row => {
      console.log(`  - ${row.table_name}`);
    });

    console.log('\n🎉 Database setup completed successfully!');
    
  } catch (error) {
    console.error('❌ Database setup failed:', error);
  } finally {
    await client.end();
  }
}

setupDatabase();
