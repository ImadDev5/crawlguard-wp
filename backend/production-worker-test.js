/**
 * CrawlGuard Test Worker - Ultra Simple Version
 */

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    
    // Handle CORS
    const corsHeaders = {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type',
    };

    if (request.method === 'OPTIONS') {
      return new Response(null, { status: 200, headers: corsHeaders });
    }

    try {
      // Root endpoint
      if (url.pathname === '/') {
        return new Response(JSON.stringify({
          service: 'CrawlGuard API',
          status: 'running',
          version: '1.0.0',
          timestamp: new Date().toISOString()
        }), {
          headers: { 'Content-Type': 'application/json', ...corsHeaders }
        });
      }

      // Status endpoint
      if (url.pathname === '/v1/status') {
        return new Response(JSON.stringify({
          success: true,
          status: 'operational',
          environment: env.ENVIRONMENT || 'production',
          timestamp: new Date().toISOString()
        }), {
          headers: { 'Content-Type': 'application/json', ...corsHeaders }
        });
      }

      // Health endpoint
      if (url.pathname === '/v1/health') {
        return new Response(JSON.stringify({
          success: true,
          status: 'healthy',
          database: env.DATABASE_URL ? 'configured' : 'not_configured',
          jwt: env.JWT_SECRET ? 'configured' : 'not_configured',
          timestamp: new Date().toISOString()
        }), {
          headers: { 'Content-Type': 'application/json', ...corsHeaders }
        });
      }

      // Default 404
      return new Response(JSON.stringify({
        error: 'Not found',
        path: url.pathname
      }), {
        status: 404,
        headers: { 'Content-Type': 'application/json', ...corsHeaders }
      });

    } catch (error) {
      return new Response(JSON.stringify({
        error: 'Internal server error',
        message: error.message
      }), {
        status: 500,
        headers: { 'Content-Type': 'application/json', ...corsHeaders }
      });
    }
  }
};
