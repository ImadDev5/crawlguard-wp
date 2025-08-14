import express from 'express';
import cors from 'cors';
import { RuleEvaluator } from './engine/rule-evaluator';
import { setupRulesRoutes } from './api/rules-controller';

// Extend Express Request interface to include user
declare global {
  namespace Express {
    interface Request {
      user?: {
        id: string;
        email: string;
        role: string;
        [key: string]: any;
      };
    }
  }
}

/**
 * Rules Engine Server
 * Microservice for dynamic pricing rules evaluation
 */
class RulesEngineServer {
  private app: express.Application;
  private evaluator: RuleEvaluator;
  private port: number;

  constructor() {
    this.app = express();
    this.port = parseInt(process.env.RULES_ENGINE_PORT || '3020');
    this.evaluator = new RuleEvaluator();
    
    this.setupMiddleware();
    this.setupRoutes();
    this.setupErrorHandling();
  }

  private setupMiddleware(): void {
    // Basic middleware
    this.app.use(cors({
      origin: process.env.ALLOWED_ORIGINS?.split(',') || [
        'http://localhost:3000',
        'http://localhost:3001'
      ],
      credentials: true
    }));
    
    this.app.use(express.json({ limit: '10mb' }));
    this.app.use(express.urlencoded({ extended: true }));
    
    // Request logging
    this.app.use((req, res, next) => {
      console.log(`${new Date().toISOString()} - ${req.method} ${req.path}`);
      next();
    });

    // Authentication middleware (placeholder)
    this.app.use('/api', this.authenticateRequest.bind(this));
  }

  private async authenticateRequest(
    req: express.Request, 
    res: express.Response, 
    next: express.NextFunction
  ): Promise<void> {
    // Skip auth for health checks and public endpoints
    if (req.path === '/health' || 
        req.path === '/api/rules/templates' || 
        req.path === '/' ||
        req.method === 'OPTIONS') {
      return next();
    }

    try {
      const authHeader = req.headers.authorization;
      if (!authHeader || !authHeader.startsWith('Bearer ')) {
        res.status(401).json({ error: 'Authorization token required' });
        return;
      }

      const token = authHeader.substring(7);
      
      // TODO: Validate JWT token and extract user info
      // For now, use a placeholder implementation
      if (token === 'test-token') {
        req.user = {
          id: 'user_123',
          email: 'test@example.com',
          role: 'publisher'
        };
        return next();
      }

      // In production, decode and validate JWT:
      // const decoded = jwt.verify(token, process.env.JWT_SECRET);
      // req.user = decoded.user;
      
      res.status(401).json({ error: 'Invalid or expired token' });
    } catch (error) {
      console.error('Authentication error:', error);
      res.status(401).json({ error: 'Authentication failed' });
    }
  }

  private setupRoutes(): void {
    // Health check
    this.app.get('/health', (req, res) => {
      res.json({
        status: 'healthy',
        service: 'rules-engine',
        timestamp: new Date().toISOString(),
        version: process.env.npm_package_version || '1.0.0'
      });
    });

    // API routes
    this.app.use('/api', setupRulesRoutes(this.evaluator));

    // Root endpoint
    this.app.get('/', (req, res) => {
      res.json({
        service: 'Arbiter Platform - Rules Engine',
        version: '1.0.0',
        documentation: '/api/docs',
        health: '/health'
      });
    });

    // 404 handler
    this.app.use('*', (req, res) => {
      res.status(404).json({
        error: 'Endpoint not found',
        method: req.method,
        path: req.originalUrl
      });
    });
  }

  private setupErrorHandling(): void {
    // Global error handler
    this.app.use((
      error: Error,
      req: express.Request,
      res: express.Response,
      next: express.NextFunction
    ) => {
      console.error('Unhandled error:', error);
      
      res.status(500).json({
        error: 'Internal server error',
        message: process.env.NODE_ENV === 'development' ? error.message : undefined,
        stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
      });
    });

    // Handle process termination
    process.on('SIGTERM', () => {
      console.log('SIGTERM received, shutting down gracefully');
      this.shutdown();
    });

    process.on('SIGINT', () => {
      console.log('SIGINT received, shutting down gracefully');
      this.shutdown();
    });
  }

  public async start(): Promise<void> {
    try {
      // Initialize the rule evaluator
      await this.evaluator.initialize();
      
      // Start the server
      this.app.listen(this.port, () => {
        console.log(`🚀 Rules Engine Server running on port ${this.port}`);
        console.log(`📊 Health check: http://localhost:${this.port}/health`);
        console.log(`🔧 API docs: http://localhost:${this.port}/api/docs`);
        console.log(`🌟 Environment: ${process.env.NODE_ENV || 'development'}`);
      });
    } catch (error) {
      console.error('Failed to start Rules Engine Server:', error);
      process.exit(1);
    }
  }

  private async shutdown(): Promise<void> {
    try {
      console.log('Shutting down Rules Engine Server...');
      
      // Close Redis connections
      await this.evaluator.shutdown();
      
      console.log('Rules Engine Server shut down successfully');
      process.exit(0);
    } catch (error) {
      console.error('Error during shutdown:', error);
      process.exit(1);
    }
  }
}

// Start the server if this file is run directly
if (require.main === module) {
  const server = new RulesEngineServer();
  server.start();
}

export { RulesEngineServer };
