/**
 * Arbiter Platform - Notification Service
 * Handles all platform notifications via multiple channels
 * Supports email, SMS, push notifications, webhooks, and in-app notifications
 */

import { EventEmitter } from 'events';

interface NotificationChannel {
  channelId: string;
  type: 'email' | 'sms' | 'push' | 'webhook' | 'in_app' | 'slack' | 'teams';
  name: string;
  config: {
    provider?: string;
    apiKey?: string;
    endpoint?: string;
    template?: string;
    settings?: Record<string, any>;
  };
  active: boolean;
  priority: number;
  rateLimits: {
    perMinute: number;
    perHour: number;
    perDay: number;
  };
}

interface NotificationTemplate {
  templateId: string;
  name: string;
  description: string;
  type: 'transactional' | 'marketing' | 'system' | 'alert';
  channels: string[];
  content: {
    subject?: string;
    title?: string;
    body: string;
    html?: string;
    variables: string[];
  };
  scheduling: {
    immediate: boolean;
    delay?: number; // minutes
    timezone?: string;
  };
  personalization: {
    enabled: boolean;
    rules: Array<{
      condition: string;
      content: Partial<NotificationTemplate['content']>;
    }>;
  };
  active: boolean;
  createdAt: Date;
  updatedAt: Date;
}

interface NotificationRequest {
  requestId: string;
  templateId: string;
  recipients: NotificationRecipient[];
  channels: string[];
  priority: 'low' | 'normal' | 'high' | 'urgent';
  data: Record<string, any>;
  scheduling: {
    sendAt?: Date;
    timezone?: string;
    retryPolicy?: {
      maxRetries: number;
      backoff: 'linear' | 'exponential';
      delay: number;
    };
  };
  metadata: {
    source: string;
    category: string;
    tags: string[];
  };
  createdAt: Date;
  status: 'pending' | 'processing' | 'sent' | 'failed' | 'cancelled';
}

interface NotificationRecipient {
  recipientId: string;
  type: 'user' | 'group' | 'role';
  contact: {
    email?: string;
    phone?: string;
    deviceToken?: string;
    webhookUrl?: string;
    userId?: string;
  };
  preferences: {
    channels: string[];
    frequency: 'immediate' | 'hourly' | 'daily' | 'weekly';
    quietHours?: {
      start: string;
      end: string;
      timezone: string;
    };
  };
  metadata?: Record<string, any>;
}

interface NotificationDelivery {
  deliveryId: string;
  requestId: string;
  recipientId: string;
  channel: string;
  status: 'pending' | 'sent' | 'delivered' | 'failed' | 'bounced' | 'opened' | 'clicked';
  attempts: NotificationAttempt[];
  cost?: number;
  metadata: {
    externalId?: string;
    providerId?: string;
    errorCode?: string;
    errorMessage?: string;
  };
  timeline: {
    queued: Date;
    sent?: Date;
    delivered?: Date;
    opened?: Date;
    clicked?: Date;
    failed?: Date;
  };
}

interface NotificationAttempt {
  attemptId: string;
  timestamp: Date;
  channel: string;
  status: 'success' | 'failure';
  responseTime: number;
  errorCode?: string;
  errorMessage?: string;
}

interface NotificationCampaign {
  campaignId: string;
  name: string;
  description: string;
  type: 'one_time' | 'recurring' | 'triggered';
  status: 'draft' | 'scheduled' | 'running' | 'paused' | 'completed' | 'cancelled';
  targeting: {
    segments: string[];
    filters: Array<{
      field: string;
      operator: string;
      value: any;
    }>;
    audienceSize: number;
  };
  content: {
    templateId: string;
    variables: Record<string, any>;
    personalization: boolean;
  };
  schedule: {
    startDate: Date;
    endDate?: Date;
    frequency?: 'daily' | 'weekly' | 'monthly';
    timezone: string;
  };
  analytics: {
    sent: number;
    delivered: number;
    opened: number;
    clicked: number;
    bounced: number;
    unsubscribed: number;
  };
  createdAt: Date;
  updatedAt: Date;
}

interface NotificationPreferences {
  userId: string;
  channels: {
    email: {
      enabled: boolean;
      categories: string[];
      frequency: 'immediate' | 'digest';
    };
    sms: {
      enabled: boolean;
      categories: string[];
      urgentOnly: boolean;
    };
    push: {
      enabled: boolean;
      categories: string[];
      deviceTokens: string[];
    };
    inApp: {
      enabled: boolean;
      categories: string[];
      soundEnabled: boolean;
    };
  };
  quietHours: {
    enabled: boolean;
    start: string;
    end: string;
    timezone: string;
    urgentOverride: boolean;
  };
  globalOptOut: boolean;
  updatedAt: Date;
}

