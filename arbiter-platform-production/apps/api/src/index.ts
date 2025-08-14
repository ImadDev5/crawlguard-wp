import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import morgan from 'morgan';
import compression from 'compression';
import rateLimit from 'express-rate-limit';
import { PrismaClient } from '@arbiter/database';
import { authService } from '@arbiter/auth';
import dotenv from 'dotenv';

// Load environment variables
dotenv.config();

const app = express();
const prisma = new PrismaClient();
const PORT = process.env.API_PORT || 4000;

// Security middleware
app.use(helmet());
app.use(cors({
  origin: process.env.CORS_ORIGINS?.split(',') || ['http://localhost:3000'],
  credentials: true
}));

// Rate limiting
const limiter = rateLimit({
  windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS || '900000'), // 15 minutes
  max: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS || '100'),
  message: 'Too many requests from this IP, please try again later.',
  standardHeaders: true,
  legacyHeaders: false,
});
app.use('/api/', limiter);

// General middleware
app.use(compression());
app.use(morgan('combined'));
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Health check
app.get('/health', (req, res) => {
  res.json({ 
    status: 'healthy', 
    timestamp: new Date().toISOString(),
    environment: process.env.NODE_ENV,
    version: '1.0.0'
  });
});

// ==========================================
// AUTHENTICATION ROUTES
// ==========================================

// Register
app.post('/api/auth/register', async (req, res) => {
  try {
    const result = await authService.register(req.body);
    res.status(201).json({
      success: true,
      message: 'User registered successfully',
      data: result
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error instanceof Error ? error.message : 'Registration failed'
    });
  }
});

// Login
app.post('/api/auth/login', async (req, res) => {
  try {
    const result = await authService.login(req.body);
    res.json({
      success: true,
      message: 'Login successful',
      data: result
    });
  } catch (error) {
    res.status(401).json({
      success: false,
      message: error instanceof Error ? error.message : 'Login failed'
    });
  }
});

// Forgot password
app.post('/api/auth/forgot-password', async (req, res) => {
  try {
    await authService.forgotPassword(req.body);
    res.json({
      success: true,
      message: 'Password reset email sent'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error instanceof Error ? error.message : 'Password reset failed'
    });
  }
});

// Reset password
app.post('/api/auth/reset-password', async (req, res) => {
  try {
    await authService.resetPassword(req.body);
    res.json({
      success: true,
      message: 'Password reset successful'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error instanceof Error ? error.message : 'Password reset failed'
    });
  }
});

// Verify email
app.post('/api/auth/verify-email', async (req, res) => {
  try {
    await authService.verifyEmail(req.body.token);
    res.json({
      success: true,
      message: 'Email verified successfully'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      message: error instanceof Error ? error.message : 'Email verification failed'
    });
  }
});

// ==========================================
// AUTHENTICATION MIDDLEWARE
// ==========================================

const authenticateToken = async (req: any, res: any, next: any) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({
      success: false,
      message: 'Access token required'
    });
  }

  try {
    const user = await authService.verifyToken(token);
    if (!user) {
      return res.status(403).json({
        success: false,
        message: 'Invalid or expired token'
      });
    }
    req.user = user;
    next();
  } catch (error) {
    return res.status(403).json({
      success: false,
      message: 'Invalid token'
    });
  }
};

// ==========================================
// PROTECTED ROUTES
// ==========================================

// Get current user
app.get('/api/auth/me', authenticateToken, (req: any, res) => {
  res.json({
    success: true,
    data: req.user
  });
});

// ==========================================
// CREATOR ROUTES
// ==========================================

// Get creator dashboard stats
app.get('/api/creator/dashboard', authenticateToken, async (req: any, res) => {
  try {
    if (req.user.role !== 'CREATOR') {
      return res.status(403).json({
        success: false,
        message: 'Creator access required'
      });
    }

    const profile = await prisma.creatorProfile.findUnique({
      where: { userId: req.user.id },
      include: {
        user: {
          include: {
            uploads: {
              take: 10,
              orderBy: { createdAt: 'desc' }
            }
          }
        }
      }
    });

    if (!profile) {
      return res.status(404).json({
        success: false,
        message: 'Creator profile not found'
      });
    }

    const stats = {
      totalEarnings: profile.totalEarnings,
      totalUploads: profile.totalUploads,
      totalLicensesSold: profile.totalLicensesSold,
      averageRating: profile.averageRating,
      recentUploads: profile.user.uploads.map(upload => ({
        id: upload.id,
        title: upload.title,
        contentType: upload.contentType,
        status: upload.status,
        totalRevenue: upload.totalRevenue,
        licensesSold: upload.licensesSold,
        createdAt: upload.createdAt
      }))
    };

    res.json({
      success: true,
      data: stats
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: 'Failed to fetch dashboard stats'
    });
  }
});

