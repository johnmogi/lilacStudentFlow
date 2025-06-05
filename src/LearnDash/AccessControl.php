<?php
/**
 * LearnDash Access Control
 *
 * Automates enrollment for students and assigns group leadership for teachers.
 * Uses LearnDash built-in content protection for courses.
 *
 * @package Hello_Child_Theme
 * @subpackage LearnDash
 */

namespace Lilac\LearnDash;

if (!defined('ABSPATH')) {
    exit;
}

class AccessControl {
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor: setup hooks
     */
    private function __construct() {
        // Auto-enroll on login
        add_action('wp_login', array($this, 'auto_enroll_on_login'), 10, 2);
        // Restrict direct course access
        add_action('template_redirect', array($this, 'restrict_course_access'));
    }

    /**
     * Auto-enroll students and assign teachers on login
     */
    public function auto_enroll_on_login($user_login, $user) {
        $roles = (array) $user->roles;
        // Students
        if ((in_array('school_student', $roles) || in_array('student_private', $roles)) && function_exists('ld_update_course_access')) {
            $course_ids = get_option('lilac_default_student_courses', array());
            foreach ($course_ids as $course_id) {
                ld_update_course_access($user->ID, $course_id, true);
            }
        }
        // Teachers
        if (in_array('school_teacher', $roles) && function_exists('learndash_set_group_leader')) {
            $group_ids = get_option('lilac_default_teacher_groups', array());
            foreach ($group_ids as $group_id) {
                learndash_set_group_leader($user->ID, $group_id);
            }
        }
    }

    /**
     * Redirect non-enrolled users away from course pages
     */
    public function restrict_course_access() {
        if (is_singular('sfwd-courses')) {
            global $post;
            $user = wp_get_current_user();
            if (function_exists('ld_has_access') && !ld_has_access($user->ID, $post->ID)) {
                wp_redirect(home_url('/login'));
                exit;
            }
        }
    }
}

// Initialize AccessControl
function lilac_learndash_accesscontrol() {
    return AccessControl::get_instance();
}
lilac_learndash_accesscontrol();
