<?php
/**
 * Unified Notification System
 *
 * Integrates alerts, messages, and other system notifications into a central toast system.
 *
 * @package HelloElementorChild
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize the unified notification system
 */
function lilac_init_notification_system() {
    // Load dependencies
    $toast_file = get_stylesheet_directory() . '/src/Messaging/Toast.php';
    $site_message_file = get_stylesheet_directory() . '/src/Messaging/SiteMessage.php';
    
    // Load required files
    if (file_exists($toast_file)) {
        require_once $toast_file;
    }
    
    if (file_exists($site_message_file)) {
        require_once $site_message_file;
    }
    
    // Register hooks
    add_action('wp_footer', 'lilac_render_toast_container', 20);
    add_action('admin_footer', 'lilac_render_toast_container', 20);
    
    // Convert WordPress alerts to toasts
    add_filter('wp_die_handler', 'lilac_custom_wp_die_handler', 10, 3);
    
    // Register shutdown function to catch fatal errors
    register_shutdown_function('lilac_catch_fatal_errors');
}
add_action('init', 'lilac_init_notification_system');

/**
 * Show a toast notification
 *
 * @param string $message   The message to display
 * @param string $type      The type of message (success, error, info, warning)
 * @param string $title     Optional title for the toast
 * @param int    $duration  How long to show the toast (in ms)
 * @param string $position  Position on screen (top-right, top-left, bottom-right, bottom-left)
 */
function lilac_show_toast($message, $type = 'info', $title = '', $duration = 5000, $position = 'top-right') {
    // When using AJAX or if headers already sent, use JavaScript approach
    if (wp_doing_ajax() || headers_sent()) {
        add_action('wp_footer', function() use ($message, $type, $title, $duration, $position) {
            echo "<script>
                (function() {
                    if (typeof window.LilacShowToast === 'function') {
                        window.LilacShowToast({
                            message: " . json_encode($message) . ",
                            type: " . json_encode($type) . ",
                            title: " . json_encode($title) . ",
                            duration: " . json_encode($duration) . ",
                            position: " . json_encode($position) . "
                        });
                    } else {
                        // Fallback if the toast system isn't loaded
                        console.log('Toast: ' + " . json_encode($title . ': ' . $message) . ");
                    }
                })();
            </script>";
        }, 999);
        
        // Also handle AJAX responses
        if (wp_doing_ajax()) {
            add_filter('wp_die_ajax_handler', function() use ($message, $type, $title) {
                return function($wpdie_message, $wpdie_title, $args) use ($message, $type, $title) {
                    wp_send_json([
                        'success' => false,
                        'data' => [
                            'message' => $message,
                            'type' => $type,
                            'title' => $title
                        ]
                    ]);
                };
            });
        }
        
        return;
    }
    
    // Fallback to a simple toast implementation
    $data = [
        'message' => $message,
        'type' => $type,
        'title' => $title,
        'duration' => $duration,
        'position' => $position
    ];
    
    // Store in a transient to display on next page load
    set_transient('lilac_toast_message_' . time(), $data, HOUR_IN_SECONDS);
}

/**
 * Display a success toast
 *
 * @param string $message  The message to display
 * @param string $title    Optional title
 */
function lilac_success_toast($message, $title = 'Success') {
    lilac_show_toast($message, 'success', $title);
}

/**
 * Display an error toast
 *
 * @param string $message  The message to display
 * @param string $title    Optional title
 */
function lilac_error_toast($message, $title = 'Error') {
    lilac_show_toast($message, 'error', $title);
}

/**
 * Display a warning toast
 *
 * @param string $message  The message to display
 * @param string $title    Optional title
 */
function lilac_warning_toast($message, $title = 'Warning') {
    lilac_show_toast($message, 'warning', $title);
}

/**
 * Display an info toast
 *
 * @param string $message  The message to display
 * @param string $title    Optional title
 */
