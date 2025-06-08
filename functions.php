<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

// Enhanced debug logging function
function custom_log($message, $data = null) {
    // Use wp-content/debug-lilac.log for better accessibility
    $log_file = WP_CONTENT_DIR . '/debug-lilac.log';
    $timestamp = current_time('mysql');
    $log_message = "[$timestamp] $message" . PHP_EOL;
    
    // Add request URI and method
    $log_message .= "[URL] " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A') . PHP_EOL;
    $log_message .= "[METHOD] " . (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'N/A') . PHP_EOL;
    
    // Add POST data if this is a POST request
    if (!empty($_POST)) {
        $log_message .= "[POST DATA] " . print_r($_POST, true) . PHP_EOL;
    }
    
    // Add GET data if this is a GET request
    if (!empty($_GET)) {
        $log_message .= "[GET DATA] " . print_r($_GET, true) . PHP_EOL;
    }
    
    // Add any additional data
    if ($data !== null) {
        $log_message .= "[DATA] " . (is_array($data) || is_object($data) ? print_r($data, true) : $data) . PHP_EOL;
    }
    
    // Add a separator
    $log_message .= str_repeat('-', 80) . PHP_EOL;
    
    // Ensure the log directory exists and is writable
    if (!file_exists(dirname($log_file))) {
        @mkdir(dirname($log_file), 0755, true);
    }
    
    // Write to the log file
    @file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Also log to PHP error log for visibility
    error_log('LILAC DEBUG: ' . strip_tags($message));
}

// Add debug test endpoint
add_action('init', function() {
    if (isset($_GET['test_debug'])) {
        custom_log('Debug test', 'This is a test message');
        echo 'Debug test completed. Check debug-lilac.log';
        exit;
    }
});

// AJAX handler for client-side debug logging
add_action('wp_ajax_lilac_debug_log', 'lilac_handle_debug_log');
add_action('wp_ajax_nopriv_lilac_debug_log', 'lilac_handle_debug_log');
function lilac_handle_debug_log() {
    if (!empty($_POST['message'])) {
        $log_file = WP_CONTENT_DIR . '/debug-lilac.log';
        $message = '[' . current_time('mysql') . '] ' . sanitize_text_field($_POST['message']) . "\n";
        error_log($message, 3, $log_file);
    }
    wp_die();
}

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
if (!defined('LILAC_QUIZ_FOLLOWUP_VERSION')) {
    define('LILAC_QUIZ_FOLLOWUP_VERSION', '1.0.0');
}

/**
 * Change Add to Cart button text for WooCommerce
 */
add_filter('woocommerce_product_single_add_to_cart_text', 'ccr_custom_add_to_cart_text');
add_filter('woocommerce_product_add_to_cart_text', 'ccr_custom_add_to_cart_text');
function ccr_custom_add_to_cart_text() {
    return 'רכשו עכשיו';
}

/**
 * Debug function to log to wp-content/debug.log
 */
