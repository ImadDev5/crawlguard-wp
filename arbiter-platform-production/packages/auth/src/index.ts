import { z } from 'zod';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { nanoid } from 'nanoid';
import { PrismaClient, User, UserRole } from '@arbiter/database';

const prisma = new PrismaClient();

// Validation schemas
export const registerSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
  username: z.string().min(3).max(30),
  firstName: z.string().min(1).max(50),
  lastName: z.string().min(1).max(50),
  role: z.enum(['CREATOR', 'AI_COMPANY']),
  companyName: z.string().optional(),
});

export const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

export const forgotPasswordSchema = z.object({
  email: z.string().email(),
});

export const resetPasswordSchema = z.object({
  token: z.string(),
  password: z.string().min(8),
});

// Types
export interface AuthUser {
  id: string;
  email: string;
  username: string;
  firstName: string | null;
  lastName: string | null;
  role: UserRole;
  status: string;
  emailVerified: Date | null;
  avatar: string | null;
}

export interface LoginResult {
  user: AuthUser;
  token: string;
  expiresIn: string;
}

export class AuthService {
  private jwtSecret: string;
  private jwtExpiresIn: string;
  private bcryptRounds: number;

  constructor() {
    this.jwtSecret = process.env.JWT_SECRET || 'fallback-secret';
    this.jwtExpiresIn = process.env.JWT_EXPIRES_IN || '7d';
    this.bcryptRounds = parseInt(process.env.BCRYPT_ROUNDS || '12');
  }

  /**
   * Register a new user
   */
  async register(data: z.infer<typeof registerSchema>): Promise<LoginResult> {
    // Validate input
    const validated = registerSchema.parse(data);

    // Check if user exists
    const existingUser = await prisma.user.findFirst({
      where: {
        OR: [
          { email: validated.email },
          { username: validated.username }
        ]
      }
    });

    if (existingUser) {
      throw new Error('User with this email or username already exists');
    }

    // Hash password
    const passwordHash = await bcrypt.hash(validated.password, this.bcryptRounds);

    // Generate verification token
    const emailVerificationToken = nanoid();

    // Create user
    const user = await prisma.user.create({
      data: {
        email: validated.email,
        username: validated.username,
        firstName: validated.firstName,
        lastName: validated.lastName,
        role: validated.role as UserRole,
        passwordHash,
        emailVerificationToken,
        // Create profile based on role
        ...(validated.role === 'CREATOR' && {
          creatorProfile: {
            create: {}
          }
        }),
        ...(validated.role === 'AI_COMPANY' && {
          aiCompanyProfile: {
            create: {
              companyName: validated.companyName || validated.firstName + ' ' + validated.lastName
            }
          }
        })
      },
      include: {
        creatorProfile: true,
        aiCompanyProfile: true
      }
    });

    // Generate JWT token
    const token = this.generateToken(user);

    return {
      user: this.formatUser(user),
      token,
      expiresIn: this.jwtExpiresIn
    };
  }

  /**
   * Login user
   */
  async login(data: z.infer<typeof loginSchema>): Promise<LoginResult> {
    const validated = loginSchema.parse(data);

    // Find user
    const user = await prisma.user.findUnique({
      where: { email: validated.email },
      include: {
        creatorProfile: true,
        aiCompanyProfile: true
      }
    });

    if (!user || !user.passwordHash) {
      throw new Error('Invalid credentials');
    }

    // Check password
    const isValidPassword = await bcrypt.compare(validated.password, user.passwordHash);
    if (!isValidPassword) {
      throw new Error('Invalid credentials');
    }

    // Check if user is active
    if (user.status === 'BANNED' || user.status === 'SUSPENDED') {
      throw new Error('Account is suspended or banned');
    }

    // Update last login
    await prisma.user.update({
      where: { id: user.id },
      data: { lastLoginAt: new Date() }
    });

    // Generate JWT token
    const token = this.generateToken(user);

    return {
      user: this.formatUser(user),
      token,
      expiresIn: this.jwtExpiresIn
    };
  }

