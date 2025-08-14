/**
 * Arbiter Platform - Dynamic Pricing Engine Service
 * AI-powered content pricing based on multiple factors
 * Supports real-time pricing adjustments and market dynamics
 */

interface ContentMetadata {
  contentType: 'article' | 'image' | 'video' | 'dataset' | 'code' | 'research';
  contentLength: number;
  publishDate: Date;
  authorReputation: number;
  topicCategory: string;
  exclusivity: 'exclusive' | 'limited' | 'open';
  qualityScore: number;
  engagementMetrics: {
    views: number;
    shares: number;
    comments: number;
    timeOnPage: number;
  };
}

interface PublisherSettings {
  id: string;
  tier: 'free' | 'pro' | 'business' | 'enterprise';
  baseRate: number;
  premiumMultiplier: number;
  volumeDiscounts: boolean;
  customPricingRules: PricingRule[];
  minimumRate: number;
  maximumRate: number;
}

interface AICompanyProfile {
  id: string;
  company: string;
  creditRating: 'A' | 'B' | 'C' | 'D';
  monthlyVolume: number;
  paymentHistory: 'excellent' | 'good' | 'fair' | 'poor';
  preferredContentTypes: string[];
  maxRateWillingness: number;
}

interface PricingRule {
  id: string;
  name: string;
  condition: string; // JSON logic expression
  action: 'multiply' | 'add' | 'set' | 'block';
  value: number;
  priority: number;
  active: boolean;
}

interface PricingRequest {
  contentMetadata: ContentMetadata;
  publisherSettings: PublisherSettings;
  aiCompanyProfile: AICompanyProfile;
  marketConditions: MarketConditions;
  timestamp: number;
}

interface MarketConditions {
  demandLevel: 'low' | 'medium' | 'high' | 'surge';
  supplyLevel: 'low' | 'medium' | 'high';
  competitorPricing: number[];
  seasonalAdjustment: number;
  industryTrends: {
    aiTrainingDemand: number;
    contentScarcity: number;
    regulatoryPressure: number;
  };
}

interface PricingResult {
  finalPrice: number;
  basePrice: number;
  adjustments: PricingAdjustment[];
  confidence: number;
  reasoning: string[];
  alternativePricing: {
    bulk: number;
    subscription: number;
    exclusive: number;
  };
  validUntil: Date;
}

interface PricingAdjustment {
  type: string;
  factor: number;
  amount: number;
  reasoning: string;
}

class DynamicPricingEngine {
  private baseRates: Map<string, number>;
  private marketData: any;

  constructor() {
    this.initializeBaseRates();
    this.loadMarketData();
  }

  private initializeBaseRates() {
    this.baseRates = new Map([
      // Content type base rates (per 1000 words/tokens)
      ['article', 0.001],
      ['research', 0.005],
      ['code', 0.003],
      ['dataset', 0.002],
      ['image', 0.0005],
      ['video', 0.01],
      
      // Industry multipliers
      ['healthcare', 1.5],
      ['finance', 1.8],
      ['legal', 2.0],
      ['technology', 1.2],
      ['education', 0.8],
      ['news', 1.0],
      
      // AI company type multipliers
      ['training', 1.0],
      ['inference', 1.2],
      ['research', 0.7],
      ['commercial', 1.5]
    ]);
  }

  private async loadMarketData() {
    // In production, this would load real market data
    this.marketData = {
      averagePricing: 0.001,
      demandTrends: 'increasing',
      supplySituation: 'stable',
      lastUpdated: new Date()
    };
  }

