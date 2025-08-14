/**
 * License Management API Routes
 * RESTful endpoints for license operations
 */

const express = require('express');
const router = express.Router();
const { body, query, validationResult } = require('express-validator');
const LicenseService = require('../../services/license-service');
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);
const rateLimit = require('express-rate-limit');

// Initialize license service
let licenseService;

// Rate limiters for different endpoints
const generateLimiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 10, // 10 requests per window
    message: 'Too many license generation requests'
});

const validateLimiter = rateLimit({
    windowMs: 1 * 60 * 1000, // 1 minute
    max: 60, // 60 requests per minute
    message: 'Too many validation requests'
});

const heartbeatLimiter = rateLimit({
    windowMs: 1 * 60 * 1000, // 1 minute
    max: 120, // 120 requests per minute
    message: 'Too many heartbeat requests'
});

/**
 * Initialize license service with database connection
 */
router.initialize = (dbPool) => {
    licenseService = new LicenseService(dbPool);
};

/**
 * Middleware to check API authentication
 */
const authenticateAPI = async (req, res, next) => {
    const apiKey = req.headers['x-api-key'];
    
    if (!apiKey) {
        return res.status(401).json({
            success: false,
            error: 'API key required'
        });
    }

    // Validate API key (implement your validation logic)
    // For now, we'll check against environment variable
    if (apiKey !== process.env.ADMIN_API_KEY) {
        return res.status(403).json({
            success: false,
            error: 'Invalid API key'
        });
    }

    next();
};

/**
 * POST /api/licenses/generate
 * Generate a new license key
 */
router.post('/generate',
    authenticateAPI,
    generateLimiter,
    [
        body('customerEmail').isEmail().normalizeEmail(),
        body('customerName').optional().trim(),
        body('tier').isIn(['free', 'pro', 'business', 'enterprise']).optional(),
        body('validDays').optional().isInt({ min: 1, max: 3650 }),
        body('trialDays').optional().isInt({ min: 1, max: 90 }),
        body('maxActivations').optional().isInt({ min: 1, max: 100 }),
        body('price').optional().isFloat({ min: 0 }),
        body('stripeSubscriptionId').optional().trim()
    ],
    async (req, res) => {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    errors: errors.array()
                });
            }

            const result = await licenseService.generateLicense(req.body);
            
            // Send license key via email if requested
            if (req.body.sendEmail) {
                // Implement email sending logic
                await sendLicenseEmail(req.body.customerEmail, result.license);
            }

            res.json(result);
        } catch (error) {
            console.error('License generation error:', error);
            res.status(500).json({
                success: false,
                error: 'Failed to generate license'
            });
        }
    }
);

/**
 * POST /api/licenses/validate
 * Validate a license key
 */
router.post('/validate',
    validateLimiter,
    [
        body('licenseKey').notEmpty().trim(),
        body('siteUrl').notEmpty().trim().isURL(),
        body('autoActivate').optional().isBoolean(),
        body('wordpressVersion').optional().trim(),
        body('pluginVersion').optional().trim(),
        body('phpVersion').optional().trim()
    ],
    async (req, res) => {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    errors: errors.array()
                });
            }

            const { licenseKey, siteUrl, ...options } = req.body;
            options.ipAddress = req.ip;
            options.userAgent = req.get('user-agent');

            const result = await licenseService.validateLicense(
                licenseKey,
                siteUrl,
                options
            );

            res.json(result);
        } catch (error) {
            console.error('License validation error:', error);
            res.status(500).json({
                success: false,
                error: 'Failed to validate license'
            });
        }
    }
);

/**
 * POST /api/licenses/activate
 * Activate a license for a site
 */
