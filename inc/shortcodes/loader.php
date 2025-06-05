<?php
/**
 * Shortcodes Loader
 *
 * Loads all shortcode modules for the theme.
 *
 * @package Hello_Child_Theme
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the login page shortcode
require_once __DIR__ . '/login-page-shortcode.php';

// Load the minimal login shortcode
require_once __DIR__ . '/minimal-login-shortcode.php';



// Add more shortcodes below as needed
// require_once get_stylesheet_directory() . '/inc/shortcodes/another-shortcode.php';
