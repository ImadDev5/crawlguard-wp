// Core types for the Rules Engine
export interface CrawlerRequest {
  id: string;
  timestamp: Date;
  domain: string;
  url: string;
  botId?: string;
  userAgent: string;
  ipAddress: string;
  referer?: string;
  contentType?: string;
  requestMethod: string;
  headers: Record<string, string>;
  metadata?: Record<string, any>;
}

export interface PricingRule {
  id: string;
  publisherId: string;
  name: string;
  description?: string;
  conditions: RuleCondition[];
  actions: RuleAction[];
  priority: number;
  isActive: boolean;
  validFrom?: Date;
  validUntil?: Date;
  createdAt: Date;
  updatedAt: Date;
}

export interface RuleCondition {
  id: string;
  type: ConditionType;
  operator: ConditionOperator;
  value: string | number | boolean;
  metadata?: Record<string, any>;
}

export enum ConditionType {
  BOT_ID = 'bot_id',
  USER_AGENT = 'user_agent',
  CONTENT_TYPE = 'content_type',
  REQUEST_FREQUENCY = 'request_frequency',
  IP_ADDRESS = 'ip_address',
  REFERER = 'referer',
  DOMAIN = 'domain',
  URL_PATTERN = 'url_pattern',
  TIME_OF_DAY = 'time_of_day',
  DAY_OF_WEEK = 'day_of_week',
  GEOGRAPHY = 'geography',
  REQUEST_COUNT = 'request_count'
}

export enum ConditionOperator {
  EQUALS = 'equals',
  NOT_EQUALS = 'not_equals',
  CONTAINS = 'contains',
  NOT_CONTAINS = 'not_contains',
  STARTS_WITH = 'starts_with',
  ENDS_WITH = 'ends_with',
  GREATER_THAN = 'greater_than',
  LESS_THAN = 'less_than',
  GREATER_THAN_OR_EQUAL = 'greater_than_or_equal',
  LESS_THAN_OR_EQUAL = 'less_than_or_equal',
  IN = 'in',
  NOT_IN = 'not_in',
  REGEX = 'regex',
  IS_EMPTY = 'is_empty',
  IS_NOT_EMPTY = 'is_not_empty'
}

export interface RuleAction {
  id: string;
  type: ActionType;
  value: string | number | boolean;
  metadata?: Record<string, any>;
}

export enum ActionType {
  SET_PRICE = 'set_price',
  BLOCK_ACCESS = 'block_access',
  REQUIRE_PAYMENT = 'require_payment',
  REDIRECT = 'redirect',
  RATE_LIMIT = 'rate_limit',
  LOG_EVENT = 'log_event',
  SEND_NOTIFICATION = 'send_notification',
  APPLY_DISCOUNT = 'apply_discount',
  REQUIRE_AUTHENTICATION = 'require_authentication',
  CUSTOM_RESPONSE = 'custom_response'
}

export interface RuleEvaluationResult {
  matched: boolean;
  matchedRules: PricingRule[];
  actions: ExecutableAction[];
  pricing?: PricingDecision;
  metadata?: Record<string, any>;
  evaluationTime: number; // in milliseconds
}

export interface ExecutableAction {
  type: ActionType;
  value: string | number | boolean;
  rule: PricingRule;
  metadata?: Record<string, any>;
}

export interface PricingDecision {
  price: number;
  currency: string;
  priceType: 'per_request' | 'per_minute' | 'per_mb' | 'flat_rate';
  rule: PricingRule;
  discounts?: Discount[];
  metadata?: Record<string, any>;
}

export interface Discount {
  type: 'percentage' | 'fixed_amount';
  value: number;
  reason: string;
  metadata?: Record<string, any>;
}

export interface RuleTemplate {
  id: string;
  name: string;
  description: string;
  category: string;
  conditions: Partial<RuleCondition>[];
  actions: Partial<RuleAction>[];
  isPublic: boolean;
  usageCount: number;
  rating: number;
  createdBy: string;
  createdAt: Date;
}

export interface RuleValidationResult {
  valid: boolean;
  errors: string[];
  warnings: string[];
  suggestions?: string[];
}

export interface RuleExecutionContext {
  request: CrawlerRequest;
  publisherId: string;
  timestamp: Date;
  metadata?: Record<string, any>;
}

// Event types for Kafka
export interface RuleEvaluationEvent {
  eventId: string;
  eventType: 'rule_evaluation';
  timestamp: Date;
  publisherId: string;
  request: CrawlerRequest;
  result: RuleEvaluationResult;
  metadata?: Record<string, any>;
}

export interface RuleCreatedEvent {
  eventId: string;
  eventType: 'rule_created';
  timestamp: Date;
  publisherId: string;
  rule: PricingRule;
  metadata?: Record<string, any>;
}

export interface RuleUpdatedEvent {
  eventId: string;
  eventType: 'rule_updated';
  timestamp: Date;
  publisherId: string;
  rule: PricingRule;
  previousVersion: Partial<PricingRule>;
  metadata?: Record<string, any>;
}

export interface RuleDeletedEvent {
  eventId: string;
  eventType: 'rule_deleted';
  timestamp: Date;
  publisherId: string;
  ruleId: string;
  metadata?: Record<string, any>;
}
