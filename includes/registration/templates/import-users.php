<?php
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary scripts and styles
function ccr_enqueue_import_scripts($hook) {
    if ('toplevel_page_import-users' !== $hook) {
        return;
    }
    
    wp_enqueue_script(
        'ccr-import-js',
        get_stylesheet_directory_uri() . '/assets/js/import-users.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    wp_localize_script('ccr-import-js', 'ccrImport', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ccr_import_nonce'),
        'i18n' => array(
            'uploading' => __('Uploading file...', 'ccr'),
            'processing' => __('Processing...', 'ccr'),
            'complete' => __('Import complete!', 'ccr'),
            'error' => __('An error occurred', 'ccr'),
            'select_file' => __('Please select a file to upload', 'ccr'),
            'select_user_type' => __('Please select a user type', 'ccr'),
            'processing_row' => __('Processing row %d of %d', 'ccr'),
            'import_complete' => __('Import complete! Processed %d rows with %d errors.', 'ccr')
        )
    ));
    
    wp_enqueue_style(
        'ccr-import-css',
        get_stylesheet_directory_uri() . '/assets/css/import-users.css',
        array(),
        '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'ccr_enqueue_import_scripts');

// Add menu page
function ccr_add_import_page() {
    add_menu_page(
        __('Import Users', 'ccr'),
        __('Import Users', 'ccr'),
        'manage_options',
        'import-users',
        'ccr_render_import_page',
        'dashicons-upload',
        30
    );
}
add_action('admin_menu', 'ccr_add_import_page');

// Render the import page
function ccr_render_import_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get user types for the dropdown
    $user_types = array(
        'student_school' => __('School Student', 'ccr'),
        'school_teacher' => __('School Teacher', 'ccr'),
        'independent_student' => __('Independent Student', 'ccr')
    );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="card">
            <h2><?php _e('Import Users from CSV', 'ccr'); ?></h2>
            <p><?php _e('Upload a CSV file containing user data. The first row should contain column headers.', 'ccr'); ?></p>
            
            <form id="ccr-import-form" method="post" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_type"><?php _e('User Type', 'ccr'); ?></label>
                        </th>
                        <td>
                            <select name="user_type" id="user_type" class="regular-text" required>
                                <option value=""><?php _e('Select User Type', 'ccr'); ?></option>
                                <?php foreach ($user_types as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Select the type of users you are importing.', 'ccr'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="import_file"><?php _e('CSV File', 'ccr'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="import_file" id="import_file" accept=".csv" required>
                            <p class="description">
                                <?php _e('Upload a CSV file containing user data. Maximum file size: 10MB', 'ccr'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="import-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress"></div>
                    </div>
                    <p class="status"></p>
                    <div class="import-results"></div>
                </div>
                
                <p class="submit">
                    <?php wp_nonce_field('ccr_import_nonce', 'ccr_import_nonce'); ?>
                    <button type="submit" class="button button-primary" id="start-import">
                        <?php _e('Start Import', 'ccr'); ?>
                    </button>
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2><?php _e('CSV Format', 'ccr'); ?></h2>
            <p><?php _e('Your CSV file should include the following columns:', 'ccr'); ?></p>
            
            <h3><?php _e('Required for all users:', 'ccr'); ?></h3>
            <ul>
                <li><code>first_name</code> - <?php _e('User first name', 'ccr'); ?></li>
                <li><code>last_name</code> - <?php _e('User last name', 'ccr'); ?></li>
                <li><code>phone</code> - <?php _e('Phone number (used as username)', 'ccr'); ?></li>
                <li><code>confirm_phone</code> - <?php _e('Confirm phone number', 'ccr'); ?></li>
            </ul>
            
            <h3><?php _e('For School Students:', 'ccr'); ?></h3>
            <ul>
                <li><code>id_number</code> - <?php _e('Student ID number', 'ccr'); ?></li>
                <li><code>confirm_id</code> - <?php _e('Confirm ID number', 'ccr'); ?></li>
                <li><code>course_code</code> - <?php _e('Course code', 'ccr'); ?></li>
                <li><code>subscription_period</code> - <?php _e('Subscription period (e.g., 1 year)', 'ccr'); ?></li>
                <li><code>school_code</code> - <?php _e('(Optional) School code', 'ccr'); ?></li>
            </ul>
            
            <h3><?php _e('For School Teachers:', 'ccr'); ?></h3>
            <ul>
                <li><code>school_code</code> - <?php _e('School code', 'ccr'); ?></li>
            </ul>
            
            <h3><?php _e('Optional fields:', 'ccr'); ?></h3>
            <ul>
                <li><code>shipping_method</code> - <?php _e('Preferred shipping method', 'ccr'); ?></li>
                <li><code>shipping_city</code> - <?php _e('Shipping city', 'ccr'); ?></li>
                <li><code>shipping_street</code> - <?php _e('Shipping street address', 'ccr'); ?></li>
                <li><code>shipping_phone</code> - <?php _e('Shipping phone number', 'ccr'); ?></li>
                <li><code>coupon_code</code> - <?php _e('Coupon code for discount', 'ccr'); ?></li>
            </ul>
            
            <p>
                <a href="<?php echo esc_url(plugins_url('sample-import.csv', __FILE__)); ?>" class="button button-secondary">
                    <?php _e('Download Sample CSV', 'ccr'); ?>
                </a>
            </p>
        </div>
    </div>
    <?php
}

// Enqueue scripts and styles
function ccr_enqueue_import_scripts() {
    wp_enqueue_script(
        'ccr-import-js',
        get_stylesheet_directory_uri() . '/assets/js/import-users.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    wp_localize_script('ccr-import-js', 'ccrImport', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ccr_import_nonce'),
        'i18n' => array(
            'uploading' => __('Uploading file...', 'ccr'),
            'processing' => __('Processing...', 'ccr'),
            'complete' => __('Import complete!', 'ccr'),
            'error' => __('An error occurred', 'ccr')
        )
    ));
    
    wp_enqueue_style(
        'ccr-import-css',
        get_stylesheet_directory_uri() . '/assets/css/import-users.css',
        array(),
        '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'ccr_enqueue_import_scripts');

// Initialize variables
$import_results = array();
$has_error = false;
$success_count = 0;
$error_count = 0;

if (isset($_POST['import_users']) && check_admin_referer('import_users_action', 'import_users_nonce')) {
    if (!empty($_FILES['users_csv']['tmp_name'])) {
        $file = $_FILES['users_csv'];
        
        // Check file type
        $file_type = wp_check_filetype($file['name'], array('csv' => 'text/csv'));
        
        if ($file_type['ext'] === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            
            if ($handle !== false) {
                $header = array_map('trim', fgetcsv($handle)); // Get and trim header row
                $header = array_map('strtolower', $header); // Convert header to lowercase
                
                // Define required fields based on import type
                $required_fields = array('first_name', 'last_name', 'phone', 'confirm_phone');
                
                // Check if this is a course subscription import
                $is_course_import = in_array('course_code', $header);
                if ($is_course_import) {
                    $required_fields = array_merge($required_fields, [
                        'id_number', 'confirm_id', 'course_code', 'course_program', 'subscription_period'
                    ]);
                }
                
                $missing_fields = array_diff($required_fields, $header);
                
                if (empty($missing_fields)) {
                    $row_number = 1; // Start from 1 to account for header row
                    
                    // Process each row in chunks
                    $chunk_size = 10;
                    $rows = array();
                    while (($data = fgetcsv($handle)) !== false) {
                        $rows[] = $data;
                        if (count($rows) >= $chunk_size) {
                            process_chunk($rows, $header, $is_course_import);
                            $rows = array();
                        }
                    }
                    
                    // Process remaining rows
                    if (!empty($rows)) {
                        process_chunk($rows, $header, $is_course_import);
                    }
                } else {
                    $has_error = true;
                    $import_results[] = array(
                        'status' => 'error',
                        'message' => sprintf(__('Missing required fields in CSV: %s', 'registration-codes'), 
                            implode(', ', $missing_fields))
                    );
                }
                
                fclose($handle);
            } else {
                $has_error = true;
                $import_results[] = array(
                    'status' => 'error',
                    'message' => __('Could not read the uploaded file.', 'registration-codes')
                );
            }
        } else {
            $has_error = true;
            $import_results[] = array(
                'status' => 'error',
                'message' => __('Invalid file type. Please upload a CSV file with .csv extension.', 'registration-codes')
            );
        }
    } else {
        $has_error = true;
        $import_results[] = array(
            'status' => 'error',
            'message' => __('Please select a file to upload.', 'registration-codes')
        );
    }
}

function process_chunk($rows, $header, $is_course_import) {
    global $import_results, $success_count, $error_count;
    
    foreach ($rows as $row) {
        $user_data = array_combine($header, array_map('trim', $row));
        
        // Skip empty rows
        if (empty(array_filter($user_data))) {
            continue;
        }
        
        // Validate required fields
        $missing_values = [];
        foreach ($required_fields as $field) {
            if (empty($user_data[$field])) {
                $missing_values[] = $field;
            }
        }
        
        if (!empty($missing_values)) {
            $error_count++;
            $import_results[] = array(
                'status' => 'error',
                'message' => sprintf(__('Row %d: Missing required fields: %s', 'registration-codes'), 
                    $row_number, implode(', ', $missing_values))
            );
            continue;
        }
        
        // Validate phone confirmation
        if ($user_data['phone'] !== $user_data['confirm_phone']) {
            $error_count++;
            $import_results[] = array(
                'status' => 'error',
                'message' => sprintf(__('Row %d: Phone numbers do not match', 'registration-codes'), $row_number)
            );
            continue;
        }
        
        // Validate ID confirmation for course imports
        if ($is_course_import && $user_data['id_number'] !== $user_data['confirm_id']) {
            $error_count++;
            $import_results[] = array(
                'status' => 'error',
                'message' => sprintf(__('Row %d: ID numbers do not match', 'registration-codes'), $row_number)
            );
            continue;
        }
        
        // Check if user already exists by phone (username)
        $user_exists = get_users(array(
            'meta_key' => 'phone',
            'meta_value' => $user_data['phone'],
            'number' => 1,
            'count_total' => false
        ));
        
        $is_new_user = empty($user_exists);
        $user_id = $is_new_user ? 0 : $user_exists[0]->ID;
        
        try {
            // Create or update user
            $userdata = array(
                'user_login'    => sanitize_user($user_data['phone'], true),
                'user_email'    => sanitize_email($user_data['phone'] . '@' . $_SERVER['HTTP_HOST']),
                'user_pass'     => !empty($user_data['id_number']) ? $user_data['id_number'] : wp_generate_password(12, true, true),
                'first_name'    => sanitize_text_field($user_data['first_name']),
                'last_name'     => sanitize_text_field($user_data['last_name']),
                'role'          => 'student_private', // Default role for imported independent students
                'display_name'  => sanitize_text_field($user_data['first_name'] . ' ' . $user_data['last_name'])
            );
            
            if ($is_new_user) {
                $user_id = wp_insert_user($userdata);
                if (is_wp_error($user_id)) {
                    throw new Exception($user_id->get_error_message());
                }
                $action = 'created';
            } else {
                $userdata['ID'] = $user_id;
                wp_update_user($userdata);
                $action = 'updated';
            }
            
            // Add/update user meta
            $meta_fields = [
                'phone' => $user_data['phone'],
                'confirm_phone' => $user_data['confirm_phone']
            ];
            
            if ($is_course_import) {
                $meta_fields = array_merge($meta_fields, [
                    'id_number' => $user_data['id_number'],
                    'confirm_id' => $user_data['confirm_id'],
                    'course_code' => $user_data['course_code'],
                    'course_program' => $user_data['course_program'],
                    'subscription_period' => $user_data['subscription_period']
                ]);
                
                // Handle course enrollment here if needed
                // $this->enroll_user_in_course($user_id, $user_data['course_code']);
            }
            
            // Handle shipping information if provided
            $shipping_fields = ['shipping_method', 'shipping_city', 'shipping_street', 'shipping_phone'];
            foreach ($shipping_fields as $field) {
                if (!empty($user_data[$field])) {
                    update_user_meta($user_id, $field, sanitize_text_field($user_data[$field]));
                }
            }
            
            // Add coupon code if provided
            if (!empty($user_data['coupon_code'])) {
                update_user_meta($user_id, 'coupon_code', sanitize_text_field($user_data['coupon_code']));
            }
            
            // Save all meta fields
            foreach ($meta_fields as $key => $value) {
                update_user_meta($user_id, $key, sanitize_text_field($value));
            }
            
            $success_count++;
            $import_results[] = array(
                'status' => 'success',
                'message' => sprintf(__('Successfully %s user: %s %s (Phone: %s)', 'registration-codes'), 
                    $action, $user_data['first_name'], $user_data['last_name'], $user_data['phone'])
            );
            
        } catch (Exception $e) {
            $error_count++;
            $import_results[] = array(
                'status' => 'error',
                'message' => sprintf(__('Row %d: Error processing user - %s', 'registration-codes'), 
                    $row_number, $e->getMessage())
            );
            continue;
        }
    }
}

?>

<div class="wrap ccr-import-container">
    <h1><?php _e('Import Users', 'registration-codes'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Use this tool to import users in bulk. The import process will run in the background, allowing you to import large files without timeouts.', 'registration-codes'); ?></p>
    </div>
        
        <div class="notice notice-info">
            <p><?php _e('Use this tool to import independent students (תלמיד עצמאי) with or without course subscriptions.', 'registration-codes'); ?></p>
        </div>
        
        <h3><?php _e('CSV File Requirements', 'registration-codes'); ?></h3>
        
        <div class="import-instructions">
            <h4><?php _e('Required Fields for All Imports:', 'registration-codes'); ?></h4>
            <ul class="ul-disc">
                <li><strong>first_name</strong> - <?php _e('User\'s first name', 'registration-codes'); ?></li>
                <li><strong>last_name</strong> - <?php _e('User\'s last name', 'registration-codes'); ?></li>
                <li><strong>phone</strong> - <?php _e('Mobile phone number with country code (used as username)', 'registration-codes'); ?></li>
                <li><strong>confirm_phone</strong> - <?php _e('Must match the phone number', 'registration-codes'); ?></li>
            </ul>
            
            <h4><?php _e('Additional Required Fields for Course Subscriptions:', 'registration-codes'); ?></h4>
            <ul class="ul-disc">
                <li><strong>id_number</strong> - <?php _e('User\'s ID number (used as password)', 'registration-codes'); ?></li>
                <li><strong>confirm_id</strong> - <?php _e('Must match the ID number', 'registration-codes'); ?></li>
                <li><strong>course_code</strong> - <?php _e('Numeric or string code of the course', 'registration-codes'); ?></li>
                <li><strong>course_program</strong> - <?php _e('Name of the study program', 'registration-codes'); ?></li>
                <li><strong>subscription_period</strong> - <?php _e('Duration (e.g., 2 weeks, 1 month, 2 months)', 'registration-codes'); ?></li>
            </ul>
            
            <h4><?php _e('Optional Fields:', 'registration-codes'); ?></h4>
            <ul class="ul-disc">
                <li><strong>coupon_code</strong> - <?php _e('Discount coupon code (if any)', 'registration-codes'); ?></li>
                <li><strong>shipping_method</strong> - <?php _e('pickup or delivery (for book orders)', 'registration-codes'); ?></li>
                <li><strong>shipping_city</strong> - <?php _e('City for delivery', 'registration-codes'); ?></li>
                <li><strong>shipping_street</strong> - <?php _e('Street address for delivery', 'registration-codes'); ?></li>
                <li><strong>shipping_phone</strong> - <?php _e('Contact phone for delivery', 'registration-codes'); ?></li>
            </ul>
        </div>
        
        <div class="import-actions">
            <a href="#" id="download-sample-csv" class="button button-primary">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Download Sample CSV', 'registration-codes'); ?>
            </a>
            
            <a href="#" id="show-csv-format" class="button">
                <span class="dashicons dashicons-editor-table"></span>
                <?php _e('View CSV Format', 'registration-codes'); ?>
            </a>
        </div>
        
        <div id="csv-format-example" style="display: none; margin-top: 20px; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
            <h4><?php _e('Example CSV Format:', 'registration-codes'); ?></h4>
            <pre>first_name,last_name,phone,confirm_phone,id_number,confirm_id,course_code,course_program,subscription_period,coupon_code,shipping_method,shivery_city,shipping_street,shipping_phone
דני,כהן,972501234567,972501234567,123456789,123456789,COURSE101,חינוך תעבורתי,6 months,DISCOUNT10,delivery,תל אביב,הרצל 123,0521234567
שרה,לוי,972502345678,972502345678,987654321,987654321,COURSE101,חינוך תעבורתי,6 months,,pickup,,,,</pre>
        </div>
        
        <form id="ccr-import-form" class="ccr-import-form" enctype="multipart/form-data">
        <?php wp_nonce_field('ccr_import_action', 'ccr_import_nonce'); ?>
        
        <div class="ccr-form-group">
            <label for="user-type"><?php _e('User Type', 'registration-codes'); ?></label>
            <select name="user_type" id="user-type" class="regular-text" required>
                <option value="student_private"><?php _e('Independent Student', 'registration-codes'); ?></option>
                <option value="student_school"><?php _e('School Student', 'registration-codes'); ?></option>
                <option value="school_teacher"><?php _e('School Teacher', 'registration-codes'); ?></option>
            </select>
        </div>
        
        <div id="import-options">
            <!-- Dynamic options will be inserted here by JS -->
        </div>
        
        <div class="ccr-form-group">
            <label for="import-file"><?php _e('CSV File', 'registration-codes'); ?></label>
            <input type="file" name="import_file" id="import-file" accept=".csv" required>
            <p class="description">
                <?php _e('Upload a CSV file containing user data. The first row should be column headers.', 'registration-codes'); ?>
            </p>
        </div>
            
            <div class="ccr-form-actions">
            <button type="submit" class="ccr-button" id="start-import">
                <?php _e('Start Import', 'registration-codes'); ?>
            </button>
            <button type="button" class="ccr-button ccr-button-secondary" id="pause-import" style="display: none;">
                <?php _e('Pause', 'registration-codes'); ?>
            </button>
            <span class="spinner"></span>
        </div>
        </form>
    
    <div id="import-progress" class="ccr-progress-container">
        <h3><?php _e('Import Progress', 'registration-codes'); ?></h3>
        <div class="ccr-progress-bar">
            <div class="ccr-progress"><span class="progress-percent">0%</span></div>
        </div>
        <div class="ccr-progress-details">
            <div>
                <span class="label"><?php _e('Processed:', 'registration-codes'); ?></span>
                <span id="processed-count">0</span> / <span id="total-count">0</span>
            </div>
            <div>
                <span class="label"><?php _e('Success:', 'registration-codes'); ?></span>
                <span id="success-count">0</span>
            </div>
            <div>
                <span class="label"><?php _e('Errors:', 'registration-codes'); ?></span>
                <span id="error-count">0</span>
            </div>
        </div>
        <div class="ccr-progress-message">
            <p class="status"><?php _e('Ready to start import...', 'registration-codes'); ?></p>
        </div>
        <div id="import-messages" class="ccr-messages"></div>
    </div>
    
    <div id="import-results"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Toggle CSV format example
    $('#show-csv-format').on('click', function(e) {
        e.preventDefault();
        $('#csv-format-example').slideToggle();
    });
    
    // Handle sample CSV download
    $('#download-sample-csv').on('click', function(e) {
        e.preventDefault();
        
        // Sample CSV data for independent students with course subscription
        const csvContent = [
            'first_name,last_name,phone,confirm_phone,id_number,confirm_id,course_code,course_program,subscription_period,coupon_code,shipping_method,shipping_city,shipping_street,shipping_phone',
            'דני,כהן,972501234567,972501234567,123456789,123456789,TRANS101,חינוך תעבורתי - כיתה י,6 months,DISCOUNT10,delivery,תל אביב,הרצל 123,0521234567',
            'שרה,לוי,972502345678,972502345678,987654321,987654321,TRANS101,חינוך תעבורתי - כיתה י,6 months,,pickup,,,',
            'משה,פרץ,972503456789,972503456789,456789123,456789123,TRANS101,חינוך תעבורתי - כיתה י,12 months,EARLYBIRD,delivery,חיפה,העצמאות 45,0545678912',
            'נעמה,כהן,972504567890,972504567890,321654987,321654987,,,,,,,,' // User without course subscription
        ].join('\n');
        
        // Create download link with BOM for Excel
        const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', 'independent-students-import-' + new Date().toISOString().split('T')[0] + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Show success message
        const $button = $(this);
        const originalText = $button.html();
        $button.html('<span class="dashicons dashicons-yes"></span> ' + '<?php _e('Sample Downloaded!', 'registration-codes'); ?>');
        
        setTimeout(function() {
            $button.html(originalText);
        }, 3000);
    });
    
    // Show/hide course-specific fields based on import type
    $('input[name="import_type"]').on('change', function() {
        if ($(this).val() === 'course') {
            $('.course-fields').show();
        } else {
            $('.course-fields').hide();
        }
    });
    
    // Show file name when selected
    $('input[type="file"]').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).next('.custom-file-label').html(fileName);
        }
    });
});
</script>

<style>
.import-instructions {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-left: 4px solid #2271b1;
}

.import-actions {
    margin: 20px 0;
    display: flex;
    gap: 10px;
}

.import-actions .dashicons {
    margin-right: 5px;
    vertical-align: middle;
    margin-top: -2px;
}

.import-form .form-table th {
    width: 200px;
}

.import-form .form-table td {
    padding: 15px 10px;
}

.import-form .notice {
    margin: 10px 0;
    padding: 10px 15px;
}

.import-form .button-primary {
    margin-top: 15px;
}

.import-log {
    margin-top: 20px;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
}

.log-entry {
    padding: 8px 12px;
    margin: 5px 0;
    border-radius: 3px;
}

.log-success {
    background-color: #edfaef;
    border-left: 4px solid #00a32a;
}

.log-error {
    background-color: #fcf0f1;
    border-left: 4px solid #d63638;
}

.log-warning {
    background-color: #fef8ee;
    border-left: 4px solid #dba617;
}
</style>
