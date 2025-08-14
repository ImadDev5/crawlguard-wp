import { 
  PricingRule, 
  CrawlerRequest, 
  RuleEvaluationResult, 
  RuleCondition, 
  RuleAction,
  ConditionType,
  ConditionOperator,
  ActionType,
  ExecutableAction,
  PricingDecision,
  RuleExecutionContext 
} from '../models/types';

export class RuleEvaluator {
  private redisUrl: string;
  private redis: any; // Will be initialized later
  private cache: Map<string, PricingRule[]> = new Map();
  private initialized: boolean = false;

  constructor(
    redisUrl: string = process.env.REDIS_URL || 'redis://localhost:6379'
  ) {
    this.redisUrl = redisUrl;
  }

  /**
   * Initialize Redis connection
   */
  async initialize(): Promise<void> {
    try {
      const { createClient } = await import('redis');
      this.redis = createClient({ url: this.redisUrl });
      await this.redis.connect();
      this.initialized = true;
      console.log('✅ Rules Engine initialized successfully');
    } catch (error) {
      console.error('❌ Failed to initialize Rules Engine:', error);
      // For development, continue without Redis
      console.warn('⚠️ Continuing without Redis connection');
      this.initialized = true;
    }
  }

  /**
   * Shutdown connections gracefully
   */
  async shutdown(): Promise<void> {
    try {
      if (this.redis && this.redis.quit) {
        await this.redis.quit();
      }
      this.initialized = false;
      console.log('✅ Rules Engine shut down successfully');
    } catch (error) {
      console.error('❌ Error during Rules Engine shutdown:', error);
      throw error;
    }
  }

  /**
   * Main evaluation method - the core of the Rules Engine
   * This is where the magic happens: matching requests to rules and determining actions
   */
  async evaluateRequest(
    request: CrawlerRequest, 
    publisherId: string
  ): Promise<RuleEvaluationResult> {
    const startTime = Date.now();
    
    try {
      // 1. Get active rules for publisher (with caching)
      const rules = await this.getActiveRules(publisherId);
      
      // 2. Match conditions against request
      const matchedRules = this.matchRules(request, rules);
      
      // 3. Sort by priority and execute actions
      const sortedRules = matchedRules.sort((a, b) => b.priority - a.priority);
      
      // 4. Determine actions to execute
      const actions = this.determineActions(sortedRules, request);
      
      // 5. Calculate pricing if applicable
      const pricing = this.calculatePricing(actions, request);
      
      const evaluationTime = Date.now() - startTime;
      
      const result: RuleEvaluationResult = {
        matched: matchedRules.length > 0,
        matchedRules,
        actions,
        pricing,
        evaluationTime,
        metadata: {
          totalRulesEvaluated: rules.length,
          cacheHit: this.cache.has(publisherId)
        }
      };
      
      // 6. Log the evaluation (async)
      this.logEvaluation(publisherId, request, result).catch(console.error);
      
      return result;
      
    } catch (error) {
      console.error('Rule evaluation error:', error);
      
      return {
        matched: false,
        matchedRules: [],
        actions: [],
        evaluationTime: Date.now() - startTime,
        metadata: { error: error instanceof Error ? error.message : 'Unknown error' }
      };
    }
  }

