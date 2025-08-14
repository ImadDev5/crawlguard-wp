import UAParser from 'ua-parser-js';
import axios from 'axios';
import crypto from 'crypto';
import { prisma } from '../utils/prisma';
import { redis } from '../utils/redis';
import { config } from '../config';
import { logger } from '../utils/logger';

interface DetectionResult {
  isBot: boolean;
  botType?: string;
  confidence: number;
  action: 'ALLOW' | 'BLOCK' | 'CHALLENGE' | 'MONITOR';
  details: {
    userAgent?: any;
    ipInfo?: any;
    tlsFingerprint?: string;
    httpFingerprint?: string;
    behaviorScore?: number;
    rateLimitExceeded?: boolean;
  };
}

interface RequestInfo {
  ip: string;
  userAgent: string;
  headers: Record<string, string | string[]>;
  method: string;
  path: string;
  tlsFingerprint?: string;
  userId?: string;
  siteId?: string;
}

export class BotDetectionService {
  private knownBots = new Map<string, { name: string; type: string; malicious: boolean }>();
  private ipCache = new Map<string, any>();
  private userAgentParser: UAParser;

  constructor() {
    this.userAgentParser = new UAParser();
    this.initializeBotSignatures();
  }

  /**
   * Initialize known bot signatures
   */
  private initializeBotSignatures(): void {
    // AI Bots & Crawlers
    const aiBots = [
      { pattern: /GPTBot/i, name: 'OpenAI GPTBot', type: 'AI_CRAWLER', malicious: false },
      { pattern: /ChatGPT/i, name: 'ChatGPT', type: 'AI_CRAWLER', malicious: false },
      { pattern: /Claude-Web/i, name: 'Claude Web', type: 'AI_CRAWLER', malicious: false },
      { pattern: /Anthropic/i, name: 'Anthropic', type: 'AI_CRAWLER', malicious: false },
      { pattern: /CCBot/i, name: 'Common Crawl', type: 'AI_CRAWLER', malicious: false },
      { pattern: /PerplexityBot/i, name: 'Perplexity AI', type: 'AI_CRAWLER', malicious: false },
      { pattern: /YouBot/i, name: 'You.com Bot', type: 'AI_CRAWLER', malicious: false },
      { pattern: /Bytespider/i, name: 'ByteDance Spider', type: 'AI_CRAWLER', malicious: false },
    ];

    // Search Engine Bots
    const searchBots = [
      { pattern: /Googlebot/i, name: 'Googlebot', type: 'SEARCH_ENGINE', malicious: false },
      { pattern: /bingbot/i, name: 'Bingbot', type: 'SEARCH_ENGINE', malicious: false },
      { pattern: /Slurp/i, name: 'Yahoo Slurp', type: 'SEARCH_ENGINE', malicious: false },
      { pattern: /DuckDuckBot/i, name: 'DuckDuckGo', type: 'SEARCH_ENGINE', malicious: false },
      { pattern: /Baiduspider/i, name: 'Baidu Spider', type: 'SEARCH_ENGINE', malicious: false },
      { pattern: /YandexBot/i, name: 'Yandex Bot', type: 'SEARCH_ENGINE', malicious: false },
    ];

    // Malicious Bots
    const maliciousBots = [
      { pattern: /scrapy/i, name: 'Scrapy', type: 'SCRAPER', malicious: true },
      { pattern: /python-requests/i, name: 'Python Requests', type: 'SCRAPER', malicious: true },
      { pattern: /curl/i, name: 'cURL', type: 'TOOL', malicious: false },
      { pattern: /wget/i, name: 'wget', type: 'TOOL', malicious: false },
      { pattern: /Go-http-client/i, name: 'Go HTTP Client', type: 'SCRAPER', malicious: true },
      { pattern: /axios/i, name: 'Axios', type: 'LIBRARY', malicious: false },
      { pattern: /node-fetch/i, name: 'Node Fetch', type: 'LIBRARY', malicious: false },
      { pattern: /HeadlessChrome/i, name: 'Headless Chrome', type: 'BROWSER_AUTOMATION', malicious: true },
      { pattern: /PhantomJS/i, name: 'PhantomJS', type: 'BROWSER_AUTOMATION', malicious: true },
      { pattern: /Selenium/i, name: 'Selenium', type: 'BROWSER_AUTOMATION', malicious: true },
      { pattern: /Puppeteer/i, name: 'Puppeteer', type: 'BROWSER_AUTOMATION', malicious: true },
    ];

    [...aiBots, ...searchBots, ...maliciousBots].forEach(bot => {
      this.knownBots.set(bot.pattern.source, {
        name: bot.name,
        type: bot.type,
        malicious: bot.malicious,
      });
    });
  }

