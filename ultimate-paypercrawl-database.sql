-- 🚀 ULTIMATE PAYPERCRAWL DATABASE SCHEMA
-- Complete integration for WordPress Plugin + Website + Authentication + Analytics
-- Designed for maximum performance, security, and scalability

-- ==========================================
-- CORE SYSTEM TABLES
-- ==========================================

-- System-wide configuration (global settings)
CREATE TABLE IF NOT EXISTS system_config (
    id SERIAL PRIMARY KEY,
    config_key VARCHAR(255) NOT NULL UNIQUE,
    config_value JSONB NOT NULL,
    description TEXT,
    category VARCHAR(100) DEFAULT 'general',
    is_public BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Configuration registry (connects settings to code/schema)
CREATE TABLE IF NOT EXISTS config_registry (
    id SERIAL PRIMARY KEY,
    registry_key VARCHAR(255) NOT NULL UNIQUE,
    schema_reference VARCHAR(255) NOT NULL,
    config_type VARCHAR(100) NOT NULL,
    default_value JSONB,
    validation_rules JSONB DEFAULT '{}',
    description TEXT,
    middleware_function VARCHAR(255),
    is_system BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- WORDPRESS PLUGIN CORE TABLES
-- ==========================================

-- WordPress sites registered with the plugin
CREATE TABLE IF NOT EXISTS sites (
    id SERIAL PRIMARY KEY,
    site_url VARCHAR(255) NOT NULL UNIQUE,
    site_name VARCHAR(255),
    admin_email VARCHAR(255) NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    plugin_version VARCHAR(20),
    wordpress_version VARCHAR(20),
    subscription_tier VARCHAR(20) DEFAULT 'free',
    monetization_enabled BOOLEAN DEFAULT false,
    pricing_per_request DECIMAL(10,6) DEFAULT 0.001,
    allowed_bots JSONB DEFAULT '[]',
    blocked_bots JSONB DEFAULT '[]',
    detection_settings JSONB DEFAULT '{}',
    stripe_account_id VARCHAR(255),
    cloudflare_zone_id VARCHAR(255),
    last_activity TIMESTAMP WITH TIME ZONE,
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- AI companies and their subscription details
CREATE TABLE IF NOT EXISTS ai_companies (
    id SERIAL PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL UNIQUE,
    contact_email VARCHAR(255),
    subscription_active BOOLEAN DEFAULT false,
    subscription_tier VARCHAR(50),
    monthly_budget DECIMAL(12,2),
    rate_per_request DECIMAL(10,6) DEFAULT 0.001,
    allowed_sites JSONB DEFAULT '[]',
    rate_limits JSONB DEFAULT '{}',
    stripe_customer_id VARCHAR(255),
    api_access_token VARCHAR(255),
    webhook_url VARCHAR(500),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Bot detection and monetization events
CREATE TABLE IF NOT EXISTS bot_requests (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    ai_company_id INTEGER REFERENCES ai_companies(id) ON DELETE SET NULL,
    ip_address INET NOT NULL,
    user_agent TEXT NOT NULL,
    request_headers JSONB DEFAULT '{}',
    bot_detected BOOLEAN DEFAULT false,
    bot_type VARCHAR(100),
    bot_name VARCHAR(100),
    confidence_score INTEGER DEFAULT 0,
    detection_method VARCHAR(100),
    page_url TEXT,
    content_type VARCHAR(50),
    content_length INTEGER DEFAULT 0,
    response_code INTEGER DEFAULT 200,
    action_taken VARCHAR(20) DEFAULT 'logged',
    revenue_amount DECIMAL(10,6) DEFAULT 0.00,
    payment_id VARCHAR(255),
    processing_time_ms INTEGER,
    geo_country VARCHAR(2),
    geo_city VARCHAR(100),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Payment transactions and revenue tracking
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    ai_company_id INTEGER REFERENCES ai_companies(id) ON DELETE SET NULL,
    bot_request_id INTEGER REFERENCES bot_requests(id) ON DELETE SET NULL,
    payment_intent_id VARCHAR(255),
    stripe_payment_id VARCHAR(255),
    amount DECIMAL(12,6) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(50) DEFAULT 'pending',
    stripe_fee DECIMAL(12,6) DEFAULT 0,
    platform_fee DECIMAL(12,6) DEFAULT 0,
    creator_payout DECIMAL(12,6) DEFAULT 0,
    payout_date TIMESTAMP WITH TIME ZONE,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- AUTHENTICATION & SECURITY SYSTEM
-- ==========================================

-- API keys for authentication and rate limiting
CREATE TABLE IF NOT EXISTS api_keys (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    key_hash VARCHAR(64) NOT NULL UNIQUE,
    key_name VARCHAR(100),
    permissions JSONB DEFAULT '[]',
    rate_limit INTEGER DEFAULT 1000,
    rate_window INTEGER DEFAULT 3600,
    last_used_at TIMESTAMP WITH TIME ZONE,
    expires_at TIMESTAMP WITH TIME ZONE,
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- User sessions for authentication
CREATE TABLE IF NOT EXISTS user_sessions (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    auth_token VARCHAR(255) NOT NULL UNIQUE,
    refresh_token VARCHAR(255),
    ip_address INET,
    user_agent TEXT,
    permissions JSONB DEFAULT '{}',
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    is_active BOOLEAN DEFAULT true,
    last_activity TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- API authentication middleware
CREATE TABLE IF NOT EXISTS api_authentication (
    id SERIAL PRIMARY KEY,
    api_key_id INTEGER REFERENCES api_keys(id) ON DELETE CASCADE,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    auth_token VARCHAR(255) NOT NULL UNIQUE,
    token_type VARCHAR(50) DEFAULT 'bearer',
    middleware_config JSONB DEFAULT '{}',
    rate_limit_config JSONB DEFAULT '{}',
    cors_config JSONB DEFAULT '{}',
    security_headers JSONB DEFAULT '{}',
    token_expires_at TIMESTAMP WITH TIME ZONE,
    is_active BOOLEAN DEFAULT true,
    last_used TIMESTAMP WITH TIME ZONE,
    usage_count INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Authentication logs for monitoring
CREATE TABLE IF NOT EXISTS auth_logs (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    api_key_id INTEGER REFERENCES api_keys(id) ON DELETE SET NULL,
    session_id INTEGER REFERENCES user_sessions(id) ON DELETE SET NULL,
    auth_method VARCHAR(100) NOT NULL,
    auth_token_hash VARCHAR(255),
    request_ip INET,
    request_headers JSONB,
    auth_status VARCHAR(50) NOT NULL,
    failure_reason TEXT,
    endpoint_accessed VARCHAR(255),
    response_code INTEGER,
    processing_time_ms INTEGER,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- CONFIGURATION MANAGEMENT
-- ==========================================

-- Per-site plugin configuration
CREATE TABLE IF NOT EXISTS plugin_config (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    config_key VARCHAR(255) NOT NULL,
    config_value JSONB NOT NULL,
    config_type VARCHAR(50) DEFAULT 'setting',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, config_key)
);

-- Headers configuration for API requests
CREATE TABLE IF NOT EXISTS headers_config (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    header_name VARCHAR(255) NOT NULL,
    header_value TEXT NOT NULL,
    header_type VARCHAR(50) DEFAULT 'api',
    is_required BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- ANALYTICS & REPORTING
-- ==========================================

-- Daily analytics aggregations
CREATE TABLE IF NOT EXISTS analytics_daily (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    total_requests INTEGER DEFAULT 0,
    bot_requests INTEGER DEFAULT 0,
    monetized_requests INTEGER DEFAULT 0,
    blocked_requests INTEGER DEFAULT 0,
    total_revenue DECIMAL(12,6) DEFAULT 0.00,
    unique_bots INTEGER DEFAULT 0,
    unique_ips INTEGER DEFAULT 0,
    top_bot_types JSONB DEFAULT '{}',
    top_countries JSONB DEFAULT '{}',
    avg_confidence_score DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, date)
);

-- Real-time analytics cache
CREATE TABLE IF NOT EXISTS analytics_realtime (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,6) DEFAULT 0,
    time_window VARCHAR(20) DEFAULT '1h',
    expires_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, metric_name, time_window)
);

-- ==========================================
-- WEBSITE SPECIFIC TABLES
-- ==========================================

-- Beta applications
CREATE TABLE IF NOT EXISTS beta_applications (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    position VARCHAR(255),
    company VARCHAR(255),
    website VARCHAR(500),
    phone VARCHAR(50),
    resume_url VARCHAR(500),
    cover_letter TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    notes TEXT,
    reviewed_by VARCHAR(255),
    reviewed_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Waitlist entries
CREATE TABLE IF NOT EXISTS waitlist_entries (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    company VARCHAR(255),
    website VARCHAR(500),
    company_size VARCHAR(50),
    use_case TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    position INTEGER,
    invite_token VARCHAR(255),
    invited_at TIMESTAMP WITH TIME ZONE,
    joined_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Contact form submissions
CREATE TABLE IF NOT EXISTS contact_submissions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    message TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'new',
    responded_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Email logs for tracking
CREATE TABLE IF NOT EXISTS email_logs (
    id SERIAL PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    body TEXT,
    status VARCHAR(50) DEFAULT 'sent',
    provider VARCHAR(50) DEFAULT 'resend',
    provider_id VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- WEBHOOKS & NOTIFICATIONS
-- ==========================================

-- Webhook delivery tracking
CREATE TABLE IF NOT EXISTS webhooks (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    event_type VARCHAR(100) NOT NULL,
    payload JSONB NOT NULL,
    webhook_url VARCHAR(500),
    status VARCHAR(20) DEFAULT 'pending',
    attempts INTEGER DEFAULT 0,
    max_attempts INTEGER DEFAULT 3,
    last_attempt_at TIMESTAMP WITH TIME ZONE,
    next_attempt_at TIMESTAMP WITH TIME ZONE,
    response_code INTEGER,
    response_body TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Token blacklist for security
CREATE TABLE IF NOT EXISTS token_blacklist (
    id SERIAL PRIMARY KEY,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    token_type VARCHAR(50) NOT NULL,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    reason VARCHAR(255),
    blacklisted_by VARCHAR(255),
    blacklisted_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP WITH TIME ZONE
);

-- ==========================================
-- PERFORMANCE INDEXES
-- ==========================================

-- Core table indexes
CREATE INDEX IF NOT EXISTS idx_sites_api_key ON sites(api_key);
CREATE INDEX IF NOT EXISTS idx_sites_active ON sites(active);
CREATE INDEX IF NOT EXISTS idx_sites_subscription_tier ON sites(subscription_tier);

CREATE INDEX IF NOT EXISTS idx_bot_requests_site_id ON bot_requests(site_id);
CREATE INDEX IF NOT EXISTS idx_bot_requests_created_at ON bot_requests(created_at);
CREATE INDEX IF NOT EXISTS idx_bot_requests_bot_detected ON bot_requests(bot_detected);
CREATE INDEX IF NOT EXISTS idx_bot_requests_ip_address ON bot_requests(ip_address);
CREATE INDEX IF NOT EXISTS idx_bot_requests_bot_type ON bot_requests(bot_type);

CREATE INDEX IF NOT EXISTS idx_payments_site_id ON payments(site_id);
CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status);
CREATE INDEX IF NOT EXISTS idx_payments_created_at ON payments(created_at);

-- Authentication indexes
CREATE INDEX IF NOT EXISTS idx_api_keys_key_hash ON api_keys(key_hash);
CREATE INDEX IF NOT EXISTS idx_api_keys_site_id ON api_keys(site_id);
CREATE INDEX IF NOT EXISTS idx_api_keys_active ON api_keys(active);

CREATE INDEX IF NOT EXISTS idx_user_sessions_session_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_auth_token ON user_sessions(auth_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires_at ON user_sessions(expires_at);

CREATE INDEX IF NOT EXISTS idx_api_auth_auth_token ON api_authentication(auth_token);
CREATE INDEX IF NOT EXISTS idx_api_auth_site_id ON api_authentication(site_id);

-- Configuration indexes
CREATE INDEX IF NOT EXISTS idx_system_config_key ON system_config(config_key);
CREATE INDEX IF NOT EXISTS idx_system_config_category ON system_config(category);
CREATE INDEX IF NOT EXISTS idx_plugin_config_site_key ON plugin_config(site_id, config_key);
CREATE INDEX IF NOT EXISTS idx_headers_config_site_id ON headers_config(site_id);

-- Analytics indexes
CREATE INDEX IF NOT EXISTS idx_analytics_daily_site_date ON analytics_daily(site_id, date);
CREATE INDEX IF NOT EXISTS idx_analytics_realtime_site_metric ON analytics_realtime(site_id, metric_name);

-- Website indexes
CREATE INDEX IF NOT EXISTS idx_waitlist_email ON waitlist_entries(email);
CREATE INDEX IF NOT EXISTS idx_waitlist_status ON waitlist_entries(status);
CREATE INDEX IF NOT EXISTS idx_beta_applications_email ON beta_applications(email);
CREATE INDEX IF NOT EXISTS idx_email_logs_to_email ON email_logs(to_email);

-- ==========================================
-- TRIGGERS & FUNCTIONS
-- ==========================================

-- Update timestamp function
CREATE OR REPLACE FUNCTION update_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply update triggers
CREATE TRIGGER update_sites_timestamp BEFORE UPDATE ON sites FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER update_payments_timestamp BEFORE UPDATE ON payments FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER update_system_config_timestamp BEFORE UPDATE ON system_config FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER update_plugin_config_timestamp BEFORE UPDATE ON plugin_config FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER update_headers_config_timestamp BEFORE UPDATE ON headers_config FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER update_api_auth_timestamp BEFORE UPDATE ON api_authentication FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER update_analytics_realtime_timestamp BEFORE UPDATE ON analytics_realtime FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER update_beta_applications_timestamp BEFORE UPDATE ON beta_applications FOR EACH ROW EXECUTE FUNCTION update_timestamp();
CREATE TRIGGER update_waitlist_entries_timestamp BEFORE UPDATE ON waitlist_entries FOR EACH ROW EXECUTE FUNCTION update_timestamp();

-- Generate API key function
CREATE OR REPLACE FUNCTION generate_api_key()
RETURNS TEXT AS $$
BEGIN
    RETURN 'pk_' || substr(md5(random()::text || extract(epoch from now())::text), 1, 32);
END;
$$ LANGUAGE plpgsql;

-- Generate auth token function
CREATE OR REPLACE FUNCTION generate_auth_token(site_id_param INTEGER)
RETURNS TEXT AS $$
DECLARE
    new_token TEXT;
BEGIN
    new_token := 'auth_' || substr(md5(random()::text || site_id_param::text || extract(epoch from now())::text), 1, 32);

    UPDATE api_authentication
    SET auth_token = new_token,
        updated_at = CURRENT_TIMESTAMP,
        token_expires_at = CURRENT_TIMESTAMP + INTERVAL '24 hours'
    WHERE site_id = site_id_param AND is_active = true;

    RETURN new_token;
END;
$$ LANGUAGE plpgsql;

-- Validate auth token function
CREATE OR REPLACE FUNCTION validate_auth_token(token_param TEXT)
RETURNS TABLE(
    is_valid BOOLEAN,
    site_id INTEGER,
    api_key_id INTEGER,
    permissions JSONB,
    rate_limit JSONB
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        (aa.is_active AND (aa.token_expires_at IS NULL OR aa.token_expires_at > CURRENT_TIMESTAMP)) as is_valid,
        aa.site_id,
        aa.api_key_id,
        COALESCE(aa.middleware_config, '{}'::jsonb) as permissions,
        COALESCE(aa.rate_limit_config, '{}'::jsonb) as rate_limit
    FROM api_authentication aa
    WHERE aa.auth_token = token_param;
END;
$$ LANGUAGE plpgsql;

-- Update analytics function
CREATE OR REPLACE FUNCTION update_daily_analytics(site_id_param INTEGER, date_param DATE)
RETURNS VOID AS $$
BEGIN
    INSERT INTO analytics_daily (
        site_id,
        date,
        total_requests,
        bot_requests,
        monetized_requests,
        total_revenue,
        unique_bots,
        unique_ips
    )
    SELECT
        site_id_param,
        date_param,
        COUNT(*) as total_requests,
        COUNT(*) FILTER (WHERE bot_detected = true) as bot_requests,
        COUNT(*) FILTER (WHERE revenue_amount > 0) as monetized_requests,
        COALESCE(SUM(revenue_amount), 0) as total_revenue,
        COUNT(DISTINCT bot_type) FILTER (WHERE bot_detected = true) as unique_bots,
        COUNT(DISTINCT ip_address) as unique_ips
    FROM bot_requests
    WHERE site_id = site_id_param
    AND DATE(created_at) = date_param
    ON CONFLICT (site_id, date)
    DO UPDATE SET
        total_requests = EXCLUDED.total_requests,
        bot_requests = EXCLUDED.bot_requests,
        monetized_requests = EXCLUDED.monetized_requests,
        total_revenue = EXCLUDED.total_revenue,
        unique_bots = EXCLUDED.unique_bots,
        unique_ips = EXCLUDED.unique_ips;
END;
$$ LANGUAGE plpgsql;

-- ==========================================
-- VIEWS FOR EASY DATA ACCESS
-- ==========================================

-- Complete site configuration view
CREATE OR REPLACE VIEW site_complete_config AS
SELECT
    s.id as site_id,
    s.site_url,
    s.site_name,
    s.admin_email,
    s.api_key,
    s.subscription_tier,
    s.monetization_enabled,
    s.pricing_per_request,
    s.allowed_bots,
    s.active,
    -- Authentication info
    aa.auth_token,
    aa.token_type,
    aa.middleware_config,
    aa.rate_limit_config,
    -- Plugin configuration
    jsonb_object_agg(
        COALESCE(pc.config_key, 'no_config'),
        COALESCE(pc.config_value, 'null'::jsonb)
    ) FILTER (WHERE pc.id IS NOT NULL) as plugin_config,
    -- Headers configuration
    array_agg(
        jsonb_build_object(
            'name', hc.header_name,
            'value', CASE WHEN hc.header_name LIKE '%Auth%' OR hc.header_name LIKE '%Token%'
                         THEN '***MASKED***'
                         ELSE hc.header_value END,
            'type', hc.header_type,
            'required', hc.is_required
        )
    ) FILTER (WHERE hc.id IS NOT NULL) as headers_config,
    -- Recent activity
    s.last_activity,
    s.created_at,
    s.updated_at
FROM sites s
LEFT JOIN api_authentication aa ON s.id = aa.site_id AND aa.is_active = true
LEFT JOIN plugin_config pc ON s.id = pc.site_id AND pc.is_active = true
LEFT JOIN headers_config hc ON s.id = hc.site_id AND hc.is_active = true
WHERE s.active = true
GROUP BY s.id, s.site_url, s.site_name, s.admin_email, s.api_key, s.subscription_tier,
         s.monetization_enabled, s.pricing_per_request, s.allowed_bots, s.active,
         aa.auth_token, aa.token_type, aa.middleware_config, aa.rate_limit_config,
         s.last_activity, s.created_at, s.updated_at;

-- Site revenue summary view
CREATE OR REPLACE VIEW site_revenue_summary AS
SELECT
    s.id as site_id,
    s.site_url,
    s.subscription_tier,
    COUNT(br.id) as total_requests,
    COUNT(br.id) FILTER (WHERE br.bot_detected = true) as bot_requests,
    COUNT(br.id) FILTER (WHERE br.revenue_amount > 0) as monetized_requests,
    COALESCE(SUM(br.revenue_amount), 0) as total_revenue,
    COALESCE(AVG(br.revenue_amount) FILTER (WHERE br.revenue_amount > 0), 0) as avg_revenue_per_request,
    COUNT(DISTINCT br.bot_type) as unique_bot_types,
    COUNT(DISTINCT br.ip_address) as unique_visitors,
    MAX(br.created_at) as last_request_at
FROM sites s
LEFT JOIN bot_requests br ON s.id = br.site_id
WHERE s.active = true
GROUP BY s.id, s.site_url, s.subscription_tier;

-- Daily platform statistics view
CREATE OR REPLACE VIEW daily_platform_stats AS
SELECT
    ad.date,
    COUNT(DISTINCT ad.site_id) as active_sites,
    SUM(ad.total_requests) as total_requests,
    SUM(ad.bot_requests) as total_bot_requests,
    SUM(ad.monetized_requests) as total_monetized_requests,
    SUM(ad.total_revenue) as total_revenue,
    AVG(ad.total_revenue) as avg_revenue_per_site,
    SUM(ad.unique_bots) as total_unique_bots,
    SUM(ad.unique_ips) as total_unique_ips
FROM analytics_daily ad
GROUP BY ad.date
ORDER BY ad.date DESC;

-- ==========================================
-- SAMPLE DATA & CONFIGURATION
-- ==========================================

-- Insert essential system configuration
INSERT INTO system_config (config_key, config_value, description, category) VALUES
('api_base_url', '"https://paypercrawl.tech/api"', 'Base URL for API endpoints', 'api'),
('api_version', '"v1"', 'Current API version', 'api'),
('default_pricing', '{"per_request": 0.001, "currency": "USD"}', 'Default pricing configuration', 'billing'),
('rate_limits', '{"free": 1000, "pro": 5000, "enterprise": 10000}', 'Rate limiting by subscription tier', 'security'),
('allowed_origins', '["https://paypercrawl.tech", "https://creativeinteriorsstudio.com"]', 'CORS allowed origins', 'security'),
('webhook_retry_config', '{"max_attempts": 3, "retry_delay": 300}', 'Webhook retry configuration', 'webhooks'),
('bot_detection_config', '{"confidence_threshold": 80, "enabled_types": ["ChatGPT", "Claude", "Gemini", "Perplexity"]}', 'Bot detection settings', 'detection'),
('payment_config', '{"stripe_fee": 0.029, "platform_fee": 0.05, "min_payout": 10.00}', 'Payment processing configuration', 'billing'),
('auth_token_expiry', '{"default": 86400, "extended": 604800, "refresh": 2592000}', 'Authentication token expiry times in seconds', 'authentication'),
('security_headers', '{"x_frame_options": "DENY", "x_content_type_options": "nosniff", "x_xss_protection": "1; mode=block"}', 'Security headers configuration', 'authentication')
ON CONFLICT (config_key) DO NOTHING;

-- Insert configuration registry entries
INSERT INTO config_registry (registry_key, schema_reference, config_type, default_value, description, middleware_function) VALUES
('api.middleware.authenticate', 'api_authentication.auth_token', 'authentication', '{"require_active": true}', 'API authentication middleware', 'authenticateApiKey'),
('api.middleware.rate_limit', 'api_keys.rate_limit', 'security', '{"requests_per_minute": 60}', 'Rate limiting middleware', 'checkRateLimit'),
('bot.detection.confidence', 'system_config.bot_detection_config', 'detection', '{"threshold": 80}', 'Bot detection confidence threshold', 'detectBot'),
('payment.processing.stripe', 'system_config.payment_config', 'billing', '{"auto_payout": true}', 'Stripe payment processing', 'processPayment'),
('cors.validation', 'system_config.allowed_origins', 'security', '{"allow_credentials": true}', 'CORS validation middleware', 'validateCors'),
('headers.validation', 'headers_config', 'security', '{"required": ["Authorization"]}', 'Headers validation middleware', 'validateHeaders'),
('webhook.delivery', 'system_config.webhook_retry_config', 'webhooks', '{"async": true}', 'Webhook delivery system', 'deliverWebhook'),
('analytics.aggregation', 'analytics_daily', 'analytics', '{"real_time": true}', 'Analytics aggregation system', 'aggregateAnalytics')
ON CONFLICT (registry_key) DO NOTHING;

-- Insert sample AI companies
INSERT INTO ai_companies (company_name, contact_email, subscription_active, rate_per_request, subscription_tier) VALUES
('OpenAI', 'partnerships@openai.com', true, 0.002, 'enterprise'),
('Anthropic', 'business@anthropic.com', true, 0.0015, 'pro'),
('Google AI', 'ai-partnerships@google.com', true, 0.001, 'enterprise'),
('Microsoft AI', 'ai-partnerships@microsoft.com', true, 0.0012, 'pro'),
('Meta AI', 'ai-partnerships@meta.com', true, 0.001, 'pro'),
('Perplexity AI', 'partnerships@perplexity.ai', true, 0.0015, 'pro'),
('Cohere', 'partnerships@cohere.ai', false, 0.0012, 'free'),
('Hugging Face', 'partnerships@huggingface.co', false, 0.001, 'free')
ON CONFLICT (company_name) DO NOTHING;

-- Comments for documentation
COMMENT ON TABLE sites IS 'WordPress sites registered with PayPerCrawl plugin';
COMMENT ON TABLE bot_requests IS 'Bot detection events and monetization tracking';
COMMENT ON TABLE payments IS 'Payment transactions and revenue distribution';
COMMENT ON TABLE api_authentication IS 'API authentication tokens and middleware configuration';
COMMENT ON TABLE system_config IS 'Global system configuration settings';
COMMENT ON TABLE config_registry IS 'Registry mapping configuration to code functions';
COMMENT ON VIEW site_complete_config IS 'Complete configuration view for each registered site';
COMMENT ON VIEW site_revenue_summary IS 'Revenue analytics summary per site';
COMMENT ON VIEW daily_platform_stats IS 'Daily platform-wide statistics and metrics';
