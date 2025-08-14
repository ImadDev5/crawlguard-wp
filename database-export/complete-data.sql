-- Complete Database Data Export
-- Generated from Neon Database

-- Data for table: ai_companies (5 rows)
INSERT INTO ai_companies (id, company_name, contact_email, subscription_active, subscription_tier, monthly_budget, rate_per_request, allowed_sites, stripe_customer_id, created_at, updated_at) VALUES (1, 'OpenAI', 'partnerships@openai.com', false, NULL, NULL, '0.002000', NULL, NULL, '"2025-08-02T00:04:53.607Z"'::jsonb, '"2025-08-02T00:04:53.607Z"'::jsonb);
INSERT INTO ai_companies (id, company_name, contact_email, subscription_active, subscription_tier, monthly_budget, rate_per_request, allowed_sites, stripe_customer_id, created_at, updated_at) VALUES (2, 'Anthropic', 'business@anthropic.com', false, NULL, NULL, '0.001500', NULL, NULL, '"2025-08-02T00:04:53.607Z"'::jsonb, '"2025-08-02T00:04:53.607Z"'::jsonb);
INSERT INTO ai_companies (id, company_name, contact_email, subscription_active, subscription_tier, monthly_budget, rate_per_request, allowed_sites, stripe_customer_id, created_at, updated_at) VALUES (3, 'Google AI', 'ai-partnerships@google.com', false, NULL, NULL, '0.001000', NULL, NULL, '"2025-08-02T00:04:53.607Z"'::jsonb, '"2025-08-02T00:04:53.607Z"'::jsonb);
INSERT INTO ai_companies (id, company_name, contact_email, subscription_active, subscription_tier, monthly_budget, rate_per_request, allowed_sites, stripe_customer_id, created_at, updated_at) VALUES (4, 'Microsoft AI', 'ai-licensing@microsoft.com', false, NULL, NULL, '0.001200', NULL, NULL, '"2025-08-02T00:04:53.607Z"'::jsonb, '"2025-08-02T00:04:53.607Z"'::jsonb);
INSERT INTO ai_companies (id, company_name, contact_email, subscription_active, subscription_tier, monthly_budget, rate_per_request, allowed_sites, stripe_customer_id, created_at, updated_at) VALUES (5, 'Meta AI', 'ai-data@meta.com', false, NULL, NULL, '0.001000', NULL, NULL, '"2025-08-02T00:04:53.607Z"'::jsonb, '"2025-08-02T00:04:53.607Z"'::jsonb);

-- Data for table: beta_applications (1 rows)
INSERT INTO beta_applications (id, name, email, position, resumeUrl, phone, website, coverLetter, status, notes, createdAt, updatedAt) VALUES ('cmds0eeda0008kv044zg14gqf', 'Beta Test User', 'betatest1754003503294@example.com', 'Beta Tester', NULL, '+1234567890', 'https://testsite.com', 'This is a test beta application to verify that beta application emails are working properly.', 'pending', NULL, '"2025-07-31T17:41:45.214Z"'::jsonb, '"2025-07-31T17:41:45.214Z"'::jsonb);

