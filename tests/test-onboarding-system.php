<?php
/**
 * First-Time User Onboarding System Tests
 * 
 * Tests the complete onboarding experience including modal interactions,
 * AJAX handlers, user journey tracking, and completion milestones.
 * 
 * @package CardCrafter
 * @subpackage Tests
 */

class CardCrafter_Onboarding_Test extends WP_UnitTestCase
{
    private $cardcrafter;
    private $user_id;

    public function setUp(): void
    {
        parent::setUp();
        
        // Initialize CardCrafter instance
        $this->cardcrafter = CardCrafter::get_instance();
        
        // Create test user
        $this->user_id = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        wp_set_current_user($this->user_id);
        
        // Clean slate for each test
        $this->clean_onboarding_options();
    }

    public function tearDown(): void
    {
        // Clean up after each test
        $this->clean_onboarding_options();
        parent::tearDown();
    }

    /**
     * Remove all onboarding-related options for clean testing
     */
    private function clean_onboarding_options()
    {
        delete_option('cc_show_activation_notice');
        delete_option('cc_do_activation_redirect');
        delete_option('cc_onboarding_step');
        delete_option('cc_user_completed_first_card');
        delete_option('cc_onboarding_start_time');
        delete_option('cc_preferred_demo_type');
        delete_option('cc_onboarding_completion_time');
        delete_option('cc_first_card_demo_type');
    }

    /**
     * Test plugin activation sets up onboarding correctly
     */
    public function test_activation_sets_onboarding_options()
    {
        CardCrafter::activate();
        
        $this->assertTrue(get_option('cc_show_activation_notice'), 'Should show activation notice');
        $this->assertTrue(get_option('cc_do_activation_redirect'), 'Should enable activation redirect');
        $this->assertEquals(0, get_option('cc_onboarding_step'), 'Should start at step 0');
        $this->assertFalse(get_option('cc_user_completed_first_card'), 'Should not be completed initially');
        $this->assertEquals('team', get_option('cc_preferred_demo_type'), 'Should default to team demo');
        
        $start_time = get_option('cc_onboarding_start_time');
        $this->assertNotEmpty($start_time, 'Should set onboarding start time');
        $this->assertTrue(is_numeric($start_time), 'Start time should be numeric timestamp');
    }

    /**
     * Test activation redirect logic
     */
    public function test_activation_redirect()
    {
        // Set redirect flag
        add_option('cc_do_activation_redirect', true);
        
        // Mock the redirect (we can't test actual redirects in unit tests)
        $redirect_called = false;
        add_filter('wp_redirect', function($location) use (&$redirect_called) {
            $redirect_called = true;
            $this->assertStringContains('admin.php?page=cardcrafter', $location);
            return false; // Prevent actual redirect
        });
        
        $this->cardcrafter->activation_redirect();
        
        $this->assertFalse(get_option('cc_do_activation_redirect'), 'Should remove redirect flag');
    }

    /**
     * Test onboarding modal display conditions
     */
    public function test_onboarding_modal_display_conditions()
    {
        // Test: Should not show when flag is false
        add_option('cc_show_activation_notice', false);
        $_GET['page'] = 'cardcrafter';
        
        ob_start();
        $this->cardcrafter->show_activation_notice();
        $output = ob_get_clean();
        
        $this->assertEmpty($output, 'Should not show modal when flag is false');
        
        // Test: Should show when conditions are met
        update_option('cc_show_activation_notice', true);
        
        ob_start();
        $this->cardcrafter->show_activation_notice();
        $output = ob_get_clean();
        
        $this->assertStringContains('cc-onboarding-overlay', $output, 'Should show modal overlay');
        $this->assertStringContains('Welcome to CardCrafter!', $output, 'Should show welcome message');
        $this->assertStringContains('cc-onboarding-step-1', $output, 'Should show step 1');
        $this->assertStringContains('cc-onboarding-step-2', $output, 'Should show step 2');
        $this->assertStringContains('cc-onboarding-step-3', $output, 'Should show step 3');
    }

    /**
     * Test onboarding step progression
     */
    public function test_onboarding_step_progression()
    {
        // Test step 0 (initial)
        add_option('cc_onboarding_step', 0);
        add_option('cc_show_activation_notice', true);
        $_GET['page'] = 'cardcrafter';
        
        ob_start();
        $this->cardcrafter->show_activation_notice();
        $output = ob_get_clean();
        
        $this->assertStringContains('var currentStep = 0', $output, 'Should initialize at step 0');
        
        // Test step 1
        update_option('cc_onboarding_step', 1);
        
        ob_start();
        $this->cardcrafter->show_activation_notice();
        $output = ob_get_clean();
        
        $this->assertStringContains('var currentStep = 1', $output, 'Should show step 1 progress');
    }

    /**
     * Test demo type selection persistence
     */
    public function test_demo_type_selection()
    {
        // Test default demo type
        add_option('cc_preferred_demo_type', 'products');
        add_option('cc_show_activation_notice', true);
        $_GET['page'] = 'cardcrafter';
        
        ob_start();
        $this->cardcrafter->show_activation_notice();
        $output = ob_get_clean();
        
        $this->assertStringContains("var selectedDemo = 'products'", $output, 'Should load saved demo preference');
        
        // Test demo selection logic
        $this->assertStringContains('cc-demo-option[data-demo="products"]', $output, 'Should pre-select saved demo');
    }

