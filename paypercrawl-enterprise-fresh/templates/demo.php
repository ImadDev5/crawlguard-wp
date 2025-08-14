<?php
/**
 * Demo template for PayPerCrawl Enterprise
 * 
 * This template demonstrates how to integrate PayPerCrawl
 * detection and analytics into your theme or custom pages.
 * 
 * @package PayPerCrawl_Enterprise
 * @version 6.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the analytics engine
$analytics = new PayPerCrawl_Analytics_Engine();
$dashboard = new PayPerCrawl_Dashboard_Pro();

// Get some sample data
$today_stats = $analytics->get_today_stats();
$recent_detections = $analytics->get_recent_detections(5);
$top_bots = $analytics->get_top_bots(3);
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PayPerCrawl Enterprise Demo</title>
    <style>
        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .demo-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            border-radius: 12px;
        }
        .demo-header h1 {
            margin: 0 0 10px 0;
            font-size: 36px;
            font-weight: 700;
        }
        .demo-header p {
            margin: 0;
            font-size: 18px;
            opacity: 0.9;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 16px 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #ff6b35;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        .detections-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            margin-bottom: 40px;
        }
        .detections-section h2 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            padding-bottom: 16px;
            border-bottom: 2px solid #ff6b35;
        }
        .detection-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .detection-item:last-child {
            margin-bottom: 0;
        }
        .bot-info {
            flex: 1;
        }
        .bot-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        .bot-details {
            font-size: 14px;
            color: #6c757d;
        }
        .detection-revenue {
            font-weight: 700;
            color: #4CAF50;
            font-size: 16px;
        }
        .top-bots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .bot-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
        }
        .bot-card h4 {
            margin: 0 0 16px 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
        }
        .bot-stats {
            display: flex;
            justify-content: space-between;
            text-align: center;
        }
        .bot-stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #ff6b35;
            display: block;
        }
        .bot-stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px;
        }
        .demo-footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .top-bots-grid {
                grid-template-columns: 1fr;
            }
            .detection-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <!-- Header -->
        <div class="demo-header">
            <h1>🚀 PayPerCrawl Enterprise</h1>
            <p>Real-time AI Bot Detection & Revenue Analytics</p>
        </div>

        <!-- Today's Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Today's Revenue</h3>
                <div class="stat-value">$<?php echo number_format($today_stats['revenue'] ?? 0, 2); ?></div>
                <div class="stat-label">Generated from AI bot detections</div>
            </div>
            <div class="stat-card">
                <h3>Detections Today</h3>
                <div class="stat-value"><?php echo number_format($today_stats['detections'] ?? 0); ?></div>
                <div class="stat-label">AI bots detected and monetized</div>
            </div>
            <div class="stat-card">
                <h3>Average Confidence</h3>
                <div class="stat-value"><?php echo number_format($today_stats['avg_confidence'] ?? 0, 1); ?>%</div>
                <div class="stat-label">Detection accuracy score</div>
            </div>
            <div class="stat-card">
                <h3>Performance</h3>
                <div class="stat-value"><?php echo number_format($today_stats['avg_response_time'] ?? 0, 1); ?>ms</div>
                <div class="stat-label">Average response time</div>
            </div>
        </div>

        <!-- Recent Detections -->
        <div class="detections-section">
            <h2>🎯 Recent AI Bot Detections</h2>
            <?php if (!empty($recent_detections)): ?>
                <?php foreach ($recent_detections as $detection): ?>
                    <div class="detection-item">
                        <div class="bot-info">
                            <div class="bot-name"><?php echo esc_html($detection['bot_name']); ?></div>
                            <div class="bot-details">
                                <?php echo esc_html($detection['page_url']); ?> • 
                                Confidence: <?php echo esc_html($detection['confidence']); ?>% • 
                                <?php echo esc_html($detection['detected_at']); ?>
                            </div>
                        </div>
                        <div class="detection-revenue">
                            $<?php echo number_format($detection['revenue'], 4); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    No detections yet. AI bots will appear here when detected.
                </div>
            <?php endif; ?>
        </div>

        <!-- Top Performing Bots -->
        <div class="detections-section">
            <h2>🏆 Top Performing AI Bots</h2>
            <?php if (!empty($top_bots)): ?>
                <div class="top-bots-grid">
                    <?php foreach ($top_bots as $bot): ?>
                        <div class="bot-card">
                            <h4><?php echo esc_html($bot['name']); ?></h4>
                            <div class="bot-stats">
                                <div>
                                    <span class="bot-stat-value"><?php echo number_format($bot['detections']); ?></span>
                                    <div class="bot-stat-label">Detections</div>
                                </div>
                                <div>
                                    <span class="bot-stat-value">$<?php echo number_format($bot['revenue'], 2); ?></span>
                                    <div class="bot-stat-label">Revenue</div>
                                </div>
                                <div>
                                    <span class="bot-stat-value"><?php echo number_format($bot['avg_confidence'], 1); ?>%</span>
                                    <div class="bot-stat-label">Avg Confidence</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    No top bots yet. Start detecting AI bots to see top performers.
                </div>
            <?php endif; ?>
        </div>

        <!-- Demo Footer -->
        <div class="demo-footer">
            <p>
                <strong>PayPerCrawl Enterprise v6.0.0</strong> - 
                Monetize your AI bot traffic with advanced detection and real-time analytics.
            </p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=paypercrawl-dashboard'); ?>">
                    View Full Dashboard
                </a> | 
                <a href="<?php echo admin_url('admin.php?page=paypercrawl-settings'); ?>">
                    Configure Settings
                </a>
            </p>
        </div>
    </div>

    <script>
        // Auto-refresh demo every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Add some interactive effects
        document.querySelectorAll('.stat-card, .bot-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
