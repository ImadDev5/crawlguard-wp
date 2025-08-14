/**
 * Production Database Connection Utilities
 * Handles PostgreSQL connections with connection pooling and error handling
 */

class DatabaseManager {
  constructor(env) {
    this.env = env;
    this.connectionPool = null;
  }

  async getConnection() {
    // In production, this would use a proper PostgreSQL client
    // For Cloudflare Workers, we'd use a service like Neon, PlanetScale, or Supabase
    
    if (!this.env.DATABASE_URL) {
      throw new Error('DATABASE_URL not configured');
    }

    // Mock connection for now - replace with actual PostgreSQL client
    return {
      query: async (sql, params = []) => {
        console.log('Executing query:', sql, params);
        return { rows: [], rowCount: 0 };
      },
      release: () => {
        // Release connection back to pool
      }
    };
  }

  async executeQuery(sql, params = []) {
    const connection = await this.getConnection();
    try {
      const result = await connection.query(sql, params);
      return result;
    } finally {
      connection.release();
    }
  }

  async executeTransaction(queries) {
    const connection = await this.getConnection();
    try {
      await connection.query('BEGIN');
      
      const results = [];
      for (const { sql, params } of queries) {
        const result = await connection.query(sql, params);
        results.push(result);
      }
      
      await connection.query('COMMIT');
      return results;
    } catch (error) {
      await connection.query('ROLLBACK');
      throw error;
    } finally {
      connection.release();
    }
  }

  // Site management
  async createSite(siteData) {
    const sql = `
      INSERT INTO sites (
        site_url, site_name, admin_email, api_key, plugin_version, 
        wordpress_version, subscription_tier, created_at
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
      RETURNING id
    `;
    
    const params = [
      siteData.site_url,
      siteData.site_name,
      siteData.admin_email,
      siteData.api_key,
      siteData.plugin_version,
      siteData.wordpress_version,
      siteData.subscription_tier || 'free',
      siteData.created_at
    ];
    
    const result = await this.executeQuery(sql, params);
    return result.rows[0]?.id;
  }

  async getSiteByApiKey(apiKey) {
    const sql = `
      SELECT s.*, 
        COUNT(br.id) FILTER (WHERE br.created_at > NOW() - INTERVAL '1 hour') as hourly_requests
      FROM sites s
      LEFT JOIN bot_requests br ON s.id = br.site_id
      WHERE s.api_key = $1 AND s.active = true
      GROUP BY s.id
    `;
    
    const result = await this.executeQuery(sql, [apiKey]);
    return result.rows[0];
  }

  async getSiteByUrl(siteUrl) {
    const sql = 'SELECT * FROM sites WHERE site_url = $1';
    const result = await this.executeQuery(sql, [siteUrl]);
    return result.rows[0];
  }

  // Bot request logging
  async logBotRequest(data) {
    const sql = `
      INSERT INTO bot_requests (
        site_id, ip_address, user_agent, bot_detected, bot_type, bot_name,
        confidence_score, page_url, content_type, content_length, action_taken,
        revenue_amount, processing_time, created_at
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14)
      RETURNING id
    `;
    
    const params = [
      data.site_id,
      data.ip_address,
      data.user_agent,
      data.bot_detected,
      data.bot_type,
      data.bot_name,
      data.confidence_score,
      data.page_url,
      data.content_type,
      data.content_length,
      data.action_taken,
      data.revenue_amount || 0,
      data.processing_time,
      data.created_at
    ];
    
    const result = await this.executeQuery(sql, params);
    return result.rows[0]?.id;
  }