  /**
   * Main detection method
   */
  async detect(requestInfo: RequestInfo): Promise<DetectionResult> {
    const detectionLayers = await Promise.all([
      this.detectByUserAgent(requestInfo.userAgent),
      this.detectByIP(requestInfo.ip),
      this.detectByHeaders(requestInfo.headers),
      this.detectByBehavior(requestInfo),
      this.detectByFingerprint(requestInfo),
    ]);

    // Aggregate detection results
    const aggregatedResult = this.aggregateDetectionResults(detectionLayers);

    // Log detection
    await this.logDetection(requestInfo, aggregatedResult);

    return aggregatedResult;
  }

  /**
   * User Agent based detection
   */
  private async detectByUserAgent(userAgent: string): Promise<Partial<DetectionResult>> {
    const ua = this.userAgentParser.setUA(userAgent).getResult();
    
    // Check against known bot patterns
    for (const [pattern, botInfo] of this.knownBots.entries()) {
      if (new RegExp(pattern).test(userAgent)) {
        return {
          isBot: true,
          botType: botInfo.name,
          confidence: 0.95,
          action: botInfo.malicious ? 'BLOCK' : 'MONITOR',
          details: { userAgent: ua },
        };
      }
    }

    // Check for missing or suspicious user agents
    if (!userAgent || userAgent.length < 10) {
      return {
        isBot: true,
        confidence: 0.8,
        action: 'CHALLENGE',
        details: { userAgent: 'Missing or invalid user agent' },
      };
    }

    // Check for browser inconsistencies
    if (ua.browser.name && !ua.browser.version) {
      return {
        isBot: true,
        confidence: 0.7,
        action: 'CHALLENGE',
        details: { userAgent: ua },
      };
    }

    return {
      isBot: false,
      confidence: 0.1,
      action: 'ALLOW',
      details: { userAgent: ua },
    };
  }

  /**
   * IP-based detection
   */
  private async detectByIP(ip: string): Promise<Partial<DetectionResult>> {
    try {
      // Check cache first
      const cacheKey = `ip:${ip}`;
      const cached = await redis.get(cacheKey);
      if (cached) {
        const ipInfo = JSON.parse(cached);
        return this.evaluateIPInfo(ipInfo);
      }

      // Use IP info service
      let ipInfo = null;
      if (config.botDetection.ipinfoToken) {
        const response = await axios.get(`https://ipinfo.io/${ip}?token=${config.botDetection.ipinfoToken}`);
        ipInfo = response.data;
        
        // Cache for 1 hour
        await redis.setex(cacheKey, 3600, JSON.stringify(ipInfo));
      }

      return this.evaluateIPInfo(ipInfo);
    } catch (error) {
      logger.error('IP detection error:', error);
      return {
        isBot: false,
        confidence: 0,
        action: 'ALLOW',
      };
    }
  }

  /**
   * Evaluate IP information
   */
  private evaluateIPInfo(ipInfo: any): Partial<DetectionResult> {
    if (!ipInfo) {
      return {
        isBot: false,
        confidence: 0,
        action: 'ALLOW',
      };
    }

    // Check for datacenter/hosting provider
    const hostingProviders = ['amazon', 'google', 'microsoft', 'digitalocean', 'linode', 'ovh', 'hetzner'];
    const isDatacenter = ipInfo.org && hostingProviders.some(provider => 
      ipInfo.org.toLowerCase().includes(provider)
    );

    // Check for VPN/Proxy
    const isVpnProxy = ipInfo.privacy?.vpn || ipInfo.privacy?.proxy;

    if (isDatacenter || isVpnProxy) {
      return {
        isBot: true,
        confidence: 0.85,
        action: 'CHALLENGE',
        details: { ipInfo },
      };
    }

    return {
      isBot: false,
      confidence: 0.2,
      action: 'ALLOW',
      details: { ipInfo },
    };
  }