  async calculatePrice(request: PricingRequest): Promise<PricingResult> {
    try {
      // 1. Calculate base price
      const basePrice = this.calculateBasePrice(request);
      
      // 2. Apply content quality adjustments
      const qualityAdjustments = this.applyQualityAdjustments(basePrice, request);
      
      // 3. Apply market condition adjustments
      const marketAdjustments = this.applyMarketAdjustments(qualityAdjustments.price, request);
      
      // 4. Apply publisher-specific rules
      const publisherAdjustments = this.applyPublisherRules(marketAdjustments.price, request);
      
      // 5. Apply AI company adjustments
      const companyAdjustments = this.applyCompanyAdjustments(publisherAdjustments.price, request);
      
      // 6. Apply volume discounts
      const volumeAdjustments = this.applyVolumeDiscounts(companyAdjustments.price, request);
      
      // 7. Ensure price bounds
      const finalPrice = this.enforcePriceBounds(volumeAdjustments.price, request);
      
      // Combine all adjustments
      const allAdjustments = [
        ...qualityAdjustments.adjustments,
        ...marketAdjustments.adjustments,
        ...publisherAdjustments.adjustments,
        ...companyAdjustments.adjustments,
        ...volumeAdjustments.adjustments
      ];

      // Generate reasoning
      const reasoning = this.generatePricingReasoning(request, allAdjustments);
      
      // Calculate alternative pricing options
      const alternativePricing = this.calculateAlternativePricing(finalPrice, request);
      
      // Calculate confidence score
      const confidence = this.calculateConfidence(request, allAdjustments);

      return {
        finalPrice,
        basePrice,
        adjustments: allAdjustments,
        confidence,
        reasoning,
        alternativePricing,
        validUntil: new Date(Date.now() + 15 * 60 * 1000) // 15 minutes
      };
    } catch (error) {
      console.error('Pricing calculation error:', error);
      throw new Error('Failed to calculate pricing');
    }
  }

  private calculateBasePrice(request: PricingRequest): number {
    const { contentMetadata, publisherSettings } = request;
    
    // Start with content type base rate
    let baseRate = this.baseRates.get(contentMetadata.contentType) || 0.001;
    
    // Adjust for content length
    const lengthMultiplier = Math.max(1, contentMetadata.contentLength / 1000);
    
    // Apply publisher's base rate if higher
    if (publisherSettings.baseRate > baseRate) {
      baseRate = publisherSettings.baseRate;
    }
    
    return baseRate * lengthMultiplier;
  }

  private applyQualityAdjustments(basePrice: number, request: PricingRequest): {
    price: number;
    adjustments: PricingAdjustment[];
  } {
    const adjustments: PricingAdjustment[] = [];
    let price = basePrice;

    // Quality score adjustment
    const qualityMultiplier = 0.5 + (request.contentMetadata.qualityScore / 100);
    const qualityAdjustment = price * (qualityMultiplier - 1);
    
    adjustments.push({
      type: 'quality_score',
      factor: qualityMultiplier,
      amount: qualityAdjustment,
      reasoning: `Content quality score: ${request.contentMetadata.qualityScore}/100`
    });
    
    price *= qualityMultiplier;

    // Author reputation adjustment
    const reputationMultiplier = 0.8 + (request.contentMetadata.authorReputation / 500);
    const reputationAdjustment = price * (reputationMultiplier - 1);
    
    adjustments.push({
      type: 'author_reputation',
      factor: reputationMultiplier,
      amount: reputationAdjustment,
      reasoning: `Author reputation: ${request.contentMetadata.authorReputation}/100`
    });
    
    price *= reputationMultiplier;

    // Exclusivity premium
    const exclusivityMultipliers = {
      'open': 1.0,
      'limited': 1.3,
      'exclusive': 1.8
    };
    
    const exclusivityMultiplier = exclusivityMultipliers[request.contentMetadata.exclusivity];
    const exclusivityAdjustment = price * (exclusivityMultiplier - 1);
    
    adjustments.push({
      type: 'exclusivity',
      factor: exclusivityMultiplier,
      amount: exclusivityAdjustment,
      reasoning: `Content exclusivity: ${request.contentMetadata.exclusivity}`
    });
    
    price *= exclusivityMultiplier;

    // Engagement premium
    const engagement = request.contentMetadata.engagementMetrics;
    const engagementScore = Math.min(
      (engagement.views / 1000) + 
      (engagement.shares / 100) + 
      (engagement.comments / 50) +
      (engagement.timeOnPage / 300), 
      10
    );
    
    const engagementMultiplier = 1 + (engagementScore / 20);
    const engagementAdjustment = price * (engagementMultiplier - 1);
    
    adjustments.push({
      type: 'engagement',
      factor: engagementMultiplier,
      amount: engagementAdjustment,
      reasoning: `High engagement content (score: ${engagementScore.toFixed(1)})`
    });
    
    price *= engagementMultiplier;

    return { price, adjustments };
  }