  // Analytics queries
  async getAnalytics(siteId, dateRange = '30d') {
    const interval = this.parseInterval(dateRange);
    
    const queries = {
      overview: `
        SELECT 
          COUNT(*) as total_requests,
          COUNT(*) FILTER (WHERE bot_detected = true) as bot_requests,
          COUNT(*) FILTER (WHERE revenue_amount > 0) as monetized_requests,
          COALESCE(SUM(revenue_amount), 0) as total_revenue,
          COUNT(DISTINCT bot_name) FILTER (WHERE bot_detected = true) as unique_bots
        FROM bot_requests 
        WHERE site_id = $1 AND created_at >= NOW() - INTERVAL '${interval}'
      `,
      
      topBots: `
        SELECT 
          bot_name,
          COUNT(*) as visits,
          COALESCE(SUM(revenue_amount), 0) as revenue
        FROM bot_requests 
        WHERE site_id = $1 AND created_at >= NOW() - INTERVAL '${interval}' 
          AND bot_detected = true AND bot_name IS NOT NULL
        GROUP BY bot_name
        ORDER BY revenue DESC, visits DESC
        LIMIT 10
      `,
      
      recentActivity: `
        SELECT 
          created_at,
          bot_name,
          page_url,
          revenue_amount,
          action_taken
        FROM bot_requests 
        WHERE site_id = $1 
        ORDER BY created_at DESC 
        LIMIT 20
      `,
      
      dailyStats: `
        SELECT 
          DATE(created_at) as date,
          COUNT(*) as requests,
          COUNT(*) FILTER (WHERE bot_detected = true) as bot_requests,
          COALESCE(SUM(revenue_amount), 0) as revenue
        FROM bot_requests 
        WHERE site_id = $1 AND created_at >= NOW() - INTERVAL '${interval}'
        GROUP BY DATE(created_at)
        ORDER BY date DESC
      `
    };

    const results = {};
    for (const [key, sql] of Object.entries(queries)) {
      const result = await this.executeQuery(sql, [siteId]);
      results[key] = result.rows;
    }

    return this.formatAnalyticsResponse(results);
  }

  // Payment tracking
  async logPayment(paymentData) {
    const sql = `
      INSERT INTO payments (
        site_id, ai_company_id, payment_intent_id, amount, currency,
        status, stripe_fee, platform_fee, creator_payout, bot_request_id,
        metadata, created_at
      ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)
      RETURNING id
    `;
    
    const params = [
      paymentData.site_id,
      paymentData.ai_company_id,
      paymentData.payment_intent_id,
      paymentData.amount,
      paymentData.currency || 'USD',
      paymentData.status,
      paymentData.stripe_fee || 0,
      paymentData.platform_fee || 0,
      paymentData.creator_payout || 0,
      paymentData.bot_request_id,
      JSON.stringify(paymentData.metadata || {}),
      paymentData.created_at
    ];
    
    const result = await this.executeQuery(sql, params);
    return result.rows[0]?.id;
  }

  // AI Company management
  async getAICompany(companyName) {
    const sql = 'SELECT * FROM ai_companies WHERE company_name = $1';
    const result = await this.executeQuery(sql, [companyName]);
    return result.rows[0];
  }

  async checkAICompanySubscription(companyName) {
    const sql = `
      SELECT * FROM ai_companies 
      WHERE company_name = $1 AND subscription_active = true
    `;
    const result = await this.executeQuery(sql, [companyName]);
    return result.rows[0];
  }

  // Utility methods
  parseInterval(range) {
    const intervals = {
      '7d': '7 days',
      '30d': '30 days',
      '90d': '90 days',
      '1y': '1 year'
    };
    return intervals[range] || '30 days';
  }

  formatAnalyticsResponse(results) {
    const overview = results.overview[0] || {};
    
    return {
      total_revenue: parseFloat(overview.total_revenue || 0),
      bot_visits: parseInt(overview.bot_requests || 0),
      total_requests: parseInt(overview.total_requests || 0),
      monetized_requests: parseInt(overview.monetized_requests || 0),
      unique_bots: parseInt(overview.unique_bots || 0),
      monetization_rate: overview.total_requests > 0 
        ? (overview.monetized_requests / overview.total_requests) * 100 
        : 0,
      
      top_bots: results.topBots.map(bot => ({
        name: bot.bot_name,
        visits: parseInt(bot.visits),
        revenue: parseFloat(bot.revenue)
      })),
      
      recent_activity: results.recentActivity.map(activity => ({
        time: this.formatTime(activity.created_at),
        bot_name: activity.bot_name,
        page: activity.page_url,
        revenue: parseFloat(activity.revenue_amount || 0),
        action: activity.action_taken
      })),
      
      daily_stats: results.dailyStats.map(day => ({
        date: day.date,
        requests: parseInt(day.requests),
        bot_requests: parseInt(day.bot_requests),
        revenue: parseFloat(day.revenue)
      }))
    };
  }

  formatTime(timestamp) {
    return new Date(timestamp).toLocaleString();
  }

  // Health check
  async checkHealth() {
    try {
      const result = await this.executeQuery('SELECT 1 as health_check');
      return result.rows.length > 0;
    } catch (error) {
      console.error('Database health check failed:', error);
      return false;
    }
  }
}

export { DatabaseManager };
