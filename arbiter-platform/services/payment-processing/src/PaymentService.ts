/**
 * Arbiter Platform - Payment Processing Service
 * Handles all payment transactions, subscriptions, and financial operations
 * Supports multiple payment providers and currencies
 */

import { EventEmitter } from 'events';

interface PaymentProvider {
  providerId: string;
  name: string;
  type: 'stripe' | 'paypal' | 'bank_transfer' | 'crypto' | 'apple_pay' | 'google_pay';
  config: {
    apiKey: string;
    secretKey: string;
    webhookSecret: string;
    environment: 'sandbox' | 'production';
  };
  supportedCurrencies: string[];
  fees: {
    percentage: number;
    fixed: number;
    currency: string;
  };
  active: boolean;
}

interface PaymentAccount {
  accountId: string;
  userId: string;
  userType: 'publisher' | 'ai_company';
  provider: string;
  externalAccountId: string;
  status: 'pending' | 'verified' | 'suspended' | 'closed';
  capabilities: {
    canReceive: boolean;
    canSend: boolean;
    instantTransfer: boolean;
  };
  metadata: {
    createdAt: Date;
    lastVerified: Date;
    kycStatus: 'pending' | 'approved' | 'rejected';
  };
}

interface Transaction {
  transactionId: string;
  type: 'payment' | 'payout' | 'refund' | 'fee' | 'adjustment';
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled';
  amount: {
    gross: number;
    net: number;
    fees: number;
    currency: string;
  };
  parties: {
    payer: {
      id: string;
      type: 'publisher' | 'ai_company' | 'platform';
      accountId: string;
    };
    payee: {
      id: string;
      type: 'publisher' | 'ai_company' | 'platform';
      accountId: string;
    };
  };
  description: string;
  metadata: {
    licenseId?: string;
    contentId?: string;
    subscriptionId?: string;
    invoiceId?: string;
  };
  provider: {
    name: string;
    transactionId: string;
    fees: number;
  };
  timeline: {
    createdAt: Date;
    processedAt?: Date;
    completedAt?: Date;
    failedAt?: Date;
  };
}

interface Subscription {
  subscriptionId: string;
  customerId: string;
  planId: string;
  status: 'active' | 'past_due' | 'cancelled' | 'paused';
  billing: {
    interval: 'daily' | 'weekly' | 'monthly' | 'yearly';
    amount: number;
    currency: string;
    nextBilling: Date;
    lastBilled?: Date;
  };
  trial: {
    active: boolean;
    endsAt?: Date;
  };
  usage: {
    current: number;
    limit: number;
    resetDate: Date;
  };
  createdAt: Date;
  updatedAt: Date;
}

interface Invoice {
  invoiceId: string;
  customerId: string;
  subscriptionId?: string;
  status: 'draft' | 'open' | 'paid' | 'void' | 'uncollectible';
  amount: {
    subtotal: number;
    tax: number;
    total: number;
    currency: string;
  };
  lineItems: InvoiceLineItem[];
  dueDate: Date;
  paidAt?: Date;
  createdAt: Date;
}

interface InvoiceLineItem {
  description: string;
  quantity: number;
  unitPrice: number;
  total: number;
  metadata?: Record<string, any>;
}

interface PaymentMethod {
  methodId: string;
  customerId: string;
  type: 'card' | 'bank_account' | 'digital_wallet' | 'crypto';
  details: {
    last4?: string;
    brand?: string;
    expiryMonth?: number;
    expiryYear?: number;
    country?: string;
  };
  isDefault: boolean;
  status: 'active' | 'inactive' | 'expired';
  createdAt: Date;
}

interface Payout {
  payoutId: string;
  accountId: string;
  amount: {
    gross: number;
    fees: number;
    net: number;
    currency: string;
  };
  status: 'pending' | 'in_transit' | 'paid' | 'failed' | 'cancelled';
  method: 'bank_transfer' | 'card' | 'digital_wallet';
  transactions: string[];
  scheduledFor: Date;
  completedAt?: Date;
  metadata: {
    period: {
      start: Date;
      end: Date;
    };
    transactionCount: number;
  };
}

