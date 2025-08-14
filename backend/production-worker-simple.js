/**
 * CrawlGuard Production Cloudflare Worker - Simple Test Version
 * Basic API for testing without database dependencies
 */

import { Router } from 'itty-router';

const router = Router();

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Site-URL, X-API-Key',
  'Access-Control-Max-Age': '86400',
};

// Helper functions
function jsonResponse(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: {
      'Content-Type': 'application/json',
      ...corsHeaders
    }
  });
}

function errorResponse(errors, status = 400) {
  return jsonResponse({
    success: false,
    errors: Array.isArray(errors) ? errors : [errors]
  }, status);
}

// Handle CORS preflight
router.options('*', () => {
  return new Response(null, { status: 200, headers: corsHeaders });
});

// Root endpoint
router.get('/', () => {
  return jsonResponse({
    service: 'CrawlGuard API',
    version: '1.0.0',
    status: 'running',
    description: 'WordPress Plugin Monetization API',
    endpoints: {
      '/v1/status': 'API status',
      '/v1/health': 'Health check',
      '/v1/register': 'Site registration',
      '/v1/monetize': 'Bot monetization'
    }
  });
});

// API Status endpoint
router.get('/v1/status', (request, env) => {
  return jsonResponse({
    success: true,
    status: 'operational',
    version: '1.0.0',
    environment: env.ENVIRONMENT || 'production',
    timestamp: new Date().toISOString(),
    features: {
      bot_detection: true,
      monetization: true,
      analytics: true,
      payments: env.STRIPE_SECRET_KEY ? true : false
    }
  });
});

// Health check endpoint
router.get('/v1/health', async (request, env) => {
  try {
    // Test basic functionality
    const checks = {
      api: true,
      timestamp: new Date().toISOString(),
      database: env.DATABASE_URL ? 'configured' : 'not_configured',
      jwt: env.JWT_SECRET ? 'configured' : 'not_configured'
    };

    return jsonResponse({
      success: true,
      status: 'healthy',
      checks: checks
    });
  } catch (error) {
    return errorResponse(['Health check failed'], 500);
  }
});

// Site registration endpoint - simplified
router.post('/v1/register', async (request) => {
  try {
    const data = await request.json();
    
    // Basic validation
    if (!data.site_url || !data.admin_email) {
      return errorResponse(['Site URL and admin email are required'], 400);
    }
    
    // Generate API key
    const apiKey = 'cg_test_' + Math.random().toString(36).substr(2, 32);
    
    return jsonResponse({
      success: true,
      api_key: apiKey,
      site_id: 'test_' + Math.random().toString(36).substr(2, 8),
      message: 'Site registered successfully (test mode)',
      note: 'This is a test response. Database integration pending.'
    });
    
  } catch (error) {
    console.error('Registration error:', error);
    return errorResponse(['Registration failed'], 500);
  }
});

// Bot monetization endpoint - simplified
router.post('/v1/monetize', async (request) => {
  try {
    const data = await request.json();
    
    if (!data.api_key) {
      return errorResponse(['API key required'], 401);
    }
    
    const requestData = data.request_data || {};
    const userAgent = requestData.user_agent || '';
    
    // Simple bot detection
    const isBot = /bot|crawler|scraper|gpt|chatgpt|claude|bard|ai/i.test(userAgent);
    
    if (!isBot) {
      return jsonResponse({
        action: 'allow',
        reason: 'not_ai_bot',
        timestamp: new Date().toISOString()
      });
    }
    
    // Bot detected - test monetization response
    return jsonResponse({
      action: 'paywall',
      amount: 0.001,
      payment_url: 'https://test-payment-url.com',
      payment_id: 'test_payment_' + Math.random().toString(36).substr(2, 8),
      expires_at: Date.now() + (15 * 60 * 1000),
      bot_detected: {
        type: 'ai_bot',
        confidence: 85,
        user_agent: userAgent
      }
    });
    
  } catch (error) {
    console.error('Monetization error:', error);
    return errorResponse(['Processing failed'], 500);
  }
});

// Analytics endpoint - simplified
router.get('/v1/analytics', (request) => {
  const url = new URL(request.url);
  const apiKey = url.searchParams.get('api_key');
  
  if (!apiKey) {
    return errorResponse(['API key required'], 401);
  }
  
  return jsonResponse({
    success: true,
    analytics: {
      total_requests: 1250,
      bot_requests: 345,
      revenue: 0.847,
      top_bots: [
        { name: 'GPTBot', requests: 125, revenue: 0.250 },
        { name: 'ChatGPT-User', requests: 98, revenue: 0.196 },
        { name: 'Claude-Web', requests: 67, revenue: 0.134 }
      ],
      period: '30d',
      note: 'Test data - real analytics coming soon'
    }
  });
});

// Main handler
export default {
  async fetch(request, env, ctx) {
    try {
      return await router.handle(request, env, ctx);
    } catch (error) {
      console.error('Worker error:', error);
      return errorResponse(['Internal server error'], 500);
    }
  }
};