// ==========================================
// AI COMPANY ROUTES
// ==========================================

// Get AI company dashboard stats
app.get('/api/ai-company/dashboard', authenticateToken, async (req: any, res) => {
  try {
    if (req.user.role !== 'AI_COMPANY') {
      return res.status(403).json({
        success: false,
        message: 'AI company access required'
      });
    }

    const profile = await prisma.aICompanyProfile.findUnique({
      where: { userId: req.user.id },
      include: {
        user: {
          include: {
            licenses: {
              take: 10,
              orderBy: { createdAt: 'desc' },
              include: {
                upload: {
                  select: {
                    title: true,
                    contentType: true
                  }
                }
              }
            }
          }
        }
      }
    });

    if (!profile) {
      return res.status(404).json({
        success: false,
        message: 'AI company profile not found'
      });
    }

    const stats = {
      totalSpent: profile.totalSpent,
      totalLicenses: profile.totalLicenses,
      activeProjects: profile.activeProjects,
      apiCalls: profile.apiCalls,
      recentLicenses: profile.user.licenses.map(license => ({
        id: license.id,
        uploadTitle: license.upload.title,
        contentType: license.upload.contentType,
        type: license.type,
        status: license.status,
        price: license.price,
        createdAt: license.createdAt
      }))
    };

    res.json({
      success: true,
      data: stats
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: 'Failed to fetch dashboard stats'
    });
  }
});

// ==========================================
// CONTENT ROUTES
// ==========================================

// Get public content (search/browse)
app.get('/api/content', async (req, res) => {
  try {
    const { 
      page = 1, 
      limit = 20, 
      contentType, 
      tags, 
      minPrice, 
      maxPrice, 
      sortBy = 'createdAt',
      sortOrder = 'desc'
    } = req.query;

    const skip = (parseInt(page as string) - 1) * parseInt(limit as string);

    const where: any = {
      status: 'APPROVED'
    };

    if (contentType) {
      where.contentType = contentType;
    }

    if (tags) {
      where.tags = {
        hasSome: (tags as string).split(',')
      };
    }

    if (minPrice || maxPrice) {
      where.basePrice = {};
      if (minPrice) where.basePrice.gte = parseFloat(minPrice as string);
      if (maxPrice) where.basePrice.lte = parseFloat(maxPrice as string);
    }

    const uploads = await prisma.upload.findMany({
      where,
      skip,
      take: parseInt(limit as string),
      orderBy: {
        [sortBy as string]: sortOrder
      },
      include: {
        user: {
          select: {
            username: true,
            firstName: true,
            lastName: true,
            avatar: true
          }
        },
        _count: {
          select: {
            licenses: true,
            reviews: true
          }
        }
      }
    });

    const total = await prisma.upload.count({ where });

    res.json({
      success: true,
      data: {
        uploads,
        pagination: {
          page: parseInt(page as string),
          limit: parseInt(limit as string),
          total,
          pages: Math.ceil(total / parseInt(limit as string))
        }
      }
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: 'Failed to fetch content'
    });
  }
});

// ==========================================
// ERROR HANDLING
// ==========================================

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({
    success: false,
    message: 'Route not found'
  });
});

// Global error handler
app.use((error: any, req: any, res: any, next: any) => {
  console.error('Global error:', error);
  
  res.status(500).json({
    success: false,
    message: process.env.NODE_ENV === 'development' ? error.message : 'Internal server error'
  });
});

// ==========================================
// START SERVER
// ==========================================

const server = app.listen(PORT, () => {
  console.log(`🚀 Arbiter Platform API Server running on port ${PORT}`);
  console.log(`🌐 Environment: ${process.env.NODE_ENV}`);
  console.log(`📊 Database: Connected to PostgreSQL`);
  console.log(`🔒 Authentication: JWT enabled`);
  console.log(`⚡ Rate limiting: ${process.env.RATE_LIMIT_MAX_REQUESTS} requests per ${process.env.RATE_LIMIT_WINDOW_MS}ms`);
});

// Graceful shutdown
process.on('SIGTERM', async () => {
  console.log('🔄 Shutting down gracefully...');
  
  server.close(() => {
    console.log('✅ HTTP server closed');
  });
  
  await prisma.$disconnect();
  console.log('✅ Database connection closed');
  
  process.exit(0);
});

process.on('SIGINT', async () => {
  console.log('🔄 Shutting down gracefully...');
  
  server.close(() => {
    console.log('✅ HTTP server closed');
  });
  
  await prisma.$disconnect();
  console.log('✅ Database connection closed');
  
  process.exit(0);
});

export default app;
