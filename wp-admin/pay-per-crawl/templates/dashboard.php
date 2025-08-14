<?php
/**
 * Dashboard Template
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get analytics data
$analytics = null;
$stats = null;
$recent_detections = array();
$potential_today = 0;
$potential_total = 0;
$missing_credentials = array();

try {
    if (class_exists('PayPerCrawl_Analytics')) {
        $analytics = PayPerCrawl_Analytics::get_instance();
        $stats = $analytics->get_dashboard_stats();
        $recent_detections = $analytics->get_recent_detections();
        $potential_today = $analytics->calculate_potential_earnings($stats['today']->count);
        $potential_total = $analytics->calculate_potential_earnings($stats['total']->count);
    }
    
    if (class_exists('PayPerCrawl')) {
        $plugin = PayPerCrawl::get_instance();
        $missing_credentials = $plugin->audit_credentials();
    }
} catch (Exception $e) {
    // Fallback data if classes fail
    $stats = (object) array(
        'today' => (object) array('count' => 0, 'unique_ips' => 0, 'unique_bots' => 0),
        'yesterday' => (object) array('count' => 0, 'unique_ips' => 0, 'unique_bots' => 0),
        'total' => (object) array('count' => 0, 'unique_ips' => 0, 'unique_bots' => 0),
        'top_bots' => array()
    );
}
?>

<div class="wrap paypercrawl-dashboard">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-shield-alt"></span>
        PayPerCrawl Dashboard
    </h1>
    
    <?php if (!empty($missing_credentials)): ?>
    <div class="notice notice-warning">
        <p>
            <strong>⚠️ Missing credentials detected:</strong> 
            <?php echo implode(', ', $missing_credentials); ?>. 
            <a href="<?php echo admin_url('admin.php?page=paypercrawl-settings'); ?>">Head to Settings → PayPerCrawl to enter them.</a>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- Early Access Banner -->
    <div class="early-access-banner">
        <div class="banner-content">
            <div class="banner-icon">🎉</div>
            <div class="banner-text">
                <h2>Early Access Beta - 100% Free!</h2>
                <p>You're part of our exclusive early access program. Detect AI bots and keep 100% of your revenue - no fees, no revenue sharing!</p>
            </div>
            <div class="banner-cta">
                <a href="https://paypercrawl.com/beta" class="button button-primary" target="_blank">Learn More</a>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card bots-today">
            <div class="card-header">
                <h3>Bots Today</h3>
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="card-content">
                <div class="stat-number"><?php echo number_format($stats['today']->count); ?></div>
                <div class="stat-subtitle"><?php echo number_format($stats['today']->unique_ips); ?> unique IPs</div>
            </div>
        </div>
        
        <div class="stat-card total-bots">
            <div class="card-header">
                <h3>Total Bots</h3>
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="card-content">
                <div class="stat-number"><?php echo number_format($stats['total']->count); ?></div>
                <div class="stat-subtitle">All time detections</div>
            </div>
        </div>
        
        <div class="stat-card potential-earnings">
            <div class="card-header">
                <h3>Potential Earnings Today</h3>
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="card-content">
                <div class="stat-number">$<?php echo number_format($potential_today, 2); ?></div>
                <div class="stat-subtitle">Based on detection rates</div>
            </div>
        </div>
        
        <div class="stat-card active-companies">
            <div class="card-header">
                <h3>Active Bot Companies</h3>
                <span class="dashicons dashicons-building"></span>
            </div>
            <div class="card-content">
                <div class="stat-number"><?php echo number_format($stats['today']->unique_bots); ?></div>
                <div class="stat-subtitle">Today's unique companies</div>
            </div>
        </div>
    </div>
    
    <!-- Chart Section -->
    <div class="chart-section">
        <div class="chart-container">
            <div class="chart-header">
                <h3>Detection Trends (30 Days)</h3>
                <button type="button" class="button refresh-chart" id="refresh-chart">
                    <span class="dashicons dashicons-update"></span> Refresh
                </button>
            </div>
            <div class="chart-content">
                <canvas id="detections-chart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Detections -->
    <div class="recent-detections">
        <div class="section-header">
            <h3>Recent Detections</h3>
            <a href="<?php echo admin_url('admin.php?page=paypercrawl-analytics'); ?>" class="button button-secondary">View All</a>
        </div>
        
        <?php if (!empty($recent_detections)): ?>
        <div class="detections-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Bot Company</th>
                        <th>IP Address</th>
                        <th>Confidence</th>
                        <th>Potential Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_detections as $detection): ?>
                    <tr>
                        <td><?php echo date('M j, H:i', strtotime($detection->timestamp)); ?></td>
                        <td><strong><?php echo esc_html($detection->bot_company); ?></strong></td>
                        <td><code><?php echo esc_html($detection->ip_address); ?></code></td>
                        <td>
                            <span class="confidence-badge confidence-<?php echo $detection->confidence_score >= 80 ? 'high' : ($detection->confidence_score >= 60 ? 'medium' : 'low'); ?>">
                                <?php echo $detection->confidence_score; ?>%
                            </span>
                        </td>
                        <td class="potential-value">$<?php echo number_format($analytics->calculate_potential_earnings(1), 3); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-detections">
            <p>No bot detections yet. When AI bots visit your site, they'll appear here.</p>
            <p><em>Tip: Share your content on social media to attract more bot crawlers!</em></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Top Bot Companies -->
    <?php if (!empty($stats['top_bots'])): ?>
    <div class="top-companies">
        <h3>Top Bot Companies</h3>
        <div class="companies-grid">
            <?php foreach ($stats['top_bots'] as $bot): ?>
            <div class="company-card">
                <div class="company-name"><?php echo esc_html($bot->bot_company); ?></div>
                <div class="company-count"><?php echo number_format($bot->count); ?> detections</div>
                <div class="company-value">~$<?php echo number_format($analytics->calculate_potential_earnings($bot->count), 2); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Chart.js initialization
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('detections-chart');
    if (ctx) {
        // Get chart data via AJAX
        jQuery.post(paypercrawl_ajax.ajax_url, {
            action: 'crawlguard_get_analytics',
            nonce: paypercrawl_ajax.nonce
        }, function(response) {
            if (response.success) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            label: 'Bot Detections',
                            data: response.data.detections,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Unique IPs',
                            data: response.data.unique_ips,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.1)',
                            tension: 0.4,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
    }
    
    // Refresh chart button
    document.getElementById('refresh-chart')?.addEventListener('click', function() {
        location.reload();
    });
});
</script>
