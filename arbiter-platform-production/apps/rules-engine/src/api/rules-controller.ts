import express from 'express';
import { z } from 'zod';
import { RuleEvaluator } from '../engine/rule-evaluator';
import { 
  PricingRule, 
  CrawlerRequest, 
  ConditionType, 
  ConditionOperator, 
  ActionType 
} from '../models/types';

const router = express.Router();

// Validation schemas
const createRuleSchema = z.object({
  name: z.string().min(1).max(255),
  description: z.string().optional(),
  conditions: z.array(z.object({
    type: z.nativeEnum(ConditionType),
    operator: z.nativeEnum(ConditionOperator),
    value: z.union([z.string(), z.number(), z.boolean()])
  })),
  actions: z.array(z.object({
    type: z.nativeEnum(ActionType),
    value: z.union([z.string(), z.number(), z.boolean()])
  })),
  priority: z.number().min(0).max(1000).default(100),
  isActive: z.boolean().default(true),
  validFrom: z.string().datetime().optional(),
  validUntil: z.string().datetime().optional()
});

const evaluateRequestSchema = z.object({
  id: z.string().optional(),
  domain: z.string(),
  url: z.string().url(),
  botId: z.string().optional(),
  userAgent: z.string(),
  ipAddress: z.string().ip(),
  referer: z.string().optional(),
  contentType: z.string().optional(),
  requestMethod: z.string().default('GET'),
  headers: z.record(z.string()).optional().default({}),
  metadata: z.record(z.any()).optional()
});

/**
 * Rules Engine API Controller
 * Handles all REST endpoints for rule management and evaluation
 */
export class RulesController {
  private evaluator: RuleEvaluator;

  constructor(evaluator: RuleEvaluator) {
    this.evaluator = evaluator;
  }

  /**
   * Create a new pricing rule
   * POST /api/rules
   */
  async createRule(req: express.Request, res: express.Response): Promise<void> {
    try {
      const publisherId = req.user?.id;
      if (!publisherId) {
        res.status(401).json({ error: 'Authentication required' });
        return;
      }

      const ruleData = createRuleSchema.parse(req.body);
      
      // TODO: Create rule in database
      // const rule = await this.prisma.rule.create({...});
      
      res.status(201).json({
        success: true,
        message: 'Rule created successfully',
        data: {
          id: 'rule_' + Date.now(), // Placeholder
          ...ruleData,
          publisherId,
          createdAt: new Date(),
          updatedAt: new Date()
        }
      });
    } catch (error) {
      if (error instanceof z.ZodError) {
        res.status(400).json({
          error: 'Validation error',
          details: error.errors
        });
      } else {
        console.error('Create rule error:', error);
        res.status(500).json({
          error: 'Failed to create rule'
        });
      }
    }
  }

  /**
   * Get all rules for the authenticated publisher
   * GET /api/rules
   */
  async getRules(req: express.Request, res: express.Response): Promise<void> {
    try {
      const publisherId = req.user?.id;
      if (!publisherId) {
        res.status(401).json({ error: 'Authentication required' });
        return;
      }

      // TODO: Fetch rules from database
      // const rules = await this.prisma.rule.findMany({...});
      
      res.json({
        success: true,
        data: {
          rules: [], // Placeholder
          total: 0,
          page: 1,
          limit: 50
        }
      });
    } catch (error) {
      console.error('Get rules error:', error);
      res.status(500).json({
        error: 'Failed to fetch rules'
      });
    }
  }

  /**
   * Get a specific rule by ID
   * GET /api/rules/:id
   */
  async getRule(req: express.Request, res: express.Response): Promise<void> {
    try {
      const { id } = req.params;
      const publisherId = req.user?.id;
      
      if (!publisherId) {
        res.status(401).json({ error: 'Authentication required' });
        return;
      }

      // TODO: Fetch rule from database
      // const rule = await this.prisma.rule.findUnique({...});
      
      res.json({
        success: true,
        data: {
          id,
          publisherId,
          // ... rule data
        }
      });
    } catch (error) {
      console.error('Get rule error:', error);
      res.status(500).json({
        error: 'Failed to fetch rule'
      });
    }
  }