  /**
   * Get active rules for a publisher with Redis caching
   */
  private async getActiveRules(publisherId: string): Promise<PricingRule[]> {
    const cacheKey = `rules:${publisherId}`;
    
    // Try cache first
    if (this.cache.has(publisherId)) {
      return this.cache.get(publisherId)!;
    }
    
    // Try Redis (if available)
    let cached = null;
    if (this.redis && this.initialized) {
      try {
        cached = await this.redis.get(cacheKey);
      } catch (error) {
        console.warn('Redis get error:', error);
      }
    }
    
    if (cached) {
      const rules = JSON.parse(cached);
      this.cache.set(publisherId, rules);
      return rules;
    }
    
    // Fetch from database (using mock data for development)
    const rules = this.getMockRules(publisherId);
    
    // TODO: Replace with actual Prisma query
    // const rules = await this.prisma.pricingRule.findMany({
    //   where: {
    //     publisherId,
    //     isActive: true,
    //     OR: [
    //       { validFrom: null },
    //       { validFrom: { lte: new Date() } }
    //     ],
    //     AND: [
    //       { 
    //         OR: [
    //           { validUntil: null },
    //           { validUntil: { gte: new Date() } }
    //         ]
    //       }
    //     ]
    //   },
    //   include: {
    //     conditions: true,
    //     actions: true
    //   },
    //   orderBy: { priority: 'desc' }
    // });
    
    // Cache the results (only if Redis is available)
    if (this.redis && this.initialized) {
      try {
        await this.redis.setEx(cacheKey, 300, JSON.stringify(rules)); // 5 min TTL
      } catch (error) {
        console.warn('Redis cache error:', error);
      }
    }
    this.cache.set(publisherId, rules as any);
    
    return rules as any;
  }

  /**
   * Mock rules for development testing
   */
  private getMockRules(publisherId: string): PricingRule[] {
    return [
      {
        id: 'rule_1',
        publisherId,
        name: 'Block GPT Bots',
        description: 'Block all GPT-based bots from accessing content',
        conditions: [
          {
            id: 'cond_1',
            type: ConditionType.BOT_ID,
            operator: ConditionOperator.CONTAINS,
            value: 'GPT'
          }
        ],
        actions: [
          {
            id: 'act_1',
            type: ActionType.BLOCK_ACCESS,
            value: true
          }
        ],
        priority: 100,
        isActive: true,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        id: 'rule_2',
        publisherId,
        name: 'Premium AI Pricing',
        description: 'Charge premium rates for AI company bots',
        conditions: [
          {
            id: 'cond_2',
            type: ConditionType.BOT_ID,
            operator: ConditionOperator.IN,
            value: 'ChatGPT-User,Claude-Web,CCBot'
          }
        ],
        actions: [
          {
            id: 'act_2',
            type: ActionType.SET_PRICE,
            value: 0.01
          }
        ],
        priority: 90,
        isActive: true,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        id: 'rule_3',
        publisherId,
        name: 'Rate Limit High Volume',
        description: 'Limit requests for high-volume crawlers',
        conditions: [
          {
            id: 'cond_3',
            type: ConditionType.REQUEST_FREQUENCY,
            operator: ConditionOperator.GREATER_THAN,
            value: 100
          }
        ],
        actions: [
          {
            id: 'act_3',
            type: ActionType.RATE_LIMIT,
            value: 50
          }
        ],
        priority: 80,
        isActive: true,
        createdAt: new Date(),
        updatedAt: new Date()
      }
    ];
  }

  /**
   * Invalidate cache for a publisher (used when rules are updated)
   */
  async invalidateCache(publisherId: string): Promise<void> {
    try {
      // Clear local cache
      this.cache.delete(publisherId);
      
      // Clear Redis cache
      if (this.redis && this.initialized) {
        const cacheKey = `rules:${publisherId}`;
        await this.redis.del(cacheKey);
      }
      
      console.log(`🧹 Cache invalidated for publisher: ${publisherId}`);
    } catch (error) {
      console.error('Cache invalidation error:', error);
    }
  }

  /**
   * Match rules against incoming request
   * This is where we check if a rule's conditions are met
   */
  private matchRules(request: CrawlerRequest, rules: PricingRule[]): PricingRule[] {
    const matchedRules: PricingRule[] = [];
    
    for (const rule of rules) {
      if (this.evaluateRuleConditions(request, rule.conditions)) {
        matchedRules.push(rule);
      }
    }
    
    return matchedRules;
  }

  /**
   * Evaluate all conditions for a single rule
   * ALL conditions must be true for the rule to match (AND logic)
   */
  private evaluateRuleConditions(request: CrawlerRequest, conditions: RuleCondition[]): boolean {
    if (conditions.length === 0) return true; // No conditions = always match
    
    for (const condition of conditions) {
      if (!this.evaluateCondition(request, condition)) {
        return false; // One false condition fails the entire rule
      }
    }
    
    return true;
  }