  /**
   * Verify JWT token
   */
  async verifyToken(token: string): Promise<AuthUser | null> {
    try {
      const decoded = jwt.verify(token, this.jwtSecret) as { userId: string };
      
      const user = await prisma.user.findUnique({
        where: { id: decoded.userId },
        include: {
          creatorProfile: true,
          aiCompanyProfile: true
        }
      });

      if (!user || user.status === 'BANNED' || user.status === 'SUSPENDED') {
        return null;
      }

      return this.formatUser(user);
    } catch (error) {
      return null;
    }
  }

  /**
   * Refresh token
   */
  async refreshToken(token: string): Promise<LoginResult | null> {
    const user = await this.verifyToken(token);
    if (!user) return null;

    const fullUser = await prisma.user.findUnique({
      where: { id: user.id },
      include: {
        creatorProfile: true,
        aiCompanyProfile: true
      }
    });

    if (!fullUser) return null;

    const newToken = this.generateToken(fullUser);

    return {
      user: this.formatUser(fullUser),
      token: newToken,
      expiresIn: this.jwtExpiresIn
    };
  }

  /**
   * Forgot password
   */
  async forgotPassword(data: z.infer<typeof forgotPasswordSchema>): Promise<void> {
    const validated = forgotPasswordSchema.parse(data);

    const user = await prisma.user.findUnique({
      where: { email: validated.email }
    });

    if (!user) {
      // Don't reveal if user exists
      return;
    }

    const resetToken = nanoid();
    const resetExpires = new Date(Date.now() + 3600000); // 1 hour

    await prisma.user.update({
      where: { id: user.id },
      data: {
        passwordResetToken: resetToken,
        passwordResetExpires: resetExpires
      }
    });

    // TODO: Send email with reset link
    // await emailService.sendPasswordReset(user.email, resetToken);
  }

  /**
   * Reset password
   */
  async resetPassword(data: z.infer<typeof resetPasswordSchema>): Promise<void> {
    const validated = resetPasswordSchema.parse(data);

    const user = await prisma.user.findFirst({
      where: {
        passwordResetToken: validated.token,
        passwordResetExpires: {
          gt: new Date()
        }
      }
    });

    if (!user) {
      throw new Error('Invalid or expired reset token');
    }

    const passwordHash = await bcrypt.hash(validated.password, this.bcryptRounds);

    await prisma.user.update({
      where: { id: user.id },
      data: {
        passwordHash,
        passwordResetToken: null,
        passwordResetExpires: null
      }
    });
  }

  /**
   * Verify email
   */
  async verifyEmail(token: string): Promise<void> {
    const user = await prisma.user.findFirst({
      where: { emailVerificationToken: token }
    });

    if (!user) {
      throw new Error('Invalid verification token');
    }

    await prisma.user.update({
      where: { id: user.id },
      data: {
        emailVerified: new Date(),
        emailVerificationToken: null,
        status: 'ACTIVE'
      }
    });
  }

  /**
   * Change password
   */
  async changePassword(userId: string, currentPassword: string, newPassword: string): Promise<void> {
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });

    if (!user || !user.passwordHash) {
      throw new Error('User not found');
    }

    const isValidPassword = await bcrypt.compare(currentPassword, user.passwordHash);
    if (!isValidPassword) {
      throw new Error('Current password is incorrect');
    }

    const passwordHash = await bcrypt.hash(newPassword, this.bcryptRounds);

    await prisma.user.update({
      where: { id: userId },
      data: { passwordHash }
    });
  }

  /**
   * Generate JWT token
   */
  private generateToken(user: any): string {
    return jwt.sign(
      { userId: user.id, email: user.email, role: user.role },
      this.jwtSecret,
      { expiresIn: this.jwtExpiresIn }
    );
  }

  /**
   * Format user for response
   */
  private formatUser(user: any): AuthUser {
    return {
      id: user.id,
      email: user.email,
      username: user.username,
      firstName: user.firstName,
      lastName: user.lastName,
      role: user.role,
      status: user.status,
      emailVerified: user.emailVerified,
      avatar: user.avatar
    };
  }
}

export const authService = new AuthService();