if (!function_exists('write_log')) {
    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

/**
 * Log WooCommerce add to cart actions
 */
add_action('woocommerce_add_to_cart', 'log_add_to_cart_action', 10, 6);
function log_add_to_cart_action($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    write_log('=== ADD TO CART ACTION TRIGGERED ===');
    write_log('Product ID: ' . $product_id);
    write_log('Variation ID: ' . $variation_id);
    write_log('Quantity: ' . $quantity);
    write_log('Cart Item Data: ' . print_r($cart_item_data, true));
    write_log('$_REQUEST: ' . print_r($_REQUEST, true));
    write_log('$_POST: ' . print_r($_POST, true));
}

/**
 * Handle all add to cart redirects to checkout
 */
add_filter('woocommerce_add_to_cart_redirect', 'custom_add_to_cart_redirect', 99, 1);
function custom_add_to_cart_redirect($url) {
    // Log the start of the redirection process
    custom_log('=== START ADD TO CART REDIRECT ===');
    custom_log('Original URL', $url);
    
    // Log request data
    custom_log('Request Data', [
        'is_ajax' => wp_doing_ajax(),
        'is_cart' => is_cart(),
        'is_checkout' => is_checkout(),
        'is_product' => is_product(),
        'request' => $_REQUEST
    ]);
    
    // Don't redirect if this is an AJAX request - let the JS handle it
    if (wp_doing_ajax()) {
        custom_log('AJAX request detected, letting JS handle redirection');
        return $url;
    }
    
    // Check if this is an add to cart action
    $is_add_to_cart = (
        (isset($_REQUEST['add-to-cart']) && is_numeric($_REQUEST['add-to-cart'])) ||
        (isset($_REQUEST['add-to-cart-nonce']) && wp_verify_nonce($_REQUEST['add-to-cart-nonce'], 'add-to-cart')) ||
        (isset($_REQUEST['add-to-cart-variation']) && is_numeric($_REQUEST['add-to-cart-variation']))
    );
    
    if ($is_add_to_cart) {
        custom_log('Add to cart action detected');
        
        // Get the product ID
        $product_id = 0;
        $variation_id = 0;
        
        if (isset($_REQUEST['add-to-cart']) && is_numeric($_REQUEST['add-to-cart'])) {
            $product_id = absint($_REQUEST['add-to-cart']);
            custom_log('Simple product detected', ['product_id' => $product_id]);
        } 
        
        if (isset($_REQUEST['add-to-cart-variation']) && is_numeric($_REQUEST['add-to-cart-variation'])) {
            $variation_id = absint($_REQUEST['add-to-cart-variation']);
            $product_id = $variation_id; // For variations, use variation ID as product ID
            custom_log('Variable product detected', [
                'variation_id' => $variation_id,
                'variation' => isset($_REQUEST['variation_id']) ? $_REQUEST['variation_id'] : 'N/A'
            ]);
        }
        
        if ($product_id > 0) {
            // Clear any notices to prevent duplicate messages
            wc_clear_notices();
            
            // Get the checkout URL
            $checkout_url = wc_get_checkout_url();
            $redirect_url = add_query_arg('added-to-cart', $product_id, $checkout_url);
            
            custom_log('Redirecting to checkout', [
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'redirect_url' => $redirect_url
            ]);
            
            return $redirect_url;
        }
    }
    
    custom_log('No redirect needed, returning original URL');
    return $url;
}

/**
 * Handle AJAX add to cart redirects and variable product forms
 */
add_action('wp_footer', 'custom_add_to_cart_script');
function custom_add_to_cart_script() {
    // Only load on relevant pages
    if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_product()) return;
    
    $checkout_url = wc_get_checkout_url();
    $is_ajax = wp_doing_ajax();
    ?>
    <script type="text/javascript">
    (function($) {
        'use strict';
        
        // Enhanced debug function
        function debugLog() {
            if (!window.console || !window.console.log) return;
            
            var args = Array.prototype.slice.call(arguments);
            var timestamp = new Date().toISOString();
            
            // Add timestamp and prefix to all log messages
            args.unshift('[Lilac Debug ' + timestamp + ']');
            
            // Log to console
            console.log.apply(console, args);
            
            // Also log to a global array for debugging
            if (!window.lilacDebugLog) {
                window.lilacDebugLog = [];
            }
            window.lilacDebugLog.push({
                time: timestamp,
                message: args.join(' ')
            });
            
            // Send log to server for persistent logging
            try {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'lilac_debug_log',
                        message: args.join(' ')
                    }
                });
            } catch (e) {
                console.error('Failed to send debug log:', e);
            }
        }
        
        // Function to redirect to checkout
        function redirectToCheckout() {
            var checkoutUrl = '<?php echo esc_js($checkout_url); ?>';
            debugLog('Redirecting to checkout:', checkoutUrl);
            
            // Add a small random parameter to prevent caching
            var timestamp = new Date().getTime();
            var separator = checkoutUrl.includes('?') ? '&' : '?';
            window.location.href = checkoutUrl + separator + 'nocache=' + timestamp;
            
            // If we're still here after 1 second, force redirect
            setTimeout(function() {
                debugLog('Force redirecting to checkout after delay');
                window.location.href = checkoutUrl;
            }, 1000);
            
            return false;
        }
        
        // Handle AJAX add to cart
        function handleAddedToCart(event, fragments, hash, $button) {
            debugLog('=== ADDED TO CART EVENT ===');
            debugLog('Event:', event);
            debugLog('Fragments:', fragments);
            debugLog('Hash:', hash);
            debugLog('Button:', $button ? $button.attr('class') : 'No button');
            
            // Get the product ID from the button if available
            var productId = $button ? $button.data('product_id') || $button.closest('[data-product_id]').data('product_id') : 'unknown';
            debugLog('Product ID from button:', productId);
            
            // Check if this is a variation product
            var isVariation = $button && $button.closest('.variations_form').length > 0;
            var delay = isVariation ? 1500 : 800; // Longer delay for variations
            
            debugLog('Is variation:', isVariation, 'Using delay:', delay + 'ms');
            
            // Clear any existing timeouts
            if (window.addToCartTimeout) {
                clearTimeout(window.addToCartTimeout);
            }
            
            // Set a new timeout
            window.addToCartTimeout = setTimeout(function() {
                debugLog('Executing redirect after delay');
                redirectToCheckout();
            }, delay);
        }
        
        // Document ready
        $(function() {
            debugLog('Document ready');
            
            // Handle AJAX add to cart events
            $(document.body).on('added_to_cart', handleAddedToCart);
            
            // Handle variable product form submission
            $('form.variations_form').on('submit', function(e) {
                debugLog('Variable product form submission');
                var $form = $(this);
                var $button = $form.find('.single_add_to_cart_button');
                
                // Update button text and disable
                $button.text('מועבר לתשלום...').prop('disabled', true);
                
                // For AJAX add to cart
                if ($form.hasClass('variations_form')) {
                    debugLog('Variable product form detected');
                    return true; // Let the form submit normally
                }
                
                return true;
            });
            
            // Handle variation selection
            $('form.variations_form').on('found_variation', function(event, variation) {
                debugLog('Variation selected: ' + JSON.stringify(variation));
                $(this).find('.single_add_to_cart_button').text('רכשו עכשיו');
            });
            
            // Handle direct add to cart buttons (simple products)
            $(document).on('click', '.add_to_cart_button:not(.product_type_variable)', function(e) {
                debugLog('Add to cart button clicked');
                var $button = $(this);
                
                // Only proceed if not already processing
                if ($button.is('.processing, .disabled, :disabled, [disabled=disabled]')) {
                    return false;
                }
                
                // Mark as processing
                $button.addClass('processing').text('מועבר לתשלום...');
                
                // If this is an AJAX add to cart
                if (typeof wc_add_to_cart_params !== 'undefined' && $button.is('.ajax_add_to_cart')) {
                    return true; // Let WooCommerce handle the AJAX request
                }
                
                return true;
            });
            
            // Redirect if on cart page with items
            if ($('body').hasClass('woocommerce-cart') && $('.woocommerce-cart-form__contents').length) {
                debugLog('On cart page, redirecting to checkout');
                setTimeout(redirectToCheckout, 500);
            }
            
            // Debug AJAX requests
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.url && settings.url.includes('wc-ajax=add_to_cart')) {
                    debugLog('AJAX add to cart completed');
                    debugLog('URL: ' + settings.url);
                    debugLog('Status: ' + xhr.status);
                    
                    try {
                        var response = JSON.parse(xhr.responseText);
                        debugLog('Response: ' + JSON.stringify(response));
                    } catch (e) {
                        debugLog('Could not parse response as JSON');
                    }
                }
            });
            
            // Enhanced form submission handler for all add to cart forms
            $(document).on('submit', 'form.cart:not(.grouped_form)', function(e) {
                debugLog('=== FORM SUBMIT TRIGGERED ===');
                var $form = $(this);
                var $button = $form.find('.single_add_to_cart_button');
                var isAjax = $form.attr('enctype') === 'multipart/form-data' || 
                             $form.hasClass('variations_form') || 
                             $form.find('input[name="add-to-cart"]').length > 0;
                
                debugLog('Form data:', $form.serialize());
                debugLog('Is AJAX submission:', isAjax);
                
                // Always prevent default for our custom handling
                e.preventDefault();
                e.stopImmediatePropagation();
                
                // Disable the button to prevent multiple clicks
                $button.prop('disabled', true).addClass('loading');
                
                if (isAjax) {
                    debugLog('Processing as AJAX submission');
                    
                    // For variable products, we need to wait for variation data to be set
                    if ($form.hasClass('variations_form')) {
                        debugLog('Variable product form detected');
                        // Trigger variation selection if not already done
                        if (typeof $form.data('product_variations') === 'undefined') {
                            $form.find('.variations select').trigger('change');
                        }
                    }
                    
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
                } else {
                    debugLog('Processing as standard form submission');
                    // For non-AJAX forms, submit normally
                    this.submit();
                }
            });
        });
        
    })(jQuery);
    </script>
    <?php
}

