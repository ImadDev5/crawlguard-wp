/**
 * License Management Service
 * Handles all license operations including generation, validation, activation, and management
 */

const crypto = require('crypto');
const { Pool } = require('pg');
const jwt = require('jsonwebtoken');
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);
const NodeRSA = require('node-rsa');

class LicenseService {
    constructor(dbPool) {
        this.db = dbPool;
        this.rsaKeys = new Map(); // Cache RSA keys
        this.initializeRSAKeys();
    }

    /**
     * Initialize RSA keys for offline validation
     */
    async initializeRSAKeys() {
        try {
            const result = await this.db.query(
                'SELECT * FROM license_keys_rsa WHERE status = $1 ORDER BY version DESC LIMIT 1',
                ['active']
            );

            if (result.rows.length === 0) {
                // Generate initial RSA key pair
                await this.generateRSAKeyPair();
            } else {
                // Load existing keys
                const keyData = result.rows[0];
                const key = new NodeRSA();
                key.importKey(keyData.private_key_encrypted, 'pkcs1-private-pem');
                this.rsaKeys.set(keyData.version, key);
            }
        } catch (error) {
            console.error('Failed to initialize RSA keys:', error);
        }
    }

    /**
     * Generate new RSA key pair for license signing
     */
    async generateRSAKeyPair() {
        const key = new NodeRSA({ b: 2048 });
        const publicKey = key.exportKey('pkcs1-public-pem');
        const privateKey = key.exportKey('pkcs1-private-pem');
        
        // Encrypt private key before storage
        const encryptedPrivateKey = this.encryptData(privateKey);
        
        const result = await this.db.query(
            `INSERT INTO license_keys_rsa (version, public_key, private_key_encrypted, key_size)
             VALUES ((SELECT COALESCE(MAX(version), 0) + 1 FROM license_keys_rsa), $1, $2, $3)
             RETURNING *`,
            [publicKey, encryptedPrivateKey, 2048]
        );

        const keyData = result.rows[0];
        this.rsaKeys.set(keyData.version, key);
        
        return keyData;
    }

