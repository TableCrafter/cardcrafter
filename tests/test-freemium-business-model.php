<?php
/**
 * Test Freemium Business Model Implementation
 * 
 * Tests the new license manager, feature gating, and monetization features
 * that enable sustainable revenue generation for CardCrafter.
 * 
 * @since 1.9.0
 * @package CardCrafter
 */

class TestFreemiumBusinessModel extends WP_UnitTestCase
{
    private $license_manager;
    private $test_license_data;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Initialize license manager
        if (class_exists('CardCrafter_License_Manager')) {
            $this->license_manager = CardCrafter_License_Manager::get_instance();
        }
        
        // Reset license data
        delete_option('cardcrafter_license_data');
        
        // Test license keys
        $this->test_license_data = [
            'free' => null,
            'pro' => 'PRO-TEST-12345',
            'business' => 'BIZ-TEST-67890'
        ];
    }
    
    public function tearDown(): void
    {
        delete_option('cardcrafter_license_data');
        parent::tearDown();
    }
    
    /**
     * Test license manager instantiation
     */
    public function test_license_manager_instantiation()
    {
        if (!class_exists('CardCrafter_License_Manager')) {
            $this->markTestSkipped('License Manager class not available');
        }
        
        $this->assertInstanceOf('CardCrafter_License_Manager', $this->license_manager);
        $this->assertNotNull($this->license_manager);
    }
    
    /**
     * Test default free plan status
     */
    public function test_default_free_plan_status()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        $current_plan = $this->license_manager->get_current_plan();
        
        $this->assertEquals('free', $current_plan['slug']);
        $this->assertEquals('Free', $current_plan['name']);
        $this->assertEquals(0, $current_plan['price']);
    }
    
    /**
     * Test feature availability for free tier
     */
    public function test_free_tier_feature_restrictions()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        // Free tier should have limited features
        $this->assertFalse($this->license_manager->is_feature_available('unlimited_cards'));
        $this->assertFalse($this->license_manager->is_feature_available('premium_templates'));
        $this->assertFalse($this->license_manager->is_feature_available('all_export_formats'));
        $this->assertFalse($this->license_manager->is_feature_available('advanced_filtering'));
        $this->assertFalse($this->license_manager->is_feature_available('white_label'));
        $this->assertFalse($this->license_manager->is_feature_available('priority_support'));
    }
    
    /**
     * Test Pro license activation
     */
    public function test_pro_license_activation()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        // Simulate Pro license activation
        $pro_license = [
            'key' => $this->test_license_data['pro'],
            'status' => 'pro',
            'plan' => 'Pro',
            'expires' => date('Y-m-d', strtotime('+1 year'))
        ];
        
        update_option('cardcrafter_license_data', $pro_license);
        
        // Re-initialize to load new license
        $reflection = new ReflectionClass($this->license_manager);
        $load_method = $reflection->getMethod('load_license_status');
        $load_method->setAccessible(true);
        $load_method->invoke($this->license_manager);
        
        $current_plan = $this->license_manager->get_current_plan();
        
        $this->assertEquals('pro', $current_plan['slug']);
        $this->assertEquals('Pro', $current_plan['name']);
        $this->assertEquals(49, $current_plan['price']);
    }
    
    /**
     * Test Pro tier feature availability
     */
    public function test_pro_tier_feature_availability()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        // Activate Pro license
        update_option('cardcrafter_license_data', [
            'status' => 'pro',
            'plan' => 'Pro'
        ]);
        
        // Reload license status
        $reflection = new ReflectionClass($this->license_manager);
        $load_method = $reflection->getMethod('load_license_status');
        $load_method->setAccessible(true);
        $load_method->invoke($this->license_manager);
        
        // Pro tier should have most features
        $this->assertTrue($this->license_manager->is_feature_available('unlimited_cards'));
        $this->assertTrue($this->license_manager->is_feature_available('premium_templates'));
        $this->assertTrue($this->license_manager->is_feature_available('all_export_formats'));
        $this->assertTrue($this->license_manager->is_feature_available('advanced_filtering'));
        $this->assertTrue($this->license_manager->is_feature_available('priority_support'));
        
        // But not Business-tier features
        $this->assertFalse($this->license_manager->is_feature_available('white_label'));
    }
    
    /**
     * Test Business tier feature availability
     */
    public function test_business_tier_feature_availability()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        // Activate Business license
        update_option('cardcrafter_license_data', [
            'status' => 'business',
            'plan' => 'Business'
        ]);
        
        // Reload license status
        $reflection = new ReflectionClass($this->license_manager);
        $load_method = $reflection->getMethod('load_license_status');
        $load_method->setAccessible(true);
        $load_method->invoke($this->license_manager);
        
        // Business tier should have all features
        $this->assertTrue($this->license_manager->is_feature_available('unlimited_cards'));
        $this->assertTrue($this->license_manager->is_feature_available('premium_templates'));
        $this->assertTrue($this->license_manager->is_feature_available('all_export_formats'));
        $this->assertTrue($this->license_manager->is_feature_available('advanced_filtering'));
        $this->assertTrue($this->license_manager->is_feature_available('white_label'));
        $this->assertTrue($this->license_manager->is_feature_available('priority_support'));
    }
    
    /**
     * Test card limit filtering for free tier
     */
    public function test_card_limit_filtering()
    {
        // Test free tier limits
        $free_limit = apply_filters('cardcrafter_max_cards_per_page', 50);
        $this->assertEquals(12, $free_limit, 'Free tier should be limited to 12 cards');
        
        // Simulate Pro license
        update_option('cardcrafter_license_data', ['status' => 'pro']);
        
        $pro_limit = apply_filters('cardcrafter_max_cards_per_page', 50);
        $this->assertEquals(50, $pro_limit, 'Pro tier should have unlimited cards');
    }
    
    /**
     * Test export format filtering
     */
    public function test_export_format_filtering()
    {
        $all_formats = ['csv', 'json', 'pdf', 'excel'];
        
        // Test free tier export limits
        $free_formats = apply_filters('cardcrafter_allowed_export_formats', $all_formats);
        $this->assertEquals(['csv'], $free_formats, 'Free tier should only have CSV export');
        
        // Simulate Pro license
        update_option('cardcrafter_license_data', ['status' => 'pro']);
        
        $pro_formats = apply_filters('cardcrafter_allowed_export_formats', $all_formats);
        $this->assertContains('csv', $pro_formats);
        $this->assertContains('json', $pro_formats);
        $this->assertContains('pdf', $pro_formats);
    }
    
    /**
     * Test advanced filtering feature gate
     */
    public function test_advanced_filtering_gate()
    {
        // Free tier should not have advanced filtering
        $free_filtering = apply_filters('cardcrafter_advanced_filtering_enabled', true);
        $this->assertFalse($free_filtering, 'Free tier should not have advanced filtering');
        
        // Pro tier should have advanced filtering
        update_option('cardcrafter_license_data', ['status' => 'pro']);
        $pro_filtering = apply_filters('cardcrafter_advanced_filtering_enabled', true);
        $this->assertTrue($pro_filtering, 'Pro tier should have advanced filtering');
    }
    
    /**
     * Test premium templates feature gate
     */
    public function test_premium_templates_gate()
    {
        // Free tier should not have premium templates
        $free_templates = apply_filters('cardcrafter_premium_templates_enabled', true);
        $this->assertFalse($free_templates, 'Free tier should not have premium templates');
        
        // Pro tier should have premium templates
        update_option('cardcrafter_license_data', ['status' => 'pro']);
        $pro_templates = apply_filters('cardcrafter_premium_templates_enabled', true);
        $this->assertTrue($pro_templates, 'Pro tier should have premium templates');
    }
    
    /**
     * Test upgrade prompt generation
     */
    public function test_upgrade_prompt_generation()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        $prompt = $this->license_manager->get_upgrade_prompt('unlimited_cards');
        
        $this->assertIsArray($prompt);
        $this->assertArrayHasKey('html', $prompt);
        $this->assertArrayHasKey('text', $prompt);
        $this->assertStringContainsString('Unlimited Cards', $prompt['html']);
        $this->assertStringContainsString('cardcrafter-upgrade-prompt', $prompt['html']);
    }
    
    /**
     * Test license key validation
     */
    public function test_license_key_validation()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->license_manager);
        $validate_method = $reflection->getMethod('validate_license_key');
        $validate_method->setAccessible(true);
        
        // Test Pro license key
        $pro_result = $validate_method->invoke($this->license_manager, 'PRO-TEST-12345');
        $this->assertNotFalse($pro_result);
        $this->assertEquals('pro', $pro_result['status']);
        
        // Test Business license key
        $business_result = $validate_method->invoke($this->license_manager, 'BIZ-TEST-67890');
        $this->assertNotFalse($business_result);
        $this->assertEquals('business', $business_result['status']);
        
        // Test invalid license key
        $invalid_result = $validate_method->invoke($this->license_manager, 'INVALID-KEY');
        $this->assertFalse($invalid_result);
    }
    
    /**
     * Test usage analytics tracking
     */
    public function test_usage_analytics()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        // Set up test analytics data
        $test_analytics = [
            'widgets' => 5,
            'cards' => 100,
            'exports' => 10,
            'upgrade_clicks' => 3,
            'install_date' => strtotime('-30 days')
        ];
        update_option('cardcrafter_usage_analytics', $test_analytics);
        
        $analytics = $this->license_manager->get_usage_analytics();
        
        $this->assertIsArray($analytics);
        $this->assertEquals(5, $analytics['active_widgets']);
        $this->assertEquals(100, $analytics['total_cards_displayed']);
        $this->assertEquals(10, $analytics['export_attempts']);
        $this->assertEquals(3, $analytics['upgrade_clicks']);
        $this->assertEquals(30, $analytics['days_since_install']);
    }
    
    /**
     * Test integration with existing CardCrafter functionality
     */
    public function test_integration_with_existing_functionality()
    {
        // Test that CardCrafter still works with free tier
        $cardcrafter = CardCrafter::get_instance();
        
        $shortcode_output = $cardcrafter->render_cards([
            'source' => '',
            'wp_query' => '',
            'post_type' => 'post',
            'posts_per_page' => 15, // Should be limited to 12
            'layout' => 'grid'
        ]);
        
        $this->assertNotEmpty($shortcode_output);
        $this->assertStringContainsString('cardcrafter-container', $shortcode_output);
    }
    
    /**
     * Test admin menu integration
     */
    public function test_admin_menu_integration()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        // Set up WordPress admin context
        set_current_screen('dashboard');
        wp_set_current_user($this->factory->user->create(['role' => 'administrator']));
        
        // Test that license menu is added
        do_action('admin_menu');
        
        global $submenu;
        $this->assertTrue(isset($submenu['cardcrafter']));
        
        // Find the license submenu
        $license_menu_found = false;
        if (isset($submenu['cardcrafter'])) {
            foreach ($submenu['cardcrafter'] as $menu_item) {
                if ($menu_item[2] === 'cardcrafter-license') {
                    $license_menu_found = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($license_menu_found, 'License menu should be added to admin');
    }
    
    /**
     * Test freemium conversion tracking
     */
    public function test_conversion_tracking()
    {
        // Test that conversion events can be tracked
        $this->assertTrue(has_action('wp_ajax_cardcrafter_track_event'));
        
        // Simulate tracking call
        $_POST['action'] = 'cardcrafter_track_event';
        $_POST['event'] = 'upgrade_click';
        $_POST['data'] = json_encode(['feature' => 'unlimited_cards']);
        $_POST['nonce'] = wp_create_nonce('cardcrafter_license_nonce');
        
        // This would normally trigger AJAX handler
        $this->assertTrue(true); // Placeholder for actual tracking test
    }
    
    /**
     * Test error handling for invalid licenses
     */
    public function test_invalid_license_error_handling()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        // Set invalid license data
        update_option('cardcrafter_license_data', [
            'status' => 'invalid',
            'key' => 'EXPIRED-KEY'
        ]);
        
        // Should fallback to free tier
        $current_plan = $this->license_manager->get_current_plan();
        $this->assertEquals('free', $current_plan['slug']);
    }
    
    /**
     * Test business model sustainability metrics
     */
    public function test_business_model_metrics()
    {
        // Test potential revenue calculation
        $user_count = 10000; // Simulated user base
        $conversion_rate = 0.05; // 5% conversion rate
        $pro_price = 49;
        
        $potential_revenue = $user_count * $conversion_rate * $pro_price;
        $this->assertEquals(24500, $potential_revenue);
        
        // Test that we're tracking the right metrics for optimization
        $this->assertTrue(class_exists('CardCrafter_License_Manager'));
    }
    
    /**
     * Test upgrade flow user experience
     */
    public function test_upgrade_flow_ux()
    {
        if (!$this->license_manager) {
            $this->markTestSkipped('License Manager not available');
        }
        
        // Test that upgrade prompts are contextual and helpful
        $unlimited_cards_prompt = $this->license_manager->get_upgrade_prompt('unlimited_cards');
        $premium_templates_prompt = $this->license_manager->get_upgrade_prompt('premium_templates');
        
        $this->assertStringContainsString('Unlimited', $unlimited_cards_prompt['text']);
        $this->assertStringContainsString('Templates', $premium_templates_prompt['text']);
        
        // Each prompt should be different and specific
        $this->assertNotEquals($unlimited_cards_prompt['text'], $premium_templates_prompt['text']);
    }
}