router.post('/activate',
    [
        body('licenseKey').notEmpty().trim(),
        body('siteUrl').notEmpty().trim().isURL(),
        body('machineId').optional().trim(),
        body('wordpressVersion').optional().trim(),
        body('pluginVersion').optional().trim(),
        body('phpVersion').optional().trim()
    ],
    async (req, res) => {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    errors: errors.array()
                });
            }

            const { licenseKey, siteUrl, ...options } = req.body;
            
            // First validate the license
            const validation = await licenseService.validateLicense(
                licenseKey,
                siteUrl,
                { skipActivation: true }
            );

            if (!validation.valid && validation.status !== 'max_activations') {
                return res.status(400).json({
                    success: false,
                    error: validation.message,
                    status: validation.status
                });
            }

            // Get license ID
            const licenseResult = await licenseService.db.query(
                'SELECT id FROM licenses WHERE license_key = $1',
                [licenseKey]
            );

            if (licenseResult.rows.length === 0) {
                return res.status(404).json({
                    success: false,
                    error: 'License not found'
                });
            }

            // Activate the license
            options.ipAddress = req.ip;
            options.userAgent = req.get('user-agent');
            
            const activation = await licenseService.activateLicense(
                licenseResult.rows[0].id,
                siteUrl,
                options
            );

            res.json({
                success: true,
                activationToken: activation.activation_token,
                message: 'License activated successfully'
            });
        } catch (error) {
            console.error('License activation error:', error);
            res.status(500).json({
                success: false,
                error: 'Failed to activate license'
            });
        }
    }
);

/**
 * POST /api/licenses/deactivate
 * Deactivate a license
 */
router.post('/deactivate',
    [
        body('licenseKey').notEmpty().trim(),
        body('siteUrl').notEmpty().trim().isURL()
    ],
    async (req, res) => {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    errors: errors.array()
                });
            }

            const result = await licenseService.deactivateLicense(
                req.body.licenseKey,
                req.body.siteUrl
            );

            res.json(result);
        } catch (error) {
            console.error('License deactivation error:', error);
            res.status(500).json({
                success: false,
                error: 'Failed to deactivate license'
            });
        }
    }
);

/**
 * POST /api/licenses/heartbeat
 * License heartbeat check
 */
router.post('/heartbeat',
    heartbeatLimiter,
    [
        body('activationToken').notEmpty().trim()
    ],
    async (req, res) => {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    errors: errors.array()
                });
            }

            const result = await licenseService.heartbeat(req.body.activationToken);
            res.json(result);
        } catch (error) {
            console.error('Heartbeat error:', error);
            res.status(500).json({
                success: false,
                error: 'Heartbeat failed'
            });
        }
    }
);

/**
 * POST /api/licenses/transfer
 * Transfer license to another site
 */
router.post('/transfer',
    [
        body('licenseKey').notEmpty().trim(),
        body('fromSiteUrl').notEmpty().trim().isURL(),
        body('toSiteUrl').notEmpty().trim().isURL(),
        body('reason').optional().trim()
    ],
    async (req, res) => {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    errors: errors.array()
                });
            }

            const result = await licenseService.transferLicense(
                req.body.licenseKey,
                req.body.fromSiteUrl,
                req.body.toSiteUrl,
                req.body.reason
            );

            res.json(result);
        } catch (error) {
            console.error('License transfer error:', error);
            res.status(500).json({
                success: false,
                error: 'Failed to transfer license'
            });
        }
    }
);

/**
 * GET /api/licenses/stats
 * Get license statistics
 */
router.get('/stats',
    [
        query('licenseKey').notEmpty().trim()
    ],
    async (req, res) => {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    errors: errors.array()
                });
            }

            const stats = await licenseService.getLicenseStats(req.query.licenseKey);
            
            if (!stats) {
                return res.status(404).json({
                    success: false,
                    error: 'License not found'
                });
            }

            res.json({
                success: true,
                stats
            });
        } catch (error) {
            console.error('Stats error:', error);
            res.status(500).json({
                success: false,
                error: 'Failed to get license stats'
            });
        }
    }
);

/**
 * GET /api/licenses/public-key
 * Get public key for offline validation
 */
router.get('/public-key',
    [
        query('version').optional().isInt()
    ],
    async (req, res) => {
        try {
            const publicKey = await licenseService.getPublicKey(req.query.version);
            
            if (!publicKey) {
                return res.status(404).json({
                    success: false,
                    error: 'Public key not found'
                });
            }

            res.json({
                success: true,
                publicKey,
                algorithm: 'RS256'
            });
        } catch (error) {
            console.error('Public key error:', error);
            res.status(500).json({
                success: false,
                error: 'Failed to get public key'
            });
        }
    }
);

