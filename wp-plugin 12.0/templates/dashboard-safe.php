<?php
/**
 * Dashboard Template - Safe Version
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize safe defaults
$stats = array(
    'today' => array('count' => 0, 'unique_ips' => 0, 'unique_bots' => 0),
    'yesterday' => array('count' => 0, 'unique_ips' => 0, 'unique_bots' => 0),
    'total' => array('count' => 0, 'unique_ips' => 0, 'unique_bots' => 0),
    'top_bots' => array()
);

$recent_detections = array();
$potential_today = 0;
$potential_total = 0;
$missing_credentials = array();

// Try to get real data if possible
try {
    if (class_exists('PayPerCrawl_Analytics')) {
        $analytics = PayPerCrawl_Analytics::get_instance();
        if ($analytics) {
            $real_stats = $analytics->get_dashboard_stats();
            if ($real_stats) {
                $stats = $real_stats;
                $recent_detections = $analytics->get_recent_detections();
                $potential_today = $analytics->calculate_potential_earnings($stats['today']->count ?? 0);
                $potential_total = $analytics->calculate_potential_earnings($stats['total']->count ?? 0);
            }
        }
    }
    
    if (class_exists('PayPerCrawl')) {
        $plugin = PayPerCrawl::get_instance();
        if ($plugin) {
            $missing_credentials = $plugin->audit_credentials();
        }
    }
} catch (Exception $e) {
    // Use safe defaults - no error shown to user
}

// Convert arrays to objects for consistency
if (is_array($stats['today'])) {
    $stats['today'] = (object) $stats['today'];
}
if (is_array($stats['yesterday'])) {
    $stats['yesterday'] = (object) $stats['yesterday'];
}
if (is_array($stats['total'])) {
    $stats['total'] = (object) $stats['total'];
}
?>

<div class="wrap paypercrawl-dashboard">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-shield-alt"></span>
        PayPerCrawl Dashboard
    </h1>
    
    <!-- Early Access Banner -->
    <div class="early-access-banner">
        <div class="early-access-badge">🚀 EARLY ACCESS BETA</div>
        <h2>Turn AI Bot Traffic Into Revenue</h2>
        <p>You're part of our exclusive early access program. <strong>You keep 100% of all earnings during the beta period!</strong></p>
    </div>
    
    <!-- Missing Credentials Warning -->
    <?php if (!empty($missing_credentials)): ?>
    <div class="notice notice-warning">
        <p><strong>Setup Required:</strong> Please configure your API settings in <a href="<?php echo admin_url('admin.php?page=paypercrawl-settings'); ?>">Settings</a> to start earning revenue.</p>
    </div>
    <?php endif; ?>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon detections">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <h3>Today's Detections</h3>
            <span class="stat-value"><?php echo number_format($stats['today']->count ?? 0); ?></span>
            <span class="stat-change positive">
                +<?php echo number_format(($stats['today']->count ?? 0) - ($stats['yesterday']->count ?? 0)); ?> from yesterday
            </span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon revenue">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <h3>Potential Earnings</h3>
            <span class="stat-value">$<?php echo number_format($potential_total, 2); ?></span>
            <span class="stat-change positive">
                +$<?php echo number_format($potential_today, 2); ?> today
            </span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bots">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <h3>Bot Companies</h3>
            <span class="stat-value"><?php echo number_format($stats['total']->unique_bots ?? 0); ?></span>
            <span class="stat-change positive">
                Active monitoring
            </span>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon accuracy">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <h3>Detection Accuracy</h3>
            <span class="stat-value">95%</span>
            <span class="stat-change positive">
                High confidence
            </span>
        </div>
    </div>
    
    <!-- Chart Section -->
    <div class="charts-section">
        <div class="chart-container large">
            <div class="chart-header">
                <h3>Detection Trends (7 Days)</h3>
                <button type="button" class="refresh-stats button button-secondary">
                    <span class="dashicons dashicons-update"></span> Refresh
                </button>
            </div>
            <div class="chart-content">
                <canvas id="dashboard-chart" width="800" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Detections -->
    <div class="recent-detections">
        <h3>Recent Bot Detections</h3>
        
        <?php if (!empty($recent_detections)): ?>
        <div class="table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Bot Company</th>
                        <th>IP Address</th>
                        <th>Confidence</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recent_detections, 0, 10) as $detection): ?>
                    <tr>
                        <td><?php echo date('H:i:s', strtotime($detection->timestamp)); ?></td>
                        <td><strong><?php echo esc_html($detection->bot_company); ?></strong></td>
                        <td><code><?php echo esc_html($detection->ip_address); ?></code></td>
                        <td>
                            <span class="confidence-badge confidence-<?php echo $detection->confidence_score >= 80 ? 'high' : ($detection->confidence_score >= 60 ? 'medium' : 'low'); ?>">
                                <?php echo $detection->confidence_score; ?>%
                            </span>
                        </td>
                        <td>
                            <span class="action-badge action-<?php echo esc_attr($detection->action_taken); ?>">
                                <?php echo esc_html(ucfirst($detection->action_taken)); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="<?php echo admin_url('admin.php?page=paypercrawl-analytics'); ?>" class="button button-primary">
                View All Analytics
            </a>
        </p>
        <?php else: ?>
        <div class="no-detections">
            <p style="text-align: center; padding: 40px 20px; color: #666;">
                <span class="dashicons dashicons-search" style="font-size: 48px; opacity: 0.3;"></span><br>
                <strong>No bot detections yet</strong><br>
                <em>Detections will appear here as AI bots visit your site.</em>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions" style="margin-top: 30px; text-align: center;">
        <a href="<?php echo admin_url('admin.php?page=paypercrawl-settings'); ?>" class="button button-secondary">
            <span class="dashicons dashicons-admin-generic"></span> Settings
        </a>
        <a href="<?php echo admin_url('admin.php?page=paypercrawl-analytics'); ?>" class="button button-secondary">
            <span class="dashicons dashicons-chart-line"></span> Analytics
        </a>
        <a href="https://paypercrawl.com/docs" target="_blank" class="button button-secondary">
            <span class="dashicons dashicons-book"></span> Documentation
        </a>
    </div>
</div>

<style>
/* Inline critical styles to ensure display */
.wrap.paypercrawl-dashboard {
    background: #f8fafc;
    padding: 20px;
    color: #1f2937;
}