class PaymentProcessingService extends EventEmitter {
  private providers: Map<string, PaymentProvider>;
  private accounts: Map<string, PaymentAccount>;
  private transactions: Map<string, Transaction>;
  private subscriptions: Map<string, Subscription>;
  private invoices: Map<string, Invoice>;
  private paymentMethods: Map<string, PaymentMethod>;
  private payouts: Map<string, Payout>;

  constructor() {
    super();
    this.providers = new Map();
    this.accounts = new Map();
    this.transactions = new Map();
    this.subscriptions = new Map();
    this.invoices = new Map();
    this.paymentMethods = new Map();
    this.payouts = new Map();
    
    this.initializeProviders();
  }

  async processPayment(request: {
    amount: number;
    currency: string;
    payerId: string;
    payeeId: string;
    description: string;
    metadata?: Record<string, any>;
    paymentMethodId?: string;
  }): Promise<Transaction> {
    try {
      const transactionId = this.generateTransactionId();
      
      // Validate accounts
      const payerAccount = this.getAccountByUserId(request.payerId);
      const payeeAccount = this.getAccountByUserId(request.payeeId);
      
      if (!payerAccount || !payeeAccount) {
        throw new Error('Invalid payer or payee account');
      }

      // Calculate fees
      const provider = this.providers.get('stripe')!; // Default to Stripe
      const grossAmount = request.amount;
      const fees = this.calculateFees(grossAmount, provider);
      const netAmount = grossAmount - fees;

      // Create transaction
      const transaction: Transaction = {
        transactionId,
        type: 'payment',
        status: 'pending',
        amount: {
          gross: grossAmount,
          net: netAmount,
          fees: fees,
          currency: request.currency
        },
        parties: {
          payer: {
            id: request.payerId,
            type: payerAccount.userType,
            accountId: payerAccount.accountId
          },
          payee: {
            id: request.payeeId,
            type: payeeAccount.userType,
            accountId: payeeAccount.accountId
          }
        },
        description: request.description,
        metadata: request.metadata || {},
        provider: {
          name: provider.name,
          transactionId: `${provider.name}_${Date.now()}`,
          fees: fees
        },
        timeline: {
          createdAt: new Date()
        }
      };

      this.transactions.set(transactionId, transaction);

      // Process with payment provider
      const providerResult = await this.processWithProvider(transaction, provider);
      
      if (providerResult.success) {
        transaction.status = 'completed';
        transaction.timeline.completedAt = new Date();
        transaction.provider.transactionId = providerResult.externalId;
      } else {
        transaction.status = 'failed';
        transaction.timeline.failedAt = new Date();
      }

      this.transactions.set(transactionId, transaction);
      this.emit('paymentProcessed', { transaction });

      return transaction;

    } catch (error) {
      console.error('Payment processing error:', error);
      throw new Error(`Payment failed: ${error.message}`);
    }
  }

  async createSubscription(request: {
    customerId: string;
    planId: string;
    paymentMethodId: string;
    trialDays?: number;
  }): Promise<Subscription> {
    const subscriptionId = this.generateSubscriptionId();
    
    const subscription: Subscription = {
      subscriptionId,
      customerId: request.customerId,
      planId: request.planId,
      status: 'active',
      billing: {
        interval: 'monthly', // Would come from plan
        amount: 99.00, // Would come from plan
        currency: 'USD',
        nextBilling: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) // 30 days
      },
      trial: {
        active: !!request.trialDays,
        endsAt: request.trialDays ? new Date(Date.now() + request.trialDays * 24 * 60 * 60 * 1000) : undefined
      },
      usage: {
        current: 0,
        limit: 10000, // Would come from plan
        resetDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
      },
      createdAt: new Date(),
      updatedAt: new Date()
    };

    this.subscriptions.set(subscriptionId, subscription);
    this.emit('subscriptionCreated', { subscription });

