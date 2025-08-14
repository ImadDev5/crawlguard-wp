<?php
/**
 * Cloudflare Workers Integration for PayPerCrawl Enterprise
 * 
 * Edge computing integration for global bot detection performance
 * 
 * @package PayPerCrawl_Enterprise
 * @subpackage Cloudflare
 * @version 6.0.0
 * @author PayPerCrawl.tech
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Cloudflare Integration with Workers deployment
 */
class PayPerCrawl_Cloudflare_Integration {
    
    /**
     * Cloudflare API base URL
     */
    private $api_base = 'https://api.cloudflare.com/client/v4/';
    
    /**
     * Cloudflare credentials
     */
    private $api_token;
    private $zone_id;
    private $account_id;
    
    /**
     * Worker script name
     */
    private $worker_name = 'paypercrawl-enterprise-detector';
    
    /**
     * Initialize Cloudflare integration
     */
    public function __construct() {
        $this->api_token = get_option('paypercrawl_cloudflare_api_token', '');
        $this->zone_id = get_option('paypercrawl_cloudflare_zone_id', '');
        $this->account_id = get_option('paypercrawl_cloudflare_account_id', '');
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('paypercrawl_deploy_worker', array($this, 'deploy_worker'));
        add_action('paypercrawl_update_worker_config', array($this, 'update_worker_config'));
    }
    
