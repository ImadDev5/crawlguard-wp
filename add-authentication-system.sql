-- Add Complete Authentication System with Registry Integration
-- This will restore your API authentication, middleware, and token management

-- 1. User Authentication Table (for login sessions)
CREATE TABLE IF NOT EXISTS user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    auth_token VARCHAR(255) NOT NULL UNIQUE,
    refresh_token VARCHAR(255),
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    ip_address INET,
    user_agent TEXT,
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    is_active BOOLEAN DEFAULT true,
    login_method VARCHAR(50) DEFAULT 'api_key',
    permissions JSONB DEFAULT '{}',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. API Authentication Middleware Table
CREATE TABLE IF NOT EXISTS api_authentication (
    id SERIAL PRIMARY KEY,
    api_key_id INTEGER REFERENCES api_keys(id) ON DELETE CASCADE,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    auth_token VARCHAR(255) NOT NULL UNIQUE,
    token_type VARCHAR(50) DEFAULT 'bearer', -- bearer, basic, custom
    middleware_config JSONB NOT NULL DEFAULT '{}',
    rate_limit_config JSONB DEFAULT '{"requests_per_minute": 60, "burst_limit": 100}',
    authentication_method VARCHAR(100) DEFAULT 'api_key',
    required_headers JSONB DEFAULT '[]',
    allowed_origins JSONB DEFAULT '["*"]',
    token_expires_at TIMESTAMP WITH TIME ZONE,
    is_active BOOLEAN DEFAULT true,
    last_used TIMESTAMP WITH TIME ZONE,
    usage_count INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. Authentication Registry (connects auth to your registry system)
CREATE TABLE IF NOT EXISTS auth_registry (
    id SERIAL PRIMARY KEY,
    registry_key VARCHAR(255) NOT NULL UNIQUE,
    auth_method VARCHAR(100) NOT NULL, -- api_key, bearer_token, session, oauth
    middleware_function VARCHAR(255) NOT NULL, -- Function name in your code
    validation_rules JSONB NOT NULL DEFAULT '{}',
    required_permissions JSONB DEFAULT '[]',
    rate_limit_rules JSONB DEFAULT '{}',
    cache_config JSONB DEFAULT '{}',
    error_responses JSONB DEFAULT '{}',
    is_system BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 4. Authentication Logs (for debugging and monitoring)
CREATE TABLE IF NOT EXISTS auth_logs (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    api_key_id INTEGER REFERENCES api_keys(id) ON DELETE SET NULL,
    session_id INTEGER REFERENCES user_sessions(id) ON DELETE SET NULL,
    auth_method VARCHAR(100) NOT NULL,
    auth_token_hash VARCHAR(255), -- Hashed version for security
    request_ip INET,
    request_headers JSONB,
    auth_status VARCHAR(50) NOT NULL, -- success, failed, expired, invalid
    failure_reason TEXT,
    endpoint_accessed VARCHAR(255),
    response_code INTEGER,
    processing_time_ms INTEGER,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 5. Token Blacklist (for revoked tokens)
CREATE TABLE IF NOT EXISTS token_blacklist (
    id SERIAL PRIMARY KEY,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    token_type VARCHAR(50) NOT NULL, -- auth_token, session_token, api_key
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    reason VARCHAR(255),
    blacklisted_by VARCHAR(255),
    blacklisted_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP WITH TIME ZONE
);

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_auth_token ON user_sessions(auth_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_site_active ON user_sessions(site_id, is_active);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires ON user_sessions(expires_at);

CREATE INDEX IF NOT EXISTS idx_api_auth_token ON api_authentication(auth_token);
CREATE INDEX IF NOT EXISTS idx_api_auth_site_active ON api_authentication(site_id, is_active);
CREATE INDEX IF NOT EXISTS idx_api_auth_key_id ON api_authentication(api_key_id);

CREATE INDEX IF NOT EXISTS idx_auth_registry_key ON auth_registry(registry_key);
CREATE INDEX IF NOT EXISTS idx_auth_registry_method ON auth_registry(auth_method);

CREATE INDEX IF NOT EXISTS idx_auth_logs_site_status ON auth_logs(site_id, auth_status);
CREATE INDEX IF NOT EXISTS idx_auth_logs_created ON auth_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_auth_logs_ip ON auth_logs(request_ip);

CREATE INDEX IF NOT EXISTS idx_token_blacklist_hash ON token_blacklist(token_hash);
CREATE INDEX IF NOT EXISTS idx_token_blacklist_expires ON token_blacklist(expires_at);

-- Insert Authentication Registry Entries (connects to your middleware)
INSERT INTO auth_registry (registry_key, auth_method, middleware_function, validation_rules, required_permissions) VALUES
('api.middleware.authenticate', 'api_key', 'authenticateApiKey', '{"require_active": true, "check_rate_limit": true}', '["api_access"]'),
('api.middleware.authorize', 'bearer_token', 'authorizeBearer', '{"require_valid_token": true, "check_expiry": true}', '["api_access"]'),
('api.middleware.session', 'session', 'validateSession', '{"require_active_session": true, "update_activity": true}', '["user_access"]'),
('api.middleware.rate_limit', 'any', 'checkRateLimit', '{"requests_per_minute": 60, "burst_limit": 100}', '[]'),
('api.middleware.cors', 'any', 'validateCors', '{"check_origin": true, "allow_credentials": true}', '[]'),
('api.middleware.headers', 'any', 'validateHeaders', '{"required_headers": ["Content-Type", "Authorization"]}', '[]'),
('bot.middleware.detect', 'api_key', 'detectBotRequest', '{"confidence_threshold": 80, "log_requests": true}', '["bot_detection"]'),
('payment.middleware.validate', 'api_key', 'validatePayment', '{"check_subscription": true, "verify_limits": true}', '["payment_access"]')
ON CONFLICT (registry_key) DO NOTHING;

-- Generate authentication tokens for existing sites
INSERT INTO api_authentication (api_key_id, site_id, auth_token, middleware_config, authentication_method)
SELECT
    ak.id,
    s.id,
    'auth_' || substr(md5(random()::text || s.id::text || ak.id::text), 1, 32),
    jsonb_build_object(
        'require_https', true,
        'validate_origin', true,
        'log_requests', true,
        'rate_limit_enabled', true,
        'cors_enabled', true
    ),
    'api_key'
FROM sites s
JOIN api_keys ak ON s.id = ak.site_id
WHERE s.active = true AND ak.is_active = true
ON CONFLICT DO NOTHING;

-- Update system configuration with authentication settings
INSERT INTO system_config (config_key, config_value, description, category) VALUES
('auth_token_expiry', '{"default": 3600, "extended": 86400, "refresh": 604800}', 'Authentication token expiry times in seconds', 'authentication'),
('auth_middleware_config', '{"require_https": true, "validate_origin": true, "log_all_requests": false}', 'Global middleware configuration', 'authentication'),
('rate_limiting', '{"default_rpm": 60, "burst_limit": 100, "premium_rpm": 300, "enterprise_rpm": 1000}', 'Rate limiting configuration by tier', 'authentication'),
('cors_config', '{"allow_credentials": true, "max_age": 86400, "allowed_methods": ["GET", "POST", "PUT", "DELETE"]}', 'CORS configuration', 'authentication'),
('security_headers', '{"x_frame_options": "DENY", "x_content_type_options": "nosniff", "x_xss_protection": "1; mode=block"}', 'Security headers configuration', 'authentication')
ON CONFLICT (config_key) DO NOTHING;

-- Add authentication configuration to existing plugin configs
INSERT INTO plugin_config (site_id, config_key, config_value, config_type)
SELECT 
    s.id,
    'authentication_enabled',
    'true',
    'security'
FROM sites s
WHERE s.active = true
ON CONFLICT (site_id, config_key) DO NOTHING;

INSERT INTO plugin_config (site_id, config_key, config_value, config_type)
SELECT 
    s.id,
    'middleware_settings',
    jsonb_build_object(
        'auth_required', true,
        'rate_limit_enabled', true,
        'cors_enabled', true,
        'logging_enabled', true,
        'token_validation', true
    ),
    'security'
FROM sites s
WHERE s.active = true
ON CONFLICT (site_id, config_key) DO NOTHING;

-- Add authentication headers to existing header configs
INSERT INTO headers_config (site_id, header_name, header_value, header_type, is_required)
SELECT 
    s.id,
    'X-Auth-Token',
    aa.auth_token,
    'authentication',
    true
FROM sites s
JOIN api_authentication aa ON s.id = aa.site_id
WHERE s.active = true AND aa.is_active = true
ON CONFLICT DO NOTHING;

-- Add update triggers for authentication tables
CREATE OR REPLACE FUNCTION update_auth_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_user_sessions_timestamp
    BEFORE UPDATE ON user_sessions
    FOR EACH ROW
    EXECUTE FUNCTION update_auth_timestamp();

CREATE TRIGGER update_api_authentication_timestamp
    BEFORE UPDATE ON api_authentication
    FOR EACH ROW
    EXECUTE FUNCTION update_auth_timestamp();

CREATE TRIGGER update_auth_registry_timestamp
    BEFORE UPDATE ON auth_registry
    FOR EACH ROW
    EXECUTE FUNCTION update_auth_timestamp();

-- Create authentication views for easy access
CREATE OR REPLACE VIEW site_authentication_config AS
SELECT 
    s.id as site_id,
    s.site_url,
    s.api_key,
    ak.key_name as api_key_name,
    ak.rate_limit as api_rate_limit,
    aa.auth_token,
    aa.token_type,
    aa.middleware_config,
    aa.rate_limit_config as auth_rate_limit,
    aa.is_active as auth_active,
    jsonb_object_agg(
        COALESCE(pc.config_key, 'no_config'), 
        COALESCE(pc.config_value, 'null'::jsonb)
    ) FILTER (WHERE pc.config_type = 'security') as security_config,
    array_agg(
        jsonb_build_object(
            'name', hc.header_name,
            'value', CASE WHEN hc.header_name LIKE '%Auth%' OR hc.header_name LIKE '%Token%' 
                         THEN '***MASKED***' 
                         ELSE hc.header_value END,
            'type', hc.header_type,
            'required', hc.is_required
        )
    ) FILTER (WHERE hc.header_type IN ('authentication', 'api')) as auth_headers
FROM sites s
LEFT JOIN api_keys ak ON s.id = ak.site_id AND ak.is_active = true
LEFT JOIN api_authentication aa ON s.id = aa.site_id AND aa.is_active = true
LEFT JOIN plugin_config pc ON s.id = pc.site_id AND pc.is_active = true
LEFT JOIN headers_config hc ON s.id = hc.site_id AND hc.is_active = true
WHERE s.active = true
GROUP BY s.id, s.site_url, s.api_key, ak.key_name, ak.rate_limit, aa.auth_token, aa.token_type, aa.middleware_config, aa.rate_limit_config, aa.is_active;

-- Function to generate new authentication token
CREATE OR REPLACE FUNCTION generate_auth_token(site_id_param INTEGER)
RETURNS TEXT AS $$
DECLARE
    new_token TEXT;
BEGIN
    new_token := 'auth_' || substr(md5(random()::text || site_id_param::text || extract(epoch from now())::text), 1, 32);

    UPDATE api_authentication
    SET auth_token = new_token,
        updated_at = CURRENT_TIMESTAMP,
        token_expires_at = CURRENT_TIMESTAMP + INTERVAL '1 hour'
    WHERE site_id = site_id_param AND is_active = true;

    RETURN new_token;
END;
$$ LANGUAGE plpgsql;

-- Function to validate authentication token
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

COMMENT ON TABLE user_sessions IS 'User login sessions with authentication tokens';
COMMENT ON TABLE api_authentication IS 'API authentication middleware configuration';
COMMENT ON TABLE auth_registry IS 'Registry connecting authentication methods to middleware functions';
COMMENT ON TABLE auth_logs IS 'Authentication request logs for monitoring and debugging';
COMMENT ON TABLE token_blacklist IS 'Revoked/blacklisted authentication tokens';
COMMENT ON VIEW site_authentication_config IS 'Complete authentication configuration for each site';