class NotificationService extends EventEmitter {
  private channels: Map<string, NotificationChannel>;
  private templates: Map<string, NotificationTemplate>;
  private requests: Map<string, NotificationRequest>;
  private deliveries: Map<string, NotificationDelivery>;
  private campaigns: Map<string, NotificationCampaign>;
  private userPreferences: Map<string, NotificationPreferences>;
  private queue: NotificationRequest[];
  private processing: boolean;

  constructor() {
    super();
    this.channels = new Map();
    this.templates = new Map();
    this.requests = new Map();
    this.deliveries = new Map();
    this.campaigns = new Map();
    this.userPreferences = new Map();
    this.queue = [];
    this.processing = false;
    
    this.initializeChannels();
    this.initializeTemplates();
    this.startProcessing();
  }

  async sendNotification(request: Omit<NotificationRequest, 'requestId' | 'createdAt' | 'status'>): Promise<NotificationRequest> {
    const requestId = this.generateRequestId();
    
    const notificationRequest: NotificationRequest = {
      requestId,
      createdAt: new Date(),
      status: 'pending',
      ...request
    };

    // Validate request
    await this.validateRequest(notificationRequest);
    
    // Apply user preferences
    const filteredRequest = await this.applyUserPreferences(notificationRequest);
    
    this.requests.set(requestId, filteredRequest);
    this.queue.push(filteredRequest);
    
    this.emit('notificationQueued', { request: filteredRequest });
    
    return filteredRequest;
  }

  async sendBulkNotification(templateId: string, recipients: NotificationRecipient[], data: Record<string, any>): Promise<{
    batchId: string;
    requests: NotificationRequest[];
    summary: {
      total: number;
      queued: number;
      filtered: number;
    };
  }> {
    const batchId = this.generateBatchId();
    const requests: NotificationRequest[] = [];
    let filteredCount = 0;

    for (const recipient of recipients) {
      try {
        const request = await this.sendNotification({
          templateId,
          recipients: [recipient],
          channels: recipient.preferences.channels,
          priority: 'normal',
          data: { ...data, recipientId: recipient.recipientId },
          scheduling: {},
          metadata: {
            source: 'bulk',
            category: 'marketing',
            tags: [batchId]
          }
        });
        
        requests.push(request);
      } catch (error) {
        filteredCount++;
        console.warn(`Recipient ${recipient.recipientId} filtered:`, error.message);
      }
    }

    return {
      batchId,
      requests,
      summary: {
        total: recipients.length,
        queued: requests.length,
        filtered: filteredCount
      }
    };
  }

  async createTemplate(template: Omit<NotificationTemplate, 'templateId' | 'createdAt' | 'updatedAt'>): Promise<NotificationTemplate> {
    const templateId = this.generateTemplateId();
    
    const notificationTemplate: NotificationTemplate = {
      templateId,
      createdAt: new Date(),
      updatedAt: new Date(),
      ...template
    };

    this.validateTemplate(notificationTemplate);
    this.templates.set(templateId, notificationTemplate);
    
    this.emit('templateCreated', { template: notificationTemplate });
    
    return notificationTemplate;
  }

  async updateUserPreferences(userId: string, preferences: Partial<NotificationPreferences>): Promise<NotificationPreferences> {
    const existing = this.userPreferences.get(userId) || this.getDefaultPreferences(userId);
    
    const updated: NotificationPreferences = {
      ...existing,
      ...preferences,
      userId,
      updatedAt: new Date()
    };

    this.userPreferences.set(userId, updated);
    this.emit('preferencesUpdated', { userId, preferences: updated });
    
    return updated;
  }

  async getUserPreferences(userId: string): Promise<NotificationPreferences> {
    return this.userPreferences.get(userId) || this.getDefaultPreferences(userId);
  }

  async createCampaign(campaign: Omit<NotificationCampaign, 'campaignId' | 'createdAt' | 'updatedAt' | 'analytics'>): Promise<NotificationCampaign> {
    const campaignId = this.generateCampaignId();
    
    const notificationCampaign: NotificationCampaign = {
      campaignId,
      analytics: {
        sent: 0,
        delivered: 0,
        opened: 0,
        clicked: 0,
        bounced: 0,
        unsubscribed: 0
      },
      createdAt: new Date(),
      updatedAt: new Date(),
      ...campaign
    };

    this.campaigns.set(campaignId, notificationCampaign);
    
    if (campaign.schedule.startDate <= new Date()) {
      await this.executeCampaign(campaignId);
    }
    
    this.emit('campaignCreated', { campaign: notificationCampaign });
    
    return notificationCampaign;
  }

