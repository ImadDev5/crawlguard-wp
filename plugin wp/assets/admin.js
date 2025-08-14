/**
 * Pay Per Crawl Admin JavaScript
 */

jQuery(document).ready(function($) {
    // Dashboard refresh functionality
    $('#ppc-refresh-activity').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        button.text('Refreshing...');
        
        $.ajax({
            url: paypercrawl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'paypercrawl_bot_activity',
                nonce: paypercrawl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#ppc-bot-activity').html(response.data.activity);
                }
                button.text('🔄 Refresh');
            },
            error: function() {
                button.text('🔄 Refresh');
            }
        });
    });

    // Auto-refresh activity every 30 seconds
    setInterval(function() {
        $('#ppc-refresh-activity').trigger('click');
    }, 30000);
});