  /**
   * Evaluate a single condition against the request
   * This is where we implement all the different condition types and operators
   */
  private evaluateCondition(request: CrawlerRequest, condition: RuleCondition): boolean {
    const requestValue = this.extractRequestValue(request, condition.type);
    const conditionValue = condition.value;
    
    switch (condition.operator) {
      case ConditionOperator.EQUALS:
        return requestValue === conditionValue;
        
      case ConditionOperator.NOT_EQUALS:
        return requestValue !== conditionValue;
        
      case ConditionOperator.CONTAINS:
        return String(requestValue).toLowerCase().includes(String(conditionValue).toLowerCase());
        
      case ConditionOperator.NOT_CONTAINS:
        return !String(requestValue).toLowerCase().includes(String(conditionValue).toLowerCase());
        
      case ConditionOperator.STARTS_WITH:
        return String(requestValue).toLowerCase().startsWith(String(conditionValue).toLowerCase());
        
      case ConditionOperator.ENDS_WITH:
        return String(requestValue).toLowerCase().endsWith(String(conditionValue).toLowerCase());
        
      case ConditionOperator.GREATER_THAN:
        return Number(requestValue) > Number(conditionValue);
        
      case ConditionOperator.LESS_THAN:
        return Number(requestValue) < Number(conditionValue);
        
      case ConditionOperator.GREATER_THAN_OR_EQUAL:
        return Number(requestValue) >= Number(conditionValue);
        
      case ConditionOperator.LESS_THAN_OR_EQUAL:
        return Number(requestValue) <= Number(conditionValue);
        
      case ConditionOperator.IN:
        const inArray = Array.isArray(conditionValue) ? conditionValue : String(conditionValue).split(',');
        return inArray.includes(String(requestValue));
        
      case ConditionOperator.NOT_IN:
        const notInArray = Array.isArray(conditionValue) ? conditionValue : String(conditionValue).split(',');
        return !notInArray.includes(String(requestValue));
        
      case ConditionOperator.REGEX:
        try {
          const regex = new RegExp(String(conditionValue), 'i');
          return regex.test(String(requestValue));
        } catch {
          return false;
        }
        
      case ConditionOperator.IS_EMPTY:
        return !requestValue || requestValue === '';
        
      case ConditionOperator.IS_NOT_EMPTY:
        return requestValue && requestValue !== '';
        
      default:
        console.warn(`Unknown condition operator: ${condition.operator}`);
        return false;
    }
  }

  /**
   * Extract the relevant value from the request based on condition type
   */
  private extractRequestValue(request: CrawlerRequest, conditionType: ConditionType): any {
    switch (conditionType) {
      case ConditionType.BOT_ID:
        return request.botId || this.extractBotIdFromUserAgent(request.userAgent);
        
      case ConditionType.USER_AGENT:
        return request.userAgent;
        
      case ConditionType.CONTENT_TYPE:
        return request.contentType || request.headers['content-type'];
        
      case ConditionType.IP_ADDRESS:
        return request.ipAddress;
        
      case ConditionType.REFERER:
        return request.referer || request.headers['referer'];
        
      case ConditionType.DOMAIN:
        return request.domain;
        
      case ConditionType.URL_PATTERN:
        return request.url;
        
      case ConditionType.TIME_OF_DAY:
        return new Date().getHours();
        
      case ConditionType.DAY_OF_WEEK:
        return new Date().getDay();
        
      case ConditionType.REQUEST_FREQUENCY:
        // This would need to be calculated from recent request history
        return 0; // Placeholder
        
      case ConditionType.REQUEST_COUNT:
        // This would need to be calculated from historical data
        return 0; // Placeholder
        
      case ConditionType.GEOGRAPHY:
        // This would need GeoIP lookup
        return 'unknown'; // Placeholder
        
      default:
        return undefined;
    }
  }