  async getDeliveryStatus(requestId: string): Promise<{
    request: NotificationRequest;
    deliveries: NotificationDelivery[];
    summary: {
      total: number;
      sent: number;
      delivered: number;
      failed: number;
      pending: number;
    };
  }> {
    const request = this.requests.get(requestId);
    if (!request) {
      throw new Error(`Notification request ${requestId} not found`);
    }

    const deliveries = Array.from(this.deliveries.values())
      .filter(d => d.requestId === requestId);

    const summary = {
      total: deliveries.length,
      sent: deliveries.filter(d => ['sent', 'delivered', 'opened', 'clicked'].includes(d.status)).length,
      delivered: deliveries.filter(d => ['delivered', 'opened', 'clicked'].includes(d.status)).length,
      failed: deliveries.filter(d => ['failed', 'bounced'].includes(d.status)).length,
      pending: deliveries.filter(d => d.status === 'pending').length
    };

    return { request, deliveries, summary };
  }

  async getAnalytics(timeRange: { start: Date; end: Date }): Promise<{
    overview: {
      totalSent: number;
      deliveryRate: number;
      openRate: number;
      clickRate: number;
      bounceRate: number;
    };
    byChannel: Record<string, {
      sent: number;
      delivered: number;
      cost: number;
    }>;
    byTemplate: Record<string, {
      sent: number;
      openRate: number;
      clickRate: number;
    }>;
    trends: Array<{
      date: Date;
      sent: number;
      delivered: number;
      opened: number;
      clicked: number;
    }>;
  }> {
    const relevantDeliveries = Array.from(this.deliveries.values())
      .filter(d => d.timeline.queued >= timeRange.start && d.timeline.queued <= timeRange.end);

    const totalSent = relevantDeliveries.filter(d => d.status !== 'pending').length;
    const delivered = relevantDeliveries.filter(d => ['delivered', 'opened', 'clicked'].includes(d.status)).length;
    const opened = relevantDeliveries.filter(d => ['opened', 'clicked'].includes(d.status)).length;
    const clicked = relevantDeliveries.filter(d => d.status === 'clicked').length;
    const bounced = relevantDeliveries.filter(d => d.status === 'bounced').length;

    return {
      overview: {
        totalSent,
        deliveryRate: totalSent > 0 ? (delivered / totalSent) * 100 : 0,
        openRate: delivered > 0 ? (opened / delivered) * 100 : 0,
        clickRate: opened > 0 ? (clicked / opened) * 100 : 0,
        bounceRate: totalSent > 0 ? (bounced / totalSent) * 100 : 0
      },
      byChannel: this.getAnalyticsByChannel(relevantDeliveries),
      byTemplate: this.getAnalyticsByTemplate(relevantDeliveries),
      trends: this.getAnalyticsTrends(relevantDeliveries, timeRange)
    };
  }

  async testNotification(templateId: string, recipientId: string, data: Record<string, any>): Promise<NotificationDelivery[]> {
    const template = this.templates.get(templateId);
    if (!template) {
      throw new Error(`Template ${templateId} not found`);
    }

    const testRequest = await this.sendNotification({
      templateId,
      recipients: [{
        recipientId,
        type: 'user',
        contact: { email: 'test@example.com' },
        preferences: { channels: ['email'], frequency: 'immediate' }
      }],
      channels: ['email'],
      priority: 'normal',
      data,
      scheduling: {},
      metadata: {
        source: 'test',
        category: 'system',
        tags: ['test']
      }
    });

    // Wait for processing
    await this.processRequest(testRequest);
    
    return Array.from(this.deliveries.values())
      .filter(d => d.requestId === testRequest.requestId);
  }

  private async startProcessing(): Promise<void> {
    if (this.processing) return;
    
    this.processing = true;
    
    setInterval(async () => {
      while (this.queue.length > 0) {
        const request = this.queue.shift();
        if (request) {
          await this.processRequest(request);
        }
      }
    }, 1000); // Process every second
  }

