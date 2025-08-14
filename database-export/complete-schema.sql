-- Complete Database Schema Export
-- Generated from Neon Database

-- Table: ai_companies
CREATE TABLE ai_companies (id INTEGER NOT NULL DEFAULT nextval('ai_companies_id_seq'::regclass), rate_per_request DECIMAL(10,6) DEFAULT 0.001, allowed_sites TEXT[], updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, stripe_customer_id VARCHAR(255), monthly_budget DECIMAL(12,2), subscription_tier VARCHAR(50), contact_email VARCHAR(255), company_name VARCHAR(255) NOT NULL, created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, subscription_active BOOLEAN DEFAULT false);

-- Table: analytics_daily
CREATE TABLE analytics_daily (total_revenue DECIMAL(12,6) DEFAULT 0.00, bot_requests INTEGER DEFAULT 0, monetized_requests INTEGER DEFAULT 0, top_bot_types JSONB, site_id INTEGER, created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, total_requests INTEGER DEFAULT 0, id INTEGER NOT NULL DEFAULT nextval('analytics_daily_id_seq'::regclass), date DATE NOT NULL, unique_bots INTEGER DEFAULT 0);

-- Table: api_keys
CREATE TABLE api_keys (id INTEGER NOT NULL DEFAULT nextval('api_keys_id_seq'::regclass), expires_at TIMESTAMP WITH TIME ZONE, created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, site_id INTEGER, last_used_at TIMESTAMP WITH TIME ZONE, key_name VARCHAR(100), permissions TEXT[], key_hash VARCHAR(64) NOT NULL, active BOOLEAN DEFAULT true, rate_limit INTEGER DEFAULT 1000);

-- Table: beta_applications
CREATE TABLE beta_applications (email TEXT NOT NULL, name TEXT NOT NULL, notes TEXT, website TEXT, coverLetter TEXT, updatedAt TIMESTAMP WITHOUT TIME ZONE NOT NULL, status TEXT NOT NULL DEFAULT 'pending'::text, id TEXT NOT NULL, phone TEXT, resumeUrl TEXT, createdAt TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, position TEXT NOT NULL);

-- Table: bot_requests
CREATE TABLE bot_requests (site_id INTEGER, bot_type VARCHAR(100), content_type VARCHAR(50), action_taken VARCHAR(20) DEFAULT 'logged'::character varying, content_length INTEGER DEFAULT 0, ip_address INET NOT NULL, payment_id VARCHAR(255), created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, ai_company_id INTEGER, user_agent TEXT NOT NULL, page_url TEXT, bot_detected BOOLEAN DEFAULT false, revenue_amount DECIMAL(10,6) DEFAULT 0.00, bot_name VARCHAR(100), id INTEGER NOT NULL DEFAULT nextval('bot_requests_id_seq'::regclass), confidence_score INTEGER DEFAULT 0);

-- Table: config_registry
CREATE TABLE config_registry (validation_rules JSONB, updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, schema_reference VARCHAR(255) NOT NULL, config_type VARCHAR(100) NOT NULL, registry_key VARCHAR(255) NOT NULL, description TEXT, is_system BOOLEAN DEFAULT false, id INTEGER NOT NULL DEFAULT nextval('config_registry_id_seq'::regclass), created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, default_value JSONB);

-- Table: contact_submissions
CREATE TABLE contact_submissions (updatedAt TIMESTAMP WITHOUT TIME ZONE NOT NULL, message TEXT NOT NULL, createdAt TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, subject TEXT, email TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'pending'::text, name TEXT NOT NULL, id TEXT NOT NULL);

-- Table: daily_platform_stats
CREATE TABLE daily_platform_stats (active_sites BIGINT, date DATE, bot_requests BIGINT, monetized_requests BIGINT, total_requests BIGINT);

-- Table: email_logs
CREATE TABLE email_logs (provider TEXT NOT NULL, to TEXT NOT NULL, createdAt TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, body TEXT NOT NULL, id TEXT NOT NULL, status TEXT NOT NULL, subject TEXT NOT NULL);

