/**
 * CrawlGuard Checkout JavaScript
 * Handles Stripe Elements and payment processing
 */

(function($) {
    'use strict';

    // Initialize Stripe
    const stripe = Stripe(crawlguard_checkout.stripe_public_key);
    
    // Create card element
    let cardElement = null;
    let isProcessing = false;

    /**
     * Initialize checkout form
     */
    function initCheckoutForm() {
        const paymentForm = document.getElementById('payment-form');
        if (!paymentForm) return;

        // Create Stripe Elements
        const elements = stripe.elements({
            locale: crawlguard_checkout.locale || 'en'
        });

        // Create card element with custom styling
        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            },
            hidePostalCode: true // We collect postal code separately
        });

        // Mount card element
        cardElement.mount('#card-element');

        // Handle card element errors
        cardElement.on('change', function(event) {
            const errorElement = document.getElementById('card-errors');
            if (event.error) {
                errorElement.textContent = event.error.message;
                errorElement.style.display = 'block';
            } else {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
        });

        // Handle form submission
        paymentForm.addEventListener('submit', handleFormSubmit);
    }

    /**
     * Handle form submission
     */
    async function handleFormSubmit(event) {
        event.preventDefault();

        if (isProcessing) return;
        isProcessing = true;

        // Show loading state
        const submitButton = document.getElementById('submit-payment');
        const buttonText = submitButton.querySelector('.button-text');
        const spinner = submitButton.querySelector('.spinner');
        
        buttonText.style.display = 'none';
        spinner.style.display = 'inline-block';
        submitButton.disabled = true;

        try {
            // Create payment method
            const { error, paymentMethod } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    name: document.getElementById('name').value,
                    email: document.getElementById('email').value,
                    address: {
                        postal_code: document.getElementById('postal_code').value,
                        country: document.getElementById('country').value
                    }
                }
            });

            if (error) {
                throw new Error(error.message);
            }

            // Send payment method to server
            const response = await $.ajax({
                url: crawlguard_checkout.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_create_checkout_session',
                    nonce: $('#checkout_nonce').val(),
                    payment_method_id: paymentMethod.id,
                    tier: $('input[name="tier"]').val(),
                    email: $('#email').val(),
                    save_payment_method: $('input[name="save_payment_method"]').is(':checked')
                }
            });

            if (response.success) {
                // Handle subscription confirmation
                if (response.data.client_secret) {
                    // Confirm payment if needed
                    const result = await stripe.confirmCardPayment(response.data.client_secret);
                    
                    if (result.error) {
                        throw new Error(result.error.message);
                    }
                    
                    // Payment successful
                    showSuccessMessage('Subscription activated successfully!');
                    setTimeout(() => {
                        window.location.href = '/account/subscription-confirmed/';
                    }, 2000);
                } else if (response.data.subscription_id) {
                    // Subscription created successfully
                    showSuccessMessage('Subscription activated successfully!');
                    setTimeout(() => {
                        window.location.href = '/account/';
                    }, 2000);
                }
            } else {
                throw new Error(response.data.message || 'Payment failed');
            }

        } catch (error) {
            showErrorMessage(error.message);
            console.error('Payment error:', error);
        } finally {
            // Reset button state
            buttonText.style.display = 'inline-block';
            spinner.style.display = 'none';
            submitButton.disabled = false;
            isProcessing = false;
        }
    }

    /**
     * Initialize pricing table
     */
    function initPricingTable() {
        $('.select-plan-btn').on('click', function() {
            const tier = $(this).data('tier');
            const priceId = $(this).data('price-id');
            
            if (tier === 'enterprise') {
                // Redirect to contact form for enterprise
                window.location.href = '/contact-sales/';
            } else {
                // Redirect to checkout with selected tier
                window.location.href = '/checkout/?tier=' + tier;
            }
        });
    }

    /**
     * Handle payment method update
     */
    function initPaymentMethodUpdate() {
        $('#update-payment-method').on('click', function(e) {
            e.preventDefault();
            showPaymentMethodModal();
        });
    }

    /**
     * Show payment method modal
     */
    function showPaymentMethodModal() {
        const modal = $('#payment-method-modal');
        if (!modal.length) return;

        // Create new card element for update form
        const elements = stripe.elements();
        const updateCardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                }
            }
        });

        updateCardElement.mount('#card-element-update');
        modal.show();

        // Handle update form submission
        $('#update-payment-form').on('submit', async function(e) {
            e.preventDefault();

            const { error, paymentMethod } = await stripe.createPaymentMethod({
                type: 'card',
                card: updateCardElement
            });

            if (error) {
                $('#card-errors-update').text(error.message);
                return;
            }

            // Send to server
            $.ajax({
                url: crawlguard_checkout.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_update_payment_method',
                    nonce: crawlguard_checkout.nonce,
                    payment_method_id: paymentMethod.id
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessMessage('Payment method updated successfully!');
                        modal.hide();
                        location.reload();
                    } else {
                        $('#card-errors-update').text(response.data.message);
                    }
                },
                error: function() {
                    $('#card-errors-update').text('An error occurred. Please try again.');
                }
            });
        });
    }

    /**
     * Handle subscription cancellation
     */
    function initSubscriptionCancellation() {
        $('#cancel-subscription').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to cancel your subscription? You will lose access at the end of your billing period.')) {
                return;
            }

            $.ajax({
                url: crawlguard_checkout.ajax_url,
                type: 'POST',
                data: {
                    action: 'crawlguard_cancel_subscription',
                    nonce: crawlguard_checkout.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessMessage('Subscription cancelled. You will have access until the end of your billing period.');
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    } else {
                        showErrorMessage(response.data.message);
                    }
                },
                error: function() {
                    showErrorMessage('An error occurred. Please try again.');
                }
            });
        });
    }

    /**
     * Handle plan changes
     */
    function initPlanChange() {
        $('#change-plan').on('click', function(e) {
            e.preventDefault();
            window.location.href = '/pricing/';
        });
    }

    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        const alertDiv = $('<div class="alert alert-success"></div>').text(message);
        $('.crawlguard-checkout-form').prepend(alertDiv);
        
        setTimeout(() => {
            alertDiv.fadeOut(() => alertDiv.remove());
        }, 5000);
    }

    /**
     * Show error message
     */
    function showErrorMessage(message) {
        const alertDiv = $('<div class="alert alert-error"></div>').text(message);
        $('.crawlguard-checkout-form').prepend(alertDiv);
        
        // Auto-scroll to error
        $('html, body').animate({
            scrollTop: alertDiv.offset().top - 100
        }, 500);
        
        setTimeout(() => {
            alertDiv.fadeOut(() => alertDiv.remove());
        }, 10000);
    }

    /**
     * Close modal
     */
    window.closeModal = function() {
        $('.modal').hide();
    };

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: crawlguard_checkout.currency || 'USD'
        }).format(amount);
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initCheckoutForm();
        initPricingTable();
        initPaymentMethodUpdate();
        initSubscriptionCancellation();
        initPlanChange();

        // Handle country selection change
        $('#country').on('change', function() {
            const country = $(this).val();
            const postalCodeField = $('#postal_code');
            const postalLabel = $('label[for="postal_code"]');
            
            if (country === 'US') {
                postalLabel.text('ZIP Code');
                postalCodeField.attr('placeholder', '12345');
            } else if (country === 'CA') {
                postalLabel.text('Postal Code');
                postalCodeField.attr('placeholder', 'A1A 1A1');
            } else {
                postalLabel.text('Postal Code');
                postalCodeField.attr('placeholder', '');
            }
        });

        // Auto-format card number display
        if (cardElement) {
            cardElement.on('change', function(event) {
                if (event.complete) {
                    // Card number is complete and valid
                    $('#card-element').addClass('complete');
                } else {
                    $('#card-element').removeClass('complete');
                }
            });
        }

        // Handle terms checkbox
        $('input[name="agree_terms"]').on('change', function() {
            const submitButton = $('#submit-payment');
            if ($(this).is(':checked')) {
                submitButton.prop('disabled', false);
            } else {
                submitButton.prop('disabled', true);
            }
        });
    });

})(jQuery);