/**
 * Debug function to log to wp-content/debug.log
 */
if (!function_exists('write_log')) {
    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

// Remove any other conflicting redirects
remove_action('template_redirect', 'wc_redirect_to_checkout');
remove_action('template_redirect', 'wc_cart_redirect_after_error');

// Ensure WooCommerce session is started for all users
add_action('wp_loaded', function() {
    if (!is_admin() && !defined('DOING_CRON') && !defined('DOING_AJAX') && function_exists('WC')) {
        // Initialize session if not already started
        if (is_null(WC()->session)) {
            WC()->initialize_session();
        }
        // Ensure customer session cookie is set
        if (WC()->session && !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
    }
});

// Ensure cart is initialized for all users
add_action('wp_loaded', function() {
    if (function_exists('WC') && !is_admin() && !defined('DOING_CRON') && !defined('DOING_AJAX')) {
        if (is_null(WC()->cart)) {
            WC()->initialize_cart();
        }
    }
}, 5);

// Handle AJAX logging
add_action('wp_ajax_log_to_console', 'handle_console_log');
add_action('wp_ajax_nopriv_log_to_console', 'handle_console_log');
function handle_console_log() {
    if (isset($_POST['message'])) {
        write_log('JS: ' . sanitize_text_field($_POST['message']));
    }
    wp_die();
}

/**
 * Prevent cart empty redirect
 */
add_action('template_redirect', function() {
    if (is_cart() && WC()->cart->is_empty() && !is_ajax()) {
        $referer = wp_get_referer();
        if ($referer) {
            wp_safe_redirect($referer);
            exit;
        }
    }
});

/**
 * Customize checkout fields for school registration
 */
add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');
function custom_override_checkout_fields($fields) {
    // Remove order comments and unnecessary fields
    unset($fields['order']['order_comments']);
    
    // Student Information Section
    $fields['billing']['billing_first_name'] = array(
        'label'       => 'שם פרטי',
        'placeholder' => 'שם פרטי',
        'required'    => true,
        'class'       => array('form-row-first'),
        'priority'    => 10
    );
    
    $fields['billing']['billing_last_name'] = array(
        'label'       => 'שם משפחה',
        'placeholder' => 'שם משפחה',
        'required'    => true,
        'class'       => array('form-row-last'),
        'priority'    => 20
    );
    
    // School Code Section
    $fields['billing']['school_code'] = array(
        'type'        => 'text',
        'label'       => 'קוד בית ספר',
        'placeholder' => 'הזן קוד בית ספר',
        'required'    => true,
        'class'       => array('form-row-first'),
        'priority'    => 30,
        'clear'       => true
    );
    
    // School Info Section (will be populated via AJAX)
    $fields['billing']['school_info'] = array(
        'type'        => 'hidden',
        'class'       => array('school-info-container'),
        'priority'    => 35
    );
    
    // Class Number
    $fields['billing']['class_number'] = array(
        'type'        => 'text',
        'label'       => 'מספר כיתה',
        'placeholder' => 'מספר הכיתה שלך',
        'required'    => true,
        'class'       => array('form-row-last'),
        'priority'    => 40
    );
    
    // Phone and ID Section
    $fields['billing']['billing_phone'] = array(
        'label'       => 'טלפון נייד (זיהוי משתמש)',
        'placeholder' => 'הזן מספר טלפון נייד',
        'required'    => true,
        'class'       => array('form-row-first'),
        'priority'    => 50,
        'clear'       => true
    );
    
    $fields['billing']['phone_confirm'] = array(
        'type'        => 'text',
        'label'       => 'וידוא טלפון נייד',
        'placeholder' => 'הזן שוב את המספר',
        'required'    => true,
        'class'       => array('form-row-last'),
        'priority'    => 60
    );
    
    $fields['billing']['id_number'] = array(
        'type'        => 'text',
        'label'       => 'מספר ת.ז (סיסמה)',
        'placeholder' => 'תעודת זהות',
        'required'    => true,
        'class'       => array('form-row-first'),
        'priority'    => 70
    );
    
    $fields['billing']['id_confirm'] = array(
        'type'        => 'text',
        'label'       => 'וידוא תעודת זהות',
        'placeholder' => 'הזן שוב ת.ז',
        'required'    => true,
        'class'       => array('form-row-last'),
        'priority'    => 80
    );
    
    // Email field
    $fields['billing']['billing_email'] = array(
        'label'       => 'אימייל לאישור',
        'placeholder' => 'אימייל לאישור הרשמה',
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 90,
        'clear'       => true
    );
    
    // Promo code link
    $fields['billing']['promo_code'] = array(
        'type'        => 'checkbox',
        'label'       => 'יש ברשותי קוד הטבה',
        'class'       => array('form-row-wide'),
        'priority'    => 100
    );
    
    // Remove all address fields for virtual products
    if (WC()->cart && !empty(WC()->cart->get_cart())) {
        $is_virtual = true;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!$cart_item['data']->is_virtual()) {
                $is_virtual = false;
                break;
            }
        }
        
        if ($is_virtual) {
            unset($fields['billing']['billing_company']);
            unset($fields['billing']['billing_country']);
            unset($fields['billing']['billing_address_1']);
            unset($fields['billing']['billing_address_2']);
            unset($fields['billing']['billing_city']);
            unset($fields['billing']['billing_state']);
            unset($fields['billing']['billing_postcode']);
        }
    }
    
    return $fields;
}

