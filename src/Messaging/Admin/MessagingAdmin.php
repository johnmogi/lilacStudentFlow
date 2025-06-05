<?php
/**
 * Messaging System Admin Interface
 *
 * Provides an admin interface for configuring the toast messaging system.
 *
 * @package Hello_Child_Theme
 * @subpackage Messaging/Admin
 */

namespace Lilac\Messaging\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MessagingAdmin {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Option name for storing message settings
     */
    private $option_name = 'lilac_messaging_settings';

    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add settings link on plugin page
        add_filter('plugin_action_links_hello-theme-child-master/style.css', array($this, 'add_settings_link'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Messaging System', 'hello-child'),
            __('Messaging', 'hello-child'),
            'manage_options',
            'lilac-messaging',
            array($this, 'admin_page_display'),
            'dashicons-megaphone',
            30
        );
        
        // Add submenus
        add_submenu_page(
            'lilac-messaging',
            __('Welcome Messages', 'hello-child'),
            __('Welcome Messages', 'hello-child'),
            'manage_options',
            'lilac-messaging',
            array($this, 'admin_page_display')
        );
        
        add_submenu_page(
            'lilac-messaging',
            __('Contextual Messages', 'hello-child'),
            __('Contextual Messages', 'hello-child'),
            'manage_options',
            'lilac-messaging-contextual',
            array($this, 'contextual_page_display')
        );
        
        add_submenu_page(
            'lilac-messaging',
            __('Progress Notifications', 'hello-child'),
            __('Progress Notifications', 'hello-child'),
            'manage_options',
            'lilac-messaging-progress',
            array($this, 'progress_page_display')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('lilac_messaging_settings', $this->option_name);
        
        // Welcome Message Settings
        add_settings_section(
            'lilac_messaging_welcome_section',
            __('Welcome Message Settings', 'hello-child'),
            array($this, 'welcome_section_callback'),
            'lilac-messaging'
        );
        
        add_settings_field(
            'welcome_message_enabled',
            __('Enable Welcome Message', 'hello-child'),
            array($this, 'checkbox_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_enabled',
                'field_name' => $this->option_name . '[welcome_message_enabled]'
            )
        );
        
        add_settings_field(
            'welcome_message_title',
            __('Welcome Message Title', 'hello-child'),
            array($this, 'text_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_title',
                'field_name' => $this->option_name . '[welcome_message_title]'
            )
        );
        
        add_settings_field(
            'welcome_message_content',
            __('Welcome Message Content', 'hello-child'),
            array($this, 'textarea_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_content',
                'field_name' => $this->option_name . '[welcome_message_content]',
                'description' => __('Supports HTML. Use this to welcome users to your site.', 'hello-child')
            )
        );
        
        add_settings_field(
            'welcome_message_type',
            __('Message Type', 'hello-child'),
            array($this, 'select_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_type',
                'field_name' => $this->option_name . '[welcome_message_type]',
                'options' => array(
                    'info' => __('Info (Blue)', 'hello-child'),
                    'success' => __('Success (Green)', 'hello-child'),
                    'warning' => __('Warning (Yellow)', 'hello-child'),
                    'error' => __('Error (Red)', 'hello-child')
                )
            )
        );
        
        add_settings_field(
            'welcome_message_auto_close',
            __('Auto Close After (seconds)', 'hello-child'),
            array($this, 'number_field_callback'),
            'lilac-messaging',
            'lilac_messaging_welcome_section',
            array(
                'label_for' => 'welcome_message_auto_close',
                'field_name' => $this->option_name . '[welcome_message_auto_close]',
                'description' => __('Set to 0 to disable auto-close', 'hello-child'),
                'min' => 0,
                'max' => 60
            )
        );
        
        // Display Conditions
        add_settings_section(
            'lilac_messaging_display_section',
            __('Display Conditions', 'hello-child'),
            array($this, 'display_section_callback'),
            'lilac-messaging'
        );
        
        add_settings_field(
            'welcome_display_homepage',
            __('Show on Homepage', 'hello-child'),
            array($this, 'checkbox_field_callback'),
            'lilac-messaging',
            'lilac_messaging_display_section',
            array(
                'label_for' => 'welcome_display_homepage',
                'field_name' => $this->option_name . '[welcome_display_homepage]'
            )
        );
        
        add_settings_field(
            'welcome_display_once_per_session',
            __('Show Once Per Session', 'hello-child'),
            array($this, 'checkbox_field_callback'),
            'lilac-messaging',
            'lilac_messaging_display_section',
            array(
                'label_for' => 'welcome_display_once_per_session',
                'field_name' => $this->option_name . '[welcome_display_once_per_session]'
            )
        );
        
        add_settings_field(
            'welcome_specific_urls',
            __('Show on Specific URLs', 'hello-child'),
            array($this, 'textarea_field_callback'),
            'lilac-messaging',
            'lilac_messaging_display_section',
            array(
                'label_for' => 'welcome_specific_urls',
                'field_name' => $this->option_name . '[welcome_specific_urls]',
                'description' => __('Enter one URL per line. Leave empty to show on all pages.', 'hello-child')
            )
        );
    }

    /**
     * Section callbacks
     */
    public function welcome_section_callback() {
        echo '<p>' . __('Configure the welcome message that will be shown to users.', 'hello-child') . '</p>';
    }
    
    public function display_section_callback() {
        echo '<p>' . __('Control where and when messages are displayed.', 'hello-child') . '</p>';
    }

    /**
     * Field callbacks
     */
    public function text_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $value = isset($options[$field_id]) ? $options[$field_id] : '';
        
        echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="regular-text">';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function textarea_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $value = isset($options[$field_id]) ? $options[$field_id] : '';
        
        echo '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function checkbox_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $checked = isset($options[$field_id]) && $options[$field_id] ? 'checked' : '';
        
        echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="1" ' . $checked . '>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function select_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $value = isset($options[$field_id]) ? $options[$field_id] : '';
        
        echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '">';
        
        foreach ($args['options'] as $option_value => $option_label) {
            $selected = ($value === $option_value) ? 'selected' : '';
            echo '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        
        echo '</select>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function number_field_callback($args) {
        $options = get_option($this->option_name);
        $field_name = $args['field_name'];
        $field_id = $args['label_for'];
        $value = isset($options[$field_id]) ? $options[$field_id] : 0;
        $min = isset($args['min']) ? 'min="' . intval($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . intval($args['max']) . '"' : '';
        
        echo '<input type="number" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" class="small-text" ' . $min . ' ' . $max . '>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Display the admin page
     */
    public function admin_page_display() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show status message
        settings_errors('lilac_messaging_settings');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(admin_url('admin.php?page=lilac-messaging')); ?>" class="nav-tab nav-tab-active"><?php _e('Welcome Message', 'hello-child'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=lilac-message-manager')); ?>" class="nav-tab"><?php _e('Message Management', 'hello-child'); ?></a>
            </h2>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('lilac_messaging_settings');
                do_settings_sections('lilac-messaging');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display the contextual messages page
     */
    public function contextual_page_display() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Enqueue necessary scripts and styles
        wp_enqueue_style('toast-system-css');
        wp_enqueue_script('jquery');
        wp_enqueue_script('toast-message-system');
        
        // Get current settings or defaults
        $settings = get_option($this->option_name . '_contextual', [
            'enable_quiz_exit_warning' => '1',
            'quiz_exit_message' => 'You have not completed this quiz yet. Are you sure you want to leave?',
            'quiz_incomplete_redirect' => '',
            'quiz_incomplete_message' => 'Please complete the quiz to continue.',
            'enable_quiz_completion_redirect' => '0',
            'quiz_completion_redirect' => '',
            'quiz_completion_delay' => '5',
            'quiz_completion_message' => 'Quiz completed successfully! Redirecting you in {seconds} seconds...',
        ]);
        
        // Handle form submission
        if (isset($_POST['lilac_contextual_nonce']) && wp_verify_nonce($_POST['lilac_contextual_nonce'], 'lilac_save_contextual_settings')) {
            $settings = [
                'enable_quiz_exit_warning' => isset($_POST['enable_quiz_exit_warning']) ? '1' : '0',
                'quiz_exit_message' => sanitize_text_field($_POST['quiz_exit_message'] ?? ''),
                'quiz_incomplete_redirect' => esc_url_raw($_POST['quiz_incomplete_redirect'] ?? ''),
                'quiz_incomplete_message' => sanitize_text_field($_POST['quiz_incomplete_message'] ?? ''),
                'enable_quiz_completion_redirect' => isset($_POST['enable_quiz_completion_redirect']) ? '1' : '0',
                'quiz_completion_redirect' => esc_url_raw($_POST['quiz_completion_redirect'] ?? ''),
                'quiz_completion_delay' => absint($_POST['quiz_completion_delay'] ?? 5),
                'quiz_completion_message' => sanitize_text_field($_POST['quiz_completion_message'] ?? ''),
            ];
            
            update_option($this->option_name . '_contextual', $settings);
            add_settings_error(
                'lilac_contextual_messages',
                'lilac_message',
                __('Settings Saved', 'hello-child'),
                'success'
            );
        }
        
        // Display any messages
        settings_errors('lilac_contextual_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('Configure contextual messages for quizzes and other interactive elements.', 'hello-child'); ?></p>
            </div>
            
            <div class="lilac-contextual-admin-container">
                <div class="lilac-contextual-admin-main">
                    <form method="post" action="">
                        <?php wp_nonce_field('lilac_save_contextual_settings', 'lilac_contextual_nonce'); ?>
                        
                        <h2><?php _e('Quiz Exit Intent', 'hello-child'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Exit Warning', 'hello-child'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_quiz_exit_warning" value="1" <?php checked($settings['enable_quiz_exit_warning'], '1'); ?>>
                                        <?php _e('Show warning when leaving an incomplete quiz', 'hello-child'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_exit_message"><?php _e('Exit Warning Message', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="quiz_exit_message" name="quiz_exit_message" value="<?php echo esc_attr($settings['quiz_exit_message']); ?>" class="regular-text">
                                    <p class="description"><?php _e('Message to show when user tries to leave an incomplete quiz', 'hello-child'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_incomplete_redirect"><?php _e('Incomplete Quiz Redirect', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="quiz_incomplete_redirect" name="quiz_incomplete_redirect" value="<?php echo esc_attr($settings['quiz_incomplete_redirect']); ?>" class="regular-text">
                                    <p class="description"><?php _e('Where to redirect users who try to access content without completing a quiz (leave blank to show a message instead)', 'hello-child'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_incomplete_message"><?php _e('Incomplete Quiz Message', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="quiz_incomplete_message" name="quiz_incomplete_message" value="<?php echo esc_attr($settings['quiz_incomplete_message']); ?>" class="regular-text">
                                    <p class="description"><?php _e('Message to show when user hasn\'t completed required quiz (if no redirect is set)', 'hello-child'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <h2><?php _e('Quiz Completion', 'hello-child'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Completion Redirect', 'hello-child'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_quiz_completion_redirect" value="1" <?php checked($settings['enable_quiz_completion_redirect'], '1'); ?>>
                                        <?php _e('Redirect users after completing a quiz', 'hello-child'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_completion_redirect"><?php _e('Completion Redirect URL', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="quiz_completion_redirect" name="quiz_completion_redirect" value="<?php echo esc_attr($settings['quiz_completion_redirect']); ?>" class="regular-text">
                                    <p class="description"><?php _e('Where to redirect users after they complete a quiz', 'hello-child'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_completion_delay"><?php _e('Redirect Delay (seconds)', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="quiz_completion_delay" name="quiz_completion_delay" min="0" max="60" value="<?php echo esc_attr($settings['quiz_completion_delay']); ?>" class="small-text">
                                    <p class="description"><?php _e('How long to show the completion message before redirecting (0 for immediate)', 'hello-child'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="quiz_completion_message"><?php _e('Completion Message', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="quiz_completion_message" name="quiz_completion_message" value="<?php echo esc_attr($settings['quiz_completion_message']); ?>" class="regular-text">
                                    <p class="description"><?php _e('Use {seconds} to show countdown. Example: "Redirecting in {seconds} seconds..."', 'hello-child'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Settings', 'hello-child')); ?>
                    </form>
                </div>
                
                <div class="lilac-contextual-admin-sidebar">
                    <div class="lilac-contextual-demo-card">
                        <h3><?php _e('Implementation Notes', 'hello-child'); ?></h3>
                        <p><?php _e('To use these features, add the following code to your quiz template or theme:', 'hello-child'); ?></p>
                        
                        <h4><?php _e('1. Quiz Exit Intent', 'hello-child'); ?></h4>
                        <pre><code>&lt;?php
// Add this to your quiz page template
if (function_exists('lilac_quiz_exit_intent')) {
    lilac_quiz_exit_intent();
}
?&gt;</code></pre>
                        
                        <h4><?php _e('2. Quiz Completion Check', 'hello-child'); ?></h4>
                        <pre><code>&lt;?php
// Add this to content that requires quiz completion
if (function_exists('lilac_check_quiz_completion') && !lilac_check_quiz_completion($required_quiz_id)) {
    // Show message or redirect based on settings
    lilac_show_quiz_incomplete_message($required_quiz_id);
    return; // Stop further content rendering
}
?&gt;</code></pre>
                        
                        <h4><?php _e('3. Quiz Completion Handler', 'hello-child'); ?></h4>
                        <pre><code>&lt;?php
// Add this when a quiz is completed
if (function_exists('lilac_handle_quiz_completion')) {
    lilac_handle_quiz_completion($quiz_id, $user_id);
}
?&gt;</code></pre>
                    </div>
                </div>
            </div>
            
            <style>
                .lilac-contextual-admin-container {
                    display: flex;
                    gap: 20px;
                    margin-top: 20px;
                }
                .lilac-contextual-admin-main {
                    flex: 2;
                    background: #fff;
                    padding: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .lilac-contextual-admin-sidebar {
                    flex: 1;
                }
                .lilac-contextual-demo-card {
                    background: #fff;
                    padding: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .lilac-contextual-demo-card h3 {
                    margin-top: 0;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }
                .lilac-contextual-demo-card h4 {
                    margin: 1.5em 0 0.5em;
                }
                .lilac-contextual-demo-card pre {
                    background: #f5f5f5;
                    padding: 10px;
                    border-radius: 3px;
                    overflow-x: auto;
                }
                .lilac-contextual-demo-card code {
                    font-family: monospace;
                    font-size: 13px;
                    line-height: 1.5;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Display the progress notifications page
     */
    public function progress_page_display() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('toast-system-css');
        wp_enqueue_script('jquery');
        wp_enqueue_script('toast-message-system');
        wp_enqueue_script('toast-session');
        wp_enqueue_script('toast-test-timer');
        
        // Localize script for AJAX and translations
        wp_localize_script('toast-message-system', 'lilacToastAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lilac_toast_admin_nonce'),
            'i18n' => [
                'testNotification' => __('Test notification sent!', 'hello-child'),
                'settingsSaved' => __('Settings saved successfully!', 'hello-child'),
                'errorSaving' => __('Error saving settings. Please try again.', 'hello-child')
            ]
        ]);
        
        // Get current settings or defaults
        $settings = get_option($this->option_name . '_progress', [
            'enable_session_warning' => '1',
            'session_timeout' => '30',
            'warning_before' => '5',
            'enable_test_timer' => '1',
            'test_duration' => '60',
            'warning_time' => '10',
            'critical_time' => '2'
        ]);
        
        // Handle form submission
        if (isset($_POST['lilac_progress_nonce']) && wp_verify_nonce($_POST['lilac_progress_nonce'], 'lilac_save_progress_settings')) {
            $settings = [
                'enable_session_warning' => isset($_POST['enable_session_warning']) ? '1' : '0',
                'session_timeout' => absint($_POST['session_timeout'] ?? 30),
                'warning_before' => absint($_POST['warning_before'] ?? 5),
                'enable_test_timer' => isset($_POST['enable_test_timer']) ? '1' : '0',
                'test_duration' => absint($_POST['test_duration'] ?? 60),
                'warning_time' => absint($_POST['warning_time'] ?? 10),
                'critical_time' => absint($_POST['critical_time'] ?? 2)
            ];
            
            update_option($this->option_name . '_progress', $settings);
            add_settings_error(
                'lilac_progress_messages',
                'lilac_message',
                __('Settings Saved', 'hello-child'),
                'success'
            );
        }
        
        // Display any messages
        settings_errors('lilac_progress_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php _e('Configure notifications for course progress and test timing.', 'hello-child'); ?></p>
            
            <div class="notice notice-info">
                <p><?php _e('Configure the toast notification system for session management and test timing.', 'hello-child'); ?></p>
            </div>
            
            <div class="lilac-toast-admin-container">
                <div class="lilac-toast-admin-main">
                    <form method="post" action="">
                        <?php wp_nonce_field('lilac_save_progress_settings', 'lilac_progress_nonce'); ?>
                        
                        <h2><?php _e('Session Management', 'hello-child'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Session Warnings', 'hello-child'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_session_warning" value="1" <?php checked($settings['enable_session_warning'], '1'); ?>>
                                        <?php _e('Show warning when session is about to expire', 'hello-child'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="session_timeout"><?php _e('Session Timeout (minutes)', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="session_timeout" name="session_timeout" min="1" max="240" value="<?php echo esc_attr($settings['session_timeout']); ?>" class="small-text">
                                    <p class="description"><?php _e('Time of inactivity before session expires', 'hello-child'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="warning_before"><?php _e('Show Warning Before (minutes)', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="warning_before" name="warning_before" min="1" max="30" value="<?php echo esc_attr($settings['warning_before']); ?>" class="small-text">
                                    <p class="description"><?php _e('Show warning this many minutes before session expires', 'hello-child'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <h2><?php _e('Test Timer Settings', 'hello-child'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Test Timer', 'hello-child'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_test_timer" value="1" <?php checked($settings['enable_test_timer'], '1'); ?>>
                                        <?php _e('Show countdown timer for tests', 'hello-child'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="test_duration"><?php _e('Test Duration (minutes)', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="test_duration" name="test_duration" min="1" max="240" value="<?php echo esc_attr($settings['test_duration']); ?>" class="small-text">
                                    <p class="description"><?php _e('Default test duration in minutes', 'hello-child'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="warning_time"><?php _e('Show Warning At (minutes remaining)', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="warning_time" name="warning_time" min="1" max="60" value="<?php echo esc_attr($settings['warning_time']); ?>" class="small-text">
                                    <p class="description"><?php _e('Show warning when this many minutes are left', 'hello-child'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="critical_time"><?php _e('Show Critical Warning At (minutes remaining)', 'hello-child'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="critical_time" name="critical_time" min="0" max="10" value="<?php echo esc_attr($settings['critical_time']); ?>" class="small-text">
                                    <p class="description"><?php _e('Show critical warning when this many minutes are left', 'hello-child'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Settings', 'hello-child')); ?>
                    </form>
                </div>
                
                <div class="lilac-toast-admin-sidebar">
                    <div class="lilac-toast-demo-card">
                        <h3><?php _e('Test Notifications', 'hello-child'); ?></h3>
                        <p><?php _e('Test the different notification types:', 'hello-child'); ?></p>
                        <p>
                            <button type="button" class="button button-secondary lilac-test-toast" data-type="success"><?php _e('Success', 'hello-child'); ?></button>
                            <button type="button" class="button button-secondary lilac-test-toast" data-type="error"><?php _e('Error', 'hello-child'); ?></button>
                            <button type="button" class="button button-secondary lilac-test-toast" data-type="warning"><?php _e('Warning', 'hello-child'); ?></button>
                            <button type="button" class="button button-secondary lilac-test-toast" data-type="info"><?php _e('Info', 'hello-child'); ?></button>
                        </p>
                        
                        <h3><?php _e('Test Session Warning', 'hello-child'); ?></h3>
                        <p><?php _e('Preview the session warning:', 'hello-child'); ?></p>
                        <p>
                            <button type="button" class="button button-primary" id="lilac-test-session-warning"><?php _e('Show Session Warning', 'hello-child'); ?></button>
                        </p>
                        
                        <h3><?php _e('Test Timer', 'hello-child'); ?></h3>
                        <p><?php _e('Preview the test timer:', 'hello-child'); ?></p>
                        <p>
                            <button type="button" class="button button-primary" id="lilac-test-timer"><?php _e('Start Test Timer', 'hello-child'); ?></button>
                        </p>
                    </div>
                </div>
            </div>
            
            <style>
                .lilac-toast-admin-container {
                    display: flex;
                    gap: 20px;
                    margin-top: 20px;
                }
                .lilac-toast-admin-main {
                    flex: 2;
                    background: #fff;
                    padding: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .lilac-toast-admin-sidebar {
                    flex: 1;
                }
                .lilac-toast-demo-card {
                    background: #fff;
                    padding: 20px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .lilac-toast-demo-card h3 {
                    margin-top: 0;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }
                .lilac-toast-demo-card p {
                    margin: 1em 0;
                }
                .lilac-test-toast {
                    margin-right: 5px;
                    margin-bottom: 5px;
                }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                // Test toast notifications
                $('.lilac-test-toast').on('click', function() {
                    const type = $(this).data('type');
                    const title = type.charAt(0).toUpperCase() + type.slice(1) + '!';
                    const message = 'This is a ' + type + ' notification example.';
                    
                    switch(type) {
                        case 'success':
                            window.LilacToast.success(message, title);
                            break;
                        case 'error':
                            window.LilacToast.error(message, title);
                            break;
                        case 'warning':
                            window.LilacToast.warning(message, title);
                            break;
                        case 'info':
                        default:
                            window.LilacToast.info(message, title);
                    }
                });
                
                // Test session warning
                $('#lilac-test-session-warning').on('click', function() {
                    if (typeof window.LilacToast.session !== 'undefined') {
                        // Show a warning that would normally appear 5 minutes before session timeout
                        window.LilacToast.session.showWarning();
                    } else {
                        alert('Session management not loaded. Make sure the session-toast.js is enqueued.');
                    }
                });
                
                // Test timer
                $('#lilac-test-timer').on('click', function() {
                    if (typeof window.LilacToast.testTimer !== 'undefined') {
                        // Start a 2-minute test timer for demo purposes
                        window.LilacToast.testTimer.init({
                            testDuration: 2 * 60 * 1000, // 2 minutes
                            warningTime: 60 * 1000, // 1 minute
                            criticalTime: 30 * 1000, // 30 seconds
                            onTimeUp: function() {
                                window.LilacToast.error('Test timer complete!', 'Time\'s Up!');
                            },
                            onWarningTime: function() {
                                window.LilacToast.warning('Test will end in 1 minute!', 'Time Warning');
                            },
                            onCriticalTime: function() {
                                window.LilacToast.error('Less than 30 seconds remaining!', 'Hurry!');
                            }
                        });
                        
                        window.LilacToast.info('Test timer started! 2 minutes remaining.', 'Timer Started');
                    } else {
                        alert('Test timer not loaded. Make sure the test-timer-toast.js is enqueued.');
                    }
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Add settings link
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=lilac-messaging">' . __('Settings', 'hello-child') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the class
function lilac_messaging_admin() {
    return MessagingAdmin::get_instance();
}
lilac_messaging_admin();
