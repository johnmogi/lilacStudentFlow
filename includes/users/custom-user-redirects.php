<?php
/**
 * Custom User Redirects and Admin
 * Handles login redirects and admin interface customization based on user roles
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Debug: Check if file is loaded
error_log('CUSTOM USER REDIRECTS LOADED - ' . date('Y-m-d H:i:s'));

// Add a custom admin page for teachers
add_action('admin_menu', 'add_teacher_dashboard_page', 1);
function add_teacher_dashboard_page() {
    add_menu_page(
        'Teacher Dashboard',
        'Teacher Dashboard',
        'school_teacher',
        'teacher-dashboard',
        'render_teacher_dashboard',
        'dashicons-welcome-learn-more',
        2
    );
}

// Render the teacher dashboard page
function render_teacher_dashboard() {
    if (!current_user_can('school_teacher')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    echo '<div class="wrap">';
    echo '<h1>Teacher Dashboard</h1>';
    echo '<div class="teacher-dashboard-content">';
    echo '<p>Welcome to your teacher dashboard. This area is under construction.</p>';
    echo '</div></div>';
    
    echo '<style>
        .teacher-dashboard-content {
            margin-top: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>';
}

// Handle login redirects
add_filter('login_redirect', 'custom_login_redirect', 10, 3);
function custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('school_teacher', $user->roles)) {
            return admin_url('admin.php?page=teacher-dashboard');
        } else {
            return get_author_posts_url($user->ID);
        }
    }
    return $redirect_to;
}

// Clean up admin for school teachers - run early
add_action('init', 'custom_admin_for_teachers', 1);
function custom_admin_for_teachers() {
    if (is_user_logged_in() && current_user_can('school_teacher')) {
        // Remove admin menu items
        add_action('admin_menu', 'remove_admin_menus', 9999);
        
        // Customize admin bar
        add_action('wp_before_admin_bar_render', 'customize_admin_bar', 9999);
        
        // Custom admin footer
        add_filter('admin_footer_text', 'custom_admin_footer');
        
        // Remove help tabs
        add_action('admin_head', 'remove_help_tabs', 9999);
    }
}

function remove_admin_menus() {
    global $menu, $submenu;
    
    // List of menu items to keep
    $allowed_menus = array(
        'index.php', // Dashboard
        'separator1', // First separator
        'teacher-dashboard' // Our custom dashboard
    );
    
    // First, remove all menus
    if (isset($menu)) {
        foreach ($menu as $menu_key => $menu_item) {
            if (!in_array($menu_item[2], $allowed_menus) && 
                strpos($menu_item[2], 'separator') === false) {
                remove_menu_page($menu_item[2]);
            }
        }
    }
    
    // Remove submenus from remaining items
    if (isset($submenu)) {
        foreach ($submenu as $parent => $items) {
            if ($parent !== 'teacher-dashboard') {
                foreach ($items as $key => $item) {
                    remove_submenu_page($parent, $item[2]);
                }
            }
        }
    }
    
    // Remove dashboard widgets
    add_action('wp_dashboard_setup', 'remove_dashboard_widgets', 9999);
}

function remove_dashboard_widgets() {
    global $wp_meta_boxes;
    
    // Remove all dashboard widgets
    if (isset($wp_meta_boxes['dashboard'])) {
        unset($wp_meta_boxes['dashboard']['normal']['core']);
        unset($wp_meta_boxes['dashboard']['side']['core']);
        unset($wp_meta_boxes['dashboard']['normal']['high']);
        unset($wp_meta_boxes['dashboard']['side']['low']);
    }
    
    // Add a custom welcome message
    wp_add_dashboard_widget(
        'welcome_teacher',
        'Welcome to Your Teacher Dashboard',
        'welcome_teacher_widget'
    );
}

function welcome_teacher_widget() {
    echo '<p>Welcome to your teacher dashboard. This is your central hub for managing your courses and students.</p>';
}

function remove_help_tabs() {
    $screen = get_current_screen();
    if ($screen) {
        $screen->remove_help_tabs();
    }
}

function customize_admin_bar() {
    global $wp_admin_bar;
    if ($wp_admin_bar) {
        $nodes_to_remove = array(
            'wp-logo', 'about', 'wporg', 'documentation', 
            'support-forums', 'feedback', 'site-name', 
            'comments', 'new-content', 'updates', 'search'
        );
        
        foreach ($nodes_to_remove as $node) {
            $wp_admin_bar->remove_node($node);
        }
        
        $wp_admin_bar->add_node(array(
            'id'    => 'dashboard',
            'title' => 'Dashboard',
            'href'  => admin_url('admin.php?page=teacher-dashboard')
        ));
        
        $wp_admin_bar->add_node(array(
            'id'    => 'profile',
            'title' => 'My Profile',
            'href'  => admin_url('profile.php')
        ));
    }
}

function custom_admin_footer() {
    return 'School Teacher Dashboard &copy; ' . date('Y') . ' - ' . get_bloginfo('name');
}

// Add custom user roles and capabilities
add_action('after_setup_theme', 'custom_user_roles_capabilities');
function custom_user_roles_capabilities() {
    if (!get_role('school_teacher')) {
        add_role('school_teacher', __('School Teacher'), array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => true,
        ));
    }
    
    $role = get_role('school_teacher');
    if ($role) {
        $capabilities = array(
            'edit_courses', 'edit_published_courses', 'publish_courses',
            'delete_published_courses', 'edit_others_courses', 'delete_others_courses'
        );
        
        foreach ($capabilities as $cap) {
            $role->add_cap($cap);
        }
    }
}

// Force refresh to ensure all changes take effect
if (is_admin() && current_user_can('school_teacher')) {
    add_action('admin_init', function() {
        global $pagenow;
        if ($pagenow === 'index.php' || $pagenow === 'profile.php') {
            wp_redirect(admin_url('admin.php?page=teacher-dashboard'));
            exit;
        }
    });
}