/**
 * Set default country to Israel and hide country field
 */
add_filter('default_checkout_billing_country', function() {
    return 'IL'; // ISO code for Israel
});

/**
 * Remove address fields from checkout
 */
add_filter('woocommerce_checkout_fields', function($fields) {
    // Remove shipping fields
    unset($fields['shipping']);
    
    return $fields;
});

/**
 * Remove duplicate product error message
 */
add_filter('woocommerce_add_error', function($error) {
    if (strpos($error, 'You cannot add another') !== false) {
        // Return an empty string to prevent the error from showing
        return '';
    }
    return $error;
});

/**
 * Clear any error notices on the cart page
 */
add_action('template_redirect', function() {
    if (is_cart()) {
        wc_clear_notices();
        
        // If cart is not empty, redirect to checkout
        if (!WC()->cart->is_empty()) {
            wp_redirect(wc_get_checkout_url());
            exit;
        }
    }
}, 99);

/**
 * Enqueue scripts and styles
 */
function hello_elementor_child_scripts_styles() {
    // Enqueue parent theme styles
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('hello-elementor-theme-style'),
        wp_get_theme()->get('Version')
    );

    // Enqueue WooCommerce scripts and styles if WooCommerce is active
    if (class_exists('WooCommerce')) {
        // Enqueue WooCommerce scripts
        if (is_product() || is_cart() || is_checkout()) {
            wp_enqueue_script('wc-add-to-cart');
            wp_enqueue_script('wc-cart-fragments');
            
            // Localize the script with the AJAX URL and other parameters
            wp_localize_script('wc-add-to-cart', 'wc_add_to_cart_params', array(
                'ajax_url' => WC()->ajax_url(),
                'wc_ajax_url' => WC_AJAX::get_endpoint("%%endpoint%%"),
                'i18n_view_cart' => __('View cart', 'woocommerce'),
                'cart_url' => wc_get_cart_url(),
                'is_cart' => is_cart(),
                'cart_redirect_after_add' => get_option('woocommerce_cart_redirect_after_add')
            ));
        }
    }
    
    // Enqueue custom scripts only if they exist
    if (is_product() || is_shop() || is_product_category()) {
        // Enqueue debug script
        wp_enqueue_script(
            'lilac-debug',
            get_stylesheet_directory_uri() . '/js/debug-test.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/js/debug-test.js'),
            true
        );
        
        // Enqueue add to cart script
        wp_enqueue_script(
            'lilac-add-to-cart',
            get_stylesheet_directory_uri() . '/js/add-to-cart.js',
            array('jquery', 'wc-add-to-cart', 'wc-cart-fragments'),
            filemtime(get_stylesheet_directory() . '/js/add-to-cart.js'),
            true
        );
        
        // Localize script with necessary data
        $lilac_vars = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'checkout_url' => wc_get_checkout_url(),
            'is_product' => is_product() ? 'yes' : 'no',
            'is_shop' => is_shop() ? 'yes' : 'no',
            'is_product_category' => is_product_category() ? 'yes' : 'no',
            'home_url' => home_url('/'),
            'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
            'debug' => WP_DEBUG ? 'yes' : 'no',
            'user_logged_in' => is_user_logged_in() ? 'yes' : 'no',
            'wc_cart_url' => wc_get_cart_url(),
            'nonce' => wp_create_nonce('woocommerce-cart')
        );
        
        wp_localize_script('lilac-add-to-cart', 'lilac_vars', $lilac_vars);
        
        // Log the debug info to PHP error log
        if (WP_DEBUG) {
            error_log('Lilac Debug - Enqueued Scripts: ' . print_r($lilac_vars, true));
        }
        
        // Also make sure WooCommerce scripts have the right data
        wp_localize_script('wc-add-to-cart', 'wc_add_to_cart_params', array(
            'ajax_url' => WC()->ajax_url(),
            'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
            'i18n_view_cart' => __('View cart', 'woocommerce'),
            'cart_url' => wc_get_cart_url(),
            'is_cart' => is_cart() ? '1' : '0',
            'cart_redirect_after_add' => 'no'
        ));
    }
    if (file_exists(get_stylesheet_directory() . '/assets/js/custom.js')) {
        wp_enqueue_script(
            'hello-elementor-child-script',
            get_stylesheet_directory_uri() . '/assets/js/custom.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/assets/js/custom.js'),
            true
        );
    }
    
    // Enqueue quiz answer handler on quiz pages
    if (is_singular('sfwd-quiz')) {
        wp_enqueue_script(
            'quiz-answer-handler',
            get_stylesheet_directory_uri() . '/assets/js/quiz-answer-handler.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/assets/js/quiz-answer-handler.js'),
            true
        );
        
        // Ensure jQuery is loaded
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }
    }

    // Enqueue child theme scripts
    wp_enqueue_script(
        'hello-elementor-child-script',
        get_stylesheet_directory_uri() . '/js/scripts.js',
        array('jquery'),
        wp_get_theme()->get('Version'),
        true
    );

    // Localize script with AJAX URL
    wp_localize_script(
        'hello-elementor-child-script',
        'ajax_object',
        array('ajax_url' => admin_url('admin-ajax.php'))
    );

    // Enqueue Font Awesome
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css',
        array(),
        '6.0.0-beta3'
    );

    // Enqueue custom styles if the file exists
    $custom_styles_path = get_stylesheet_directory() . '/css/custom-styles.css';
    if (file_exists($custom_styles_path)) {
        wp_enqueue_style(
            'custom-styles',
            get_stylesheet_directory_uri() . '/css/custom-styles.css',
            array(),
            filemtime($custom_styles_path)
        );
    }
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20);

