/**
 * CrawlGuard Pro Real-Time Dashboard JavaScript
 * Enhanced with Chart.js integration and live updates
 */

(function($) {
    'use strict';
    
    // Dashboard state management
    let dashboardState = {
        realtimeEnabled: true,
        updateInterval: 5000, // 5 seconds
        chartInstance: null,
        revenueChart: null,
        lastUpdate: null,
        notifications: []
    };
    
    // Initialize when document ready
    $(document).ready(function() {
        initializeDashboard();
        setupEventHandlers();
        startRealtimeUpdates();
        initializeCharts();
        loadChartLibrary();
    });
    
    function loadChartLibrary() {
        // Load Chart.js if not already loaded
        if (typeof Chart === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
            script.onload = function() {
                console.log('Chart.js loaded successfully');
                initializeCharts();
            };
            document.head.appendChild(script);
        }
    }
    
    function initializeDashboard() {
        console.log('CrawlGuard Dashboard initializing...');
        
        // Add loading indicators to metrics
        $('.metric-value').each(function() {
            $(this).attr('data-original', $(this).text());
            $(this).addClass('loading');
        });
        
        // Add dashboard controls
        addDashboardControls();
        
        // Check for first-time users
        checkFirstTimeUser();
        
        // Initialize notification system
        initializeNotifications();
    }
    
    function addDashboardControls() {
        const controlsHtml = `
            <div class="crawlguard-dashboard-controls">
                <div class="controls-left">
                    <button id="refresh-dashboard" class="button button-primary">
                        <span class="refresh-icon">🔄</span> Refresh
                    </button>
                    <button id="toggle-realtime" class="button">
                        <span class="realtime-icon">⏸️</span> Pause Updates
                    </button>
                </div>
                <div class="controls-right">
                    <span class="last-update">
                        Last updated: <span id="last-update-time">Never</span>
                    </span>
                    <div class="connection-status">
                        <span id="connection-indicator" class="status-connected">🟢 Connected</span>
                    </div>
                </div>
            </div>
        `;
        
        $('.crawlguard-header').after(controlsHtml);
    }
    
    function setupEventHandlers() {
        // Refresh button
        $(document).on('click', '#refresh-dashboard', function(e) {
            e.preventDefault();
            refreshDashboard();
        });
        
        // Toggle real-time updates
        $(document).on('click', '#toggle-realtime', function(e) {
            e.preventDefault();
            toggleRealtimeUpdates();
        });
        
        // Enable monetization button
        $(document).on('click', '.enable-monetization-btn', function(e) {
            e.preventDefault();
            showMonetizationModal();
        });
        
        // API connection test
        $(document).on('click', '#test-api-connection', function(e) {
            e.preventDefault();
            testAPIConnection();
        });
        
        // Settings save
        $(document).on('click', '#save-settings', function(e) {
            e.preventDefault();
            saveSettings();
        });
    }
    
    function startRealtimeUpdates() {
        // Initial load
        fetchDashboardData();
        
        // Set up interval for real-time updates
        setInterval(function() {
            if (dashboardState.realtimeEnabled) {
                fetchDashboardData();
            }
        }, dashboardState.updateInterval);
    }
    
    function fetchDashboardData() {
        updateConnectionStatus('connecting');
        
        $.ajax({
            url: crawlguard_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crawlguard_get_dashboard_data',
                nonce: crawlguard_ajax.nonce
            },
            timeout: 10000,
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                    updateConnectionStatus('connected');
                    dashboardState.lastUpdate = new Date();
                    updateLastUpdateTime();
                } else {
                    console.error('Dashboard API error:', response.data);
                    updateConnectionStatus('error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Dashboard fetch error:', error);
                updateConnectionStatus('error');
                showNotification('Failed to fetch dashboard data. Retrying...', 'error');
            }
        });
    }
    
    function updateDashboard(data) {
        // Update main metrics with smooth animations
        animateMetricUpdate('#total-revenue', data.total_revenue || 0, '$');
        animateMetricUpdate('#daily-revenue', data.daily_revenue || 0, '$');
        animateMetricUpdate('#bots-detected-today', data.bots_detected_today || 0);
        animateMetricUpdate('#total-requests', data.total_requests || 0);
        animateMetricUpdate('#detection-rate', data.detection_rate || 0, '%');
        
        // Update charts if data available
        if (data.chart_data) {
            updateCharts(data.chart_data);
        }
        
        // Update recent activity feed
        if (data.recent_activity) {
            updateActivityFeed(data.recent_activity);
        }
        
        // Show notifications for new detections
        if (data.new_detections && data.new_detections > 0) {
            showNotification(`🤖 ${data.new_detections} new AI bot${data.new_detections > 1 ? 's' : ''} detected!`, 'info');
        }
        
        // Update AI company breakdown
        if (data.ai_companies) {
            updateAICompanyBreakdown(data.ai_companies);
        }
    }
    
    function animateMetricUpdate(selector, newValue, prefix = '', suffix = '') {
        const element = $(selector);
        const currentText = element.text().replace(/[^0-9.]/g, '');
        const currentValue = parseFloat(currentText) || 0;
        const targetValue = parseFloat(newValue) || 0;
        
        if (Math.abs(currentValue - targetValue) > 0.001) {
            // Animate the number counting up/down
            element.parent().addClass('metric-updating');
            
            $({value: currentValue}).animate({value: targetValue}, {
                duration: 800,
                easing: 'swing',
                step: function() {
                    const displayValue = prefix + this.value.toFixed(prefix === '$' ? 4 : 0) + suffix;
                    element.text(displayValue);
                },
                complete: function() {
                    const finalValue = prefix + targetValue.toFixed(prefix === '$' ? 4 : 0) + suffix;
                    element.text(finalValue);
                    element.parent().removeClass('metric-updating').addClass('metric-updated');
                    
                    setTimeout(() => {
                        element.parent().removeClass('metric-updated');
                    }, 1500);
                }
            });
        }
    }
    
    function initializeCharts() {
        // Wait for Chart.js to be available
        if (typeof Chart === 'undefined') {
            setTimeout(initializeCharts, 1000);
            return;
        }
        
        initializeRevenueChart();
        initializeDetectionChart();
    }
    
    function initializeRevenueChart() {
        const canvas = document.getElementById('revenue-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        dashboardState.revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '💰 Revenue Over Time'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(4);
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
    
    function initializeDetectionChart() {
        const canvas = document.getElementById('detection-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        dashboardState.chartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['AI Bots', 'Regular Traffic'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: [
                        'rgb(239, 68, 68)',
                        'rgb(107, 114, 128)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '🤖 Bot Detection Rate'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    function updateCharts(chartData) {
        // Update revenue chart
        if (dashboardState.revenueChart && chartData.revenue) {
            dashboardState.revenueChart.data.labels = chartData.revenue.labels || [];
            dashboardState.revenueChart.data.datasets[0].data = chartData.revenue.data || [];
            dashboardState.revenueChart.update('none');
        }
        
        // Update detection chart
        if (dashboardState.chartInstance && chartData.detection) {
            const botCount = chartData.detection.bots || 0;
            const totalCount = chartData.detection.total || 1;
            const regularCount = totalCount - botCount;
            
            dashboardState.chartInstance.data.datasets[0].data = [botCount, regularCount];
            dashboardState.chartInstance.update('none');
        }
    }
    
    function updateActivityFeed(activities) {
        const feedContainer = $('#recent-activity-feed');
        if (!feedContainer.length) return;
        
        // Clear existing items
        feedContainer.empty();
        
        if (!activities || activities.length === 0) {
            feedContainer.html('<div class="no-activity">No recent activity</div>');
            return;
        }
        
        activities.forEach(function(activity) {
            const activityHtml = createActivityItem(activity);
            feedContainer.append(activityHtml);
        });
    }
    
    function createActivityItem(activity) {
        const timeAgo = getTimeAgo(activity.timestamp);
        const revenueClass = parseFloat(activity.revenue) > 0 ? 'revenue-positive' : 'revenue-zero';
        
        return `
            <div class="activity-item" data-id="${activity.id}">
                <div class="activity-icon">🤖</div>
                <div class="activity-details">
                    <div class="activity-main">
                        <span class="bot-type">${escapeHtml(activity.bot_type)}</span>
                        <span class="page-url">${escapeHtml(activity.page_url || '/')}</span>
                    </div>
                    <div class="activity-meta">
                        <span class="timestamp">${timeAgo}</span>
                        <span class="revenue ${revenueClass}">+$${parseFloat(activity.revenue || 0).toFixed(4)}</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    function updateAICompanyBreakdown(companies) {
        const container = $('#ai-company-breakdown');
        if (!container.length) return;
        
        container.empty();
        
        companies.forEach(function(company) {
            const percentage = ((company.count / companies.reduce((sum, c) => sum + c.count, 0)) * 100).toFixed(1);
            
            const companyHtml = `
                <div class="company-item">
                    <div class="company-name">${escapeHtml(company.name)}</div>
                    <div class="company-stats">
                        <span class="count">${company.count} requests</span>
                        <span class="percentage">${percentage}%</span>
                        <span class="revenue">$${parseFloat(company.revenue || 0).toFixed(4)}</span>
                    </div>
                </div>
            `;
            
            container.append(companyHtml);
        });
    }
    
    function toggleRealtimeUpdates() {
        dashboardState.realtimeEnabled = !dashboardState.realtimeEnabled;
        
        const button = $('#toggle-realtime');
        const icon = button.find('.realtime-icon');
        
        if (dashboardState.realtimeEnabled) {
            button.html('<span class="realtime-icon">⏸️</span> Pause Updates');
            fetchDashboardData(); // Immediately fetch when resuming
        } else {
            button.html('<span class="realtime-icon">▶️</span> Resume Updates');
        }
    }
    
    function refreshDashboard() {
        const button = $('#refresh-dashboard');
        const icon = button.find('.refresh-icon');
        
        button.prop('disabled', true);
        icon.addClass('rotating');
        
        fetchDashboardData();
        
        setTimeout(function() {
            button.prop('disabled', false);
            icon.removeClass('rotating');
        }, 1500);
    }
    
    function updateConnectionStatus(status) {
        const indicator = $('#connection-indicator');
        
        switch(status) {
            case 'connected':
                indicator.html('🟢 Connected').removeClass('status-error status-connecting').addClass('status-connected');
                break;
            case 'connecting':
                indicator.html('🟡 Connecting...').removeClass('status-error status-connected').addClass('status-connecting');
                break;
            case 'error':
                indicator.html('🔴 Connection Error').removeClass('status-connected status-connecting').addClass('status-error');
                break;
        }
    }
    
    function updateLastUpdateTime() {
        if (dashboardState.lastUpdate) {
            $('#last-update-time').text(getTimeAgo(dashboardState.lastUpdate));
        }
    }
    
    function testAPIConnection() {
        const button = $('#test-api-connection');
        button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: crawlguard_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crawlguard_test_connection',
                nonce: crawlguard_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('✅ API Connection Successful!', 'success');
                } else {
                    showNotification('❌ API Connection Failed: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotification('❌ Connection test failed', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    }
    
    function initializeNotifications() {
        // Create notification container if it doesn't exist
        if (!$('#crawlguard-notifications').length) {
            $('body').append('<div id="crawlguard-notifications"></div>');
        }
    }
    
    function showNotification(message, type = 'info', duration = 4000) {
        const notificationId = 'notification-' + Date.now();
        const typeIcons = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
        };
        
        const notificationHtml = `
            <div id="${notificationId}" class="crawlguard-notification notification-${type}">
                <span class="notification-icon">${typeIcons[type] || 'ℹ️'}</span>
                <span class="notification-message">${escapeHtml(message)}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        const container = $('#crawlguard-notifications');
        container.append(notificationHtml);
        
        const notification = $('#' + notificationId);
        notification.slideDown(300);
        
        // Auto-remove after duration
        setTimeout(function() {
            notification.slideUp(300, function() {
                $(this).remove();
            });
        }, duration);
        
        // Manual close button
        notification.find('.notification-close').on('click', function() {
            notification.slideUp(300, function() {
                $(this).remove();
            });
        });
    }
    
    function checkFirstTimeUser() {
        if (!localStorage.getItem('crawlguard_dashboard_visited')) {
            showWelcomeOnboarding();
            localStorage.setItem('crawlguard_dashboard_visited', 'true');
        }
    }
    
    function showWelcomeOnboarding() {
        const welcomeHtml = `
            <div class="crawlguard-welcome-modal">
                <div class="welcome-overlay"></div>
                <div class="welcome-content">
                    <h2>🚀 Welcome to CrawlGuard Pro!</h2>
                    <div class="welcome-steps">
                        <div class="step">
                            <div class="step-icon">🤖</div>
                            <h3>AI Bot Detection</h3>
                            <p>Your site is now protected against AI content scrapers</p>
                        </div>
                        <div class="step">
                            <div class="step-icon">💰</div>
                            <h3>Revenue Tracking</h3>
                            <p>Earn money when AI companies access your content</p>
                        </div>
                        <div class="step">
                            <div class="step-icon">📊</div>
                            <h3>Real-time Analytics</h3>
                            <p>Monitor everything with live dashboard updates</p>
                        </div>
                    </div>
                    <div class="welcome-actions">
                        <button id="start-onboarding" class="button button-primary">Get Started</button>
                        <button id="skip-onboarding" class="button">Skip for Now</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(welcomeHtml);
        
        $('#start-onboarding').on('click', function() {
            $('.crawlguard-welcome-modal').fadeOut(300, function() {
                $(this).remove();
            });
            showNotification('CrawlGuard Pro is now active and monitoring your site!', 'success');
        });
        
        $('#skip-onboarding').on('click', function() {
            $('.crawlguard-welcome-modal').fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    // Utility functions
    function getTimeAgo(date) {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' min ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
        return Math.floor(seconds / 86400) + ' days ago';
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Update last update time every minute
    setInterval(updateLastUpdateTime, 60000);
    
})(jQuery);

// CSS for enhanced dashboard styling
const dashboardCSS = `
<style>
.crawlguard-dashboard-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 15px 20px;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 20px;
}

.controls-left button {
    margin-right: 10px;
}

.controls-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.last-update {
    font-size: 13px;
    color: #666;
}

.connection-status {
    font-size: 12px;
}

.status-connected { color: #059669; }
.status-connecting { color: #d97706; }
.status-error { color: #dc2626; }

.metric-updating {
    opacity: 0.7;
    transform: scale(1.05);
    transition: all 0.3s ease;
}

.metric-updated {
    background: #f0fdf4;
    border-color: #22c55e;
    transition: all 0.3s ease;
}

.refresh-icon.rotating {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

#crawlguard-notifications {
    position: fixed;
    top: 32px;
    right: 20px;
    z-index: 999999;
    max-width: 400px;
}

.crawlguard-notification {
    background: #fff;
    border-left: 4px solid #3b82f6;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 12px 16px;
    margin-bottom: 10px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    position: relative;
}

.notification-success { border-left-color: #22c55e; }
.notification-error { border-left-color: #ef4444; }
.notification-warning { border-left-color: #f59e0b; }

.notification-icon {
    margin-right: 10px;
    font-size: 16px;
}

.notification-message {
    flex: 1;
    font-size: 14px;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    margin-left: 10px;
    opacity: 0.5;
}

.notification-close:hover {
    opacity: 1;
}

.crawlguard-welcome-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 999999;
}

.welcome-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
}

.welcome-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    text-align: center;
}

.welcome-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 30px;
    margin: 30px 0;
}

.step {
    text-align: center;
}

.step-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.step h3 {
    margin: 0 0 10px 0;
    color: #1f2937;
}

.step p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.welcome-actions {
    margin-top: 30px;
}

.welcome-actions button {
    margin: 0 10px;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s;
}

.activity-item:hover {
    background-color: #f9fafb;
}

.activity-icon {
    margin-right: 12px;
    font-size: 18px;
}

.activity-details {
    flex: 1;
}

.activity-main {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
}

.bot-type {
    background: #3b82f6;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.page-url {
    color: #6b7280;
    font-size: 13px;
}

.activity-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
}

.timestamp {
    color: #9ca3af;
}

.revenue-positive {
    color: #059669;
    font-weight: 500;
}

.revenue-zero {
    color: #6b7280;
}

.company-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #f3f4f6;
}

.company-stats {
    display: flex;
    gap: 15px;
    font-size: 13px;
}

.company-stats .count {
    color: #6b7280;
}

.company-stats .percentage {
    color: #3b82f6;
    font-weight: 500;
}

.company-stats .revenue {
    color: #059669;
    font-weight: 500;
}

.no-activity {
    text-align: center;
    color: #9ca3af;
    padding: 40px 20px;
    font-style: italic;
}
</style>
`;

// Inject CSS
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', dashboardCSS);
}
