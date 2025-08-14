/**
 * CrawlGuard Cloudflare Worker
 * Handles API requests from WordPress plugins
 */

// Environment variables (set in Cloudflare dashboard)
// - DATABASE_URL: PostgreSQL connection string
// - STRIPE_SECRET_KEY: Stripe API key
// - JWT_SECRET: For API authentication

import { Router } from 'itty-router';

const router = Router();

// CORS headers
const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Site-URL',
};

// Handle CORS preflight
router.options('*', () => new Response(null, { headers: corsHeaders }));

// Health check endpoint
router.get('/v1/status', () => {
  return new Response(JSON.stringify({ status: 'ok', timestamp: Date.now() }), {
    headers: { ...corsHeaders, 'Content-Type': 'application/json' }
  });
});

// Site registration
router.post('/v1/sites/register', async (request) => {
  try {
    const data = await request.json();
    
    // Validate required fields
    if (!data.site_url || !data.admin_email) {
      return new Response(JSON.stringify({ error: 'Missing required fields' }), {
        status: 400,
        headers: { ...corsHeaders, 'Content-Type': 'application/json' }
      });
    }
    
    // Generate API key
    const apiKey = await generateApiKey();
    
    // Store site in database
    const siteId = await storeSite({
      site_url: data.site_url,
      site_name: data.site_name,
      admin_email: data.admin_email,
      api_key: apiKey,
      plugin_version: data.plugin_version,
      wordpress_version: data.wordpress_version,
      created_at: new Date().toISOString()
    });
    
    return new Response(JSON.stringify({
      success: true,
      api_key: apiKey,
      site_id: siteId
    }), {
      headers: { ...corsHeaders, 'Content-Type': 'application/json' }
    });
    
  } catch (error) {
    console.error('Registration error:', error);
    return new Response(JSON.stringify({ error: 'Registration failed' }), {
      status: 500,
      headers: { ...corsHeaders, 'Content-Type': 'application/json' }
    });
  }
});

// Monetization endpoint - core business logic
router.post('/v1/monetize', async (request) => {
  try {
    const data = await request.json();
    
    // Validate API key
    const site = await validateApiKey(data.api_key);
    if (!site) {
      return new Response(JSON.stringify({ error: 'Invalid API key' }), {
        status: 401,
        headers: { ...corsHeaders, 'Content-Type': 'application/json' }
      });
    }
    
    const requestData = data.request_data;
    
    // Log the bot request
    await logBotRequest({
      site_id: site.id,
      ip_address: requestData.ip_address,
      user_agent: requestData.user_agent,
      bot_info: requestData.bot_info,
      page_url: requestData.page_url,
      timestamp: requestData.timestamp
    });
    
    // Determine monetization action
    const action = await determineAction(site, requestData);
    
    return new Response(JSON.stringify(action), {
      headers: { ...corsHeaders, 'Content-Type': 'application/json' }
    });
    
  } catch (error) {
    console.error('Monetization error:', error);
    return new Response(JSON.stringify({ error: 'Processing failed' }), {
      status: 500,
      headers: { ...corsHeaders, 'Content-Type': 'application/json' }
    });
  }
});

// Analytics endpoint
router.get('/v1/analytics', async (request) => {
  try {
    const url = new URL(request.url);
    const apiKey = url.searchParams.get('api_key');
    const range = url.searchParams.get('range') || '30d';
    
    const site = await validateApiKey(apiKey);
    if (!site) {
      return new Response(JSON.stringify({ error: 'Invalid API key' }), {
        status: 401,
        headers: { ...corsHeaders, 'Content-Type': 'application/json' }
      });
    }
    
    const analytics = await getAnalytics(site.id, range);
    
    return new Response(JSON.stringify(analytics), {
      headers: { ...corsHeaders, 'Content-Type': 'application/json' }
    });
    
  } catch (error) {
    console.error('Analytics error:', error);
    return new Response(JSON.stringify({ error: 'Analytics failed' }), {
      status: 500,
      headers: { ...corsHeaders, 'Content-Type': 'application/json' }
    });
  }
});

// Payment webhook from Stripe
router.post('/v1/webhooks/stripe', async (request) => {
  try {
    const signature = request.headers.get('stripe-signature');
    const body = await request.text();
    
    // Verify webhook signature
    const event = await verifyStripeWebhook(body, signature);
    
    if (event.type === 'payment_intent.succeeded') {
      await handleSuccessfulPayment(event.data.object);
    }
    
    return new Response(JSON.stringify({ received: true }), {
      headers: { ...corsHeaders, 'Content-Type': 'application/json' }
    });
    
  } catch (error) {
    console.error('Webhook error:', error);
    return new Response(JSON.stringify({ error: 'Webhook failed' }), {
      status: 400,
      headers: { ...corsHeaders, 'Content-Type': 'application/json' }
    });
  }
});

// Lightweight beacon endpoint
router.post('/v1/beacon', async (request) => {
  try {
    const data = await request.json();
    
    // Store beacon data asynchronously (fire and forget)
    request.waitUntil(storeBeaconData(data));
    
    return new Response('OK', {
      headers: corsHeaders
    });
    
  } catch (error) {
    // Don't fail beacons - just log and continue
    console.error('Beacon error:', error);
    return new Response('OK', {
      headers: corsHeaders
    });
  }
});

// Helper functions
async function generateApiKey() {
  const array = new Uint8Array(32);
  crypto.getRandomValues(array);
  return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
}

