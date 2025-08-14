import { Request, Response, NextFunction } from 'express';
import * as argon2 from 'argon2';
import jwt from 'jsonwebtoken';
import { nanoid } from 'nanoid';
import { z } from 'zod';
import { prisma } from '../utils/prisma';
import { redis } from '../utils/redis';
import { config } from '../config';
import { sendEmail } from '../services/email.service';
import { logger } from '../utils/logger';
import { ApiError } from '../utils/ApiError';

// Validation schemas
const registerSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8).regex(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/),
  firstName: z.string().min(1),
  lastName: z.string().min(1),
  company: z.string().optional(),
  website: z.string().url().optional(),
  inviteCode: z.string().optional(),
});

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string(),
});

const refreshSchema = z.object({
  refreshToken: z.string(),
});

export class AuthController {
  /**
   * Register a new user
   */
  async register(req: Request, res: Response, next: NextFunction): Promise<void> {
    try {
      const validation = registerSchema.safeParse(req.body);
      if (!validation.success) {
        throw new ApiError(400, 'Validation error', validation.error.errors);
      }

      const { email, password, firstName, lastName, company, website, inviteCode } = validation.data;

      // Check if beta mode requires invite
      if (config.app.inviteRequired && config.app.betaMode) {
        if (!inviteCode) {
          throw new ApiError(400, 'Invite code is required during beta phase');
        }

        // Validate invite code
        const invite = await prisma.invite.findUnique({
          where: { code: inviteCode },
        });

        if (!invite || invite.usedCount >= invite.maxUses) {
          throw new ApiError(400, 'Invalid or expired invite code');
        }

        if (invite.expiresAt && new Date(invite.expiresAt) < new Date()) {
          throw new ApiError(400, 'Invite code has expired');
        }

        // Update invite usage
        await prisma.invite.update({
          where: { id: invite.id },
          data: { usedCount: invite.usedCount + 1 },
        });
      }

      // Check if user already exists
      const existingUser = await prisma.user.findUnique({
        where: { email },
      });

      if (existingUser) {
        throw new ApiError(409, 'Email already registered');
      }

      // Hash password
      const hashedPassword = await argon2.hash(password);

      // Generate verification token
      const verificationToken = nanoid(32);

      // Create user
      const user = await prisma.user.create({
        data: {
          email,
          password: hashedPassword,
          firstName,
          lastName,
          company,
          website,
          verificationToken,
          inviteCode,
          status: config.app.betaMode ? 'PENDING' : 'ACTIVE',
        },
        select: {
          id: true,
          email: true,
          firstName: true,
          lastName: true,
          company: true,
          status: true,
        },
      });

      // Create default API key for the user
      const apiKey = nanoid(32);
      const hashedApiKey = await argon2.hash(apiKey + config.api.keySalt);
      
      await prisma.apiKey.create({
        data: {
          key: hashedApiKey,
          name: 'Default API Key',
          userId: user.id,
          permissions: ['read', 'write'],
        },
      });

      // Send verification email
      await sendEmail({
        to: email,
        subject: 'Welcome to PayPerCrawl - Verify Your Email',
        template: 'verification',
        data: {
          firstName,
          verificationUrl: `${config.app.url}/verify?token=${verificationToken}`,
        },
      });

      // Log registration
      await prisma.auditLog.create({
        data: {
          action: 'USER_REGISTERED',
          entity: 'user',
          entityId: user.id,
          userId: user.id,
          ip: req.ip,
          userAgent: req.get('user-agent'),
        },
      });

      res.status(201).json({
        success: true,
        message: 'Registration successful. Please check your email to verify your account.',
        data: {
          user,
          apiKey: `ppc_${apiKey}`, // Return the plain API key once
        },
      });
    } catch (error) {
      next(error);
    }
  }