-- Table: headers_config
CREATE TABLE headers_config (id INTEGER NOT NULL DEFAULT nextval('headers_config_id_seq'::regclass), header_type VARCHAR(50) DEFAULT 'api'::character varying, created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, is_active BOOLEAN DEFAULT true, is_required BOOLEAN DEFAULT false, updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, header_value TEXT NOT NULL, header_name VARCHAR(255) NOT NULL, site_id INTEGER);

-- Table: payments
CREATE TABLE payments (payment_intent_id VARCHAR(255) NOT NULL, currency VARCHAR(3) DEFAULT 'USD'::character varying, stripe_fee DECIMAL(10,6) DEFAULT 0.00, created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, amount DECIMAL(10,6) NOT NULL, creator_payout DECIMAL(10,6) DEFAULT 0.00, metadata JSONB, site_id INTEGER, bot_request_id INTEGER, platform_fee DECIMAL(10,6) DEFAULT 0.00, status VARCHAR(20) NOT NULL, ai_company_id INTEGER, updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, id INTEGER NOT NULL DEFAULT nextval('payments_id_seq'::regclass));

-- Table: plugin_config
CREATE TABLE plugin_config (is_active BOOLEAN DEFAULT true, site_id INTEGER, config_value JSONB NOT NULL, id INTEGER NOT NULL DEFAULT nextval('plugin_config_id_seq'::regclass), config_type VARCHAR(50) DEFAULT 'setting'::character varying, config_key VARCHAR(255) NOT NULL, created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP);

-- Table: site_full_config
CREATE TABLE site_full_config (monetization_enabled BOOLEAN, site_url VARCHAR(255), headers TEXT[], api_key VARCHAR(64), allowed_bots TEXT[], subscription_tier VARCHAR(20), pricing_per_request DECIMAL(10,6), site_id INTEGER, plugin_config JSONB);

-- Table: site_revenue_summary
CREATE TABLE site_revenue_summary (total_requests BIGINT, site_url VARCHAR(255), monetized_requests BIGINT, id INTEGER, subscription_tier VARCHAR(20), bot_requests BIGINT, site_name VARCHAR(255));

-- Table: sites
CREATE TABLE sites (active BOOLEAN DEFAULT true, subscription_tier VARCHAR(20) DEFAULT 'free'::character varying, wordpress_version VARCHAR(20), plugin_version VARCHAR(20), site_name VARCHAR(255), monetization_enabled BOOLEAN DEFAULT false, api_key VARCHAR(64) NOT NULL, admin_email VARCHAR(255) NOT NULL, updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, allowed_bots TEXT[], stripe_account_id VARCHAR(255), pricing_per_request DECIMAL(10,6) DEFAULT 0.001, site_url VARCHAR(255) NOT NULL, id INTEGER NOT NULL DEFAULT nextval('sites_id_seq'::regclass), created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP);

-- Table: system_config
CREATE TABLE system_config (config_key VARCHAR(255) NOT NULL, updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP, id INTEGER NOT NULL DEFAULT nextval('system_config_id_seq'::regclass), is_public BOOLEAN DEFAULT false, config_value JSONB NOT NULL, category VARCHAR(100) DEFAULT 'general'::character varying, description TEXT);

-- Table: waitlist_entries
CREATE TABLE waitlist_entries (createdAt TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP, updatedAt TIMESTAMP WITHOUT TIME ZONE NOT NULL, status TEXT NOT NULL DEFAULT 'pending'::text, invitedAt TIMESTAMP WITHOUT TIME ZONE, companySize TEXT, inviteToken TEXT, id TEXT NOT NULL, website TEXT, email TEXT NOT NULL, name TEXT NOT NULL, useCase TEXT);

-- Table: webhooks
CREATE TABLE webhooks (site_id INTEGER, event_type VARCHAR(100) NOT NULL, id INTEGER NOT NULL DEFAULT nextval('webhooks_id_seq'::regclass), payload JSONB NOT NULL, attempts INTEGER DEFAULT 0, last_attempt_at TIMESTAMP WITH TIME ZONE, status VARCHAR(20) DEFAULT 'pending'::character varying, next_attempt_at TIMESTAMP WITH TIME ZONE, created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP);

