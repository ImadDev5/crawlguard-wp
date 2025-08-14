/**
 * Arbiter Platform - Content Licensing Service
 * Manages content licensing agreements, permissions, and usage tracking
 * Handles complex licensing scenarios and compliance
 */

import { EventEmitter } from 'events';

interface LicenseTerms {
  licenseId: string;
  contentId: string;
  publisherId: string;
  licenseeId: string;
  licenseType: 'standard' | 'premium' | 'exclusive' | 'research' | 'commercial';
  usage: UsageRights;
  pricing: PricingTerms;
  duration: LicenseDuration;
  territory: string[];
  restrictions: LicenseRestriction[];
  compliance: ComplianceRequirements;
  createdAt: Date;
  updatedAt: Date;
  status: 'active' | 'expired' | 'suspended' | 'terminated';
}

interface UsageRights {
  training: boolean;
  inference: boolean;
  commercialUse: boolean;
  redistribution: boolean;
  modification: boolean;
  attribution: boolean;
  maxRequests?: number;
  maxTokens?: number;
  purposeRestrictions: string[];
  industryRestrictions: string[];
}

interface PricingTerms {
  model: 'per_request' | 'per_token' | 'flat_rate' | 'revenue_share' | 'hybrid';
  baseRate: number;
  currency: string;
  billing: 'prepaid' | 'postpaid' | 'monthly' | 'annual';
  minimumCommitment?: number;
  volumeDiscounts: VolumeDiscount[];
  revenueSharePercent?: number;
  paymentTerms: number; // days
}

interface VolumeDiscount {
  threshold: number;
  discountPercent: number;
}

interface LicenseDuration {
  startDate: Date;
  endDate?: Date;
  autoRenewal: boolean;
  renewalTerms?: string;
  noticePeriod: number; // days
}

interface LicenseRestriction {
  type: 'usage' | 'content' | 'territory' | 'purpose' | 'time' | 'volume';
  description: string;
  parameters: any;
  enforced: boolean;
  violationAction: 'warn' | 'throttle' | 'block' | 'terminate';
}

interface ComplianceRequirements {
  dataProtection: string[]; // GDPR, CCPA, etc.
  industryStandards: string[]; // SOC2, ISO27001, etc.
  auditRequirements: boolean;
  reportingFrequency: 'daily' | 'weekly' | 'monthly' | 'quarterly';
  retentionPeriod: number; // days
}

interface LicenseUsage {
  licenseId: string;
  timestamp: Date;
  usage: {
    requestCount: number;
    tokenCount: number;
    dataVolume: number;
    processingTime: number;
  };
  metadata: {
    endpoint: string;
    userAgent: string;
    ipAddress: string;
    purpose: string;
    model: string;
  };
}

interface LicenseViolation {
  licenseId: string;
  violationType: 'usage_exceeded' | 'unauthorized_purpose' | 'territory_violation' | 'content_misuse' | 'payment_default';
  description: string;
  severity: 'low' | 'medium' | 'high' | 'critical';
  detectedAt: Date;
  evidence: any;
  resolved: boolean;
  action: string;
}

interface LicenseAgreement {
  agreementId: string;
  publisherId: string;
  licenseeId: string;
  licenses: string[];
  masterTerms: MasterTerms;
  signedAt: Date;
  effectiveDate: Date;
  status: 'draft' | 'pending' | 'signed' | 'terminated';
  documents: LicenseDocument[];
}

interface MasterTerms {
  jurisdiction: string;
  disputeResolution: 'arbitration' | 'litigation' | 'mediation';
  liability: {
    cap: number;
    exclusions: string[];
  };
  indemnification: {
    mutual: boolean;
    scope: string[];
  };
  termination: {
    forCause: string[];
    withoutCause: number; // days notice
    survivingClauses: string[];
  };
}

interface LicenseDocument {
  documentId: string;
  type: 'agreement' | 'amendment' | 'exhibit' | 'certificate';
  title: string;
  content: string;
  signature: {
    signedBy: string;
    signedAt: Date;
    ipAddress: string;
    signature: string;
  };
}