function lilac_info_toast($message, $title = 'Information') {
    lilac_show_toast($message, 'info', $title);
}

/**
 * Render the toast container in the footer
 */
function lilac_render_toast_container() {
    // Try to use the advanced toast system if available
    if (class_exists('\\Lilac\\Messaging\\Toast')) {
        $toast = \Lilac\Messaging\Toast::get_instance();
        // The class already renders the container
        return;
    }
    
    // Fallback: Simple toast container
    ?>
    <div id="lilac-toast-container" class="top-right">
        <!-- Toast messages will be dynamically inserted here -->
    </div>
    
    <script>
    (function($) {
        // Process any stored toast messages
        $(document).ready(function() {
            // Check for toast messages in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('toast_message')) {
                const message = urlParams.get('toast_message');
                const type = urlParams.get('toast_type') || 'info';
                const title = urlParams.get('toast_title') || '';
                
                showToast(message, type, title);
            }
            
            // Function to display toast
            function showToast(message, type, title, duration = 5000, position = 'top-right') {
                const toast = $('<div class="lilac-toast ' + type + '"></div>');
                
                if (title) {
                    toast.append('<div class="toast-header">' + title + '</div>');
                }
                
                toast.append('<div class="toast-body">' + message + '</div>');
                toast.append('<button class="toast-close">&times;</button>');
                
                $('#lilac-toast-container').append(toast);
                
                // Position the container
                $('#lilac-toast-container').attr('class', position);
                
                // Show with animation
                setTimeout(function() {
                    toast.addClass('show');
                }, 10);
                
                // Set auto-dismiss
                if (duration > 0) {
                    setTimeout(function() {
                        toast.removeClass('show');
                        setTimeout(function() {
                            toast.remove();
                        }, 300);
                    }, duration);
                }
                
                // Close button
                toast.find('.toast-close').on('click', function() {
                    toast.removeClass('show');
                    setTimeout(function() {
                        toast.remove();
                    }, 300);
                });
            }
        });
    })(jQuery);
    </script>
    
    <style>
    #lilac-toast-container {
        position: fixed;
        z-index: 9999;
        max-width: 320px;
    }
    
    #lilac-toast-container.top-right {
        top: 20px;
        right: 20px;
    }
    
    #lilac-toast-container.top-left {
        top: 20px;
        left: 20px;
    }
    
    #lilac-toast-container.bottom-right {
        bottom: 20px;
        right: 20px;
    }
    
    #lilac-toast-container.bottom-left {
        bottom: 20px;
        left: 20px;
    }
    
    .lilac-toast {
        margin-bottom: 10px;
        min-width: 250px;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        background: white;
        border-left: 4px solid #ccc;
        opacity: 0;
        transform: translateX(50px);
        transition: all 0.3s ease;
    }
    
    .lilac-toast.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .lilac-toast.success {
        border-left-color: #28a745;
    }
    
    .lilac-toast.error {
        border-left-color: #dc3545;
    }
    
    .lilac-toast.warning {
        border-left-color: #ffc107;
    }
    
    .lilac-toast.info {
        border-left-color: #17a2b8;
    }
    
    .toast-header {
        padding: 10px 15px;
        font-weight: bold;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .toast-body {
        padding: 12px 15px;
    }
    
    .toast-close {
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #999;
    }
    </style>
    <?php
}

/**
 * Custom handler for wp_die to convert errors to toasts when appropriate
 * 
 * @param string|callable $handler The default handler
 * @param string|WP_Error $message Optional. Error message or WP_Error object
 * @param string          $title   Optional. Error title
 * @return callable                The handler function
 */
