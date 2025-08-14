import dotenv from 'dotenv';
dotenv.config();

export const config = {
  app: {
    name: process.env.APP_NAME || 'PayPerCrawl',
    url: process.env.APP_URL || 'http://localhost:3000',
    port: parseInt(process.env.PORT || '3000', 10),
    env: process.env.NODE_ENV || 'development',
    betaMode: process.env.BETA_MODE === 'true',
    inviteRequired: process.env.INVITE_REQUIRED === 'true',
  },
  
  database: {
    url: process.env.DATABASE_URL!,
  },
  
  supabase: {
    url: process.env.SUPABASE_URL!,
    anonKey: process.env.SUPABASE_ANON_KEY!,
    serviceKey: process.env.SUPABASE_SERVICE_KEY!,
  },
  
  redis: {
    url: process.env.REDIS_URL || 'redis://localhost:6379',
    password: process.env.REDIS_PASSWORD,
  },
  
  jwt: {
    accessSecret: process.env.JWT_ACCESS_SECRET!,
    refreshSecret: process.env.JWT_REFRESH_SECRET!,
    accessExpiry: process.env.JWT_ACCESS_EXPIRY || '15m',
    refreshExpiry: process.env.JWT_REFRESH_EXPIRY || '7d',
  },
  
  api: {
    keyS alt: process.env.API_KEY_SALT!,
    hmacSecret: process.env.HMAC_SECRET!,
  },
  
  cloudflare: {
    apiToken: process.env.CLOUDFLARE_API_TOKEN!,
    accountId: process.env.CLOUDFLARE_ACCOUNT_ID!,
    zoneId: process.env.CLOUDFLARE_ZONE_ID!,
    workerUrl: process.env.CLOUDFLARE_WORKER_URL!,
  },
  
  stripe: {
    secretKey: process.env.STRIPE_SECRET_KEY!,
    webhookSecret: process.env.STRIPE_WEBHOOK_SECRET!,
    publishableKey: process.env.STRIPE_PUBLISHABLE_KEY!,
  },
  
  botDetection: {
    maxmindLicenseKey: process.env.MAXMIND_LICENSE_KEY,
    ipinfoToken: process.env.IPINFO_TOKEN,
    userstackApiKey: process.env.USERSTACK_API_KEY,
  },
  
  email: {
    host: process.env.SMTP_HOST!,
    port: parseInt(process.env.SMTP_PORT || '587', 10),
    user: process.env.SMTP_USER!,
    pass: process.env.SMTP_PASS!,
    from: process.env.EMAIL_FROM!,
  },
  
  cors: {
    origins: (process.env.CORS_ORIGIN || 'http://localhost:3000').split(','),
  },
  
  security: {
    ipWhitelist: process.env.IP_WHITELIST?.split(',') || [],
    rateLimitWindow: parseInt(process.env.RATE_LIMIT_WINDOW || '15', 10),
    rateLimitMax: parseInt(process.env.RATE_LIMIT_MAX || '100', 10),
  },
  
  wordpress: {
    pluginSecret: process.env.WP_PLUGIN_SECRET!,
    webhookUrl: process.env.WP_WEBHOOK_URL,
  },
  
  monitoring: {
    sentryDsn: process.env.SENTRY_DSN,
    logLevel: process.env.LOG_LEVEL || 'info',
  },
};

// Validate required configurations
const requiredEnvVars = [
  'DATABASE_URL',
  'JWT_ACCESS_SECRET',
  'JWT_REFRESH_SECRET',
  'API_KEY_SALT',
  'HMAC_SECRET',
];

for (const envVar of requiredEnvVars) {
  if (!process.env[envVar]) {
    throw new Error(`Missing required environment variable: ${envVar}`);
  }
}
