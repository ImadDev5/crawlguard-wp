const { Client } = require('pg');

async function checkConfigurationData() {
  const client = new Client({
    connectionString: "postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
  });

  try {
    await client.connect();
    console.log('✅ Connected to Neon database');

    console.log('\n🔍 CHECKING FOR CONFIGURATION DATA:\n');

    // Check for configuration-related tables
    const configTables = await client.query(`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public' 
      AND (
        table_name LIKE '%config%' OR 
        table_name LIKE '%setting%' OR 
        table_name LIKE '%header%' OR
        table_name LIKE '%registry%' OR
        table_name LIKE '%api_key%'
      )
      ORDER BY table_name
    `);

    console.log('📋 Configuration-related tables:');
    if (configTables.rows.length > 0) {
      configTables.rows.forEach(row => {
        console.log(`   ✅ ${row.table_name}`);
      });
    } else {
      console.log('   ❌ No dedicated configuration tables found');
    }

    // Check api_keys table specifically
    console.log('\n🔑 API KEYS TABLE:');
    try {
      const apiKeysColumns = await client.query(`
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_name = 'api_keys' 
        ORDER BY ordinal_position
      `);
      
      if (apiKeysColumns.rows.length > 0) {
        console.log('   ✅ FOUND: api_keys table');
        console.log('   📋 Structure:');
        apiKeysColumns.rows.forEach(col => {
          console.log(`      - ${col.column_name} (${col.data_type})`);
        });

        // Check for data
        const apiKeysData = await client.query('SELECT * FROM api_keys LIMIT 5');
        console.log(`   📊 Data: ${apiKeysData.rows.length} rows`);
        if (apiKeysData.rows.length > 0) {
          console.log('   🔍 Sample data:');
          apiKeysData.rows.forEach((row, i) => {
            console.log(`      ${i+1}. Key: ${row.key_name || 'N/A'}, Site: ${row.site_id || 'N/A'}`);
          });
        }
      } else {
        console.log('   ❌ api_keys table not found');
      }
    } catch (error) {
      console.log('   ❌ Error checking api_keys:', error.message);
    }

    // Check sites table for configuration columns
    console.log('\n🏢 SITES TABLE CONFIGURATION:');
    try {
      const sitesConfig = await client.query(`
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'sites' 
        AND (
          column_name LIKE '%config%' OR 
          column_name LIKE '%setting%' OR 
          column_name LIKE '%header%' OR
          column_name LIKE '%api%' OR
          column_name LIKE '%allowed%' OR
          column_name LIKE '%pricing%'
        )
        ORDER BY ordinal_position
      `);

      if (sitesConfig.rows.length > 0) {
        console.log('   ✅ Configuration columns in sites table:');
        sitesConfig.rows.forEach(col => {
          console.log(`      - ${col.column_name} (${col.data_type})`);
        });

        // Check for actual configuration data
        const sitesData = await client.query(`
          SELECT 
            site_url, 
            api_key, 
            subscription_tier, 
            monetization_enabled, 
            pricing_per_request, 
            allowed_bots,
            plugin_version
          FROM sites 
          LIMIT 3
        `);
        
        console.log(`   📊 Sites with configuration: ${sitesData.rows.length} rows`);
        if (sitesData.rows.length > 0) {
          console.log('   🔍 Sample configurations:');
          sitesData.rows.forEach((row, i) => {
            console.log(`      ${i+1}. ${row.site_url || 'No URL'}`);
            console.log(`         - API Key: ${row.api_key ? 'SET' : 'NOT SET'}`);
            console.log(`         - Tier: ${row.subscription_tier || 'free'}`);
            console.log(`         - Monetization: ${row.monetization_enabled ? 'ON' : 'OFF'}`);
            console.log(`         - Pricing: $${row.pricing_per_request || '0.001'}/request`);
            console.log(`         - Allowed Bots: ${row.allowed_bots ? row.allowed_bots.length + ' configured' : 'None'}`);
          });
        }
      } else {
        console.log('   ❌ No configuration columns found in sites table');
      }
    } catch (error) {
      console.log('   ❌ Error checking sites configuration:', error.message);
    }

    // Check for webhooks table (configuration for notifications)
    console.log('\n🔗 WEBHOOKS CONFIGURATION:');
    try {
      const webhooksColumns = await client.query(`
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'webhooks' 
        ORDER BY ordinal_position
      `);
      
      if (webhooksColumns.rows.length > 0) {
        console.log('   ✅ FOUND: webhooks table');
        console.log('   📋 Columns:', webhooksColumns.rows.map(r => r.column_name).join(', '));
        
        const webhooksData = await client.query('SELECT * FROM webhooks LIMIT 3');
        console.log(`   📊 Webhook configurations: ${webhooksData.rows.length} rows`);
      } else {
        console.log('   ❌ webhooks table not found');
      }
    } catch (error) {
      console.log('   ❌ Error checking webhooks:', error.message);
    }

    // Check for any JSON configuration columns
    console.log('\n📄 JSON CONFIGURATION COLUMNS:');
    try {
      const jsonColumns = await client.query(`
        SELECT table_name, column_name, data_type
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND (data_type = 'json' OR data_type = 'jsonb')
        ORDER BY table_name, column_name
      `);
      
      if (jsonColumns.rows.length > 0) {
        console.log('   ✅ JSON configuration columns found:');
        jsonColumns.rows.forEach(col => {
          console.log(`      - ${col.table_name}.${col.column_name} (${col.data_type})`);
        });
      } else {
        console.log('   ❌ No JSON configuration columns found');
      }
    } catch (error) {
      console.log('   ❌ Error checking JSON columns:', error.message);
    }

    console.log('\n🎯 CONFIGURATION ANALYSIS SUMMARY:');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    
    // Final assessment
    const hasApiKeys = await client.query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'api_keys'");
    const hasSitesConfig = await client.query(`
      SELECT COUNT(*) FROM information_schema.columns 
      WHERE table_name = 'sites' AND column_name IN ('api_key', 'allowed_bots', 'pricing_per_request')
    `);
    
    if (hasApiKeys.rows[0].count > 0) {
      console.log('✅ API key management: PRESENT');
    } else {
      console.log('❌ API key management: MISSING');
    }
    
    if (hasSitesConfig.rows[0].count >= 3) {
      console.log('✅ Site configuration: PRESENT');
    } else {
      console.log('❌ Site configuration: MISSING');
    }

  } catch (error) {
    console.error('❌ Configuration check failed:', error.message);
  } finally {
    await client.end();
  }
}

checkConfigurationData();
