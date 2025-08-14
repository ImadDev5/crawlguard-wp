-- License Management System Schema
-- Complete licensing infrastructure for SaaS monetization

-- License keys table - stores all generated licenses
CREATE TABLE licenses (
    id SERIAL PRIMARY KEY,
    license_key VARCHAR(255) NOT NULL UNIQUE,
    license_hash VARCHAR(64) NOT NULL UNIQUE, -- SHA-256 hash for secure storage
    checksum VARCHAR(8) NOT NULL, -- Checksum for validation
    site_id INTEGER REFERENCES sites(id) ON DELETE SET NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_name VARCHAR(255),
    
    -- License tier and pricing
    tier VARCHAR(20) NOT NULL DEFAULT 'free', -- free, pro, business, enterprise
    price DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    
    -- License state management
    status VARCHAR(20) NOT NULL DEFAULT 'inactive', -- inactive, active, suspended, expired, revoked
    activation_count INTEGER DEFAULT 0,
    max_activations INTEGER DEFAULT 1, -- Number of sites this license can activate
    
    -- Time-based controls
    valid_from TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    valid_until TIMESTAMP WITH TIME ZONE, -- NULL for lifetime licenses
    trial_ends_at TIMESTAMP WITH TIME ZONE, -- Trial expiration
    grace_period_ends_at TIMESTAMP WITH TIME ZONE, -- Grace period for network failures
    
    -- Stripe integration
    stripe_subscription_id VARCHAR(255),
    stripe_customer_id VARCHAR(255),
    stripe_payment_method_id VARCHAR(255),
    
    -- RSA signature for offline validation
    signature TEXT, -- RSA signature of license data
    public_key_version INTEGER DEFAULT 1, -- Track key rotation
    
    -- Metadata
    features JSONB DEFAULT '{}', -- Feature flags based on tier
    metadata JSONB DEFAULT '{}', -- Additional custom data
    notes TEXT,
    
    -- Audit fields
    created_by VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP WITH TIME ZONE,
    revoked_by VARCHAR(255),
    revoke_reason TEXT
);

-- License activations table - tracks where licenses are used
CREATE TABLE license_activations (
    id SERIAL PRIMARY KEY,
    license_id INTEGER REFERENCES licenses(id) ON DELETE CASCADE,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    site_url VARCHAR(255) NOT NULL,
    
    -- Activation details
    activation_token VARCHAR(64) NOT NULL UNIQUE, -- Unique token per activation
    machine_id VARCHAR(255), -- Hardware fingerprint for additional validation
    wordpress_version VARCHAR(20),
    plugin_version VARCHAR(20),
    php_version VARCHAR(20),
    
    -- State management
    status VARCHAR(20) DEFAULT 'active', -- active, deactivated, transferred
    activated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP WITH TIME ZONE,
    last_validated_at TIMESTAMP WITH TIME ZONE,
    
    -- Network failure handling
    last_heartbeat_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    offline_validation_count INTEGER DEFAULT 0,
    grace_period_used BOOLEAN DEFAULT FALSE,
    
    -- Security
    ip_address INET,
    user_agent TEXT,
    
    UNIQUE(license_id, site_url, status) -- Prevent duplicate active activations
);

