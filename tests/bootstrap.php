<?php
/**
 * PHPUnit Bootstrap
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Initialize WP_Mock
WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

// Define test environment constant
define('WP_INT_TEST', true);

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

// Mock essential WP functions
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file)
    {
        return 'http://example.com/wp-content/plugins/cardcrafter-data-grids/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file)
    {
        return __DIR__ . '/../';
    }
}

// Mock WP Core functions
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
    {
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback)
    {
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = [])
    {
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = [])
    {
        return false;
    }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null)
    {
    }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url)
    {
        return parse_url($url);
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing)
    {
        return false;
    }
}

// Load the plugin file
require_once plugin_dir_path(__FILE__) . 'cardcrafter.php';
