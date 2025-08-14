jQuery(document).ready(function($) {
    
    var CrawlGuardAdmin = {
        
        init: function() {
            this.bindEvents();
            this.checkConnectionStatus();
            this.loadAnalytics();
            this.initCharts();
        },
        
        bindEvents: function() {
            $('#test-connection').on('click', this.testConnection);
            $('#generate-api-key').on('click', this.generateApiKey);
            $(document).on('click', '.refresh-analytics', this.loadAnalytics);
        },
        
        testConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $status = $('#connection-status');
            var $statusDot = $('#status-dot');
            var $statusText = $('#status-text');
            
            $button.prop('disabled', true).text('Testing...');
            $statusDot.removeClass('connected disconnected').addClass('checking');
            $statusText.text('Testing connection...');
            
            $.ajax({
                url: crawlguard_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_test_connection',
                    nonce: crawlguard_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $statusDot.removeClass('checking disconnected').addClass('connected');
                        $statusText.text('Connected');
                        CrawlGuardAdmin.showNotice('Connection successful!', 'success');
                    } else {
                        $statusDot.removeClass('checking connected').addClass('disconnected');
                        $statusText.text('Disconnected');
                        CrawlGuardAdmin.showNotice('Connection failed: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    $statusDot.removeClass('checking connected').addClass('disconnected');
                    $statusText.text('Connection Error');
                    CrawlGuardAdmin.showNotice('Connection test failed', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },
        
        generateApiKey: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            
            if (!confirm('Generate a new API key? This will replace your current key.')) {
                return;
            }
            
            $button.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: crawlguard_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_generate_api_key',
                    nonce: crawlguard_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('input[name="crawlguard_options[api_key]"]').val(response.data.api_key);
                        CrawlGuardAdmin.showNotice('API key generated successfully!', 'success');
                    } else {
                        CrawlGuardAdmin.showNotice('Failed to generate API key', 'error');
                    }
                },
                error: function() {
                    CrawlGuardAdmin.showNotice('Error generating API key', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Generate API Key');
                }
            });
        },
        
        checkConnectionStatus: function() {
            var $statusDot = $('#status-dot');
            var $statusText = $('#status-text');
            
            $statusDot.addClass('checking');
            $statusText.text('Checking connection...');
            
            $.ajax({
                url: crawlguard_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_test_connection',
                    nonce: crawlguard_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $statusDot.removeClass('checking disconnected').addClass('connected');
                        $statusText.text('Connected');
                    } else {
                        $statusDot.removeClass('checking connected').addClass('disconnected');
                        $statusText.text('Disconnected');
                    }
                },
                error: function() {
                    $statusDot.removeClass('checking connected').addClass('disconnected');
                    $statusText.text('Connection Error');
                }
            });
        },
        
        loadAnalytics: function() {
            $.ajax({
                url: crawlguard_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_get_analytics',
                    nonce: crawlguard_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CrawlGuardAdmin.updateAnalyticsDisplay(response.data);
                    }
                },
                error: function() {
                    console.log('Failed to load analytics');
                }
            });
        },
        
        updateAnalyticsDisplay: function(data) {
            $('#total-requests').text(data.total_requests || 0);
            $('#bot-requests').text(data.bot_requests || 0);
            $('#revenue-generated').text('$' + (data.revenue_generated || 0).toFixed(4));
            
            var accuracy = data.total_requests > 0 ? 
                Math.round((data.bot_requests / data.total_requests) * 100) : 95;
            $('#detection-accuracy').text(accuracy + '%');
            
            this.updateRecentDetections(data.recent_detections || []);
        },
        
        updateRecentDetections: function(detections) {
            var $container = $('#recent-detections');
            
            if (detections.length === 0) {
                $container.html('<p>No recent bot detections</p>');
                return;
            }
            
            var html = '';
            detections.forEach(function(detection) {
                var confidenceClass = detection.confidence >= 90 ? 'high' : 
                                    detection.confidence >= 70 ? 'medium' : 'low';
                
                html += '<div class="detection-item">';
                html += '<div class="detection-info">';
                html += '<div class="detection-bot">' + detection.bot_name + '</div>';
                html += '<div class="detection-time">' + detection.timestamp + '</div>';
                html += '</div>';
                html += '<div class="detection-confidence ' + confidenceClass + '">';
                html += detection.confidence + '%';
                html += '</div>';
                html += '</div>';
            });
            
            $container.html(html);
        },
        
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }
            
            this.initBotDetectionChart();
            this.initRevenueChart();
        },
        
        initBotDetectionChart: function() {
            var ctx = document.getElementById('bot-detection-chart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['6h ago', '5h ago', '4h ago', '3h ago', '2h ago', '1h ago', 'Now'],
                    datasets: [{
                        label: 'Bot Detections',
                        data: [12, 19, 8, 15, 22, 18, 25],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },
        
        initRevenueChart: function() {
            var ctx = document.getElementById('revenue-chart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['OpenAI', 'Anthropic', 'Google', 'Common Crawl', 'Others'],
                    datasets: [{
                        label: 'Revenue ($)',
                        data: [0.045, 0.032, 0.028, 0.015, 0.008],
                        backgroundColor: [
                            '#0073aa',
                            '#46b450',
                            '#ffb900',
                            '#dc3232',
                            '#666666'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toFixed(3);
                                }
                            }
                        }
                    }
                }
            });
        },
        
        showNotice: function(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    CrawlGuardAdmin.init();
});
