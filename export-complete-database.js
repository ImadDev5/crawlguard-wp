const { Client } = require('pg');
const fs = require('fs');
const path = require('path');

async function exportCompleteDatabase() {
  const client = new Client({
    connectionString: "postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
  });

  try {
    await client.connect();
    console.log('✅ Connected to Neon database');

    // Create export directory
    const exportDir = '../database-export';
    if (!fs.existsSync(exportDir)) {
      fs.mkdirSync(exportDir, { recursive: true });
    }

    console.log('\n📦 EXPORTING COMPLETE DATABASE:\n');

    // 1. Export Schema Structure
    console.log('🏗️ Exporting database schema...');
    const schemaQuery = `
      SELECT 
        'CREATE TABLE ' || table_name || ' (' || 
        string_agg(
          column_name || ' ' || 
          CASE 
            WHEN data_type = 'character varying' THEN 'VARCHAR(' || character_maximum_length || ')'
            WHEN data_type = 'numeric' THEN 'DECIMAL(' || numeric_precision || ',' || numeric_scale || ')'
            WHEN data_type = 'integer' THEN 'INTEGER'
            WHEN data_type = 'bigint' THEN 'BIGINT'
            WHEN data_type = 'boolean' THEN 'BOOLEAN'
            WHEN data_type = 'text' THEN 'TEXT'
            WHEN data_type = 'timestamp with time zone' THEN 'TIMESTAMP WITH TIME ZONE'
            WHEN data_type = 'date' THEN 'DATE'
            WHEN data_type = 'jsonb' THEN 'JSONB'
            WHEN data_type = 'ARRAY' THEN 'TEXT[]'
            WHEN data_type = 'inet' THEN 'INET'
            ELSE UPPER(data_type)
          END ||
          CASE WHEN is_nullable = 'NO' THEN ' NOT NULL' ELSE '' END ||
          CASE WHEN column_default IS NOT NULL THEN ' DEFAULT ' || column_default ELSE '' END,
          ', '
        ) || ');' as create_statement,
        table_name
      FROM information_schema.columns 
      WHERE table_schema = 'public' 
      AND table_name NOT LIKE 'pg_%'
      GROUP BY table_name
      ORDER BY table_name;
    `;

    const schemaResult = await client.query(schemaQuery);
    let schemaSQL = '-- Complete Database Schema Export\n-- Generated from Neon Database\n\n';
    
    schemaResult.rows.forEach(row => {
      schemaSQL += `-- Table: ${row.table_name}\n`;
      schemaSQL += row.create_statement + '\n\n';
    });

    fs.writeFileSync(path.join(exportDir, 'complete-schema.sql'), schemaSQL);
    console.log('   ✅ Schema exported to complete-schema.sql');

    // 2. Export All Data
    console.log('\n📊 Exporting all table data...');
    
    const tables = await client.query(`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public' 
      AND table_type = 'BASE TABLE'
      ORDER BY table_name
    `);

    let dataSQL = '-- Complete Database Data Export\n-- Generated from Neon Database\n\n';
    let totalRows = 0;

    for (const table of tables.rows) {
      const tableName = table.table_name;
      console.log(`   📋 Exporting ${tableName}...`);

      // Get column names
      const columnsResult = await client.query(`
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = $1 
        ORDER BY ordinal_position
      `, [tableName]);

      const columns = columnsResult.rows.map(r => r.column_name);

      // Get data
      const dataResult = await client.query(`SELECT * FROM ${tableName}`);
      
      if (dataResult.rows.length > 0) {
        dataSQL += `-- Data for table: ${tableName} (${dataResult.rows.length} rows)\n`;
        
        // Generate INSERT statements
        for (const row of dataResult.rows) {
          const values = columns.map(col => {
            const value = row[col];
            if (value === null) return 'NULL';
            if (typeof value === 'string') return `'${value.replace(/'/g, "''")}'`;
            if (typeof value === 'boolean') return value ? 'true' : 'false';
            if (Array.isArray(value)) return `ARRAY[${value.map(v => `'${v}'`).join(', ')}]`;
            if (typeof value === 'object') return `'${JSON.stringify(value).replace(/'/g, "''")}'::jsonb`;
            return value;
          });

          dataSQL += `INSERT INTO ${tableName} (${columns.join(', ')}) VALUES (${values.join(', ')});\n`;
        }
        dataSQL += '\n';
        totalRows += dataResult.rows.length;
        console.log(`      ✅ ${dataResult.rows.length} rows exported`);
      } else {
        console.log(`      ⚠️  No data in ${tableName}`);
      }
    }

    fs.writeFileSync(path.join(exportDir, 'complete-data.sql'), dataSQL);
    console.log(`   ✅ All data exported (${totalRows} total rows)`);

    // 3. Export Configuration Summary
    console.log('\n⚙️ Exporting configuration summary...');
    
    const configSummary = {
      export_date: new Date().toISOString(),
      database_info: {
        host: "ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech",
        database: "neondb",
        user: "neondb_owner"
      },
      tables_exported: tables.rows.length,
      total_rows: totalRows,
      system_config: {},
      sites_config: [],
      headers_config: [],
      plugin_config: []
    };

    // Get system configuration
    const systemConfig = await client.query('SELECT config_key, config_value, category FROM system_config');
    systemConfig.rows.forEach(row => {
      configSummary.system_config[row.config_key] = {
        value: row.config_value,
        category: row.category
      };
    });

    // Get sites configuration
    const sitesConfig = await client.query(`
      SELECT site_url, api_key, subscription_tier, monetization_enabled, 
             pricing_per_request, allowed_bots, stripe_account_id
      FROM sites WHERE active = true
    `);
    configSummary.sites_config = sitesConfig.rows.map(row => ({
      ...row,
      api_key: row.api_key ? 'SET' : 'NOT SET' // Mask API key
    }));

    // Get headers configuration
    const headersConfig = await client.query(`
      SELECT s.site_url, hc.header_name, hc.header_value, hc.header_type
      FROM headers_config hc
      JOIN sites s ON hc.site_id = s.id
      WHERE hc.is_active = true
    `);
    configSummary.headers_config = headersConfig.rows.map(row => ({
      ...row,
      header_value: row.header_name === 'Authorization' ? 'Bearer ***' : row.header_value
    }));

    // Get plugin configuration
    const pluginConfig = await client.query(`
      SELECT s.site_url, pc.config_key, pc.config_value, pc.config_type
      FROM plugin_config pc
      JOIN sites s ON pc.site_id = s.id
      WHERE pc.is_active = true
    `);
    configSummary.plugin_config = pluginConfig.rows;

    fs.writeFileSync(
      path.join(exportDir, 'configuration-summary.json'), 
      JSON.stringify(configSummary, null, 2)
    );
    console.log('   ✅ Configuration summary exported');

    // 4. Create restoration script
    console.log('\n🔧 Creating restoration script...');
    
    const restorationScript = `#!/bin/bash
# Database Restoration Script
# This script will restore your complete database to any PostgreSQL instance

echo "🔄 Starting database restoration..."

# Set your target database connection details
DB_HOST="localhost"
DB_NAME="your_database_name"
DB_USER="your_username"
DB_PASS="your_password"

# Create database if it doesn't exist
createdb -h $DB_HOST -U $DB_USER $DB_NAME 2>/dev/null || true

# Restore schema
echo "📋 Restoring database schema..."
psql -h $DB_HOST -U $DB_USER -d $DB_NAME -f complete-schema.sql

# Restore data
echo "📊 Restoring database data..."
psql -h $DB_HOST -U $DB_USER -d $DB_NAME -f complete-data.sql

echo "✅ Database restoration completed!"
echo "📋 Check configuration-summary.json for your settings"
`;

    fs.writeFileSync(path.join(exportDir, 'restore-database.sh'), restorationScript);
    fs.chmodSync(path.join(exportDir, 'restore-database.sh'), '755');
    console.log('   ✅ Restoration script created');

    // 5. Create Windows batch file
    const windowsScript = `@echo off
REM Database Restoration Script for Windows
REM This script will restore your complete database to any PostgreSQL instance

echo Starting database restoration...

REM Set your target database connection details
set DB_HOST=localhost
set DB_NAME=your_database_name
set DB_USER=your_username
set PGPASSWORD=your_password

REM Create database if it doesn't exist
createdb -h %DB_HOST% -U %DB_USER% %DB_NAME% 2>nul

REM Restore schema
echo Restoring database schema...
psql -h %DB_HOST% -U %DB_USER% -d %DB_NAME% -f complete-schema.sql

REM Restore data
echo Restoring database data...
psql -h %DB_HOST% -U %DB_USER% -d %DB_NAME% -f complete-data.sql

echo Database restoration completed!
echo Check configuration-summary.json for your settings
pause
`;

    fs.writeFileSync(path.join(exportDir, 'restore-database.bat'), windowsScript);
    console.log('   ✅ Windows restoration script created');

    console.log('\n🎯 EXPORT COMPLETED SUCCESSFULLY!');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`📁 Export location: ${path.resolve(exportDir)}`);
    console.log(`📋 Tables exported: ${tables.rows.length}`);
    console.log(`📊 Total rows: ${totalRows}`);
    console.log('');
    console.log('📦 Files created:');
    console.log('   - complete-schema.sql (Database structure)');
    console.log('   - complete-data.sql (All your data)');
    console.log('   - configuration-summary.json (Configuration overview)');
    console.log('   - restore-database.sh (Linux/Mac restoration script)');
    console.log('   - restore-database.bat (Windows restoration script)');
    console.log('');
    console.log('🔄 TO RESTORE TO YOUR OLD SETUP:');
    console.log('1. Set up your old database server');
    console.log('2. Edit restore-database.bat with your database details');
    console.log('3. Run restore-database.bat');
    console.log('4. Your complete setup will be restored!');

  } catch (error) {
    console.error('❌ Export failed:', error.message);
  } finally {
    await client.end();
  }
}

exportCompleteDatabase();
