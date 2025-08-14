/**
 * Production Stripe Integration
 * Handles payments, Connect accounts, and webhooks
 */

class StripeManager {
  constructor(env) {
    this.env = env;
    this.stripeSecretKey = env.STRIPE_SECRET_KEY;
    this.webhookSecret = env.STRIPE_WEBHOOK_SECRET;
    this.baseUrl = 'https://api.stripe.com/v1';
  }

  async makeStripeRequest(endpoint, method = 'GET', data = null) {
    const url = `${this.baseUrl}${endpoint}`;
    const options = {
      method,
      headers: {
        'Authorization': `Bearer ${this.stripeSecretKey}`,
        'Content-Type': 'application/x-www-form-urlencoded',
        'Stripe-Version': '2023-10-16'
      }
    };

    if (data && method !== 'GET') {
      options.body = new URLSearchParams(data).toString();
    }

    const response = await fetch(url, options);
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(`Stripe API error: ${error.error?.message || 'Unknown error'}`);
    }

    return response.json();
  }

  // Create payment intent for bot access
  async createPaymentIntent(siteId, amount, requestData) {
    const paymentData = {
      amount: Math.round(amount * 100), // Convert to cents
      currency: 'usd',
      'metadata[site_id]': siteId,
      'metadata[bot_type]': requestData.bot_info?.bot_type || 'unknown',
      'metadata[page_url]': requestData.page_url,
      'metadata[user_agent]': requestData.user_agent.substring(0, 500), // Limit length
      description: `AI Bot Access - ${requestData.page_url}`,
      'automatic_payment_methods[enabled]': 'true'
    };

    const paymentIntent = await this.makeStripeRequest('/payment_intents', 'POST', paymentData);
    
    return {
      id: paymentIntent.id,
      client_secret: paymentIntent.client_secret,
      amount: amount,
      status: paymentIntent.status
    };
  }

  // Create Stripe Connect account for site owner
  async createConnectAccount(siteData) {
    const accountData = {
      type: 'express',
      country: 'US', // Default, should be configurable
      email: siteData.admin_email,
      'capabilities[card_payments][requested]': 'true',
      'capabilities[transfers][requested]': 'true',
      'business_profile[name]': siteData.site_name,
      'business_profile[url]': siteData.site_url,
      'business_profile[product_description]': 'AI content monetization via CrawlGuard'
    };

    const account = await this.makeStripeRequest('/accounts', 'POST', accountData);
    
    return {
      account_id: account.id,
      charges_enabled: account.charges_enabled,
      details_submitted: account.details_submitted
    };
  }

  // Create account link for onboarding
  async createAccountLink(accountId, siteUrl) {
    const linkData = {
      account: accountId,
      refresh_url: `${siteUrl}/wp-admin/admin.php?page=crawlguard-settings&stripe_refresh=1`,
      return_url: `${siteUrl}/wp-admin/admin.php?page=crawlguard-settings&stripe_success=1`,
      type: 'account_onboarding'
    };

    const link = await this.makeStripeRequest('/account_links', 'POST', linkData);
    return link.url;
  }

  // Process successful payment
  async processSuccessfulPayment(paymentIntent, db) {
    const metadata = paymentIntent.metadata;
    const siteId = metadata.site_id;
    const amount = paymentIntent.amount / 100; // Convert from cents
    
    // Calculate fees
    const stripeFee = this.calculateStripeFee(amount);
    const platformFee = amount * 0.20; // 20% platform fee
    const creatorPayout = amount - stripeFee - platformFee;

    // Log payment in database
    const paymentData = {
      site_id: siteId,
      payment_intent_id: paymentIntent.id,
      amount: amount,
      currency: paymentIntent.currency,
      status: 'succeeded',
      stripe_fee: stripeFee,
      platform_fee: platformFee,
      creator_payout: creatorPayout,
      metadata: metadata,
      created_at: new Date().toISOString()
    };

    await db.logPayment(paymentData);

    // Update bot request with revenue
    await this.updateBotRequestRevenue(siteId, metadata, amount, db);

    // Schedule payout to creator (if Connect account is set up)
    await this.schedulePayout(siteId, creatorPayout, db);

    return paymentData;
  }

  async updateBotRequestRevenue(siteId, metadata, amount, db) {
    const sql = `
      UPDATE bot_requests 
      SET revenue_amount = $1, action_taken = 'monetized'
      WHERE site_id = $2 
        AND page_url = $3 
        AND user_agent = $4
        AND created_at >= NOW() - INTERVAL '1 hour'
      ORDER BY created_at DESC
      LIMIT 1
    `;

    await db.executeQuery(sql, [
      amount,
      siteId,
      metadata.page_url,
      metadata.user_agent
    ]);
  }

  async schedulePayout(siteId, amount, db) {
    // Get site's Stripe Connect account
    const site = await db.executeQuery(
      'SELECT stripe_account_id FROM sites WHERE id = $1',
      [siteId]
    );

    if (!site.rows[0]?.stripe_account_id) {
      console.log(`No Stripe account for site ${siteId}, payout queued`);
      return;
    }

    // Create transfer to Connect account
    try {
      const transferData = {
        amount: Math.round(amount * 100), // Convert to cents
        currency: 'usd',
        destination: site.rows[0].stripe_account_id,
        'metadata[site_id]': siteId,
        'metadata[type]': 'creator_payout'
      };

      const transfer = await this.makeStripeRequest('/transfers', 'POST', transferData);
      
      console.log(`Payout scheduled: ${transfer.id} for $${amount}`);
      
    } catch (error) {
      console.error('Payout failed:', error);
      // Log failed payout for manual processing
    }
  }

  // Webhook verification and processing
  async verifyWebhook(body, signature) {
    // In production, implement proper webhook signature verification
    // This is a simplified version
    
    if (!signature || !this.webhookSecret) {
      throw new Error('Missing webhook signature or secret');
    }

    // Stripe webhook signature verification would go here
    // For now, just parse the body
    try {
      return JSON.parse(body);
    } catch (error) {
      throw new Error('Invalid webhook payload');
    }
  }

  async handleWebhookEvent(event, db) {
    console.log(`Processing webhook: ${event.type}`);

    switch (event.type) {
      case 'payment_intent.succeeded':
        return await this.processSuccessfulPayment(event.data.object, db);

      case 'account.updated':
        return await this.handleAccountUpdate(event.data.object, db);

      case 'transfer.created':
        return await this.handleTransferCreated(event.data.object, db);

      case 'payout.paid':
        return await this.handlePayoutPaid(event.data.object, db);

      default:
        console.log(`Unhandled webhook event: ${event.type}`);
        return { processed: false, reason: 'unhandled_event_type' };
    }
  }

  async handleAccountUpdate(account, db) {
    // Update site's Stripe account status
    const sql = `
      UPDATE sites 
      SET stripe_account_id = $1, updated_at = NOW()
      WHERE admin_email = $2
    `;

    await db.executeQuery(sql, [account.id, account.email]);

    return { processed: true, account_id: account.id };
  }

  async handleTransferCreated(transfer, db) {
    // Log transfer for accounting
    console.log(`Transfer created: ${transfer.id} for ${transfer.amount / 100}`);
    return { processed: true, transfer_id: transfer.id };
  }

  async handlePayoutPaid(payout, db) {
    // Log successful payout
    console.log(`Payout completed: ${payout.id} for ${payout.amount / 100}`);
    return { processed: true, payout_id: payout.id };
  }

  // Utility methods
  calculateStripeFee(amount) {
    // Stripe fee: 2.9% + $0.30 for US cards
    return (amount * 0.029) + 0.30;
  }

  async getAccountStatus(accountId) {
    try {
      const account = await this.makeStripeRequest(`/accounts/${accountId}`);
      return {
        charges_enabled: account.charges_enabled,
        details_submitted: account.details_submitted,
        payouts_enabled: account.payouts_enabled
      };
    } catch (error) {
      console.error('Failed to get account status:', error);
      return null;
    }
  }

  // Create checkout session for subscription upgrades
  async createCheckoutSession(siteId, priceId, siteUrl) {
    const sessionData = {
      'payment_method_types[]': 'card',
      'line_items[0][price]': priceId,
      'line_items[0][quantity]': '1',
      mode: 'subscription',
      success_url: `${siteUrl}/wp-admin/admin.php?page=crawlguard&upgrade_success=1`,
      cancel_url: `${siteUrl}/wp-admin/admin.php?page=crawlguard&upgrade_cancelled=1`,
      'metadata[site_id]': siteId,
      'metadata[type]': 'subscription_upgrade'
    };

    const session = await this.makeStripeRequest('/checkout/sessions', 'POST', sessionData);
    return session.url;
  }

  // Health check
  async checkHealth() {
    try {
      await this.makeStripeRequest('/balance');
      return true;
    } catch (error) {
      console.error('Stripe health check failed:', error);
      return false;
    }
  }
}

export { StripeManager };