class ContentLicensingService extends EventEmitter {
  private licenses: Map<string, LicenseTerms>;
  private usageTracking: Map<string, LicenseUsage[]>;
  private violations: Map<string, LicenseViolation[]>;
  private agreements: Map<string, LicenseAgreement>;

  constructor() {
    super();
    this.licenses = new Map();
    this.usageTracking = new Map();
    this.violations = new Map();
    this.agreements = new Map();
  }

  async createLicense(terms: Partial<LicenseTerms>): Promise<LicenseTerms> {
    const licenseId = this.generateLicenseId();
    
    const license: LicenseTerms = {
      licenseId,
      contentId: terms.contentId!,
      publisherId: terms.publisherId!,
      licenseeId: terms.licenseeId!,
      licenseType: terms.licenseType || 'standard',
      usage: terms.usage || this.getDefaultUsageRights(),
      pricing: terms.pricing || this.getDefaultPricingTerms(),
      duration: terms.duration || this.getDefaultDuration(),
      territory: terms.territory || ['global'],
      restrictions: terms.restrictions || [],
      compliance: terms.compliance || this.getDefaultCompliance(),
      createdAt: new Date(),
      updatedAt: new Date(),
      status: 'active'
    };

    // Validate license terms
    this.validateLicenseTerms(license);
    
    // Store license
    this.licenses.set(licenseId, license);
    this.usageTracking.set(licenseId, []);
    
    // Emit event
    this.emit('licenseCreated', { license });
    
    return license;
  }

  async getLicense(licenseId: string): Promise<LicenseTerms | null> {
    const license = this.licenses.get(licenseId);
    return license || null;
  }

  async updateLicense(licenseId: string, updates: Partial<LicenseTerms>): Promise<LicenseTerms> {
    const license = this.licenses.get(licenseId);
    if (!license) {
      throw new Error(`License ${licenseId} not found`);
    }

    const updatedLicense = {
      ...license,
      ...updates,
      updatedAt: new Date()
    };

    this.validateLicenseTerms(updatedLicense);
    this.licenses.set(licenseId, updatedLicense);
    
    this.emit('licenseUpdated', { license: updatedLicense, changes: updates });
    
    return updatedLicense;
  }

  async checkLicenseCompliance(licenseId: string, requestedUsage: Partial<UsageRights>): Promise<{
    allowed: boolean;
    violations: string[];
    restrictions: LicenseRestriction[];
  }> {
    const license = this.licenses.get(licenseId);
    if (!license) {
      throw new Error(`License ${licenseId} not found`);
    }

    const violations: string[] = [];
    const applicableRestrictions: LicenseRestriction[] = [];

    // Check license status
    if (license.status !== 'active') {
      violations.push(`License is ${license.status}`);
    }

    // Check expiration
    if (license.duration.endDate && license.duration.endDate < new Date()) {
      violations.push('License has expired');
    }

    // Check usage rights
    if (requestedUsage.training && !license.usage.training) {
      violations.push('Training usage not permitted');
    }

    if (requestedUsage.inference && !license.usage.inference) {
      violations.push('Inference usage not permitted');
    }

    if (requestedUsage.commercialUse && !license.usage.commercialUse) {
      violations.push('Commercial use not permitted');
    }

    if (requestedUsage.redistribution && !license.usage.redistribution) {
      violations.push('Redistribution not permitted');
    }

    // Check usage limits
    const currentUsage = this.getCurrentUsage(licenseId);
    if (license.usage.maxRequests && currentUsage.requestCount >= license.usage.maxRequests) {
      violations.push('Request limit exceeded');
    }

    if (license.usage.maxTokens && currentUsage.tokenCount >= license.usage.maxTokens) {
      violations.push('Token limit exceeded');
    }

    // Check restrictions
    for (const restriction of license.restrictions) {
      if (restriction.enforced && this.checkRestriction(restriction, requestedUsage)) {
        violations.push(`Restriction violated: ${restriction.description}`);
        applicableRestrictions.push(restriction);
      }
    }

    return {
      allowed: violations.length === 0,
      violations,
      restrictions: applicableRestrictions
    };
  }