  /**
   * Header-based detection
   */
  private async detectByHeaders(headers: Record<string, string | string[]>): Promise<Partial<DetectionResult>> {
    const suspiciousHeaders = [];
    
    // Check for missing standard browser headers
    const requiredHeaders = ['accept', 'accept-language', 'accept-encoding'];
    for (const header of requiredHeaders) {
      if (!headers[header]) {
        suspiciousHeaders.push(`Missing ${header}`);
      }
    }

    // Check for bot-specific headers
    const botHeaders = ['x-forwarded-for', 'x-real-ip', 'cf-connecting-ip'];
    const hasMultipleProxyHeaders = botHeaders.filter(h => headers[h]).length > 2;
    if (hasMultipleProxyHeaders) {
      suspiciousHeaders.push('Multiple proxy headers');
    }

    // Check for automation tools headers
    if (headers['selenium'] || headers['puppeteer'] || headers['playwright']) {
      return {
        isBot: true,
        botType: 'AUTOMATION_TOOL',
        confidence: 1.0,
        action: 'BLOCK',
      };
    }

    if (suspiciousHeaders.length > 0) {
      return {
        isBot: true,
        confidence: Math.min(0.3 * suspiciousHeaders.length, 0.9),
        action: suspiciousHeaders.length > 2 ? 'CHALLENGE' : 'MONITOR',
      };
    }

    return {
      isBot: false,
      confidence: 0.1,
      action: 'ALLOW',
    };
  }

  /**
   * Behavior-based detection
   */
  private async detectByBehavior(requestInfo: RequestInfo): Promise<Partial<DetectionResult>> {
    const { ip, userId, siteId } = requestInfo;
    const now = Date.now();
    
    // Check request rate
    const rateLimitKey = `rate:${ip}`;
    const requestCount = await redis.incr(rateLimitKey);
    
    if (requestCount === 1) {
      await redis.expire(rateLimitKey, 60); // 1 minute window
    }

    // Check for excessive requests
    if (requestCount > 60) { // More than 60 requests per minute
      return {
        isBot: true,
        confidence: 0.9,
        action: 'BLOCK',
        details: { rateLimitExceeded: true },
      };
    }

    // Check request patterns
    const patternKey = `pattern:${ip}`;
    const pattern = await redis.get(patternKey);
    
    if (pattern) {
      const patternData = JSON.parse(pattern);
      const timeDiff = now - patternData.lastRequest;
      
      // Consistent timing patterns (likely bot)
      if (timeDiff > 0 && timeDiff < 100) { // Requests within 100ms
        patternData.fastRequests++;
        if (patternData.fastRequests > 10) {
          return {
            isBot: true,
            confidence: 0.85,
            action: 'CHALLENGE',
            details: { behaviorScore: patternData.fastRequests },
          };
        }
      }
      
      patternData.lastRequest = now;
      await redis.setex(patternKey, 300, JSON.stringify(patternData));
    } else {
      await redis.setex(patternKey, 300, JSON.stringify({
        firstRequest: now,
        lastRequest: now,
        fastRequests: 0,
      }));
    }

    return {
      isBot: false,
      confidence: 0.2,
      action: 'ALLOW',
    };
  }

  /**
   * TLS/HTTP fingerprinting
   */
  private async detectByFingerprint(requestInfo: RequestInfo): Promise<Partial<DetectionResult>> {
    const { headers, tlsFingerprint } = requestInfo;
    
    // Generate HTTP fingerprint
    const httpFingerprint = this.generateHttpFingerprint(headers);
    
    // Check against known bot fingerprints
    const knownBotFingerprints = await redis.smembers('bot:fingerprints');
    
    if (knownBotFingerprints.includes(httpFingerprint)) {
      return {
        isBot: true,
        confidence: 0.95,
        action: 'BLOCK',
        details: { httpFingerprint, tlsFingerprint },
      };
    }

    // Check TLS fingerprint if available
    if (tlsFingerprint) {
      const tlsBotFingerprints = await redis.smembers('bot:tls:fingerprints');
      if (tlsBotFingerprints.includes(tlsFingerprint)) {
        return {
          isBot: true,
          confidence: 0.9,
          action: 'BLOCK',
          details: { httpFingerprint, tlsFingerprint },
        };
      }
    }

    return {
      isBot: false,
      confidence: 0.1,
      action: 'ALLOW',
      details: { httpFingerprint, tlsFingerprint },
    };
  }