  private applyMarketAdjustments(basePrice: number, request: PricingRequest): {
    price: number;
    adjustments: PricingAdjustment[];
  } {
    const adjustments: PricingAdjustment[] = [];
    let price = basePrice;
    const market = request.marketConditions;

    // Demand adjustment
    const demandMultipliers = {
      'low': 0.8,
      'medium': 1.0,
      'high': 1.3,
      'surge': 1.6
    };
    
    const demandMultiplier = demandMultipliers[market.demandLevel];
    const demandAdjustment = price * (demandMultiplier - 1);
    
    adjustments.push({
      type: 'market_demand',
      factor: demandMultiplier,
      amount: demandAdjustment,
      reasoning: `Market demand: ${market.demandLevel}`
    });
    
    price *= demandMultiplier;

    // Supply adjustment
    const supplyMultipliers = {
      'low': 1.4,   // Scarcity premium
      'medium': 1.0,
      'high': 0.85  // Abundance discount
    };
    
    const supplyMultiplier = supplyMultipliers[market.supplyLevel];
    const supplyAdjustment = price * (supplyMultiplier - 1);
    
    adjustments.push({
      type: 'market_supply',
      factor: supplyMultiplier,
      amount: supplyAdjustment,
      reasoning: `Market supply: ${market.supplyLevel}`
    });
    
    price *= supplyMultiplier;

    // Seasonal adjustment
    const seasonalAdjustment = price * market.seasonalAdjustment;
    
    adjustments.push({
      type: 'seasonal',
      factor: 1 + market.seasonalAdjustment,
      amount: seasonalAdjustment,
      reasoning: `Seasonal market adjustment: ${(market.seasonalAdjustment * 100).toFixed(1)}%`
    });
    
    price *= (1 + market.seasonalAdjustment);

    return { price, adjustments };
  }

  private applyPublisherRules(basePrice: number, request: PricingRequest): {
    price: number;
    adjustments: PricingAdjustment[];
  } {
    const adjustments: PricingAdjustment[] = [];
    let price = basePrice;
    const settings = request.publisherSettings;

    // Apply custom pricing rules
    const sortedRules = settings.customPricingRules
      .filter(rule => rule.active)
      .sort((a, b) => b.priority - a.priority);

    for (const rule of sortedRules) {
      if (this.evaluateRuleCondition(rule.condition, request)) {
        const adjustment = this.applyPricingRule(price, rule);
        adjustments.push({
          type: 'custom_rule',
          factor: rule.action === 'multiply' ? rule.value : 1,
          amount: adjustment,
          reasoning: `Custom rule: ${rule.name}`
        });
        
        if (rule.action === 'multiply') {
          price *= rule.value;
        } else if (rule.action === 'add') {
          price += rule.value;
        } else if (rule.action === 'set') {
          price = rule.value;
        }
      }
    }

    // Apply premium multiplier
    if (settings.premiumMultiplier > 1) {
      const premiumAdjustment = price * (settings.premiumMultiplier - 1);
      adjustments.push({
        type: 'premium_publisher',
        factor: settings.premiumMultiplier,
        amount: premiumAdjustment,
        reasoning: `Premium publisher multiplier: ${settings.premiumMultiplier}x`
      });
      
      price *= settings.premiumMultiplier;
    }

    return { price, adjustments };
  }

  private applyCompanyAdjustments(basePrice: number, request: PricingRequest): {
    price: number;
    adjustments: PricingAdjustment[];
  } {
    const adjustments: PricingAdjustment[] = [];
    let price = basePrice;
    const company = request.aiCompanyProfile;

    // Credit rating adjustment
    const creditMultipliers = {
      'A': 1.0,   // No adjustment for excellent credit
      'B': 1.05,  // Small premium for good credit
      'C': 1.15,  // Higher premium for fair credit
      'D': 1.3    // Significant premium for poor credit
    };
    
    const creditMultiplier = creditMultipliers[company.creditRating];
    const creditAdjustment = price * (creditMultiplier - 1);
    
    adjustments.push({
      type: 'credit_rating',
      factor: creditMultiplier,
      amount: creditAdjustment,
      reasoning: `Credit rating: ${company.creditRating}`
    });
    
    price *= creditMultiplier;

    // Payment history adjustment
    const paymentMultipliers = {
      'excellent': 0.95, // Discount for excellent payers
      'good': 1.0,
      'fair': 1.1,
      'poor': 1.25
    };
    
    const paymentMultiplier = paymentMultipliers[company.paymentHistory];
    const paymentAdjustment = price * (paymentMultiplier - 1);
    
    adjustments.push({
      type: 'payment_history',
      factor: paymentMultiplier,
      amount: paymentAdjustment,
      reasoning: `Payment history: ${company.paymentHistory}`
    });
    
    price *= paymentMultiplier;

    return { price, adjustments };
  }

