<?php
/**
 * Plugin Name: CardCrafter – Data-Driven Card Grids
 * Plugin URI: https://github.com/TableCrafter/cardcrafter-data-grids
 * Description: Transform JSON data and WordPress posts into beautiful card grids. Perfect for teams, products, portfolios, and blogs.
 * Version: 1.14.0
 * Author: fahdi
 * Author URI: https://github.com/TableCrafter
 * License: GPLv2 or later
 * Text Domain: cardcrafter-data-grids
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
Note: Plugin name and slug updated to CardCrafter – Data-Driven Card Grids / cardcrafter-data-grids. 
All functional code remains unchanged. These changes are recommended by an AI and do not replace WordPress.org volunteer review guidance.
*/

define('CARDCRAFTER_VERSION', '1.14.0');
define('CARDCRAFTER_URL', plugin_dir_url(__FILE__));
define('CARDCRAFTER_PATH', plugin_dir_path(__FILE__));

class CardCrafter
{

    private static $instance = null;

    /**
     * Get singleton instance.
     * 
     * @return CardCrafter
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        add_shortcode('cardcrafter-data-grids', array($this, 'render_cards'));
        add_shortcode('cardcrafter', array($this, 'render_cards'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Activation Notice & Redirect
        add_action('admin_init', array($this, 'activation_redirect'));
        add_action('admin_notices', array($this, 'show_activation_notice'));
        add_action('wp_ajax_cc_dismiss_activation_notice', array($this, 'dismiss_activation_notice'));
        
        // Gutenberg Block Support
        add_action('init', array($this, 'register_block'));

        // Secure Proxy Handlers
        add_action('wp_ajax_cardcrafter_proxy_fetch', array($this, 'ajax_proxy_fetch'));
        add_action('wp_ajax_nopriv_cardcrafter_proxy_fetch', array($this, 'ajax_proxy_fetch'));
        
        // WordPress Posts Preview Handler
        add_action('wp_ajax_cardcrafter_wp_posts_preview', array($this, 'ajax_wp_posts_preview'));

        // Background Caching
        add_action('cardcrafter_refresher_cron', array($this, 'automated_cache_refresh'));
        if (!wp_next_scheduled('cardcrafter_refresher_cron')) {
            wp_schedule_event(time(), 'hourly', 'cardcrafter_refresher_cron');
        }

        // Lead Magnet Handler
        add_action('wp_ajax_cc_subscribe_lead', array($this, 'handle_lead_subscription'));
        add_action('wp_ajax_nopriv_cc_subscribe_lead', array($this, 'handle_lead_subscription'));

        // Onboarding Progress Handlers
        add_action('wp_ajax_cc_save_onboarding_progress', array($this, 'save_onboarding_progress'));
        add_action('wp_ajax_cc_complete_first_card', array($this, 'complete_first_card'));

        // Elementor Integration
        add_action('plugins_loaded', array($this, 'init_elementor_integration'));
        
        // License Manager Integration
        add_action('plugins_loaded', array($this, 'init_license_manager'));
    }

    /**
     * Plugin activation hook.
     */
    public static function activate()
    {
        // Enhanced onboarding system
        add_option('cc_show_activation_notice', true);
        add_option('cc_do_activation_redirect', true);
        add_option('cc_onboarding_step', 0);  // Track onboarding progress
        add_option('cc_user_completed_first_card', false);  // Track success milestone
        add_option('cc_onboarding_start_time', current_time('timestamp'));  // Track time to value
        
        // Set default demo preference for new users
        add_option('cc_preferred_demo_type', 'team');  // Default to team directory demo
    }

    /**
     * Redirect to CardCrafter admin page on activation.
     */
    public function activation_redirect()
    {
        if (!get_option('cc_do_activation_redirect', false)) {
            return;
        }
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (is_network_admin()) {
            return;
        }
        delete_option('cc_do_activation_redirect');
        wp_safe_redirect(admin_url('admin.php?page=cardcrafter'));
        exit;
    }