  /**
   * Generate HTTP fingerprint
   */
  private generateHttpFingerprint(headers: Record<string, string | string[]>): string {
    const significantHeaders = [
      'accept',
      'accept-encoding',
      'accept-language',
      'cache-control',
      'connection',
      'dnt',
      'upgrade-insecure-requests',
      'user-agent',
    ];

    const fingerprint = significantHeaders
      .map(h => `${h}:${headers[h] || ''}`)
      .join('|');

    return crypto.createHash('sha256').update(fingerprint).digest('hex');
  }

  /**
   * Aggregate detection results
   */
  private aggregateDetectionResults(results: Partial<DetectionResult>[]): DetectionResult {
    let totalConfidence = 0;
    let botCount = 0;
    let highestConfidence = 0;
    let finalAction: DetectionResult['action'] = 'ALLOW';
    let botType: string | undefined;
    const details: any = {};

    for (const result of results) {
      if (result.isBot) {
        botCount++;
        totalConfidence += result.confidence || 0;
        if ((result.confidence || 0) > highestConfidence) {
          highestConfidence = result.confidence || 0;
          botType = result.botType;
          finalAction = result.action || 'MONITOR';
        }
      }
      Object.assign(details, result.details);
    }

    const averageConfidence = botCount > 0 ? totalConfidence / botCount : 0;
    const isBot = averageConfidence > 0.5 || highestConfidence > 0.8;

    // Determine final action based on confidence
    if (isBot) {
      if (highestConfidence > 0.9) {
        finalAction = 'BLOCK';
      } else if (highestConfidence > 0.7) {
        finalAction = 'CHALLENGE';
      } else {
        finalAction = 'MONITOR';
      }
    }

    return {
      isBot,
      botType,
      confidence: Math.min(highestConfidence, 1.0),
      action: finalAction,
      details,
    };
  }

  /**
   * Log detection result
   */
  private async logDetection(requestInfo: RequestInfo, result: DetectionResult): Promise<void> {
    try {
      await prisma.detection.create({
        data: {
          userId: requestInfo.userId,
          siteId: requestInfo.siteId,
          ip: requestInfo.ip,
          userAgent: requestInfo.userAgent,
          method: requestInfo.method,
          path: requestInfo.path,
          headers: requestInfo.headers,
          isBot: result.isBot,
          botType: result.botType,
          confidence: result.confidence,
          action: result.action,
          tlsFingerprint: result.details.tlsFingerprint,
          httpFingerprint: result.details.httpFingerprint,
          ipInfo: result.details.ipInfo,
          blocked: result.action === 'BLOCK',
          challenged: result.action === 'CHALLENGE',
          allowed: result.action === 'ALLOW',
        },
      });

      // Update analytics cache
      const analyticsKey = `analytics:${requestInfo.siteId || 'global'}:${new Date().toISOString().split('T')[0]}`;
      await redis.hincrby(analyticsKey, result.isBot ? 'bots' : 'humans', 1);
      if (result.action === 'BLOCK') {
        await redis.hincrby(analyticsKey, 'blocked', 1);
      }
      await redis.expire(analyticsKey, 86400 * 7); // Keep for 7 days
    } catch (error) {
      logger.error('Failed to log detection:', error);
    }
  }

  /**
   * Challenge bot with proof of work
   */
  async generateChallenge(ip: string): Promise<{ challenge: string; difficulty: number }> {
    const challenge = crypto.randomBytes(32).toString('hex');
    const difficulty = 4; // Number of leading zeros required
    
    // Store challenge
    await redis.setex(`challenge:${ip}`, 300, JSON.stringify({ challenge, difficulty }));
    
    return { challenge, difficulty };
  }

  /**
   * Verify proof of work challenge
   */
  async verifyChallenge(ip: string, solution: string): Promise<boolean> {
    const stored = await redis.get(`challenge:${ip}`);
    if (!stored) return false;
    
    const { challenge, difficulty } = JSON.parse(stored);
    const hash = crypto.createHash('sha256').update(challenge + solution).digest('hex');
    const leadingZeros = '0'.repeat(difficulty);
    
    if (hash.startsWith(leadingZeros)) {
      await redis.del(`challenge:${ip}`);
      return true;
    }
    
    return false;
  }
}

export default new BotDetectionService();