async function storeSite(siteData) {
  // Connect to PostgreSQL database
  const db = await connectToDatabase();
  
  const query = `
    INSERT INTO sites (site_url, site_name, admin_email, api_key, plugin_version, wordpress_version, created_at)
    VALUES ($1, $2, $3, $4, $5, $6, $7)
    RETURNING id
  `;
  
  const result = await db.query(query, [
    siteData.site_url,
    siteData.site_name,
    siteData.admin_email,
    siteData.api_key,
    siteData.plugin_version,
    siteData.wordpress_version,
    siteData.created_at
  ]);
  
  return result.rows[0].id;
}

async function validateApiKey(apiKey) {
  if (!apiKey) return null;
  
  const db = await connectToDatabase();
  
  const query = 'SELECT * FROM sites WHERE api_key = $1 AND active = true';
  const result = await db.query(query, [apiKey]);
  
  return result.rows[0] || null;
}

async function determineAction(site, requestData) {
  const botInfo = requestData.bot_info;
  
  // Check if site has monetization enabled
  if (!site.monetization_enabled) {
    return { action: 'allow', message: 'Monetization disabled' };
  }
  
  // Check if this is a known AI bot
  if (!botInfo.is_ai_bot) {
    return { action: 'allow', message: 'Not an AI bot' };
  }
  
  // Check if bot is in allowed list
  if (site.allowed_bots && site.allowed_bots.includes(botInfo.bot_type)) {
    return { action: 'allow', message: 'Bot in allowed list' };
  }
  
  // Calculate pricing
  const basePrice = site.pricing_per_request || 0.001;
  const contentMultiplier = Math.max(1, requestData.content_length || 1);
  const finalPrice = basePrice * contentMultiplier;
  
  // Check if AI company has active subscription
  const hasSubscription = await checkAICompanySubscription(botInfo.bot_name);
  
  if (hasSubscription) {
    // Allow access and log revenue
    await logRevenue(site.id, finalPrice, botInfo.bot_name);
    return {
      action: 'allow',
      revenue: finalPrice,
      message: 'Paid access via subscription'
    };
  }
  
  // Generate payment link for one-time access
  const paymentUrl = await createStripePaymentLink(site.id, finalPrice, requestData);
  
  return {
    action: 'paywall',
    amount: finalPrice,
    payment_url: paymentUrl,
    message: 'Payment required for access'
  };
}

async function getAnalytics(siteId, range) {
  const db = await connectToDatabase();
  
  // Calculate date range
  const endDate = new Date();
  const startDate = new Date();
  
  switch (range) {
    case '7d':
      startDate.setDate(endDate.getDate() - 7);
      break;
    case '30d':
      startDate.setDate(endDate.getDate() - 30);
      break;
    case '90d':
      startDate.setDate(endDate.getDate() - 90);
      break;
    default:
      startDate.setDate(endDate.getDate() - 30);
  }
  
  // Get revenue data
  const revenueQuery = `
    SELECT 
      COALESCE(SUM(revenue_amount), 0) as total_revenue,
      COUNT(*) as total_requests,
      COUNT(CASE WHEN revenue_amount > 0 THEN 1 END) as monetized_requests
    FROM bot_requests 
    WHERE site_id = $1 AND created_at >= $2
  `;
  
  const revenueResult = await db.query(revenueQuery, [siteId, startDate.toISOString()]);
  const stats = revenueResult.rows[0];
  
  // Get top bots
  const botsQuery = `
    SELECT 
      bot_name,
      COUNT(*) as visits,
      COALESCE(SUM(revenue_amount), 0) as revenue
    FROM bot_requests 
    WHERE site_id = $1 AND created_at >= $2 AND bot_detected = true
    GROUP BY bot_name
    ORDER BY revenue DESC, visits DESC
    LIMIT 10
  `;
  
  const botsResult = await db.query(botsQuery, [siteId, startDate.toISOString()]);
  
  // Get recent activity
  const activityQuery = `
    SELECT 
      created_at,
      bot_name,
      page_url,
      revenue_amount
    FROM bot_requests 
    WHERE site_id = $1 
    ORDER BY created_at DESC 
    LIMIT 20
  `;
  
  const activityResult = await db.query(activityQuery, [siteId]);
  
  return {
    total_revenue: parseFloat(stats.total_revenue),
    bot_visits: parseInt(stats.total_requests),
    monetized_requests: parseInt(stats.monetized_requests),
    monetization_rate: stats.total_requests > 0 ? (stats.monetized_requests / stats.total_requests) * 100 : 0,
    lost_revenue: calculateLostRevenue(stats),
    top_bots: botsResult.rows.map(bot => ({
      name: bot.bot_name,
      visits: parseInt(bot.visits),
      revenue: parseFloat(bot.revenue)
    })),
    recent_activity: activityResult.rows.map(activity => ({
      time: formatTime(activity.created_at),
      bot_name: activity.bot_name,
      page: activity.page_url,
      revenue: parseFloat(activity.revenue_amount || 0)
    }))
  };
}

async function connectToDatabase() {
  // This would connect to your PostgreSQL database
  // Implementation depends on your database setup
  // For now, return a mock connection
  return {
    query: async (sql, params) => ({ rows: [] })
  };
}

// Main handler
export default {
  async fetch(request, env, ctx) {
    return router.handle(request, env, ctx);
  }
};