/**
 * Enqueue progress bar styles
 */
function enqueue_progress_bar_styles() {
    // Only load on quiz pages
    if (is_singular('sfwd-quiz')) {
        wp_enqueue_style(
            'progress-bar-styles',
            get_stylesheet_directory_uri() . '/assets/css/progress-bar.css',
            array(),
            filemtime(get_stylesheet_directory() . '/assets/css/progress-bar.css')
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_progress_bar_styles', 99);

// Load other theme files
require_once get_stylesheet_directory() . '/inc/shortcodes/loader.php';

// Load Quiz Follow-up System
require_once get_stylesheet_directory() . '/includes/messaging/class-quiz-followup.php';

// Load Ultimate Member integration if UM is active
function ccr_load_um_integration() {
    if (class_exists('UM')) {
        require_once get_stylesheet_directory() . '/includes/integrations/class-ultimate-member-integration.php';
    }
}
add_action('after_setup_theme', 'ccr_load_um_integration', 5);

// Load Messaging System
function ccr_load_messaging_system() {
    require_once get_stylesheet_directory() . '/includes/messaging/notifications.php';
    
    if (is_admin()) {
        require_once get_stylesheet_directory() . '/includes/messaging/admin-functions.php';
    }
    
    // Enqueue toast system and alert integration scripts
    add_action('wp_enqueue_scripts', 'lilac_enqueue_toast_system');
    add_action('wp_footer', 'lilac_add_toast_debug_code');
}
add_action('after_setup_theme', 'ccr_load_messaging_system', 10);

/**
 * Enqueue Toast Notification System scripts
 */
function lilac_enqueue_toast_system() {
    // Force script versions to prevent caching during development
    $force_version = time();
    
    // Enqueue jQuery as a dependency
    wp_enqueue_script('jquery');
    
    // Enqueue Toast message system CSS FIRST
    wp_enqueue_style(
        'toast-system-css',
        get_stylesheet_directory_uri() . '/includes/messaging/css/toast-system.css',
        [],
        $force_version
    );
    
    // Enqueue Toast message system
    wp_enqueue_script(
        'toast-message-system',
        get_stylesheet_directory_uri() . '/includes/messaging/js/toast-system.js',
        ['jquery'],
        $force_version,
        true // Load in footer for better performance
    );
    
    // Enqueue Session Toast Extension
    wp_enqueue_script(
        'toast-session',
        get_stylesheet_directory_uri() . '/includes/messaging/js/session-toast.js',
        ['jquery', 'toast-message-system'],
        $force_version,
        true
    );
    
    // Enqueue Test Timer Extension
    wp_enqueue_script(
        'toast-test-timer',
        get_stylesheet_directory_uri() . '/includes/messaging/js/test-timer-toast.js',
        ['jquery', 'toast-message-system'],
        $force_version,
        true
    );
    
    // Enqueue Alert Helpers
    wp_enqueue_script(
        'alert-helpers',
        get_stylesheet_directory_uri() . '/includes/messaging/js/alert-helpers.js',
        ['jquery', 'toast-message-system'],
        $force_version,
        true
    );
    
    // Enqueue Toast Extensions CSS
    wp_enqueue_style(
        'toast-extensions-css',
        get_stylesheet_directory_uri() . '/includes/messaging/css/toast-extensions.css',
        ['toast-system-css'],
        $force_version
    );
    
    // Localize toast settings
    wp_localize_script('toast-message-system', 'toastSettings', [
        'defaultDuration' => 5000,
        'position' => 'top-right', // Make sure the position is set correctly
        'enableAlertIntegration' => true,
        'debugMode' => true
    ]);
    
    // Add a small fix to make sure the toast container uses the correct position
    wp_add_inline_script('toast-message-system', '
        jQuery(document).ready(function($) {
            // Force the container to use the correct position
            if ($("#lilac-toast-container").length) {
                $("#lilac-toast-container").attr("class", "top-right");
                console.log("Toast container position set to top-right");
            }
        });
    ');
}

/**
 * Add debug code to test toast functionality
 */
function lilac_add_toast_debug_code() {
    ?>
    <script type="text/javascript">
    /* Toast System Debug Code */
    console.log('Toast Debug Script Loaded');
    
    // Create global test function
    window.TestToast = function() {
        console.log('Testing Toast System...');
        
        if (typeof window.LilacToast !== 'undefined') {
            console.log('LilacToast API found!');
            window.LilacToast.success('Toast API is working!', 'Success');
            return 'Test successful';
        } else {
            console.log('LilacToast API not found');
            alert('This is a native alert - LilacToast not loaded');
            return 'Test failed';
        }
    };
    
    // Test alert integration
    window.TestAlert = function(message) {
        console.log('Testing Alert Integration...');
        alert(message || 'This is a test alert');
        return 'Alert test completed';
    };
    
    // Log toast system status on page load
    jQuery(document).ready(function($) {
        console.log('Toast System Status:', {
            'jQuery Loaded': typeof $ === 'function',
            'LilacToast Available': typeof window.LilacToast === 'function',
            'LilacShowToast Available': typeof window.LilacShowToast === 'function',
            'Alert Overridden': window.alert !== window.originalAlert
        });
    });
    </script>
    <?php
}

// Load Login System
function ccr_load_login_system() {
    if (!is_admin()) {
        error_log('Loading LoginManager...');
        
        $login_manager_path = get_stylesheet_directory() . '/src/Login/LoginManager.php';
        
        if (file_exists($login_manager_path)) {
            error_log('LoginManager.php found, including file...');
            require_once $login_manager_path;
            
            // Check if the class exists and can be loaded
            if (class_exists('Lilac\Login\LoginManager')) {
                error_log('LoginManager class exists, initializing...');
                // The init method will handle the initialization
                $instance = Lilac\Login\LoginManager::init();
                if ($instance) {
                    error_log('LoginManager initialized successfully');
                } else {
                    error_log('WARNING: LoginManager::init() returned null');
                }
            } else {
                error_log('ERROR: Lilac\Login\LoginManager class not found!');
            }
        } else {
            error_log('ERROR: LoginManager.php not found at: ' . $login_manager_path);
        }
        
        // Load other required files
        $captcha_path = get_stylesheet_directory() . '/src/Login/Captcha.php';
        if (file_exists($captcha_path)) {
            require_once $captcha_path;
        }
        
        $widget_path = get_stylesheet_directory() . '/src/Login/UserAccountWidget.php';
        if (file_exists($widget_path)) {
            require_once $widget_path;
        }
    } else {
        error_log('Skipping login system load in admin');
    }
}
add_action('after_setup_theme', 'ccr_load_login_system', 10);

// Debug function to check registered shortcodes
function debug_registered_shortcodes() {
    global $shortcode_tags;
    error_log('Registered Shortcodes: ' . print_r(array_keys($shortcode_tags), true));
}
add_action('init', 'debug_registered_shortcodes', 999);

// Add body class for quiz types
add_filter('body_class', function($classes) {
    if (is_singular('sfwd-quiz')) {
        $classes[] = 'quiz-page';
        // Add quiz ID as a body class
        global $post;
        if ($post) {
            $classes[] = 'quiz-' . $post->ID;
        }
        
        // Backward compatibility
        if ($enforce_hint === 'yes') {
            $classes[] = 'forced-hint-quiz';
        }
    }
    return $classes;
}, 5); // Lower priority to ensure it runs early

// Debug helper function
if (!function_exists('write_log')) {
    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}


function remove_css_js_version_query( $src ) {
    if ( strpos( $src, '?ver=' ) !== false ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'style_loader_src', 'remove_css_js_version_query', 9999 );
add_filter( 'script_loader_src', 'remove_css_js_version_query', 9999 );

// Load custom user functionality
require_once get_stylesheet_directory() . '/includes/users/custom-user-redirects.php';

// Load Registration Codes System
function load_registration_codes_system() {
    // Include the main registration codes class
    $registration_codes_file = get_stylesheet_directory() . '/includes/registration/class-registration-codes.php';
    
    if (file_exists($registration_codes_file)) {
        require_once $registration_codes_file;
        
        // Initialize the registration codes system
        if (class_exists('Registration_Codes')) {
            Registration_Codes::get_instance();
            
            // Add admin notice if current user is admin
            if (current_user_can('manage_options')) {
                add_action('admin_notices', function() {
                   // echo '<div class="notice notice-success"><p>Registration Codes system is active. <a href="' . admin_url('admin.php?page=registration-codes') . '">Manage Registration Codes</a></p></div>';
                });
            }
        }
    }
}
add_action('after_setup_theme', 'load_registration_codes_system', 15);

// Load WooCommerce Customizations
if (class_exists('WooCommerce')) {
    require_once get_stylesheet_directory() . '/includes/woocommerce/class-woocommerce-customizations.php';
}



// ============================================
// WooCommerce Checkout Redirect Functionality
// ============================================

// 1. Change Add to Cart button text
add_filter('woocommerce_product_single_add_to_cart_text', 'custom_add_to_cart_text');
add_filter('woocommerce_product_add_to_cart_text', 'custom_add_to_cart_text');
function custom_add_to_cart_text() {
    return 'רכשו עכשיו';
}

// 2. Clear cart before adding new product and handle add to cart
add_filter('woocommerce_add_to_cart_validation', 'clear_cart_before_adding', 10, 3);
function clear_cart_before_adding($passed, $product_id, $quantity) {
    // Debug log
    error_log('Adding to cart - Product ID: ' . $product_id . ', Quantity: ' . $quantity);
    
    // Clear the cart before adding new item
    if (!WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
        error_log('Cart was not empty - Cleared cart before adding new item');
    }
    
    return $passed;
}

// 3. Force redirect to checkout on any add-to-cart action
add_action('template_redirect', 'force_checkout_redirect');
function force_checkout_redirect() {
    // Debug log
    error_log('Force redirect check - is_cart: ' . (is_cart() ? 'yes' : 'no') . ', add-to-cart: ' . (isset($_REQUEST['add-to-cart']) ? $_REQUEST['add-to-cart'] : 'not set'));
    
    if (is_cart() || (isset($_REQUEST['add-to-cart']) && is_numeric($_REQUEST['add-to-cart']))) {
        if (!WC()->cart->is_empty()) {
            error_log('Redirecting to checkout');
            wp_redirect(wc_get_checkout_url());
            exit;
        } else {
            error_log('Cannot redirect - Cart is empty');
        }
    }
}

// 3.1 Fix for AJAX add to cart
add_filter('woocommerce_add_to_cart_fragments', 'intercept_ajax_add_to_cart');
function intercept_ajax_add_to_cart($fragments) {
    error_log('AJAX add to cart intercepted');
    if (!WC()->cart->is_empty()) {
        wp_send_json(array(
            'error' => false,
            'redirect' => wc_get_checkout_url()
        ));
    }
    $fragments['redirect_url'] = wc_get_checkout_url();
    return $fragments;
}

// 4. Add JavaScript to handle all add to cart actions
add_action('wp_footer', 'add_checkout_redirect_js', 999);
function add_checkout_redirect_js() {
    if (is_admin()) return;
    
    // Get the checkout URL with a random parameter to prevent caching
    $checkout_url = add_query_arg('nocache', time(), wc_get_checkout_url());
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // 1. Intercept all add to cart forms
        $('body').on('submit', 'form.cart', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $button = $form.find('.single_add_to_cart_button');
            
            // Disable button to prevent multiple clicks
            $button.prop('disabled', true).addClass('loading');
            
            // Submit the form via AJAX
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: $form.serialize() + '&action=woocommerce_add_to_cart',
                success: function(response) {
                    // Redirect to checkout on success
                    window.location.href = '<?php echo esc_js($checkout_url); ?>';
                },
                error: function() {
                    // If AJAX fails, submit the form normally
                    $form.off('submit').submit();
                }
            });
            
            return false;
        });
        
        // 2. Handle simple add to cart links
        $('body').on('click', '.add_to_cart_button:not(.product_type_variable, .product_type_grouped, .product_type_external)', function(e) {
            e.preventDefault();
            var $button = $(this);
            
            // Skip if already processing
            if ($button.hasClass('loading')) return false;
            
            // Get the product ID and URL
            var product_id = $button.data('product_id');
            var product_url = $button.attr('href');
            
            // Disable button
            $button.addClass('loading');
            
            // Add to cart via AJAX
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: 'add-to-cart=' + product_id + '&action=woocommerce_add_to_cart',
                success: function() {
                    // Redirect to checkout
                    window.location.href = '<?php echo esc_js($checkout_url); ?>';
                },
                error: function() {
                    // If AJAX fails, redirect to the product URL
                    window.location.href = product_url;
                }
            });
            
            return false;
        });
        
        // 3. Handle AJAX add to cart events
        $(document.body).on('added_to_cart', function() {
            // Small delay to ensure cart is updated
            setTimeout(function() {
                window.location.href = '<?php echo esc_js($checkout_url); ?>';
            }, 100);
        });
    });
    </script>
    <?php
}