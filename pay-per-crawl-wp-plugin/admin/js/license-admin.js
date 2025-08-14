/**
 * License Admin JavaScript
 * Handles license activation, deactivation, and validation
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Activate license
        $('#activate-license').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const licenseKey = $('#license_key').val().trim();
            
            if (!licenseKey) {
                showMessage('Please enter a license key', 'error');
                return;
            }
            
            // Validate license key format
            if (!isValidLicenseFormat(licenseKey)) {
                showMessage('Invalid license key format. Please use: XXXX-XXXX-XXXX-XXXX', 'error');
                return;
            }
            
            button.prop('disabled', true).text(paypercrawl_license.strings.activating);
            
            $.ajax({
                url: paypercrawl_license.ajax_url,
                type: 'POST',
                data: {
                    action: 'paypercrawl_activate_license',
                    license_key: licenseKey,
                    nonce: paypercrawl_license.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage(response.data.error || paypercrawl_license.strings.error, 'error');
                        button.prop('disabled', false).text('Activate License');
                    }
                },
                error: function() {
                    showMessage(paypercrawl_license.strings.error, 'error');
                    button.prop('disabled', false).text('Activate License');
                }
            });
        });
        
        // Deactivate license
        $('#deactivate-license').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm(paypercrawl_license.strings.confirm_deactivate)) {
                return;
            }
            
            const button = $(this);
            button.prop('disabled', true).text(paypercrawl_license.strings.deactivating);
            
            $.ajax({
                url: paypercrawl_license.ajax_url,
                type: 'POST',
                data: {
                    action: 'paypercrawl_deactivate_license',
                    nonce: paypercrawl_license.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage(response.data.error || paypercrawl_license.strings.error, 'error');
                        button.prop('disabled', false).text('Deactivate License');
                    }
                },
                error: function() {
                    showMessage(paypercrawl_license.strings.error, 'error');
                    button.prop('disabled', false).text('Deactivate License');
                }
            });
        });
        
        // Validate license
        $('#validate-license').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            button.prop('disabled', true).text(paypercrawl_license.strings.validating);
            
            $.ajax({
                url: paypercrawl_license.ajax_url,
                type: 'POST',
                data: {
                    action: 'paypercrawl_validate_license',
                    nonce: paypercrawl_license.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage(response.data.message || paypercrawl_license.strings.error, 'error');
                        button.prop('disabled', false).text('Revalidate License');
                    }
                },
                error: function() {
                    showMessage(paypercrawl_license.strings.error, 'error');
                    button.prop('disabled', false).text('Revalidate License');
                }
            });
        });
        
        // Auto-format license key input
        $('#license_key').on('input', function() {
            let value = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
            
            if (value.length > 16) {
                value = value.substring(0, 16);
            }
            
            // Add dashes every 4 characters
            if (value.length > 0) {
                const chunks = value.match(/.{1,4}/g) || [];
                value = chunks.join('-');
            }
            
            $(this).val(value);
        });
        
        // Copy license key on click (if readonly)
        $('#license_key[readonly]').on('click', function() {
            $(this).select();
            document.execCommand('copy');
            showMessage('License key copied to clipboard', 'info');
        });
        
        /**
         * Validate license key format
         */
        function isValidLicenseFormat(key) {
            return /^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/i.test(key);
        }
        
        /**
         * Show message
         */
        function showMessage(message, type) {
            const messageDiv = $('#license-message');
            
            messageDiv.removeClass('notice-success notice-error notice-warning notice-info')
                     .addClass('notice notice-' + type)
                     .html('<p>' + message + '</p>')
                     .fadeIn();
            
            // Auto-hide after 5 seconds for non-error messages
            if (type !== 'error') {
                setTimeout(function() {
                    messageDiv.fadeOut();
                }, 5000);
            }
        }
        
        // Initialize tooltips
        $('.paypercrawl-tooltip').tooltip({
            position: {
                my: 'center bottom-10',
                at: 'center top',
                using: function(position, feedback) {
                    $(this).css(position);
                    $('<div>')
                        .addClass('arrow')
                        .addClass(feedback.vertical)
                        .addClass(feedback.horizontal)
                        .appendTo(this);
                }
            }
        });
        
        // Smooth scroll to sections
        $('.paypercrawl-nav-link').on('click', function(e) {
            e.preventDefault();
            const target = $($(this).attr('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });
        
        // Feature toggle animation
        $('.feature-toggle').on('change', function() {
            const feature = $(this).data('feature');
            const enabled = $(this).is(':checked');
            
            if (!enabled && $(this).hasClass('pro-only')) {
                $(this).prop('checked', false);
                showMessage('This feature requires a Pro or Business license', 'warning');
                return false;
            }
            
            // Save feature toggle state
            $.ajax({
                url: paypercrawl_license.ajax_url,
                type: 'POST',
                data: {
                    action: 'paypercrawl_toggle_feature',
                    feature: feature,
                    enabled: enabled,
                    nonce: paypercrawl_license.nonce
                }
            });
        });
        
        // License key visibility toggle
        $('.toggle-license-visibility').on('click', function(e) {
            e.preventDefault();
            const input = $('#license_key');
            const icon = $(this).find('.dashicons');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                input.attr('type', 'password');
                icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
        
        // Upgrade button tracking
        $('.upgrade-card .button-hero').on('click', function() {
            // Track upgrade click
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click', {
                    'event_category': 'License',
                    'event_label': 'Upgrade Button',
                    'value': $(this).closest('.upgrade-card').find('.price').text()
                });
            }
        });
        
        // Progress bar animation
        $('.meter-fill').each(function() {
            const width = $(this).css('width');
            $(this).css('width', 0).animate({
                width: width
            }, 1000);
        });
    });
    
})(jQuery);