  async trackUsage(licenseId: string, usage: Omit<LicenseUsage, 'licenseId'>): Promise<void> {
    const license = this.licenses.get(licenseId);
    if (!license) {
      throw new Error(`License ${licenseId} not found`);
    }

    const usageRecord: LicenseUsage = {
      licenseId,
      ...usage
    };

    // Store usage
    const usageHistory = this.usageTracking.get(licenseId) || [];
    usageHistory.push(usageRecord);
    this.usageTracking.set(licenseId, usageHistory);

    // Check for violations
    await this.checkForViolations(licenseId, usageRecord);

    // Emit event
    this.emit('usageTracked', { licenseId, usage: usageRecord });
  }

  async getUsageReport(licenseId: string, startDate: Date, endDate: Date): Promise<{
    license: LicenseTerms;
    usage: LicenseUsage[];
    summary: {
      totalRequests: number;
      totalTokens: number;
      totalDataVolume: number;
      averageProcessingTime: number;
      peakUsage: Date;
      complianceStatus: string;
    };
  }> {
    const license = this.licenses.get(licenseId);
    if (!license) {
      throw new Error(`License ${licenseId} not found`);
    }

    const allUsage = this.usageTracking.get(licenseId) || [];
    const periodUsage = allUsage.filter(u => 
      u.timestamp >= startDate && u.timestamp <= endDate
    );

    const summary = {
      totalRequests: periodUsage.reduce((sum, u) => sum + u.usage.requestCount, 0),
      totalTokens: periodUsage.reduce((sum, u) => sum + u.usage.tokenCount, 0),
      totalDataVolume: periodUsage.reduce((sum, u) => sum + u.usage.dataVolume, 0),
      averageProcessingTime: periodUsage.length > 0 
        ? periodUsage.reduce((sum, u) => sum + u.usage.processingTime, 0) / periodUsage.length
        : 0,
      peakUsage: this.findPeakUsage(periodUsage),
      complianceStatus: this.getComplianceStatus(licenseId)
    };

    return {
      license,
      usage: periodUsage,
      summary
    };
  }

  async createAgreement(agreement: Omit<LicenseAgreement, 'agreementId' | 'signedAt'>): Promise<LicenseAgreement> {
    const agreementId = this.generateAgreementId();
    
    const newAgreement: LicenseAgreement = {
      agreementId,
      signedAt: new Date(),
      ...agreement
    };

    this.agreements.set(agreementId, newAgreement);
    this.emit('agreementCreated', { agreement: newAgreement });
    
    return newAgreement;
  }

  async signAgreement(agreementId: string, signatory: string, signature: string): Promise<void> {
    const agreement = this.agreements.get(agreementId);
    if (!agreement) {
      throw new Error(`Agreement ${agreementId} not found`);
    }

    agreement.status = 'signed';
    agreement.signedAt = new Date();
    
    // Add signature to documents
    agreement.documents.forEach(doc => {
      if (!doc.signature) {
        doc.signature = {
          signedBy: signatory,
          signedAt: new Date(),
          ipAddress: '0.0.0.0', // Would be real IP in production
          signature
        };
      }
    });

    this.agreements.set(agreementId, agreement);
    this.emit('agreementSigned', { agreement });
  }

  async terminateLicense(licenseId: string, reason: string): Promise<void> {
    const license = this.licenses.get(licenseId);
    if (!license) {
      throw new Error(`License ${licenseId} not found`);
    }

    license.status = 'terminated';
    license.updatedAt = new Date();
    
    this.licenses.set(licenseId, license);
    this.emit('licenseTerminated', { license, reason });
  }

  async getLicenseViolations(licenseId: string): Promise<LicenseViolation[]> {
    return this.violations.get(licenseId) || [];
  }

  async resolveViolation(licenseId: string, violationIndex: number, resolution: string): Promise<void> {
    const violations = this.violations.get(licenseId) || [];
    if (violations[violationIndex]) {
      violations[violationIndex].resolved = true;
      violations[violationIndex].action = resolution;
      this.violations.set(licenseId, violations);
      
      this.emit('violationResolved', { 
        licenseId, 
        violation: violations[violationIndex],
        resolution 
      });
    }
  }