  /**
   * Login user
   */
  async login(req: Request, res: Response, next: NextFunction): Promise<void> {
    try {
      const validation = loginSchema.safeParse(req.body);
      if (!validation.success) {
        throw new ApiError(400, 'Validation error', validation.error.errors);
      }

      const { email, password } = validation.data;

      // Find user
      const user = await prisma.user.findUnique({
        where: { email },
        include: {
          subscriptions: {
            where: { status: 'ACTIVE' },
            take: 1,
          },
        },
      });

      if (!user) {
        throw new ApiError(401, 'Invalid credentials');
      }

      // Verify password
      const isValidPassword = await argon2.verify(user.password, password);
      if (!isValidPassword) {
        throw new ApiError(401, 'Invalid credentials');
      }

      // Check user status
      if (user.status === 'SUSPENDED') {
        throw new ApiError(403, 'Account suspended. Please contact support.');
      }

      if (user.status === 'DELETED') {
        throw new ApiError(403, 'Account has been deleted');
      }

      // Generate tokens
      const accessToken = jwt.sign(
        {
          userId: user.id,
          email: user.email,
          role: user.role,
        },
        config.jwt.accessSecret,
        { expiresIn: config.jwt.accessExpiry }
      );

      const refreshToken = jwt.sign(
        { userId: user.id },
        config.jwt.refreshSecret,
        { expiresIn: config.jwt.refreshExpiry }
      );

      // Store refresh token
      await prisma.refreshToken.create({
        data: {
          token: refreshToken,
          userId: user.id,
          expiresAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // 7 days
        },
      });

      // Cache user session in Redis
      await redis.setex(
        `session:${user.id}`,
        15 * 60, // 15 minutes
        JSON.stringify({
          userId: user.id,
          email: user.email,
          role: user.role,
          subscription: user.subscriptions[0] || null,
        })
      );

      // Log login
      await prisma.auditLog.create({
        data: {
          action: 'USER_LOGIN',
          entity: 'user',
          entityId: user.id,
          userId: user.id,
          ip: req.ip,
          userAgent: req.get('user-agent'),
        },
      });

      res.json({
        success: true,
        message: 'Login successful',
        data: {
          accessToken,
          refreshToken,
          user: {
            id: user.id,
            email: user.email,
            firstName: user.firstName,
            lastName: user.lastName,
            company: user.company,
            role: user.role,
            emailVerified: user.emailVerified,
            subscription: user.subscriptions[0] || null,
          },
        },
      });
    } catch (error) {
      next(error);
    }
  }

  /**
   * Refresh access token
   */
  async refresh(req: Request, res: Response, next: NextFunction): Promise<void> {
    try {
      const validation = refreshSchema.safeParse(req.body);
      if (!validation.success) {
        throw new ApiError(400, 'Validation error', validation.error.errors);
      }

      const { refreshToken } = validation.data;

      // Verify refresh token
      let decoded: any;
      try {
        decoded = jwt.verify(refreshToken, config.jwt.refreshSecret);
      } catch (error) {
        throw new ApiError(401, 'Invalid refresh token');
      }

      // Check if refresh token exists in database
      const storedToken = await prisma.refreshToken.findUnique({
        where: { token: refreshToken },
        include: { user: true },
      });

      if (!storedToken) {
        throw new ApiError(401, 'Invalid refresh token');
      }

      if (new Date(storedToken.expiresAt) < new Date()) {
        await prisma.refreshToken.delete({ where: { id: storedToken.id } });
        throw new ApiError(401, 'Refresh token has expired');
      }

      // Generate new access token
      const accessToken = jwt.sign(
        {
          userId: storedToken.user.id,
          email: storedToken.user.email,
          role: storedToken.user.role,
        },
        config.jwt.accessSecret,
        { expiresIn: config.jwt.accessExpiry }
      );

      res.json({
        success: true,
        message: 'Token refreshed successfully',
        data: {
          accessToken,
        },
      });
    } catch (error) {
      next(error);
    }
  }

  /**
   * Logout user
   */
  async logout(req: Request, res: Response, next: NextFunction): Promise<void> {
    try {
      const userId = (req as any).user?.userId;
      
      if (userId) {
        // Clear Redis session
        await redis.del(`session:${userId}`);
        
        // Log logout
        await prisma.auditLog.create({
          data: {
            action: 'USER_LOGOUT',
            entity: 'user',
            entityId: userId,
            userId: userId,
            ip: req.ip,
            userAgent: req.get('user-agent'),
          },
        });
      }

      res.json({
        success: true,
        message: 'Logout successful',
      });
    } catch (error) {
      next(error);
    }
  }

  /**
   * Verify email
   */
  async verifyEmail(req: Request, res: Response, next: NextFunction): Promise<void> {
    try {
      const { token } = req.query;

      if (!token || typeof token !== 'string') {
        throw new ApiError(400, 'Invalid verification token');
      }

      const user = await prisma.user.findFirst({
        where: { verificationToken: token },
      });

      if (!user) {
        throw new ApiError(400, 'Invalid or expired verification token');
      }

      await prisma.user.update({
        where: { id: user.id },
        data: {
          emailVerified: true,
          verificationToken: null,
          status: 'ACTIVE',
        },
      });

      res.json({
        success: true,
        message: 'Email verified successfully',
      });
    } catch (error) {
      next(error);
    }
  }
}

export default new AuthController();
