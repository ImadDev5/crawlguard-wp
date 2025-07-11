-- CrawlGuard Database Schema
-- PostgreSQL database for storing site data, bot requests, and financial transactions

-- Sites table - stores registered WordPress sites
CREATE TABLE sites (
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
    allowed_bots TEXT[], -- Array of allowed bot types
    stripe_account_id VARCHAR(255), -- Stripe Connect account ID
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Bot requests table - logs all bot detection and monetization events
CREATE TABLE bot_requests (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    ip_address INET NOT NULL,
    user_agent TEXT NOT NULL,
    bot_detected BOOLEAN DEFAULT false,
    bot_type VARCHAR(100),
    bot_name VARCHAR(100),
    confidence_score INTEGER DEFAULT 0,
    page_url TEXT,
    content_type VARCHAR(50),
    content_length INTEGER DEFAULT 0,
    action_taken VARCHAR(20) DEFAULT 'logged', -- logged, allowed, blocked, monetized
    revenue_amount DECIMAL(10,6) DEFAULT 0.00,
    payment_id VARCHAR(255), -- Stripe payment intent ID
    ai_company_id INTEGER, -- Reference to AI companies table
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- AI companies table - tracks AI companies and their subscriptions
CREATE TABLE ai_companies (
    id SERIAL PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL UNIQUE,
    contact_email VARCHAR(255),
    subscription_active BOOLEAN DEFAULT false,
    subscription_tier VARCHAR(50),
    monthly_budget DECIMAL(12,2),
    rate_per_request DECIMAL(10,6) DEFAULT 0.001,
    allowed_sites TEXT[], -- Array of site IDs they have access to
    stripe_customer_id VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Payments table - tracks all financial transactions (ACID compliant)
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    ai_company_id INTEGER REFERENCES ai_companies(id),
    payment_intent_id VARCHAR(255) NOT NULL UNIQUE,
    amount DECIMAL(10,6) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) NOT NULL, -- pending, succeeded, failed, canceled
    stripe_fee DECIMAL(10,6) DEFAULT 0.00,
    platform_fee DECIMAL(10,6) DEFAULT 0.00,
    creator_payout DECIMAL(10,6) DEFAULT 0.00,
    bot_request_id INTEGER REFERENCES bot_requests(id),
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Analytics aggregations table - for faster dashboard queries
CREATE TABLE analytics_daily (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    total_requests INTEGER DEFAULT 0,
    bot_requests INTEGER DEFAULT 0,
    monetized_requests INTEGER DEFAULT 0,
    total_revenue DECIMAL(12,6) DEFAULT 0.00,
    unique_bots INTEGER DEFAULT 0,
    top_bot_types JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, date)
);

-- API keys table - for additional security and rate limiting
CREATE TABLE api_keys (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    key_hash VARCHAR(64) NOT NULL UNIQUE,
    key_name VARCHAR(100),
    permissions TEXT[], -- Array of allowed permissions
    rate_limit INTEGER DEFAULT 1000, -- Requests per hour
    last_used_at TIMESTAMP WITH TIME ZONE,
    expires_at TIMESTAMP WITH TIME ZONE,
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Webhooks table - for tracking webhook deliveries
CREATE TABLE webhooks (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    event_type VARCHAR(100) NOT NULL,
    payload JSONB NOT NULL,
    status VARCHAR(20) DEFAULT 'pending', -- pending, delivered, failed
    attempts INTEGER DEFAULT 0,
    last_attempt_at TIMESTAMP WITH TIME ZONE,
    next_attempt_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX idx_sites_api_key ON sites(api_key);
CREATE INDEX idx_sites_active ON sites(active) WHERE active = true;

CREATE INDEX idx_bot_requests_site_id ON bot_requests(site_id);
CREATE INDEX idx_bot_requests_created_at ON bot_requests(created_at);
CREATE INDEX idx_bot_requests_bot_detected ON bot_requests(bot_detected) WHERE bot_detected = true;
CREATE INDEX idx_bot_requests_revenue ON bot_requests(revenue_amount) WHERE revenue_amount > 0;

CREATE INDEX idx_payments_site_id ON payments(site_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_created_at ON payments(created_at);

CREATE INDEX idx_analytics_site_date ON analytics_daily(site_id, date);

-- Triggers for updated_at timestamps
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_sites_updated_at BEFORE UPDATE ON sites
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_ai_companies_updated_at BEFORE UPDATE ON ai_companies
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_payments_updated_at BEFORE UPDATE ON payments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to aggregate daily analytics
CREATE OR REPLACE FUNCTION aggregate_daily_analytics(target_date DATE DEFAULT CURRENT_DATE - INTERVAL '1 day')
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
        top_bot_types
    )
    SELECT 
        site_id,
        target_date,
        COUNT(*) as total_requests,
        COUNT(*) FILTER (WHERE bot_detected = true) as bot_requests,
        COUNT(*) FILTER (WHERE revenue_amount > 0) as monetized_requests,
        COALESCE(SUM(revenue_amount), 0) as total_revenue,
        COUNT(DISTINCT bot_name) FILTER (WHERE bot_detected = true) as unique_bots,
        jsonb_object_agg(
            bot_name, 
            COUNT(*)
        ) FILTER (WHERE bot_detected = true AND bot_name IS NOT NULL) as top_bot_types
    FROM bot_requests
    WHERE DATE(created_at) = target_date
    GROUP BY site_id
    ON CONFLICT (site_id, date) 
    DO UPDATE SET
        total_requests = EXCLUDED.total_requests,
        bot_requests = EXCLUDED.bot_requests,
        monetized_requests = EXCLUDED.monetized_requests,
        total_revenue = EXCLUDED.total_revenue,
        unique_bots = EXCLUDED.unique_bots,
        top_bot_types = EXCLUDED.top_bot_types;
END;
$$ LANGUAGE plpgsql;

-- Sample data for development
INSERT INTO ai_companies (company_name, contact_email, subscription_active, rate_per_request) VALUES
('OpenAI', 'partnerships@openai.com', false, 0.002),
('Anthropic', 'business@anthropic.com', false, 0.0015),
('Google AI', 'ai-partnerships@google.com', false, 0.001),
('Microsoft AI', 'ai-licensing@microsoft.com', false, 0.0012),
('Meta AI', 'ai-data@meta.com', false, 0.001);

-- Views for common queries
CREATE VIEW site_revenue_summary AS
SELECT 
    s.id,
    s.site_url,
    s.site_name,
    s.subscription_tier,
    COALESCE(SUM(br.revenue_amount), 0) as total_revenue,
    COUNT(br.id) as total_requests,
    COUNT(br.id) FILTER (WHERE br.bot_detected = true) as bot_requests,
    COUNT(br.id) FILTER (WHERE br.revenue_amount > 0) as monetized_requests
FROM sites s
LEFT JOIN bot_requests br ON s.id = br.site_id
WHERE s.active = true
GROUP BY s.id, s.site_url, s.site_name, s.subscription_tier;

CREATE VIEW daily_platform_stats AS
SELECT 
    DATE(created_at) as date,
    COUNT(DISTINCT site_id) as active_sites,
    COUNT(*) as total_requests,
    COUNT(*) FILTER (WHERE bot_detected = true) as bot_requests,
    COUNT(*) FILTER (WHERE revenue_amount > 0) as monetized_requests,
    COALESCE(SUM(revenue_amount), 0) as total_revenue
FROM bot_requests
GROUP BY DATE(created_at)
ORDER BY date DESC;
