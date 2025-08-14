-- CrawlGuard Database Schema - Step 3: Functions and Sample Data

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

-- Sample AI companies data
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
