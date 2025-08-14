-- CrawlGuard Database Schema - Step 2: Indexes and Performance

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
    permissions TEXT[],
    rate_limit INTEGER DEFAULT 1000,
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
    status VARCHAR(20) DEFAULT 'pending',
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
