<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get filter parameters
$group_filter = isset($_GET['group']) ? sanitize_text_field($_GET['group']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Get pagination parameters
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Get codes with filters
$codes = $registration_codes->get_codes($group_filter, $status_filter, $per_page, $offset);
$total_items = $registration_codes->count_codes($group_filter, $status_filter);
$total_pages = ceil($total_items / $per_page);

// Get all groups for filter dropdown
$groups = $registration_codes->get_groups();
?>

<div class="registration-codes-filters">
    <form method="get" action="">
        <input type="hidden" name="page" value="registration-codes" />
        <input type="hidden" name="tab" value="manage" />
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <label for="filter-group" class="screen-reader-text"><?php _e('Filter by group', 'registration-codes'); ?></label>
                <select name="group" id="filter-group">
                    <option value=""><?php _e('All Groups', 'registration-codes'); ?></option>
                    <?php foreach ($groups as $group) : ?>
                        <option value="<?php echo esc_attr($group); ?>" <?php selected($group_filter, $group); ?>>
                            <?php echo esc_html($group); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label for="filter-status" class="screen-reader-text"><?php _e('Filter by status', 'registration-codes'); ?></label>
                <select name="status" id="filter-status">
                    <option value=""><?php _e('All Statuses', 'registration-codes'); ?></option>
                    <option value="active" <?php selected($status_filter, 'active'); ?>><?php _e('Active', 'registration-codes'); ?></option>
                    <option value="used" <?php selected($status_filter, 'used'); ?>><?php _e('Used', 'registration-codes'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'registration-codes'); ?>">
                
                <?php if (!empty($group_filter) || !empty($status_filter)) : ?>
                    <a href="?page=registration-codes&tab=manage" class="button">
                        <?php _e('Reset', 'registration-codes'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_items, 'registration-codes'), number_format_i18n($total_items)); ?>
                </span>
                
                <?php if ($total_pages > 1) : ?>
                    <span class="pagination-links">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page,
                        ));
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </form>
    
    <form method="post" action="" id="codes-form">
        <?php wp_nonce_field('registration_codes_action', 'registration_codes_nonce'); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all" />
                    </td>
                    <th scope="col" class="manage-column column-code column-primary">
                        <?php _e('Code', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-group">
                        <?php _e('Group', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-role">
                        <?php _e('Role', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <?php _e('Status', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-created">
                        <?php _e('Created', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-used">
                        <?php _e('Used', 'registration-codes'); ?>
                    </th>
                </tr>
            </thead>
            
            <tbody id="the-list">
                <?php if (!empty($codes)) : ?>
                    <?php foreach ($codes as $code) : 
                        // Ensure properties exist before accessing them
                        $used_by = isset($code->used_by) ? $code->used_by : 0;
                        $is_used = isset($code->is_used) ? (bool)$code->is_used : false;
                        $user = $used_by ? get_user_by('id', $used_by) : null;
                        $status_class = $is_used ? 'status-used' : 'status-active';
                        $status_text = $is_used ? __('Used', 'registration-codes') : __('Active', 'registration-codes');
                    ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="codes[]" value="<?php echo esc_attr($code->id); ?>" />
                            </th>
                            <td class="code column-code column-primary" data-colname="<?php esc_attr_e('Code', 'registration-codes'); ?>">
                                <strong><?php echo esc_html($code->code); ?></strong>
                                <div class="row-actions">
                                    <span class="copy">
                                        <a href="#" class="copy-code" data-code="<?php echo esc_attr($code->code); ?>">
                                            <?php _e('Copy', 'registration-codes'); ?>
                                        </a> | 
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="delete-code" data-id="<?php echo esc_attr($code->id); ?>">
                                            <?php _e('Delete', 'registration-codes'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="group column-group" data-colname="<?php esc_attr_e('Group', 'registration-codes'); ?>">
                                <?php echo $code->group_name ? esc_html($code->group_name) : '—'; ?>
                            </td>
                            <td class="role column-role" data-colname="<?php esc_attr_e('Role', 'registration-codes'); ?>">
                                <?php 
                                $roles = wp_roles();
                                echo isset($roles->role_names[$code->role]) ? esc_html($roles->role_names[$code->role]) : esc_html($code->role);
                                ?>
                            </td>
                            <td class="status column-status" data-colname="<?php esc_attr_e('Status', 'registration-codes'); ?>">
                                <span class="status-indicator <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_text); ?>
                                </span>
                            </td>
                            <td class="created column-created" data-colname="<?php esc_attr_e('Created', 'registration-codes'); ?>">
                                <?php 
                                $created_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($code->created_at));
                                echo esc_html($created_date);
                                ?>
                                <br>
                                <small>
                                    <?php 
                                    $creator = get_user_by('id', $code->created_by);
                                    echo $creator ? esc_html($creator->display_name) : __('System', 'registration-codes');
                                    ?>
                                </small>
                            </td>
                            <td class="used column-used" data-colname="<?php esc_attr_e('Used', 'registration-codes'); ?>">
                                <?php 
                                $used_at = isset($code->used_at) ? $code->used_at : '';
                                $used_by = isset($code->used_by) ? $code->used_by : 0;
                                $is_used = isset($code->is_used) ? (bool)$code->is_used : false;
                                
                                if ($is_used && !empty($used_at)) : 
                                    $used_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($used_at));
                                    echo esc_html($used_date);
                                    
                                    if ($used_by && ($user = get_user_by('id', $used_by))) : ?>
                                        <br><small>
                                            <?php 
                                            echo esc_html($user->display_name);
                                            if (!empty($user->user_email)) {
                                                echo ' (' . esc_html($user->user_email) . ')';
                                            }
                                            ?>
                                        </small>
                                    <?php endif;
                                else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7">
                            <?php _e('No registration codes found.', 'registration-codes'); ?>
                            <a href="?page=registration-codes&tab=generate">
                                <?php _e('Generate some codes', 'registration-codes'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-2" />
                    </td>
                    <th scope="col" class="manage-column column-code column-primary">
                        <?php _e('Code', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-group">
                        <?php _e('Group', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-role">
                        <?php _e('Role', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <?php _e('Status', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-created">
                        <?php _e('Created', 'registration-codes'); ?>
                    </th>
                    <th scope="col" class="manage-column column-used">
                        <?php _e('Used', 'registration-codes'); ?>
                    </th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="action" id="bulk-action-selector-bottom">
                    <option value="-1"><?php _e('Bulk Actions', 'registration-codes'); ?></option>
                    <option value="delete"><?php _e('Delete', 'registration-codes'); ?></option>
                    <option value="export"><?php _e('Export', 'registration-codes'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'registration-codes'); ?>">
            </div>
            
            <?php if ($total_pages > 1) : ?>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(_n('%s item', '%s items', $total_items, 'registration-codes'), number_format_i18n($total_items)); ?>
                    </span>
                    <span class="pagination-links">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page,
                        ));
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Confirmation modal for delete -->
<div id="delete-confirm-modal" class="registration-codes-modal" style="display: none;">
    <div class="registration-codes-modal-content">
        <div class="registration-codes-modal-header">
            <h3><?php _e('Confirm Deletion', 'registration-codes'); ?></h3>
            <button type="button" class="registration-codes-modal-close">&times;</button>
        </div>
        <div class="registration-codes-modal-body">
            <p><?php _e('Are you sure you want to delete the selected codes? This action cannot be undone.', 'registration-codes'); ?></p>
        </div>
        <div class="registration-codes-modal-footer">
            <button type="button" class="button registration-codes-modal-cancel">
                <?php _e('Cancel', 'registration-codes'); ?>
            </button>
            <button type="button" class="button button-primary registration-codes-modal-confirm" data-action="delete">
                <?php _e('Delete', 'registration-codes'); ?>
            </button>
        </div>
    </div>
</div>