    /**
     * Test save onboarding progress AJAX handler
     */
    public function test_save_onboarding_progress_ajax()
    {
        // Test invalid nonce
        $_POST = array(
            'action' => 'cc_save_onboarding_progress',
            'nonce' => 'invalid_nonce',
            'step' => 2,
            'demo_type' => 'portfolio'
        );
        
        try {
            $this->cardcrafter->save_onboarding_progress();
        } catch (WPDieException $e) {
            $this->assertEquals('Security check failed', json_decode($e->getMessage(), true)['data']);
        }
        
        // Test valid request
        $nonce = wp_create_nonce('cc_onboarding_progress');
        $_POST = array(
            'action' => 'cc_save_onboarding_progress',
            'nonce' => $nonce,
            'step' => 2,
            'demo_type' => 'portfolio'
        );
        
        $this->expectException(WPDieException::class);
        $this->cardcrafter->save_onboarding_progress();
        
        // Verify options were saved
        $this->assertEquals(2, get_option('cc_onboarding_step'), 'Should save onboarding step');
        $this->assertEquals('portfolio', get_option('cc_preferred_demo_type'), 'Should save demo preference');
    }

    /**
     * Test complete first card AJAX handler
     */
    public function test_complete_first_card_ajax()
    {
        // Set initial onboarding start time
        $start_time = time() - 300; // 5 minutes ago
        update_option('cc_onboarding_start_time', $start_time);
        
        // Test valid completion
        $nonce = wp_create_nonce('cc_complete_first_card');
        $_POST = array(
            'action' => 'cc_complete_first_card',
            'nonce' => $nonce,
            'demo_type' => 'team'
        );
        
        $this->expectException(WPDieException::class);
        $this->cardcrafter->complete_first_card();
        
        // Verify completion was tracked
        $this->assertTrue(get_option('cc_user_completed_first_card'), 'Should mark completion');
        $this->assertEquals('team', get_option('cc_first_card_demo_type'), 'Should save demo type');
        
        $completion_time = get_option('cc_onboarding_completion_time');
        $this->assertNotEmpty($completion_time, 'Should track completion time');
        $this->assertTrue(is_numeric($completion_time), 'Completion time should be numeric');
    }

    /**
     * Test time to value calculation
     */
    public function test_time_to_value_calculation()
    {
        // Set start time 10 minutes ago
        $start_time = time() - 600;
        update_option('cc_onboarding_start_time', $start_time);
        
        $nonce = wp_create_nonce('cc_complete_first_card');
        $_POST = array(
            'action' => 'cc_complete_first_card',
            'nonce' => $nonce,
            'demo_type' => 'products'
        );
        
        // Capture JSON response
        $this->expectException(WPDieException::class);
        
        try {
            $this->cardcrafter->complete_first_card();
        } catch (WPDieException $e) {
            $response = json_decode($e->getMessage(), true);
            
            $this->assertTrue($response['success'], 'Should return success');
            $this->assertEquals('products', $response['data']['demo_type'], 'Should return demo type');
            $this->assertTrue($response['data']['completed'], 'Should confirm completion');
            
            // Time should be around 10 minutes (allowing for small variations)
            $time_to_value = $response['data']['time_to_value_minutes'];
            $this->assertGreaterThan(9, $time_to_value, 'Time to value should be around 10 minutes');
            $this->assertLessThan(11, $time_to_value, 'Time to value should be around 10 minutes');
        }
    }

    /**
     * Test activation notice dismissal
     */
    public function test_activation_notice_dismissal()
    {
        add_option('cc_show_activation_notice', true);
        
        $nonce = wp_create_nonce('cc_dismiss_notice');
        $_POST = array(
            'action' => 'cc_dismiss_activation_notice',
            'nonce' => $nonce
        );
        
        $this->expectException(WPDieException::class);
        $this->cardcrafter->dismiss_activation_notice();
        
        $this->assertFalse(get_option('cc_show_activation_notice'), 'Should remove activation notice flag');
    }

    /**
     * Test onboarding modal accessibility features
     */
    public function test_onboarding_accessibility_features()
    {
        add_option('cc_show_activation_notice', true);
        $_GET['page'] = 'cardcrafter';
        
        ob_start();
        $this->cardcrafter->show_activation_notice();
        $output = ob_get_clean();
        
        // Test ARIA attributes
        $this->assertStringContains('role="status"', $output, 'Should have status role');
        $this->assertStringContains('aria-live="polite"', $output, 'Should have aria-live for dynamic content');
        $this->assertStringContains('aria-hidden="true"', $output, 'Should hide decorative elements');
        
        // Test semantic HTML
        $this->assertStringContains('<h2>', $output, 'Should use proper heading structure');
        $this->assertStringContains('<button', $output, 'Should use button elements for interactions');
        
        // Test keyboard navigation
        $this->assertStringContains('class="button', $output, 'Should use WordPress button classes');
    }

