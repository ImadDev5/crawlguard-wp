-- Add Missing Configuration Tables and Data
-- This will restore your registry system and headers configuration

-- 1. System Configuration Table (Global Settings)
CREATE TABLE IF NOT EXISTS system_config (
    id SERIAL PRIMARY KEY,
    config_key VARCHAR(255) NOT NULL UNIQUE,
    config_value JSONB NOT NULL,
    description TEXT,
    is_public BOOLEAN DEFAULT false,
    category VARCHAR(100) DEFAULT 'general',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. Plugin Configuration Table (Per-site plugin settings)
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

-- 3. Headers Configuration Table (API Headers and Registry)
CREATE TABLE IF NOT EXISTS headers_config (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    header_name VARCHAR(255) NOT NULL,
    header_value TEXT NOT NULL,
    header_type VARCHAR(50) DEFAULT 'api', -- api, cors, security, custom
    is_required BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 4. Registry Table (Connects configuration to schema/code)
CREATE TABLE IF NOT EXISTS config_registry (
    id SERIAL PRIMARY KEY,
    registry_key VARCHAR(255) NOT NULL UNIQUE,
    schema_reference VARCHAR(255) NOT NULL, -- Points to schema/code location
    config_type VARCHAR(100) NOT NULL, -- header, setting, api, webhook, etc.
    default_value JSONB,
    validation_rules JSONB,
    description TEXT,
    is_system BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_system_config_key ON system_config(config_key);
CREATE INDEX IF NOT EXISTS idx_system_config_category ON system_config(category);
CREATE INDEX IF NOT EXISTS idx_plugin_config_site_key ON plugin_config(site_id, config_key);
CREATE INDEX IF NOT EXISTS idx_headers_config_site ON headers_config(site_id);
CREATE INDEX IF NOT EXISTS idx_headers_config_type ON headers_config(header_type);
CREATE INDEX IF NOT EXISTS idx_config_registry_key ON config_registry(registry_key);
CREATE INDEX IF NOT EXISTS idx_config_registry_type ON config_registry(config_type);

-- Insert Essential System Configuration
INSERT INTO system_config (config_key, config_value, description, category) VALUES
('api_base_url', '"https://paypercrawl.tech/api"', 'Base URL for API endpoints', 'api'),
('api_version', '"v1"', 'Current API version', 'api'),
('default_pricing', '{"per_request": 0.001, "currency": "USD"}', 'Default pricing configuration', 'billing'),
('rate_limits', '{"default": 1000, "premium": 5000, "enterprise": 10000}', 'Rate limiting configuration', 'security'),
('allowed_origins', '["https://paypercrawl.tech", "https://creativeinteriorsstudio.com"]', 'CORS allowed origins', 'security'),
('webhook_retry_config', '{"max_attempts": 3, "retry_delay": 300}', 'Webhook retry configuration', 'webhooks'),
('bot_detection_config', '{"confidence_threshold": 80, "enabled_types": ["ChatGPT", "Claude", "Gemini"]}', 'Bot detection settings', 'detection'),
('payment_config', '{"stripe_fee": 0.029, "platform_fee": 0.05}', 'Payment processing configuration', 'billing')
ON CONFLICT (config_key) DO NOTHING;

-- Insert Registry Entries (Connects config to your code)
INSERT INTO config_registry (registry_key, schema_reference, config_type, default_value, description) VALUES
('api.headers.authorization', 'sites.api_key', 'header', '{"required": true, "format": "Bearer {api_key}"}', 'API authorization header'),
('api.headers.content_type', 'system_config.api_content_type', 'header', '{"value": "application/json"}', 'API content type header'),
('api.headers.user_agent', 'system_config.user_agent', 'header', '{"value": "PayPerCrawl-Plugin/1.0"}', 'User agent header'),
('bot.detection.confidence', 'system_config.bot_detection_config', 'setting', '{"threshold": 80}', 'Bot detection confidence threshold'),
('billing.pricing.per_request', 'sites.pricing_per_request', 'setting', '{"value": 0.001}', 'Per-request pricing'),
('security.rate_limit', 'api_keys.rate_limit', 'setting', '{"default": 1000}', 'API rate limiting'),
('webhooks.retry_attempts', 'system_config.webhook_retry_config', 'setting', '{"max": 3}', 'Webhook retry attempts'),
('cors.allowed_origins', 'system_config.allowed_origins', 'header', '{"origins": ["*"]}', 'CORS allowed origins')
ON CONFLICT (registry_key) DO NOTHING;

-- Insert Default Headers Configuration for existing sites
INSERT INTO headers_config (site_id, header_name, header_value, header_type, is_required)
SELECT 
    s.id,
    'Authorization',
    'Bearer ' || s.api_key,
    'api',
    true
FROM sites s
WHERE s.active = true
ON CONFLICT DO NOTHING;

INSERT INTO headers_config (site_id, header_name, header_value, header_type, is_required)
SELECT 
    s.id,
    'Content-Type',
    'application/json',
    'api',
    true
FROM sites s
WHERE s.active = true
ON CONFLICT DO NOTHING;

INSERT INTO headers_config (site_id, header_name, header_value, header_type, is_required)
SELECT 
    s.id,
    'User-Agent',
    'PayPerCrawl-Plugin/' || COALESCE(s.plugin_version, '1.0'),
    'api',
    false
FROM sites s
WHERE s.active = true
ON CONFLICT DO NOTHING;

-- Insert Default Plugin Configuration for existing sites
INSERT INTO plugin_config (site_id, config_key, config_value, config_type)
SELECT 
    s.id,
    'bot_detection_enabled',
    'true',
    'feature'
FROM sites s
WHERE s.active = true
ON CONFLICT (site_id, config_key) DO NOTHING;

INSERT INTO plugin_config (site_id, config_key, config_value, config_type)
SELECT 
    s.id,
    'monetization_settings',
    jsonb_build_object(
        'enabled', s.monetization_enabled,
        'pricing_per_request', s.pricing_per_request,
        'allowed_bots', COALESCE(s.allowed_bots, ARRAY[]::text[])
    ),
    'billing'
FROM sites s
WHERE s.active = true
ON CONFLICT (site_id, config_key) DO NOTHING;

-- Add update triggers for timestamps
CREATE OR REPLACE FUNCTION update_config_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_system_config_timestamp
    BEFORE UPDATE ON system_config
    FOR EACH ROW
    EXECUTE FUNCTION update_config_timestamp();

CREATE TRIGGER update_plugin_config_timestamp
    BEFORE UPDATE ON plugin_config
    FOR EACH ROW
    EXECUTE FUNCTION update_config_timestamp();

CREATE TRIGGER update_headers_config_timestamp
    BEFORE UPDATE ON headers_config
    FOR EACH ROW
    EXECUTE FUNCTION update_config_timestamp();

CREATE TRIGGER update_config_registry_timestamp
    BEFORE UPDATE ON config_registry
    FOR EACH ROW
    EXECUTE FUNCTION update_config_timestamp();

-- Create views for easy configuration access
CREATE OR REPLACE VIEW site_full_config AS
SELECT 
    s.id as site_id,
    s.site_url,
    s.api_key,
    s.subscription_tier,
    s.monetization_enabled,
    s.pricing_per_request,
    s.allowed_bots,
    jsonb_object_agg(pc.config_key, pc.config_value) as plugin_config,
    array_agg(
        jsonb_build_object(
            'name', hc.header_name,
            'value', hc.header_value,
            'type', hc.header_type,
            'required', hc.is_required
        )
    ) FILTER (WHERE hc.id IS NOT NULL) as headers
FROM sites s
LEFT JOIN plugin_config pc ON s.id = pc.site_id AND pc.is_active = true
LEFT JOIN headers_config hc ON s.id = hc.site_id AND hc.is_active = true
WHERE s.active = true
GROUP BY s.id, s.site_url, s.api_key, s.subscription_tier, s.monetization_enabled, s.pricing_per_request, s.allowed_bots;

COMMENT ON TABLE system_config IS 'Global system configuration settings';
COMMENT ON TABLE plugin_config IS 'Per-site plugin configuration settings';
COMMENT ON TABLE headers_config IS 'API headers configuration for each site';
COMMENT ON TABLE config_registry IS 'Registry connecting configuration to schema/code references';
COMMENT ON VIEW site_full_config IS 'Complete configuration view for each site including headers and plugin settings';