  private applyVolumeDiscounts(basePrice: number, request: PricingRequest): {
    price: number;
    adjustments: PricingAdjustment[];
  } {
    const adjustments: PricingAdjustment[] = [];
    let price = basePrice;
    
    if (!request.publisherSettings.volumeDiscounts) {
      return { price, adjustments };
    }

    const monthlyVolume = request.aiCompanyProfile.monthlyVolume;
    let discountRate = 0;

    // Volume discount tiers
    if (monthlyVolume >= 1000000) {
      discountRate = 0.15; // 15% discount for 1M+ requests
    } else if (monthlyVolume >= 500000) {
      discountRate = 0.10; // 10% discount for 500K+ requests
    } else if (monthlyVolume >= 100000) {
      discountRate = 0.05; // 5% discount for 100K+ requests
    }

    if (discountRate > 0) {
      const discountAmount = price * discountRate;
      adjustments.push({
        type: 'volume_discount',
        factor: 1 - discountRate,
        amount: -discountAmount,
        reasoning: `Volume discount: ${(discountRate * 100).toFixed(0)}% for ${monthlyVolume} monthly requests`
      });
      
      price *= (1 - discountRate);
    }

    return { price, adjustments };
  }

  private enforcePriceBounds(price: number, request: PricingRequest): number {
    const settings = request.publisherSettings;
    
    if (price < settings.minimumRate) {
      return settings.minimumRate;
    }
    
    if (price > settings.maximumRate) {
      return settings.maximumRate;
    }
    
    return price;
  }

  private evaluateRuleCondition(condition: string, request: PricingRequest): boolean {
    try {
      // This would use a JSON logic evaluator in production
      // For now, return true for demonstration
      return true;
    } catch (error) {
      console.error('Rule condition evaluation error:', error);
      return false;
    }
  }

  private applyPricingRule(price: number, rule: PricingRule): number {
    switch (rule.action) {
      case 'multiply':
        return price * (rule.value - 1);
      case 'add':
        return rule.value;
      case 'set':
        return rule.value - price;
      default:
        return 0;
    }
  }

  private generatePricingReasoning(request: PricingRequest, adjustments: PricingAdjustment[]): string[] {
    const reasoning: string[] = [];
    
    reasoning.push(`Base rate for ${request.contentMetadata.contentType}: $${this.baseRates.get(request.contentMetadata.contentType)}`);
    
    adjustments.forEach(adj => {
      if (Math.abs(adj.amount) > 0.00001) {
        reasoning.push(adj.reasoning);
      }
    });
    
    return reasoning;
  }

  private calculateAlternativePricing(basePrice: number, request: PricingRequest): {
    bulk: number;
    subscription: number;
    exclusive: number;
  } {
    return {
      bulk: basePrice * 0.7,        // 30% discount for bulk licensing
      subscription: basePrice * 0.6, // 40% discount for subscription
      exclusive: basePrice * 2.5     // 150% premium for exclusive access
    };
  }

  private calculateConfidence(request: PricingRequest, adjustments: PricingAdjustment[]): number {
    let confidence = 85; // Base confidence
    
    // Reduce confidence for unusual market conditions
    if (request.marketConditions.demandLevel === 'surge') {
      confidence -= 10;
    }
    
    // Reduce confidence for new AI companies
    if (request.aiCompanyProfile.paymentHistory === 'poor') {
      confidence -= 15;
    }
    
    // Increase confidence for stable publishers
    if (request.publisherSettings.tier === 'enterprise') {
      confidence += 10;
    }
    
    return Math.max(50, Math.min(95, confidence));
  }
}

export default DynamicPricingEngine;
export { 
  PricingRequest, 
  PricingResult, 
  ContentMetadata, 
  PublisherSettings, 
  AICompanyProfile,
  MarketConditions 
};
