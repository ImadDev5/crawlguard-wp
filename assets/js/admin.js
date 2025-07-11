/**
 * CrawlGuard Admin Dashboard
 */

(function($) {
    'use strict';
    
    const CrawlGuardDashboard = {
        
        init: function() {
            this.loadDashboard();
            this.bindEvents();
        },
        
        loadDashboard: function() {
            const dashboardContainer = $('#crawlguard-dashboard');
            
            if (dashboardContainer.length === 0) {
                return;
            }
            
            // Show loading state
            dashboardContainer.html('<div class="crawlguard-loading">Loading your revenue dashboard...</div>');
            
            // Fetch analytics data
            $.ajax({
                url: crawlguard_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_get_analytics',
                    nonce: crawlguard_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CrawlGuardDashboard.renderDashboard(response.data);
                    } else {
                        CrawlGuardDashboard.renderError('Failed to load analytics data');
                    }
                },
                error: function() {
                    CrawlGuardDashboard.renderError('Connection error');
                }
            });
        },
        
        renderDashboard: function(data) {
            const isMonetizationEnabled = data.monetization_enabled || false;
            const lostRevenue = this.calculateLostRevenue(data);
            
            const dashboardHtml = `
                <div class="crawlguard-dashboard">
                    ${!isMonetizationEnabled ? this.renderUpgradePrompt(lostRevenue) : ''}
                    
                    <div class="crawlguard-stats-grid">
                        <div class="crawlguard-stat-card ${data.total_revenue > 0 ? 'positive' : ''}">
                            <h3>Total Revenue</h3>
                            <div class="stat-value">$${(data.total_revenue || 0).toFixed(2)}</div>
                            <div class="stat-change">
                                ${data.revenue_change >= 0 ? '+' : ''}${(data.revenue_change || 0).toFixed(1)}% this month
                            </div>
                        </div>
                        
                        <div class="crawlguard-stat-card">
                            <h3>AI Bot Visits</h3>
                            <div class="stat-value">${data.bot_visits || 0}</div>
                            <div class="stat-change">
                                ${data.visits_change >= 0 ? '+' : ''}${(data.visits_change || 0).toFixed(1)}% this month
                            </div>
                        </div>
                        
                        <div class="crawlguard-stat-card">
                            <h3>Monetized Requests</h3>
                            <div class="stat-value">${data.monetized_requests || 0}</div>
                            <div class="stat-change">${(data.monetization_rate || 0).toFixed(1)}% conversion rate</div>
                        </div>
                        
                        <div class="crawlguard-stat-card ${!isMonetizationEnabled ? 'highlight-lost' : ''}">
                            <h3>${isMonetizationEnabled ? 'This Month Revenue' : 'Potential Lost Revenue'}</h3>
                            <div class="stat-value ${!isMonetizationEnabled ? 'lost-revenue' : ''}">
                                $${lostRevenue.toFixed(2)}
                            </div>
                            <div class="stat-change">
                                ${isMonetizationEnabled ? 'Great job monetizing!' : 'Enable Pro to capture this!'}
                            </div>
                        </div>
                    </div>
                    
                    <div class="crawlguard-charts">
                        <div class="chart-container">
                            <h3>Revenue Over Time</h3>
                            <div class="chart-controls">
                                <button class="chart-period active" data-period="7d">7 Days</button>
                                <button class="chart-period" data-period="30d">30 Days</button>
                                <button class="chart-period" data-period="90d">90 Days</button>
                            </div>
                            <canvas id="revenue-chart"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <h3>Top AI Bots</h3>
                            <div id="bot-list">
                                ${this.renderBotList(data.top_bots || [])}
                            </div>
                        </div>
                    </div>
                    
                    <div class="crawlguard-insights">
                        <h3>AI Traffic Insights</h3>
                        <div class="insights-grid">
                            ${this.renderInsights(data)}
                        </div>
                    </div>
                    
                    <div class="crawlguard-recent-activity">
                        <h3>Recent AI Bot Activity</h3>
                        <div class="activity-controls">
                            <select id="activity-filter">
                                <option value="all">All Activity</option>
                                <option value="monetized">Monetized Only</option>
                                <option value="blocked">Blocked Only</option>
                            </select>
                            <button id="export-data" class="button">Export Data</button>
                        </div>
                        <div class="activity-list">
                            ${this.renderRecentActivity(data.recent_activity || [])}
                        </div>
                    </div>
                </div>
            `;
            
            $('#crawlguard-dashboard').html(dashboardHtml);
            
            // Initialize interactive features
            this.initChartControls();
            this.initActivityFilters();
            
            // Initialize charts if data is available
            if (data.daily_stats) {
                this.initRevenueChart(data.daily_stats);
            }
        },
        
        renderBotList: function(bots) {
            if (!bots.length) {
                return '<p>No AI bot activity detected yet.</p>';
            }
            
            return bots.map(bot => `
                <div class="bot-item">
                    <div class="bot-name">${bot.name}</div>
                    <div class="bot-visits">${bot.visits} visits</div>
                    <div class="bot-revenue">$${bot.revenue.toFixed(4)}</div>
                </div>
            `).join('');
        },
        
        renderRecentActivity: function(activities) {
            if (!activities.length) {
                return '<p>No recent activity.</p>';
            }
            
            return activities.map(activity => `
                <div class="activity-item">
                    <div class="activity-time">${activity.time}</div>
                    <div class="activity-bot">${activity.bot_name}</div>
                    <div class="activity-page">${activity.page}</div>
                    <div class="activity-revenue ${activity.revenue > 0 ? 'positive' : ''}">
                        ${activity.revenue > 0 ? '$' + activity.revenue.toFixed(4) : 'Blocked'}
                    </div>
                </div>
            `).join('');
        },
        
        renderError: function(message) {
            const errorHtml = `
                <div class="crawlguard-error">
                    <h3>Dashboard Error</h3>
                    <p>${message}</p>
                    <button class="button" onclick="location.reload()">Retry</button>
                </div>
            `;
            
            $('#crawlguard-dashboard').html(errorHtml);
        },
        
        initRevenueChart: function(chartData) {
            // Simple chart implementation using HTML5 Canvas
            const canvas = document.getElementById('revenue-chart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = 200;
            
            // Clear canvas
            ctx.clearRect(0, 0, width, height);
            
            if (!chartData.length) {
                ctx.fillStyle = '#666';
                ctx.font = '14px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('No revenue data available yet', width / 2, height / 2);
                return;
            }
            
            // Draw simple line chart
            const maxRevenue = Math.max(...chartData.map(d => d.revenue));
            const padding = 40;
            const chartWidth = width - (padding * 2);
            const chartHeight = height - (padding * 2);
            
            ctx.strokeStyle = '#2271b1';
            ctx.lineWidth = 2;
            ctx.beginPath();
            
            chartData.forEach((point, index) => {
                const x = padding + (index / (chartData.length - 1)) * chartWidth;
                const y = padding + chartHeight - (point.revenue / maxRevenue) * chartHeight;
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.stroke();
        },
        
        calculateLostRevenue: function(data) {
            // Calculate potential revenue from unmonetized bot visits
            const botVisits = data.bot_visits || 0;
            const monetizedRequests = data.monetized_requests || 0;
            const unmonetizedRequests = botVisits - monetizedRequests;
            const avgRevenuePerRequest = 0.001; // Base rate
            
            return unmonetizedRequests * avgRevenuePerRequest;
        },
        
        renderUpgradePrompt: function(lostRevenue) {
            if (lostRevenue < 0.01) return ''; // Don't show for very small amounts
            
            return `
                <div class="crawlguard-upgrade-prompt">
                    <h3>💰 You're Missing Out on Revenue!</h3>
                    <p>Your site has generated <strong>$${lostRevenue.toFixed(2)}</strong> in potential revenue from AI bot traffic.</p>
                    <p>Upgrade to <strong>CrawlGuard Pro</strong> to start monetizing this traffic automatically.</p>
                    <a href="#" class="crawlguard-upgrade-button" id="upgrade-to-pro">
                        Upgrade to Pro - $15/month
                    </a>
                </div>
            `;
        },
        
        renderInsights: function(data) {
            const insights = [];
            
            // Peak traffic time
            if (data.peak_hour) {
                insights.push(`
                    <div class="insight-card">
                        <div class="insight-icon">⏰</div>
                        <div class="insight-content">
                            <h4>Peak AI Traffic</h4>
                            <p>Most AI bots visit at ${data.peak_hour}:00</p>
                        </div>
                    </div>
                `);
            }
            
            // Top performing content
            if (data.top_content) {
                insights.push(`
                    <div class="insight-card">
                        <div class="insight-icon">📄</div>
                        <div class="insight-content">
                            <h4>Most Valuable Content</h4>
                            <p>${data.top_content.title}</p>
                            <small>$${data.top_content.revenue.toFixed(3)} revenue</small>
                        </div>
                    </div>
                `);
            }
            
            // Growth trend
            const growthRate = data.growth_rate || 0;
            insights.push(`
                <div class="insight-card">
                    <div class="insight-icon">${growthRate >= 0 ? '📈' : '📉'}</div>
                    <div class="insight-content">
                        <h4>Traffic Trend</h4>
                        <p>${growthRate >= 0 ? 'Growing' : 'Declining'} ${Math.abs(growthRate).toFixed(1)}%</p>
                        <small>vs last month</small>
                    </div>
                </div>
            `);
            
            return insights.join('');
        },
        
        initChartControls: function() {
            $(document).on('click', '.chart-period', function() {
                $('.chart-period').removeClass('active');
                $(this).addClass('active');
                
                const period = $(this).data('period');
                CrawlGuardDashboard.loadChartData(period);
            });
        },
        
        initActivityFilters: function() {
            $(document).on('change', '#activity-filter', function() {
                const filter = $(this).val();
                CrawlGuardDashboard.filterActivity(filter);
            });
            
            $(document).on('click', '#export-data', function() {
                CrawlGuardDashboard.exportData();
            });
            
            $(document).on('click', '#upgrade-to-pro', function(e) {
                e.preventDefault();
                CrawlGuardDashboard.showUpgradeModal();
            });
        },
        
        loadChartData: function(period) {
            $.ajax({
                url: crawlguard_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_get_chart_data',
                    period: period,
                    nonce: crawlguard_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CrawlGuardDashboard.initRevenueChart(response.data);
                    }
                }
            });
        },
        
        filterActivity: function(filter) {
            $('.activity-item').each(function() {
                const $item = $(this);
                const revenue = parseFloat($item.find('.activity-revenue').text().replace('$', '')) || 0;
                
                switch (filter) {
                    case 'monetized':
                        $item.toggle(revenue > 0);
                        break;
                    case 'blocked':
                        $item.toggle($item.find('.activity-revenue').text() === 'Blocked');
                        break;
                    default:
                        $item.show();
                }
            });
        },
        
        exportData: function() {
            // Create CSV export
            const data = [];
            $('.activity-item').each(function() {
                const $item = $(this);
                data.push({
                    time: $item.find('.activity-time').text(),
                    bot: $item.find('.activity-bot').text(),
                    page: $item.find('.activity-page').text(),
                    revenue: $item.find('.activity-revenue').text()
                });
            });
            
            const csv = this.arrayToCSV(data);
            this.downloadCSV(csv, 'crawlguard-activity.csv');
        },
        
        arrayToCSV: function(data) {
            if (!data.length) return '';
            
            const headers = Object.keys(data[0]);
            const csvContent = [
                headers.join(','),
                ...data.map(row => headers.map(header => `"${row[header]}"`).join(','))
            ].join('\n');
            
            return csvContent;
        },
        
        downloadCSV: function(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            window.URL.revokeObjectURL(url);
        },
        
        showUpgradeModal: function() {
            const modalHtml = `
                <div class="crawlguard-modal-overlay">
                    <div class="crawlguard-modal">
                        <div class="modal-header">
                            <h2>Upgrade to CrawlGuard Pro</h2>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-content">
                            <div class="upgrade-features">
                                <h3>Unlock Full Monetization</h3>
                                <ul>
                                    <li>✅ Automatic AI bot monetization</li>
                                    <li>✅ Stripe Connect integration</li>
                                    <li>✅ Advanced bot detection</li>
                                    <li>✅ Real-time revenue tracking</li>
                                    <li>✅ Priority email support</li>
                                </ul>
                                <div class="pricing">
                                    <div class="price">$15<span>/month</span></div>
                                    <p>Start earning from AI traffic today!</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="button button-primary" id="start-upgrade">Start 7-Day Free Trial</button>
                            <button class="button" id="close-modal">Maybe Later</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Modal event handlers
            $(document).on('click', '.modal-close, #close-modal', function() {
                $('.crawlguard-modal-overlay').remove();
            });
            
            $(document).on('click', '#start-upgrade', function() {
                // Redirect to upgrade flow
                window.location.href = 'admin.php?page=crawlguard-settings&upgrade=pro';
            });
        },
        
        bindEvents: function() {
            // Refresh dashboard every 5 minutes
            setInterval(() => {
                this.loadDashboard();
            }, 300000);
            
            // Real-time updates every 30 seconds for active users
            if (document.hasFocus()) {
                setInterval(() => {
                    this.updateRealtimeStats();
                }, 30000);
            }
        },
        
        updateRealtimeStats: function() {
            $.ajax({
                url: crawlguard_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_get_realtime_stats',
                    nonce: crawlguard_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update key metrics without full reload
                        $('.stat-value').each(function(index) {
                            const newValue = response.data.stats[index];
                            if (newValue !== undefined) {
                                $(this).text(newValue);
                            }
                        });
                    }
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        CrawlGuardDashboard.init();
    });
    
})(jQuery);
