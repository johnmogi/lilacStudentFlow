<?php
/**
 * Simple Redirect to Specific Course After Purchase
 * 
 * Redirects users to course ID 898 after order completion
 */

// Redirect to specific course after purchase
add_action('template_redirect', 'redirect_to_specific_course_after_purchase');
function redirect_to_specific_course_after_purchase() {
    // Only on thank you page
    if (!is_wc_endpoint_url('order-received')) {
        return;
    }
    
    // Get the order ID from the URL
    $order_id = absint(get_query_var('order-received'));
    if (!$order_id) {
        return;
    }
    
    // Get the order object
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    // Only redirect if order is paid
    if (!$order->is_paid()) {
        return;
    }
    
    // Redirect to the specific course (ID 898)
    $course_url = get_permalink(898);
    
    // Add a small delay to ensure everything is processed
    add_action('wp_footer', function() use ($course_url) {
        ?>
        <script type="text/javascript">
        setTimeout(function() {
            window.location.href = '<?php echo esc_js($course_url); ?>';
        }, 1000); // 1 second delay
        </script>
        <?php
    });
    
    // Show a loading message
    add_filter('the_content', function($content) {
        if (is_wc_endpoint_url('order-received')) {
            return '<div style="text-align: center; padding: 40px 20px; font-family: Arial, sans-serif;">
                <h2>תודה על רכישתך!</h2>
                <p>מעביר אותך לקורס שלך...</p>
                <div style="margin: 20px 0;">
                    <div style="width: 50px; height: 50px; margin: 0 auto; border: 5px solid #f3f3f3; border-top: 5px solid #4f46e5; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
                <p>אם לא הועברת אוטומטית, <a href="' . esc_url($course_url) . '">לחץ כאן</a> כדי לגשת לקורס שלך.</p>
                <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                </style>
            </div>';
        }
        return $content;
    });
}
