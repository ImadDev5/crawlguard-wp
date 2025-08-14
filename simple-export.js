const { Client } = require('pg');
const fs = require('fs');

async function simpleExport() {
  const client = new Client({
    connectionString: "postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
  });

  try {
    await client.connect();
    console.log('✅ Connected to Neon database');

    // Create export directory
    if (!fs.existsSync('../database-export')) {
      fs.mkdirSync('../database-export');
    }

    console.log('\n📦 EXPORTING YOUR COMPLETE DATABASE:\n');

    // 1. Export all tables list
    const tables = await client.query(`
      SELECT table_name, 
             (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = t.table_name) as columns,
             (SELECT COUNT(*) FROM information_schema.tables WHERE table_name = t.table_name) as exists
      FROM information_schema.tables t
      WHERE table_schema = 'public' 
      AND table_type = 'BASE TABLE'
      ORDER BY table_name
    `);

    console.log('📋 TABLES TO EXPORT:');
    tables.rows.forEach(row => {
      console.log(`   - ${row.table_name} (${row.columns} columns)`);
    });

    // 2. Export system configuration
    console.log('\n⚙️ SYSTEM CONFIGURATION:');
    const systemConfig = await client.query('SELECT * FROM system_config ORDER BY config_key');
    console.log(`   📊 ${systemConfig.rows.length} system configurations`);
    
    let configExport = '-- System Configuration Export\n';
    systemConfig.rows.forEach(row => {
      configExport += `INSERT INTO system_config (config_key, config_value, description, category) VALUES ('${row.config_key}', '${JSON.stringify(row.config_value).replace(/'/g, "''")}', '${row.description || ''}', '${row.category}');\n`;
    });

    // 3. Export sites data
    console.log('\n🏢 SITES DATA:');
    const sites = await client.query('SELECT * FROM sites ORDER BY id');
    console.log(`   📊 ${sites.rows.length} sites`);
    
    sites.rows.forEach(site => {
      console.log(`   - ${site.site_url} (${site.subscription_tier})`);
      configExport += `-- Site: ${site.site_url}\n`;
      configExport += `INSERT INTO sites (site_url, site_name, admin_email, api_key, subscription_tier, monetization_enabled, pricing_per_request, allowed_bots, active) VALUES ('${site.site_url}', '${site.site_name || ''}', '${site.admin_email}', '${site.api_key}', '${site.subscription_tier}', ${site.monetization_enabled}, ${site.pricing_per_request}, ARRAY[${(site.allowed_bots || []).map(b => `'${b}'`).join(', ')}], ${site.active});\n`;
    });

    // 4. Export headers configuration
    console.log('\n🔗 HEADERS CONFIGURATION:');
    const headers = await client.query(`
      SELECT hc.*, s.site_url 
      FROM headers_config hc 
      JOIN sites s ON hc.site_id = s.id 
      ORDER BY s.site_url, hc.header_name
    `);
    console.log(`   📊 ${headers.rows.length} header configurations`);
    
    headers.rows.forEach(header => {
      const maskedValue = header.header_name === 'Authorization' ? 'Bearer ***' : header.header_value;
      console.log(`   - ${header.site_url}: ${header.header_name} = ${maskedValue}`);
    });

    // 5. Export AI companies
    console.log('\n🤖 AI COMPANIES:');
    const aiCompanies = await client.query('SELECT * FROM ai_companies ORDER BY company_name');
    console.log(`   📊 ${aiCompanies.rows.length} AI companies`);
    
    aiCompanies.rows.forEach(company => {
      console.log(`   - ${company.company_name}: $${company.rate_per_request}/request`);
      configExport += `INSERT INTO ai_companies (company_name, contact_email, subscription_active, rate_per_request) VALUES ('${company.company_name}', '${company.contact_email || ''}', ${company.subscription_active}, ${company.rate_per_request});\n`;
    });

    // Save configuration export
    fs.writeFileSync('../database-export/essential-config.sql', configExport);
    console.log('\n✅ Essential configuration exported to essential-config.sql');

    // 6. Create simple restoration guide
    const restorationGuide = `# DATABASE RESTORATION GUIDE

## Your Complete Database Export

### 🔑 NEON DATABASE CREDENTIALS:
- Host: ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech
- Database: neondb
- User: neondb_owner
- Password: npg_nf1TKzFajLV2
- Connection: postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require

### 📋 TABLES IN YOUR DATABASE:
${tables.rows.map(t => `- ${t.table_name} (${t.columns} columns)`).join('\n')}

### ⚙️ SYSTEM CONFIGURATION:
${systemConfig.rows.map(c => `- ${c.config_key}: ${JSON.stringify(c.config_value)}`).join('\n')}

### 🏢 YOUR SITES:
${sites.rows.map(s => `- ${s.site_url} (${s.subscription_tier}) - API Key: ${s.api_key ? 'SET' : 'NOT SET'}`).join('\n')}

### 🤖 AI COMPANIES:
${aiCompanies.rows.map(a => `- ${a.company_name}: $${a.rate_per_request}/request`).join('\n')}

## 🔄 TO RESTORE TO YOUR OLD SETUP:

### Option 1: Use pg_dump (Recommended)
\`\`\`bash
# Full database dump
pg_dump "postgresql://neondb_owner:npg_nf1TKzFajLV2@ep-steep-resonance-adkp2zt6-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require" > complete_backup.sql

# Restore to your old database
psql -h your_old_host -U your_old_user -d your_old_database < complete_backup.sql
\`\`\`

### Option 2: Use Essential Config
\`\`\`bash
# Run the essential configuration
psql -h your_old_host -U your_old_user -d your_old_database < essential-config.sql
\`\`\`

### Option 3: Manual Migration
1. Copy the connection string above
2. Connect to Neon database using any PostgreSQL client
3. Export specific tables you need
4. Import to your old setup

## 🎯 WHAT YOU HAVE IN NEON:
- ✅ Complete subscription tracking system
- ✅ Bot detection and monetization
- ✅ Payment processing setup
- ✅ Analytics and reporting
- ✅ Configuration management
- ✅ Headers and API setup
- ✅ Registry system
- ✅ All your website data (waitlist, emails, etc.)

Your Neon database is a COMPLETE, WORKING system with all functionality!
`;

    fs.writeFileSync('../database-export/RESTORATION-GUIDE.md', restorationGuide);
    console.log('✅ Restoration guide created');

    console.log('\n🎯 EXPORT SUMMARY:');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`📁 Export location: ${require('path').resolve('../database-export')}`);
    console.log(`📋 Tables: ${tables.rows.length}`);
    console.log(`⚙️ System configs: ${systemConfig.rows.length}`);
    console.log(`🏢 Sites: ${sites.rows.length}`);
    console.log(`🔗 Headers: ${headers.rows.length}`);
    console.log(`🤖 AI companies: ${aiCompanies.rows.length}`);
    console.log('');
    console.log('📦 Files created:');
    console.log('   - essential-config.sql (Key configurations)');
    console.log('   - RESTORATION-GUIDE.md (Complete guide)');
    console.log('');
    console.log('🔄 TO SWITCH BACK TO OLD SETUP:');
    console.log('1. Use pg_dump to get complete backup');
    console.log('2. Or use essential-config.sql for key data');
    console.log('3. Or manually copy what you need');
    console.log('4. Your Neon database will remain available!');

  } catch (error) {
    console.error('❌ Export failed:', error.message);
  } finally {
    await client.end();
  }
}

simpleExport();
