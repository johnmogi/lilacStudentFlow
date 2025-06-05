<?php
/**
 * Admin page template for Registration Codes
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get instance of the registration codes class
$registration_codes = Registration_Codes::get_instance();
?>

<div class="wrap registration-codes">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php 
    // Display admin notices
    settings_errors('registration_codes_messages');
    
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'manage';
    $tabs = array(
        'manage' => __('Manage Codes', 'registration-codes'),
        'generate' => __('Generate Codes', 'registration-codes'),
        'import' => __('Import/Export', 'registration-codes'),
    );
    ?>
    
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab => $name) : ?>
            <a href="?page=registration-codes&tab=<?php echo esc_attr($tab); ?>" 
               class="nav-tab <?php echo $current_tab === $tab ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($name); ?>
            </a>
        <?php endforeach; ?>
    </h2>
    
    <div class="registration-codes-content">
        <?php 
        // Include the appropriate template based on the current tab
        $template_path = '';
        
        switch ($current_tab) {
            case 'generate':
                $template_path = __DIR__ . '/generate-codes.php';
                break;
                
            case 'import':
                $template_path = __DIR__ . '/import-export.php';
                break;
                
            case 'manage':
            default:
                $template_path = __DIR__ . '/manage-codes.php';
                $current_tab = 'manage';
                break;
        }
        
        // Check if template exists and include it
        if (!empty($template_path) && file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error"><p>' . 
                 sprintf(
                     __('Template file not found for tab: %s', 'registration-codes'),
                     '<code>' . esc_html($current_tab) . '</code>'
                 ) . 
                 '</p></div>';
        }
        ?>
    </div>
</div>

<script type="text/javascript">
// Ensure the current tab is properly highlighted
jQuery(document).ready(function($) {
    // Set the current tab in the URL if not already set
    var currentTab = '<?php echo esc_js($current_tab); ?>';
    if (window.location.href.indexOf('tab=') === -1) {
        var newUrl = window.location.href + (window.location.href.indexOf('?') === -1 ? '?' : '&') + 'tab=' + currentTab;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    // Add active class to current tab
    $('.nav-tab').removeClass('nav-tab-active');
    $('.nav-tab[href*="tab=' + currentTab + '"]').addClass('nav-tab-active');
});
</script>
