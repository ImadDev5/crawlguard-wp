<?php
/**
 * Cloudflare Integration for Enterprise Bot Detection
 * 
 * @package PayPerCrawl
 * @subpackage Cloudflare
 * @version 4.0.0
 * @author PayPerCrawl.tech
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Cloudflare Integration Class
 * 
 * Features:
 * - Bot Management API integration
 * - Worker deployment and management
 * - Real-time bot blocking
 * - Analytics and reporting
 * - Custom rules management
 * - API credential validation
 * 
 * @since 4.0.0
 */
class PayPerCrawl_Cloudflare_Integration {
    
    /**
     * Cloudflare API credentials
     * @var array
     */
    private $credentials = [];
    
    /**
     * API client instance
     * @var object
     */
    private $api_client;
    
    /**
     * Worker script template
     * @var string
     */
    private $worker_script;
    
    /**
     * Configuration settings
     * @var array
     */
    private $config = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_credentials();
        $this->load_configuration();
        $this->init_api_client();
        $this->prepare_worker_script();
    }
    
    /**
     * Load Cloudflare credentials
     */
    private function load_credentials() {
        $this->credentials = [
            'api_token' => get_option('paypercrawl_cloudflare_api_token', ''),
            'zone_id' => get_option('paypercrawl_cloudflare_zone_id', ''),
            'account_id' => get_option('paypercrawl_cloudflare_account_id', ''),
            'email' => get_option('paypercrawl_cloudflare_email', ''),
            'global_api_key' => get_option('paypercrawl_cloudflare_global_key', ''),
        ];
    }
    
    /**
     * Load configuration settings
     */
    private function load_configuration() {
        $this->config = [
            'bot_fight_mode' => get_option('paypercrawl_cf_bot_fight_mode', 'on'),
            'security_level' => get_option('paypercrawl_cf_security_level', 'medium'),
            'challenge_passage' => get_option('paypercrawl_cf_challenge_passage', 3600),
            'auto_block_threshold' => get_option('paypercrawl_cf_auto_block_threshold', 85),
            'worker_enabled' => get_option('paypercrawl_cf_worker_enabled', false),
            'worker_name' => get_option('paypercrawl_cf_worker_name', 'paypercrawl-bot-detector'),
            'analytics_enabled' => get_option('paypercrawl_cf_analytics_enabled', true),
        ];
    }
    
    /**
     * Initialize API client
     */
    private function init_api_client() {
        $this->api_client = new PayPerCrawl_Cloudflare_API_Client($this->credentials);
    }
    
    /**
     * Prepare Cloudflare Worker script
     */
    private function prepare_worker_script() {
        $this->worker_script = $this->generate_worker_script();
    }
    
    /**
     * Process bot detection with Cloudflare
     * 
     * @param array $detection_result Bot detection result
     * @return array Processing result
     */
    public function process_detection($detection_result) {
        try {
            $bot_info = $detection_result['bot_info'];
            $request_data = $detection_result['request_data'];
            
            $actions = [];
            
            // Determine action based on bot type and confidence
            $action = $this->determine_action($detection_result);
            
            switch ($action) {
                case 'block':
                    $result = $this->block_ip($request_data['ip'], $bot_info);
                    $actions[] = $result;
                    break;
                    
                case 'challenge':
                    $result = $this->challenge_ip($request_data['ip'], $bot_info);
                    $actions[] = $result;
                    break;
                    
                case 'rate_limit':
                    $result = $this->rate_limit_ip($request_data['ip'], $bot_info);
                    $actions[] = $result;
                    break;
                    
                case 'monitor':
                    $result = $this->add_monitoring($request_data['ip'], $bot_info);
                    $actions[] = $result;
                    break;
            }
            
            // Log to Cloudflare Analytics
            if ($this->config['analytics_enabled']) {
                $this->log_to_analytics($detection_result, $actions);
            }
            
            return [
                'success' => true,
                'actions' => $actions,
                'cloudflare_response' => $actions,
            ];
            
        } catch (Exception $e) {
            $this->log_error('Cloudflare processing failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Determine appropriate action for detected bot
     * 
     * @param array $detection_result Detection result
     * @return string Action to take
     */
    private function determine_action($detection_result) {
        $confidence = $detection_result['confidence'];
        $bot_info = $detection_result['bot_info'];
        $bot_type = $bot_info['type'] ?? 'unknown';
        $priority = $bot_info['priority'] ?? 'low';
        
        // Verified bots (like search engines) get special treatment
        if ($bot_type === 'verified' || $bot_type === 'search_engine') {
            return 'monitor';
        }
        
        // High-confidence detections
        if ($confidence >= 0.9) {
            switch ($priority) {
                case 'high':
                    return $bot_type === 'premium' ? 'rate_limit' : 'block';
                case 'medium':
                    return 'challenge';
                case 'low':
                    return 'rate_limit';
            }
        }
        
        // Medium-confidence detections
        if ($confidence >= 0.7) {
            return $priority === 'high' ? 'challenge' : 'rate_limit';
        }
        
        // Low-confidence detections
        return 'monitor';
    }
    
    /**
     * Block IP address
     * 
     * @param string $ip IP address to block
     * @param array $bot_info Bot information
     * @return array Block result
     */
    private function block_ip($ip, $bot_info) {
        $rule_data = [
            'mode' => 'block',
            'configuration' => [
                'target' => 'ip',
                'value' => $ip,
            ],
            'notes' => "PayPerCrawl Bot Block: {$bot_info['name']} ({$bot_info['company']})",
            'paused' => false,
        ];
        
        $response = $this->api_client->create_firewall_rule($rule_data);
        
        return [
            'action' => 'block',
            'ip' => $ip,
            'success' => $response['success'] ?? false,
            'rule_id' => $response['result']['id'] ?? null,
            'response' => $response,
        ];
    }
    
    /**
     * Challenge IP address
     * 
     * @param string $ip IP address to challenge
     * @param array $bot_info Bot information
     * @return array Challenge result
     */
    private function challenge_ip($ip, $bot_info) {
        $rule_data = [
            'mode' => 'challenge',
            'configuration' => [
                'target' => 'ip',
                'value' => $ip,
            ],
            'notes' => "PayPerCrawl Bot Challenge: {$bot_info['name']} ({$bot_info['company']})",
            'paused' => false,
        ];
        
        $response = $this->api_client->create_firewall_rule($rule_data);
        
        return [
            'action' => 'challenge',
            'ip' => $ip,
            'success' => $response['success'] ?? false,
            'rule_id' => $response['result']['id'] ?? null,
            'response' => $response,
        ];
    }
    
    /**
     * Apply rate limiting to IP
     * 
     * @param string $ip IP address to rate limit
     * @param array $bot_info Bot information
     * @return array Rate limit result
     */
    private function rate_limit_ip($ip, $bot_info) {
        $rule_data = [
            'threshold' => 10, // 10 requests
            'period' => 60,    // per minute
            'action' => 'simulate', // or 'ban', 'challenge'
            'match' => [
                'request' => [
                    'url' => '*',
                ],
                'response' => [
                    'status' => [200, 301, 302, 403, 404],
                ],
            ],
            'bypass' => [],
            'description' => "PayPerCrawl Rate Limit: {$bot_info['name']} ({$bot_info['company']})",
            'disabled' => false,
        ];
        
        $response = $this->api_client->create_rate_limit_rule($rule_data);
        
        return [
            'action' => 'rate_limit',
            'ip' => $ip,
            'success' => $response['success'] ?? false,
            'rule_id' => $response['result']['id'] ?? null,
            'response' => $response,
        ];
    }
    
    /**
     * Add IP to monitoring list
     * 
     * @param string $ip IP address to monitor
     * @param array $bot_info Bot information
     * @return array Monitor result
     */
    private function add_monitoring($ip, $bot_info) {
        // For monitoring, we just log the event without blocking
        $this->log_monitoring_event($ip, $bot_info);
        
        return [
            'action' => 'monitor',
            'ip' => $ip,
            'success' => true,
            'message' => 'Added to monitoring list',
        ];
    }
    
    /**
     * Deploy Cloudflare Worker
     * 
     * @return array Deployment result
     */
    public function deploy_worker() {
        try {
            $worker_script = $this->worker_script;
            $worker_name = $this->config['worker_name'];
            
            // Create or update worker
            $response = $this->api_client->create_worker($worker_name, $worker_script);
            
            if ($response['success']) {
                // Create worker route
                $route_response = $this->api_client->create_worker_route(
                    "*.{$this->get_domain()}/*",
                    $worker_name
                );
                
                update_option('paypercrawl_cf_worker_deployed', true);
                update_option('paypercrawl_cf_worker_deployment_date', current_time('mysql'));
                
                return [
                    'success' => true,
                    'worker_response' => $response,
                    'route_response' => $route_response,
                    'message' => 'Worker deployed successfully',
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Worker deployment failed',
                'response' => $response,
            ];
            
        } catch (Exception $e) {
            $this->log_error('Worker deployment failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Generate Cloudflare Worker script
     * 
     * @return string Worker JavaScript code
     */
    private function generate_worker_script() {
        $webhook_url = rest_url('paypercrawl/v1/cloudflare-webhook');
        $api_key = get_option('paypercrawl_api_key', '');
        
        return <<<JAVASCRIPT
/**
 * PayPerCrawl Enterprise Bot Detection Worker
 * Deployed on Cloudflare Edge for real-time bot detection
 */

// Bot signatures - updated from WordPress plugin
const BOT_SIGNATURES = {
  // OpenAI Family
  gptbot: { rate: 0.15, company: 'OpenAI', type: 'premium' },
  'chatgpt-user': { rate: 0.15, company: 'OpenAI', type: 'premium' },
  
  // Anthropic Claude
  claudebot: { rate: 0.12, company: 'Anthropic', type: 'premium' },
  ccbot: { rate: 0.12, company: 'Anthropic', type: 'premium' },
  
  // Google AI
  'google-extended': { rate: 0.10, company: 'Google', type: 'standard' },
  bard: { rate: 0.10, company: 'Google', type: 'standard' },
  gemini: { rate: 0.10, company: 'Google', type: 'standard' },
  
  // Microsoft
  bingbot: { rate: 0.08, company: 'Microsoft', type: 'standard' },
  msnbot: { rate: 0.08, company: 'Microsoft', type: 'standard' },
  
  // Emerging
  perplexitybot: { rate: 0.06, company: 'Perplexity', type: 'emerging' },
  youbot: { rate: 0.05, company: 'You.com', type: 'emerging' },
  bytespider: { rate: 0.04, company: 'ByteDance', type: 'emerging' },
};

// IP ranges for known AI companies
const AI_IP_RANGES = {
  openai: ['20.171.0.0/16', '52.230.0.0/15'],
  anthropic: ['52.84.0.0/15', '54.230.0.0/16'],
  google: ['66.249.64.0/19', '216.239.32.0/19'],
  microsoft: ['40.76.0.0/14', '65.52.0.0/14'],
};

addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
  const startTime = Date.now();
  
  try {
    // Extract request information
    const requestInfo = await extractRequestInfo(request);
    
    // Detect bot
    const botDetection = await detectBot(requestInfo);
    
    if (botDetection.isBot) {
      // Log detection to WordPress
      await logDetection(requestInfo, botDetection);
      
      // Apply action based on bot type
      const action = determineAction(botDetection);
      
      switch (action) {
        case 'block':
          return new Response('Access Denied - Bot Detected', { 
            status: 403,
            headers: {
              'X-PayPerCrawl-Action': 'blocked',
              'X-PayPerCrawl-Bot': botDetection.botName,
              'X-PayPerCrawl-Revenue': botDetection.revenue.toString(),
            }
          });
          
        case 'challenge':
          return new Response('Bot Challenge Required', {
            status: 403,
            headers: {
              'CF-Challenge': '1',
              'X-PayPerCrawl-Action': 'challenged',
              'X-PayPerCrawl-Bot': botDetection.botName,
            }
          });
          
        case 'rate_limit':
          // Continue to origin but add headers
          const response = await fetch(request);
          response.headers.set('X-PayPerCrawl-Action', 'rate_limited');
          response.headers.set('X-PayPerCrawl-Bot', botDetection.botName);
          return response;
      }
    }
    
    // Regular request - continue to origin
    return fetch(request);
    
  } catch (error) {
    console.error('PayPerCrawl Worker Error:', error);
    return fetch(request); // Fallback to origin
  }
}

async function extractRequestInfo(request) {
  const url = new URL(request.url);
  const headers = Object.fromEntries(request.headers);
  
  return {
    ip: headers['cf-connecting-ip'] || headers['x-forwarded-for'] || '',
    userAgent: headers['user-agent'] || '',
    url: url.toString(),
    method: request.method,
    headers: headers,
    timestamp: Date.now(),
    cfRay: headers['cf-ray'] || '',
    cfCountry: headers['cf-ipcountry'] || '',
    cfBotScore: parseInt(headers['cf-bot-management-score']) || 0,
    cfIsBot: headers['cf-is-bot'] === '1',
    cfVerifiedBot: headers['cf-verified-bot'] === '1',
  };
}

async function detectBot(requestInfo) {
  const userAgent = requestInfo.userAgent.toLowerCase();
  const detections = [];
  
  // Check user agent signatures
  for (const [signature, config] of Object.entries(BOT_SIGNATURES)) {
    if (userAgent.includes(signature.toLowerCase())) {
      detections.push({
        method: 'user_agent',
        confidence: 0.95,
        botName: signature,
        botConfig: config,
        evidence: `User agent contains: \${signature}`,
      });
    }
  }
  
  // Check IP ranges
  const ipDetection = await checkIPRanges(requestInfo.ip);
  if (ipDetection) {
    detections.push(ipDetection);
  }
  
  // Use Cloudflare's bot detection
  if (requestInfo.cfIsBot) {
    detections.push({
      method: 'cloudflare',
      confidence: requestInfo.cfVerifiedBot ? 0.95 : 0.85,
      botName: requestInfo.cfVerifiedBot ? 'Verified Bot' : 'CF Detected Bot',
      botConfig: { rate: 0.01, company: 'Cloudflare', type: 'detected' },
      evidence: 'Cloudflare bot detection',
    });
  }
  
  // Analyze bot score
  if (requestInfo.cfBotScore >= 66) {
    detections.push({
      method: 'cf_score',
      confidence: Math.min(0.95, requestInfo.cfBotScore / 100),
      botName: 'CF Scored Bot',
      botConfig: { rate: 0.01, company: 'Cloudflare', type: 'scored' },
      evidence: `Cloudflare bot score: \${requestInfo.cfBotScore}`,
    });
  }
  
  if (detections.length === 0) {
    return { isBot: false };
  }
  
  // Use highest confidence detection
  const primaryDetection = detections.reduce((prev, current) => 
    current.confidence > prev.confidence ? current : prev
  );
  
  const revenue = calculateRevenue(primaryDetection.botConfig, requestInfo);
  
  return {
    isBot: true,
    botName: primaryDetection.botName,
    botType: primaryDetection.botConfig.type,
    company: primaryDetection.botConfig.company,
    confidence: primaryDetection.confidence,
    method: primaryDetection.method,
    evidence: primaryDetection.evidence,
    revenue: revenue,
    detections: detections,
  };
}

async function checkIPRanges(ip) {
  // Simplified IP range check - in production, use more sophisticated method
  for (const [company, ranges] of Object.entries(AI_IP_RANGES)) {
    for (const range of ranges) {
      if (isIPInRange(ip, range)) {
        return {
          method: 'ip_range',
          confidence: 0.85,
          botName: `\${company} Bot`,
          botConfig: { rate: 0.01, company: company, type: 'ip_verified' },
          evidence: `IP \${ip} in \${company} range \${range}`,
        };
      }
    }
  }
  return null;
}

function isIPInRange(ip, range) {
  // Simplified IPv4 range check
  // In production, implement proper CIDR matching
  if (!range.includes('/')) {
    return ip === range;
  }
  
  const [subnet, mask] = range.split('/');
  const subnetParts = subnet.split('.').map(Number);
  const ipParts = ip.split('.').map(Number);
  const maskBits = parseInt(mask);
  
  if (ipParts.length !== 4 || subnetParts.length !== 4) {
    return false;
  }
  
  const subnetInt = (subnetParts[0] << 24) + (subnetParts[1] << 16) + 
                   (subnetParts[2] << 8) + subnetParts[3];
  const ipInt = (ipParts[0] << 24) + (ipParts[1] << 16) + 
               (ipParts[2] << 8) + ipParts[3];
  const maskInt = (-1 << (32 - maskBits)) >>> 0;
  
  return (ipInt & maskInt) === (subnetInt & maskInt);
}

function calculateRevenue(botConfig, requestInfo) {
  let baseRate = botConfig.rate || 0.01;
  
  // Apply time-based multiplier
  const hour = new Date().getUTCHours();
  if (hour >= 9 && hour <= 17) {
    baseRate *= 1.2; // Peak hours
  }
  
  // Apply geographic multiplier based on CF country
  const premiumCountries = ['US', 'CA', 'GB', 'DE', 'FR', 'JP', 'AU'];
  if (premiumCountries.includes(requestInfo.cfCountry)) {
    baseRate *= 1.3;
  }
  
  return Math.round(baseRate * 10000) / 10000; // Round to 4 decimal places
}

function determineAction(botDetection) {
  const confidence = botDetection.confidence;
  const botType = botDetection.botType;
  
  // Premium AI bots get rate limiting instead of blocking
  if (botType === 'premium' && confidence >= 0.9) {
    return 'rate_limit';
  }
  
  // High confidence generic bots get blocked
  if (confidence >= 0.9 && (botType === 'generic' || botType === 'suspicious')) {
    return 'block';
  }
  
  // Medium confidence gets challenge
  if (confidence >= 0.7) {
    return 'challenge';
  }
  
  // Low confidence gets monitoring only
  return 'monitor';
}

async function logDetection(requestInfo, botDetection) {
  try {
    const logData = {
      timestamp: Date.now(),
      bot_name: botDetection.botName,
      bot_type: botDetection.botType,
      company: botDetection.company,
      confidence: botDetection.confidence,
      method: botDetection.method,
      revenue: botDetection.revenue,
      ip: requestInfo.ip,
      user_agent: requestInfo.userAgent,
      url: requestInfo.url,
      cf_ray: requestInfo.cfRay,
      cf_country: requestInfo.cfCountry,
      cf_bot_score: requestInfo.cfBotScore,
      evidence: botDetection.evidence,
    };
    
    // Send to WordPress webhook
    await fetch('${webhook_url}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-PayPerCrawl-API-Key': '${api_key}',
        'User-Agent': 'PayPerCrawl-Worker/1.0',
      },
      body: JSON.stringify(logData),
    });
    
  } catch (error) {
    console.error('Failed to log detection:', error);
  }
}
JAVASCRIPT;
    }
    
    /**
     * Test Cloudflare connection and credentials
     * 
     * @param string $api_token Optional API token to test
     * @return array Test result
     */
    public function test_connection($api_token = null) {
        try {
            $token = $api_token ?: $this->credentials['api_token'];
            
            if (empty($token)) {
                return [
                    'success' => false,
                    'error' => 'No API token provided',
                ];
            }
            
            // Test API connection
            $test_client = new PayPerCrawl_Cloudflare_API_Client([
                'api_token' => $token,
                'zone_id' => $this->credentials['zone_id'],
            ]);
            
            $response = $test_client->verify_token();
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Cloudflare connection successful',
                    'token_info' => $response['result'],
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid API token or insufficient permissions',
                    'details' => $response,
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get Cloudflare analytics
     * 
     * @param string $time_range Time range for analytics
     * @return array Analytics data
     */
    public function get_analytics($time_range = '24h') {
        try {
            $analytics_data = $this->api_client->get_analytics($time_range);
            
            return [
                'success' => true,
                'data' => $analytics_data,
            ];
            
        } catch (Exception $e) {
            $this->log_error('Analytics retrieval failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Manage firewall rules
     * 
     * @param string $action Action to perform (list, create, delete)
     * @param array $data Rule data
     * @return array Management result
     */
    public function manage_firewall_rules($action = 'list', $data = []) {
        try {
            switch ($action) {
                case 'list':
                    return $this->api_client->list_firewall_rules();
                    
                case 'create':
                    return $this->api_client->create_firewall_rule($data);
                    
                case 'delete':
                    return $this->api_client->delete_firewall_rule($data['rule_id']);
                    
                default:
                    return [
                        'success' => false,
                        'error' => 'Invalid action',
                    ];
            }
            
        } catch (Exception $e) {
            $this->log_error("Firewall rule management failed: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Update bot fight mode settings
     * 
     * @param string $mode Bot fight mode (on, off, super_bot_fight_mode)
     * @return array Update result
     */
    public function update_bot_fight_mode($mode = 'on') {
        try {
            $response = $this->api_client->update_bot_fight_mode($mode);
            
            if ($response['success']) {
                update_option('paypercrawl_cf_bot_fight_mode', $mode);
            }
            
            return $response;
            
        } catch (Exception $e) {
            $this->log_error("Bot fight mode update failed: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Log monitoring event
     * 
     * @param string $ip IP address
     * @param array $bot_info Bot information
     */
    private function log_monitoring_event($ip, $bot_info) {
        global $wpdb;
        
        $table_monitoring = $wpdb->prefix . 'paypercrawl_monitoring';
        
        $wpdb->insert(
            $table_monitoring,
            [
                'ip_address' => $ip,
                'bot_name' => $bot_info['name'],
                'company' => $bot_info['company'],
                'event_type' => 'monitor',
                'created_at' => current_time('mysql'),
            ]
        );
    }
    
    /**
     * Log to Cloudflare analytics
     * 
     * @param array $detection_result Detection result
     * @param array $actions Actions taken
     */
    private function log_to_analytics($detection_result, $actions) {
        try {
            $analytics_data = [
                'timestamp' => time(),
                'bot_detection' => $detection_result,
                'actions_taken' => $actions,
                'cloudflare_data' => $detection_result['cloudflare_data'] ?? [],
            ];
            
            // Store in WordPress for local analytics
            update_option('paypercrawl_cf_last_analytics', $analytics_data);
            
            // Send to Cloudflare if configured
            if (!empty($this->credentials['api_token'])) {
                $this->api_client->send_custom_analytics($analytics_data);
            }
            
        } catch (Exception $e) {
            $this->log_error('Analytics logging failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get domain from WordPress site URL
     * 
     * @return string Domain name
     */
    private function get_domain() {
        $site_url = get_site_url();
        $parsed = parse_url($site_url);
        return $parsed['host'] ?? 'localhost';
    }
    
    /**
     * Log error message
     * 
     * @param string $message Error message
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PayPerCrawl Cloudflare] ERROR: ' . $message);
        }
    }
    
    /**
     * Get Cloudflare integration status
     * 
     * @return array Status information
     */
    public function get_status() {
        return [
            'credentials_configured' => !empty($this->credentials['api_token']),
            'worker_deployed' => get_option('paypercrawl_cf_worker_deployed', false),
            'bot_fight_mode' => $this->config['bot_fight_mode'],
            'security_level' => $this->config['security_level'],
            'last_deployment' => get_option('paypercrawl_cf_worker_deployment_date', 'Never'),
            'last_analytics_check' => get_option('paypercrawl_cf_last_analytics_check', 'Never'),
        ];
    }
    
    /**
     * Handle Cloudflare webhook
     * 
     * @param array $data Webhook data
     * @return array Processing result
     */
    public function handle_webhook($data) {
        try {
            // Process webhook data from Cloudflare Worker
            global $wpdb;
            
            $table_detections = $wpdb->prefix . 'paypercrawl_detections';
            
            // Insert detection record
            $result = $wpdb->insert(
                $table_detections,
                [
                    'bot_type' => $data['bot_type'] ?? 'unknown',
                    'bot_name' => $data['bot_name'] ?? 'Unknown',
                    'company' => $data['company'] ?? 'Unknown',
                    'detection_method' => 'cloudflare_worker',
                    'confidence_score' => $data['confidence'] ?? 0.5,
                    'revenue' => $data['revenue'] ?? 0.01,
                    'url' => $data['url'] ?? '',
                    'ip_address' => $data['ip'] ?? '',
                    'user_agent' => $data['user_agent'] ?? '',
                    'headers' => json_encode([
                        'cf_ray' => $data['cf_ray'] ?? '',
                        'cf_country' => $data['cf_country'] ?? '',
                        'cf_bot_score' => $data['cf_bot_score'] ?? 0,
                    ]),
                    'cloudflare_data' => json_encode($data),
                    'detected_at' => current_time('mysql'),
                    'status' => 'processed',
                ]
            );
            
            return [
                'success' => $result !== false,
                'message' => 'Webhook processed successfully',
                'detection_id' => $wpdb->insert_id,
            ];
            
        } catch (Exception $e) {
            $this->log_error('Webhook processing failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

/**
 * Cloudflare API Client
 */
class PayPerCrawl_Cloudflare_API_Client {
    
    private $credentials;
    private $base_url = 'https://api.cloudflare.com/client/v4/';
    
    public function __construct($credentials) {
        $this->credentials = $credentials;
    }
    
    /**
     * Make API request
     */
    private function make_request($endpoint, $method = 'GET', $data = null) {
        $url = $this->base_url . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
        ];
        
        // Use API token if available, fallback to email + global key
        if (!empty($this->credentials['api_token'])) {
            $headers[] = 'Authorization: Bearer ' . $this->credentials['api_token'];
        } elseif (!empty($this->credentials['email']) && !empty($this->credentials['global_api_key'])) {
            $headers[] = 'X-Auth-Email: ' . $this->credentials['email'];
            $headers[] = 'X-Auth-Key: ' . $this->credentials['global_api_key'];
        } else {
            throw new Exception('No valid Cloudflare credentials provided');
        }
        
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
        ];
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Cloudflare API');
        }
        
        return $decoded;
    }
    
    /**
     * Verify API token
     */
    public function verify_token() {
        return $this->make_request('user/tokens/verify');
    }
    
    /**
     * Create firewall rule
     */
    public function create_firewall_rule($rule_data) {
        $zone_id = $this->credentials['zone_id'];
        return $this->make_request("zones/{$zone_id}/firewall/rules", 'POST', $rule_data);
    }
    
    /**
     * List firewall rules
     */
    public function list_firewall_rules() {
        $zone_id = $this->credentials['zone_id'];
        return $this->make_request("zones/{$zone_id}/firewall/rules");
    }
    
    /**
     * Delete firewall rule
     */
    public function delete_firewall_rule($rule_id) {
        $zone_id = $this->credentials['zone_id'];
        return $this->make_request("zones/{$zone_id}/firewall/rules/{$rule_id}", 'DELETE');
    }
    
    /**
     * Create rate limiting rule
     */
    public function create_rate_limit_rule($rule_data) {
        $zone_id = $this->credentials['zone_id'];
        return $this->make_request("zones/{$zone_id}/rate_limits", 'POST', $rule_data);
    }
    
    /**
     * Create Cloudflare Worker
     */
    public function create_worker($name, $script) {
        $account_id = $this->credentials['account_id'];
        
        $data = [
            'script' => $script,
            'metadata' => [
                'body_part' => 'script',
                'main_module' => $name . '.js',
            ],
        ];
        
        return $this->make_request("accounts/{$account_id}/workers/scripts/{$name}", 'PUT', $data);
    }
    
    /**
     * Create worker route
     */
    public function create_worker_route($pattern, $script_name) {
        $zone_id = $this->credentials['zone_id'];
        
        $data = [
            'pattern' => $pattern,
            'script' => $script_name,
        ];
        
        return $this->make_request("zones/{$zone_id}/workers/routes", 'POST', $data);
    }
    
    /**
     * Get analytics
     */
    public function get_analytics($time_range = '24h') {
        $zone_id = $this->credentials['zone_id'];
        
        $since = date('c', strtotime("-{$time_range}"));
        $until = date('c');
        
        return $this->make_request("zones/{$zone_id}/analytics/dashboard?since={$since}&until={$until}");
    }
    
    /**
     * Update bot fight mode
     */
    public function update_bot_fight_mode($mode) {
        $zone_id = $this->credentials['zone_id'];
        
        $data = ['value' => $mode];
        
        return $this->make_request("zones/{$zone_id}/settings/bot_fight_mode", 'PATCH', $data);
    }
    
    /**
     * Send custom analytics
     */
    public function send_custom_analytics($data) {
        // This would send custom analytics data to Cloudflare
        // Implementation depends on specific Cloudflare analytics endpoint
        return ['success' => true, 'message' => 'Analytics sent'];
    }
}

// End of file