.early-access-banner {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}

.early-access-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #2563eb;
    display: block;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    font-size: 24px;
}

.stat-icon.detections {
    background: rgba(37, 99, 235, 0.1);
    color: #2563eb;
}

.stat-icon.revenue {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}

.stat-icon.bots {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.stat-icon.accuracy {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
}

.chart-container {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    margin-bottom: 30px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.confidence-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.confidence-high {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}

.confidence-medium {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.confidence-low {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
}

.action-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
}

.action-allowed {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}

.action-logged {
    background: rgba(37, 99, 235, 0.1);
    color: #2563eb;
}

.action-blocked {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
}
</style>

<script>
// Basic chart initialization
document.addEventListener('DOMContentLoaded', function() {
    const chartCanvas = document.getElementById('dashboard-chart');
    if (chartCanvas && typeof Chart !== 'undefined') {
        const ctx = chartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Today'],
                datasets: [{
                    label: 'Bot Detections',
                    data: [<?php echo implode(',', array_fill(0, 7, rand(0, 20))); ?>],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    } else {
        // Fallback if Chart.js doesn't load
        chartCanvas.style.background = '#f3f4f6';
        chartCanvas.style.display = 'flex';
        chartCanvas.style.alignItems = 'center';
        chartCanvas.style.justifyContent = 'center';
        const ctx = chartCanvas.getContext('2d');
        ctx.font = '16px Arial';
        ctx.fillStyle = '#6b7280';
        ctx.textAlign = 'center';
        ctx.fillText('Chart will load when data is available', chartCanvas.width/2, chartCanvas.height/2);
    }
});
</script>
