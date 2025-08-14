/**
 * PayPerCrawl Admin JavaScript
 * 
 * @package PayPerCrawl
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Global variables
    let trendsChart = null;
    let companiesChart = null;

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeDashboard();
        initializeSettings();
        initializeAnalytics();
        initializeCommon();
    });

    /**
     * Initialize Dashboard functionality
     */
    function initializeDashboard() {
        if (!$('.paypercrawl-dashboard').length) return;

        // Initialize main dashboard chart
        initializeDashboardChart();
        
        // Refresh stats button
        $('.refresh-stats').on('click', function(e) {
            e.preventDefault();
            refreshDashboardStats();
        });

        // Auto-refresh every 5 minutes
        setInterval(refreshDashboardStats, 300000);
    }

    /**
     * Initialize Settings functionality
     */
    function initializeSettings() {
        if (!$('.paypercrawl-settings').length) return;

        // Test API connection
        $('.test-api-btn').on('click', function(e) {
            e.preventDefault();
            testApiConnection($(this));
        });

        // Save settings with validation
        $('#paypercrawl-settings-form').on('submit', function(e) {
            if (!validateSettings()) {
                e.preventDefault();
                return false;
            }
        });

        // Bot action radio changes
        $('input[name="bot_action"]').on('change', function() {
            updateBotActionPreview($(this).val());
        });

        // Show/hide conditional fields
        toggleConditionalFields();
        $('input[name="enable_logging"]').on('change', toggleConditionalFields);
    }

    /**
     * Initialize Analytics functionality
     */
    function initializeAnalytics() {
        if (!$('.paypercrawl-analytics').length) return;

        // Initialize analytics charts
        initializeAnalyticsCharts();
        
        // Table filtering and sorting
        initializeTableFeatures();
        
        // Export functionality
        $('.export-csv').on('click', function(e) {
            e.preventDefault();
            exportAnalyticsData();
        });

        // Chart period controls
        $('.chart-period').on('click', function(e) {
            e.preventDefault();
            updateChartPeriod($(this));
        });
    }

    /**
     * Initialize common functionality
     */
    function initializeCommon() {
        // Tooltips
        $('[title]').tooltip();
        
        // Notice dismissal
        $('.notice-dismiss').on('click', function() {
            $(this).closest('.notice').fadeOut();
        });

        // Copy to clipboard functionality
        $('.copy-to-clipboard').on('click', function(e) {
            e.preventDefault();
            copyToClipboard($(this).data('text'));
        });
    }

    /**
     * Initialize main dashboard chart
     */
    function initializeDashboardChart() {
        const chartCanvas = $('#dashboard-chart');
        if (!chartCanvas.length) return;

        const ctx = chartCanvas[0].getContext('2d');
        
        // Get chart data
        $.post(paypercrawl_ajax.ajax_url, {
            action: 'paypercrawl_get_chart_data',
            nonce: paypercrawl_ajax.nonce,
            period: 7
        }, function(response) {
            if (response.success) {
                trendsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            label: 'Bot Detections',
                            data: response.data.detections,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6
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
                                backgroundColor: 'rgba(31, 41, 55, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#2563eb',
                                borderWidth: 1,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(229, 231, 235, 0.5)'
                                },
                                ticks: {
                                    color: '#6b7280'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(229, 231, 235, 0.5)'
                                },
                                ticks: {
                                    color: '#6b7280'
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
        });
    }

    /**
     * Initialize analytics charts
     */
    function initializeAnalyticsCharts() {
        initializeTrendsChart();
        initializeCompaniesChart();
        initializeHeatmap();
    }

    /**
     * Initialize trends chart for analytics
     */
    function initializeTrendsChart() {
        const chartCanvas = $('#trends-chart');
        if (!chartCanvas.length) return;

        const ctx = chartCanvas[0].getContext('2d');
        
        $.post(paypercrawl_ajax.ajax_url, {
            action: 'paypercrawl_get_analytics_data',
            nonce: paypercrawl_ajax.nonce,
            type: 'trends'
        }, function(response) {
            if (response.success) {
                trendsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            label: 'Total Detections',
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

    /**
     * Initialize companies chart
     */
    function initializeCompaniesChart() {
        const chartCanvas = $('#companies-chart');
        if (!chartCanvas.length) return;

        const ctx = chartCanvas[0].getContext('2d');
        
        $.post(paypercrawl_ajax.ajax_url, {
            action: 'paypercrawl_get_analytics_data',
            nonce: paypercrawl_ajax.nonce,
            type: 'companies'
        }, function(response) {
            if (response.success) {
                companiesChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            data: response.data.values,
                            backgroundColor: [
                                '#2563eb',
                                '#16a34a',
                                '#dc2626',
                                '#ea580c',
                                '#7c3aed',
                                '#0891b2',
                                '#c2410c'
                            ],
                            borderWidth: 0
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
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const percentage = ((context.parsed / context.dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1);
                                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    }

    /**
     * Initialize detection heatmap
     */
    function initializeHeatmap() {
        $.post(paypercrawl_ajax.ajax_url, {
            action: 'paypercrawl_get_analytics_data',
            nonce: paypercrawl_ajax.nonce,
            type: 'heatmap'
        }, function(response) {
            if (response.success) {
                renderHeatmap(response.data);
            }
        });
    }

    /**
     * Render heatmap visualization
     */
    function renderHeatmap(data) {
        const heatmapContainer = $('#detection-heatmap');
        if (!heatmapContainer.length) return;

        heatmapContainer.empty();

        for (let hour = 0; hour < 24; hour++) {
            const hourData = data[hour] || 0;
            const intensity = Math.min(hourData / Math.max(...Object.values(data)), 1);
            const hourString = hour.toString().padStart(2, '0') + ':00';

            const hourElement = $('<div>')
                .addClass('heatmap-hour')
                .attr('data-hour', hour)
                .attr('data-count', hourData)
                .attr('title', `${hourString}: ${hourData} detections`)
                .css('opacity', 0.2 + (intensity * 0.8))
                .text(hourString);

            heatmapContainer.append(hourElement);
        }

        // Add hover effects
        $('.heatmap-hour').hover(
            function() {
                $(this).css('transform', 'scale(1.1)');
            },
            function() {
                $(this).css('transform', 'scale(1)');
            }
        );
    }

    /**
     * Initialize table features (filtering, sorting)
     */
    function initializeTableFeatures() {
        // Table filtering
        $('#company-filter, #confidence-filter').on('change', function() {
            filterDetectionsTable();
        });

        // Table sorting
        $('.sortable').on('click', function() {
            sortTable($(this));
        });

        // Pagination if needed
        initializeTablePagination();
    }

    /**
     * Filter detections table
     */
    function filterDetectionsTable() {
        const companyFilter = $('#company-filter').val().toLowerCase();
        const confidenceFilter = $('#confidence-filter').val().toLowerCase();
        
        $('#detections-table tbody tr').each(function() {
            const $row = $(this);
            const company = $row.data('company').toString().toLowerCase();
            const confidence = $row.data('confidence').toString().toLowerCase();
            
            const companyMatch = !companyFilter || company.includes(companyFilter);
            const confidenceMatch = !confidenceFilter || confidence === confidenceFilter;
            
            $row.toggle(companyMatch && confidenceMatch);
        });

        updateTableStats();
    }

    /**
     * Sort table by column
     */
    function sortTable($header) {
        const table = $header.closest('table');
        const tbody = table.find('tbody');
        const rows = tbody.find('tr').toArray();
        const columnIndex = $header.index();
        const isAscending = !$header.hasClass('sort-desc');

        // Remove previous sort classes
        table.find('th').removeClass('sort-asc sort-desc');
        
        // Add current sort class
        $header.addClass(isAscending ? 'sort-asc' : 'sort-desc');

        rows.sort(function(a, b) {
            const aVal = $(a).find('td').eq(columnIndex).text().trim();
            const bVal = $(b).find('td').eq(columnIndex).text().trim();
            
            // Try numeric comparison first
            const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
            const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            }
            
            // String comparison
            return isAscending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        });

        tbody.empty().append(rows);
    }

    /**
     * Update table statistics
     */
    function updateTableStats() {
        const totalRows = $('#detections-table tbody tr').length;
        const visibleRows = $('#detections-table tbody tr:visible').length;
        
        $('.table-stats').text(`Showing ${visibleRows} of ${totalRows} detections`);
    }

    /**
     * Initialize table pagination
     */
    function initializeTablePagination() {
        // Implementation depends on requirements
        // For now, just show/hide load more button
        const loadMoreBtn = $('.load-more-detections');
        if (loadMoreBtn.length) {
            loadMoreBtn.on('click', function() {
                loadMoreDetections($(this));
            });
        }
    }

    /**
     * Load more detections via AJAX
     */
    function loadMoreDetections($button) {
        const offset = $('#detections-table tbody tr').length;
        
        $button.prop('disabled', true).text('Loading...');
        
        $.post(paypercrawl_ajax.ajax_url, {
            action: 'paypercrawl_load_more_detections',
            nonce: paypercrawl_ajax.nonce,
            offset: offset
        }, function(response) {
            if (response.success && response.data.html) {
                $('#detections-table tbody').append(response.data.html);
                
                if (!response.data.has_more) {
                    $button.hide();
                } else {
                    $button.prop('disabled', false).text('Load More');
                }
            }
        });
    }

    /**
     * Test API connection
     */
    function testApiConnection($button) {
        const apiKey = $('#api_key').val();
        const apiSecret = $('#api_secret').val();
        
        if (!apiKey || !apiSecret) {
            showNotice('Please enter both API key and secret', 'error');
            return;
        }

        $button.prop('disabled', true).text('Testing...');
        
        $.post(paypercrawl_ajax.ajax_url, {
            action: 'paypercrawl_test_api',
            nonce: paypercrawl_ajax.nonce,
            api_key: apiKey,
            api_secret: apiSecret
        }, function(response) {
            $button.prop('disabled', false).text('Test Connection');
            
            if (response.success) {
                showNotice('API connection successful!', 'success');
            } else {
                showNotice('API connection failed: ' + response.data.message, 'error');
            }
        });
    }

    /**
     * Validate settings form
     */
    function validateSettings() {
        let isValid = true;
        
        // Validate API credentials if they're required
        const apiKey = $('#api_key').val();
        const apiSecret = $('#api_secret').val();
        
        if ((apiKey && !apiSecret) || (!apiKey && apiSecret)) {
            showNotice('Both API key and secret are required', 'error');
            isValid = false;
        }
        
        return isValid;
    }

    /**
     * Update bot action preview
     */
    function updateBotActionPreview(action) {
        const preview = $('.bot-action-preview');
        const messages = {
            'block': 'Bots will be blocked with a 403 Forbidden response',
            'log': 'Bot visits will be logged but allowed to continue',
            'redirect': 'Bots will be redirected to a custom page'
        };
        
        preview.text(messages[action] || '');
    }

    /**
     * Toggle conditional fields based on settings
     */
    function toggleConditionalFields() {
        const enableLogging = $('input[name="enable_logging"]:checked').val();
        const logFields = $('.log-conditional');
        
        if (enableLogging === '1') {
            logFields.show();
        } else {
            logFields.hide();
        }
    }

    /**
     * Refresh dashboard stats
     */
    function refreshDashboardStats() {
        $.post(paypercrawl_ajax.ajax_url, {
            action: 'paypercrawl_refresh_stats',
            nonce: paypercrawl_ajax.nonce
        }, function(response) {
            if (response.success) {
                // Update stat cards
                $('.stat-value[data-stat="detections"]').text(response.data.detections);
                $('.stat-value[data-stat="revenue"]').text('$' + response.data.revenue);
                $('.stat-value[data-stat="bots"]').text(response.data.bots);
                $('.stat-value[data-stat="accuracy"]').text(response.data.accuracy + '%');
                
                // Update chart if needed
                if (trendsChart && response.data.chart) {
                    trendsChart.data = response.data.chart;
                    trendsChart.update();
                }
                
                showNotice('Stats refreshed successfully', 'success', 3000);
            }
        });
    }

    /**
     * Update chart period
     */
    function updateChartPeriod($button) {
        const period = $button.data('period');
        
        $('.chart-period').removeClass('active');
        $button.addClass('active');
        
        // Update charts with new period
        $.post(paypercrawl_ajax.ajax_url, {
            action: 'paypercrawl_get_chart_data',
            nonce: paypercrawl_ajax.nonce,
            period: period
        }, function(response) {
            if (response.success && trendsChart) {
                trendsChart.data.labels = response.data.labels;
                trendsChart.data.datasets[0].data = response.data.detections;
                trendsChart.update();
            }
        });
    }

    /**
     * Export analytics data
     */
    function exportAnalyticsData() {
        const form = $('<form>', {
            method: 'POST',
            action: paypercrawl_ajax.ajax_url
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'paypercrawl_export_csv'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: paypercrawl_ajax.nonce
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
    }

    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showNotice('Copied to clipboard', 'success', 2000);
            });
        } else {
            // Fallback for older browsers
            const textArea = $('<textarea>').val(text).appendTo('body');
            textArea[0].select();
            document.execCommand('copy');
            textArea.remove();
            showNotice('Copied to clipboard', 'success', 2000);
        }
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type, duration) {
        const notice = $('<div>', {
            class: `notice notice-${type} is-dismissible`,
            html: `<p>${message}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>`
        });
        
        $('.wrap').prepend(notice);
        
        // Auto-dismiss after duration
        if (duration) {
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, duration);
        }
        
        // Manual dismiss
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        });
    }

    /**
     * Format numbers for display
     */
    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    /**
     * Format currency for display
     */
    function formatCurrency(amount) {
        return '$' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Make functions available globally if needed
    window.PayPerCrawl = {
        refreshStats: refreshDashboardStats,
        testApi: testApiConnection,
        showNotice: showNotice,
        formatNumber: formatNumber,
        formatCurrency: formatCurrency
    };

})(jQuery);