    /**
     * Test onboarding modal responsiveness
     */
    public function test_onboarding_modal_responsiveness()
    {
        add_option('cc_show_activation_notice', true);
        $_GET['page'] = 'cardcrafter';
        
        ob_start();
        $this->cardcrafter->show_activation_notice();
        $output = ob_get_clean();
        
        // Test responsive CSS
        $this->assertStringContains('@media (max-width: 768px)', $output, 'Should have mobile breakpoint');
        $this->assertStringContains('width: 95%', $output, 'Should have mobile-friendly width');
        $this->assertStringContains('margin: 20px', $output, 'Should have mobile margins');
        
        // Test flexible layouts
        $this->assertStringContains('display: grid', $output, 'Should use grid layout');
        $this->assertStringContains('display: flex', $output, 'Should use flex layout');
    }

    /**
     * Test onboarding integration with CardCrafter admin interface
     */
    public function test_onboarding_admin_integration()
    {
        add_option('cc_show_activation_notice', true);
        $_GET['page'] = 'cardcrafter';
        
        ob_start();
        $this->cardcrafter->show_activation_notice();
        $output = ob_get_clean();
        
        // Test integration with admin interface elements
        $this->assertStringContains('#cc-source-url', $output, 'Should integrate with URL input');
        $this->assertStringContains('#cc-preview-btn', $output, 'Should integrate with preview button');
        $this->assertStringContains('demo-data/', $output, 'Should reference demo data paths');
        
        // Test shortcode generation
        $this->assertStringContains('[cardcrafter', $output, 'Should generate shortcodes');
        $this->assertStringContains('cc-generated-shortcode', $output, 'Should have shortcode display area');
    }

    /**
     * Test onboarding performance and efficiency
     */
    public function test_onboarding_performance()
    {
        add_option('cc_show_activation_notice', true);
        $_GET['page'] = 'cardcrafter';
        
        $start_time = microtime(true);
        
        ob_start();
        $this->cardcrafter->show_activation_notice();
        $output = ob_get_clean();
        
        $execution_time = (microtime(true) - $start_time) * 1000; // Convert to milliseconds
        
        $this->assertLessThan(50, $execution_time, 'Onboarding modal should render quickly (under 50ms)');
        $this->assertNotEmpty($output, 'Should generate output');
        $this->assertLessThan(100000, strlen($output), 'Output should be reasonable size (under 100KB)');
    }

    /**
     * Test complete user journey from activation to completion
     */
    public function test_complete_onboarding_journey()
    {
        // Step 1: Fresh activation
        CardCrafter::activate();
        
        $this->assertEquals(0, get_option('cc_onboarding_step'), 'Journey should start at step 0');
        $this->assertFalse(get_option('cc_user_completed_first_card'), 'Should not be completed initially');
        
        // Step 2: Progress through onboarding steps
        $nonce = wp_create_nonce('cc_onboarding_progress');
        
        // Step 1 -> 2
        $_POST = array('nonce' => $nonce, 'step' => 1);
        $this->expectException(WPDieException::class);
        try {
            $this->cardcrafter->save_onboarding_progress();
        } catch (WPDieException $e) {
            // Continue
        }
        $this->assertEquals(1, get_option('cc_onboarding_step'), 'Should progress to step 1');
        
        // Step 2 -> 3 with demo selection
        $_POST = array('nonce' => $nonce, 'step' => 2, 'demo_type' => 'portfolio');
        try {
            $this->cardcrafter->save_onboarding_progress();
        } catch (WPDieException $e) {
            // Continue
        }
        $this->assertEquals(2, get_option('cc_onboarding_step'), 'Should progress to step 2');
        $this->assertEquals('portfolio', get_option('cc_preferred_demo_type'), 'Should save demo choice');
        
        // Step 3: Complete first card
        $completion_nonce = wp_create_nonce('cc_complete_first_card');
        $_POST = array('nonce' => $completion_nonce, 'demo_type' => 'portfolio');
        
        try {
            $this->cardcrafter->complete_first_card();
        } catch (WPDieException $e) {
            // Continue
        }
        
        $this->assertTrue(get_option('cc_user_completed_first_card'), 'Should mark journey completion');
        $this->assertNotEmpty(get_option('cc_onboarding_completion_time'), 'Should track completion time');
        
        // Step 4: Dismiss onboarding
        $dismiss_nonce = wp_create_nonce('cc_dismiss_notice');
        $_POST = array('nonce' => $dismiss_nonce);
        
        try {
            $this->cardcrafter->dismiss_activation_notice();
        } catch (WPDieException $e) {
            // Continue
        }
        
        $this->assertFalse(get_option('cc_show_activation_notice'), 'Should complete onboarding dismissal');
        
        // Verify complete journey metrics
        $start_time = get_option('cc_onboarding_start_time');
        $completion_time = get_option('cc_onboarding_completion_time');
        
        $this->assertNotEmpty($start_time, 'Should have start time');
        $this->assertNotEmpty($completion_time, 'Should have completion time');
        $this->assertGreaterThan($start_time, $completion_time, 'Completion should be after start');
    }
}