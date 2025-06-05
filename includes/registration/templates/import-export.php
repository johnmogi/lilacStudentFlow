<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get available user roles
$editable_roles = array_reverse(get_editable_roles());
$roles = array();

foreach ($editable_roles as $role => $details) {
    $roles[$role] = translate_user_role($details['name']);
}

// Get existing groups for suggestions
global $wpdb;
$groups = $wpdb->get_col("SELECT DISTINCT group_name FROM {$wpdb->prefix}registration_codes WHERE group_name != '' ORDER BY group_name");
?>

<div class="registration-codes-import-export">
    <div class="card">
        <h2><?php _e('Import/Export Registration Codes', 'registration-codes'); ?></h2>
        
        <div class="nav-tab-wrapper">
            <a href="#import-tab" class="nav-tab nav-tab-active"><?php _e('Import Codes', 'registration-codes'); ?></a>
            <a href="#export-tab" class="nav-tab"><?php _e('Export Codes', 'registration-codes'); ?></a>
        </div>
        
        <div class="tab-content">
            <!-- Import Tab -->
            <div id="import-tab" class="tab-pane active">
                <div class="card">
                    <h3><?php _e('Import Codes from CSV', 'registration-codes'); ?></h3>
                    <p><?php _e('Upload a CSV file containing registration codes. Each code should be on a new line.', 'registration-codes'); ?></p>
                    
                    <form id="import-codes-form" method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('registration_codes_action', 'registration_codes_nonce'); ?>
                        
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="import-file"><?php _e('CSV File', 'registration-codes'); ?></label>
                                    </th>
                                    <td>
                                        <input type="file" 
                                               id="import-file" 
                                               name="import_file" 
                                               accept=".csv" 
                                               required>
                                        <p class="description">
                                            <?php _e('Upload a CSV file with one code per line. Optionally, include a second column for group names.', 'registration-codes'); ?>
                                            <a href="#" id="download-sample-csv"><?php _e('Download sample CSV', 'registration-codes'); ?></a>
                                        </p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="import-role"><?php _e('Default User Role', 'registration-codes'); ?></label>
                                    </th>
                                    <td>
                                        <select id="import-role" name="import_role" class="regular-text" required>
                                            <?php foreach ($roles as $role => $name) : ?>
                                                <option value="<?php echo esc_attr($role); ?>">
                                                    <?php echo esc_html($name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">
                                            <?php _e('Default role for imported codes (if not specified in CSV).', 'registration-codes'); ?>
                                        </p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="import-group"><?php _e('Default Group', 'registration-codes'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="import-group" 
                                               name="import_group" 
                                               class="regular-text" 
                                               list="import-group-suggestions"
                                               placeholder="<?php esc_attr_e('e.g., Marketing Team', 'registration-codes'); ?>">
                                        <datalist id="import-group-suggestions">
                                            <?php foreach ($groups as $group) : ?>
                                                <option value="<?php echo esc_attr($group); ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                        <p class="description">
                                            <?php _e('Default group for imported codes (if not specified in CSV).', 'registration-codes'); ?>
                                        </p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="import-duplicates"><?php _e('Duplicate Handling', 'registration-codes'); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="radio" 
                                                   name="import_duplicates" 
                                                   value="skip" 
                                                   checked>
                                            <?php _e('Skip duplicate codes', 'registration-codes'); ?>
                                        </label>
                                        <br>
                                        <label>
                                            <input type="radio" 
                                                   name="import_duplicates" 
                                                   value="overwrite">
                                            <?php _e('Overwrite existing codes', 'registration-codes'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" name="import_codes" class="button button-primary">
                                <?php _e('Import Codes', 'registration-codes'); ?>
                            </button>
                            <span class="spinner"></span>
                        </p>
                    </form>
                </div>
                
                <div id="import-results" class="card" style="display: none;">
                    <h3><?php _e('Import Results', 'registration-codes'); ?></h3>
                    <div id="import-results-content"></div>
                </div>
            </div>
            
            <!-- Export Tab -->
            <div id="export-tab" class="tab-pane">
                <div class="card">
                    <h3><?php _e('Export Registration Codes', 'registration-codes'); ?></h3>
                    <p><?php _e('Export your registration codes to a CSV file.', 'registration-codes'); ?></p>
                    
                    <form id="export-codes-form" method="post">
                        <?php wp_nonce_field('registration_codes_action', 'registration_codes_nonce'); ?>
                        
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="export-format"><?php _e('Export Format', 'registration-codes'); ?></label>
                                    </th>
                                    <td>
                                        <select id="export-format" name="export_format" class="regular-text">
                                            <option value="csv" selected><?php _e('CSV (Comma-Separated Values)', 'registration-codes'); ?></option>
                                            <option value="excel"><?php _e('Excel CSV (for Excel)', 'registration-codes'); ?></option>
                                            <option value="json"><?php _e('JSON', 'registration-codes'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="export-group"><?php _e('Filter by Group', 'registration-codes'); ?></label>
                                    </th>
                                    <td>
                                        <select id="export-group" name="export_group" class="regular-text">
                                            <option value=""><?php _e('All Groups', 'registration-codes'); ?></option>
                                            <?php foreach ($groups as $group) : ?>
                                                <option value="<?php echo esc_attr($group); ?>">
                                                    <?php echo esc_html($group); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="export-status"><?php _e('Filter by Status', 'registration-codes'); ?></label>
                                    </th>
                                    <td>
                                        <select id="export-status" name="export_status" class="regular-text">
                                            <option value=""><?php _e('All Statuses', 'registration-codes'); ?></option>
                                            <option value="active"><?php _e('Active Only', 'registration-codes'); ?></option>
                                            <option value="used"><?php _e('Used Only', 'registration-codes'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="export-fields"><?php _e('Fields to Export', 'registration-codes'); ?></label>
                                    </th>
                                    <td>
                                        <fieldset>
                                            <label>
                                                <input type="checkbox" name="export_fields[]" value="code" checked>
                                                <?php _e('Code', 'registration-codes'); ?>
                                            </label>
                                            <br>
                                            <label>
                                                <input type="checkbox" name="export_fields[]" value="role" checked>
                                                <?php _e('Role', 'registration-codes'); ?>
                                            </label>
                                            <br>
                                            <label>
                                                <input type="checkbox" name="export_fields[]" value="group_name" checked>
                                                <?php _e('Group', 'registration-codes'); ?>
                                            </label>
                                            <br>
                                            <label>
                                                <input type="checkbox" name="export_fields[]" value="is_used" checked>
                                                <?php _e('Status', 'registration-codes'); ?>
                                            </label>
                                            <br>
                                            <label>
                                                <input type="checkbox" name="export_fields[]" value="used_by">
                                                <?php _e('Used By (User ID)', 'registration-codes'); ?>
                                            </label>
                                            <br>
                                            <label>
                                                <input type="checkbox" name="export_fields[]" value="used_at">
                                                <?php _e('Used At', 'registration-codes'); ?>
                                            </label>
                                            <br>
                                            <label>
                                                <input type="checkbox" name="export_fields[]" value="created_at" checked>
                                                <?php _e('Created At', 'registration-codes'); ?>
                                            </label>
                                            <br>
                                            <label>
                                                <input type="checkbox" name="export_fields[]" value="created_by">
                                                <?php _e('Created By (User ID)', 'registration-codes'); ?>
                                            </label>
                                        </fieldset>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" name="export_codes" class="button button-primary">
                                <?php _e('Export Codes', 'registration-codes'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sample CSV Modal -->
<div id="sample-csv-modal" class="registration-codes-modal" style="display: none;">
    <div class="registration-codes-modal-content">
        <div class="registration-codes-modal-header">
            <h3><?php _e('Sample CSV Format', 'registration-codes'); ?></h3>
            <button type="button" class="registration-codes-modal-close">&times;</button>
        </div>
        <div class="registration-codes-modal-body">
            <p><?php _e('Your CSV file should look like this (one code per line):', 'registration-codes'); ?></p>
            <pre><code>CODE1,Group 1
CODE2,Group 2
CODE3,Group 1
CODE4,Another Group</code></pre>
            <p><?php _e('The first column is the registration code, and the optional second column is the group name.', 'registration-codes'); ?></p>
            <p>
                <a href="#" class="button" id="download-sample-csv-file">
                    <?php _e('Download Sample CSV', 'registration-codes'); ?>
                </a>
            </p>
        </div>
        <div class="registration-codes-modal-footer">
            <button type="button" class="button button-primary registration-codes-modal-close">
                <?php _e('Close', 'registration-codes'); ?>
            </button>
        </div>
    </div>
</div>