  /**
   * Extract bot ID from User-Agent string
   * Common patterns for AI bots
   */
  private extractBotIdFromUserAgent(userAgent: string): string | undefined {
    const botPatterns = [
      /GPTBot/i,
      /ChatGPT-User/i,
      /CCBot/i,
      /Claude-Web/i,
      /anthropic-ai/i,
      /PerplexityBot/i,
      /YouBot/i,
      /Meta-ExternalAgent/i,
      /FacebookBot/i,
      /Google-Extended/i,
      /Googlebot/i,
      /Bingbot/i,
      /facebookexternalhit/i
    ];
    
    for (const pattern of botPatterns) {
      const match = userAgent.match(pattern);
      if (match) {
        return match[0];
      }
    }
    
    return undefined;
  }

  /**
   * Determine which actions to execute based on matched rules
   * Handle conflicts between rules (highest priority wins)
   */
  private determineActions(matchedRules: PricingRule[], request: CrawlerRequest): ExecutableAction[] {
    const actions: ExecutableAction[] = [];
    const actionTypes = new Set<ActionType>();
    
    // Process rules in priority order (highest first)
    for (const rule of matchedRules) {
      for (const action of rule.actions) {
        // Only execute the first action of each type (highest priority rule)
        if (!actionTypes.has(action.type)) {
          actions.push({
            type: action.type,
            value: action.value,
            rule,
            metadata: action.metadata
          });
          actionTypes.add(action.type);
        }
      }
    }
    
    return actions;
  }

  /**
   * Calculate pricing based on executed actions
   */
  private calculatePricing(actions: ExecutableAction[], request: CrawlerRequest): PricingDecision | undefined {
    const priceAction = actions.find(a => a.type === ActionType.SET_PRICE);
    
    if (!priceAction) {
      return undefined;
    }
    
    const discountAction = actions.find(a => a.type === ActionType.APPLY_DISCOUNT);
    let price = Number(priceAction.value);
    const discounts: any[] = [];
    
    if (discountAction) {
      const discountValue = Number(discountAction.value);
      const discountType = discountAction.metadata?.type || 'percentage';
      
      if (discountType === 'percentage') {
        price = price * (1 - discountValue / 100);
      } else {
        price = Math.max(0, price - discountValue);
      }
      
      discounts.push({
        type: discountType,
        value: discountValue,
        reason: `Applied by rule: ${discountAction.rule.name}`,
        metadata: discountAction.metadata
      });
    }
    
    return {
      price: Math.max(0, price), // Never negative
      currency: 'USD', // TODO: Make configurable
      priceType: 'per_request', // TODO: Make configurable
      rule: priceAction.rule,
      discounts: discounts.length > 0 ? discounts : undefined,
      metadata: {
        originalPrice: Number(priceAction.value),
        discountsApplied: discounts.length
      }
    };
  }

  /**
   * Log the rule evaluation for analytics and debugging
   */
  private async logEvaluation(
    publisherId: string, 
    request: CrawlerRequest, 
    result: RuleEvaluationResult
  ): Promise<void> {
    try {
      // TODO: Store in database for analytics
      console.log('📊 Rule evaluation result:', {
        publisherId,
        requestId: request.id,
        domain: request.domain,
        matched: result.matched,
        matchedRules: result.matchedRules.length,
        evaluationTime: result.evaluationTime
      });
      
      // TODO: Replace with actual Prisma call
      // await this.prisma.ruleEvaluation.create({
      //   data: {
      //     publisherId,
      //     requestId: request.id,
      //     domain: request.domain,
      //     url: request.url,
      //     botId: request.botId,
      //     userAgent: request.userAgent,
      //     ipAddress: request.ipAddress,
      //     matched: result.matched,
      //     matchedRuleIds: result.matchedRules.map(r => r.id),
      //     actions: JSON.stringify(result.actions),
      //     pricing: result.pricing ? JSON.stringify(result.pricing) : null,
      //     evaluationTime: result.evaluationTime,
      //     metadata: JSON.stringify(result.metadata),
      //     createdAt: new Date()
      //   }
      // });
      
      // TODO: Send to Kafka for real-time analytics
      
    } catch (error) {
      console.error('Failed to log rule evaluation:', error);
    }
  }

  /**
   * Invalidate cache for a publisher when rules change
   */
}
