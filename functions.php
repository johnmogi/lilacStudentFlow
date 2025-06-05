<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

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
 * Redirect to checkout after adding a product to cart
 */
add_action('wp_footer', 'redirect_to_checkout_after_add_to_cart');
function redirect_to_checkout_after_add_to_cart() {
    if (!is_product()) return; // Only on single product pages
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Handle both AJAX and non-AJAX add to cart
        $(document).on('added_to_cart', function() {
            setTimeout(function() {
                window.location.href = '<?php echo esc_js(wc_get_checkout_url()); ?>';
            }, 500); // Small delay to ensure cart is updated
        });

        // Fallback for non-AJAX add to cart
        $('form.cart').on('submit', function(e) {
            var $form = $(this);
            var $button = $form.find('.single_add_to_cart_button');
            
            // Only if it's the purchase now button
            if ($button.text().trim() === 'רכשו עכשיו') {
                e.preventDefault();
                
                // Add the product to cart
                $.ajax({
                    type: 'POST',
                    url: wc_add_to_cart_params.ajax_url,
                    data: $form.serialize() + '&add-to-cart=' + $button.val(),
                    success: function(response) {
                        if (response.error && response.product_url) {
                            window.location = response.product_url;
                            return;
                        }
                        window.location.href = '<?php echo esc_js(wc_get_checkout_url()); ?>';
                    },
                    error: function() {
                        // If AJAX fails, let the form submit normally
                        $form.off('submit').submit();
                    }
                });
            }
        });
    });
    </script>
    <?php
}

/**
 * Enqueue scripts and styles
 */
function hello_elementor_child_scripts_styles() {
    // Parent theme styles
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['hello-elementor-theme-style'],
        filemtime(get_stylesheet_directory() . '/style.css')
    );
    
    // Enqueue custom scripts only if they exist
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
        
        // Enqueue quiz styles
        wp_enqueue_style(
            'quiz-styles',
            get_stylesheet_directory_uri() . '/assets/css/quiz-styles.css',
            [],
            filemtime(get_stylesheet_directory() . '/assets/css/quiz-styles.css')
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
        require_once get_stylesheet_directory() . '/src/Login/LoginManager.php';
        require_once get_stylesheet_directory() . '/src/Login/Captcha.php';
        require_once get_stylesheet_directory() . '/src/Login/UserAccountWidget.php';
    }
}
add_action('after_setup_theme', 'ccr_load_login_system', 10);

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

// Load Registration System
if (file_exists(get_stylesheet_directory() . '/includes/registration/class-registration-codes.php')) {
    require_once get_stylesheet_directory() . '/includes/registration/class-registration-codes.php';
    Registration_Codes::get_instance();
}