function lilac_custom_wp_die_handler($handler = null, $message = '', $title = '') {
    // Handle case when called with only one argument (the handler)
    if (func_num_args() === 1 && is_string($handler) && $handler === '_default_wp_die_handler') {
        return $handler;
    }
    // Only convert to toast for AJAX requests or if specifically requested
    if (wp_doing_ajax() || (isset($_REQUEST['toast_errors']) && $_REQUEST['toast_errors'])) {
        // Convert the error to a toast
        lilac_error_toast(strip_tags($message), $title ?: 'Error');
        
        // Return a JSON response for AJAX
        if (wp_doing_ajax()) {
            wp_send_json_error([
                'message' => strip_tags($message),
                'title' => $title
            ]);
            exit;
        }
        
        // For non-AJAX, redirect back with a toast parameter
        $redirect_url = add_query_arg([
            'toast_message' => urlencode(strip_tags($message)),
            'toast_type' => 'error',
            'toast_title' => urlencode($title ?: 'Error')
        ], wp_get_referer() ?: home_url());
        
        wp_redirect($redirect_url);
        exit;
    }
    
    // Fall back to default handler
    return $handler;
}

/**
 * Catch fatal PHP errors and display them as toasts
 */
function lilac_catch_fatal_errors() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR])) {
        // Only show detailed errors to admins
        if (current_user_can('manage_options')) {
            $message = sprintf(
                "Fatal error in %s on line %d: %s",
                basename($error['file']),
                $error['line'],
                $error['message']
            );
        } else {
            $message = "A system error has occurred. Please try again later.";
        }
        
        // Log the error
        error_log(sprintf(
            "Fatal error caught: %s in %s on line %d",
            $error['message'],
            $error['file'],
            $error['line']
        ));
        
        // Only convert to toast for AJAX or if we can redirect
        if (wp_doing_ajax() || !headers_sent()) {
            lilac_error_toast($message, 'System Error');
            
            if (wp_doing_ajax()) {
                // Send JSON response for AJAX
                wp_send_json_error([
                    'message' => $message,
                    'title' => 'System Error'
                ]);
                exit;
            } elseif (!headers_sent()) {
                // Redirect with toast parameter
                $redirect_url = add_query_arg([
                    'toast_message' => urlencode($message),
                    'toast_type' => 'error',
                    'toast_title' => 'System Error'
                ], home_url());
                
                wp_redirect($redirect_url);
                exit;
            }
        }
    }
}

/**
 * Converts WordPress admin notices to toast notifications
 */
function lilac_convert_admin_notices_to_toasts() {
    global $wp_filter;
    
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }
    
    // Get all admin notice hooks
    $notice_hooks = ['admin_notices', 'all_admin_notices', 'user_admin_notices', 'network_admin_notices'];
    
    foreach ($notice_hooks as $hook) {
        if (isset($wp_filter[$hook])) {
            // Store original callbacks
            $original_callbacks = $wp_filter[$hook]->callbacks;
            
            // Remove all callbacks
            $wp_filter[$hook]->callbacks = [];
            
            // Add a custom callback to handle notices
            add_action($hook, function() use ($original_callbacks) {
                // Start output buffering
                ob_start();
                
                // Run all original callbacks
                foreach ($original_callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $callback) {
                        call_user_func($callback['function']);
                    }
                }
                
                // Get the buffered content
                $notices = ob_get_clean();
                
                // Parse the notices and convert to toasts
                if (!empty($notices)) {
                    // Very basic parsing - this could be improved
                    $notices = strip_tags($notices, '<div><p><a><strong><em>');
                    
                    // Determine the type
                    $type = 'info';
                    if (strpos($notices, 'notice-error') !== false) {
                        $type = 'error';
                    } elseif (strpos($notices, 'notice-warning') !== false) {
                        $type = 'warning';
                    } elseif (strpos($notices, 'notice-success') !== false) {
                        $type = 'success';
                    }
                    
                    // Show the notice as a toast
                    lilac_show_toast($notices, $type, 'WordPress Notice');
                }
            });
        }
    }
}

// Only enable this if specifically requested, as it affects all admin notices
// add_action('admin_init', 'lilac_convert_admin_notices_to_toasts');
