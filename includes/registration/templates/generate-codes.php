<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Define specific roles with their display names
$roles = array(
    'school_teacher'  => 'מורה / רכז',
    'student_school'  => 'תלמיד חינוך תעבורתי',
    'student_private' => 'תלמיד עצמאי'
);

// Get existing groups for suggestions
global $wpdb;
$groups = $wpdb->get_col("SELECT DISTINCT group_name FROM {$wpdb->prefix}registration_codes WHERE group_name != '' ORDER BY group_name");
?>

<div class="registration-codes-generate">
    <div class="card">
        <h2><?php _e('Generate New Registration Codes', 'registration-codes'); ?></h2>
        
        <form id="generate-codes-form" method="post" action="">
            <?php wp_nonce_field('registration_codes_action', 'registration_codes_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="code-count"><?php _e('Number of codes', 'registration-codes'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="code-count" 
                                   name="code_count" 
                                   class="regular-text" 
                                   min="1" 
                                   max="1000" 
                                   value="10" 
                                   required>
                            <p class="description">
                                <?php _e('Enter the number of codes you want to generate (max 1000 at once).', 'registration-codes'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-role"><?php _e('User Role', 'registration-codes'); ?></label>
                        </th>
                        <td>
                            <select id="code-role" name="code_role" class="regular-text" required>
                                <?php foreach ($roles as $role => $name) : ?>
                                    <option value="<?php echo esc_attr($role); ?>">
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Select the user role that will be assigned when this code is used.', 'registration-codes'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-group"><?php _e('Group Name', 'registration-codes'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="code-group" 
                                   name="code_group" 
                                   class="regular-text" 
                                   list="group-suggestions"
                                   placeholder="<?php esc_attr_e('e.g., Marketing Team, Class 2023', 'registration-codes'); ?>">
                            <datalist id="group-suggestions">
                                <?php foreach ($groups as $group) : ?>
                                    <option value="<?php echo esc_attr($group); ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <p class="description">
                                <?php _e('Optional: Group these codes together for easier management.', 'registration-codes'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-prefix"><?php _e('Code Prefix', 'registration-codes'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="code-prefix" 
                                   name="code_prefix" 
                                   class="regular-text" 
                                   maxlength="10"
                                   placeholder="<?php esc_attr_e('e.g., PROMO', 'registration-codes'); ?>">
                            <p class="description">
                                <?php _e('Optional: Add a prefix to all generated codes (max 10 characters).', 'registration-codes'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-format"><?php _e('Code Format', 'registration-codes'); ?></label>
                        </th>
                        <td>
                            <select id="code-format" name="code_format" class="regular-text">
                                <option value="alphanumeric" selected><?php _e('Alphanumeric (A-Z, 0-9)', 'registration-codes'); ?></option>
                                <option value="letters"><?php _e('Letters only (A-Z)', 'registration-codes'); ?></option>
                                <option value="numbers"><?php _e('Numbers only (0-9)', 'registration-codes'); ?></option>
                            </select>
                            <p class="description">
                                <?php _e('Select the character set for the generated codes.', 'registration-codes'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="code-length"><?php _e('Code Length', 'registration-codes'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="code-length" 
                                   name="code_length" 
                                   class="small-text" 
                                   min="6" 
                                   max="32" 
                                   value="8">
                            <p class="description">
                                <?php _e('Length of each generated code (excluding prefix).', 'registration-codes'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <button type="submit" name="generate_codes" class="button button-primary">
                    <?php _e('Generate Codes', 'registration-codes'); ?>
                </button>
                <span class="spinner"></span>
            </p>
        </form>
    </div>
    
    <div id="generated-codes-container" class="card" style="display: none;">
        <h2><?php _e('Generated Codes', 'registration-codes'); ?></h2>
        
        <div class="generated-codes-actions">
            <button type="button" id="copy-codes" class="button">
                <span class="dashicons dashicons-clipboard"></span>
                <?php _e('Copy to Clipboard', 'registration-codes'); ?>
            </button>
            <button type="button" id="download-csv" class="button">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Download as CSV', 'registration-codes'); ?>
            </button>
            <button type="button" id="print-codes" class="button">
                <span class="dashicons dashicons-printer"></span>
                <?php _e('Print', 'registration-codes'); ?>
            </button>
        </div>
        
        <div id="generated-codes-output" class="code-output">
            <!-- Codes will be displayed here -->
        </div>
        
        <div class="generated-codes-notice notice notice-success" style="display: none;">
            <p></p>
        </div>
    </div>
</div>

<!-- Code Preview Modal -->
<div id="code-preview-modal" class="registration-codes-modal" style="display: none;">
    <div class="registration-codes-modal-content">
        <div class="registration-codes-modal-header">
            <h3><?php _e('Generated Codes Preview', 'registration-codes'); ?></h3>
            <button type="button" class="registration-codes-modal-close">&times;</button>
        </div>
        <div class="registration-codes-modal-body">
            <div class="code-preview-container"></div>
        </div>
        <div class="registration-codes-modal-footer">
            <button type="button" class="button button-primary registration-codes-modal-close">
                <?php _e('Close', 'registration-codes'); ?>
            </button>
        </div>
    </div>
</div>