    /**
     * Test Cloudflare connection
     */
    public function test_connection() {
        if (empty($this->api_token) || empty($this->zone_id)) {
            return array(
                'success' => false,
                'message' => 'Cloudflare credentials not configured'
            );
        }
        
        $response = $this->make_cloudflare_request('GET', "zones/{$this->zone_id}");
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['success']) && $data['success']) {
            return array(
                'success' => true,
                'message' => 'Cloudflare connection successful',
                'zone_name' => $data['result']['name']
            );
        }
        
        return array(
            'success' => false,
            'message' => $data['errors'][0]['message'] ?? 'Unknown Cloudflare error'
        );
    }
    
    /**
     * Deploy PayPerCrawl Worker to Cloudflare
     */
    public function deploy_worker() {
        if (!$this->validate_credentials()) {
            return new WP_Error('invalid_credentials', 'Invalid Cloudflare credentials');
        }
        
        $worker_script = $this->get_worker_script();
        
        // Upload worker script
        $response = $this->upload_worker_script($worker_script);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Create route for the worker
        $route_response = $this->create_worker_route();
        
        if (is_wp_error($route_response)) {
            return $route_response;
        }
        
        // Update deployment status
        update_option('paypercrawl_worker_deployed', true);
        update_option('paypercrawl_worker_deployed_at', current_time('timestamp'));
        
        return array(
            'success' => true,
            'message' => 'Worker deployed successfully',
            'worker_url' => $this->get_worker_url()
        );
    }
    
    /**
     * Get PayPerCrawl Worker JavaScript code
     */
    private function get_worker_script() {
        return "
// PayPerCrawl Enterprise Worker v6.0.0
// Advanced AI Bot Detection at the Edge

class PayPerCrawlDetector {
    constructor() {
        this.botSignatures = " . wp_json_encode($this->get_bot_signatures()) . ";
        this.ipRanges = " . wp_json_encode($this->get_ip_ranges()) . ";
        this.mlModel = new SimpleBotMLModel();
    }
    
    async detectBot(request) {
        const userAgent = request.headers.get('user-agent') || '';
        const ip = request.headers.get('cf-connecting-ip') || '';
        const headers = Object.fromEntries(request.headers);
        
        let confidence = 0;
        let detections = [];
        
        // Layer 1: User Agent Analysis
        const uaResult = this.analyzeUserAgent(userAgent);
        if (uaResult) {
            detections.push(uaResult);
            confidence += 40;
        }
        
        // Layer 2: IP Range Detection
        const ipResult = this.analyzeIPRange(ip);
        if (ipResult) {
            detections.push(ipResult);
            confidence += 25;
        }
        
        // Layer 3: Header Analysis
        const headerResult = this.analyzeHeaders(headers);
        if (headerResult) {
            detections.push(headerResult);
            confidence += 20;
        }
        
        // Layer 4: ML Scoring
        const mlResult = await this.mlModel.analyze(userAgent, headers, ip);
        if (mlResult.isBot) {
            detections.push(mlResult);
            confidence += 15;
        }
        
        if (confidence >= 80 && detections.length > 0) {
            return {
                isBot: true,
                confidence: confidence,
                detections: detections,
                primaryBot: detections[0]
            };
        }
        
        return { isBot: false, confidence: 0 };
    }
    
    analyzeUserAgent(userAgent) {
        const ua = userAgent.toLowerCase();
        
        for (const [botName, info] of Object.entries(this.botSignatures)) {
            if (ua.includes(botName.toLowerCase())) {
                return {
                    name: botName,
                    company: info.company,
                    type: info.type,
                    rate: info.rate,
                    method: 'user_agent'
                };
            }
            
            // Check aliases
            if (info.aliases) {
                for (const alias of info.aliases) {
                    if (ua.includes(alias.toLowerCase())) {
                        return {
                            name: botName,
                            company: info.company,
                            type: info.type,
                            rate: info.rate,
                            method: 'user_agent_alias'
                        };
                    }
                }
            }
        }
        
        return null;
    }
    
    analyzeIPRange(ip) {
        for (const [company, ranges] of Object.entries(this.ipRanges)) {
            for (const range of ranges) {
                if (this.ipInRange(ip, range)) {
                    return {
                        name: company + 'Bot',
                        company: company,
                        type: 'ip_range',
                        rate: 0.05,
                        method: 'ip_range'
                    };
                }
            }
        }
        
        return null;
    }
    
    analyzeHeaders(headers) {
        let suspiciousScore = 0;
        
        // Check for missing standard headers
        const standardHeaders = ['accept-language', 'accept-encoding', 'dnt'];
        for (const header of standardHeaders) {
            if (!headers[header]) {
                suspiciousScore += 15;
            }
        }
        
        // Check for bot-like accept headers
        const accept = headers['accept'] || '';
        if (accept === '*/*' || accept === 'text/html,application/xhtml+xml') {
            suspiciousScore += 20;
        }
        
        // Check cache control
        const cacheControl = headers['cache-control'] || '';
        if (cacheControl.includes('no-cache') || cacheControl.includes('max-age=0')) {
            suspiciousScore += 10;
        }
        
        if (suspiciousScore >= 30) {
            return {
                name: 'SuspiciousHeaders',
                company: 'Unknown',
                type: 'headers',
                rate: 0.02,
                method: 'header_analysis',
                score: suspiciousScore
            };
        }
        
        return null;
    }
    
    ipInRange(ip, range) {
        // Simple CIDR check implementation
        if (!range.includes('/')) {
            return ip === range;
        }
        
        const [rangeIP, prefixLength] = range.split('/');
        const mask = (0xffffffff << (32 - parseInt(prefixLength))) >>> 0;
        
        const ipNum = this.ipToNumber(ip);
        const rangeNum = this.ipToNumber(rangeIP);
        
        return (ipNum & mask) === (rangeNum & mask);
    }
    
    ipToNumber(ip) {
        return ip.split('.').reduce((acc, octet) => (acc << 8) + parseInt(octet), 0) >>> 0;
    }
}

class SimpleBotMLModel {
    async analyze(userAgent, headers, ip) {
        let score = 0;
        
        // Pattern-based ML indicators
        const ua = userAgent.toLowerCase();
        
        // Programming language indicators
        if (ua.includes('python') || ua.includes('curl') || ua.includes('wget')) {
            score += 30;
        }
        
        // Headless browser indicators
        if (ua.includes('headless') || ua.includes('phantom') || ua.includes('selenium')) {
            score += 25;
        }
        
        // Missing browser fingerprint
        if (!headers['accept-language'] && !headers['accept-encoding']) {
            score += 20;
        }
        
        // Too generic user agent
        if (ua.length < 20 || !ua.includes('mozilla')) {
            score += 15;
        }
        
        return {
            isBot: score >= 40,
            confidence: Math.min(score, 100),
            method: 'ml_analysis',
            indicators: { score }
        };
    }
}

class PayPerCrawlRevenueEngine {
    constructor() {
        this.exchangeRates = { USD: 1.0 }; // Base currency
    }
    
    calculateRevenue(detection) {
        if (!detection.isBot) return 0;
        
        const bot = detection.primaryBot;
        const baseRate = bot.rate || 0.02;
        
        // Apply confidence multiplier
        const confidenceMultiplier = detection.confidence / 100;
        
        // Apply tier multiplier
        const tierMultiplier = this.getTierMultiplier(bot.type);
        
        return baseRate * confidenceMultiplier * tierMultiplier;
    }
    
    getTierMultiplier(type) {
        const multipliers = {
            'premium': 1.5,
            'standard': 1.2,
            'emerging': 1.0,
            'enterprise': 1.3,
            'research': 0.8
        };
        
        return multipliers[type] || 1.0;
    }
}

// Main Worker Event Handler
addEventListener('fetch', event => {
    event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
    const url = new URL(request.url);
    
    // Handle API endpoints
    if (url.pathname.startsWith('/api/paypercrawl/')) {
        return handleAPIRequest(request, url);
    }
    
    // Main detection logic for website traffic
    const detector = new PayPerCrawlDetector();
    const revenueEngine = new PayPerCrawlRevenueEngine();
    
    try {
        const detection = await detector.detectBot(request);
        
        if (detection.isBot) {
            const revenue = revenueEngine.calculateRevenue(detection);
            
            // Log detection
            await logDetection({
                ...detection,
                revenue: revenue,
                timestamp: Date.now(),
                url: request.url,
                ip: request.headers.get('cf-connecting-ip'),
                userAgent: request.headers.get('user-agent')
            });
            
            // Return monetization response
            return new Response(JSON.stringify({
                message: 'AI bot detected - PayPerCrawl Enterprise',
                bot: detection.primaryBot.name,
                revenue: revenue.toFixed(4),
                confidence: detection.confidence
            }), {
                status: 402, // Payment Required
                headers: {
                    'content-type': 'application/json',
                    'x-paypercrawl-detection': 'true',
                    'x-paypercrawl-bot': detection.primaryBot.name,
                    'x-paypercrawl-revenue': revenue.toString()
                }
            });
        }
        
        // Pass through normal traffic
        return fetch(request);
        
    } catch (error) {
        console.error('PayPerCrawl detection error:', error);
        return fetch(request); // Fail open
    }
}

async function handleAPIRequest(request, url) {
    const path = url.pathname.replace('/api/paypercrawl/', '');
    
    switch (path) {
        case 'health':
            return new Response(JSON.stringify({
                status: 'healthy',
                version: '6.0.0',
                timestamp: Date.now()
            }), {
                headers: { 'content-type': 'application/json' }
            });
            
        case 'stats':
            return getDetectionStats();
            
        default:
            return new Response('Not Found', { status: 404 });
    }
}

async function logDetection(detection) {
    // Log to Cloudflare Analytics
    try {
        // Store in KV or send to external analytics
        // Implementation depends on Cloudflare account setup
        console.log('Bot detection logged:', detection);
    } catch (error) {
        console.error('Logging error:', error);
    }
}

async function getDetectionStats() {
    // Return cached detection statistics
    return new Response(JSON.stringify({
        detections_today: 0, // Would come from KV storage
        revenue_today: 0,
        top_bots: []
    }), {
        headers: { 'content-type': 'application/json' }
    });
}
";
    }
    
    /**
     * Get bot signatures for worker
     */
    private function get_bot_signatures() {
        // Get bot detector instance to access signatures
        if (class_exists('PayPerCrawl_Bot_Detector_Enterprise')) {
            $detector = new PayPerCrawl_Bot_Detector_Enterprise();
            return $detector->get_bot_signatures();
        }
        
        // Fallback basic signatures
        return array(
            'GPTBot' => array('rate' => 0.15, 'type' => 'premium', 'company' => 'OpenAI'),
            'ClaudeBot' => array('rate' => 0.12, 'type' => 'premium', 'company' => 'Anthropic'),
            'Google-Extended' => array('rate' => 0.10, 'type' => 'standard', 'company' => 'Google')
        );
    }
    
    /**
     * Get IP ranges for worker
     */
    private function get_ip_ranges() {
        return array(
            'openai' => array('20.171.0.0/16', '52.230.0.0/15'),
            'google' => array('66.249.64.0/19', '216.239.32.0/19'),
            'microsoft' => array('40.76.0.0/14', '65.52.0.0/14')
        );
    }
    
    /**
     * Upload worker script to Cloudflare
     */
    private function upload_worker_script($script) {
        if (empty($this->account_id)) {
            // Try to get account ID
            $this->account_id = $this->get_account_id();
            if (!$this->account_id) {
                return new WP_Error('no_account_id', 'Cloudflare Account ID required');
            }
        }
        
        $endpoint = \"accounts/{$this->account_id}/workers/scripts/{$this->worker_name}\";
        
        $response = $this->make_cloudflare_request('PUT', $endpoint, $script, array(
            'Content-Type' => 'application/javascript'
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['success']) && $data['success']) {
            return array(
                'success' => true,
                'script_id' => $data['result']['id']
            );
        }
        
        return new WP_Error('upload_failed', $data['errors'][0]['message'] ?? 'Worker upload failed');
    }
    
    /**
     * Create worker route
     */
    private function create_worker_route() {
        $site_url = get_site_url();
        $domain = parse_url($site_url, PHP_URL_HOST);
        
        $route_pattern = $domain . '/api/paypercrawl/*';
        
        $payload = array(
            'pattern' => $route_pattern,
            'script' => $this->worker_name
        );
        
        $response = $this->make_cloudflare_request('POST', \"zones/{$this->zone_id}/workers/routes\", $payload);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['success']) && $data['success']) {
            update_option('paypercrawl_worker_route_id', $data['result']['id']);
            return array('success' => true, 'route_id' => $data['result']['id']);
        }
        
        return new WP_Error('route_failed', $data['errors'][0]['message'] ?? 'Route creation failed');
    }
    
    /**
     * Update worker configuration
     */
    public function update_worker_config($config = array()) {
        if (!get_option('paypercrawl_worker_deployed', false)) {
            return new WP_Error('worker_not_deployed', 'Worker must be deployed first');
        }
        
        // Update worker environment variables
        $endpoint = \"accounts/{$this->account_id}/workers/scripts/{$this->worker_name}/settings\";
        
        $settings = array(
            'bindings' => array(
                array(
                    'name' => 'PAYPERCRAWL_CONFIG',
                    'type' => 'json',
                    'json' => $config
                )
            )
        );
        
        $response = $this->make_cloudflare_request('PATCH', $endpoint, $settings);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return array('success' => true, 'message' => 'Worker configuration updated');
    }
    
    /**
     * Get worker statistics
     */
    public function get_worker_stats($days = 7) {
        $endpoint = \"accounts/{$this->account_id}/workers/scripts/{$this->worker_name}/usage\";
        
        $params = array(
            'since' => date('Y-m-d', strtotime(\"-{$days} days\")),
            'until' => date('Y-m-d')
        );
        
        $response = $this->make_cloudflare_request('GET', $endpoint, $params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['success']) && $data['success']) {
            return $data['result'];
        }
        
        return new WP_Error('stats_failed', 'Failed to fetch worker statistics');
    }
    
    /**
     * Get Cloudflare account ID
     */
    private function get_account_id() {
        $response = $this->make_cloudflare_request('GET', 'accounts');
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['success']) && $data['success'] && !empty($data['result'])) {
            $account_id = $data['result'][0]['id'];
            update_option('paypercrawl_cloudflare_account_id', $account_id);
            return $account_id;
        }
        
        return false;
    }
    
    /**
     * Make Cloudflare API request
     */
    private function make_cloudflare_request($method, $endpoint, $data = null, $extra_headers = array()) {
        $url = $this->api_base . ltrim($endpoint, '/');
        
        $headers = array_merge(array(
            'Authorization' => 'Bearer ' . $this->api_token,
            'Content-Type' => 'application/json'
        ), $extra_headers);
        
        $args = array(
            'method' => strtoupper($method),
            'timeout' => 30,
            'headers' => $headers
        );
        
        if ($data !== null) {
            if ($method === 'GET') {
                $url = add_query_arg($data, $url);
            } else {
                $args['body'] = is_string($data) ? $data : wp_json_encode($data);
            }
        }
        
        return wp_remote_request($url, $args);
    }
    
    /**
     * Validate Cloudflare credentials
     */
    private function validate_credentials() {
        return !empty($this->api_token) && !empty($this->zone_id);
    }
    
    /**
     * Get worker URL
     */
    private function get_worker_url() {
        $site_url = get_site_url();
        $domain = parse_url($site_url, PHP_URL_HOST);
        return \"https://{$domain}/api/paypercrawl/\";
    }
    
    /**
     * Delete worker
     */
    public function delete_worker() {
        if (empty($this->account_id)) {
            $this->account_id = $this->get_account_id();
        }
        
        // Delete worker route first
        $route_id = get_option('paypercrawl_worker_route_id', '');
        if ($route_id) {
            $this->make_cloudflare_request('DELETE', \"zones/{$this->zone_id}/workers/routes/{$route_id}\");
        }
        
        // Delete worker script
        $endpoint = \"accounts/{$this->account_id}/workers/scripts/{$this->worker_name}\";
        $response = $this->make_cloudflare_request('DELETE', $endpoint);
        
        // Clean up options
        delete_option('paypercrawl_worker_deployed');
        delete_option('paypercrawl_worker_route_id');
        delete_option('paypercrawl_worker_deployed_at');
        
        return !is_wp_error($response);
    }
    
    /**
     * Check if worker is deployed
     */
    public function is_worker_deployed() {
        return get_option('paypercrawl_worker_deployed', false);
    }
    
    /**
     * Get worker deployment status
     */
    public function get_deployment_status() {
        return array(
            'deployed' => $this->is_worker_deployed(),
            'deployed_at' => get_option('paypercrawl_worker_deployed_at', 0),
            'worker_url' => $this->get_worker_url(),
            'credentials_valid' => $this->validate_credentials()
        );
    }
    
    /**
     * Setup Cloudflare page rules for bot detection
     */
    public function setup_page_rules() {
        $rules = array(
            array(
                'targets' => array(
                    array(
                        'target' => 'url',
                        'constraint' => array(
                            'operator' => 'matches',
                            'value' => get_site_url() . '/*'
                        )
                    )
                ),
                'actions' => array(
                    array(
                        'id' => 'browser_check',
                        'value' => 'on'
                    ),
                    array(
                        'id' => 'security_level',
                        'value' => 'medium'
                    )
                ),
                'priority' => 1,
                'status' => 'active'
            )
        );
        
        foreach ($rules as $rule) {
            $response = $this->make_cloudflare_request('POST', \"zones/{$this->zone_id}/pagerules\", $rule);
            
            if (is_wp_error($response)) {
                continue;
            }
        }
        
        return true;
    }
}"
