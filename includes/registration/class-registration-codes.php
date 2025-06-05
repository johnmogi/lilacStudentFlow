<?php
/**
 * Registration Codes Handler
 * Manages the generation, validation, and usage of registration codes with group management
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Registration_Codes {
    private static $instance = null;
    private $table_name;
    private $version = '1.1.0';

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'registration_codes';
        
        add_action('plugins_loaded', array($this, 'create_tables'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_generate_codes', array($this, 'ajax_generate_codes'));
        add_action('wp_ajax_validate_code', array($this, 'ajax_validate_code'));
        add_action('wp_ajax_import_codes', array($this, 'ajax_import_codes'));
        add_action('wp_ajax_export_codes', array($this, 'ajax_export_codes'));
        
        // User registration
        add_action('user_register', array($this, 'process_registration_code'));
        
        // Handle form submissions
        add_action('admin_init', array($this, 'handle_form_submissions'));
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $current_version = get_option('registration_codes_db_version', '0');

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            role varchar(50) NOT NULL DEFAULT 'subscriber',
            group_name varchar(100) DEFAULT '',
            is_used tinyint(1) DEFAULT 0,
            used_by bigint(20) DEFAULT NULL,
            used_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY group_name (group_name),
            KEY is_used (is_used)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Add group_name column if it doesn't exist (for updates)
        if (version_compare($current_version, '1.1.0', '<')) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN group_name varchar(100) DEFAULT '' AFTER role");
            update_option('registration_codes_db_version', $this->version);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Registration Codes',
            'Registration Codes',
            'manage_options',
            'registration-codes',
            array($this, 'render_admin_page'),
            'dashicons-tickets',
            30
        );

        // Add submenu for Import Users
        add_submenu_page(
            'registration-codes',
            'Import Users',
            'Import Users',
            'manage_options',
            'registration-import-users',
            array($this, 'render_import_users_page')
        );

        // Add submenu for Teacher Dashboard if needed
        add_submenu_page(
            'registration-codes',
            'Teacher Dashboard',
            'Teacher Dashboard',
            'manage_options',
            'teacher-dashboard',
            array($this, 'render_teacher_dashboard')
        );

        // Add submenu for Import Logs if needed
        add_submenu_page(
            'registration-codes',
            'Import Logs',
            'Import Logs',
            'manage_options',
            'registration-codes&tab=import-logs',
            array($this, 'render_admin_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'registration-codes') === false && $hook !== 'toplevel_page_registration-codes') {
            return;
        }

        // Enqueue WordPress media scripts for file uploads
        wp_enqueue_media();

        // Enqueue styles
        wp_enqueue_style(
            'registration-codes-css',
            get_stylesheet_directory_uri() . '/includes/registration/css/admin.css',
            array(),
            $this->version
        );

        // Enqueue scripts
        wp_enqueue_script(
            'registration-codes-js',
            get_stylesheet_directory_uri() . '/includes/registration/js/admin.js',
            array('jquery', 'jquery-ui-tabs'),
            $this->version,
            true
        );

        // Localize script with data
        wp_localize_script('registration-codes-js', 'registrationCodes', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('registration_codes_nonce'),
            'confirm_delete' => __('Are you sure you want to delete the selected codes?', 'registration-codes'),
            'confirm_export' => __('Preparing export file...', 'registration-codes'),
            'no_codes_selected' => __('Please select at least one code to export.', 'registration-codes'),
            'error_occurred' => __('An error occurred. Please try again.', 'registration-codes')
        ));
    }

    public function generate_codes($count = 1, $role = 'subscriber', $user_id = 0) {
        global $wpdb;
        $codes = array();
        $user_id = $user_id ?: get_current_user_id();

        for ($i = 0; $i < $count; $i++) {
            $code = $this->generate_unique_code();
            $wpdb->insert(
                $this->table_name,
                array(
                    'code' => $code,
                    'role' => $role,
                    'created_by' => $user_id
                ),
                array('%s', '%s', '%d')
            );
            $codes[] = $code;
        }

        return $codes;
    }

    /**
     * Render the admin page
     */
    public function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'registration-codes'));
        }

        // Get the template path
        $template_path = get_stylesheet_directory() . '/includes/registration/templates/admin-page.php';
        
        // Check if template exists
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error"><p>' . 
                 sprintf(
                     __('Template file not found: %s', 'registration-codes'),
                     '<code>' . esc_html($template_path) . '</code>'
                 ) . 
                 '</p></div>';
        }
    }

    /**
     * Render the teacher dashboard
     */
    public function render_teacher_dashboard() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'registration-codes'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Teacher Dashboard', 'registration-codes') . '</h1>';
        echo '<p>' . esc_html__('Teacher dashboard content will be displayed here.', 'registration-codes') . '</p>';
        echo '</div>';
    }

    /**
     * Render the Import Users page
     */
    public function render_import_users_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Include the import users template
        include plugin_dir_path(__FILE__) . 'templates/import-users.php';
    }

    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        if (!isset($_POST['registration_codes_nonce']) || 
            !wp_verify_nonce($_POST['registration_codes_nonce'], 'registration_codes_action')) {
            return;
        }

        // Handle form submissions here
        if (isset($_POST['generate_codes'])) {
            $this->handle_generate_codes();
        } elseif (isset($_FILES['import_file'])) {
            $this->handle_import_codes();
        }
    }

    /**
     * Handle code generation form submission
     */
    private function handle_generate_codes() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $count = isset($_POST['code_count']) ? absint($_POST['code_count']) : 1;
        $role = isset($_POST['code_role']) ? sanitize_text_field($_POST['code_role']) : 'subscriber';
        $group = isset($_POST['code_group']) ? sanitize_text_field($_POST['code_group']) : '';
        $prefix = isset($_POST['code_prefix']) ? sanitize_text_field($_POST['code_prefix']) : '';
        $format = isset($_POST['code_format']) ? sanitize_text_field($_POST['code_format']) : 'alphanumeric';
        $length = isset($_POST['code_length']) ? absint($_POST['code_length']) : 8;

        // Generate codes
        $codes = array();
        for ($i = 0; $i < $count; $i++) {
            $code = $this->generate_unique_code($length, $format, $prefix);
            if ($this->add_code($code, $role, $group)) {
                $codes[] = $code;
            }
        }

        // Set success message
        if (!empty($codes)) {
            add_settings_error(
                'registration_codes_messages',
                'codes_generated',
                sprintf(
                    _n(
                        'Successfully generated %d code.',
                        'Successfully generated %d codes.',
                        count($codes),
                        'registration-codes'
                    ),
                    count($codes)
                ),
                'updated'
            );
        }
    }

    /**
     * Add a new registration code
     */
    public function add_code($code, $role = 'subscriber', $group = '') {
        global $wpdb;
        
        // Check if code already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE code = %s",
            $code
        ));
        
        if ($exists) {
            return false;
        }
        
        // Insert new code
        return $wpdb->insert(
            $this->table_name,
            array(
                'code' => $code,
                'role' => $role,
                'group_name' => $group,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
    }

    /**
     * Generate a unique registration code
     */
    private function generate_unique_code($length = 8, $format = 'alphanumeric', $prefix = '') {
        $characters = '';
        
        switch ($format) {
            case 'letters':
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'numbers':
                $characters = '0123456789';
                break;
            case 'alphanumeric':
            default:
                $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
        }
        
        $code = $prefix;
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[mt_rand(0, $max)];
        }
        
        // Check if code already exists
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE code = %s",
            $code
        ));
        
        // If code exists, generate a new one recursively
        return $exists ? $this->generate_unique_code($length, $format, $prefix) : $code;
    }

    /**
     * Handle import of codes from CSV file
     */
    private function handle_import_codes() {
        if (!current_user_can('manage_options') || !isset($_FILES['import_file'])) {
            return;
        }

        $file = $_FILES['import_file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'registration_codes_messages',
                'import_error',
                __('Error uploading file. Please try again.', 'registration-codes'),
                'error'
            );
            return;
        }

        // Check file type
        $file_type = wp_check_filetype($file['name'], array('csv' => 'text/csv'));
        if ($file_type['ext'] !== 'csv') {
            add_settings_error(
                'registration_codes_messages',
                'import_error',
                __('Invalid file type. Please upload a CSV file.', 'registration-codes'),
                'error'
            );
            return;
        }

        // Open the file
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            add_settings_error(
                'registration_codes_messages',
                'import_error',
                __('Could not open the uploaded file.', 'registration-codes'),
                'error'
            );
            return;
        }

        // Get form data
        $has_headers = isset($_POST['has_headers']);
        $default_role = isset($_POST['default_role']) ? sanitize_text_field($_POST['default_role']) : 'subscriber';
        $default_group = isset($_POST['default_group']) ? sanitize_text_field($_POST['default_group']) : '';
        $skip_duplicates = !isset($_POST['skip_duplicates']) || $_POST['skip_duplicates'] === '1';

        $imported = 0;
        $skipped = 0;
        $row = 0;

        // Process the file
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            
            // Skip header row if needed
            if ($row === 1 && $has_headers) {
                continue;
            }

            // Get code and group from CSV
            $code = isset($data[0]) ? trim($data[0]) : '';
            $role = isset($data[1]) ? trim($data[1]) : $default_role;
            $group = isset($data[2]) ? trim($data[2]) : $default_group;

            // Validate code
            if (empty($code)) {
                $skipped++;
                continue;
            }

            // Check for duplicates
            if ($skip_duplicates && $this->code_exists($code)) {
                $skipped++;
                continue;
            }

            // Add the code
            if ($this->add_code($code, $role, $group)) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        fclose($handle);

        // Add success/error message
        if ($imported > 0) {
            add_settings_error(
                'registration_codes_messages',
                'import_success',
                sprintf(
                    _n(
                        'Successfully imported %d code. %d skipped.',
                        'Successfully imported %d codes. %d skipped.',
                        $imported,
                        'registration-codes'
                    ),
                    $imported,
                    $skipped
                ),
                'updated'
            );
        } else {
            add_settings_error(
                'registration_codes_messages',
                'import_error',
                __('No codes were imported. Please check your CSV file and try again.', 'registration-codes'),
                'error'
            );
        }
    }

    /**
     * Check if a code already exists
     */
    private function code_exists($code) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE code = %s",
            $code
        ));
    }
    
    /**
     * Get registration codes with filters and pagination
     *
     * @param string $group_filter Group filter
     * @param string $status_filter Status filter (active/used)
     * @param int $per_page Number of items per page
     * @param int $offset Offset for pagination
     * @return array List of registration codes
     */
    public function get_codes($group_filter = '', $status_filter = '', $per_page = 20, $offset = 0) {
        global $wpdb;
        
        $where = array('1=1');
        $params = array();
        
        // Add group filter
        if (!empty($group_filter)) {
            $where[] = 'group_name = %s';
            $params[] = $group_filter;
        }
        
        // Add status filter
        if ($status_filter === 'used') {
            $where[] = 'is_used = 1';
        } elseif ($status_filter === 'active') {
            $where[] = 'is_used = 0';
        }
        
        // Prepare the WHERE clause
        $where_clause = implode(' AND ', $where);
        
        // Prepare the query
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, array($per_page, $offset))
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Count registration codes with filters
     *
     * @param string $group_filter Group filter
     * @param string $status_filter Status filter (active/used)
     * @return int Number of matching codes
     */
    public function count_codes($group_filter = '', $status_filter = '') {
        global $wpdb;
        
        $where = array('1=1');
        $params = array();
        
        // Add group filter
        if (!empty($group_filter)) {
            $where[] = 'group_name = %s';
            $params[] = $group_filter;
        }
        
        // Add status filter
        if ($status_filter === 'used') {
            $where[] = 'is_used = 1';
        } elseif ($status_filter === 'active') {
            $where[] = 'is_used = 0';
        }
        
        // Prepare the WHERE clause
        $where_clause = implode(' AND ', $where);
        
        // Prepare the query
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE $where_clause",
            $params
        );
        
        return (int) $wpdb->get_var($query);
    }
    
    /**
     * Get all unique group names
     * 
     * @return array List of group names
     */
    public function get_groups() {
        global $wpdb;
        
        $groups = $wpdb->get_col(
            "SELECT DISTINCT group_name FROM {$this->table_name} WHERE group_name != '' ORDER BY group_name ASC"
        );
        
        return $groups ?: array();
    }
    
    /**
     * Export codes to CSV
     * 
     * @param array $args Export arguments
     */
    public function export_codes($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'format' => 'csv',
            'group' => '',
            'status' => 'all',
            'fields' => array('code', 'group', 'role', 'status', 'used_by', 'used_at'),
            'filename' => 'registration-codes-export-' . date('Y-m-d-H-i-s')
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build the query
        $where = array('1=1');
        $params = array();
        
        // Add group filter
        if (!empty($args['group'])) {
            $where[] = 'group_name = %s';
            $params[] = $args['group'];
        }
        
        // Add status filter
        if ($args['status'] === 'used') {
            $where[] = 'is_used = 1';
        } elseif ($args['status'] === 'active') {
            $where[] = 'is_used = 0';
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get the codes
        $query = "SELECT * FROM {$this->table_name} WHERE $where_clause ORDER BY created_at DESC";
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        $codes = $wpdb->get_results($query);
        
        if (empty($codes)) {
            return new WP_Error('no_codes', __('No codes found matching your criteria.', 'registration-codes'));
        }
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $args['filename'] . '.csv"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fputs($output, "\xEF\xBB\xBF");
        
        // Add headers
        $headers = array();
        foreach ($args['fields'] as $field) {
            switch ($field) {
                case 'code':
                    $headers[] = __('Code', 'registration-codes');
                    break;
                case 'group':
                    $headers[] = __('Group', 'registration-codes');
                    break;
                case 'role':
                    $headers[] = __('Role', 'registration-codes');
                    break;
                case 'status':
                    $headers[] = __('Status', 'registration-codes');
                    break;
                case 'used_by':
                    $headers[] = __('Used By', 'registration-codes');
                    break;
                case 'used_at':
                    $headers[] = __('Used At', 'registration-codes');
                    break;
                case 'created_at':
                    $headers[] = __('Created At', 'registration-codes');
                    break;
            }
        }
        
        fputcsv($output, $headers);
        
        // Add rows
        foreach ($codes as $code) {
            $row = array();
            $user = $code->used_by ? get_user_by('id', $code->used_by) : null;
            
            foreach ($args['fields'] as $field) {
                switch ($field) {
                    case 'code':
                        $row[] = $code->code;
                        break;
                    case 'group':
                        $row[] = $code->group_name;
                        break;
                    case 'role':
                        $row[] = $code->role;
                        break;
                    case 'status':
                        $row[] = $code->is_used ? __('Used', 'registration-codes') : __('Active', 'registration-codes');
                        break;
                    case 'used_by':
                        $row[] = $user ? $user->user_login : '';
                        break;
                    case 'used_at':
                        $row[] = $code->used_at ? $code->used_at : '';
                        break;
                    case 'created_at':
                        $row[] = $code->created_at;
                        break;
                }
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * AJAX handler for exporting codes
     */
    public function ajax_export_codes() {
        check_ajax_referer('registration_codes_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'registration-codes')));
        }
        
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        $group = isset($_POST['group']) ? sanitize_text_field($_POST['group']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'all';
        $fields = isset($_POST['fields']) ? (array) $_POST['fields'] : array('code', 'group', 'role', 'status');
        
        // Sanitize fields
        $allowed_fields = array('code', 'group', 'role', 'status', 'used_by', 'used_at', 'created_at');
        $fields = array_intersect($fields, $allowed_fields);
        
        if (empty($fields)) {
            $fields = array('code', 'group', 'role', 'status');
        }
        
        $result = $this->export_codes(array(
            'format' => $format,
            'group' => $group,
            'status' => $status,
            'fields' => $fields,
            'filename' => 'registration-codes-export-' . date('Y-m-d-H-i-s')
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success();
    }
    
    /**
     * AJAX handler for importing codes
     */
    public function ajax_import_codes() {
        check_ajax_referer('registration_codes_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'registration-codes')));
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded', 'registration-codes')));
        }
        
        $file = $_FILES['file'];
        $default_role = isset($_POST['default_role']) ? sanitize_text_field($_POST['default_role']) : 'subscriber';
        $default_group = isset($_POST['default_group']) ? sanitize_text_field($_POST['default_group']) : '';
        $skip_duplicates = !isset($_POST['skip_duplicates']) || $_POST['skip_duplicates'] === '1';
        $has_headers = isset($_POST['has_headers']);
        
        // Process the file
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            wp_send_json_error(array('message' => __('Could not open the uploaded file', 'registration-codes')));
        }
        
        $imported = 0;
        $skipped = 0;
        $row = 0;
        $results = array();
        
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            
            // Skip header row if needed
            if ($row === 1 && $has_headers) {
                continue;
            }
            
            // Get code and group from CSV
            $code = isset($data[0]) ? trim($data[0]) : '';
            $role = isset($data[1]) ? trim($data[1]) : $default_role;
            $group = isset($data[2]) ? trim($data[2]) : $default_group;
            
            // Validate code
            if (empty($code)) {
                $skipped++;
                $results[] = array(
                    'code' => '',
                    'status' => 'error',
                    'message' => sprintf(__('Row %d: Empty code', 'registration-codes'), $row)
                );
                continue;
            }
            
            // Check for duplicates
            if ($skip_duplicates && $this->code_exists($code)) {
                $skipped++;
                $results[] = array(
                    'code' => $code,
                    'status' => 'skipped',
                    'message' => sprintf(__('Code %s already exists', 'registration-codes'), $code)
                );
                continue;
            }
            
            // Add the code
            if ($this->add_code($code, $role, $group)) {
                $imported++;
                $results[] = array(
                    'code' => $code,
                    'status' => 'imported',
                    'message' => sprintf(__('Code %s imported successfully', 'registration-codes'), $code)
                );
            } else {
                $skipped++;
                $results[] = array(
                    'code' => $code,
                    'status' => 'error',
                    'message' => sprintf(__('Failed to import code %s', 'registration-codes'), $code)
                );
            }
        }
        
        fclose($handle);
        
        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => $imported + $skipped,
            'results' => $results
        ));
    }
    
    /**
     * AJAX handler for deleting codes
     */
    public function ajax_delete_codes() {
        check_ajax_referer('registration_codes_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'registration-codes')));
        }
        
        if (empty($_POST['codes']) || !is_array($_POST['codes'])) {
            wp_send_json_error(array('message' => __('No codes selected', 'registration-codes')));
        }
        
        $codes = array_map('intval', $_POST['codes']);
        $deleted = $this->delete_codes($codes);
        
        wp_send_json_success(array(
            'deleted' => $deleted,
            'message' => sprintf(
                _n('%d code deleted', '%d codes deleted', $deleted, 'registration-codes'),
                $deleted
            )
        ));
    }
    
    /**
     * Delete multiple codes
     * 
     * @param array $code_ids Array of code IDs to delete
     * @return int Number of deleted codes
     */
    public function delete_codes($code_ids) {
        global $wpdb;
        
        if (empty($code_ids)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($code_ids), '%d'));
        $query = $wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE id IN ($placeholders)",
            $code_ids
        );
        
        return $wpdb->query($query);
    }
}

// Initialize the registration codes system
function registration_codes_init() {
    return Registration_Codes::get_instance();
}
add_action('plugins_loaded', 'registration_codes_init');
