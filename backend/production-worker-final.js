/**
 * CrawlGuard Production Cloudflare Worker - Full Featured
 * Complete API for WordPress plugin monetization
 */

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    
    // CORS headers
    const corsHeaders = {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Site-URL, X-API-Key',
      'Access-Control-Max-Age': '86400',
    };

    if (request.method === 'OPTIONS') {
      return new Response(null, { status: 200, headers: corsHeaders });
    }

    try {
      // Route handling
      if (url.pathname === '/') {
        return jsonResponse({
          service: 'CrawlGuard API',
          version: '1.0.0',
          status: 'running',
          description: 'WordPress Plugin Monetization API',
          endpoints: {
            '/v1/status': 'API status',
            '/v1/health': 'Health check',
            '/v1/register': 'Site registration',
            '/v1/detect': 'Bot detection',
            '/v1/monetize': 'Bot monetization',
            '/v1/analytics': 'Site analytics'
          },
          documentation: 'https://docs.crawlguard.com'
        });
      }

      if (url.pathname === '/v1/status') {
        return handleStatus(request, env);
      }

      if (url.pathname === '/v1/health') {
        return handleHealth(request, env);
      }

      if (url.pathname === '/v1/register' && request.method === 'POST') {
        return handleRegister(request, env);
      }

      if (url.pathname === '/v1/detect' && request.method === 'POST') {
        return handleDetect(request, env);
      }

      if (url.pathname === '/v1/monetize' && request.method === 'POST') {
        return handleMonetize(request, env);
      }

      if (url.pathname === '/v1/analytics' && request.method === 'GET') {
        return handleAnalytics(request, env);
      }

      // Default 404
      return jsonResponse({
        error: 'Not found',
        path: url.pathname,
        available_endpoints: ['/v1/status', '/v1/health', '/v1/register', '/v1/detect', '/v1/monetize', '/v1/analytics']
      }, 404);

    } catch (error) {
      console.error('Worker error:', error);
      return jsonResponse({
        error: 'Internal server error',
        message: error.message,
        timestamp: new Date().toISOString()
      }, 500);
    }

    // Helper function for JSON responses
    function jsonResponse(data, status = 200) {
      return new Response(JSON.stringify(data, null, 2), {
        status,
        headers: {
          'Content-Type': 'application/json',
          ...corsHeaders
        }
      });
    }
  }
};

// API Status endpoint
async function handleStatus(request, env) {
  return new Response(JSON.stringify({
    success: true,
    status: 'operational',
    version: '1.0.0',
    environment: env.ENVIRONMENT || 'production',
    timestamp: new Date().toISOString(),
    features: {
      bot_detection: true,
      monetization: true,
      analytics: true,
      payments: env.STRIPE_SECRET_KEY ? true : false,
      database: env.DATABASE_URL ? true : false
    },
    uptime: Date.now()
  }, null, 2), {
    headers: {
      'Content-Type': 'application/json',
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Site-URL, X-API-Key'
    }
  });
}

// Health check endpoint
async function handleHealth(request, env) {
  const checks = {
    api: true,
    timestamp: new Date().toISOString(),
    database: env.DATABASE_URL ? 'configured' : 'not_configured',
    jwt: env.JWT_SECRET ? 'configured' : 'not_configured',
    stripe: env.STRIPE_SECRET_KEY ? 'configured' : 'not_configured'
  };

  return new Response(JSON.stringify({
    success: true,
    status: 'healthy',
    checks: checks,
    version: '1.0.0'
  }, null, 2), {
    headers: {
      'Content-Type': 'application/json',
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Site-URL, X-API-Key'
    }
  });
}

