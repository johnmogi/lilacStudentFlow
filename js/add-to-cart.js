jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Lilac Add to Cart script loaded');
    
    // Debug function
    function debugLog() {
        if (!window.console || !window.console.log) return;
        
        var args = Array.prototype.slice.call(arguments);
        var timestamp = new Date().toISOString();
        
        // Add timestamp and prefix to all log messages
        args.unshift('[Lilac Debug ' + timestamp + ']');
        
        // Log to console
        console.log.apply(console, args);
    }
    
    // Function to redirect to checkout
    function redirectToCheckout() {
        debugLog('Redirecting to checkout');
        var checkoutUrl = lilac_vars.checkout_url;
        
        // Add a small random parameter to prevent caching
        var timestamp = new Date().getTime();
        var separator = checkoutUrl.includes('?') ? '&' : '?';
        window.location.href = checkoutUrl + separator + 'nocache=' + timestamp;
    }
    
    // Handle AJAX add to cart events
    $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
        debugLog('=== ADDED TO CART VIA AJAX ===');
        debugLog('Button:', $button ? $button.attr('class') : 'No button');
        
        // Redirect to checkout after a short delay
        setTimeout(redirectToCheckout, 500);
    });
    
    // Handle form submission for simple products
    $(document).on('submit', 'form.cart:not(.grouped_form)', function(e) {
        debugLog('=== FORM SUBMIT TRIGGERED ===');
        var $form = $(this);
        var $button = $form.find('.single_add_to_cart_button');
        
        // Always prevent default for our custom handling
        e.preventDefault();
        e.stopImmediatePropagation();
        
        // Disable the button to prevent multiple clicks
        $button.prop('disabled', true).addClass('loading');
        
        debugLog('Form data:', $form.serialize());
        
        // Submit via AJAX
        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: $form.serialize() + '&action=woocommerce_add_to_cart',
            dataType: 'json',
            success: function(response) {
                debugLog('AJAX add to cart success:', response);
                if (response.error && response.product_url) {
                    window.location = response.product_url;
                    return;
                }
                // Redirect to checkout after successful add to cart
                redirectToCheckout();
            },
            error: function(xhr, status, error) {
                var errorMsg = 'שגיאה בהוספת המוצר לעגלה. אנא נסה שוב.';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.error_message) {
                        errorMsg = response.error_message;
                    }
                    debugLog('AJAX add to cart error:', response);
                } catch (e) {
                    debugLog('Error parsing error response:', e);
                }
                alert(errorMsg);
                $button.prop('disabled', false).removeClass('loading');
            }
        });
    });
    
    // Handle direct add to cart buttons (like on shop page)
    $(document).on('click', '.add_to_cart_button:not(.product_type_variable):not(.product_type_grouped)', function(e) {
        var $button = $(this);
        
        // Only handle if not already processing
        if ($button.is('.loading, .added')) {
            return true;
        }
        
        debugLog('=== DIRECT ADD TO CART CLICKED ===');
        debugLog('Button data:', $button.data());
        
        // Let WooCommerce handle the click, our added_to_cart handler will take care of the rest
    });
});
