<?php
/**
 * Database Performance Optimization Verification Script
 * 
 * This script verifies that all the new performance optimization methods
 * are properly implemented and callable.
 * 
 * @package CardCrafter
 * @subpackage Testing
 */

// Mock WordPress constants and functions for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!defined('CARDCRAFTER_VERSION')) {
    define('CARDCRAFTER_VERSION', '1.13.1');
}

if (!defined('CARDCRAFTER_URL')) {
    define('CARDCRAFTER_URL', 'http://example.com/wp-content/plugins/cardcrafter-data-grids/');
}

if (!defined('CARDCRAFTER_PATH')) {
    define('CARDCRAFTER_PATH', __DIR__ . '/');
}

// Mock WordPress functions
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) { return CARDCRAFTER_URL; }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return CARDCRAFTER_PATH; }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { return true; }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) { return true; }
}

if (!function_exists('shortcode_atts')) {
    function shortcode_atts($defaults, $atts, $shortcode = '') { 
        return array_merge($defaults, (array)$atts);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) { return filter_var($url, FILTER_SANITIZE_URL); }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return strip_tags($str); }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) { return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', $key)); }
}

if (!function_exists('absint')) {
    function absint($maybeint) { return abs(intval($maybeint)); }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) { return $value; }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) { return md5($action . time()); }
}

if (!function_exists('get_transient')) {
    function get_transient($transient) { return false; }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration) { return true; }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) { return true; }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) { return true; }
}

if (!function_exists('get_current_blog_id')) {
    function get_current_blog_id() { return 1; }
}

if (!function_exists('get_locale')) {
    function get_locale() { return 'en_US'; }
}

if (!function_exists('wp_list_pluck')) {
    function wp_list_pluck($list, $field) { 
        $result = array();
        foreach ($list as $item) {
            if (is_object($item) && isset($item->$field)) {
                $result[] = $item->$field;
            } elseif (is_array($item) && isset($item[$field])) {
                $result[] = $item[$field];
            }
        }
        return $result;
    }
}

if (!function_exists('get_users')) {
    function get_users($args) { 
        return array(
            (object) array('ID' => 1, 'display_name' => 'Test User 1'),
            (object) array('ID' => 2, 'display_name' => 'Test User 2')
        );
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words($text, $num_words = 55, $more = '...') {
        $words = explode(' ', $text);
        if (count($words) > $num_words) {
            return implode(' ', array_slice($words, 0, $num_words)) . $more;
        }
        return $text;
    }
}

if (!function_exists('strip_shortcodes')) {
    function strip_shortcodes($content) {
        return preg_replace('/\[[^\]]+\]/', '', $content);
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string) {
        return strip_tags($string);
    }
}

if (!function_exists('has_action')) {
    function has_action($tag, $function_to_check = false) { return false; }
}

if (!function_exists('wp_is_post_revision')) {
    function wp_is_post_revision($post) { return false; }
}

if (!function_exists('wp_is_post_autosave')) {
    function wp_is_post_autosave($post) { return false; }
}

if (!function_exists('get_post')) {
    function get_post($post_id) {
        return (object) array(
            'ID' => $post_id,
            'post_type' => 'post',
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_excerpt' => 'Test excerpt'
        );
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) { return true; }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = array()) { return true; }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = array()) { return false; }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) { return true; }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value) { return $value; }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') { return 'http://example.com/wp-admin/' . $path; }
}

if (!function_exists('wp_safe_remote_get')) {
    function wp_safe_remote_get($url, $args = array()) { return array('body' => '{}'); }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) { return $response['body']; }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) { return false; }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) { return true; }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) { return true; }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) { return true; }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() { return 1; }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = array()) { return array('body' => '{}'); }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) { return true; }
}

if (!function_exists('get_site_url')) {
    function get_site_url() { return 'http://example.com'; }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) { return date('Y-m-d H:i:s'); }
}

if (!function_exists('did_action')) {
    function did_action($tag) { return false; }
}

// Override constants definition check
if (!defined('CARDCRAFTER_VERSION')) {
    define('CARDCRAFTER_VERSION', '1.13.1');
}
if (!defined('CARDCRAFTER_URL')) {
    define('CARDCRAFTER_URL', 'http://example.com/wp-content/plugins/cardcrafter-data-grids/');
}
if (!defined('CARDCRAFTER_PATH')) {
    define('CARDCRAFTER_PATH', __DIR__ . '/');
}

// Mock global $wpdb
global $wpdb;
if (!isset($wpdb)) {
    $wpdb = new stdClass();
    $wpdb->options = 'wp_options';
    $wpdb->query = function() { return true; };
    $wpdb->prepare = function($query, ...$args) { return $query; };
}

// Include the main plugin file
require_once __DIR__ . '/cardcrafter.php';

/**
 * Test runner class
 */
class DatabasePerformanceVerification
{
    private $cardcrafter;
    private $tests_passed = 0;
    private $tests_failed = 0;

    public function __construct()
    {
        $this->cardcrafter = CardCrafter::get_instance();
    }

