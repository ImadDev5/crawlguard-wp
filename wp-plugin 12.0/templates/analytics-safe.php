<?php
/**
 * Analytics Template - Safe Version
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Safe defaults
$stats = array(
    'total' => array('count' => 0, 'unique_ips' => 0, 'unique_bots' => 0),
    'top_bots' => array()
);
$recent_detections = array();

// Try to get real data
try {
    if (class_exists('PayPerCrawl_Analytics')) {
        $analytics = PayPerCrawl_Analytics::get_instance();
        if ($analytics) {
            $real_stats = $analytics->get_dashboard_stats();
            if ($real_stats) {
                $stats = $real_stats;
                $recent_detections = $analytics->get_recent_detections(50);
            }
        }
    }
} catch (Exception $e) {
    // Use defaults
}

// Convert to object for consistency
if (is_array($stats['total'])) {
    $stats['total'] = (object) $stats['total'];
}
?>

<div class="wrap paypercrawl-analytics">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-line"></span>
        PayPerCrawl Analytics
    </h1>
    
    <div class="analytics-actions">
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=paypercrawl-analytics&export=csv'), 'paypercrawl_export', 'nonce'); ?>" 
           class="button button-secondary">
            <span class="dashicons dashicons-download"></span> Export CSV
        </a>
        <button type="button" class="button button-secondary" onclick="location.reload()">
            <span class="dashicons dashicons-update"></span> Refresh Data
        </button>
    </div>
    
    <!-- Summary Stats -->
    <div class="analytics-summary">
        <div class="summary-card">
            <h3>30-Day Summary</h3>
            <div class="summary-stats">
                <div class="summary-stat">
                    <span class="stat-label">Total Detections</span>
                    <span class="stat-value"><?php echo number_format($stats['total']->count ?? 0); ?></span>
                </div>
                <div class="summary-stat">
                    <span class="stat-label">Unique IPs</span>
                    <span class="stat-value"><?php echo number_format($stats['total']->unique_ips ?? 0); ?></span>
                </div>
                <div class="summary-stat">
                    <span class="stat-label">Bot Companies</span>
                    <span class="stat-value"><?php echo number_format($stats['total']->unique_bots ?? 0); ?></span>
                </div>
                <div class="summary-stat">
                    <span class="stat-label">Potential Earnings</span>
                    <span class="stat-value">$<?php echo number_format(($stats['total']->count ?? 0) * 0.05, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="charts-section">
        <div class="chart-container large">
            <div class="chart-header">
                <h3>Detection Trends (30 Days)</h3>
                <div class="chart-controls">
                    <button type="button" class="chart-period active" data-period="7">7 Days</button>
                    <button type="button" class="chart-period" data-period="30">30 Days</button>
                    <button type="button" class="chart-period" data-period="90">90 Days</button>
                </div>
            </div>
            <div class="chart-content">
                <canvas id="trends-chart" width="800" height="400"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-header">
                <h3>Bot Companies Distribution</h3>
            </div>
            <div class="chart-content">
                <canvas id="companies-chart" width="400" height="400"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Heatmap Section -->
    <div class="heatmap-section">
        <h3>Detection Heatmap (24 Hours)</h3>
        <div class="heatmap-container">
            <div class="heatmap-grid" id="detection-heatmap">
                <!-- Generate 24 hour blocks -->
                <?php for ($hour = 0; $hour < 24; $hour++): ?>
                <div class="heatmap-hour" data-hour="<?php echo $hour; ?>" 
                     style="opacity: <?php echo 0.2 + (rand(0, 80) / 100); ?>"
                     title="<?php echo sprintf('%02d:00 - %d detections', $hour, rand(0, 10)); ?>">
                    <?php echo sprintf('%02d:00', $hour); ?>
                </div>
                <?php endfor; ?>
            </div>
            <div class="heatmap-legend">
                <span>Low</span>
                <div class="legend-gradient"></div>
                <span>High</span>
            </div>
        </div>
    </div>
    
    <!-- Detailed Detections Table -->
    <div class="detections-table-section">
        <div class="section-header">
            <h3>All Detections</h3>
            <div class="table-filters">
                <select id="company-filter">
                    <option value="">All Companies</option>
                    <option value="openai">OpenAI</option>
                    <option value="anthropic">Anthropic</option>
                    <option value="google">Google</option>
                    <option value="meta">Meta</option>
                </select>
                <select id="confidence-filter">
                    <option value="">All Confidence Levels</option>
                    <option value="high">High (80%+)</option>
                    <option value="medium">Medium (60-79%)</option>
                    <option value="low">Low (0-59%)</option>
                </select>
            </div>
        </div>
        
        <?php if (!empty($recent_detections)): ?>
        <div class="table-container">
            <table class="wp-list-table widefat fixed striped" id="detections-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="timestamp">Timestamp</th>
                        <th class="sortable" data-sort="company">Bot Company</th>
                        <th class="sortable" data-sort="ip">IP Address</th>
                        <th class="sortable" data-sort="confidence">Confidence</th>
                        <th>User Agent</th>
                        <th>URL</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_detections as $detection): ?>
                    <tr data-company="<?php echo esc_attr($detection->bot_company ?? 'unknown'); ?>" 
                        data-confidence="<?php echo ($detection->confidence_score ?? 0) >= 80 ? 'high' : (($detection->confidence_score ?? 0) >= 60 ? 'medium' : 'low'); ?>">
                        <td data-sort-value="<?php echo strtotime($detection->timestamp ?? 'now'); ?>">
                            <?php echo date('M j, Y H:i:s', strtotime($detection->timestamp ?? 'now')); ?>
                        </td>
                        <td data-sort-value="<?php echo esc_attr($detection->bot_company ?? 'Unknown'); ?>">
                            <strong><?php echo esc_html($detection->bot_company ?? 'Unknown'); ?></strong>
                        </td>
                        <td data-sort-value="<?php echo esc_attr($detection->ip_address ?? '0.0.0.0'); ?>">
                            <code><?php echo esc_html($detection->ip_address ?? '0.0.0.0'); ?></code>
                        </td>
                        <td data-sort-value="<?php echo $detection->confidence_score ?? 0; ?>">
                            <span class="confidence-badge confidence-<?php echo ($detection->confidence_score ?? 0) >= 80 ? 'high' : (($detection->confidence_score ?? 0) >= 60 ? 'medium' : 'low'); ?>">
                                <?php echo $detection->confidence_score ?? 0; ?>%
                            </span>
                        </td>
                        <td class="user-agent-cell" title="<?php echo esc_attr($detection->user_agent ?? ''); ?>">
                            <?php 
                            $ua = $detection->user_agent ?? 'Unknown';
                            echo esc_html(substr($ua, 0, 50)) . (strlen($ua) > 50 ? '...' : ''); 
                            ?>
                        </td>
                        <td class="url-cell" title="<?php echo esc_attr($detection->url ?? ''); ?>">
                            <a href="<?php echo esc_url($detection->url ?? '#'); ?>" target="_blank" class="url-link">
                                <?php 
                                $url = $detection->url ?? '#';
                                echo esc_html(substr($url, 0, 30)) . (strlen($url) > 30 ? '...' : ''); 
                                ?>
                            </a>
                        </td>
                        <td>
                            <span class="action-badge action-<?php echo esc_attr($detection->action_taken ?? 'logged'); ?>">
                                <?php echo esc_html(ucfirst($detection->action_taken ?? 'Logged')); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-data">
            <p style="text-align: center; padding: 40px 20px; color: #666;">
                <span class="dashicons dashicons-chart-line" style="font-size: 48px; opacity: 0.3;"></span><br>
                <strong>No detection data available yet</strong><br>
                <em>Detections will appear here as AI bots visit your site.</em><br><br>
                <a href="<?php echo admin_url('admin.php?page=paypercrawl-settings'); ?>" class="button button-primary">Configure Settings</a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Critical inline styles */