    return subscription;
  }

  async cancelSubscription(subscriptionId: string, reason?: string): Promise<void> {
    const subscription = this.subscriptions.get(subscriptionId);
    if (!subscription) {
      throw new Error(`Subscription ${subscriptionId} not found`);
    }

    subscription.status = 'cancelled';
    subscription.updatedAt = new Date();
    
    this.subscriptions.set(subscriptionId, subscription);
    this.emit('subscriptionCancelled', { subscription, reason });
  }

  async createInvoice(customerId: string, lineItems: InvoiceLineItem[]): Promise<Invoice> {
    const invoiceId = this.generateInvoiceId();
    
    const subtotal = lineItems.reduce((sum, item) => sum + item.total, 0);
    const tax = subtotal * 0.08; // 8% tax rate
    const total = subtotal + tax;

    const invoice: Invoice = {
      invoiceId,
      customerId,
      status: 'open',
      amount: {
        subtotal,
        tax,
        total,
        currency: 'USD'
      },
      lineItems,
      dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), // 30 days
      createdAt: new Date()
    };

    this.invoices.set(invoiceId, invoice);
    this.emit('invoiceCreated', { invoice });

    return invoice;
  }

  async payInvoice(invoiceId: string, paymentMethodId: string): Promise<Transaction> {
    const invoice = this.invoices.get(invoiceId);
    if (!invoice) {
      throw new Error(`Invoice ${invoiceId} not found`);
    }

    if (invoice.status !== 'open') {
      throw new Error('Invoice is not payable');
    }

    // Process payment
    const transaction = await this.processPayment({
      amount: invoice.amount.total,
      currency: invoice.amount.currency,
      payerId: invoice.customerId,
      payeeId: 'platform', // Platform receives payment
      description: `Payment for invoice ${invoiceId}`,
      metadata: { invoiceId },
      paymentMethodId
    });

    if (transaction.status === 'completed') {
      invoice.status = 'paid';
      invoice.paidAt = new Date();
      this.invoices.set(invoiceId, invoice);
    }

    return transaction;
  }

  async addPaymentMethod(customerId: string, methodData: {
    type: 'card' | 'bank_account';
    token: string;
    isDefault?: boolean;
  }): Promise<PaymentMethod> {
    const methodId = this.generatePaymentMethodId();
    
    const paymentMethod: PaymentMethod = {
      methodId,
      customerId,
      type: methodData.type,
      details: {
        last4: '4242', // Would come from tokenized data
        brand: 'visa',
        expiryMonth: 12,
        expiryYear: 2028,
        country: 'US'
      },
      isDefault: methodData.isDefault || false,
      status: 'active',
      createdAt: new Date()
    };

    this.paymentMethods.set(methodId, paymentMethod);
    this.emit('paymentMethodAdded', { paymentMethod });

    return paymentMethod;
  }

  async createPayout(accountId: string, amount: number): Promise<Payout> {
    const payoutId = this.generatePayoutId();
    const fees = amount * 0.01; // 1% payout fee
    const netAmount = amount - fees;

    const payout: Payout = {
      payoutId,
      accountId,
      amount: {
        gross: amount,
        fees: fees,
        net: netAmount,
        currency: 'USD'
      },
      status: 'pending',
      method: 'bank_transfer',
      transactions: [],
      scheduledFor: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000), // 2 days
      metadata: {
        period: {
          start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000),
          end: new Date()
        },
        transactionCount: 0
      }
    };

    this.payouts.set(payoutId, payout);
    this.emit('payoutCreated', { payout });

    return payout;
  }

  async getAccountBalance(accountId: string): Promise<{
    available: number;
    pending: number;
    total: number;
    currency: string;
  }> {
    const transactions = Array.from(this.transactions.values())
      .filter(t => t.parties.payee.accountId === accountId && t.status === 'completed');

    const totalEarnings = transactions.reduce((sum, t) => sum + t.amount.net, 0);
    const pendingPayouts = Array.from(this.payouts.values())
      .filter(p => p.accountId === accountId && p.status === 'pending')
      .reduce((sum, p) => sum + p.amount.gross, 0);

    return {
      available: totalEarnings - pendingPayouts,
      pending: pendingPayouts,
      total: totalEarnings,
      currency: 'USD'
    };
  }

  async getTransactionHistory(accountId: string, limit: number = 50): Promise<Transaction[]> {
    return Array.from(this.transactions.values())
      .filter(t => 
        t.parties.payer.accountId === accountId || 
        t.parties.payee.accountId === accountId
      )
      .sort((a, b) => b.timeline.createdAt.getTime() - a.timeline.createdAt.getTime())
      .slice(0, limit);
  }

  async processRefund(transactionId: string, amount?: number, reason?: string): Promise<Transaction> {
    const originalTransaction = this.transactions.get(transactionId);
    if (!originalTransaction) {
      throw new Error(`Transaction ${transactionId} not found`);
    }

    if (originalTransaction.status !== 'completed') {
      throw new Error('Can only refund completed transactions');
    }

    const refundAmount = amount || originalTransaction.amount.gross;
    const refundId = this.generateTransactionId();

    const refund: Transaction = {
      transactionId: refundId,
      type: 'refund',
      status: 'completed',
      amount: {
        gross: -refundAmount,
        net: -refundAmount,
        fees: 0,
        currency: originalTransaction.amount.currency
      },
      parties: {
        payer: originalTransaction.parties.payee,
        payee: originalTransaction.parties.payer
      },
      description: `Refund for ${transactionId}: ${reason || 'Customer refund'}`,
      metadata: { originalTransactionId: transactionId },
      provider: originalTransaction.provider,
      timeline: {
        createdAt: new Date(),
        completedAt: new Date()
      }
    };

    this.transactions.set(refundId, refund);
    this.emit('refundProcessed', { refund, originalTransaction });

    return refund;
  }

  private calculateFees(amount: number, provider: PaymentProvider): number {
    return (amount * provider.fees.percentage / 100) + provider.fees.fixed;
  }

  private async processWithProvider(transaction: Transaction, provider: PaymentProvider): Promise<{
    success: boolean;
    externalId?: string;
    error?: string;
  }> {
    // Simulate payment processing
    await new Promise(resolve => setTimeout(resolve, 1000));

    // 95% success rate simulation
    if (Math.random() > 0.05) {
      return {
        success: true,
        externalId: `${provider.type}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
      };
    } else {
      return {
        success: false,
        error: 'Payment declined by provider'
      };
    }
  }

  private getAccountByUserId(userId: string): PaymentAccount | undefined {
    return Array.from(this.accounts.values()).find(acc => acc.userId === userId);
  }

  private initializeProviders(): void {
    // Stripe provider
    const stripeProvider: PaymentProvider = {
      providerId: 'stripe',
      name: 'Stripe',
      type: 'stripe',
      config: {
        apiKey: 'pk_test_demo',
        secretKey: 'sk_test_demo',
        webhookSecret: 'whsec_demo',
        environment: 'sandbox'
      },
      supportedCurrencies: ['USD', 'EUR', 'GBP', 'CAD'],
      fees: {
        percentage: 2.9,
        fixed: 0.30,
        currency: 'USD'
      },
      active: true
    };

    this.providers.set('stripe', stripeProvider);

    // Create demo accounts
    const publisherAccount: PaymentAccount = {
      accountId: 'acc_publisher_demo',
      userId: 'publisher_demo',
      userType: 'publisher',
      provider: 'stripe',
      externalAccountId: 'acct_demo_publisher',
      status: 'verified',
      capabilities: {
        canReceive: true,
        canSend: false,
        instantTransfer: true
      },
      metadata: {
        createdAt: new Date(),
        lastVerified: new Date(),
        kycStatus: 'approved'
      }
    };

    const aiCompanyAccount: PaymentAccount = {
      accountId: 'acc_ai_company_demo',
      userId: 'ai_company_demo',
      userType: 'ai_company',
      provider: 'stripe',
      externalAccountId: 'acct_demo_ai_company',
      status: 'verified',
      capabilities: {
        canReceive: false,
        canSend: true,
        instantTransfer: true
      },
      metadata: {
        createdAt: new Date(),
        lastVerified: new Date(),
        kycStatus: 'approved'
      }
    };

    this.accounts.set(publisherAccount.accountId, publisherAccount);
    this.accounts.set(aiCompanyAccount.accountId, aiCompanyAccount);
  }

  private generateTransactionId(): string {
    return `txn_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateSubscriptionId(): string {
    return `sub_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generateInvoiceId(): string {
    return `inv_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generatePaymentMethodId(): string {
    return `pm_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  private generatePayoutId(): string {
    return `po_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
}

export default PaymentProcessingService;
export {
  Transaction,
  Subscription,
  Invoice,
  PaymentMethod,
  PaymentAccount,
  Payout
};