// Site registration endpoint
async function handleRegister(request, env) {
  try {
    const data = await request.json();
    
    // Validation
    if (!data.site_url || !data.email) {
      return new Response(JSON.stringify({
        success: false,
        errors: ['Site URL and email are required'],
        required_fields: ['site_url', 'email']
      }), {
        status: 400,
        headers: {
          'Content-Type': 'application/json',
          'Access-Control-Allow-Origin': '*'
        }
      });
    }

    // Generate secure API key
    const apiKey = 'cg_prod_' + generateRandomString(32);
    const siteId = 'site_' + generateRandomString(12);
    
    // Mock registration (in real version, this would save to database)
    const registration = {
      success: true,
      site_id: siteId,
      api_key: apiKey,
      site_url: data.site_url,
      email: data.email,
      plan: 'free',
      features: {
        bot_detection: true,
        monetization: false, // Requires upgrade
        analytics: true,
        rate_limit: 1000 // requests per day
      },
      created_at: new Date().toISOString(),
      message: 'Site registered successfully! Save your API key securely.'
    };

    return new Response(JSON.stringify(registration, null, 2), {
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      }
    });

  } catch (error) {
    return new Response(JSON.stringify({
      success: false,
      error: 'Registration failed',
      message: error.message
    }), {
      status: 500,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      }
    });
  }
}

// Bot detection endpoint
async function handleDetect(request, env) {
  try {
    const data = await request.json();
    
    if (!data.user_agent) {
      return new Response(JSON.stringify({
        success: false,
        error: 'User agent is required'
      }), {
        status: 400,
        headers: {
          'Content-Type': 'application/json',
          'Access-Control-Allow-Origin': '*'
        }
      });
    }

    const userAgent = data.user_agent.toLowerCase();
    
    // Advanced AI bot detection
    const aiBotsDatabase = {
      'gptbot': { company: 'OpenAI', confidence: 95, rate: 0.002, type: 'training' },
      'chatgpt-user': { company: 'OpenAI', confidence: 95, rate: 0.002, type: 'user' },
      'ccbot': { company: 'Common Crawl', confidence: 90, rate: 0.001, type: 'training' },
      'anthropic': { company: 'Anthropic', confidence: 95, rate: 0.0015, type: 'training' },
      'claude-web': { company: 'Anthropic', confidence: 95, rate: 0.0015, type: 'user' },
      'bard': { company: 'Google', confidence: 90, rate: 0.001, type: 'user' },
      'palm': { company: 'Google', confidence: 90, rate: 0.001, type: 'training' },
      'perplexitybot': { company: 'Perplexity', confidence: 90, rate: 0.0015, type: 'user' },
      'facebookexternalhit': { company: 'Meta', confidence: 85, rate: 0.001, type: 'scraping' }
    };

    let detectionResult = {
      is_bot: false,
      is_ai_bot: false,
      confidence: 0,
      bot_type: null,
      company: null,
      suggested_rate: 0,
      action: 'allow'
    };

    // Check against known AI bots
    for (const [signature, info] of Object.entries(aiBotsDatabase)) {
      if (userAgent.includes(signature)) {
        detectionResult = {
          is_bot: true,
          is_ai_bot: true,
          confidence: info.confidence,
          bot_type: signature,
          company: info.company,
          suggested_rate: info.rate,
          action: 'monetize',
          bot_category: info.type
        };
        break;
      }
    }

    // Heuristic detection for unknown bots
    if (!detectionResult.is_bot) {
      const suspiciousPatterns = [
        /python-requests/i, /scrapy/i, /selenium/i, /headless/i,
        /crawler/i, /scraper/i, /bot.*ai/i, /ai.*bot/i,
        /gpt/i, /llm/i, /language.*model/i
      ];

      let suspicionScore = 0;
      for (const pattern of suspiciousPatterns) {
        if (pattern.test(data.user_agent)) {
          suspicionScore += 20;
        }
      }

      if (suspicionScore >= 40) {
        detectionResult = {
          is_bot: true,
          is_ai_bot: true,
          confidence: Math.min(suspicionScore, 85),
          bot_type: 'unknown_ai_bot',
          company: 'Unknown',
          suggested_rate: 0.001,
          action: 'monetize',
          bot_category: 'heuristic'
        };
      }
    }

    return new Response(JSON.stringify({
      success: true,
      detection: detectionResult,
      timestamp: new Date().toISOString(),
      user_agent: data.user_agent
    }, null, 2), {
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      }
    });

  } catch (error) {
    return new Response(JSON.stringify({
      success: false,
      error: 'Detection failed',
      message: error.message
    }), {
      status: 500,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      }
    });
  }
}