  /**
   * Update a rule
   * PUT /api/rules/:id
   */
  async updateRule(req: express.Request, res: express.Response): Promise<void> {
    try {
      const { id } = req.params;
      const publisherId = req.user?.id;
      
      if (!publisherId) {
        res.status(401).json({ error: 'Authentication required' });
        return;
      }

      const ruleData = createRuleSchema.parse(req.body);
      
      // TODO: Update rule in database
      // const rule = await this.prisma.rule.update({...});
      
      // Invalidate cache
      await this.evaluator.invalidateCache(publisherId);
      
      res.json({
        success: true,
        message: 'Rule updated successfully',
        data: {
          id,
          ...ruleData,
          publisherId,
          updatedAt: new Date()
        }
      });
    } catch (error) {
      if (error instanceof z.ZodError) {
        res.status(400).json({
          error: 'Validation error',
          details: error.errors
        });
      } else {
        console.error('Update rule error:', error);
        res.status(500).json({
          error: 'Failed to update rule'
        });
      }
    }
  }

  /**
   * Delete a rule
   * DELETE /api/rules/:id
   */
  async deleteRule(req: express.Request, res: express.Response): Promise<void> {
    try {
      const { id } = req.params;
      const publisherId = req.user?.id;
      
      if (!publisherId) {
        res.status(401).json({ error: 'Authentication required' });
        return;
      }

      // TODO: Delete rule from database
      // await this.prisma.rule.delete({...});
      
      // Invalidate cache
      await this.evaluator.invalidateCache(publisherId);
      
      res.json({
        success: true,
        message: 'Rule deleted successfully'
      });
    } catch (error) {
      console.error('Delete rule error:', error);
      res.status(500).json({
        error: 'Failed to delete rule'
      });
    }
  }

  /**
   * Evaluate a request against rules
   * POST /api/rules/evaluate
   */
  async evaluateRequest(req: express.Request, res: express.Response): Promise<void> {
    try {
      const publisherId = req.user?.id;
      if (!publisherId) {
        res.status(401).json({ error: 'Authentication required' });
        return;
      }

      const requestData = evaluateRequestSchema.parse(req.body);
      
      const crawlerRequest: CrawlerRequest = {
        id: requestData.id || 'req_' + Date.now(),
        timestamp: new Date(),
        domain: requestData.domain,
        url: requestData.url,
        botId: requestData.botId,
        userAgent: requestData.userAgent,
        ipAddress: requestData.ipAddress,
        referer: requestData.referer,
        contentType: requestData.contentType,
        requestMethod: requestData.requestMethod,
        headers: requestData.headers || {},
        metadata: requestData.metadata
      };

      const result = await this.evaluator.evaluateRequest(crawlerRequest, publisherId);
      
      res.json({
        success: true,
        data: {
          request: crawlerRequest,
          result
        }
      });
    } catch (error) {
      if (error instanceof z.ZodError) {
        res.status(400).json({
          error: 'Validation error',
          details: error.errors
        });
      } else {
        console.error('Evaluate request error:', error);
        res.status(500).json({
          error: 'Failed to evaluate request'
        });
      }
    }
  }

  /**
   * Test rule conditions without saving
   * POST /api/rules/test
   */
  async testRule(req: express.Request, res: express.Response): Promise<void> {
    try {
      const publisherId = req.user?.id;
      if (!publisherId) {
        res.status(401).json({ error: 'Authentication required' });
        return;
      }

      const { rule, request } = req.body;
      
      // Validate inputs
      const ruleData = createRuleSchema.parse(rule);
      const requestData = evaluateRequestSchema.parse(request);
      
      // Create temporary rule for testing
      const tempRule: PricingRule = {
        id: 'temp_' + Date.now(),
        publisherId,
        name: ruleData.name,
        description: ruleData.description,
        conditions: ruleData.conditions.map((c, i) => ({
          id: `temp_condition_${i}`,
          ...c
        })),
        actions: ruleData.actions.map((a, i) => ({
          id: `temp_action_${i}`,
          ...a
        })),
        priority: ruleData.priority,
        isActive: true,
        createdAt: new Date(),
        updatedAt: new Date()
      };
      
      const crawlerRequest: CrawlerRequest = {
        id: requestData.id || 'test_' + Date.now(),
        timestamp: new Date(),
        domain: requestData.domain,
        url: requestData.url,
        botId: requestData.botId,
        userAgent: requestData.userAgent,
        ipAddress: requestData.ipAddress,
        referer: requestData.referer,
        contentType: requestData.contentType,
        requestMethod: requestData.requestMethod,
        headers: requestData.headers || {},
        metadata: requestData.metadata
      };

      // Test the rule (without database lookup)
      const matched = this.evaluator['evaluateRuleConditions'](crawlerRequest, tempRule.conditions);
      const actions = matched ? this.evaluator['determineActions']([tempRule], crawlerRequest) : [];
      const pricing = this.evaluator['calculatePricing'](actions, crawlerRequest);
      
      res.json({
        success: true,
        data: {
          matched,
          actions,
          pricing,
          rule: tempRule,
          request: crawlerRequest
        }
      });
    } catch (error) {
      if (error instanceof z.ZodError) {
        res.status(400).json({
          error: 'Validation error',
          details: error.errors
        });
      } else {
        console.error('Test rule error:', error);
        res.status(500).json({
          error: 'Failed to test rule'
        });
      }
    }
  }