    /**
     * Show interactive onboarding modal on first activation
     */
    public function show_activation_notice()
    {
        if (!get_option('cc_show_activation_notice', false)) {
            return;
        }
        
        // Only show on CardCrafter admin page
        if (!isset($_GET['page']) || $_GET['page'] !== 'cardcrafter') {
            return;
        }
        
        $onboarding_step = get_option('cc_onboarding_step', 0);
        $has_completed_first_card = get_option('cc_user_completed_first_card', false);
        
        ?>
        <!-- Enhanced Onboarding Modal -->
        <div id="cc-onboarding-overlay" class="cc-onboarding-overlay" style="display: none;">
            <div class="cc-onboarding-modal">
                
                <!-- Step 1: Welcome -->
                <div id="cc-onboarding-step-1" class="cc-onboarding-step" data-step="1">
                    <div class="cc-onboarding-header">
                        <div class="cc-onboarding-icon">🎉</div>
                        <h2>Welcome to CardCrafter!</h2>
                        <p>Let's get you set up with your first beautiful card display in under 2 minutes.</p>
                    </div>
                    <div class="cc-onboarding-content">
                        <div class="cc-value-props">
                            <div class="cc-value-prop">
                                <span class="cc-prop-icon">⚡</span>
                                <span>Transform any data into beautiful cards</span>
                            </div>
                            <div class="cc-value-prop">
                                <span class="cc-prop-icon">📱</span>
                                <span>Fully responsive on all devices</span>
                            </div>
                            <div class="cc-value-prop">
                                <span class="cc-prop-icon">🔍</span>
                                <span>Built-in search and filtering</span>
                            </div>
                        </div>
                        <div class="cc-onboarding-stats">
                            <small>Join 10,000+ websites using CardCrafter</small>
                        </div>
                    </div>
                    <div class="cc-onboarding-actions">
                        <button class="button button-primary button-large cc-onboarding-next">
                            Get Started →
                        </button>
                        <button class="button button-link cc-onboarding-skip">Skip Tutorial</button>
                    </div>
                </div>
                
                <!-- Step 2: Quick Start Options -->
                <div id="cc-onboarding-step-2" class="cc-onboarding-step" data-step="2" style="display: none;">
                    <div class="cc-onboarding-header">
                        <div class="cc-onboarding-icon">🚀</div>
                        <h2>Choose Your Quick Start</h2>
                        <p>Pick a demo to see CardCrafter in action, then customize it for your needs.</p>
                    </div>
                    <div class="cc-onboarding-content">
                        <div class="cc-demo-options">
                            <div class="cc-demo-option" data-demo="team">
                                <div class="cc-demo-preview">
                                    <div class="cc-demo-icon">👥</div>
                                    <h3>Team Directory</h3>
                                    <p>Display team members with photos, roles, and contact info</p>
                                    <div class="cc-demo-tags">
                                        <span class="cc-tag">Popular</span>
                                        <span class="cc-tag">Business</span>
                                    </div>
                                </div>
                            </div>
                            <div class="cc-demo-option" data-demo="products">
                                <div class="cc-demo-preview">
                                    <div class="cc-demo-icon">📦</div>
                                    <h3>Product Showcase</h3>
                                    <p>Beautiful product cards with images, prices, and features</p>
                                    <div class="cc-demo-tags">
                                        <span class="cc-tag">E-commerce</span>
                                        <span class="cc-tag">Sales</span>
                                    </div>
                                </div>
                            </div>
                            <div class="cc-demo-option" data-demo="portfolio">
                                <div class="cc-demo-preview">
                                    <div class="cc-demo-icon">🎨</div>
                                    <h3>Portfolio Gallery</h3>
                                    <p>Showcase creative work with stunning visual layouts</p>
                                    <div class="cc-demo-tags">
                                        <span class="cc-tag">Creative</span>
                                        <span class="cc-tag">Design</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cc-onboarding-actions">
                        <button class="button button-primary button-large cc-onboarding-create-demo" disabled>
                            Create My First Cards →
                        </button>
                        <button class="button button-link cc-onboarding-back">← Back</button>
                    </div>
                </div>
                
                <!-- Step 3: Success Celebration -->
                <div id="cc-onboarding-step-3" class="cc-onboarding-step" data-step="3" style="display: none;">
                    <div class="cc-onboarding-header">
                        <div class="cc-onboarding-icon cc-success-icon">🎊</div>
                        <h2>Congratulations!</h2>
                        <p>You've successfully created your first card display! Your cards are ready to use.</p>
                    </div>
                    <div class="cc-onboarding-content">
                        <div class="cc-success-preview" id="cc-success-preview-area">
                            <!-- Generated cards preview will appear here -->
                        </div>
                        <div class="cc-next-steps">
                            <h3>What's Next?</h3>
                            <div class="cc-next-step">
                                <span class="cc-step-icon">📋</span>
                                <span>Copy the shortcode below to use anywhere on your site</span>
                            </div>
                            <div class="cc-next-step">
                                <span class="cc-step-icon">⚙️</span>
                                <span>Customize colors, layouts, and styling options</span>
                            </div>
                            <div class="cc-next-step">
                                <span class="cc-step-icon">📊</span>
                                <span>Connect your own data sources (JSON, WordPress posts, etc.)</span>
                            </div>
                        </div>
                        <div class="cc-shortcode-result">
                            <label>Your Shortcode (copy this!):</label>
                            <div class="cc-shortcode-display-success">
                                <code id="cc-generated-shortcode">[cardcrafter source="demo" layout="grid"]</code>
                                <button class="button button-secondary cc-copy-success-shortcode">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="cc-onboarding-actions">
                        <button class="button button-primary button-large cc-onboarding-finish">
                            Start Creating Cards! 🚀
                        </button>
                        <button class="button button-link cc-onboarding-explore">Explore Features</button>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Onboarding Styles -->
        <style>
        .cc-onboarding-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cc-onboarding-modal {
            background: #ffffff;
            border-radius: 12px;
            padding: 0;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: cc-modal-appear 0.3s ease-out;
        }
        
        @keyframes cc-modal-appear {
            from { opacity: 0; transform: scale(0.9) translateY(-20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .cc-onboarding-header {
            text-align: center;
            padding: 40px 40px 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cc-onboarding-icon {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
        }
        
        .cc-success-icon {
            animation: cc-bounce 1s ease-in-out;
        }
        
        @keyframes cc-bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .cc-onboarding-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
            color: #1a202c;
        }
        
        .cc-onboarding-header p {
            margin: 0;
            color: #6b7280;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .cc-onboarding-content {
            padding: 30px 40px;
        }
        
        .cc-value-props {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .cc-value-prop {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
            color: #374151;
        }
        
        .cc-prop-icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        
        .cc-onboarding-stats {
            text-align: center;
            color: #9ca3af;
        }
        
        .cc-demo-options {
            display: grid;
            gap: 20px;
            grid-template-columns: 1fr;
        }
        
        .cc-demo-option {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .cc-demo-option:hover {
            border-color: #3b82f6;
            background: #f8faff;
        }
        
        .cc-demo-option.selected {
            border-color: #3b82f6;
            background: #f0f7ff;
        }
        
        .cc-demo-preview h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .cc-demo-preview p {
            margin: 0 0 12px 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .cc-demo-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .cc-demo-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .cc-tag {
            background: #e5e7eb;
            color: #6b7280;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .cc-demo-option.selected .cc-tag {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .cc-success-preview {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
            color: #6b7280;
        }
        
        .cc-next-steps h3 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #1f2937;
        }
        
        .cc-next-step {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            color: #374151;
        }
        
        .cc-step-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .cc-shortcode-result {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
        }
        
        .cc-shortcode-result label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .cc-shortcode-display-success {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .cc-shortcode-display-success code {
            flex: 1;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 8px 12px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
        }
        
        .cc-onboarding-actions {
            padding: 20px 40px 40px;
            text-align: center;
            border-top: 1px solid #f0f0f0;
        }
        
        .cc-onboarding-actions .button {
            margin: 0 8px;
        }
        
        .cc-onboarding-actions .button-primary {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .cc-onboarding-actions .button-link {
            color: #6b7280;
            text-decoration: none;
        }
        
        .cc-onboarding-actions .button-link:hover {
            color: #374151;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .cc-onboarding-modal {
                width: 95%;
                margin: 20px;
            }
            
            .cc-onboarding-header,
            .cc-onboarding-content,
            .cc-onboarding-actions {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .cc-demo-options {
                gap: 15px;
            }
            
            .cc-demo-option {
                padding: 15px;
            }
        }
        
        /* Show/Hide Logic */
        .cc-onboarding-step {
            display: none;
        }
        
        .cc-onboarding-step.active {
            display: block;
        }
        </style>

        <!-- Onboarding JavaScript -->
        <script>
        jQuery(document).ready(function($) {
            // Initialize onboarding
            var currentStep = <?php echo intval($onboarding_step); ?>;
            var selectedDemo = '<?php echo esc_js(get_option('cc_preferred_demo_type', 'team')); ?>';
            
            // Show onboarding modal immediately for new users
            if (currentStep === 0) {
                $('#cc-onboarding-overlay').show();
                showStep(1);
            }
            
            // Step Navigation
            $('.cc-onboarding-next').click(function() {
                var current = getCurrentStep();
                if (current < 3) {
                    showStep(current + 1);
                    updateProgress(current + 1);
                }
            });
            
            $('.cc-onboarding-back').click(function() {
                var current = getCurrentStep();
                if (current > 1) {
                    showStep(current - 1);
                    updateProgress(current - 1);
                }
            });
            
            // Demo Selection
            $('.cc-demo-option').click(function() {
                $('.cc-demo-option').removeClass('selected');
                $(this).addClass('selected');
                selectedDemo = $(this).data('demo');
                $('.cc-onboarding-create-demo').prop('disabled', false);
            });
            
            // Pre-select saved demo preference
            $('.cc-demo-option[data-demo="' + selectedDemo + '"]').addClass('selected');
            if (selectedDemo) {
                $('.cc-onboarding-create-demo').prop('disabled', false);
            }
            
            // Create Demo Cards
            $('.cc-onboarding-create-demo').click(function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Creating Cards...');
                
                // Update the demo URL in the admin interface
                var demoUrl = '<?php echo CARDCRAFTER_URL; ?>demo-data/' + selectedDemo + '.json';
                $('#cc-source-url').val(demoUrl);
                
                // Trigger preview to generate cards
                $('#cc-preview-btn').click();
                
                // Save demo preference
                $.post(ajaxurl, {
                    action: 'cc_save_onboarding_progress',
                    step: 2,
                    demo_type: selectedDemo,
                    nonce: '<?php echo wp_create_nonce('cc_onboarding_progress'); ?>'
                });
                
                // Show success after brief delay
                setTimeout(function() {
                    showStep(3);
                    updateProgress(3);
                    celebrateSuccess();
                }, 1500);
            });
            
            // Finish Onboarding
            $('.cc-onboarding-finish').click(function() {
                $('#cc-onboarding-overlay').hide();
                completeOnboarding();
            });
            
            // Skip Tutorial
            $('.cc-onboarding-skip').click(function() {
                if (confirm('Are you sure you want to skip the tutorial? You can always access help from the documentation section.')) {
                    $('#cc-onboarding-overlay').hide();
                    completeOnboarding();
                }
            });
            
            // Copy Shortcode in Success Step
            $('.cc-copy-success-shortcode').click(function() {
                var shortcode = $('#cc-generated-shortcode').text();
                navigator.clipboard.writeText(shortcode).then(function() {
                    $('.cc-copy-success-shortcode').text('Copied!').css('background', '#22c55e');
                    setTimeout(function() {
                        $('.cc-copy-success-shortcode').text('Copy').css('background', '');
                    }, 2000);
                });
            });
            
            // Helper Functions
            function getCurrentStep() {
                return parseInt($('.cc-onboarding-step:visible').data('step')) || 1;
            }
            
            function showStep(stepNumber) {
                $('.cc-onboarding-step').hide();
                $('#cc-onboarding-step-' + stepNumber).show();
            }
            
            function updateProgress(step) {
                currentStep = step;
                $.post(ajaxurl, {
                    action: 'cc_save_onboarding_progress',
                    step: step,
                    nonce: '<?php echo wp_create_nonce('cc_onboarding_progress'); ?>'
                });
            }
            
            function celebrateSuccess() {
                // Update generated shortcode
                var demoUrl = '<?php echo CARDCRAFTER_URL; ?>demo-data/' + selectedDemo + '.json';
                var generatedShortcode = '[cardcrafter source="' + demoUrl + '" layout="grid" columns="3"]';
                $('#cc-generated-shortcode').text(generatedShortcode);
                
                // Add celebration animation
                $('.cc-success-icon').addClass('animated');
                
                // Show preview message
                $('#cc-success-preview-area').html(
                    '<div style="color: #16a34a; font-weight: 600;">✅ Your ' + 
                    selectedDemo.charAt(0).toUpperCase() + selectedDemo.slice(1) + 
                    ' cards are now live!</div>' +
                    '<p style="margin-top: 10px; color: #6b7280;">Check the preview below to see your cards in action.</p>'
                );
                
                // Mark first card completion
                $.post(ajaxurl, {
                    action: 'cc_complete_first_card',
                    demo_type: selectedDemo,
                    nonce: '<?php echo wp_create_nonce('cc_complete_first_card'); ?>'
                });
            }
            
            function completeOnboarding() {
                $.post(ajaxurl, {
                    action: 'cc_dismiss_activation_notice',
                    nonce: '<?php echo wp_create_nonce('cc_dismiss_notice'); ?>'
                });
                
                // Scroll to generated cards if they exist
                if ($('.cardcrafter-container').length) {
                    $('html, body').animate({
                        scrollTop: $('.cardcrafter-container').offset().top - 100
                    }, 500);
                }
            }
        });
        </script>
        <?php
    }

    /**
     * Handle activation notice dismissal.
     */
    public function dismiss_activation_notice()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'cc_dismiss_notice')) {
            wp_die('Security check failed');
        }
        delete_option('cc_show_activation_notice');
        wp_die();
    }

    /**
     * Add admin menu page.
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('CardCrafter', 'cardcrafter-data-grids'),
            __('CardCrafter', 'cardcrafter-data-grids'),
            'manage_options',
            'cardcrafter',
            array($this, 'render_admin_page'),
            'dashicons-grid-view',
            21
        );

    }

    /**
     * Render the admin dashboard page.
     */
    public function render_admin_page()
    {
        // Enqueue assets for the preview
        wp_enqueue_script('cardcrafter-admin');
        wp_enqueue_style('cardcrafter-style');

        $team_url = CARDCRAFTER_URL . 'demo-data/team.json';
        $products_url = CARDCRAFTER_URL . 'demo-data/products.json';
        $portfolio_url = CARDCRAFTER_URL . 'demo-data/portfolio.json';
        ?>
        <style>
        .cc-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 32px 24px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        /* Header Section */
        .cc-header {
            margin-bottom: 48px;
        }
        .cc-badge {
            background: #f3f4f6;
            color: #374151;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 12px;
        }
        .cc-title {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: #111827;
        }
        .cc-subtitle {
            color: #6b7280;
            font-size: 16px;
            margin: 0;
        }
        
        /* Main Two-Column Layout */
        .cc-main-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
            margin-bottom: 32px;
        }
        
        @media (max-width: 1200px) {
            .cc-main-layout {
                grid-template-columns: 1fr;
            }
        }
        
        .cc-left-column,
        .cc-right-column {
            min-width: 0; /* Prevent overflow */
        }
        
        /* Demo Section */
        .cc-demo {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 32px;
        }
        .cc-demo h2 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: #111827;
        }
        .cc-demo p {
            color: #6b7280;
            margin: 0 0 24px 0;
        }
        
        /* Controls */
        .cc-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        /* Vertical Controls for Right Column */
        .cc-controls-vertical {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 24px;
        }
        .cc-control label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }
        .cc-control select,
        .cc-control input[type="text"],
        .cc-control input[type="url"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        .cc-control input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            transform: scale(1.3);
        }
        .cc-control .description {
            color: #6b7280;
            font-size: 12px;
            margin: 4px 0 0 0;
        }
        
        /* Preview */
        .cc-preview {
            min-height: 400px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fafafa;
        }
        
        /* Demo Grid for Quick Start */
        .cc-demo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        
        @media (max-width: 900px) {
            .cc-demo-grid {
                grid-template-columns: 1fr;
            }
        }
        .cc-demo-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 12px;
            padding: 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
        }
        .cc-demo-card:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
            transform: translateY(-1px);
            text-decoration: none;
            color: inherit;
        }
        .cc-demo-icon {
            font-size: 32px;
            line-height: 1;
        }
        .cc-demo-content h3 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }
        .cc-demo-content p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        /* Features Grid for Documentation */
        .cc-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .cc-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .cc-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 8px 0 8px 0;
            color: #111827;
        }
        .cc-card p {
            color: #6b7280;
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
        }
        .cc-icon {
            font-size: 20px;
        }
        
        /* Help Icons */
        .cc-help-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 16px;
            text-align: center;
            background: #6b7280;
            color: white;
            border-radius: 50%;
            font-size: 11px;
            font-weight: bold;
            margin-left: 6px;
            cursor: help;
            vertical-align: middle;
            position: relative;
        }
        .cc-help-icon:hover {
            background: #374151;
        }
        
        /* Tooltip */
        .cc-help-icon:hover::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: normal;
            white-space: nowrap;
            z-index: 1000;
            max-width: 280px;
            white-space: normal;
            width: max-content;
        }
        
        .cc-help-icon:hover::after {
            content: '';
            position: absolute;
            bottom: 120%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1f2937;
            z-index: 1000;
        }
        
        .cc-shortcode-display {
            background: #f3f4f6;
            padding: 12px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            margin: 12px 0;
            display: block;
        }
        </style>
        
        <div class="wrap">
            <div class="cc-container">
                <!-- Header -->
                <header class="cc-header">
                    <h1 class="cc-title">CardCrafter</h1>
                    <p class="cc-subtitle">Transform JSON data into beautiful, responsive card layouts</p>
                </header>

                <!-- Main Two-Column Layout -->
                <div class="cc-main-layout">
                    <!-- Left Column: Quick Start + Preview -->
                    <div class="cc-left-column">
                        <section class="cc-demo">
                            <h2>🚀 Quick Start Demos</h2>
                            <p>Click any dataset below to instantly load a live preview</p>
                            
                            <div class="cc-demo-grid">
                                <a href="#" data-url="<?php echo esc_url($team_url); ?>" class="cc-demo-card">
                                    <div class="cc-demo-icon">👥</div>
                                    <div class="cc-demo-content">
                                        <h3>Team Directory</h3>
                                        <p>Professional team member profiles with photos, roles, and contact information</p>
                                    </div>
                                </a>
                                <a href="#" data-url="<?php echo esc_url($products_url); ?>" class="cc-demo-card">
                                    <div class="cc-demo-icon">🛍️</div>
                                    <div class="cc-demo-content">
                                        <h3>Product Showcase</h3>
                                        <p>E-commerce product display with images, prices, and descriptions</p>
                                    </div>
                                </a>
                                <a href="#" data-url="<?php echo esc_url($portfolio_url); ?>" class="cc-demo-card">
                                    <div class="cc-demo-icon">🎨</div>
                                    <div class="cc-demo-content">
                                        <h3>Portfolio Gallery</h3>
                                        <p>Creative portfolio showcase with project images and details</p>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Preview Area -->
                            <div id="cc-preview-container" class="cc-preview">
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #6b7280; min-height: 400px; flex-direction: column;">
                                    <span class="dashicons dashicons-grid-view" style="font-size: 48px; width: 48px; height: 48px; color: #d1d5db; margin-bottom: 16px;"></span>
                                    <p style="margin: 0 0 8px; font-size: 16px; color: #374151;">
                                        <?php esc_html_e('Ready to generate your cards!', 'cardcrafter-data-grids'); ?>
                                    </p>
                                    <p style="margin: 0; font-size: 14px; color: #6b7280;">
                                        <?php esc_html_e('👆 Click a Quick Start Demo above or configure settings →', 'cardcrafter-data-grids'); ?>
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Right Column: Configuration -->
                    <div class="cc-right-column">
                        <section class="cc-demo">
                            <h2>⚙️ Configuration</h2>
                            <p>Configure your data source and layout settings</p>
                            
                            <!-- Controls -->
                            <div class="cc-controls-vertical">
                                <div class="cc-control">
                                    <label for="cc-preview-url"><?php esc_html_e('Data Source URL', 'cardcrafter-data-grids'); ?></label>
                                    <input type="text" id="cc-preview-url" 
                                           placeholder="https://api.example.com/data.json"
                                           value="<?php echo isset($_GET['demo_url']) ? esc_attr($_GET['demo_url']) : ''; ?>">
                                    <p class="description"><?php esc_html_e('Enter a remote URL, upload a JSON file, or use WordPress posts.', 'cardcrafter-data-grids'); ?></p>
                                </div>
                                
                                <div class="cc-control">
                                    <label for="cc-layout"><?php esc_html_e('Layout Style', 'cardcrafter-data-grids'); ?></label>
                                    <select id="cc-layout">
                                        <option value="grid"><?php esc_html_e('Grid Layout', 'cardcrafter-data-grids'); ?></option>
                                        <option value="masonry"><?php esc_html_e('Masonry Layout', 'cardcrafter-data-grids'); ?></option>
                                        <option value="list"><?php esc_html_e('List Layout', 'cardcrafter-data-grids'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="cc-control">
                                    <label for="cc-columns"><?php esc_html_e('Columns', 'cardcrafter-data-grids'); ?></label>
                                    <select id="cc-columns">
                                        <option value="2">2 Columns</option>
                                        <option value="3" selected>3 Columns</option>
                                        <option value="4">4 Columns</option>
                                        <option value="5">5 Columns</option>
                                    </select>
                                </div>
                                
                                <!-- Display Options Separator -->
                                <div style="border-top: 1px solid #e5e7eb; margin: 24px 0 20px 0; padding-top: 20px;">
                                    <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #374151;">Display Options</h4>
                                </div>
                                
                                <div class="cc-control">
                                    <label>
                                        <input type="checkbox" id="cc-enable-search" checked> <?php esc_html_e('Enable Search Box', 'cardcrafter-data-grids'); ?>
                                        <span class="cc-help-icon" data-tooltip="Show a search input that allows users to search through the cards by title, description, or other content.">?</span>
                                    </label>
                                </div>
                                
                                <div class="cc-control">
                                    <label>
                                        <input type="checkbox" id="cc-enable-filters" checked> <?php esc_html_e('Enable Sorting Filters', 'cardcrafter-data-grids'); ?>
                                        <span class="cc-help-icon" data-tooltip="Show sorting dropdown options like A-Z, Z-A to help users organize the displayed cards.">?</span>
                                    </label>
                                </div>
                                
                                <div class="cc-control">
                                    <label>
                                        <input type="checkbox" id="cc-show-description" checked> <?php esc_html_e('Show Description', 'cardcrafter-data-grids'); ?>
                                        <span class="cc-help-icon" data-tooltip="Display the description text under each card's title and subtitle.">?</span>
                                    </label>
                                </div>
                                
                                <div class="cc-control">
                                    <label>
                                        <input type="checkbox" id="cc-show-buttons" checked> <?php esc_html_e('Show CTAs', 'cardcrafter-data-grids'); ?>
                                        <span class="cc-help-icon" data-tooltip="CTA stands for Call-to-Action. These are buttons like 'Learn More', 'View Details', or 'Read More' that encourage users to click and take action.">?</span>
                                    </label>
                                </div>
                                
                                <div class="cc-control">
                                    <label>
                                        <input type="checkbox" id="cc-enable-export" checked> <?php esc_html_e('Enable Export', 'cardcrafter-data-grids'); ?>
                                        <span class="cc-help-icon" data-tooltip="Allow users to export the displayed data in various formats like CSV, JSON, or PDF.">?</span>
                                    </label>
                                </div>
                                
                                <div class="cc-control">
                                    <label>
                                        <input type="checkbox" id="cc-show-image" checked> <?php esc_html_e('Show Images', 'cardcrafter-data-grids'); ?>
                                        <span class="cc-help-icon" data-tooltip="Display images in each card. If disabled, cards will be text-only.">?</span>
                                    </label>
                                </div>
                                
                                <div class="cc-control">
                                    <label for="cc-card-style">
                                        <?php esc_html_e('Card Style', 'cardcrafter-data-grids'); ?>
                                        <span class="cc-help-icon" data-tooltip="Choose the visual appearance of your cards: Default (clean), Minimal (simple), Bordered (outlined), or Shadow (elevated).">?</span>
                                    </label>
                                    <select id="cc-card-style">
                                        <option value="default"><?php esc_html_e('Default', 'cardcrafter-data-grids'); ?></option>
                                        <option value="minimal"><?php esc_html_e('Minimal', 'cardcrafter-data-grids'); ?></option>
                                        <option value="bordered"><?php esc_html_e('Bordered', 'cardcrafter-data-grids'); ?></option>
                                        <option value="shadow"><?php esc_html_e('Shadow', 'cardcrafter-data-grids'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="cc-control">
                                    <label>
                                        <input type="checkbox" id="cc-enable-pagination" checked> <?php esc_html_e('Enable Pagination', 'cardcrafter-data-grids'); ?>
                                        <span class="cc-help-icon" data-tooltip="Split cards across multiple pages with navigation controls. Disable to show all cards at once.">?</span>
                                    </label>
                                </div>
                                
                                <div class="cc-control">
                                    <label for="cc-items-per-page">
                                        <?php esc_html_e('Items Per Page', 'cardcrafter-data-grids'); ?>
                                        <span class="cc-help-icon" data-tooltip="How many cards to show on each page when pagination is enabled.">?</span>
                                    </label>
                                    <select id="cc-items-per-page">
                                        <option value="6" selected>6</option>
                                        <option value="9">9</option>
                                        <option value="12">12</option>
                                        <option value="18">18</option>
                                        <option value="24">24</option>
                                        <option value="-1"><?php esc_html_e('Show All', 'cardcrafter-data-grids'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                                <button id="cc-preview-btn" class="button button-primary button-large"><?php esc_html_e('Preview Cards', 'cardcrafter-data-grids'); ?></button>
                                <button id="cc-upload-json-btn" class="button button-secondary">
                                    <span class="dashicons dashicons-upload" style="margin-right: 4px; vertical-align: middle;"></span>
                                    <?php esc_html_e('Upload JSON File', 'cardcrafter-data-grids'); ?>
                                </button>
                                <button id="cc-wp-posts-btn" class="button button-secondary">
                                    <span class="dashicons dashicons-wordpress" style="margin-right: 4px; vertical-align: middle;"></span>
                                    <?php esc_html_e('Use WP Posts', 'cardcrafter-data-grids'); ?>
                                </button>
                            </div>
                            
                            <!-- Generated Shortcode -->
                            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-top: 24px;">
                                <h3 style="margin: 0 0 12px 0; font-size: 16px; color: #111827;">📋 Generated Shortcode</h3>
                                <p style="margin: 0 0 12px 0; color: #6b7280; font-size: 14px;">Copy this shortcode to use anywhere in WordPress</p>
                                <code id="cc-shortcode-display" class="cc-shortcode-display">[cardcrafter source="URL" layout="grid" columns="3"]</code>
                                <button id="cc-copy-shortcode" class="button button-secondary" style="width: 100%; margin-top: 12px;">
                                    <?php esc_html_e('Copy Shortcode', 'cardcrafter-data-grids'); ?>
                                </button>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Documentation Section -->
                <section class="cc-demo">
                    <h2>📚 Documentation</h2>
                    <p>Learn more about CardCrafter's features and capabilities</p>
                    
                    <div class="cc-features">
                        <div class="cc-card">
                            <div class="cc-icon">🎨</div>
                            <h3>Multiple Layouts</h3>
                            <p>Grid, masonry, and list layouts. All responsive and customizable.</p>
                        </div>
                        <div class="cc-card">
                            <div class="cc-icon">📊</div>
                            <h3>Any Data Source</h3>
                            <p>JSON APIs, WordPress posts, WooCommerce products, or CSV files.</p>
                        </div>
                        <div class="cc-card">
                            <div class="cc-icon">🔍</div>
                            <h3>Live Search</h3>
                            <p>Real-time search and filtering built-in.</p>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 24px;">
                        <p style="color: #6b7280; margin-bottom: 12px;">
                            Visit our <a href="https://github.com/isupersk/cardcrafter-data-grids" target="_blank" style="color: #0073aa;">GitHub repository</a> for comprehensive examples and documentation.
                        </p>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }


    /**
     * Register Gutenberg block for the block editor.
     */
    public function register_block()
    {
        wp_register_script(
            'cardcrafter-block',
            CARDCRAFTER_URL . 'assets/js/block.js',
            array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element'),
            CARDCRAFTER_VERSION,
            true
        );

        wp_register_style(
            'cardcrafter-block-editor',
            CARDCRAFTER_URL . 'assets/css/cardcrafter.css',
            array(),
            CARDCRAFTER_VERSION
        );

        // Demo URLs for the block editor
        $demo_urls = array(
            array('label' => 'Select a demo...', 'value' => ''),
            array('label' => '👥 Team Directory', 'value' => CARDCRAFTER_URL . 'demo-data/team.json'),
            array('label' => '📦 Product Showcase', 'value' => CARDCRAFTER_URL . 'demo-data/products.json'),
            array('label' => '🎨 Portfolio Gallery', 'value' => CARDCRAFTER_URL . 'demo-data/portfolio.json')
        );

        wp_localize_script('cardcrafter-block', 'cardcrafterData', array(
            'demoUrls' => $demo_urls
        ));

        register_block_type('cardcrafter/data-grid', array(
            'editor_script' => 'cardcrafter-block',
            'editor_style' => 'cardcrafter-block-editor',
            'render_callback' => array($this, 'render_block_callback'),
            'attributes' => array(
                'source' => array('type' => 'string', 'default' => ''),
                'layout' => array('type' => 'string', 'default' => 'grid'),
                'search' => array('type' => 'boolean', 'default' => true),
                'sort' => array('type' => 'boolean', 'default' => true),
                'cards_per_row' => array('type' => 'number', 'default' => 3)
            )
        ));
    }

    /**
     * Render callback for the Gutenberg block (frontend only).
     */
    public function render_block_callback($attributes)
    {
        $shortcode_attrs = array(
            'source' => $attributes['source'] ?? '',
            'wp_query' => $attributes['wp_query'] ?? '',
            'post_type' => $attributes['post_type'] ?? 'post',
            'posts_per_page' => $attributes['posts_per_page'] ?? 12,
            'layout' => $attributes['layout'] ?? 'grid',
            'search' => ($attributes['search'] ?? true) ? 'true' : 'false',
            'sort' => ($attributes['sort'] ?? true) ? 'true' : 'false',
            'columns' => $attributes['cards_per_row'] ?? 3
        );

        return $this->render_cards($shortcode_attrs);
    }

    /**
     * Register frontend assets.
     */
    public function register_assets()
    {
        wp_register_script(
            'cardcrafter-lib',
            CARDCRAFTER_URL . 'assets/js/cardcrafter.js',
            array(),
            CARDCRAFTER_VERSION,
            true
        );

        wp_register_script(
            'cardcrafter-admin',
            CARDCRAFTER_URL . 'assets/js/admin.js',
            array('cardcrafter-lib'),
            CARDCRAFTER_VERSION,
            true
        );

        wp_localize_script('cardcrafter-admin', 'cardcrafterAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cardcrafter_proxy_nonce'),
            'i18n' => array(
                'validUrl' => __('Please enter a valid URL', 'cardcrafter-data-grids'),
                'loading' => __('Loading cards...', 'cardcrafter-data-grids'),
                'libNotLoaded' => __('CardCrafter library not loaded.', 'cardcrafter-data-grids'),
                'copyFailed' => __('Failed to copy to clipboard. Please copy manually.', 'cardcrafter-data-grids'),
                'copied' => __('Copied!', 'cardcrafter-data-grids')
            )
        ));

        wp_register_script(
            'cardcrafter-frontend',
            CARDCRAFTER_URL . 'assets/js/frontend.js',
            array('cardcrafter-lib'),
            CARDCRAFTER_VERSION,
            true
        );

        wp_register_style(
            'cardcrafter-style',
            CARDCRAFTER_URL . 'assets/css/cardcrafter.css',
            array(),
            CARDCRAFTER_VERSION
        );
    }


    /**
     * Shortcode to render the card container.
     * Usage: [cardcrafter source="/path/to/data.json" layout="grid" columns="3"]
     * 
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_cards($atts)
    {
        $atts = shortcode_atts(array(
            'source' => '',
            'wp_query' => '',
            'post_type' => 'post',
            'posts_per_page' => 12,
            'id' => 'cardcrafter-' . uniqid(),
            'layout' => 'grid',
            'columns' => 3,
            'items_per_page' => 12,
            'image_field' => 'image',
            'title_field' => 'title',
            'subtitle_field' => 'subtitle',
            'description_field' => 'description',
            'link_field' => 'link'
        ), $atts, 'cardcrafter-data-grids');

        // Sanitize inputs
        $atts['source'] = esc_url_raw($atts['source']);
        $atts['wp_query'] = sanitize_text_field($atts['wp_query']);
        $atts['post_type'] = sanitize_text_field($atts['post_type']);
        $atts['posts_per_page'] = min(100, max(1, absint($atts['posts_per_page'])));
        $atts['layout'] = sanitize_key($atts['layout']);
        $atts['columns'] = absint($atts['columns']);
        $atts['items_per_page'] = min(100, max(1, absint($atts['items_per_page']))); // Limit between 1-100

        // Apply license-based feature gating
        $atts['items_per_page'] = apply_filters('cardcrafter_max_cards_per_page', $atts['items_per_page']);
        $atts['posts_per_page'] = apply_filters('cardcrafter_max_cards_per_page', $atts['posts_per_page']);

        // WordPress Native Data Mode
        if (!empty($atts['wp_query']) || (!empty($atts['post_type']) && empty($atts['source'])) || $atts['source'] === 'wp_posts') {
            return $this->render_wordpress_data($atts);
        }

        // Auto-demo mode: Show demo data if no source provided
        if (empty($atts['source'])) {
            $atts['source'] = CARDCRAFTER_URL . 'demo-data/team.json';
            $demo_mode = true;
        } else {
            $demo_mode = false;
        }

        // Try Cache First (SWR pattern)
        $cache_key = 'cardcrafter_cache_' . md5($atts['source']);
        $cached_data = get_transient($cache_key);

        // Enqueue assets
        wp_enqueue_script('cardcrafter-lib');
        wp_enqueue_style('cardcrafter-style');

        // Build config object
        $config = array(
            'source' => admin_url('admin-ajax.php') . '?action=cardcrafter_proxy_fetch&url=' . urlencode($atts['source']) . '&nonce=' . wp_create_nonce('cardcrafter_proxy_nonce'),
            'layout' => $atts['layout'],
            'columns' => $atts['columns'],
            'itemsPerPage' => $atts['items_per_page'],
            'fields' => array(
                'image' => sanitize_key($atts['image_field']),
                'title' => sanitize_key($atts['title_field']),
                'subtitle' => sanitize_key($atts['subtitle_field']),
                'description' => sanitize_key($atts['description_field']),
                'link' => sanitize_key($atts['link_field'])
            )
        );

        ob_start();
        ?>
        <div id="<?php echo esc_attr($atts['id']); ?>" class="cardcrafter-container"
            role="region"
            aria-label="<?php esc_attr_e('Card Grid', 'cardcrafter-data-grids'); ?>"
            data-config='<?php echo esc_attr(wp_json_encode($config)); ?>'>
            <?php if ($demo_mode): ?>
                <div class="cardcrafter-demo-banner" role="status">
                    <div class="cardcrafter-demo-content">
                        <span class="cardcrafter-demo-badge"><?php esc_html_e('🚀 Demo Mode', 'cardcrafter-data-grids'); ?></span>
                        <p><?php esc_html_e('This is sample team data.', 'cardcrafter-data-grids'); ?> <strong><a href="#" class="cardcrafter-try-own-data"><?php esc_html_e('Try Your Own Data →', 'cardcrafter-data-grids'); ?></a></strong></p>
                    </div>
                </div>
            <?php endif; ?>
            <div class="cardcrafter-loading" role="status" aria-live="polite">
                <div class="cardcrafter-spinner" aria-hidden="true"></div>
                <p><?php esc_html_e('Loading CardCrafter...', 'cardcrafter-data-grids'); ?></p>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof CardCrafter !== 'undefined') {
                var container = document.getElementById('<?php echo esc_js($atts['id']); ?>');
                if (container) {
                    var config = JSON.parse(container.getAttribute('data-config'));
                    config.selector = '#<?php echo esc_js($atts['id']); ?>';
                    config.enableAccessibility = true;
                    new CardCrafter(config);
                }
            } else {
                console.error('CardCrafter library not loaded');
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Secure AJAX Data Proxy & Cache Handler.
     */
    /**
     * Secure AJAX Data Proxy & Cache Handler.
     */
    public function ajax_proxy_fetch()
    {
        // 1. Verify Nonce First (Compliance: NonceVerification)
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'cardcrafter_proxy_nonce')) {
            wp_send_json_error('Security check failed.');
        }

        // 1.5 Rate Limiting Check (Security)
        if ($this->is_rate_limited()) {
            status_header(429);
            wp_send_json_error('Rate limit exceeded. Please wait.', 429);
        }

        // 2. Fetch and Unslash URL (Compliance: MissingUnslash)
        $url = isset($_REQUEST['url']) ? esc_url_raw(wp_unslash($_REQUEST['url'])) : '';

        // Verification: Use wp_safe_remote_get which handles private IP blocking
        if (empty($url)) {
            wp_send_json_error('Invalid URL.');
        }

        $cache_key = 'cardcrafter_cache_' . md5($url);
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            wp_send_json_success($cached_data);
        }

        // SENSITIVE SINK: Using wp_safe_remote_get to prevent SSRF
        $response = wp_safe_remote_get($url, array('timeout' => 15));

        if (is_wp_error($response)) {
            // This handles both connection errors AND blocked local IPs
            // Security fix: Use sanitized error message to prevent information disclosure
            wp_send_json_error($this->sanitize_error_message($response));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data === null) {
            wp_send_json_error('Invalid JSON from source.');
        }

        set_transient($cache_key, $data, HOUR_IN_SECONDS);
        $this->track_url($url);

        wp_send_json_success($data);
    }

    /**
     * AJAX handler for WordPress posts preview
     */
    public function ajax_wp_posts_preview()
    {
        // Verify nonce
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'cardcrafter_proxy_nonce')) {
            wp_send_json_error('Security check failed.');
        }

        // Get recent posts (with cache busting)
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
            'cache_results' => false, // Disable caching for fresh results
            'no_found_rows' => true
        ));

        if (empty($posts)) {
            wp_send_json_error('No WordPress posts found.');
        }

        // Convert WordPress posts to CardCrafter data format
        $card_data = array();
        foreach ($posts as $post) {
            $featured_image = get_the_post_thumbnail_url($post->ID, 'medium');
            
            // Fallback to full size if medium doesn't exist
            if (!$featured_image) {
                $featured_image = get_the_post_thumbnail_url($post->ID, 'full');
            }
            
            $card_item = array(
                'id' => $post->ID,
                'title' => get_the_title($post->ID),
                'subtitle' => get_the_date('F j, Y', $post->ID),
                'description' => wp_trim_words(get_the_excerpt($post->ID), 20, '...'),
                'link' => get_permalink($post->ID),
                'image' => $featured_image ?: $this->get_placeholder_image(get_the_title($post->ID)),
                'post_type' => $post->post_type,
                'author' => get_the_author_meta('display_name', $post->post_author),
                'debug_thumbnail_id' => get_post_thumbnail_id($post->ID), // Debug info
                'debug_image_url' => $featured_image // Debug info
            );

            $card_data[] = $card_item;
        }

        wp_send_json_success($card_data);
    }

    /**
     * URL Analytics & Tracking.
     */
    private function track_url(string $url)
    {
        $urls = get_option('cardcrafter_tracked_urls', array());
        if (!is_array($urls))
            $urls = array();
        if (!in_array($url, $urls)) {
            $urls[] = $url;
            update_option('cardcrafter_tracked_urls', array_slice($urls, -50));
        }
    }

    /**
     * Automated Cache Refresh (Cron).
     */
    public function automated_cache_refresh()
    {
        $urls = get_option('cardcrafter_tracked_urls', array());
        foreach ($urls as $url) {
            // Using safe method here as well
            $response = wp_safe_remote_get($url, array('timeout' => 10));
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                if ($data) {
                    set_transient('cardcrafter_cache_' . md5($url), $data, HOUR_IN_SECONDS);
                }
            }
        }
    }

    /**
     * Rate Limiting Constants.
     */
    private const RATE_LIMIT_MAX_REQUESTS = 30;
    private const RATE_LIMIT_WINDOW_SECONDS = 60;

    /**
     * Rate Limiting Helper.
     * 
     * Checks and increments the request count for the current user/IP.
     * 
     * @return bool True if rate limit exceeded, false if allowed.
     */
    private function is_rate_limited(): bool
    {
        // Build unique identifier
        $identifier = get_current_user_id();
        if ($identifier === 0) {
            $identifier = $this->get_client_ip();
        }

        $transient_key = 'cc_rate_' . md5((string) $identifier);
        $current_count = get_transient($transient_key);

        if ($current_count === false) {
            set_transient($transient_key, 1, self::RATE_LIMIT_WINDOW_SECONDS);
            return false;
        }

        if ((int) $current_count >= self::RATE_LIMIT_MAX_REQUESTS) {
            return true;
        }

        set_transient($transient_key, (int) $current_count + 1, self::RATE_LIMIT_WINDOW_SECONDS);
        return false;
    }

    /**
     * Get Client IP Address.
     * 
     * @return string The client IP address.
     */
    private function get_client_ip(): string
    {
        $ip = '';
        $headers = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', sanitize_text_field(wp_unslash($_SERVER[$header])))[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    break;
                }
                $ip = '';
            }
        }

        return $ip ?: 'unknown_' . md5(wp_json_encode($_SERVER));
    }

    /**
     * Sanitize error messages to prevent information disclosure.
     * 
     * Maps internal error codes to safe, user-friendly messages while
     * preserving debugging information in error logs.
     * 
     * @param WP_Error $error The WordPress error object.
     * @return string Safe error message for frontend display.
     */
    private function sanitize_error_message($error): string
    {
        $error_code = $error->get_error_code();
        $error_message = $error->get_error_message();
        
        // Log the actual error for debugging (admin only)
        if (current_user_can('manage_options')) {
            error_log('CardCrafter Error [' . $error_code . ']: ' . $error_message);
        }

        // Map error codes to safe user messages
        $safe_messages = array(
            'http_request_failed' => 'Unable to connect to the data source. Please check the URL and try again.',
            'http_request_timeout' => 'Request timed out. The data source may be temporarily unavailable.',
            'http_404' => 'Data source not found. Please verify the URL is correct.',
            'http_403' => 'Access denied to the data source.',
            'http_500' => 'The data source is experiencing technical difficulties.',
            'http_502' => 'The data source is temporarily unavailable.',
            'http_503' => 'The data source is temporarily unavailable.',
        );

        // Check message content for sensitive patterns first (more specific)
        if (strpos($error_message, 'cURL error') !== false) {
            return 'Network connection error. Please try again later.';
        }
        
        if (strpos($error_message, 'SSL') !== false) {
            return 'Secure connection error. Please verify the URL uses HTTPS.';
        }

        // Check for specific HTTP error codes
        if (strpos($error_code, 'http_') === 0) {
            return $safe_messages[$error_code] ?? 'Unable to retrieve data from the source.';
        }

        // Generic fallback for any unhandled error types
        return 'Unable to retrieve data. Please check your data source URL.';
    }

    /**
     * Render WordPress native data as cards with optimized performance
     */
    public function render_wordpress_data($atts)
    {
        // Performance optimization: Check cache first
        $cache_key = $this->generate_wp_query_cache_key($atts);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false && !$this->is_debug_mode()) {
            return $cached_result;
        }

        $performance_start = microtime(true);
        
        // Build optimized WP_Query arguments
        $query_args = array(
            'post_type' => $atts['post_type'],
            'posts_per_page' => $atts['posts_per_page'],
            'post_status' => 'publish',
            'meta_query' => array(),
            'tax_query' => array(),
            // Performance optimizations
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'cache_results' => false  // We handle our own caching
        );

        // Parse custom wp_query string (e.g., "category=news&author=5")
        if (!empty($atts['wp_query'])) {
            parse_str($atts['wp_query'], $custom_args);
            $query_args = array_merge($query_args, $custom_args);
        }

        // Use WP_Query for better control over performance
        $wp_query = new WP_Query($query_args);
        $posts = $wp_query->posts;
        
        if (empty($posts)) {
            $result = '<div class="cardcrafter-no-results"><p>No WordPress posts found matching your criteria.</p></div>';
            // Cache empty results for shorter time
            set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
            return $result;
        }

        // Batch-load all required data to reduce database calls
        $post_ids = wp_list_pluck($posts, 'ID');
        
        // Batch-load featured images
        $featured_images = $this->batch_load_featured_images($post_ids);
        
        // Batch-load author data
        $author_ids = array_unique(wp_list_pluck($posts, 'post_author'));
        $authors_data = $this->batch_load_authors_data($author_ids);

        // Convert WordPress posts to CardCrafter data format (optimized)
        $card_data = array();
        foreach ($posts as $post) {
            $featured_image = $featured_images[$post->ID] ?? '';
            $author_data = $authors_data[$post->post_author] ?? '';
            
            $card_item = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'subtitle' => get_the_date('F j, Y', $post),
                'description' => $this->get_optimized_excerpt($post),
                'link' => get_permalink($post->ID),
                'image' => $featured_image ?: $this->get_placeholder_image($post->post_title),
                'post_type' => $post->post_type,
                'author' => $author_data
            );

            // Add custom fields support (ACF integration with fallback) - optimized
            if (function_exists('get_fields')) {
                $custom_fields = get_fields($post->ID);
                if ($custom_fields && is_array($custom_fields)) {
                    $card_item = array_merge($card_item, $custom_fields);
                }
            }

            $card_data[] = $card_item;
        }

        // Clean up query object
        wp_reset_postdata();

        // Enqueue assets
        wp_enqueue_script('cardcrafter-lib');
        wp_enqueue_style('cardcrafter-style');

        // Build config for WordPress data
        $config = array(
            'data' => $card_data, // Pass data directly instead of URL
            'layout' => $atts['layout'],
            'columns' => $atts['columns'],
            'itemsPerPage' => $atts['items_per_page'],
            'wpDataMode' => true,
            'fields' => array(
                'image' => sanitize_key($atts['image_field']),
                'title' => sanitize_key($atts['title_field']),
                'subtitle' => sanitize_key($atts['subtitle_field']),
                'description' => sanitize_key($atts['description_field']),
                'link' => sanitize_key($atts['link_field'])
            )
        );

        ob_start();
        ?>
        <div id="<?php echo esc_attr($atts['id']); ?>" class="cardcrafter-container"
            role="region"
            aria-label="<?php esc_attr_e('WordPress Posts Card Grid', 'cardcrafter-data-grids'); ?>"
            data-config='<?php echo esc_attr(wp_json_encode($config)); ?>'>
            <div class="cardcrafter-wp-banner" role="status">
                <div class="cardcrafter-wp-content">
                    <span class="cardcrafter-wp-badge"><?php esc_html_e('📝 WordPress Data', 'cardcrafter-data-grids'); ?></span>
                    <p><?php
                        /* translators: %1$d: number of items, %2$s: post type */
                        printf(esc_html__('Showing %1$d %2$s(s) from your site', 'cardcrafter-data-grids'), count($card_data), esc_html($atts['post_type']));
                    ?></p>
                </div>
            </div>
            <div class="cardcrafter-loading" role="status" aria-live="polite">
                <div class="cardcrafter-spinner" aria-hidden="true"></div>
                <p><?php esc_html_e('Loading WordPress content...', 'cardcrafter-data-grids'); ?></p>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof CardCrafter !== 'undefined') {
                var container = document.getElementById('<?php echo esc_js($atts['id']); ?>');
                if (container) {
                    var config = JSON.parse(container.getAttribute('data-config'));
                    new CardCrafter({
                        selector: '#<?php echo esc_js($atts['id']); ?>',
                        source: 'wp_posts',
                        data: config.data,
                        layout: config.layout,
                        columns: config.columns,
                        itemsPerPage: config.itemsPerPage,
                        wpDataMode: config.wpDataMode,
                        fields: config.fields,
                        enableAccessibility: true,
                        ariaLabel: '<?php esc_attr_e('WordPress Posts', 'cardcrafter-data-grids'); ?>'
                    });
                }
            } else {
                console.error('CardCrafter library not loaded');
            }
        });
        </script>
        <?php
        $result = ob_get_clean();
        
        // Performance tracking and caching
        $performance_end = microtime(true);
        $execution_time = ($performance_end - $performance_start) * 1000; // Convert to milliseconds
        
        // Log performance if enabled
        if ($this->is_debug_mode()) {
            error_log(sprintf(
                'CardCrafter WordPress Query Performance: %dms for %d %s posts (Cache: %s)',
                round($execution_time),
                count($card_data),
                $atts['post_type'],
                $cached_result !== false ? 'HIT' : 'MISS'
            ));
        }
        
        // Cache the result for future requests (smart cache duration based on post type)
        $cache_duration = $this->get_cache_duration($atts['post_type']);
        set_transient($cache_key, $result, $cache_duration);
        
        // Hook for cache invalidation when posts are updated
        $this->register_cache_invalidation_hooks($cache_key, $atts['post_type']);
        
        return $result;
    }

    /**
     * Generate cache key for WordPress query
     * 
     * @param array $atts Shortcode attributes
     * @return string Cache key
     */
    private function generate_wp_query_cache_key($atts)
    {
        $key_parts = array(
            'cardcrafter_wp_query',
            md5(serialize($atts)),
            get_current_blog_id(),
            get_locale()
        );
        return implode('_', $key_parts);
    }

    /**
     * Check if debug mode is enabled
     * 
     * @return bool True if debug mode is enabled
     */
    private function is_debug_mode()
    {
        return defined('WP_DEBUG') && WP_DEBUG && (defined('CARDCRAFTER_DEBUG') && CARDCRAFTER_DEBUG);
    }

    /**
     * Batch-load featured images for multiple posts to reduce database calls
     * 
     * @param array $post_ids Array of post IDs
     * @return array Associative array of post_id => image_url
     */
    private function batch_load_featured_images($post_ids)
    {
        if (empty($post_ids)) {
            return array();
        }

        $images = array();
        
        // Get all thumbnail IDs in one query
        $thumbnail_ids = get_post_meta(null, '_thumbnail_id', false);
        $thumbnail_map = array();
        
        foreach ($thumbnail_ids as $meta_id => $thumbnail_id) {
            $post_id = get_metadata_by_mid('post', $meta_id);
            if ($post_id && in_array($post_id->object_id, $post_ids)) {
                $thumbnail_map[$post_id->object_id] = $thumbnail_id;
            }
        }

        // Batch-generate image URLs
        foreach ($post_ids as $post_id) {
            if (isset($thumbnail_map[$post_id])) {
                $image_url = wp_get_attachment_image_url($thumbnail_map[$post_id], 'medium');
                $images[$post_id] = $image_url ?: '';
            } else {
                $images[$post_id] = '';
            }
        }

        return $images;
    }

    /**
     * Batch-load author data for multiple author IDs to reduce database calls
     * 
     * @param array $author_ids Array of author IDs
     * @return array Associative array of author_id => display_name
     */
    private function batch_load_authors_data($author_ids)
    {
        if (empty($author_ids)) {
            return array();
        }

        $authors_data = array();
        
        // Use get_users with include parameter for efficient batch loading
        $users = get_users(array(
            'include' => $author_ids,
            'fields' => array('ID', 'display_name')
        ));

        foreach ($users as $user) {
            $authors_data[$user->ID] = $user->display_name;
        }

        return $authors_data;
    }

    /**
     * Get optimized excerpt for a post without triggering additional queries
     * 
     * @param WP_Post $post Post object
     * @return string Optimized excerpt
     */
    private function get_optimized_excerpt($post)
    {
        if (!empty($post->post_excerpt)) {
            return wp_trim_words($post->post_excerpt, 20, '...');
        }
        
        // Generate excerpt from content if no explicit excerpt
        $content = strip_shortcodes($post->post_content);
        $content = wp_strip_all_tags($content);
        return wp_trim_words($content, 20, '...');
    }

    /**
     * Get cache duration based on post type
     * 
     * @param string $post_type Post type
     * @return int Cache duration in seconds
     */
    private function get_cache_duration($post_type)
    {
        // Different cache durations for different post types
        $durations = array(
            'post' => 15 * MINUTE_IN_SECONDS,      // Blog posts change frequently
            'page' => 2 * HOUR_IN_SECONDS,         // Pages change less frequently  
            'product' => 30 * MINUTE_IN_SECONDS,   // Products change moderately
            'attachment' => 4 * HOUR_IN_SECONDS    // Media rarely changes
        );

        return $durations[$post_type] ?? HOUR_IN_SECONDS; // Default 1 hour
    }

    /**
     * Register cache invalidation hooks for specific post type
     * 
     * @param string $cache_key Cache key to invalidate
     * @param string $post_type Post type to watch for changes
     */
    private function register_cache_invalidation_hooks($cache_key, $post_type)
    {
        // Store cache keys that need invalidation
        $cache_keys = get_option('cardcrafter_cache_keys', array());
        $cache_keys[$post_type][] = $cache_key;
        update_option('cardcrafter_cache_keys', $cache_keys);

        // Add hooks for cache invalidation (only if not already added)
        if (!has_action('save_post', array($this, 'invalidate_post_cache'))) {
            add_action('save_post', array($this, 'invalidate_post_cache'), 10, 2);
            add_action('delete_post', array($this, 'invalidate_post_cache'), 10, 2);
            add_action('wp_trash_post', array($this, 'invalidate_post_cache'), 10, 1);
            add_action('untrash_post', array($this, 'invalidate_post_cache'), 10, 1);
        }
    }

    /**
     * Invalidate post caches when posts are modified
     * 
     * @param int $post_id Post ID
     * @param WP_Post|null $post Post object (optional)
     */
    public function invalidate_post_cache($post_id, $post = null)
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        $post = $post ?: get_post($post_id);
        if (!$post) {
            return;
        }

        $cache_keys = get_option('cardcrafter_cache_keys', array());
        
        // Invalidate caches for this post type
        if (isset($cache_keys[$post->post_type])) {
            foreach ($cache_keys[$post->post_type] as $cache_key) {
                delete_transient($cache_key);
            }
            
            // Clean up expired cache keys
            unset($cache_keys[$post->post_type]);
            update_option('cardcrafter_cache_keys', $cache_keys);
        }

        // Also invalidate general WordPress query caches that might include this post
        $this->cleanup_expired_caches();
    }

    /**
     * Cleanup expired transient caches to prevent database bloat
     */
    private function cleanup_expired_caches()
    {
        global $wpdb;
        
        // Clean up expired transients related to CardCrafter (runs max once per hour)
        $cleanup_key = 'cardcrafter_cache_cleanup_last_run';
        $last_cleanup = get_transient($cleanup_key);
        
        if ($last_cleanup === false) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} 
                     WHERE option_name LIKE %s 
                     AND option_name LIKE %s",
                    '%cardcrafter_wp_query%',
                    '%transient_timeout_%'
                )
            );
            
            set_transient($cleanup_key, time(), HOUR_IN_SECONDS);
        }
    }

    /**
     * Generate placeholder image for WordPress posts
     */
    private function get_placeholder_image($title)
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">' .
            '<rect fill="#e0e0e0" width="400" height="300"/>' .
            '<text fill="#888" font-family="sans-serif" font-size="24" text-anchor="middle" x="200" y="160">' .
            esc_html(substr($title, 0, 20)) . '</text></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Initialize Elementor integration
     */
    public function init_elementor_integration()
    {
        // Check if Elementor is loaded
        if (did_action('elementor/loaded')) {
            // Load Elementor manager
            require_once CARDCRAFTER_PATH . 'elementor/class-cardcrafter-elementor-manager.php';
            CardCrafter_Elementor_Manager::get_instance();
        }
    }

    /**
     * Initialize License Manager
     */
    public function init_license_manager()
    {
        // Load license manager
        require_once CARDCRAFTER_PATH . 'includes/class-cardcrafter-license-manager.php';
        CardCrafter_License_Manager::get_instance();
    }

    /**
     * Handle Lead Subscription (Lead Magnet).
     * 
     * Validates email and sends to external API.
     * 
     * @return void
     */
    public function handle_lead_subscription()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cc_lead_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Validate email
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
            return;
        }

        // Send lead to external API (you can customize this endpoint)
        $response = wp_remote_post('https://fahdmurtaza.com/api/cardcrafter-lead', array(
            'body' => array(
                'email' => $email,
                'plugin_version' => CARDCRAFTER_VERSION,
                'site_url' => get_site_url(),
                'timestamp' => current_time('mysql'),
                'source' => 'welcome_screen'
            ),
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            )
        ));

        // Fallback: send email directly if API fails
        if (is_wp_error($response)) {
            wp_mail(
                'info@fahdmurtaza.com',
                'CardCrafter Lead: ' . $email,
                "New subscriber from CardCrafter plugin:\n\nEmail: " . $email . "\nSite: " . get_site_url() . "\nDate: " . current_time('mysql') . "\n\nNote: API call failed, sent via email fallback."
            );
        }

        wp_send_json_success(array(
            'message' => 'Subscription successful'
        ));
    }

    /**
     * Save onboarding progress
     */
    public function save_onboarding_progress()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cc_onboarding_progress')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $step = isset($_POST['step']) ? absint($_POST['step']) : 0;
        $demo_type = isset($_POST['demo_type']) ? sanitize_text_field($_POST['demo_type']) : '';

        // Update onboarding step
        if ($step > 0) {
            update_option('cc_onboarding_step', $step);
        }

        // Save demo preference
        if (!empty($demo_type)) {
            update_option('cc_preferred_demo_type', $demo_type);
        }

        wp_send_json_success(array(
            'step' => $step,
            'demo_type' => $demo_type
        ));
    }

    /**
     * Mark first card completion milestone
     */
    public function complete_first_card()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cc_complete_first_card')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $demo_type = isset($_POST['demo_type']) ? sanitize_text_field($_POST['demo_type']) : '';

        // Mark completion milestone
        update_option('cc_user_completed_first_card', true);
        update_option('cc_onboarding_completion_time', current_time('timestamp'));
        
        if (!empty($demo_type)) {
            update_option('cc_first_card_demo_type', $demo_type);
        }

        // Calculate time to first success
        $start_time = get_option('cc_onboarding_start_time', 0);
        $time_to_value = current_time('timestamp') - $start_time;

        wp_send_json_success(array(
            'completed' => true,
            'demo_type' => $demo_type,
            'time_to_value_minutes' => round($time_to_value / 60, 1)
        ));
    }
}

// Register activation hook
register_activation_hook(__FILE__, array('CardCrafter', 'activate'));

// Initialize
if (!defined('WP_INT_TEST')) {
    CardCrafter::get_instance();
}