  private async processRequest(request: NotificationRequest): Promise<void> {
    try {
      request.status = 'processing';
      this.requests.set(request.requestId, request);
      
      const template = this.templates.get(request.templateId);
      if (!template) {
        throw new Error(`Template ${request.templateId} not found`);
      }

      // Create deliveries for each recipient and channel
      const deliveries: NotificationDelivery[] = [];
      
      for (const recipient of request.recipients) {
        for (const channelId of request.channels) {
          if (recipient.preferences.channels.includes(channelId)) {
            const delivery = await this.createDelivery(request, recipient, channelId, template);
            deliveries.push(delivery);
          }
        }
      }

      // Send notifications
      await Promise.all(deliveries.map(delivery => this.sendDelivery(delivery)));
      
      request.status = 'sent';
      this.requests.set(request.requestId, request);
      
      this.emit('notificationProcessed', { request, deliveries });
      
    } catch (error) {
      request.status = 'failed';
      this.requests.set(request.requestId, request);
      
      this.emit('notificationFailed', { request, error: error.message });
    }
  }

  private async createDelivery(
    request: NotificationRequest,
    recipient: NotificationRecipient,
    channelId: string,
    template: NotificationTemplate
  ): Promise<NotificationDelivery> {
    const deliveryId = this.generateDeliveryId();
    
    const delivery: NotificationDelivery = {
      deliveryId,
      requestId: request.requestId,
      recipientId: recipient.recipientId,
      channel: channelId,
      status: 'pending',
      attempts: [],
      metadata: {},
      timeline: {
        queued: new Date()
      }
    };

    this.deliveries.set(deliveryId, delivery);
    return delivery;
  }

  private async sendDelivery(delivery: NotificationDelivery): Promise<void> {
    const channel = this.channels.get(delivery.channel);
    if (!channel || !channel.active) {
      delivery.status = 'failed';
      delivery.metadata.errorMessage = 'Channel not available';
      return;
    }

    const attemptId = this.generateAttemptId();
    const startTime = Date.now();

    try {
      // Simulate sending notification
      await this.simulateChannelSend(channel, delivery);
      
      const attempt: NotificationAttempt = {
        attemptId,
        timestamp: new Date(),
        channel: delivery.channel,
        status: 'success',
        responseTime: Date.now() - startTime
      };

      delivery.attempts.push(attempt);
      delivery.status = 'sent';
      delivery.timeline.sent = new Date();
      
      // Simulate delivery confirmation (async)
      setTimeout(() => {
        delivery.status = 'delivered';
        delivery.timeline.delivered = new Date();
        this.deliveries.set(delivery.deliveryId, delivery);
        
        this.emit('notificationDelivered', { delivery });
      }, Math.random() * 5000); // Random delay up to 5 seconds

    } catch (error) {
      const attempt: NotificationAttempt = {
        attemptId,
        timestamp: new Date(),
        channel: delivery.channel,
        status: 'failure',
        responseTime: Date.now() - startTime,
        errorCode: 'SEND_FAILED',
        errorMessage: error.message
      };

      delivery.attempts.push(attempt);
      delivery.status = 'failed';
      delivery.timeline.failed = new Date();
      delivery.metadata.errorMessage = error.message;
    }

    this.deliveries.set(delivery.deliveryId, delivery);
  }

