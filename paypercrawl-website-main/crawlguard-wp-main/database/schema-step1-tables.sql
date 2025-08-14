-- CrawlGuard Database Schema - Step 1: Core Tables

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
    allowed_bots TEXT[],
    stripe_account_id VARCHAR(255),
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
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
    allowed_sites TEXT[],
    stripe_customer_id VARCHAR(255),
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
    action_taken VARCHAR(20) DEFAULT 'logged',
    revenue_amount DECIMAL(10,6) DEFAULT 0.00,
    payment_id VARCHAR(255),
    ai_company_id INTEGER REFERENCES ai_companies(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Payments table - tracks all financial transactions
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    ai_company_id INTEGER REFERENCES ai_companies(id),
    payment_intent_id VARCHAR(255) NOT NULL UNIQUE,
    amount DECIMAL(10,6) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) NOT NULL,
    stripe_fee DECIMAL(10,6) DEFAULT 0.00,
    platform_fee DECIMAL(10,6) DEFAULT 0.00,
    creator_payout DECIMAL(10,6) DEFAULT 0.00,
    bot_request_id INTEGER REFERENCES bot_requests(id),
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