  /**
   * Get rule templates
   * GET /api/rules/templates
   */
  async getTemplates(req: express.Request, res: express.Response): Promise<void> {
    try {
      const templates = [
        {
          id: 'template_1',
          name: 'Block GPT Bots',
          description: 'Block all GPT-based bots from accessing content',
          category: 'blocking',
          conditions: [
            {
              type: ConditionType.BOT_ID,
              operator: ConditionOperator.CONTAINS,
              value: 'GPT'
            }
          ],
          actions: [
            {
              type: ActionType.BLOCK_ACCESS,
              value: true
            }
          ]
        },
        {
          id: 'template_2',
          name: 'Premium Pricing for AI Bots',
          description: 'Charge $0.01 per request for AI bot access',
          category: 'pricing',
          conditions: [
            {
              type: ConditionType.BOT_ID,
              operator: ConditionOperator.IN,
              value: 'GPTBot,ChatGPT-User,Claude-Web,CCBot'
            }
          ],
          actions: [
            {
              type: ActionType.SET_PRICE,
              value: 0.01
            }
          ]
        },
        {
          id: 'template_3',
          name: 'Rate Limit High Volume',
          description: 'Limit bots to 100 requests per minute',
          category: 'rate_limiting',
          conditions: [
            {
              type: ConditionType.REQUEST_FREQUENCY,
              operator: ConditionOperator.GREATER_THAN,
              value: 100
            }
          ],
          actions: [
            {
              type: ActionType.RATE_LIMIT,
              value: 100
            }
          ]
        }
      ];

      res.json({
        success: true,
        data: {
          templates,
          total: templates.length
        }
      });
    } catch (error) {
      console.error('Get templates error:', error);
      res.status(500).json({
        error: 'Failed to fetch templates'
      });
    }
  }

  /**
   * Get analytics for rules
   * GET /api/rules/analytics
   */
  async getAnalytics(req: express.Request, res: express.Response): Promise<void> {
    try {
      const publisherId = req.user?.id;
      if (!publisherId) {
        res.status(401).json({ error: 'Authentication required' });
        return;
      }

      // TODO: Fetch analytics from database
      const analytics = {
        totalRequests: 0,
        matchedRequests: 0,
        totalRevenue: 0,
        topRules: [],
        requestsByHour: [],
        revenueByDay: []
      };

      res.json({
        success: true,
        data: analytics
      });
    } catch (error) {
      console.error('Get analytics error:', error);
      res.status(500).json({
        error: 'Failed to fetch analytics'
      });
    }
  }
}

// Export router setup function
export function setupRulesRoutes(evaluator: RuleEvaluator): express.Router {
  const controller = new RulesController(evaluator);
  
  // Rule CRUD operations
  router.post('/rules', controller.createRule.bind(controller));
  router.get('/rules', controller.getRules.bind(controller));
  router.get('/rules/:id', controller.getRule.bind(controller));
  router.put('/rules/:id', controller.updateRule.bind(controller));
  router.delete('/rules/:id', controller.deleteRule.bind(controller));
  
  // Rule evaluation
  router.post('/rules/evaluate', controller.evaluateRequest.bind(controller));
  router.post('/rules/test', controller.testRule.bind(controller));
  
  // Utilities
  router.get('/rules/templates', controller.getTemplates.bind(controller));
  router.get('/rules/analytics', controller.getAnalytics.bind(controller));
  
  return router;
}