/**
 * POST /api/licenses/webhook/stripe
 * Handle Stripe webhooks for subscription events
 */
router.post('/webhook/stripe',
    express.raw({ type: 'application/json' }),
    async (req, res) => {
        const sig = req.headers['stripe-signature'];
        let event;

        try {
            event = stripe.webhooks.constructEvent(
                req.body,
                sig,
                process.env.STRIPE_WEBHOOK_SECRET
            );
        } catch (err) {
            console.error('Webhook signature verification failed:', err);
            return res.status(400).send(`Webhook Error: ${err.message}`);
        }

        try {
            switch (event.type) {
                case 'customer.subscription.created':
                    await handleSubscriptionCreated(event.data.object);
                    break;
                    
                case 'customer.subscription.updated':
                    await handleSubscriptionUpdated(event.data.object);
                    break;
                    
                case 'customer.subscription.deleted':
                    await handleSubscriptionDeleted(event.data.object);
                    break;
                    
                case 'invoice.payment_succeeded':
                    await handlePaymentSucceeded(event.data.object);
                    break;
                    
                case 'invoice.payment_failed':
                    await handlePaymentFailed(event.data.object);
                    break;
                    
                default:
                    console.log(`Unhandled event type: ${event.type}`);
            }

            res.json({ received: true });
        } catch (error) {
            console.error('Webhook processing error:', error);
            res.status(500).json({ error: 'Webhook processing failed' });
        }
    }
);

/**
 * Handle subscription created
 */
async function handleSubscriptionCreated(subscription) {
    const tier = mapStripePriceToTier(subscription.items.data[0].price.id);
    
    // Generate license for new subscription
    await licenseService.generateLicense({
        customerEmail: subscription.customer_email,
        tier,
        stripeSubscriptionId: subscription.id,
        stripeCustomerId: subscription.customer,
        validDays: 365, // Annual subscription
        maxActivations: tier === 'business' ? 5 : 1
    });
}

/**
 * Handle subscription updated
 */
async function handleSubscriptionUpdated(subscription) {
    // Update license tier if plan changed
    const tier = mapStripePriceToTier(subscription.items.data[0].price.id);
    
    await licenseService.db.query(
        `UPDATE licenses 
         SET tier = $1, updated_at = NOW()
         WHERE stripe_subscription_id = $2`,
        [tier, subscription.id]
    );
}

/**
 * Handle subscription deleted
 */
async function handleSubscriptionDeleted(subscription) {
    // Suspend license when subscription is cancelled
    await licenseService.db.query(
        `UPDATE licenses 
         SET status = 'suspended', updated_at = NOW()
         WHERE stripe_subscription_id = $1`,
        [subscription.id]
    );
}

/**
 * Handle payment succeeded
 */
async function handlePaymentSucceeded(invoice) {
    // Reactivate license if it was suspended
    await licenseService.db.query(
        `UPDATE licenses 
         SET status = 'active', updated_at = NOW()
         WHERE stripe_subscription_id = $1 AND status = 'suspended'`,
        [invoice.subscription]
    );
}

/**
 * Handle payment failed
 */
async function handlePaymentFailed(invoice) {
    // Set grace period for failed payment
    const gracePeriodEnd = new Date();
    gracePeriodEnd.setDate(gracePeriodEnd.getDate() + 7);
    
    await licenseService.db.query(
        `UPDATE licenses 
         SET grace_period_ends_at = $1, updated_at = NOW()
         WHERE stripe_subscription_id = $2`,
        [gracePeriodEnd, invoice.subscription]
    );
}

/**
 * Map Stripe price ID to license tier
 */
function mapStripePriceToTier(priceId) {
    const priceMap = {
        [process.env.STRIPE_PRICE_PRO]: 'pro',
        [process.env.STRIPE_PRICE_BUSINESS]: 'business',
        [process.env.STRIPE_PRICE_ENTERPRISE]: 'enterprise'
    };
    
    return priceMap[priceId] || 'free';
}

/**
 * Send license email (implement your email service)
 */
async function sendLicenseEmail(email, license) {
    // Implement email sending logic
    console.log(`Sending license to ${email}:`, license.key);
}

module.exports = router;
