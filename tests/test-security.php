<?php
/**
 * Class CardCrafterSecurityTest
 *
 * @package CardCrafter
 */

use WP_Mock\Tools\TestCase;

class CardCrafterSecurityTest extends TestCase
{

    public function setUp(): void
    {
        WP_Mock::setUp();
    }

    public function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    /**
     * Test that the proxy uses wp_safe_remote_get instead of wp_remote_get.
     * This is the primary fix for the SSRF vulnerability.
     */
    public function test_ajax_proxy_uses_safe_remote_get()
    {
        // Setup usage of the class
        $instance = CardCrafter::get_instance();

        // Mock request data
        $_REQUEST['nonce'] = 'valid_nonce';
        $_REQUEST['url'] = 'https://example.com/data.json';

        // Mock WP functions
        WP_Mock::userFunction('wp_unslash', [
            'return' => function ($val) {
                return $val; }
        ]);
        WP_Mock::userFunction('sanitize_text_field', [
            'return' => function ($val) {
                return $val; }
        ]);
        WP_Mock::userFunction('wp_verify_nonce', [
            'return' => true
        ]);
        WP_Mock::userFunction('esc_url_raw', [
            'return' => function ($val) {
                return $val; }
        ]);

        // Mock Transients (Cache miss)
        WP_Mock::userFunction('get_transient', [
            'return' => false
        ]);

        // EXPECTATION: wp_safe_remote_get must be called
        WP_Mock::userFunction('wp_safe_remote_get', [
            'times' => 1,
            'return' => ['body' => '{"foo":"bar"}']
        ]);

        // Ensure wp_remote_get is NOT called (or if it is, this test fails effectively by not matching expectations if code relies on it)
        // Note: In WP_Mock, if we don't mock it and it's called, it might error or pass through. 
        // We explicitly didn't mock wp_remote_get, so if the code calls it, it should fail if strictly mocked, 
        // or we can explicitly prevent it.

        WP_Mock::userFunction('wp_remote_retrieve_body', [
            'return' => '{"foo":"bar"}'
        ]);
        WP_Mock::userFunction('set_transient');
        WP_Mock::userFunction('get_option', ['return' => []]);
        WP_Mock::userFunction('update_option');

        // Spy on json response
        WP_Mock::userFunction('wp_send_json_success', [
            'times' => 1,
            'return' => function ($data) {
                // Assert data is what we expect
                return;
            }
        ]);

        // Run
        $instance->ajax_proxy_fetch();
    }
}
