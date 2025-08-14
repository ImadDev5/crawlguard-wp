<?php
/**
 * Professional Dashboard for PayPerCrawl Enterprise
 * 
 * Modern, responsive admin interface with real-time updates
 * 
 * @package PayPerCrawl_Enterprise
 * @subpackage Dashboard
 * @version 6.0.0
 * @author PayPerCrawl.tech
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Professional Dashboard with Early Access Banner
 */
class PayPerCrawl_Dashboard_Pro {
    
    /**
     * Analytics engine instance
     */
    private $analytics;
    
    /**
     * Initialize dashboard
     */
    public function __construct() {
        $this->analytics = new PayPerCrawl_Analytics_Engine();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'handle_settings_save'));
        add_action('wp_ajax_paypercrawl_refresh_dashboard', array($this, 'ajax_refresh_dashboard'));
        add_action('wp_ajax_paypercrawl_export_data', array($this, 'ajax_export_data'));
    }
    
    /**
     * Render main dashboard
     */
    public function render_dashboard() {
        $dashboard_data = $this->analytics->get_dashboard_data();
        $early_access = get_option('paypercrawl_early_access', true);
        
        ?>
        <div class="wrap paypercrawl-dashboard">
            <?php if ($early_access): ?>
            <!-- Early Access Banner -->
            <div class="paypercrawl-early-access-banner">
                <div class="banner-content">
                    <div class="banner-icon">🚀</div>
                    <div class="banner-text">
                        <h3>PayPerCrawl Enterprise - Early Access</h3>
                        <p>You're part of our exclusive early access program! Experience enterprise AI bot monetization before it goes live.</p>
                    </div>
                    <div class="banner-action">
                        <button class="button button-primary" onclick="paypercrawlShowUpgradeModal()">
                            View Full Features
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-shield-alt"></span>
                PayPerCrawl Enterprise Dashboard
            </h1>
            
            <div class="paypercrawl-dashboard-actions">
                <button class="button button-secondary" onclick="paypercrawlRefreshDashboard()">
                    <span class="dashicons dashicons-update"></span> Refresh Data
                </button>
                <button class="button button-secondary" onclick="paypercrawlExportData()">
                    <span class="dashicons dashicons-download"></span> Export Analytics
                </button>
                <a href="<?php echo admin_url('admin.php?page=paypercrawl-settings'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </div>
            
            <!-- Revenue Overview Cards -->
            <div class="paypercrawl-cards-grid">
                <div class="paypercrawl-card revenue-card">
                    <div class="card-header">
                        <h3>Today's Revenue</h3>
                        <span class="card-icon revenue-icon">💰</span>
                    </div>
                    <div class="card-content">
                        <div class="metric-value">$<?php echo number_format($dashboard_data['revenue']['today'], 2); ?></div>
                        <div class="metric-label">Earned from AI bot detection</div>
                    </div>
                </div>
                
                <div class="paypercrawl-card detection-card">
                    <div class="card-header">
                        <h3>Bot Detections</h3>
                        <span class="card-icon detection-icon">🤖</span>
                    </div>
                    <div class="card-content">
                        <div class="metric-value"><?php echo number_format($dashboard_data['detections']['today']); ?></div>
                        <div class="metric-label">AI bots detected today</div>
                    </div>
                </div>
                
                <div class="paypercrawl-card potential-card">
                    <div class="card-header">
                        <h3>Monthly Potential</h3>
                        <span class="card-icon potential-icon">📈</span>
                    </div>
                    <div class="card-content">
                        <div class="metric-value">$<?php echo number_format($dashboard_data['revenue']['potential'], 2); ?></div>
                        <div class="metric-label">Estimated monthly revenue</div>
                    </div>
                </div>
                
                <div class="paypercrawl-card performance-card">
                    <div class="card-header">
                        <h3>Detection Accuracy</h3>
                        <span class="card-icon performance-icon">🎯</span>
                    </div>
                    <div class="card-content">
                        <div class="metric-value"><?php echo $dashboard_data['performance']['accuracy']; ?>%</div>
                        <div class="metric-label">Confidence score average</div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="paypercrawl-charts-grid">
                <div class="paypercrawl-chart-container">
                    <div class="chart-header">
                        <h3>Revenue Trend (30 Days)</h3>
                        <div class="chart-controls">
                            <select id="revenueChartPeriod">
                                <option value="7">Last 7 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-content">
                        <canvas id="revenueChart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <div class="paypercrawl-chart-container">
                    <div class="chart-header">
                        <h3>Bot Detection Activity</h3>
                        <div class="chart-controls">
                            <button class="chart-toggle active" data-chart="detections">Detections</button>
                            <button class="chart-toggle" data-chart="hourly">Hourly</button>
                        </div>
                    </div>
                    <div class="chart-content">
                        <canvas id="detectionsChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="paypercrawl-charts-grid">
                <div class="paypercrawl-chart-container">
                    <div class="chart-header">
                        <h3>Bot Types Distribution</h3>
                    </div>
                    <div class="chart-content">
                        <canvas id="botTypesChart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <div class="paypercrawl-quick-stats">
                    <h3>Quick Statistics</h3>
                    <div class="stats-list">
                        <div class="stat-item">
                            <span class="stat-label">Total Revenue:</span>
                            <span class="stat-value">$<?php echo number_format($dashboard_data['revenue']['total'], 2); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">This Month:</span>
                            <span class="stat-value">$<?php echo number_format($dashboard_data['revenue']['month'], 2); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Growth Rate:</span>
                            <span class="stat-value <?php echo $dashboard_data['revenue']['growth_rate'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($dashboard_data['revenue']['growth_rate'] >= 0 ? '+' : '') . $dashboard_data['revenue']['growth_rate']; ?>%
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Unique Bots:</span>
                            <span class="stat-value"><?php echo $dashboard_data['detections']['unique_bots']; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Avg Processing:</span>
                            <span class="stat-value"><?php echo $dashboard_data['performance']['processing_time']; ?>ms</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">System Uptime:</span>
                            <span class="stat-value"><?php echo $dashboard_data['performance']['uptime']; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Detections Table -->
            <div class="paypercrawl-recent-detections">
                <div class="section-header">
                    <h3>Recent Bot Detections</h3>
                    <a href="<?php echo admin_url('admin.php?page=paypercrawl-logs'); ?>" class="button button-secondary">
                        View All Logs
                    </a>
                </div>
                
                <div class="detections-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Bot Type</th>
                                <th>Company</th>
                                <th>Revenue</th>
                                <th>Confidence</th>
                                <th>Time</th>
                                <th>Page</th>
                            </tr>
                        </thead>
                        <tbody id="recentDetectionsBody">
                            <?php $this->render_recent_detections(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Top Performing Bots -->
            <div class="paypercrawl-top-bots">
                <h3>Top Revenue Generating Bots (30 Days)</h3>
                <div class="top-bots-grid">
                    <?php $this->render_top_bots($dashboard_data['bots']['top_bots']); ?>
                </div>
            </div>
        </div>
        
        <!-- Upgrade Modal -->
        <div id="paypercrawlUpgradeModal" class="paypercrawl-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>🚀 Unlock Full Enterprise Features</h2>
                    <span class="close-modal" onclick="paypercrawlCloseModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="features-grid">
                        <div class="feature-item">
                            <span class="feature-icon">💰</span>
                            <h4>Advanced Revenue Analytics</h4>
                            <p>Detailed revenue forecasting and optimization insights</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">🛡️</span>
                            <h4>Premium Bot Detection</h4>
                            <p>50+ AI bot signatures with ML-powered accuracy</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">☁️</span>
                            <h4>Cloudflare Integration</h4>
                            <p>Edge computing for global performance</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">📊</span>
                            <h4>Real-time Dashboard</h4>
                            <p>Live updates and interactive analytics</p>
                        </div>
                    </div>
                    <div class="pricing-info">
                        <div class="price-tier">
                            <h3>Enterprise Plan</h3>
                            <div class="price">$99/month</div>
                            <p>Full access to all features</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="button button-primary button-large">
                        Upgrade to Enterprise
                    </button>
                    <button class="button button-secondary" onclick="paypercrawlCloseModal()">
                        Continue with Early Access
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        // Initialize dashboard data
        window.paypercrawlDashboardData = <?php echo wp_json_encode($dashboard_data); ?>;
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                paypercrawlRefreshDashboard();
            }
        }, 30000);
        </script>
        <?php
    }
    
    /**
     * Render recent detections
     */
    private function render_recent_detections() {
        global $wpdb;
        $detections_table = $wpdb->prefix . PAYPERCRAWL_DETECTIONS_TABLE;
        
        $recent_detections = $wpdb->get_results(
            "SELECT bot_type, ip_address, confidence_score, revenue_generated, 
                    detected_at, page_url, metadata
             FROM $detections_table 
             ORDER BY detected_at DESC 
             LIMIT 10",
            ARRAY_A
        );
        
        if (empty($recent_detections)) {
            echo '<tr><td colspan="6" class="no-detections">No bot detections yet. Start by configuring your settings.</td></tr>';
            return;
        }
        
        foreach ($recent_detections as $detection) {
            $metadata = json_decode($detection['metadata'], true);
            $company = $metadata['company'] ?? 'Unknown';
            $page_title = $this->get_page_title($detection['page_url']);
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($detection['bot_type']) . '</strong></td>';
            echo '<td>' . esc_html($company) . '</td>';
            echo '<td class="revenue-cell">$' . number_format($detection['revenue_generated'], 3) . '</td>';
            echo '<td class="confidence-cell">';
            echo '<span class="confidence-badge ' . $this->get_confidence_class($detection['confidence_score']) . '">';
            echo round($detection['confidence_score'], 1) . '%';
            echo '</span></td>';
            echo '<td>' . human_time_diff(strtotime($detection['detected_at'])) . ' ago</td>';
            echo '<td class="page-cell">' . esc_html($page_title) . '</td>';
            echo '</tr>';
        }
    }
    
    /**
     * Get confidence CSS class
     */
    private function get_confidence_class($score) {
        if ($score >= 90) return 'high-confidence';
        if ($score >= 70) return 'medium-confidence';
        return 'low-confidence';
    }
    
    /**
     * Get page title from URL
     */
    private function get_page_title($url) {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === '/' || empty($path)) {
            return 'Homepage';
        }
        
        // Try to get actual page title
        $post_id = url_to_postid($url);
        if ($post_id) {
            return get_the_title($post_id);
        }
        
        return basename($path);
    }
    
    /**
     * Render top performing bots
     */
    private function render_top_bots($top_bots) {
        if (empty($top_bots)) {
            echo '<div class="no-data">No bot data available yet.</div>';
            return;
        }
        
        foreach (array_slice($top_bots, 0, 6) as $bot) {
            $revenue = (float) $bot['revenue'];
            $detections = (int) $bot['detections'];
            $avg_confidence = round((float) $bot['avg_confidence'], 1);
            
            echo '<div class="top-bot-card">';
            echo '<div class="bot-header">';
            echo '<h4>' . esc_html($bot['bot_type']) . '</h4>';
            echo '<span class="bot-badge">' . $this->get_bot_tier($revenue) . '</span>';
            echo '</div>';
            echo '<div class="bot-stats">';
            echo '<div class="stat">';
            echo '<span class="stat-value">$' . number_format($revenue, 2) . '</span>';
            echo '<span class="stat-label">Revenue</span>';
            echo '</div>';
            echo '<div class="stat">';
            echo '<span class="stat-value">' . $detections . '</span>';
            echo '<span class="stat-label">Detections</span>';
            echo '</div>';
            echo '<div class="stat">';
            echo '<span class="stat-value">' . $avg_confidence . '%</span>';
            echo '<span class="stat-label">Accuracy</span>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    /**
     * Get bot tier based on revenue
     */
    private function get_bot_tier($revenue) {
        if ($revenue >= 1.00) return 'Premium';
        if ($revenue >= 0.50) return 'Standard';
        if ($revenue >= 0.10) return 'Emerging';
        return 'Basic';
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics() {
        $dashboard_data = $this->analytics->get_dashboard_data();
        $forecast = $this->analytics->get_revenue_forecast(30);
        
        ?>
        <div class="wrap paypercrawl-analytics">
            <h1><span class="dashicons dashicons-chart-line"></span> Analytics & Reports</h1>
            
            <div class="analytics-tabs">
                <button class="tab-button active" data-tab="overview">Overview</button>
                <button class="tab-button" data-tab="revenue">Revenue Analysis</button>
                <button class="tab-button" data-tab="bots">Bot Intelligence</button>
                <button class="tab-button" data-tab="performance">Performance</button>
            </div>
            
            <div id="overview-tab" class="tab-content active">
                <!-- Revenue Forecast -->
                <div class="analytics-section">
                    <h3>Revenue Forecast</h3>
                    <div class="forecast-cards">
                        <div class="forecast-card">
                            <h4>30-Day Forecast</h4>
                            <div class="forecast-value">$<?php echo number_format($forecast['forecast'], 2); ?></div>
                            <div class="forecast-confidence">Confidence: <?php echo ucfirst($forecast['confidence']); ?></div>
                        </div>
                        <div class="forecast-card">
                            <h4>Daily Average</h4>
                            <div class="forecast-value">$<?php echo number_format($forecast['daily_average'], 2); ?></div>
                            <div class="forecast-trend <?php echo $forecast['trend'] >= 0 ? 'positive' : 'negative'; ?>">
                                Trend: <?php echo ($forecast['trend'] >= 0 ? '+' : '') . $forecast['trend']; ?>%
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Charts -->
                <div class="analytics-charts">
                    <div class="chart-container large">
                        <h4>Revenue vs Detections Correlation</h4>
                        <canvas id="correlationChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Additional tab content would go here -->
        </div>
        
        <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.dataset.tab + '-tab';
                
                // Update active tab button
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Update active tab content
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById(tabId).classList.add('active');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        $api_key = get_option('paypercrawl_api_key', '');
        $cloudflare_zone_id = get_option('paypercrawl_cloudflare_zone_id', '');
        $detection_enabled = get_option('paypercrawl_detection_enabled', true);
        $confidence_threshold = get_option('paypercrawl_confidence_threshold', 85.0);
        
        ?>
        <div class="wrap paypercrawl-settings">
            <h1><span class="dashicons dashicons-admin-settings"></span> PayPerCrawl Settings</h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success"><p>Settings saved successfully!</p></div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php settings_fields('paypercrawl_settings'); ?>
                
                <div class="settings-sections">
                    <!-- API Configuration -->
                    <div class="settings-section">
                        <h3>API Configuration</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">API Key</th>
                                <td>
                                    <input type="password" name="paypercrawl_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                                    <p class="description">Your PayPerCrawl API key for authentication</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Cloudflare Zone ID</th>
                                <td>
                                    <input type="text" name="paypercrawl_cloudflare_zone_id" value="<?php echo esc_attr($cloudflare_zone_id); ?>" class="regular-text" />
                                    <p class="description">Your Cloudflare Zone ID for Workers integration</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Detection Settings -->
                    <div class="settings-section">
                        <h3>Detection Settings</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable Bot Detection</th>
                                <td>
                                    <input type="checkbox" name="paypercrawl_detection_enabled" value="1" <?php checked($detection_enabled); ?> />
                                    <label>Enable real-time AI bot detection</label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Confidence Threshold</th>
                                <td>
                                    <input type="number" name="paypercrawl_confidence_threshold" value="<?php echo esc_attr($confidence_threshold); ?>" min="0" max="100" step="0.1" class="small-text" />%
                                    <p class="description">Minimum confidence score for bot detection (0-100)</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php submit_button('Save Settings', 'primary', 'submit', true, array('class' => 'button-large')); ?>
            </form>
            
            <!-- API Connection Test -->
            <div class="settings-section">
                <h3>Connection Test</h3>
                <button type="button" class="button button-secondary" onclick="paypercrawlTestConnection()">
                    Test API Connection
                </button>
                <div id="connectionTestResult" class="test-result"></div>
            </div>
        </div>
        
        <script>
        function paypercrawlTestConnection() {
            const resultDiv = document.getElementById('connectionTestResult');
            resultDiv.innerHTML = '<p>Testing connection...</p>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=paypercrawl_test_api&nonce=' + paypercrawl_ajax.nonce
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<p class="success">✅ Connection successful!</p>';
                } else {
                    resultDiv.innerHTML = '<p class="error">❌ Connection failed: ' + data.data + '</p>';
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Render logs page
     */
    public function render_logs() {
        global $wpdb;
        $detections_table = $wpdb->prefix . PAYPERCRAWL_DETECTIONS_TABLE;
        
        // Pagination
        $per_page = 50;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $detections_table");
        $total_pages = ceil($total_logs / $per_page);
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $detections_table ORDER BY detected_at DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        
        ?>
        <div class="wrap paypercrawl-logs">
            <h1><span class="dashicons dashicons-list-view"></span> Detection Logs</h1>
            
            <div class="logs-filters">
                <form method="GET">
                    <input type="hidden" name="page" value="paypercrawl-logs">
                    <select name="bot_type">
                        <option value="">All Bot Types</option>
                        <option value="GPTBot">GPTBot</option>
                        <option value="ClaudeBot">ClaudeBot</option>
                        <option value="Google-Extended">Google-Extended</option>
                    </select>
                    <input type="date" name="date_from" placeholder="From Date">
                    <input type="date" name="date_to" placeholder="To Date">
                    <button type="submit" class="button">Filter</button>
                </form>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Bot Type</th>
                        <th>IP Address</th>
                        <th>Confidence</th>
                        <th>Revenue</th>
                        <th>Page URL</th>
                        <th>Detected At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><strong><?php echo esc_html($log->bot_type); ?></strong></td>
                        <td><?php echo esc_html($log->ip_address); ?></td>
                        <td>
                            <span class="confidence-badge <?php echo $this->get_confidence_class($log->confidence_score); ?>">
                                <?php echo round($log->confidence_score, 1); ?>%
                            </span>
                        </td>
                        <td>$<?php echo number_format($log->revenue_generated, 3); ?></td>
                        <td class="page-url"><?php echo esc_html(wp_trim_words($log->page_url, 5)); ?></td>
                        <td><?php echo date('M j, Y H:i', strtotime($log->detected_at)); ?></td>
                        <td>
                            <button class="button button-small" onclick="viewLogDetails(<?php echo $log->id; ?>)">
                                View Details
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Handle settings save
     */
    public function handle_settings_save() {
        if (isset($_POST['submit']) && current_user_can('manage_options')) {
            // WordPress handles the saving via register_setting
            // Additional validation can be added here
        }
    }
    
    /**
     * AJAX refresh dashboard
     */
    public function ajax_refresh_dashboard() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        $dashboard_data = $this->analytics->get_dashboard_data();
        wp_send_json_success($dashboard_data);
    }
    
    /**
     * AJAX export data
     */
    public function ajax_export_data() {
        check_ajax_referer('paypercrawl_nonce', 'nonce');
        
        $format = sanitize_text_field($_POST['format'] ?? 'json');
        $days = intval($_POST['days'] ?? 30);
        
        $data = $this->analytics->export_analytics_data($format, $days);
        
        wp_send_json_success(array(
            'data' => $data,
            'filename' => 'paypercrawl-export-' . date('Y-m-d') . '.' . $format
        ));
    }
}