-- Data for table: config_registry (8 rows)
INSERT INTO config_registry (id, registry_key, schema_reference, config_type, default_value, validation_rules, description, is_system, created_at, updated_at) VALUES (1, 'api.headers.authorization', 'sites.api_key', 'header', '{"format":"Bearer {api_key}","required":true}'::jsonb, NULL, 'API authorization header', false, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO config_registry (id, registry_key, schema_reference, config_type, default_value, validation_rules, description, is_system, created_at, updated_at) VALUES (2, 'api.headers.content_type', 'system_config.api_content_type', 'header', '{"value":"application/json"}'::jsonb, NULL, 'API content type header', false, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO config_registry (id, registry_key, schema_reference, config_type, default_value, validation_rules, description, is_system, created_at, updated_at) VALUES (3, 'api.headers.user_agent', 'system_config.user_agent', 'header', '{"value":"PayPerCrawl-Plugin/1.0"}'::jsonb, NULL, 'User agent header', false, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO config_registry (id, registry_key, schema_reference, config_type, default_value, validation_rules, description, is_system, created_at, updated_at) VALUES (4, 'bot.detection.confidence', 'system_config.bot_detection_config', 'setting', '{"threshold":80}'::jsonb, NULL, 'Bot detection confidence threshold', false, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO config_registry (id, registry_key, schema_reference, config_type, default_value, validation_rules, description, is_system, created_at, updated_at) VALUES (5, 'billing.pricing.per_request', 'sites.pricing_per_request', 'setting', '{"value":0.001}'::jsonb, NULL, 'Per-request pricing', false, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO config_registry (id, registry_key, schema_reference, config_type, default_value, validation_rules, description, is_system, created_at, updated_at) VALUES (6, 'security.rate_limit', 'api_keys.rate_limit', 'setting', '{"default":1000}'::jsonb, NULL, 'API rate limiting', false, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO config_registry (id, registry_key, schema_reference, config_type, default_value, validation_rules, description, is_system, created_at, updated_at) VALUES (7, 'webhooks.retry_attempts', 'system_config.webhook_retry_config', 'setting', '{"max":3}'::jsonb, NULL, 'Webhook retry attempts', false, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO config_registry (id, registry_key, schema_reference, config_type, default_value, validation_rules, description, is_system, created_at, updated_at) VALUES (8, 'cors.allowed_origins', 'system_config.allowed_origins', 'header', '{"origins":["*"]}'::jsonb, NULL, 'CORS allowed origins', false, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);

-- Data for table: contact_submissions (1 rows)
INSERT INTO contact_submissions (id, name, email, subject, message, status, createdAt, updatedAt) VALUES ('cmds0e4lq0006kv045ueehvoj', 'Contact Test User', 'contacttest@example.com', 'Testing Contact Form Email', 'This is a test message to verify that contact form emails are working properly and being sent to imaduddin.dev@gmail.com', 'pending', '"2025-07-31T17:41:32.559Z"'::jsonb, '"2025-07-31T17:41:32.559Z"'::jsonb);

-- Data for table: email_logs (46 rows)
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmdry0ijs0000k1048gl1t38i', 'test@example.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Test User,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_1d4cd865002b499792e44788ad5d4f12" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'sent', 'resend', '"2025-07-31T16:34:58.216Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds0blck0001kv04gs92vir2', 'emailtest1754003372445@example.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Email Test User,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#2</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #2</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T17:39:34.293Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds0blcx0002kv04y42g372z', 'emailtest1754003372445@example.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Email Test User,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#2</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #2</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T17:39:34.306Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds0dw9s0004kv0401icnjv8', 'finaltest1754003479963@example.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Final Email Test,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#3</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #3</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T17:41:21.760Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1cse40000lb04pa1e5lya', 'suhailult777@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi mohd suhail idrisi,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_529aa829538f4855b459821a70f2bf60" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:08:29.693Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds0dw9y0005kv04onooqm0r', 'finaltest1754003479963@example.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Final Email Test,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#3</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #3</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T17:41:21.766Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds0e4rf0007kv04sr3voqto', 'imaduddin.dev@gmail.com', 'New Contact Form Submission: Testing Contact Form Email', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Contact Form Submission</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #dc2626;">New Contact Form Submission</h1>
        
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
          <p><strong>Name:</strong> Contact Test User</p>
          <p><strong>Email:</strong> contacttest@example.com</p>
          <p><strong>Subject:</strong> Testing Contact Form Email</p>
          <p><strong>Message:</strong></p>
          <div style="background: white; padding: 15px; border-radius: 4px; margin-top: 10px;">
            This is a test message to verify that contact form emails are working properly and being sent to imaduddin.dev@gmail.com
          </div>
        </div>
        
        <p>Please respond to this inquiry promptly.</p>
      </div>
    </body>
    </html>
  ', 'sent', 'resend', '"2025-07-31T17:41:32.764Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds0eefq0009kv04up0qnv3x', 'betatest1754003503294@example.com', 'Application Received - PayPerCrawl Beta', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Application Received</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">Thank you for your application!</h1>
        
        <p>Hi Beta Test User,</p>
        
        <p>We''ve received your application for the PayPerCrawl beta program. Our team will review your application and get back to you within 2-3 business days.</p>
        
        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;">
          <h3 style="margin-top: 0;">What''s Next?</h3>
          <ul>
            <li>Our team will review your application</li>
            <li>We''ll contact you if we need additional information</li>
            <li>Selected candidates will receive beta access instructions</li>
          </ul>
        </div>
        
        <p>In the meantime, feel free to explore our website and learn more about how PayPerCrawl can help monetize your AI content.</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T17:41:45.302Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds0eefx000akv04mc51m67h', 'betatest1754003503294@example.com', 'Application Received - PayPerCrawl Beta', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Application Received</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">Thank you for your application!</h1>
        
        <p>Hi Beta Test User,</p>
        
        <p>We''ve received your application for the PayPerCrawl beta program. Our team will review your application and get back to you within 2-3 business days.</p>
        
        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;">
          <h3 style="margin-top: 0;">What''s Next?</h3>
          <ul>
            <li>Our team will review your application</li>
            <li>We''ll contact you if we need additional information</li>
            <li>Selected candidates will receive beta access instructions</li>
          </ul>
        </div>
        
        <p>In the meantime, feel free to explore our website and learn more about how PayPerCrawl can help monetize your AI content.</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T17:41:45.309Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds0syhs0001l804wovcu075', 'imaduddin.dev@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Imad Uddin,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#4</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #4</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'sent', 'resend', '"2025-07-31T17:53:04.480Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1aqr70001l204lh9tscky', 'mohdu0985@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi New Query,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#5</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #5</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:06:54.259Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1aqri0002l20457bxwwam', 'mohdu0985@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi New Query,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#5</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #5</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:06:54.271Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1cmy70001ju0432u7414m', 'suhailult777@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi mohd suhail idrisi,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#6</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #6</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:08:22.639Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1cmyl0002ju04hdravtwk', 'suhailult777@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi mohd suhail idrisi,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#6</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #6</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:08:22.653Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1csej0001lb04f0k39lvf', 'suhailult777@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi mohd suhail idrisi,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_529aa829538f4855b459821a70f2bf60" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:08:29.707Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1d5bi0003ju04uy1gwh5a', 'mohdu0985@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi New Query,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_ca7f93f24ce14d7e93e6b3e1aee2d1dc" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:08:46.447Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1d5bq0004ju047hycwduy', 'mohdu0985@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi New Query,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_ca7f93f24ce14d7e93e6b3e1aee2d1dc" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:08:46.454Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1fhso0002lb04fpvlgmpj', 'imaduddin.dev@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Imad Uddin,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_d7057713aec145479b292992310e6249" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'sent', 'resend', '"2025-07-31T18:10:35.929Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1mpfx0001k3041bbe0eyb', 'interiorsstudiocreative@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi New Queryvbbhv,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#7</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #7</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:16:12.429Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1mpga0002k30481m76e6s', 'interiorsstudiocreative@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi New Queryvbbhv,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#7</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #7</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:16:12.442Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1mwfw0003k304bkoa41wb', 'interiorsstudiocreative@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi New Queryvbbhv,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_6dd61f956ace4e2baa850b77538a52fc" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:16:21.501Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1mwg30004k304g4u07d8g', 'interiorsstudiocreative@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi New Queryvbbhv,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_6dd61f956ace4e2baa850b77538a52fc" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:16:21.507Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1ndwo0001l7046ld52bun', 'suhailult123@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi sidrisi123,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#8</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #8</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:16:44.136Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1ndwz0002l704mi029ijw', 'suhailult123@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi sidrisi123,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#8</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #8</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:16:44.148Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1o3c30005k3048ixiw9td', 'suhailult123@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi sidrisi123,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_a27796fdd69e4733b139bdfe88dce1a7" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:17:17.091Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1o3cc0006k304dhn8vxwg', 'suhailult123@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi sidrisi123,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_a27796fdd69e4733b139bdfe88dce1a7" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:17:17.100Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1what0001jp04i21kxqw2', 'freshtest1754006025741@example.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Fresh Test User,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#9</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #9</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:23:48.437Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1whb60002jp04iebghx44', 'freshtest1754006025741@example.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Fresh Test User,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#9</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #9</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:23:48.451Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1wpwx0003jp04ma6oq31t', 'freshtest1754006025741@example.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Fresh Test User,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_c9fcce900fdb4408bede679e2068b8a4" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:23:59.601Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1qi5a0007k304z8awb4xc', 'finaltest1754003479963@example.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Final Email Test,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_046a7154c4e9498580f58d9a59709959" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:19:09.599Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1qi5h0008k3046z9nr8u5', 'finaltest1754003479963@example.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Final Email Test,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_046a7154c4e9498580f58d9a59709959" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:19:09.605Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1qtzu0009k30472cz8s77', 'emailtest1754003372445@example.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Email Test User,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_85fa6da29e3d431db10b20a1b80bb9e9" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:19:24.955Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1qu01000ak304ckzha2iv', 'emailtest1754003372445@example.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Email Test User,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_85fa6da29e3d431db10b20a1b80bb9e9" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:19:24.962Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1zffy0001h204ezglcvgb', 'freshtest1754006163071@example.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Fresh Test User 2,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#11</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #11</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:26:05.998Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1wpx30004jp048uuf801t', 'freshtest1754006025741@example.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Fresh Test User,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_c9fcce900fdb4408bede679e2068b8a4" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:23:59.608Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1wvag0006jp04muphs47a', '160923753044@lords.ac.in', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Imad''s Team,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#10</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #10</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:24:06.568Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1wval0007jp04nr1veq41', '160923753044@lords.ac.in', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Imad''s Team,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#10</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #10</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:24:06.574Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1x2yo0008jp04vboranrl', '160923753044@lords.ac.in', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Imad''s Team,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_e071db8437ef464bad33175fb64fbb5f" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:24:16.512Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1x2zb0009jp04g8n6dr5e', '160923753044@lords.ac.in', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Imad''s Team,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_e071db8437ef464bad33175fb64fbb5f" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:24:16.535Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1zfge0002h204b43s938w', 'freshtest1754006163071@example.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi Fresh Test User 2,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#11</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #11</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:26:06.014Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1zht40003h2049objlpf0', 'freshtest1754006163071@example.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Fresh Test User 2,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_90c5344c1dad491995a73e5bfacc283c" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:26:09.064Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds1zhtd0004h204uff7mxwf', 'freshtest1754006163071@example.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi Fresh Test User 2,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_90c5344c1dad491995a73e5bfacc283c" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'failed', 'resend', '"2025-07-31T18:26:09.074Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds2thax0001jx04x1boywx0', 'newahmeddresses@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi CREATE TABLE,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#12</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #12</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'sent', 'resend', '"2025-07-31T18:49:28.090Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds2tvqd0000jy04hbckhg8m', 'newahmeddresses@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi CREATE TABLE,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_2181fd66b6a0497ea1509ff04fa8f663" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'sent', 'resend', '"2025-07-31T18:49:46.789Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds3cmkn0001i904vl8ktux0', 'c90198593@gmail.com', 'Welcome to PayPerCrawl Waitlist!', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Waitlist Confirmation</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">You''re on the waitlist! 🎉</h1>
        
        <p>Hi sidrisi64,</p>
        
        <p>Thank you for joining the PayPerCrawl waitlist! You''re currently <strong>#13</strong> in line.</p>
        
        <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
          <h3 style="margin-top: 0; color: #2563eb;">Your Position: #13</h3>
          <p style="margin-bottom: 0;">We''ll notify you as soon as beta access becomes available!</p>
        </div>
        
        <p>While you wait, here''s what you can do:</p>
        <ul>
          <li>Follow us on social media for updates</li>
          <li>Share PayPerCrawl with friends who create AI content</li>
          <li>Read our blog for AI monetization insights</li>
        </ul>
        
        <p>We''re working hard to launch the beta and can''t wait to help you monetize your AI content!</p>
        
        <p>Best regards,<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'sent', 'resend', '"2025-07-31T19:04:21.384Z"'::jsonb);
INSERT INTO email_logs (id, to, subject, body, status, provider, createdAt) VALUES ('cmds3d9qs0002i904fudw1sjm', 'c90198593@gmail.com', 'Your PayPerCrawl Beta Access is Ready! 🚀', '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Beta Access Ready</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
      <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Welcome to PayPerCrawl Beta! 🚀</h1>
        
        <p>Hi sidrisi64,</p>
        
        <p>Congratulations! You''ve been selected for the PayPerCrawl beta program. You can now start monetizing your AI content!</p>
        
        <div style="text-align: center; margin: 30px 0;">
          <a href="https://paypercrawl.tech/beta?token=invite_5cf74dcbd9e14ee4b09c93745d6136c1" style="background: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">
            Access Beta Dashboard
          </a>
        </div>
        
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
          <h3 style="margin-top: 0; color: #16a34a;">Getting Started</h3>
          <ol>
            <li>Click the button above to access your beta dashboard</li>
            <li>Install the PayPerCrawl WordPress plugin</li>
            <li>Configure your monetization settings</li>
            <li>Start earning from AI bot traffic!</li>
          </ol>
        </div>
        
        <p>Need help? Our support team is standing by to assist you with setup and configuration.</p>
        
        <p>Welcome aboard!<br>The PayPerCrawl Team</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="font-size: 12px; color: #6b7280;">
          PayPerCrawl - AI Content Monetization Platform<br>
          <a href="https://paypercrawl.tech">paypercrawl.tech</a>
        </p>
      </div>
    </body>
    </html>
  ', 'sent', 'resend', '"2025-07-31T19:04:51.413Z"'::jsonb);

-- Data for table: headers_config (9 rows)
INSERT INTO headers_config (id, site_id, header_name, header_value, header_type, is_required, is_active, created_at, updated_at) VALUES (1, 1, 'Authorization', 'Bearer 02a807307a31f0331a9dad8c1f8cdac5b119213766e13fa148ff38879e664dd4', 'api', true, true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO headers_config (id, site_id, header_name, header_value, header_type, is_required, is_active, created_at, updated_at) VALUES (2, 2, 'Authorization', 'Bearer 8daa680254d9931eb36fd01f031b0dee3a0cb140e6b456192368f2f38b06bb36', 'api', true, true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO headers_config (id, site_id, header_name, header_value, header_type, is_required, is_active, created_at, updated_at) VALUES (3, 3, 'Authorization', 'Bearer 43556d24756b42363611702e6022d323fdc8d60aae11af9558d4a55cba72aef2', 'api', true, true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO headers_config (id, site_id, header_name, header_value, header_type, is_required, is_active, created_at, updated_at) VALUES (4, 1, 'Content-Type', 'application/json', 'api', true, true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO headers_config (id, site_id, header_name, header_value, header_type, is_required, is_active, created_at, updated_at) VALUES (5, 2, 'Content-Type', 'application/json', 'api', true, true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO headers_config (id, site_id, header_name, header_value, header_type, is_required, is_active, created_at, updated_at) VALUES (6, 3, 'Content-Type', 'application/json', 'api', true, true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO headers_config (id, site_id, header_name, header_value, header_type, is_required, is_active, created_at, updated_at) VALUES (7, 1, 'User-Agent', 'PayPerCrawl-Plugin/1.0', 'api', false, true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO headers_config (id, site_id, header_name, header_value, header_type, is_required, is_active, created_at, updated_at) VALUES (8, 2, 'User-Agent', 'PayPerCrawl-Plugin/1.0', 'api', false, true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO headers_config (id, site_id, header_name, header_value, header_type, is_required, is_active, created_at, updated_at) VALUES (9, 3, 'User-Agent', 'PayPerCrawl-Plugin/1.0', 'api', false, true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);

-- Data for table: plugin_config (6 rows)
INSERT INTO plugin_config (id, site_id, config_key, config_value, config_type, is_active, created_at, updated_at) VALUES (1, 1, 'bot_detection_enabled', true, 'feature', true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO plugin_config (id, site_id, config_key, config_value, config_type, is_active, created_at, updated_at) VALUES (2, 2, 'bot_detection_enabled', true, 'feature', true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO plugin_config (id, site_id, config_key, config_value, config_type, is_active, created_at, updated_at) VALUES (3, 3, 'bot_detection_enabled', true, 'feature', true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO plugin_config (id, site_id, config_key, config_value, config_type, is_active, created_at, updated_at) VALUES (4, 1, 'monetization_settings', '{"enabled":true,"allowed_bots":["googlebot","bingbot"],"pricing_per_request":0.001}'::jsonb, 'billing', true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO plugin_config (id, site_id, config_key, config_value, config_type, is_active, created_at, updated_at) VALUES (5, 2, 'monetization_settings', '{"enabled":false,"allowed_bots":[],"pricing_per_request":0.001}'::jsonb, 'billing', true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO plugin_config (id, site_id, config_key, config_value, config_type, is_active, created_at, updated_at) VALUES (6, 3, 'monetization_settings', '{"enabled":false,"allowed_bots":[],"pricing_per_request":0.001}'::jsonb, 'billing', true, '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);

-- Data for table: sites (3 rows)
INSERT INTO sites (id, site_url, site_name, admin_email, api_key, plugin_version, wordpress_version, subscription_tier, monetization_enabled, pricing_per_request, allowed_bots, stripe_account_id, active, created_at, updated_at) VALUES (1, 'https://darkslategrey-grouse-900069.hostingersite.com', 'Hostinger Live Site', 'suhailult777@gmail.com', '02a807307a31f0331a9dad8c1f8cdac5b119213766e13fa148ff38879e664dd4', NULL, NULL, 'pro', true, '0.001000', ARRAY['googlebot', 'bingbot'], NULL, true, '"2025-08-02T00:16:56.965Z"'::jsonb, '"2025-08-02T00:16:56.965Z"'::jsonb);
INSERT INTO sites (id, site_url, site_name, admin_email, api_key, plugin_version, wordpress_version, subscription_tier, monetization_enabled, pricing_per_request, allowed_bots, stripe_account_id, active, created_at, updated_at) VALUES (2, 'https://creativeinteriorsstudio.com', 'Creative Interiors Studio', 'admin@creativeinteriorsstudio.com', '8daa680254d9931eb36fd01f031b0dee3a0cb140e6b456192368f2f38b06bb36', NULL, NULL, 'free', false, '0.001000', ARRAY[], NULL, true, '"2025-08-02T00:16:57.434Z"'::jsonb, '"2025-08-02T00:16:57.434Z"'::jsonb);
INSERT INTO sites (id, site_url, site_name, admin_email, api_key, plugin_version, wordpress_version, subscription_tier, monetization_enabled, pricing_per_request, allowed_bots, stripe_account_id, active, created_at, updated_at) VALUES (3, 'https://blogging-website-s.netlify.app', 'Blogging Website', 'admin@blogging-website.com', '43556d24756b42363611702e6022d323fdc8d60aae11af9558d4a55cba72aef2', NULL, NULL, 'free', false, '0.001000', ARRAY[], NULL, true, '"2025-08-02T00:16:57.880Z"'::jsonb, '"2025-08-02T00:16:57.880Z"'::jsonb);

-- Data for table: system_config (8 rows)
INSERT INTO system_config (id, config_key, config_value, description, is_public, category, created_at, updated_at) VALUES (1, 'api_base_url', 'https://paypercrawl.tech/api', 'Base URL for API endpoints', false, 'api', '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO system_config (id, config_key, config_value, description, is_public, category, created_at, updated_at) VALUES (2, 'api_version', 'v1', 'Current API version', false, 'api', '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO system_config (id, config_key, config_value, description, is_public, category, created_at, updated_at) VALUES (3, 'default_pricing', '{"currency":"USD","per_request":0.001}'::jsonb, 'Default pricing configuration', false, 'billing', '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO system_config (id, config_key, config_value, description, is_public, category, created_at, updated_at) VALUES (4, 'rate_limits', '{"default":1000,"premium":5000,"enterprise":10000}'::jsonb, 'Rate limiting configuration', false, 'security', '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO system_config (id, config_key, config_value, description, is_public, category, created_at, updated_at) VALUES (5, 'allowed_origins', ARRAY['https://paypercrawl.tech', 'https://creativeinteriorsstudio.com'], 'CORS allowed origins', false, 'security', '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO system_config (id, config_key, config_value, description, is_public, category, created_at, updated_at) VALUES (6, 'webhook_retry_config', '{"retry_delay":300,"max_attempts":3}'::jsonb, 'Webhook retry configuration', false, 'webhooks', '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO system_config (id, config_key, config_value, description, is_public, category, created_at, updated_at) VALUES (7, 'bot_detection_config', '{"enabled_types":["ChatGPT","Claude","Gemini"],"confidence_threshold":80}'::jsonb, 'Bot detection settings', false, 'detection', '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);
INSERT INTO system_config (id, config_key, config_value, description, is_public, category, created_at, updated_at) VALUES (8, 'payment_config', '{"stripe_fee":0.029,"platform_fee":0.05}'::jsonb, 'Payment processing configuration', false, 'billing', '"2025-08-02T00:31:49.682Z"'::jsonb, '"2025-08-02T00:31:49.682Z"'::jsonb);

-- Data for table: waitlist_entries (13 rows)
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmdrvb37a0000vqggdkdnfyk3', 'test@example.com', 'Test User', 'https://example.com', 'small', 'Testing the API', 'invited', 'invite_1d4cd865002b499792e44788ad5d4f12', '"2025-07-31T16:34:57.484Z"'::jsonb, '"2025-07-31T15:19:12.695Z"'::jsonb, '"2025-07-31T16:34:57.486Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds1cmu10000ju04tmsfz7pk', 'suhailult777@gmail.com', 'mohd suhail idrisi', 'https://dodgerblue-clam-568728.hostingersite.com/', 'medium', 'Monthly visitors: 100000. Reason: hi hello', 'invited', 'invite_529aa829538f4855b459821a70f2bf60', '"2025-07-31T18:08:29.555Z"'::jsonb, '"2025-07-31T18:08:22.489Z"'::jsonb, '"2025-07-31T18:08:29.556Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds1aqme0000l2042l4tsu25', 'mohdu0985@gmail.com', 'New Query', 'https://gemini.google.com/', 'medium', 'Monthly visitors: 244444. Reason: vhhhhhhhhhhhhhhh', 'invited', 'invite_ca7f93f24ce14d7e93e6b3e1aee2d1dc', '"2025-07-31T18:08:46.339Z"'::jsonb, '"2025-07-31T18:06:54.086Z"'::jsonb, '"2025-07-31T18:08:46.342Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds0sybw0000l8046zp1ll4i', 'imaduddin.dev@gmail.com', 'Imad Uddin', 'https://paypercrawl.tech', 'small', 'Testing my own PayPerCrawl website submission', 'invited', 'invite_d7057713aec145479b292992310e6249', '"2025-07-31T18:10:35.747Z"'::jsonb, '"2025-07-31T17:53:04.269Z"'::jsonb, '"2025-07-31T18:10:35.748Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds1mpb70000k304yrn32o56', 'interiorsstudiocreative@gmail.com', 'New Queryvbbhv', 'https://gemini.google.com/', 'medium', 'Monthly visitors: 244444. Reason: fhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh', 'invited', 'invite_6dd61f956ace4e2baa850b77538a52fc', '"2025-07-31T18:16:21.432Z"'::jsonb, '"2025-07-31T18:16:12.259Z"'::jsonb, '"2025-07-31T18:16:21.433Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds1ndt80000l7041ynffhxi', 'suhailult123@gmail.com', 'sidrisi123', 'https://dodgerblue-clam-568728.hostingersite.com/', 'medium', 'Monthly visitors: 100000. Reason: hi hello123', 'invited', 'invite_a27796fdd69e4733b139bdfe88dce1a7', '"2025-07-31T18:17:16.992Z"'::jsonb, '"2025-07-31T18:16:44.012Z"'::jsonb, '"2025-07-31T18:17:16.993Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds0dw7k0003kv04x9l5edcb', 'finaltest1754003479963@example.com', 'Final Email Test', 'https://testsite.com', 'medium', 'Final test to verify email functionality is working', 'invited', 'invite_046a7154c4e9498580f58d9a59709959', '"2025-07-31T18:19:09.510Z"'::jsonb, '"2025-07-31T17:41:21.681Z"'::jsonb, '"2025-07-31T18:19:09.511Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds0bl8e0000kv04hqv1x16u', 'emailtest1754003372445@example.com', 'Email Test User', 'https://example.com', 'small', 'Testing email functionality with unique email', 'invited', 'invite_85fa6da29e3d431db10b20a1b80bb9e9', '"2025-07-31T18:19:24.843Z"'::jsonb, '"2025-07-31T17:39:34.142Z"'::jsonb, '"2025-07-31T18:19:24.844Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds1wh660000jp044armmk9k', 'freshtest1754006025741@example.com', 'Fresh Test User', 'https://testsite.com', 'small', 'Testing beta invite functionality', 'invited', 'invite_c9fcce900fdb4408bede679e2068b8a4', '"2025-07-31T18:23:59.513Z"'::jsonb, '"2025-07-31T18:23:48.271Z"'::jsonb, '"2025-07-31T18:23:59.514Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds1wv6q0005jp042z7nufut', '160923753044@lords.ac.in', 'Imad''s Team', 'https://gemini.google.com/', 'medium', 'Monthly visitors: 244444. Reason: bgvghhgh', 'invited', 'invite_e071db8437ef464bad33175fb64fbb5f', '"2025-07-31T18:24:16.435Z"'::jsonb, '"2025-07-31T18:24:06.434Z"'::jsonb, '"2025-07-31T18:24:16.436Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds1zfb80000h2043roshft9', 'freshtest1754006163071@example.com', 'Fresh Test User 2', 'https://testsite.com', 'small', 'Testing beta invite functionality with better error handling', 'invited', 'invite_90c5344c1dad491995a73e5bfacc283c', '"2025-07-31T18:26:08.972Z"'::jsonb, '"2025-07-31T18:26:05.828Z"'::jsonb, '"2025-07-31T18:26:08.974Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds2th3b0000jx0457mfoy1l', 'newahmeddresses@gmail.com', 'CREATE TABLE', 'https://gemini.google.com/', 'medium', 'Monthly visitors: 3434344. Reason: sdfsdfsdfsdfsdfsdfsdfsdfsdfsdfsdfsdfsdfsdfsdfsdfsdfsdf', 'invited', 'invite_2181fd66b6a0497ea1509ff04fa8f663', '"2025-07-31T18:49:46.589Z"'::jsonb, '"2025-07-31T18:49:27.815Z"'::jsonb, '"2025-07-31T18:49:46.591Z"'::jsonb);
INSERT INTO waitlist_entries (id, email, name, website, companySize, useCase, status, inviteToken, invitedAt, createdAt, updatedAt) VALUES ('cmds3cmcl0000i9045i4e5ed4', 'c90198593@gmail.com', 'sidrisi64', 'https://dodgerblue-clam-568728.hostingersite.com/', 'medium', 'Monthly visitors: 100000. Reason: hi hello', 'invited', 'invite_5cf74dcbd9e14ee4b09c93745d6136c1', '"2025-07-31T19:04:51.217Z"'::jsonb, '"2025-07-31T19:04:21.094Z"'::jsonb, '"2025-07-31T19:04:51.218Z"'::jsonb);