  private async simulateChannelSend(channel: NotificationChannel, delivery: NotificationDelivery): Promise<void> {
    // Simulate network delay
    await new Promise(resolve => setTimeout(resolve, Math.random() * 1000));
    
    // Simulate 95% success rate
    if (Math.random() > 0.05) {
      delivery.metadata.externalId = `${channel.type}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    } else {
      throw new Error('Channel send failed');
    }
  }

  private async validateRequest(request: NotificationRequest): Promise<void> {
    const template = this.templates.get(request.templateId);
    if (!template || !template.active) {
      throw new Error(`Template ${request.templateId} not found or inactive`);
    }

    if (request.recipients.length === 0) {
      throw new Error('No recipients specified');
    }

    if (request.channels.length === 0) {
      throw new Error('No channels specified');
    }
  }

  private validateTemplate(template: NotificationTemplate): void {
    if (!template.name || !template.content.body) {
      throw new Error('Template must have name and body content');
    }

    if (template.channels.length === 0) {
      throw new Error('Template must specify at least one channel');
    }
  }

  private async applyUserPreferences(request: NotificationRequest): Promise<NotificationRequest> {
    const filteredRecipients: NotificationRecipient[] = [];
    
    for (const recipient of request.recipients) {
      const preferences = await this.getUserPreferences(recipient.recipientId);
      
      if (preferences.globalOptOut) {
        continue; // Skip this recipient
      }

      // Filter channels based on preferences
      const allowedChannels = request.channels.filter(channel => {
        switch (channel) {
          case 'email':
            return preferences.channels.email.enabled;
          case 'sms':
            return preferences.channels.sms.enabled;
          case 'push':
            return preferences.channels.push.enabled;
          case 'in_app':
            return preferences.channels.inApp.enabled;
          default:
            return true;
        }
      });

      if (allowedChannels.length > 0) {
        recipient.preferences.channels = allowedChannels;
        filteredRecipients.push(recipient);
      }
    }

    return {
      ...request,
      recipients: filteredRecipients
    };
  }

  private async executeCampaign(campaignId: string): Promise<void> {
    const campaign = this.campaigns.get(campaignId);
    if (!campaign) return;

    // This would fetch actual audience based on targeting criteria
    const recipients = this.getTargetAudience(campaign.targeting);
    
    const bulkResult = await this.sendBulkNotification(
      campaign.content.templateId,
      recipients,
      campaign.content.variables
    );

    campaign.analytics.sent += bulkResult.summary.queued;
    campaign.status = 'running';
    
    this.campaigns.set(campaignId, campaign);
  }

  private getTargetAudience(targeting: NotificationCampaign['targeting']): NotificationRecipient[] {
    // Simplified - would query actual user database
    return [];
  }

  private getDefaultPreferences(userId: string): NotificationPreferences {
    return {
      userId,
      channels: {
        email: {
          enabled: true,
          categories: ['all'],
          frequency: 'immediate'
        },
        sms: {
          enabled: false,
          categories: ['urgent'],
          urgentOnly: true
        },
        push: {
          enabled: true,
          categories: ['all'],
          deviceTokens: []
        },
        inApp: {
          enabled: true,
          categories: ['all'],
          soundEnabled: true
        }
      },
      quietHours: {
        enabled: false,
        start: '22:00',
        end: '08:00',
        timezone: 'UTC',
        urgentOverride: true
      },
      globalOptOut: false,
      updatedAt: new Date()
    };
  }

  private getAnalyticsByChannel(deliveries: NotificationDelivery[]): Record<string, any> {
    const channelStats: Record<string, any> = {};
    
    for (const delivery of deliveries) {
      if (!channelStats[delivery.channel]) {
        channelStats[delivery.channel] = { sent: 0, delivered: 0, cost: 0 };
      }
      
      if (delivery.status !== 'pending') {
        channelStats[delivery.channel].sent++;
      }
      
      if (['delivered', 'opened', 'clicked'].includes(delivery.status)) {
        channelStats[delivery.channel].delivered++;
      }
      
      channelStats[delivery.channel].cost += delivery.cost || 0;
    }
    
    return channelStats;
  }

  private getAnalyticsByTemplate(deliveries: NotificationDelivery[]): Record<string, any> {
    // Would group by template and calculate metrics
    return {};
  }

  private getAnalyticsTrends(deliveries: NotificationDelivery[], timeRange: any): any[] {
    // Would create daily/hourly trend data
    return [];
  }

  private initializeChannels(): void {
    const channels = [
      {
        channelId: 'email',
        type: 'email' as const,
        name: 'Email',
        config: {
          provider: 'sendgrid',
          apiKey: 'demo_key'
        },
        active: true,
        priority: 1,
        rateLimits: { perMinute: 100, perHour: 1000, perDay: 10000 }
      },
      {
        channelId: 'sms',
        type: 'sms' as const,
        name: 'SMS',
        config: {
          provider: 'twilio',
          apiKey: 'demo_key'
        },
        active: true,
        priority: 2,
        rateLimits: { perMinute: 50, perHour: 500, perDay: 5000 }
      }
    ];

    channels.forEach(channel => this.channels.set(channel.channelId, channel));
  }

  private initializeTemplates(): void {
    const templates = [
      {
        templateId: 'welcome',
        name: 'Welcome Email',
        description: 'Welcome new users to the platform',
        type: 'transactional' as const,
        channels: ['email'],
        content: {
          subject: 'Welcome to Arbiter Platform',
          body: 'Welcome {{name}}! Thanks for joining our platform.',
          variables: ['name']
        },
        scheduling: { immediate: true },
        personalization: { enabled: false, rules: [] },
        active: true,
        createdAt: new Date(),
        updatedAt: new Date()
      }
    ];

    templates.forEach(template => this.templates.set(template.templateId, template));
  }

  private generateRequestId(): string {
    return `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateTemplateId(): string {
    return `tpl_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateCampaignId(): string {
    return `cmp_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateDeliveryId(): string {
    return `del_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateAttemptId(): string {
    return `att_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateBatchId(): string {
    return `bat_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
}

export default NotificationService;
export {
  NotificationTemplate,
  NotificationRequest,
  NotificationDelivery,
  NotificationCampaign,
  NotificationPreferences
};
