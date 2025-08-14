import express, { Application } from 'express';
import cors from 'cors';
import helmet from 'helmet';
import morgan from 'morgan';
import dotenv from 'dotenv';
import { createServer } from 'http';
import { rateLimit } from 'express-rate-limit';

// Load environment variables
dotenv.config();

// Import configurations
import { config } from './config';
import { logger } from './utils/logger';
import { prisma } from './utils/prisma';
import { redis } from './utils/redis';

// Import middleware
import { errorHandler } from './middleware/errorHandler';
import { requestLogger } from './middleware/requestLogger';
import { securityMiddleware } from './middleware/security';

// Import routes
import authRoutes from './routes/auth.routes';
import licenseRoutes from './routes/license.routes';
import detectionRoutes from './routes/detection.routes';
import billingRoutes from './routes/billing.routes';
import payoutRoutes from './routes/payout.routes';
import analyticsRoutes from './routes/analytics.routes';
import webhookRoutes from './routes/webhook.routes';
import adminRoutes from './routes/admin.routes';

class Server {
  private app: Application;
  private server: any;
  private port: number;

  constructor() {
    this.app = express();
    this.port = parseInt(process.env.PORT || '3000', 10);
    this.initializeMiddleware();
    this.initializeRoutes();
    this.initializeErrorHandling();
  }

  private initializeMiddleware(): void {
    // Security middleware
    this.app.use(helmet({
      contentSecurityPolicy: {
        directives: {
          defaultSrc: ["'self'"],
          styleSrc: ["'self'", "'unsafe-inline'"],
          scriptSrc: ["'self'", "'unsafe-inline'"],
          imgSrc: ["'self'", "data:", "https:"],
        },
      },
    }));

    // CORS configuration
    this.app.use(cors({
      origin: (origin, callback) => {
        const allowedOrigins = config.cors.origins;
        if (!origin || allowedOrigins.includes(origin)) {
          callback(null, true);
        } else {
          callback(new Error('Not allowed by CORS'));
        }
      },
      credentials: true,
      methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
      allowedHeaders: ['Content-Type', 'Authorization', 'X-API-Key', 'X-Signature', 'X-Timestamp'],
    }));

    // Body parsing
    this.app.use(express.json({ limit: '10mb' }));
    this.app.use(express.urlencoded({ extended: true, limit: '10mb' }));

    // Logging
    if (process.env.NODE_ENV !== 'test') {
      this.app.use(morgan('combined', { stream: { write: (message) => logger.info(message.trim()) } }));
    }

    // Request logging
    this.app.use(requestLogger);

    // Security middleware
    this.app.use(securityMiddleware);

    // Trust proxy for accurate IP addresses
    this.app.set('trust proxy', 1);

    // Global rate limiting
    const limiter = rateLimit({
      windowMs: parseInt(process.env.RATE_LIMIT_WINDOW || '15', 10) * 60 * 1000,
      max: parseInt(process.env.RATE_LIMIT_MAX || '100', 10),
      message: 'Too many requests from this IP, please try again later.',
      standardHeaders: true,
      legacyHeaders: false,
      handler: (req, res) => {
        logger.warn(`Rate limit exceeded for IP: ${req.ip}`);
        res.status(429).json({
          error: 'Too many requests',
          message: 'Rate limit exceeded. Please try again later.',
          retryAfter: res.getHeader('Retry-After'),
        });
      },
    });

    this.app.use('/api/', limiter);
  }

  private initializeRoutes(): void {
    // Health check
    this.app.get('/health', (req, res) => {
      res.json({
        status: 'healthy',
        timestamp: new Date().toISOString(),
        uptime: process.uptime(),
        environment: process.env.NODE_ENV,
      });
    });

    // API routes
    this.app.use('/api/v1/auth', authRoutes);
    this.app.use('/api/v1/licenses', licenseRoutes);
    this.app.use('/api/v1/detections', detectionRoutes);
    this.app.use('/api/v1/billing', billingRoutes);
    this.app.use('/api/v1/payouts', payoutRoutes);
    this.app.use('/api/v1/analytics', analyticsRoutes);
    this.app.use('/api/v1/webhooks', webhookRoutes);
    this.app.use('/api/v1/admin', adminRoutes);

    // WordPress plugin specific routes
    this.app.use('/api/v1/wp', require('./routes/wordpress.routes').default);

    // Swagger documentation
    if (process.env.NODE_ENV !== 'production') {
      const swaggerUi = require('swagger-ui-express');
      const swaggerDocument = require('../docs/swagger.json');
      this.app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(swaggerDocument));
    }

    // 404 handler
    this.app.use('*', (req, res) => {
      res.status(404).json({
        error: 'Not Found',
        message: 'The requested resource does not exist',
        path: req.originalUrl,
      });
    });
  }

  private initializeErrorHandling(): void {
    this.app.use(errorHandler);

    // Graceful shutdown
    process.on('SIGTERM', this.gracefulShutdown.bind(this));
    process.on('SIGINT', this.gracefulShutdown.bind(this));

    // Handle uncaught exceptions
    process.on('uncaughtException', (error) => {
      logger.error('Uncaught Exception:', error);
      this.gracefulShutdown();
    });

    // Handle unhandled promise rejections
    process.on('unhandledRejection', (reason, promise) => {
      logger.error('Unhandled Rejection at:', promise, 'reason:', reason);
      this.gracefulShutdown();
    });
  }

  private async gracefulShutdown(): Promise<void> {
    logger.info('Graceful shutdown initiated...');
    
    if (this.server) {
      this.server.close(() => {
        logger.info('HTTP server closed');
      });
    }

    // Close database connections
    await prisma.$disconnect();
    logger.info('Database connection closed');

    // Close Redis connection
    await redis.quit();
    logger.info('Redis connection closed');

    process.exit(0);
  }

  public async start(): Promise<void> {
    try {
      // Test database connection
      await prisma.$connect();
      logger.info('Database connected successfully');

      // Test Redis connection
      await redis.ping();
      logger.info('Redis connected successfully');

      // Start server
      this.server = createServer(this.app);
      this.server.listen(this.port, () => {
        logger.info(`
          🚀 PayPerCrawl API Server is running!
          🔗 Local: http://localhost:${this.port}
          🔗 Network: http://${require('os').hostname()}:${this.port}
          📚 API Docs: http://localhost:${this.port}/api-docs
          🌍 Environment: ${process.env.NODE_ENV}
          🔒 Beta Mode: ${process.env.BETA_MODE === 'true' ? 'Enabled' : 'Disabled'}
        `);
      });
    } catch (error) {
      logger.error('Failed to start server:', error);
      process.exit(1);
    }
  }
}

// Start the server
const server = new Server();
server.start().catch((error) => {
  logger.error('Server startup failed:', error);
  process.exit(1);
});

export default server;