  private generateLicenseId(): string {
    return `LIC-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateAgreementId(): string {
    return `AGR-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  private validateLicenseTerms(license: LicenseTerms): void {
    if (!license.contentId || !license.publisherId || !license.licenseeId) {
      throw new Error('Missing required license fields');
    }

    if (license.duration.endDate && license.duration.endDate <= license.duration.startDate) {
      throw new Error('Invalid license duration');
    }

    if (license.pricing.baseRate <= 0) {
      throw new Error('Invalid pricing terms');
    }
  }

  private getDefaultUsageRights(): UsageRights {
    return {
      training: true,
      inference: true,
      commercialUse: false,
      redistribution: false,
      modification: false,
      attribution: true,
      purposeRestrictions: [],
      industryRestrictions: []
    };
  }

  private getDefaultPricingTerms(): PricingTerms {
    return {
      model: 'per_request',
      baseRate: 0.001,
      currency: 'USD',
      billing: 'postpaid',
      volumeDiscounts: [],
      paymentTerms: 30
    };
  }

  private getDefaultDuration(): LicenseDuration {
    return {
      startDate: new Date(),
      autoRenewal: false,
      noticePeriod: 30
    };
  }

  private getDefaultCompliance(): ComplianceRequirements {
    return {
      dataProtection: ['GDPR'],
      industryStandards: [],
      auditRequirements: false,
      reportingFrequency: 'monthly',
      retentionPeriod: 365
    };
  }

  private getCurrentUsage(licenseId: string): { requestCount: number; tokenCount: number } {
    const usage = this.usageTracking.get(licenseId) || [];
    return {
      requestCount: usage.reduce((sum, u) => sum + u.usage.requestCount, 0),
      tokenCount: usage.reduce((sum, u) => sum + u.usage.tokenCount, 0)
    };
  }

  private checkRestriction(restriction: LicenseRestriction, requestedUsage: Partial<UsageRights>): boolean {
    // Simplified restriction checking - would be more complex in production
    return false;
  }

  private async checkForViolations(licenseId: string, usage: LicenseUsage): Promise<void> {
    const license = this.licenses.get(licenseId);
    if (!license) return;

    const violations: LicenseViolation[] = [];

    // Check usage limits
    const currentUsage = this.getCurrentUsage(licenseId);
    if (license.usage.maxRequests && currentUsage.requestCount > license.usage.maxRequests) {
      violations.push({
        licenseId,
        violationType: 'usage_exceeded',
        description: 'Request limit exceeded',
        severity: 'high',
        detectedAt: new Date(),
        evidence: { limit: license.usage.maxRequests, actual: currentUsage.requestCount },
        resolved: false,
        action: 'block'
      });
    }

    if (violations.length > 0) {
      const existingViolations = this.violations.get(licenseId) || [];
      this.violations.set(licenseId, [...existingViolations, ...violations]);
      
      for (const violation of violations) {
        this.emit('violationDetected', { violation });
      }
    }
  }

  private findPeakUsage(usage: LicenseUsage[]): Date {
    if (usage.length === 0) return new Date();
    
    return usage.reduce((peak, current) => 
      current.usage.requestCount > peak.usage.requestCount ? current : peak
    ).timestamp;
  }

  private getComplianceStatus(licenseId: string): string {
    const violations = this.violations.get(licenseId) || [];
    const unresolvedViolations = violations.filter(v => !v.resolved);
    
    if (unresolvedViolations.length === 0) {
      return 'compliant';
    } else if (unresolvedViolations.some(v => v.severity === 'critical')) {
      return 'critical_violations';
    } else {
      return 'minor_violations';
    }
  }
}

export default ContentLicensingService;
export { 
  LicenseTerms, 
  UsageRights, 
  PricingTerms, 
  LicenseUsage, 
  LicenseViolation,
  LicenseAgreement,
  ComplianceRequirements 
};
