/**
 * Pay Per Crawl Admin JavaScript
 * Version: 3.0.0
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initDashboard();
        initCharts();
        initActivityFeed();
        initSettings();
        initBotDetection();
    });

    /**
     * Initialize Dashboard Components
     */
    function initDashboard() {
        // Auto-refresh stats every 30 seconds
        setInterval(refreshDashboardStats, 30000);
        
        // Timeframe selector
        $('#ppc-timeframe').on('change', function() {
            const timeframe = $(this).val();
            updateRevenueChart(timeframe);
        });
        
        // Enable auto-scale button
        $('.ppc-enable-autoscale').on('click', function(e) {
            e.preventDefault();
            enableAutoScaling();
        });
    }

    /**
     * Initialize Charts
     */
    function initCharts() {
        const ctx = document.getElementById('ppc-revenue-chart');
        if (!ctx) return;
        
        // Revenue Chart
        window.ppcRevenueChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    data: [],
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    borderWidth: 2,
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
        
        // Load initial data
        updateRevenueChart('30d');
    }

    /**
     * Update Revenue Chart
     */
    function updateRevenueChart(timeframe) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'paypercrawl_get_revenue_data',
                timeframe: timeframe,
                nonce: paypercrawl_admin.nonce
            },
            success: function(response) {
                if (response.success && window.ppcRevenueChart) {
                    window.ppcRevenueChart.data.labels = response.data.labels;
                    window.ppcRevenueChart.data.datasets[0].data = response.data.values;
                    window.ppcRevenueChart.update();
                }
            }
        });
    }

    /**
     * Initialize Activity Feed
     */
    function initActivityFeed() {
        // Refresh button
        $('#ppc-refresh-activity').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).html('🔄 Loading...');
            
            refreshActivityFeed(function() {
                $btn.prop('disabled', false).html('🔄 Refresh');
            });
        });
        
        // Auto-refresh every minute
        setInterval(refreshActivityFeed, 60000);
    }

    /**
     * Refresh Activity Feed
     */
    function refreshActivityFeed(callback) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'paypercrawl_bot_activity',
                nonce: paypercrawl_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#ppc-bot-activity').html(response.data.html);
                    animateNewItems();
                }
                if (callback) callback();
            }
        });
    }

    /**
     * Animate New Activity Items
     */
    function animateNewItems() {
        $('.ppc-activity-item').each(function(index) {
            $(this).delay(index * 50).animate({
                opacity: 1,
                marginLeft: 0
            }, 300);
        });
    }

    /**
     * Refresh Dashboard Stats
     */
    function refreshDashboardStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'paypercrawl_dashboard_stats',
                nonce: paypercrawl_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatCards(response.data);
                }
            }
        });
    }

    /**
     * Update Stat Cards
     */
    function updateStatCards(data) {
        // Animate value changes
        $('.ppc-stat-value').each(function() {
            const $el = $(this);
            const newValue = data[$el.data('stat')];
            if (newValue !== undefined) {
                animateValue($el, newValue);
            }
        });
    }

    /**
     * Animate Numeric Values
     */
    function animateValue($element, newValue) {
        const currentValue = parseFloat($element.text().replace(/[^0-9.-]+/g, ''));
        const isPrice = $element.text().includes('$');
        
        $({ value: currentValue }).animate({ value: newValue }, {
            duration: 600,
            easing: 'swing',
            step: function() {
                if (isPrice) {
                    $element.text('$' + this.value.toFixed(2));
                } else {
                    $element.text(Math.round(this.value));
                }
            }
        });
    }

    /**
     * Initialize Settings
     */
    function initSettings() {
        // Save settings with AJAX
        $('#paypercrawl-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submit = $form.find('input[type="submit"]');
            
            $submit.prop('disabled', true).val('Saving...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $form.serialize() + '&action=paypercrawl_save_settings',
                success: function(response) {
                    if (response.success) {
                        showNotice('Settings saved successfully!', 'success');
                    } else {
                        showNotice('Error saving settings. Please try again.', 'error');
                    }
                },
                complete: function() {
                    $submit.prop('disabled', false).val('Save Changes');
                }
            });
        });
        
        // Toggle advanced settings
        $('#ppc-show-advanced').on('change', function() {
            $('.ppc-advanced-settings').toggle($(this).is(':checked'));
        });
    }

    /**
     * Initialize Bot Detection Settings
     */
    function initBotDetection() {
        // Bot rate sliders
        $('.ppc-bot-rate-slider').on('input', function() {
            const value = $(this).val();
            $(this).siblings('.ppc-rate-value').text('$' + parseFloat(value).toFixed(2));
        });
        
        // Test bot detection
        $('#ppc-test-detection').on('click', function() {
            const userAgent = $('#ppc-test-useragent').val();
            
            if (!userAgent) {
                showNotice('Please enter a user agent string to test.', 'warning');
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'paypercrawl_test_detection',
                    user_agent: userAgent,
                    nonce: paypercrawl_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        displayDetectionResult(response.data);
                    }
                }
            });
        });
    }

    /**
     * Display Detection Test Result
     */
    function displayDetectionResult(data) {
        let html = '<div class="ppc-detection-result">';
        
        if (data.detected) {
            html += '<h4>✅ Bot Detected!</h4>';
            html += '<p><strong>Bot Name:</strong> ' + data.bot_name + '</p>';
            html += '<p><strong>Company:</strong> ' + data.company + '</p>';
            html += '<p><strong>Type:</strong> ' + data.type + '</p>';
            html += '<p><strong>Rate:</strong> $' + data.rate.toFixed(2) + '</p>';
        } else {
            html += '<h4>❌ No Bot Detected</h4>';
            html += '<p>This user agent does not match any known AI bot signatures.</p>';
        }
        
        html += '</div>';
        
        $('#ppc-detection-result').html(html);
    }

    /**
     * Enable Auto Scaling
     */
    function enableAutoScaling() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'paypercrawl_enable_autoscale',
                nonce: paypercrawl_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Auto-scaling enabled! Rates will now adjust based on demand.', 'success');
                    $('.ppc-enable-autoscale').text('Enabled').prop('disabled', true);
                }
            }
        });
    }

    /**
     * Show Notice
     */
    function showNotice(message, type) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap').prepend($notice);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Export Analytics Data
     */
    window.exportAnalytics = function(format) {
        const timeframe = $('#ppc-analytics-timeframe').val() || '30d';
        
        window.location.href = ajaxurl + '?action=paypercrawl_export_analytics&format=' + format + 
            '&timeframe=' + timeframe + '&nonce=' + paypercrawl_admin.nonce;
    };

    /**
     * Revenue Calculator
     */
    window.calculateRevenue = function() {
        const visits = parseInt($('#ppc-calc-visits').val()) || 0;
        const botPercentage = parseInt($('#ppc-calc-bot-percentage').val()) || 30;
        const avgRate = parseFloat($('#ppc-calc-avg-rate').val()) || 0.08;
        
        const botVisits = visits * (botPercentage / 100);
        const revenue = botVisits * avgRate;
        
        $('#ppc-calc-result').html(
            '<h4>Estimated Monthly Revenue: <strong>$' + revenue.toFixed(2) + '</strong></h4>' +
            '<p>Based on ' + Math.round(botVisits) + ' AI bot visits at $' + avgRate.toFixed(2) + ' per crawl</p>'
        );
    };

})(jQuery);
