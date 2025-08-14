/**
 * PayPerCrawl Pro - Enterprise Dashboard JavaScript
 * 
 * @package PayPerCrawl
 * @version 4.0.0
 */

(function($) {
    'use strict';

    window.PayPerCrawlDashboard = {
        
        // Configuration
        config: {
            refreshInterval: 30000, // 30 seconds
            chartColors: {
                primary: '#667eea',
                secondary: '#764ba2',
                success: '#28a745',
                warning: '#ffc107',
                danger: '#dc3545',
                info: '#17a2b8'
            },
            apiUrl: typeof payperCrawlAjax !== 'undefined' ? payperCrawlAjax.ajaxurl : ajaxurl,
            nonce: typeof payperCrawlAjax !== 'undefined' ? payperCrawlAjax.nonce : ''
        },

        // Current chart instances
        charts: {},

        // Real-time data
        liveData: {
            lastUpdate: Date.now(),
            metrics: {},
            activity: []
        },

        /**
         * Initialize Dashboard
         */
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.loadInitialData();
            this.startRealTimeUpdates();
            this.initTooltips();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Refresh button
            $(document).on('click', '.ppc-refresh-btn', this.manualRefresh.bind(this));
            
            // Export button
            $(document).on('click', '.ppc-export-btn', this.exportData.bind(this));
            
            // Alert dismissals
            $(document).on('click', '.ppc-alert-dismiss', this.dismissAlert.bind(this));
            
            // Metric card clicks for detailed view
            $(document).on('click', '.ppc-metric-card', this.showMetricDetails.bind(this));
            
            // Bot detection settings
            $(document).on('change', '.ppc-setting-toggle', this.updateSetting.bind(this));
            
            // Real-time toggle
            $(document).on('change', '#ppc-realtime-toggle', this.toggleRealTime.bind(this));
            
            // Cloudflare integration buttons
            $(document).on('click', '.ppc-deploy-cloudflare', this.deployCloudflare.bind(this));
            $(document).on('click', '.ppc-test-cloudflare', this.testCloudflare.bind(this));
        },

        /**
         * Initialize Chart.js charts
         */
        initCharts: function() {
            this.initRevenueChart();
            this.initBotDetectionChart();
            this.initGeographicChart();
            this.initPerformanceChart();
        },

        /**
         * Initialize revenue trend chart
         */
        initRevenueChart: function() {
            var ctx = document.getElementById('ppc-revenue-chart');
            if (!ctx || typeof Chart === 'undefined') return;

            this.charts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Revenue ($)',
                        data: [],
                        borderColor: this.config.chartColors.success,
                        backgroundColor: this.config.chartColors.success + '20',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Bot Detections',
                        data: [],
                        borderColor: this.config.chartColors.primary,
                        backgroundColor: this.config.chartColors.primary + '20',
                        borderWidth: 2,
                        fill: false,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: this.config.chartColors.primary,
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Detections'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    },
                    animation: {
                        duration: 750,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        },

        /**
         * Initialize bot detection pie chart
         */
        initBotDetectionChart: function() {
            var ctx = document.getElementById('ppc-bot-chart');
            if (!ctx || typeof Chart === 'undefined') return;

            this.charts.botDetection = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['GPT Bots', 'Search Crawlers', 'Unknown Bots', 'Malicious'],
                    datasets: [{
                        data: [45, 25, 20, 10],
                        backgroundColor: [
                            this.config.chartColors.primary,
                            this.config.chartColors.success,
                            this.config.chartColors.warning,
                            this.config.chartColors.danger
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        /**
         * Initialize geographic distribution chart
         */
        initGeographicChart: function() {
            var ctx = document.getElementById('ppc-geo-chart');
            if (!ctx || typeof Chart === 'undefined') return;

            this.charts.geographic = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['USA', 'Germany', 'China', 'Russia', 'UK'],
                    datasets: [{
                        label: 'Bot Requests',
                        data: [150, 89, 76, 45, 32],
                        backgroundColor: this.config.chartColors.primary + '80',
                        borderColor: this.config.chartColors.primary,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },

        /**
         * Initialize performance metrics chart
         */
        initPerformanceChart: function() {
            var ctx = document.getElementById('ppc-performance-chart');
            if (!ctx || typeof Chart === 'undefined') return;

            this.charts.performance = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Detection Speed', 'Accuracy', 'Throughput', 'Memory Usage', 'CPU Usage'],
                    datasets: [{
                        label: 'Current Performance',
                        data: [85, 92, 78, 88, 75],
                        borderColor: this.config.chartColors.primary,
                        backgroundColor: this.config.chartColors.primary + '30',
                        pointBackgroundColor: this.config.chartColors.primary
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },

        /**
         * Load initial dashboard data
         */
        loadInitialData: function() {
            this.showLoading();
            
            $.ajax({
                url: this.config.apiUrl,
                type: 'POST',
                data: {
                    action: 'ppc_get_dashboard_data',
                    nonce: this.config.nonce
                },
                success: this.handleDataLoad.bind(this),
                error: this.handleLoadError.bind(this),
                complete: this.hideLoading.bind(this)
            });
        },

        /**
         * Handle successful data load
         */
        handleDataLoad: function(response) {
            if (response && response.success) {
                this.updateMetrics(response.data.metrics || {});
                this.updateCharts(response.data.charts || {});
                this.updateActivity(response.data.activity || []);
                this.updateSystemHealth(response.data.health || {});
                this.liveData.lastUpdate = Date.now();
            } else {
                this.showError(response.data || 'Failed to load dashboard data');
            }
        },

        /**
         * Handle data load error
         */
        handleLoadError: function(xhr, status, error) {
            console.error('Dashboard data load error:', error);
            this.showError('Network error: Unable to load dashboard data');
        },

        /**
         * Update metric cards
         */
        updateMetrics: function(metrics) {
            var self = this;
            Object.keys(metrics).forEach(function(key) {
                var $card = $('.ppc-metric-card[data-metric="' + key + '"]');
                if ($card.length) {
                    var $value = $card.find('.ppc-metric-value');
                    var $change = $card.find('.ppc-metric-change');
                    
                    // Animate value change
                    self.animateValue($value, metrics[key].value, metrics[key].format);
                    
                    // Update change indicator
                    if (metrics[key].change !== undefined) {
                        $change.removeClass('positive negative').addClass(metrics[key].change >= 0 ? 'positive' : 'negative');
                        $change.find('.dashicons').removeClass().addClass(metrics[key].change >= 0 ? 'dashicons-arrow-up-alt' : 'dashicons-arrow-down-alt');
                        $change.find('span').text(Math.abs(metrics[key].change) + '%');
                    }
                }
            });
        },

        /**
         * Animate value changes
         */
        animateValue: function($element, newValue, format) {
            if (!$element.length) return;
            
            format = format || 'number';
            var currentValue = parseFloat($element.text().replace(/[^0-9.-]/g, '')) || 0;
            var duration = 1000;
            var steps = 50;
            var increment = (newValue - currentValue) / steps;
            var current = currentValue;
            var step = 0;

            var timer = setInterval(function() {
                current += increment;
                step++;
                
                var displayValue;
                switch (format) {
                    case 'currency':
                        displayValue = '$' + Math.round(current).toLocaleString();
                        break;
                    case 'percentage':
                        displayValue = Math.round(current * 10) / 10 + '%';
                        break;
                    default:
                        displayValue = Math.round(current).toLocaleString();
                }
                
                $element.text(displayValue);
                
                if (step >= steps) {
                    clearInterval(timer);
                    // Final value
                    switch (format) {
                        case 'currency':
                            $element.text('$' + newValue.toLocaleString());
                            break;
                        case 'percentage':
                            $element.text(newValue + '%');
                            break;
                        default:
                            $element.text(newValue.toLocaleString());
                    }
                }
            }, duration / steps);
        },

        /**
         * Update all charts with new data
         */
        updateCharts: function(chartData) {
            // Update revenue chart
            if (this.charts.revenue && chartData.revenue) {
                this.charts.revenue.data.labels = chartData.revenue.labels || [];
                this.charts.revenue.data.datasets[0].data = chartData.revenue.revenue || [];
                this.charts.revenue.data.datasets[1].data = chartData.revenue.detections || [];
                this.charts.revenue.update('active');
            }
        },

        /**
         * Update activity feed
         */
        updateActivity: function(activityData) {
            var $feed = $('.ppc-activity-feed');
            if (!$feed.length || !Array.isArray(activityData)) return;

            // Clear existing items
            $feed.empty();

            // Add new activity items
            activityData.forEach(function(item) {
                var $item = $('<div class="ppc-activity-item">' +
                    '<div class="ppc-activity-icon">' + (item.icon || '🤖') + '</div>' +
                    '<div class="ppc-activity-content">' +
                        '<div class="ppc-activity-title">' + (item.title || 'Bot Detection') + '</div>' +
                        '<div class="ppc-activity-meta">' +
                            '<span>' + (item.time || 'Just now') + '</span>' +
                            '<span class="ppc-activity-revenue">' + (item.revenue || '$0.00') + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>');
                $feed.append($item);
            });

            // Show live indicator
            $('.ppc-live-indicator').show();
        },

        /**
         * Update system health indicators
         */
        updateSystemHealth: function(healthData) {
            if (!healthData || !healthData.indicators) return;

            var $healthList = $('.ppc-health-indicators');
            if ($healthList.length) {
                $healthList.empty();
                
                healthData.indicators.forEach(function(indicator) {
                    var statusClass = indicator.status === 'good' ? 'ppc-status-good' : 
                                     indicator.status === 'warning' ? 'ppc-status-warning' : 
                                     'ppc-status-critical';
                    
                    var $item = $('<div class="ppc-health-item">' +
                        '<div class="ppc-health-status ' + statusClass + '">' +
                            '<span class="ppc-status-dot"></span>' +
                        '</div>' +
                        '<div class="ppc-health-info">' +
                            '<div class="ppc-health-name">' + (indicator.name || 'System Check') + '</div>' +
                            '<div class="ppc-health-value">' + (indicator.value || 'Unknown') + '</div>' +
                        '</div>' +
                    '</div>');
                    $healthList.append($item);
                });
            }

            // Update overall score
            var $score = $('.ppc-score-value');
            if ($score.length && healthData.score !== undefined) {
                $score.text(healthData.score + '%');
                $score.removeClass('ppc-score-good ppc-score-warning ppc-score-critical');
                if (healthData.score >= 80) {
                    $score.addClass('ppc-score-good');
                } else if (healthData.score >= 60) {
                    $score.addClass('ppc-score-warning');
                } else {
                    $score.addClass('ppc-score-critical');
                }
            }
        },

        /**
         * Start real-time updates
         */
        startRealTimeUpdates: function() {
            var self = this;
            setInterval(function() {
                if ($('#ppc-realtime-toggle').is(':checked')) {
                    self.loadRealTimeData();
                }
            }, this.config.refreshInterval);
        },

        /**
         * Load real-time data updates
         */
        loadRealTimeData: function() {
            var self = this;
            $.ajax({
                url: this.config.apiUrl,
                type: 'POST',
                data: {
                    action: 'ppc_get_realtime_data',
                    nonce: this.config.nonce,
                    last_update: this.liveData.lastUpdate
                },
                success: function(response) {
                    if (response && response.success && response.data && response.data.updates) {
                        self.updateMetrics(response.data.metrics || {});
                        self.updateActivity(response.data.activity || []);
                        self.liveData.lastUpdate = Date.now();
                        
                        // Show live indicator
                        $('.ppc-live-indicator').fadeIn().fadeOut();
                    }
                },
                error: function(xhr, status, error) {
                    console.warn('Real-time update failed:', error);
                }
            });
        },

        /**
         * Manual refresh
         */
        manualRefresh: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            
            $btn.prop('disabled', true).addClass('loading');
            
            this.loadInitialData();
            
            setTimeout(function() {
                $btn.prop('disabled', false).removeClass('loading');
            }, 2000);
        },

        /**
         * Export dashboard data
         */
        exportData: function(e) {
            e.preventDefault();
            
            var exportData = {
                timestamp: new Date().toISOString(),
                metrics: this.liveData.metrics,
                charts: Object.keys(this.charts).map(function(key) {
                    return {
                        type: key,
                        data: this.charts[key] ? this.charts[key].data : {}
                    };
                })
            };
            
            var blob = new Blob([JSON.stringify(exportData, null, 2)], {
                type: 'application/json'
            });
            
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'paypercrawl-dashboard-' + Date.now() + '.json';
            a.click();
            
            URL.revokeObjectURL(url);
        },

        /**
         * Dismiss alert
         */
        dismissAlert: function(e) {
            e.preventDefault();
            $(e.currentTarget).closest('.ppc-alert').fadeOut(300, function() {
                $(this).remove();
            });
        },

        /**
         * Show metric details modal
         */
        showMetricDetails: function(e) {
            var $card = $(e.currentTarget);
            var metric = $card.data('metric');
            
            // Implementation for detailed metric view
            console.log('Show details for metric:', metric);
        },

        /**
         * Update plugin setting
         */
        updateSetting: function(e) {
            var $toggle = $(e.currentTarget);
            var setting = $toggle.data('setting');
            var value = $toggle.is(':checked');
            var self = this;
            
            $.ajax({
                url: this.config.apiUrl,
                type: 'POST',
                data: {
                    action: 'ppc_update_setting',
                    nonce: this.config.nonce,
                    setting: setting,
                    value: value
                },
                success: function(response) {
                    if (response && response.success) {
                        self.showNotification('Setting updated successfully', 'success');
                    } else {
                        self.showNotification('Failed to update setting', 'error');
                        $toggle.prop('checked', !value); // Revert
                    }
                },
                error: function() {
                    self.showNotification('Network error', 'error');
                    $toggle.prop('checked', !value); // Revert
                }
            });
        },

        /**
         * Toggle real-time updates
         */
        toggleRealTime: function(e) {
            var enabled = $(e.currentTarget).is(':checked');
            $('.ppc-live-indicator').toggle(enabled);
            
            if (enabled) {
                this.loadRealTimeData();
            }
        },

        /**
         * Deploy Cloudflare Workers
         */
        deployCloudflare: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var self = this;
            
            $btn.prop('disabled', true).text('Deploying...');
            
            $.ajax({
                url: this.config.apiUrl,
                type: 'POST',
                data: {
                    action: 'ppc_deploy_cloudflare',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response && response.success) {
                        self.showNotification('Cloudflare Workers deployed successfully!', 'success');
                    } else {
                        self.showNotification(response.data || 'Deployment failed', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Network error during deployment', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Deploy Workers');
                }
            });
        },

        /**
         * Test Cloudflare integration
         */
        testCloudflare: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var self = this;
            
            $btn.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: this.config.apiUrl,
                type: 'POST',
                data: {
                    action: 'ppc_test_cloudflare',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response && response.success) {
                        self.showNotification('Cloudflare integration is working!', 'success');
                    } else {
                        self.showNotification(response.data || 'Integration test failed', 'warning');
                    }
                },
                error: function() {
                    self.showNotification('Network error during test', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Test Integration');
                }
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                $(this).attr('title', $(this).data('tooltip'));
            });
        },

        /**
         * Show loading state
         */
        showLoading: function() {
            $('.ppc-dashboard-main').addClass('loading');
            if (!$('.ppc-loading-overlay').length) {
                $('<div class="ppc-loading-overlay"><div class="ppc-spinner"></div><span>Loading dashboard...</span></div>')
                    .appendTo('.paypercrawl-dashboard, body');
            }
        },

        /**
         * Hide loading state
         */
        hideLoading: function() {
            $('.ppc-dashboard-main').removeClass('loading');
            $('.ppc-loading-overlay').fadeOut(300, function() {
                $(this).remove();
            });
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            var icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            var $notification = $('<div class="ppc-notification ppc-notification-' + type + '" style="' +
                'position: fixed;' +
                'top: 20px;' +
                'right: 20px;' +
                'background: white;' +
                'border: 1px solid #ddd;' +
                'border-radius: 4px;' +
                'padding: 15px;' +
                'box-shadow: 0 2px 8px rgba(0,0,0,0.1);' +
                'z-index: 10000;' +
                'max-width: 350px;' +
            '">' +
                '<span class="ppc-notification-icon">' + icons[type] + '</span>' +
                '<span class="ppc-notification-message" style="margin-left: 10px;">' + message + '</span>' +
                '<button class="ppc-notification-close" style="' +
                    'float: right;' +
                    'background: none;' +
                    'border: none;' +
                    'font-size: 18px;' +
                    'cursor: pointer;' +
                    'margin-left: 10px;' +
                '">×</button>' +
            '</div>');
            
            $('body').append($notification);
            
            $notification.fadeIn(300);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual dismiss
            $notification.on('click', '.ppc-notification-close', function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Show error message
         */
        showError: function(message) {
            console.error('Dashboard Error:', message);
            this.showNotification(message, 'error');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof Chart !== 'undefined') {
            PayPerCrawlDashboard.init();
        } else {
            console.warn('Chart.js library not loaded, some features may not work');
            // Still initialize the dashboard for basic functionality
            PayPerCrawlDashboard.init();
        }
    });

})(jQuery);

/**
 * Global utility functions for PayPerCrawl
 */
window.PayPerCrawlUtils = {
    
    /**
     * Format currency
     */
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },
    
    /**
     * Format number with commas
     */
    formatNumber: function(num) {
        return new Intl.NumberFormat().format(num);
    },
    
    /**
     * Calculate percentage
     */
    calculatePercentage: function(value, total) {
        return total === 0 ? 0 : ((value / total) * 100).toFixed(1);
    },
    
    /**
     * Debounce function
     */
    debounce: function(func, wait) {
        var timeout;
        return function executedFunction() {
            var args = Array.prototype.slice.call(arguments);
            var later = function() {
                clearTimeout(timeout);
                func.apply(null, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};
