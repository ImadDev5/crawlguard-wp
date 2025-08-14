-- System Configuration Export
INSERT INTO system_config (config_key, config_value, description, category) VALUES ('allowed_origins', '["https://paypercrawl.tech","https://creativeinteriorsstudio.com"]', 'CORS allowed origins', 'security');
INSERT INTO system_config (config_key, config_value, description, category) VALUES ('api_base_url', '"https://paypercrawl.tech/api"', 'Base URL for API endpoints', 'api');
INSERT INTO system_config (config_key, config_value, description, category) VALUES ('api_version', '"v1"', 'Current API version', 'api');
INSERT INTO system_config (config_key, config_value, description, category) VALUES ('bot_detection_config', '{"enabled_types":["ChatGPT","Claude","Gemini"],"confidence_threshold":80}', 'Bot detection settings', 'detection');
INSERT INTO system_config (config_key, config_value, description, category) VALUES ('default_pricing', '{"currency":"USD","per_request":0.001}', 'Default pricing configuration', 'billing');
INSERT INTO system_config (config_key, config_value, description, category) VALUES ('payment_config', '{"stripe_fee":0.029,"platform_fee":0.05}', 'Payment processing configuration', 'billing');
INSERT INTO system_config (config_key, config_value, description, category) VALUES ('rate_limits', '{"default":1000,"premium":5000,"enterprise":10000}', 'Rate limiting configuration', 'security');
INSERT INTO system_config (config_key, config_value, description, category) VALUES ('webhook_retry_config', '{"retry_delay":300,"max_attempts":3}', 'Webhook retry configuration', 'webhooks');
-- Site: https://darkslategrey-grouse-900069.hostingersite.com
INSERT INTO sites (site_url, site_name, admin_email, api_key, subscription_tier, monetization_enabled, pricing_per_request, allowed_bots, active) VALUES ('https://darkslategrey-grouse-900069.hostingersite.com', 'Hostinger Live Site', 'suhailult777@gmail.com', '02a807307a31f0331a9dad8c1f8cdac5b119213766e13fa148ff38879e664dd4', 'pro', true, 0.001000, ARRAY['googlebot', 'bingbot'], true);
-- Site: https://creativeinteriorsstudio.com
INSERT INTO sites (site_url, site_name, admin_email, api_key, subscription_tier, monetization_enabled, pricing_per_request, allowed_bots, active) VALUES ('https://creativeinteriorsstudio.com', 'Creative Interiors Studio', 'admin@creativeinteriorsstudio.com', '8daa680254d9931eb36fd01f031b0dee3a0cb140e6b456192368f2f38b06bb36', 'free', false, 0.001000, ARRAY[], true);
-- Site: https://blogging-website-s.netlify.app
INSERT INTO sites (site_url, site_name, admin_email, api_key, subscription_tier, monetization_enabled, pricing_per_request, allowed_bots, active) VALUES ('https://blogging-website-s.netlify.app', 'Blogging Website', 'admin@blogging-website.com', '43556d24756b42363611702e6022d323fdc8d60aae11af9558d4a55cba72aef2', 'free', false, 0.001000, ARRAY[], true);
INSERT INTO ai_companies (company_name, contact_email, subscription_active, rate_per_request) VALUES ('Anthropic', 'business@anthropic.com', false, 0.001500);
INSERT INTO ai_companies (company_name, contact_email, subscription_active, rate_per_request) VALUES ('Google AI', 'ai-partnerships@google.com', false, 0.001000);
INSERT INTO ai_companies (company_name, contact_email, subscription_active, rate_per_request) VALUES ('Meta AI', 'ai-data@meta.com', false, 0.001000);
INSERT INTO ai_companies (company_name, contact_email, subscription_active, rate_per_request) VALUES ('Microsoft AI', 'ai-licensing@microsoft.com', false, 0.001200);
INSERT INTO ai_companies (company_name, contact_email, subscription_active, rate_per_request) VALUES ('OpenAI', 'partnerships@openai.com', false, 0.002000);