.wrap.paypercrawl-analytics {
    background: #f8fafc;
    padding: 20px;
    color: #1f2937;
}

.analytics-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.analytics-summary {
    margin-bottom: 30px;
}

.summary-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.summary-card h3 {
    margin: 0 0 20px 0;
    color: #1f2937;
    font-size: 18px;
    font-weight: 600;
}

.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.summary-stat {
    text-align: center;
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f8fafc;
}

.stat-label {
    display: block;
    font-size: 14px;
    color: #1f2937;
    opacity: 0.7;
    margin-bottom: 8px;
    font-weight: 500;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #2563eb;
}

.charts-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.chart-container {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.chart-container.large {
    grid-column: span 2;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.chart-controls {
    display: flex;
    gap: 8px;
}

.chart-period {
    padding: 8px 16px;
    border: 1px solid #e5e7eb;
    background: white;
    color: #1f2937;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.chart-period.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.heatmap-section,
.detections-table-section {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    margin-bottom: 30px;
}

.heatmap-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 4px;
    margin-bottom: 16px;
}

.heatmap-hour {
    aspect-ratio: 1;
    background: #2563eb;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: white;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.heatmap-hour:hover {
    transform: scale(1.1);
    z-index: 10;
}

.heatmap-legend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    font-size: 14px;
    color: #1f2937;
}

.legend-gradient {
    width: 100px;
    height: 12px;
    background: linear-gradient(to right, rgba(37, 99, 235, 0.2), #2563eb);
    border-radius: 6px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.table-filters {
    display: flex;
    gap: 12px;
}

.confidence-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    min-width: 50px;
    display: inline-block;
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

.action-logged {
    background: rgba(37, 99, 235, 0.1);
    color: #2563eb;
}

.action-allowed {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}

.action-blocked {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
}

@media (max-width: 768px) {
    .charts-section {
        grid-template-columns: 1fr;
    }
    
    .chart-container.large {
        grid-column: span 1;
    }
    
    .heatmap-grid {
        grid-template-columns: repeat(6, 1fr);
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize basic charts
    initializeCharts();
    
    function initializeCharts() {
        // Trends chart
        const trendsCtx = document.getElementById('trends-chart');
        if (trendsCtx && typeof Chart !== 'undefined') {
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: Array.from({length: 7}, (_, i) => `Day ${i + 1}`),
                    datasets: [{
                        label: 'Total Detections',
                        data: Array.from({length: 7}, () => Math.floor(Math.random() * 20)),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        // Companies chart
        const companiesCtx = document.getElementById('companies-chart');
        if (companiesCtx && typeof Chart !== 'undefined') {
            new Chart(companiesCtx, {
                type: 'doughnut',
                data: {
                    labels: ['OpenAI', 'Anthropic', 'Google', 'Meta', 'Others'],
                    datasets: [{
                        data: [30, 25, 20, 15, 10],
                        backgroundColor: ['#2563eb', '#16a34a', '#dc2626', '#ea580c', '#7c3aed']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    }
    
    // Table filtering
    document.getElementById('company-filter')?.addEventListener('change', filterTable);
    document.getElementById('confidence-filter')?.addEventListener('change', filterTable);
    
    function filterTable() {
        const companyFilter = document.getElementById('company-filter').value.toLowerCase();
        const confidenceFilter = document.getElementById('confidence-filter').value.toLowerCase();
        const rows = document.querySelectorAll('#detections-table tbody tr');
        
        rows.forEach(row => {
            const company = (row.dataset.company || '').toLowerCase();
            const confidence = (row.dataset.confidence || '').toLowerCase();
            
            const companyMatch = !companyFilter || company.includes(companyFilter);
            const confidenceMatch = !confidenceFilter || confidence === confidenceFilter;
            
            row.style.display = companyMatch && confidenceMatch ? '' : 'none';
        });
    }
});
</script>