    /**
     * Generate a new license key
     */
    async generateLicense(data) {
        const client = await this.db.connect();
        
        try {
            await client.query('BEGIN');

            // Generate license key components
            const uuid = crypto.randomUUID().replace(/-/g, '').toUpperCase();
            const licenseKey = [
                uuid.substring(0, 4),
                uuid.substring(4, 8),
                uuid.substring(8, 12),
                uuid.substring(12, 16)
            ].join('-');
            
            // Generate checksum
            const checksum = this.generateChecksum(licenseKey);
            
            // Hash license for secure storage
            const licenseHash = crypto
                .createHash('sha256')
                .update(licenseKey)
                .digest('hex');

            // Get features for tier
            const featuresResult = await client.query(
                `SELECT feature_key, feature_value, quota_limit, quota_period 
                 FROM license_features 
                 WHERE tier = $1 AND enabled = true`,
                [data.tier || 'free']
            );

            const features = {};
            featuresResult.rows.forEach(row => {
                features[row.feature_key] = {
                    enabled: row.feature_value,
                    limit: row.quota_limit,
                    period: row.quota_period
                };
            });

            // Calculate expiration dates
            const now = new Date();
            const validUntil = data.validDays 
                ? new Date(now.getTime() + (data.validDays * 24 * 60 * 60 * 1000))
                : null;
            
            const trialEndsAt = data.trialDays
                ? new Date(now.getTime() + (data.trialDays * 24 * 60 * 60 * 1000))
                : null;

            const gracePeriodEndsAt = validUntil
                ? new Date(validUntil.getTime() + (7 * 24 * 60 * 60 * 1000)) // 7 days grace
                : null;

            // Create license signature
            const licenseData = {
                key: licenseKey,
                email: data.customerEmail,
                tier: data.tier || 'free',
                validUntil: validUntil?.toISOString(),
                features
            };

            const activeKey = Array.from(this.rsaKeys.values())[0];
            const signature = activeKey.sign(JSON.stringify(licenseData), 'base64');

            // Insert license
            const insertResult = await client.query(
                `INSERT INTO licenses (
                    license_key, license_hash, checksum, customer_email, customer_name,
                    tier, price, currency, status, max_activations,
                    valid_until, trial_ends_at, grace_period_ends_at,
                    stripe_customer_id, stripe_subscription_id,
                    signature, public_key_version, features, metadata
                ) VALUES (
                    $1, $2, $3, $4, $5, $6, $7, $8, $9, $10,
                    $11, $12, $13, $14, $15, $16, $17, $18, $19
                ) RETURNING *`,
                [
                    licenseKey,
                    licenseHash,
                    checksum,
                    data.customerEmail,
                    data.customerName,
                    data.tier || 'free',
                    data.price || 0,
                    data.currency || 'USD',
                    'inactive',
                    data.maxActivations || 1,
                    validUntil,
                    trialEndsAt,
                    gracePeriodEndsAt,
                    data.stripeCustomerId,
                    data.stripeSubscriptionId,
                    signature,
                    Array.from(this.rsaKeys.keys())[0],
                    JSON.stringify(features),
                    JSON.stringify(data.metadata || {})
                ]
            );

            await client.query('COMMIT');
            
            return {
                success: true,
                license: {
                    key: licenseKey,
                    checksum,
                    tier: data.tier || 'free',
                    validUntil,
                    trialEndsAt,
                    features,
                    publicKey: await this.getPublicKey()
                }
            };
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    }

    /**
     * Validate a license key
     */
    async validateLicense(licenseKey, siteUrl, options = {}) {
        const client = await this.db.connect();
        
        try {
            const startTime = Date.now();
            
            // Check rate limiting
            const rateLimited = await this.checkRateLimit(licenseKey, 'validate');
            if (rateLimited) {
                return {
                    valid: false,
                    status: 'rate_limited',
                    message: 'Too many validation requests'
                };
            }

            // Get license details
            const licenseResult = await client.query(
                'SELECT * FROM licenses WHERE license_key = $1',
                [licenseKey]
            );

            if (licenseResult.rows.length === 0) {
                await this.logValidation(null, null, 'online', 'invalid', 
                    'License key not found', siteUrl, Date.now() - startTime);
                
                return {
                    valid: false,
                    status: 'invalid',
                    message: 'Invalid license key'
                };
            }

            const license = licenseResult.rows[0];

            // Check license status
            if (license.status === 'revoked') {
                await this.logValidation(license.id, null, 'online', 'revoked',
                    'License has been revoked', siteUrl, Date.now() - startTime);
                
                return {
                    valid: false,
                    status: 'revoked',
                    message: 'License has been revoked',
                    revokeReason: license.revoke_reason
                };
            }

            if (license.status === 'suspended') {
                await this.logValidation(license.id, null, 'online', 'suspended',
                    'License is suspended', siteUrl, Date.now() - startTime);
                
                return {
                    valid: false,
                    status: 'suspended',
                    message: 'License is suspended'
                };
            }

            // Check expiration
            const now = new Date();
            if (license.valid_until && new Date(license.valid_until) < now) {
                // Check grace period
                if (license.grace_period_ends_at && 
                    new Date(license.grace_period_ends_at) > now) {
                    
                    return {
                        valid: true,
                        status: 'grace_period',
                        message: 'License in grace period',
                        gracePeriodEnds: license.grace_period_ends_at,
                        tier: license.tier,
                        features: license.features
                    };
                }

                await this.logValidation(license.id, null, 'online', 'expired',
                    'License has expired', siteUrl, Date.now() - startTime);
                
                return {
                    valid: false,
                    status: 'expired',
                    message: 'License has expired',
                    expiredAt: license.valid_until
                };
            }

            // Check activation status
            const activationResult = await client.query(
                `SELECT * FROM license_activations 
                 WHERE license_id = $1 AND site_url = $2 AND status = 'active'`,
                [license.id, siteUrl]
            );

            let activation = activationResult.rows[0];

            if (!activation && !options.skipActivation) {
                // Check if we can create new activation
                if (license.activation_count >= license.max_activations) {
                    await this.logValidation(license.id, null, 'online', 'max_activations',
                        'Maximum activations reached', siteUrl, Date.now() - startTime);
                    
                    return {
                        valid: false,
                        status: 'max_activations',
                        message: 'Maximum activations reached',
                        currentActivations: license.activation_count,
                        maxActivations: license.max_activations
                    };
                }

                // Auto-activate if requested
                if (options.autoActivate) {
                    activation = await this.activateLicense(license.id, siteUrl, options);
                }
            }

            // Update heartbeat if activation exists
            if (activation) {
                await client.query(
                    `UPDATE license_activations 
                     SET last_heartbeat_at = NOW(), last_validated_at = NOW()
                     WHERE id = $1`,
                    [activation.id]
                );
            }

            // Log successful validation
            await this.logValidation(
                license.id, 
                activation?.id, 
                'online', 
                'valid',
                'License is valid', 
                siteUrl, 
                Date.now() - startTime
            );

            return {
                valid: true,
                status: 'valid',
                message: 'License is valid',
                tier: license.tier,
                features: license.features,
                validUntil: license.valid_until,
                activationToken: activation?.activation_token
            };

        } catch (error) {
            console.error('License validation error:', error);
            throw error;
        } finally {
            client.release();
        }
    }

    /**
     * Activate a license for a site
     */
    async activateLicense(licenseId, siteUrl, options = {}) {
        const client = await this.db.connect();
        
        try {
            await client.query('BEGIN');

            // Generate activation token
            const activationToken = crypto.randomBytes(32).toString('hex');
            
            // Create activation record
            const result = await client.query(
                `INSERT INTO license_activations (
                    license_id, site_url, activation_token,
                    machine_id, wordpress_version, plugin_version, php_version,
                    ip_address, user_agent
                ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
                RETURNING *`,
                [
                    licenseId,
                    siteUrl,
                    activationToken,
                    options.machineId,
                    options.wordpressVersion,
                    options.pluginVersion,
                    options.phpVersion,
                    options.ipAddress,
                    options.userAgent
                ]
            );

            // Update activation count
            await client.query(
                `UPDATE licenses 
                 SET activation_count = activation_count + 1,
                     status = 'active'
                 WHERE id = $1`,
                [licenseId]
            );

            await client.query('COMMIT');
            
            return result.rows[0];
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    }

    /**
     * Deactivate a license
     */
    async deactivateLicense(licenseKey, siteUrl) {
        const client = await this.db.connect();
        
        try {
            await client.query('BEGIN');

            // Get license
            const licenseResult = await client.query(
                'SELECT id FROM licenses WHERE license_key = $1',
                [licenseKey]
            );

            if (licenseResult.rows.length === 0) {
                throw new Error('License not found');
            }

            const licenseId = licenseResult.rows[0].id;

            // Deactivate activation
            await client.query(
                `UPDATE license_activations 
                 SET status = 'deactivated', deactivated_at = NOW()
                 WHERE license_id = $1 AND site_url = $2 AND status = 'active'`,
                [licenseId, siteUrl]
            );

            // Update activation count
            await client.query(
                `UPDATE licenses 
                 SET activation_count = GREATEST(0, activation_count - 1)
                 WHERE id = $1`,
                [licenseId]
            );

            await client.query('COMMIT');
            
            return {
                success: true,
                message: 'License deactivated successfully'
            };
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    }

    /**
     * Transfer license to another site
     */
    async transferLicense(licenseKey, fromSiteUrl, toSiteUrl, reason) {
        const client = await this.db.connect();
        
        try {
            await client.query('BEGIN');

            // Get license
            const licenseResult = await client.query(
                'SELECT id FROM licenses WHERE license_key = $1',
                [licenseKey]
            );

            if (licenseResult.rows.length === 0) {
                throw new Error('License not found');
            }

            const licenseId = licenseResult.rows[0].id;

            // Create transfer request
            const transferToken = crypto.randomBytes(32).toString('hex');
            
            const transferResult = await client.query(
                `INSERT INTO license_transfers (
                    license_id, from_site_url, to_site_url,
                    transfer_token, transfer_reason, status
                ) VALUES ($1, $2, $3, $4, $5, 'pending')
                RETURNING *`,
                [licenseId, fromSiteUrl, toSiteUrl, transferToken, reason]
            );

            // Auto-approve for now (in production, might require admin approval)
            await this.approveTransfer(transferToken);

            await client.query('COMMIT');
            
            return {
                success: true,
                transferToken,
                message: 'License transfer initiated'
            };
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    }

    /**
     * Approve license transfer
     */
    async approveTransfer(transferToken) {
        const client = await this.db.connect();
        
        try {
            await client.query('BEGIN');

            // Get transfer details
            const transferResult = await client.query(
                `SELECT * FROM license_transfers 
                 WHERE transfer_token = $1 AND status = 'pending'`,
                [transferToken]
            );

            if (transferResult.rows.length === 0) {
                throw new Error('Transfer request not found or already processed');
            }

            const transfer = transferResult.rows[0];

            // Deactivate old activation
            await client.query(
                `UPDATE license_activations 
                 SET status = 'transferred', deactivated_at = NOW()
                 WHERE license_id = $1 AND site_url = $2 AND status = 'active'`,
                [transfer.license_id, transfer.from_site_url]
            );

            // Create new activation
            const activationToken = crypto.randomBytes(32).toString('hex');
            
            await client.query(
                `INSERT INTO license_activations (
                    license_id, site_url, activation_token, status
                ) VALUES ($1, $2, $3, 'active')`,
                [transfer.license_id, transfer.to_site_url, activationToken]
            );

            // Update transfer status
            await client.query(
                `UPDATE license_transfers 
                 SET status = 'completed', completed_at = NOW()
                 WHERE id = $1`,
                [transfer.id]
            );

            await client.query('COMMIT');
            
            return {
                success: true,
                message: 'License transfer completed'
            };
        } catch (error) {
            await client.query('ROLLBACK');
            throw error;
        } finally {
            client.release();
        }
    }

    /**
     * Perform heartbeat check
     */
    async heartbeat(activationToken) {
        try {
            const result = await this.db.query(
                `UPDATE license_activations 
                 SET last_heartbeat_at = NOW()
                 WHERE activation_token = $1 AND status = 'active'
                 RETURNING license_id`,
                [activationToken]
            );

            if (result.rows.length === 0) {
                return {
                    success: false,
                    message: 'Invalid activation token'
                };
            }

            // Log heartbeat
            await this.logValidation(
                result.rows[0].license_id,
                null,
                'heartbeat',
                'valid',
                'Heartbeat successful',
                null,
                0
            );

            return {
                success: true,
                message: 'Heartbeat recorded'
            };
        } catch (error) {
            console.error('Heartbeat error:', error);
            throw error;
        }
    }

    /**
     * Check rate limiting
     */
    async checkRateLimit(licenseKey, endpoint) {
        try {
            // Get license ID
            const licenseResult = await this.db.query(
                'SELECT id, tier FROM licenses WHERE license_key = $1',
                [licenseKey]
            );

            if (licenseResult.rows.length === 0) {
                return false;
            }

            const license = licenseResult.rows[0];
            
            // Get rate limit based on tier
            const limits = {
                free: 100,
                pro: 1000,
                business: 10000
            };
            
            const maxRequests = limits[license.tier] || 100;

            // Check current window
            const windowStart = new Date();
            windowStart.setHours(windowStart.getHours() - 1);

            const result = await this.db.query(
                `SELECT request_count FROM license_rate_limits
                 WHERE license_id = $1 AND endpoint = $2 AND window_start > $3`,
                [license.id, endpoint, windowStart]
            );

            const totalRequests = result.rows.reduce((sum, row) => sum + row.request_count, 0);

            if (totalRequests >= maxRequests) {
                return true; // Rate limited
            }

            // Update counter
            await this.db.query(
                `INSERT INTO license_rate_limits (license_id, endpoint, request_count)
                 VALUES ($1, $2, 1)
                 ON CONFLICT (license_id, endpoint, window_start) 
                 DO UPDATE SET request_count = license_rate_limits.request_count + 1`,
                [license.id, endpoint]
            );

            return false;
        } catch (error) {
            console.error('Rate limit check error:', error);
            return false;
        }
    }

    /**
     * Log validation attempt
     */
    async logValidation(licenseId, activationId, type, result, message, siteUrl, responseTime) {
        try {
            await this.db.query(
                `INSERT INTO license_validations (
                    license_id, activation_id, validation_type,
                    validation_result, validation_message,
                    site_url, response_time_ms
                ) VALUES ($1, $2, $3, $4, $5, $6, $7)`,
                [licenseId, activationId, type, result, message, siteUrl, responseTime]
            );
        } catch (error) {
            console.error('Failed to log validation:', error);
        }
    }

    /**
     * Get public key for offline validation
     */
    async getPublicKey(version = null) {
        const query = version
            ? 'SELECT public_key FROM license_keys_rsa WHERE version = $1'
            : 'SELECT public_key FROM license_keys_rsa WHERE status = $1 ORDER BY version DESC LIMIT 1';
        
        const params = version ? [version] : ['active'];
        const result = await this.db.query(query, params);
        
        return result.rows[0]?.public_key;
    }

    /**
     * Generate checksum for license key
     */
    generateChecksum(licenseKey) {
        const hash = crypto.createHash('sha256').update(licenseKey).digest('hex');
        return hash.substring(0, 8).toUpperCase();
    }

    /**
     * Encrypt sensitive data
     */
    encryptData(data) {
        const algorithm = 'aes-256-gcm';
        const key = Buffer.from(process.env.ENCRYPTION_KEY, 'hex');
        const iv = crypto.randomBytes(16);
        const cipher = crypto.createCipher(algorithm, key, iv);
        
        let encrypted = cipher.update(data, 'utf8', 'hex');
        encrypted += cipher.final('hex');
        
        const authTag = cipher.getAuthTag();
        
        return JSON.stringify({
            encrypted,
            authTag: authTag.toString('hex'),
            iv: iv.toString('hex')
        });
    }

    /**
     * Decrypt sensitive data
     */
    decryptData(encryptedData) {
        const algorithm = 'aes-256-gcm';
        const key = Buffer.from(process.env.ENCRYPTION_KEY, 'hex');
        const parsed = JSON.parse(encryptedData);
        
        const decipher = crypto.createDecipher(
            algorithm, 
            key, 
            Buffer.from(parsed.iv, 'hex')
        );
        
        decipher.setAuthTag(Buffer.from(parsed.authTag, 'hex'));
        
        let decrypted = decipher.update(parsed.encrypted, 'hex', 'utf8');
        decrypted += decipher.final('utf8');
        
        return decrypted;
    }

    /**
     * Get license statistics
     */
    async getLicenseStats(licenseKey) {
        try {
            const result = await this.db.query(
                `SELECT 
                    l.*,
                    COUNT(DISTINCT la.id) FILTER (WHERE la.status = 'active') as active_sites,
                    MAX(la.last_heartbeat_at) as last_seen,
                    COALESCE(SUM(lum.revenue_generated), 0) as total_revenue,
                    COALESCE(SUM(lum.api_calls), 0) as total_api_calls
                FROM licenses l
                LEFT JOIN license_activations la ON l.id = la.license_id
                LEFT JOIN license_usage_metrics lum ON l.id = lum.license_id
                WHERE l.license_key = $1
                GROUP BY l.id`,
                [licenseKey]
            );

            return result.rows[0];
        } catch (error) {
            console.error('Failed to get license stats:', error);
            throw error;
        }
    }
}

module.exports = LicenseService;