    public function run_tests()
    {
        echo "🔍 Database Performance Optimization Verification\n";
        echo "================================================\n\n";

        $this->test_method_exists('generate_wp_query_cache_key');
        $this->test_method_exists('is_debug_mode');
        $this->test_method_exists('batch_load_featured_images');
        $this->test_method_exists('batch_load_authors_data');
        $this->test_method_exists('get_optimized_excerpt');
        $this->test_method_exists('get_cache_duration');
        $this->test_method_exists('register_cache_invalidation_hooks');
        $this->test_method_exists('invalidate_post_cache');
        $this->test_method_exists('cleanup_expired_caches');
        
        $this->test_cache_key_generation();
        $this->test_cache_duration_logic();
        $this->test_excerpt_optimization();
        $this->test_debug_mode_detection();

        echo "\n📊 Test Results:\n";
        echo "================\n";
        echo "✅ Passed: {$this->tests_passed}\n";
        echo "❌ Failed: {$this->tests_failed}\n";
        
        if ($this->tests_failed === 0) {
            echo "\n🎉 All tests passed! Database performance optimization is working correctly.\n";
            return true;
        } else {
            echo "\n⚠️  Some tests failed. Please review the implementation.\n";
            return false;
        }
    }

    private function test_method_exists($method_name)
    {
        $reflection = new ReflectionClass($this->cardcrafter);
        
        if ($reflection->hasMethod($method_name)) {
            $this->pass("Method {$method_name} exists");
        } else {
            $this->fail("Method {$method_name} is missing");
        }
    }

    private function test_cache_key_generation()
    {
        try {
            $reflection = new ReflectionClass($this->cardcrafter);
            $method = $reflection->getMethod('generate_wp_query_cache_key');
            $method->setAccessible(true);

            $atts1 = array('post_type' => 'post', 'posts_per_page' => 10);
            $atts2 = array('post_type' => 'post', 'posts_per_page' => 20);

            $key1 = $method->invoke($this->cardcrafter, $atts1);
            $key2 = $method->invoke($this->cardcrafter, $atts1);
            $key3 = $method->invoke($this->cardcrafter, $atts2);

            if ($key1 === $key2) {
                $this->pass('Cache key generation is consistent');
            } else {
                $this->fail('Cache key generation is inconsistent');
            }

            if ($key1 !== $key3) {
                $this->pass('Different attributes generate different cache keys');
            } else {
                $this->fail('Different attributes generate same cache keys');
            }

        } catch (Exception $e) {
            $this->fail("Cache key generation test failed: " . $e->getMessage());
        }
    }

    private function test_cache_duration_logic()
    {
        try {
            $reflection = new ReflectionClass($this->cardcrafter);
            $method = $reflection->getMethod('get_cache_duration');
            $method->setAccessible(true);

            $post_duration = $method->invoke($this->cardcrafter, 'post');
            $page_duration = $method->invoke($this->cardcrafter, 'page');
            $custom_duration = $method->invoke($this->cardcrafter, 'custom_type');

            if ($post_duration === 15 * MINUTE_IN_SECONDS) {
                $this->pass('Post cache duration is correct (15 minutes)');
            } else {
                $this->fail('Post cache duration is incorrect');
            }

            if ($page_duration === 2 * HOUR_IN_SECONDS) {
                $this->pass('Page cache duration is correct (2 hours)');
            } else {
                $this->fail('Page cache duration is incorrect');
            }

            if ($custom_duration === HOUR_IN_SECONDS) {
                $this->pass('Custom post type uses default cache duration');
            } else {
                $this->fail('Custom post type cache duration is incorrect');
            }

        } catch (Exception $e) {
            $this->fail("Cache duration test failed: " . $e->getMessage());
        }
    }

    private function test_excerpt_optimization()
    {
        try {
            $reflection = new ReflectionClass($this->cardcrafter);
            $method = $reflection->getMethod('get_optimized_excerpt');
            $method->setAccessible(true);

            $post_with_excerpt = (object) array(
                'post_excerpt' => 'This is a test excerpt',
                'post_content' => 'This is the post content'
            );

            $post_without_excerpt = (object) array(
                'post_excerpt' => '',
                'post_content' => 'This is the post content that should be used for excerpt generation'
            );

            $excerpt1 = $method->invoke($this->cardcrafter, $post_with_excerpt);
            $excerpt2 = $method->invoke($this->cardcrafter, $post_without_excerpt);

            if (strpos($excerpt1, 'test excerpt') !== false) {
                $this->pass('Uses post excerpt when available');
            } else {
                $this->fail('Does not use post excerpt correctly');
            }

            if (strpos($excerpt2, 'post content') !== false) {
                $this->pass('Generates excerpt from content when excerpt is empty');
            } else {
                $this->fail('Does not generate excerpt from content');
            }

        } catch (Exception $e) {
            $this->fail("Excerpt optimization test failed: " . $e->getMessage());
        }
    }

    private function test_debug_mode_detection()
    {
        try {
            $reflection = new ReflectionClass($this->cardcrafter);
            $method = $reflection->getMethod('is_debug_mode');
            $method->setAccessible(true);

            $result = $method->invoke($this->cardcrafter);
            
            // Since WP_DEBUG is not defined in our test, it should return false
            if ($result === false) {
                $this->pass('Debug mode detection works correctly');
            } else {
                $this->fail('Debug mode detection is incorrect');
            }

        } catch (Exception $e) {
            $this->fail("Debug mode test failed: " . $e->getMessage());
        }
    }

    private function pass($message)
    {
        echo "✅ {$message}\n";
        $this->tests_passed++;
    }

    private function fail($message)
    {
        echo "❌ {$message}\n";
        $this->tests_failed++;
    }
}

// Run the verification
$verification = new DatabasePerformanceVerification();
$success = $verification->run_tests();

exit($success ? 0 : 1);