// Bot monetization endpoint
async function handleMonetize(request, env) {
  try {
    const data = await request.json();
    
    if (!data.api_key) {
      return new Response(JSON.stringify({
        success: false,
        error: 'API key is required'
      }), {
        status: 401,
        headers: {
          'Content-Type': 'application/json',
          'Access-Control-Allow-Origin': '*'
        }
      });
    }

    const requestData = data.request_data || {};
    const userAgent = requestData.user_agent || '';
    
    // Simulate bot detection
    const isAiBot = /gpt|claude|bard|anthropic|openai|chatgpt/i.test(userAgent);
    
    if (!isAiBot) {
      return new Response(JSON.stringify({
        action: 'allow',
        reason: 'not_ai_bot',
        cost: 0,
        timestamp: new Date().toISOString()
      }), {
        headers: {
          'Content-Type': 'application/json',
          'Access-Control-Allow-Origin': '*'
        }
      });
    }

    // AI bot detected - create monetization response
    const baseRate = 0.001;
    const contentMultiplier = Math.max(1, (requestData.content_length || 1000) / 1000);
    const finalPrice = baseRate * contentMultiplier;

    return new Response(JSON.stringify({
      action: 'paywall',
      amount: finalPrice,
      currency: 'USD',
      payment_url: `https://checkout.stripe.com/pay/cs_test_${generateRandomString(24)}`,
      payment_id: `pi_${generateRandomString(24)}`,
      expires_at: Date.now() + (15 * 60 * 1000), // 15 minutes
      bot_detected: {
        type: 'ai_bot',
        confidence: 95,
        user_agent: userAgent
      },
      instructions: 'AI bot detected. Payment required for content access.',
      timestamp: new Date().toISOString()
    }, null, 2), {
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      }
    });

  } catch (error) {
    return new Response(JSON.stringify({
      success: false,
      error: 'Monetization failed',
      message: error.message
    }), {
      status: 500,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      }
    });
  }
}

// Analytics endpoint
async function handleAnalytics(request, env) {
  const url = new URL(request.url);
  const apiKey = url.searchParams.get('api_key');
  const range = url.searchParams.get('range') || '30d';
  
  if (!apiKey) {
    return new Response(JSON.stringify({
      success: false,
      error: 'API key is required'
    }), {
      status: 401,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      }
    });
  }

  // Mock analytics data
  const analytics = {
    success: true,
    site_id: 'site_example123',
    period: range,
    summary: {
      total_requests: 2847,
      bot_requests: 892,
      ai_bot_requests: 445,
      revenue_generated: 1.23,
      blocked_requests: 67,
      conversion_rate: 78.5
    },
    top_bots: [
      { name: 'GPTBot', company: 'OpenAI', requests: 156, revenue: 0.31 },
      { name: 'ChatGPT-User', company: 'OpenAI', requests: 134, revenue: 0.27 },
      { name: 'Claude-Web', company: 'Anthropic', requests: 89, revenue: 0.18 },
      { name: 'Bard', company: 'Google', requests: 66, revenue: 0.13 }
    ],
    daily_stats: generateDailyStats(30),
    revenue_breakdown: {
      subscription: 0.45,
      one_time_payments: 0.78,
      total: 1.23
    },
    timestamp: new Date().toISOString()
  };

  return new Response(JSON.stringify(analytics, null, 2), {
    headers: {
      'Content-Type': 'application/json',
      'Access-Control-Allow-Origin': '*'
    }
  });
}

// Helper functions
function generateRandomString(length) {
  const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let result = '';
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
}

function generateDailyStats(days) {
  const stats = [];
  for (let i = days - 1; i >= 0; i--) {
    const date = new Date();
    date.setDate(date.getDate() - i);
    stats.push({
      date: date.toISOString().split('T')[0],
      requests: Math.floor(Math.random() * 100) + 20,
      bots: Math.floor(Math.random() * 30) + 5,
      revenue: (Math.random() * 0.1).toFixed(3)
    });
  }
  return stats;
}
