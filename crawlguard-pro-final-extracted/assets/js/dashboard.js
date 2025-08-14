/**
 * CrawlGuard Pro Dashboard Enhancement
 * Real-time updates and interactive features
 */

(function($) {
    'use strict';
    
    // Dashboard state
    let dashboardState = {
        realtimeEnabled: true,
        updateInterval: 5000, // 5 seconds
        chartInstance: null,
        lastUpdate: null
    };
    
    // Initialize dashboard
    $(document).ready(function() {
        initializeDashboard();
        setupEventHandlers();
        startRealtimeUpdates();
        initializeCharts();
    });
    
    function initializeDashboard() {
        // Add loading indicators
        $('.metric-value').each(function() {
            $(this).attr('data-original', $(this).text());
        });
        
        // Add timestamp
        addLastUpdateTime();
        
        // Check for first-time users
        checkFirstTimeUser();
    }
    
    function setupEventHandlers() {
        // Refresh button
        $(document).on('click', '.refresh-btn', function(e) {
            e.preventDefault();
            refreshDashboard();
        });
        
        // Enable monetization button
        $(document).on('click', '.enable-monetization-btn', function(e) {
            e.preventDefault();
            showMonetizationModal();
        });
        
        // Toggle real-time updates
        $(document).on('click', '#toggle-realtime', function() {
            dashboardState.realtimeEnabled = !dashboardState.realtimeEnabled;
            $(this).text(dashboardState.realtimeEnabled ? 'Pause Updates' : 'Resume Updates');
        });
    }
    
    function startRealtimeUpdates() {
        if (dashboardState.realtimeEnabled) {
            fetchDashboardData();
        }
        
        setInterval(function() {
            if (dashboardState.realtimeEnabled) {
                fetchDashboardData();
            }
        }, dashboardState.updateInterval);
    }
    
    function fetchDashboardData() {
        $.ajax({
            url: crawlguard_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crawlguard_get_dashboard_data',
                nonce: crawlguard_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                    dashboardState.lastUpdate = new Date();
                    updateLastUpdateTime();
                }
            },
            error: function() {
                console.error('Failed to fetch dashboard data');
            }
        });
    }
    
    function updateDashboard(data) {
        // Update metrics with animation
        animateMetricUpdate('.revenue-value', data.revenue_today);
        animateMetricUpdate('.bots-blocked-value', data.bots_blocked_today);
        animateMetricUpdate('.detection-rate-value', data.detection_rate + '%');
        
        // Update bot activity feed
        if (data.recent_activity && data.recent_activity.length > 0) {
            updateBotActivityFeed(data.recent_activity);
        }
        
        // Update charts if data available
        if (data.chart_data) {
            updateCharts(data.chart_data);
        }
        
        // Show notifications for new detections
        if (data.new_detections > 0) {
            showNotification(`${data.new_detections} new AI bot${data.new_detections > 1 ? 's' : ''} detected!`);
        }
    }
    
    function animateMetricUpdate(selector, newValue) {
        const element = $(selector);
        const currentValue = element.text();
        
        if (currentValue !== newValue) {
            element.fadeOut(200, function() {
                $(this).text(newValue).fadeIn(200);
                $(this).parent().addClass('metric-updated');
                setTimeout(() => $(this).parent().removeClass('metric-updated'), 1000);
            });
        }
    }
    
    function updateBotActivityFeed(activities) {
        const feedContent = $('#bot-feed-content');
        const currentCount = feedContent.find('.bot-activity-item').length;
        
        activities.forEach(function(activity, index) {
            if (index >= currentCount) {
                const activityHtml = createActivityRow(activity);
                $(activityHtml).hide().prependTo(feedContent).fadeIn(500);
                
                // Remove old items if too many
                if (feedContent.find('.bot-activity-item').length > 10) {
                    feedContent.find('.bot-activity-item:last').fadeOut(500, function() {
                        $(this).remove();
                    });
                }
            }
        });
    }
    
    function createActivityRow(activity) {
        const timeAgo = getTimeAgo(activity.timestamp);
        const revenueClass = activity.revenue > 0 ? 'revenue-positive' : 'revenue-zero';
        
        return `
            <div class="bot-activity-item new-activity">
                <span class="bot-type-tag">${escapeHtml(activity.bot_type)}</span>
                <span>${escapeHtml(activity.page_url || '/')}</span>
                <span class="action-${activity.action}">${activity.action.toUpperCase()}</span>
                <span class="${revenueClass}">+$${parseFloat(activity.revenue).toFixed(4)}</span>
                <span class="time-ago">${timeAgo}</span>
            </div>
        `;
    }
    
    function initializeCharts() {
        const canvas = document.getElementById('revenue-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        dashboardState.chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4
                }, {
                    label: 'Bot Detections',
                    data: [],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Bot Detections'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });
    }
    
    function updateCharts(chartData) {
        if (!dashboardState.chartInstance || !chartData) return;
        
        // Update chart data
        dashboardState.chartInstance.data.labels = chartData.labels;
        dashboardState.chartInstance.data.datasets[0].data = chartData.revenue;
        dashboardState.chartInstance.data.datasets[1].data = chartData.detections;
        dashboardState.chartInstance.update();
    }
    
    function showMonetizationModal() {
        const modalHtml = `
            <div class="crawlguard-modal-overlay">
                <div class="crawlguard-modal">
                    <div class="modal-header">
                        <h2>🚀 Enable Monetization</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Start earning revenue from AI companies accessing your content!</p>
                        
                        <div class="monetization-options">
                            <div class="option-card">
                                <h3>Quick Start</h3>
                                <p>Enable basic monetization tracking</p>
                                <button class="btn-primary enable-basic">Enable Now</button>
                            </div>
                            
                            <div class="option-card recommended">
                                <span class="recommended-badge">Recommended</span>
                                <h3>Stripe Integration</h3>
                                <p>Full payment processing with Stripe</p>
                                <button class="btn-primary setup-stripe">Setup Stripe</button>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <p class="help-text">Need help? <a href="#" class="open-docs">View documentation</a></p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Handle modal events
        $('.modal-close, .crawlguard-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                $('.crawlguard-modal-overlay').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
        
        $('.enable-basic').on('click', function() {
            enableBasicMonetization();
        });
        
        $('.setup-stripe').on('click', function() {
            window.location.href = 'admin.php?page=crawlguard-settings#stripe';
        });
    }
    
    function enableBasicMonetization() {
        $.ajax({
            url: crawlguard_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crawlguard_enable_monetization',
                nonce: crawlguard_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Monetization enabled! Tracking will begin immediately.', 'success');
                    $('.crawlguard-modal-overlay').fadeOut(300, function() {
                        $(this).remove();
                    });
                    setTimeout(() => location.reload(), 1500);
                }
            }
        });
    }
    
    function checkFirstTimeUser() {
        if (!localStorage.getItem('crawlguard_welcomed')) {
            showWelcomeMessage();
            localStorage.setItem('crawlguard_welcomed', 'true');
        }
    }
    
    function showWelcomeMessage() {
        const welcomeHtml = `
            <div class="crawlguard-welcome-banner">
                <div class="welcome-content">
                    <h3>👋 Welcome to CrawlGuard Pro!</h3>
                    <p>Your AI content protection is now active. Here's what happens next:</p>
                    <ul>
                        <li>✅ AI bots are being detected automatically</li>
                        <li>📊 Analytics will populate as traffic arrives</li>
                        <li>💰 Enable monetization when you're ready</li>
                    </ul>
                    <button class="dismiss-welcome">Got it!</button>
                </div>
            </div>
        `;
        
        $('.wrap').prepend(welcomeHtml);
        
        $('.dismiss-welcome').on('click', function() {
            $('.crawlguard-welcome-banner').slideUp(300, function() {
                $(this).remove();
            });
        });
    }
    
    function showNotification(message, type = 'info') {
        const notificationHtml = `
            <div class="crawlguard-notification ${type}">
                <span class="notification-icon">${type === 'success' ? '✅' : 'ℹ️'}</span>
                <span class="notification-message">${escapeHtml(message)}</span>
            </div>
        `;
        
        $('body').append(notificationHtml);
        
        const notification = $('.crawlguard-notification').last();
        notification.fadeIn(300);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }
    
    function addLastUpdateTime() {
        const updateHtml = `
            <div class="last-update-info">
                <span>Last updated: <span id="last-update-time">just now</span></span>
                <button id="toggle-realtime" class="button-link">Pause Updates</button>
            </div>
        `;
        
        $('.crawlguard-header').append(updateHtml);
    }
    
    function updateLastUpdateTime() {
        if (dashboardState.lastUpdate) {
            $('#last-update-time').text(getTimeAgo(dashboardState.lastUpdate));
        }
    }
    
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
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    function refreshDashboard() {
        $('.refresh-icon').addClass('rotating');
        fetchDashboardData();
        
        setTimeout(function() {
            $('.refresh-icon').removeClass('rotating');
        }, 1000);
    }
    
})(jQuery);
