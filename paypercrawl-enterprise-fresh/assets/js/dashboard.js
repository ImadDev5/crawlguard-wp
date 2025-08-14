/**
 * PayPerCrawl Enterprise Dashboard JavaScript
 * 
 * Handles Chart.js integration, real-time updates, and dashboard interactions
 * 
 * @package PayPerCrawl_Enterprise
 * @version 6.0.0
 */

class PayPerCrawlDashboard {
    constructor() {
        this.charts = {};
        this.updateInterval = 30000; // 30 seconds
        this.autoRefresh = true;
        this.currentTab = 'overview';
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    /**
     * Initialize dashboard
     */
    init() {
        this.setupEventListeners();
        this.initializeCharts();
        this.startAutoRefresh();
        this.loadInitialData();
        this.setupModals();
        this.setupTabs();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Refresh buttons
        document.querySelectorAll('.refresh-dashboard').forEach(button => {
            button.addEventListener('click', () => this.refreshDashboard());
        });

        // Chart controls
        document.querySelectorAll('.chart-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => this.handleChartToggle(e));
        });

        // Time range selectors
        document.querySelectorAll('.time-range-selector').forEach(selector => {
            selector.addEventListener('change', (e) => this.handleTimeRangeChange(e));
        });

        // Auto-refresh toggle
        const autoRefreshToggle = document.getElementById('auto-refresh-toggle');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                this.autoRefresh = e.target.checked;
                if (this.autoRefresh) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            });
        }

        // Modal triggers
        document.querySelectorAll('[data-modal]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.openModal(e.target.dataset.modal);
            });
        });

        // Close modal handlers
        document.querySelectorAll('.close-modal').forEach(closer => {
            closer.addEventListener('click', () => this.closeAllModals());
        });

        // Click outside modal to close
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('paypercrawl-modal')) {
                this.closeAllModals();
            }
        });

        // Escape key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    /**
     * Initialize all charts
     */
    initializeCharts() {
        this.initRevenueChart();
        this.initDetectionChart();
        this.initBotTypesChart();
        this.initPerformanceChart();
    }

    /**
     * Initialize revenue chart
     */
    initRevenueChart() {
        const canvas = document.getElementById('revenue-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.revenue = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [],
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4CAF50',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(44, 62, 80, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#4CAF50',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `Revenue: $${context.parsed.y.toFixed(2)}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6c757d',
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#6c757d',
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    /**
     * Initialize detection chart
     */
    initDetectionChart() {
        const canvas = document.getElementById('detection-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.detection = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Detections',
                    data: [],
                    backgroundColor: '#2196F3',
                    borderColor: '#1976D2',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(44, 62, 80, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#2196F3',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6c757d',
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#6c757d',
                            font: {
                                size: 12
                            },
                            stepSize: 1
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    /**
     * Initialize bot types chart
     */
    initBotTypesChart() {
        const canvas = document.getElementById('bot-types-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.botTypes = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#ff6b35',
                        '#2196F3',
                        '#4CAF50',
                        '#9C27B0',
                        '#FF9800',
                        '#607D8B'
                    ],
                    borderWidth: 0,
                    hoverBorderWidth: 2,
                    hoverBorderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            color: '#495057',
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(44, 62, 80, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderWidth: 0,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%',
                animation: {
                    animateRotate: true,
                    duration: 1000
                }
            }
        });
    }

    /**
     * Initialize performance chart
     */
    initPerformanceChart() {
        const canvas = document.getElementById('performance-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.performance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Response Time (ms)',
                        data: [],
                        borderColor: '#9C27B0',
                        backgroundColor: 'rgba(156, 39, 176, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'CPU Usage (%)',
                        data: [],
                        borderColor: '#FF9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            color: '#495057',
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(44, 62, 80, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderWidth: 0,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6c757d',
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#6c757d',
                            font: {
                                size: 12
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            color: '#6c757d',
                            font: {
                                size: 12
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    /**
     * Load initial data
     */
    async loadInitialData() {
        try {
            this.showLoading();
            
            // Load dashboard data
            const response = await this.apiCall('get_dashboard_data', {
                time_range: '24h'
            });

            if (response.success) {
                this.updateDashboardData(response.data);
            }
        } catch (error) {
            console.error('Failed to load initial data:', error);
            this.showError('Failed to load dashboard data');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Update dashboard data
     */
    updateDashboardData(data) {
        // Update metric cards
        this.updateMetricCards(data.metrics);
        
        // Update charts
        this.updateChartData('revenue', data.charts.revenue);
        this.updateChartData('detection', data.charts.detections);
        this.updateChartData('botTypes', data.charts.bot_types);
        this.updateChartData('performance', data.charts.performance);
        
        // Update recent detections table
        this.updateRecentDetections(data.recent_detections);
        
        // Update top bots
        this.updateTopBots(data.top_bots);
        
        // Update quick stats
        this.updateQuickStats(data.quick_stats);
    }

    /**
     * Update metric cards
     */
    updateMetricCards(metrics) {
        Object.keys(metrics).forEach(key => {
            const card = document.querySelector(`[data-metric="${key}"]`);
            if (card) {
                const valueElement = card.querySelector('.metric-value');
                const labelElement = card.querySelector('.metric-label');
                
                if (valueElement) {
                    valueElement.textContent = this.formatMetricValue(key, metrics[key].value);
                }
                
                if (labelElement && metrics[key].change) {
                    const change = metrics[key].change;
                    const changeText = change > 0 ? `+${change}%` : `${change}%`;
                    const changeClass = change > 0 ? 'positive' : 'negative';
                    labelElement.innerHTML = `${metrics[key].label} <span class="${changeClass}">${changeText}</span>`;
                }
            }
        });
    }

    /**
     * Format metric value
     */
    formatMetricValue(type, value) {
        switch (type) {
            case 'revenue':
            case 'potential_revenue':
                return `$${parseFloat(value).toFixed(2)}`;
            case 'detections':
                return parseInt(value).toLocaleString();
            case 'performance':
                return `${parseFloat(value).toFixed(1)}ms`;
            default:
                return value;
        }
    }

    /**
     * Update chart data
     */
    updateChartData(chartType, data) {
        const chart = this.charts[chartType];
        if (!chart || !data) return;

        chart.data.labels = data.labels;
        
        if (chartType === 'botTypes') {
            chart.data.datasets[0].data = data.values;
        } else if (chartType === 'performance') {
            chart.data.datasets[0].data = data.response_times;
            chart.data.datasets[1].data = data.cpu_usage;
        } else {
            chart.data.datasets[0].data = data.values;
        }
        
        chart.update('active');
    }

    /**
     * Update recent detections table
     */
    updateRecentDetections(detections) {
        const tbody = document.querySelector('#recent-detections-tbody');
        if (!tbody) return;

        if (!detections || detections.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-detections">No recent detections</td></tr>';
            return;
        }

        tbody.innerHTML = detections.map(detection => `
            <tr>
                <td>${this.formatDate(detection.detected_at)}</td>
                <td><strong>${detection.bot_name}</strong></td>
                <td><span class="confidence-badge ${this.getConfidenceClass(detection.confidence)}">${detection.confidence}%</span></td>
                <td class="page-cell" title="${detection.page_url}">${detection.page_url}</td>
                <td class="revenue-cell">$${parseFloat(detection.revenue).toFixed(2)}</td>
                <td>${detection.detection_method}</td>
            </tr>
        `).join('');
    }

    /**
     * Update top bots
     */
    updateTopBots(topBots) {
        const container = document.querySelector('.top-bots-grid');
        if (!container || !topBots) return;

        container.innerHTML = topBots.map(bot => `
            <div class="top-bot-card">
                <div class="bot-header">
                    <h4>${bot.name}</h4>
                    <span class="bot-badge">${bot.tier}</span>
                </div>
                <div class="bot-stats">
                    <div class="stat">
                        <span class="stat-value">${bot.detections}</span>
                        <span class="stat-label">Detections</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">$${parseFloat(bot.revenue).toFixed(2)}</span>
                        <span class="stat-label">Revenue</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">${bot.confidence}%</span>
                        <span class="stat-label">Avg Confidence</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    /**
     * Update quick stats
     */
    updateQuickStats(stats) {
        const container = document.querySelector('.stats-list');
        if (!container || !stats) return;

        container.innerHTML = Object.keys(stats).map(key => `
            <div class="stat-item">
                <span class="stat-label">${stats[key].label}</span>
                <span class="stat-value ${stats[key].trend || ''}">${stats[key].value}</span>
            </div>
        `).join('');
    }

    /**
     * Handle chart toggle
     */
    handleChartToggle(event) {
        const toggle = event.target;
        const chartType = toggle.dataset.chart;
        const isActive = toggle.classList.contains('active');
        
        // Toggle active state
        document.querySelectorAll('.chart-toggle').forEach(t => {
            if (t.dataset.chart === chartType) {
                t.classList.toggle('active');
            }
        });
        
        // Update chart visibility or type
        if (chartType && this.charts[chartType]) {
            // You can implement chart type switching here
            this.charts[chartType].update();
        }
    }

    /**
     * Handle time range change
     */
    async handleTimeRangeChange(event) {
        const timeRange = event.target.value;
        
        try {
            this.showLoading();
            
            const response = await this.apiCall('get_dashboard_data', {
                time_range: timeRange
            });

            if (response.success) {
                this.updateDashboardData(response.data);
            }
        } catch (error) {
            console.error('Failed to update time range:', error);
            this.showError('Failed to update dashboard');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Refresh dashboard
     */
    async refreshDashboard() {
        try {
            this.showLoading();
            
            const timeRange = document.querySelector('.time-range-selector')?.value || '24h';
            const response = await this.apiCall('get_dashboard_data', {
                time_range: timeRange
            });

            if (response.success) {
                this.updateDashboardData(response.data);
                this.showSuccess('Dashboard refreshed');
            }
        } catch (error) {
            console.error('Failed to refresh dashboard:', error);
            this.showError('Failed to refresh dashboard');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Start auto refresh
     */
    startAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        if (this.autoRefresh) {
            this.refreshTimer = setInterval(() => {
                this.refreshDashboard();
            }, this.updateInterval);
        }
    }

    /**
     * Stop auto refresh
     */
    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    /**
     * Setup modals
     */
    setupModals() {
        // Modal functionality is already handled in setupEventListeners
    }

    /**
     * Open modal
     */
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    /**
     * Close all modals
     */
    closeAllModals() {
        document.querySelectorAll('.paypercrawl-modal').forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = '';
    }

    /**
     * Setup tabs
     */
    setupTabs() {
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const tabId = e.target.dataset.tab;
                this.switchTab(tabId);
            });
        });
    }

    /**
     * Switch tab
     */
    switchTab(tabId) {
        // Update active tab button
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabId}"]`)?.classList.add('active');
        
        // Update active tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`tab-${tabId}`)?.classList.add('active');
        
        this.currentTab = tabId;
        
        // Load tab-specific data if needed
        this.loadTabData(tabId);
    }

    /**
     * Load tab-specific data
     */
    async loadTabData(tabId) {
        if (tabId === 'analytics') {
            // Load analytics data
            try {
                const response = await this.apiCall('get_analytics_data');
                if (response.success) {
                    this.updateAnalyticsData(response.data);
                }
            } catch (error) {
                console.error('Failed to load analytics data:', error);
            }
        }
    }

    /**
     * Update analytics data
     */
    updateAnalyticsData(data) {
        // Update forecast cards
        const forecastContainer = document.querySelector('.forecast-cards');
        if (forecastContainer && data.forecasts) {
            forecastContainer.innerHTML = Object.keys(data.forecasts).map(key => {
                const forecast = data.forecasts[key];
                return `
                    <div class="forecast-card">
                        <h4>${forecast.label}</h4>
                        <div class="forecast-value">${forecast.value}</div>
                        <div class="forecast-confidence">Confidence: ${forecast.confidence}%</div>
                        <div class="forecast-trend ${forecast.trend_direction}">${forecast.trend}</div>
                    </div>
                `;
            }).join('');
        }
    }

    /**
     * API call helper
     */
    async apiCall(action, data = {}) {
        const formData = new FormData();
        formData.append('action', 'paypercrawl_ajax');
        formData.append('paypercrawl_action', action);
        formData.append('nonce', paypercrawlAjax.nonce);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        const response = await fetch(paypercrawlAjax.ajaxurl, {
            method: 'POST',
            body: formData
        });

        return await response.json();
    }

    /**
     * Show loading state
     */
    showLoading() {
        document.querySelectorAll('.paypercrawl-card').forEach(card => {
            card.classList.add('loading');
        });
    }

    /**
     * Hide loading state
     */
    hideLoading() {
        document.querySelectorAll('.paypercrawl-card').forEach(card => {
            card.classList.remove('loading');
        });
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showNotification(message, 'error');
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `paypercrawl-notification ${type}`;
        notification.textContent = message;
        
        // Style the notification
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '12px 20px',
            borderRadius: '8px',
            color: 'white',
            fontWeight: '600',
            zIndex: '999999',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease',
            backgroundColor: type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'
        });
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Utility functions
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    getConfidenceClass(confidence) {
        if (confidence >= 80) return 'high-confidence';
        if (confidence >= 60) return 'medium-confidence';
        return 'low-confidence';
    }
}

// Initialize dashboard when script loads
const paypercrawlDashboard = new PayPerCrawlDashboard();

// Export for global access
window.PayPerCrawlDashboard = PayPerCrawlDashboard;