-- License validation log - audit trail for all validation attempts
CREATE TABLE license_validations (
    id SERIAL PRIMARY KEY,
    license_id INTEGER REFERENCES licenses(id) ON DELETE CASCADE,
    activation_id INTEGER REFERENCES license_activations(id) ON DELETE CASCADE,
    
    -- Validation details
    validation_type VARCHAR(20) NOT NULL, -- online, offline, heartbeat
    validation_result VARCHAR(20) NOT NULL, -- valid, invalid, expired, suspended, rate_limited
    validation_message TEXT,
    
    -- Request details
    ip_address INET,
    user_agent TEXT,
    site_url VARCHAR(255),
    api_version VARCHAR(20),
    
    -- Performance metrics
    response_time_ms INTEGER,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- License transfers table - track license transfers between sites
CREATE TABLE license_transfers (
    id SERIAL PRIMARY KEY,
    license_id INTEGER REFERENCES licenses(id) ON DELETE CASCADE,
    from_site_id INTEGER REFERENCES sites(id) ON DELETE SET NULL,
    to_site_id INTEGER REFERENCES sites(id) ON DELETE SET NULL,
    from_site_url VARCHAR(255),
    to_site_url VARCHAR(255),
    
    -- Transfer details
    transfer_token VARCHAR(64) NOT NULL UNIQUE,
    transfer_reason TEXT,
    approved_by VARCHAR(255),
    
    -- State
    status VARCHAR(20) DEFAULT 'pending', -- pending, approved, rejected, completed
    requested_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP WITH TIME ZONE
);

-- RSA key pairs for offline validation
CREATE TABLE license_keys_rsa (
    id SERIAL PRIMARY KEY,
    version INTEGER NOT NULL UNIQUE,
    public_key TEXT NOT NULL,
    private_key_encrypted TEXT NOT NULL, -- Encrypted with master key
    algorithm VARCHAR(20) DEFAULT 'RS256',
    key_size INTEGER DEFAULT 2048,
    
    -- Key lifecycle
    status VARCHAR(20) DEFAULT 'active', -- active, rotating, retired
    valid_from TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    valid_until TIMESTAMP WITH TIME ZONE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Feature flags table - defines features available per tier
CREATE TABLE license_features (
    id SERIAL PRIMARY KEY,
    tier VARCHAR(20) NOT NULL,
    feature_key VARCHAR(100) NOT NULL,
    feature_name VARCHAR(255),
    feature_value JSONB DEFAULT 'true',
    description TEXT,
    
    -- Limits and quotas
    quota_limit INTEGER, -- NULL for unlimited
    quota_period VARCHAR(20), -- daily, weekly, monthly
    
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tier, feature_key)
);

-- Rate limiting table for API endpoints
CREATE TABLE license_rate_limits (
    id SERIAL PRIMARY KEY,
    license_id INTEGER REFERENCES licenses(id) ON DELETE CASCADE,
    endpoint VARCHAR(255) NOT NULL,
    
    -- Rate limit tracking
    request_count INTEGER DEFAULT 0,
    window_start TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    window_size_minutes INTEGER DEFAULT 60,
    max_requests INTEGER DEFAULT 100,
    
    -- Burst handling
    burst_tokens INTEGER DEFAULT 10,
    last_refill_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(license_id, endpoint, window_start)
);

-- License usage metrics for analytics
CREATE TABLE license_usage_metrics (
    id SERIAL PRIMARY KEY,
    license_id INTEGER REFERENCES licenses(id) ON DELETE CASCADE,
    activation_id INTEGER REFERENCES license_activations(id) ON DELETE CASCADE,
    
    -- Usage data
    metric_date DATE NOT NULL,
    api_calls INTEGER DEFAULT 0,
    bot_requests_monetized INTEGER DEFAULT 0,
    revenue_generated DECIMAL(10,6) DEFAULT 0.00,
    
    -- Feature usage
    features_used JSONB DEFAULT '{}',
    
    -- Performance
    average_response_time_ms INTEGER,
    error_count INTEGER DEFAULT 0,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(license_id, activation_id, metric_date)
);

-- Indexes for performance
CREATE INDEX idx_licenses_key ON licenses(license_key);
CREATE INDEX idx_licenses_hash ON licenses(license_hash);
CREATE INDEX idx_licenses_status ON licenses(status);
CREATE INDEX idx_licenses_tier ON licenses(tier);
CREATE INDEX idx_licenses_customer_email ON licenses(customer_email);
CREATE INDEX idx_licenses_stripe_subscription ON licenses(stripe_subscription_id);
CREATE INDEX idx_licenses_valid_until ON licenses(valid_until) WHERE valid_until IS NOT NULL;

CREATE INDEX idx_activations_license ON license_activations(license_id);
CREATE INDEX idx_activations_site ON license_activations(site_id);
CREATE INDEX idx_activations_token ON license_activations(activation_token);
CREATE INDEX idx_activations_status ON license_activations(status);
CREATE INDEX idx_activations_heartbeat ON license_activations(last_heartbeat_at);

CREATE INDEX idx_validations_license ON license_validations(license_id);
CREATE INDEX idx_validations_activation ON license_validations(activation_id);
CREATE INDEX idx_validations_created ON license_validations(created_at);
CREATE INDEX idx_validations_result ON license_validations(validation_result);

CREATE INDEX idx_rate_limits_license ON license_rate_limits(license_id);
CREATE INDEX idx_rate_limits_window ON license_rate_limits(window_start);

CREATE INDEX idx_usage_metrics_date ON license_usage_metrics(license_id, metric_date);

-- Functions for license management

-- Generate license key with checksum
CREATE OR REPLACE FUNCTION generate_license_key()
RETURNS TABLE(license_key VARCHAR, checksum VARCHAR) AS $$
DECLARE
    uuid_part VARCHAR;
    key_string VARCHAR;
    checksum_value VARCHAR;
BEGIN
    -- Generate UUID v4
    uuid_part := REPLACE(gen_random_uuid()::TEXT, '-', '');
    
    -- Create license key format: XXXX-XXXX-XXXX-XXXX
    key_string := UPPER(
        SUBSTRING(uuid_part, 1, 4) || '-' ||
        SUBSTRING(uuid_part, 5, 4) || '-' ||
        SUBSTRING(uuid_part, 9, 4) || '-' ||
        SUBSTRING(uuid_part, 13, 4)
    );
    
    -- Generate checksum (simplified - in production use proper algorithm)
    checksum_value := UPPER(SUBSTRING(MD5(key_string), 1, 8));
    
    RETURN QUERY SELECT key_string, checksum_value;
END;
$$ LANGUAGE plpgsql;

-- Validate license with grace period support
CREATE OR REPLACE FUNCTION validate_license(
    p_license_key VARCHAR,
    p_site_url VARCHAR
)
RETURNS TABLE(
    is_valid BOOLEAN,
    status VARCHAR,
    message TEXT,
    tier VARCHAR,
    features JSONB
) AS $$
DECLARE
    v_license licenses%ROWTYPE;
    v_activation license_activations%ROWTYPE;
    v_now TIMESTAMP WITH TIME ZONE := CURRENT_TIMESTAMP;
BEGIN
    -- Get license
    SELECT * INTO v_license 
    FROM licenses 
    WHERE license_key = p_license_key;
    
    IF NOT FOUND THEN
        RETURN QUERY SELECT FALSE, 'invalid'::VARCHAR, 'License key not found'::TEXT, NULL::VARCHAR, NULL::JSONB;
        RETURN;
    END IF;
    
    -- Check license status
    IF v_license.status = 'revoked' THEN
        RETURN QUERY SELECT FALSE, 'revoked'::VARCHAR, 'License has been revoked'::TEXT, v_license.tier, v_license.features;
        RETURN;
    END IF;
    
    IF v_license.status = 'suspended' THEN
        RETURN QUERY SELECT FALSE, 'suspended'::VARCHAR, 'License is suspended'::TEXT, v_license.tier, v_license.features;
        RETURN;
    END IF;
    
    -- Check expiration
    IF v_license.valid_until IS NOT NULL AND v_license.valid_until < v_now THEN
        -- Check grace period
        IF v_license.grace_period_ends_at IS NOT NULL AND v_license.grace_period_ends_at > v_now THEN
            RETURN QUERY SELECT TRUE, 'grace_period'::VARCHAR, 'License in grace period'::TEXT, v_license.tier, v_license.features;
            RETURN;
        ELSE
            RETURN QUERY SELECT FALSE, 'expired'::VARCHAR, 'License has expired'::TEXT, v_license.tier, v_license.features;
            RETURN;
        END IF;
    END IF;
    
    -- Check activation
    SELECT * INTO v_activation
    FROM license_activations
    WHERE license_id = v_license.id 
    AND site_url = p_site_url 
    AND status = 'active';
    
    IF NOT FOUND THEN
        -- Check if we can create new activation
        IF v_license.activation_count >= v_license.max_activations THEN
            RETURN QUERY SELECT FALSE, 'max_activations'::VARCHAR, 'Maximum activations reached'::TEXT, v_license.tier, v_license.features;
            RETURN;
        END IF;
    END IF;
    
    -- License is valid
    RETURN QUERY SELECT TRUE, 'valid'::VARCHAR, 'License is valid'::TEXT, v_license.tier, v_license.features;
END;
$$ LANGUAGE plpgsql;

-- Heartbeat function for license checking
CREATE OR REPLACE FUNCTION license_heartbeat(
    p_activation_token VARCHAR
)
RETURNS BOOLEAN AS $$
DECLARE
    v_activation license_activations%ROWTYPE;
    v_license licenses%ROWTYPE;
BEGIN
    -- Get activation
    SELECT * INTO v_activation
    FROM license_activations
    WHERE activation_token = p_activation_token
    AND status = 'active';
    
    IF NOT FOUND THEN
        RETURN FALSE;
    END IF;
    
    -- Update heartbeat
    UPDATE license_activations
    SET last_heartbeat_at = CURRENT_TIMESTAMP
    WHERE id = v_activation.id;
    
    -- Update last validated time
    UPDATE license_activations
    SET last_validated_at = CURRENT_TIMESTAMP
    WHERE id = v_activation.id;
    
    -- Log validation
    INSERT INTO license_validations (
        license_id, activation_id, validation_type, 
        validation_result, validation_message
    ) VALUES (
        v_activation.license_id, v_activation.id, 'heartbeat',
        'valid', 'Heartbeat successful'
    );
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql;

-- Insert default feature flags
INSERT INTO license_features (tier, feature_key, feature_name, feature_value, quota_limit, quota_period) VALUES
-- Free tier
('free', 'bot_detection', 'Bot Detection', 'true', NULL, NULL),
('free', 'analytics_dashboard', 'Analytics Dashboard', 'true', NULL, NULL),
('free', 'api_calls', 'API Calls', 'true', 1000, 'daily'),
('free', 'support', 'Community Support', 'true', NULL, NULL),

-- Pro tier
('pro', 'bot_detection', 'Bot Detection', 'true', NULL, NULL),
('pro', 'analytics_dashboard', 'Analytics Dashboard', 'true', NULL, NULL),
('pro', 'monetization', 'Full Monetization', 'true', NULL, NULL),
('pro', 'stripe_integration', 'Stripe Connect', 'true', NULL, NULL),
('pro', 'advanced_rules', 'Advanced Rule Engine', 'true', NULL, NULL),
('pro', 'api_calls', 'API Calls', 'true', 10000, 'daily'),
('pro', 'support', 'Priority Email Support', 'true', NULL, NULL),
('pro', 'custom_pricing', 'Custom Pricing Rules', 'false', NULL, NULL),

-- Business tier
('business', 'bot_detection', 'Bot Detection', 'true', NULL, NULL),
('business', 'analytics_dashboard', 'Advanced Analytics', 'true', NULL, NULL),
('business', 'monetization', 'Full Monetization', 'true', NULL, NULL),
('business', 'stripe_integration', 'Stripe Connect', 'true', NULL, NULL),
('business', 'advanced_rules', 'Advanced Rule Engine', 'true', NULL, NULL),
('business', 'multi_site', 'Multi-site Management', 'true', 5, NULL),
('business', 'api_calls', 'API Calls', 'true', 100000, 'daily'),
('business', 'support', 'Phone & Email Support', 'true', NULL, NULL),
('business', 'custom_pricing', 'Custom Pricing Rules', 'true', NULL, NULL),
('business', 'white_label', 'White Label Options', 'true', NULL, NULL),
('business', 'advanced_reporting', 'Advanced Reporting', 'true', NULL, NULL);

-- Create view for license dashboard
CREATE VIEW license_dashboard AS
SELECT 
    l.id,
    l.license_key,
    l.customer_email,
    l.tier,
    l.status,
    l.activation_count,
    l.max_activations,
    l.valid_until,
    l.created_at,
    COUNT(DISTINCT la.id) FILTER (WHERE la.status = 'active') as active_sites,
    MAX(la.last_heartbeat_at) as last_seen,
    COALESCE(SUM(lum.revenue_generated), 0) as total_revenue,
    COALESCE(SUM(lum.api_calls), 0) as total_api_calls
FROM licenses l
LEFT JOIN license_activations la ON l.id = la.license_id
LEFT JOIN license_usage_metrics lum ON l.id = lum.license_id
GROUP BY l.id;
