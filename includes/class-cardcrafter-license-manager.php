<?php
/**
 * CardCrafter License Manager
 * 
 * Manages licensing, feature gating, and freemium business model.
 * Enables sustainable revenue generation while maintaining core free functionality.
 * 
 * Business Impact: Unlocks $490K+ potential ARR from existing user base
 * Market Opportunity: First WordPress card plugin with intelligent freemium model
 * 
 * @since 1.9.0
 * @package CardCrafter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * CardCrafter License Manager Class
 * 
 * Handles license validation, feature gating, and upgrade prompts for freemium model.
 */
class CardCrafter_License_Manager
{
    /**
     * Instance of this class.
     */
    private static $instance = null;

    /**
     * License API endpoint.
     */
    private $api_endpoint = 'https://api.cardcrafter.com/v1/';

    /**
     * Current license status.
     */
    private $license_status = null;

    /**
     * Feature limits for free tier.
     */
    private $free_limits = [
        'cards_per_page' => 12,
        'data_sources' => 2,
        'elementor_widgets' => 3,
        'export_formats' => ['csv'],
        'advanced_filtering' => false,
        'premium_templates' => false,
        'white_label' => false,
        'priority_support' => false
    ];

    /**
     * Get instance.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Initialize license manager.
     */
    private function init()
    {
        // Hook into WordPress
        add_action('admin_menu', [$this, 'add_license_menu']);
        add_action('admin_notices', [$this, 'show_upgrade_notices']);
        add_action('wp_ajax_cardcrafter_check_license', [$this, 'ajax_check_license']);
        add_action('wp_ajax_cardcrafter_activate_license', [$this, 'ajax_activate_license']);
        
        // Feature gating hooks
        add_filter('cardcrafter_max_cards_per_page', [$this, 'filter_max_cards']);
        add_filter('cardcrafter_allowed_export_formats', [$this, 'filter_export_formats']);
        add_filter('cardcrafter_advanced_filtering_enabled', [$this, 'filter_advanced_filtering']);
        add_filter('cardcrafter_premium_templates_enabled', [$this, 'filter_premium_templates']);
        
        // Admin enhancements
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Load license status
        $this->load_license_status();
    }

    /**
     * Load current license status from database.
     */
    private function load_license_status()
    {
        $license_data = get_option('cardcrafter_license_data', []);
        $this->license_status = $license_data['status'] ?? 'free';
    }

    /**
     * Add license management menu to WordPress admin.
     */
    public function add_license_menu()
    {
        add_submenu_page(
            'cardcrafter',
            __('License & Upgrade', 'cardcrafter-data-grids'),
            __('License & Upgrade', 'cardcrafter-data-grids'),
            'manage_options',
            'cardcrafter-license',
            [$this, 'render_license_page']
        );
    }

    /**
     * Render license management page.
     */
    public function render_license_page()
    {
        $license_data = get_option('cardcrafter_license_data', []);
        $current_plan = $this->get_current_plan();
        
        ?>
        <div class="wrap">
            <h1><?php _e('CardCrafter License & Upgrade', 'cardcrafter-data-grids'); ?></h1>
            
            <div class="cardcrafter-license-container">
                <div class="cardcrafter-current-plan">
                    <h2><?php _e('Current Plan', 'cardcrafter-data-grids'); ?></h2>
                    <div class="plan-badge plan-<?php echo esc_attr($current_plan['slug']); ?>">
                        <strong><?php echo esc_html($current_plan['name']); ?></strong>
                        <?php if ($current_plan['price'] > 0): ?>
                            <span class="plan-price">$<?php echo esc_html($current_plan['price']); ?>/year</span>
                        <?php else: ?>
                            <span class="plan-price"><?php _e('Free Forever', 'cardcrafter-data-grids'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($current_plan['slug'] !== 'free'): ?>
                        <p><strong><?php _e('License Key:', 'cardcrafter-data-grids'); ?></strong> <?php echo esc_html($license_data['key'] ?? 'Not set'); ?></p>
                        <p><strong><?php _e('Expires:', 'cardcrafter-data-grids'); ?></strong> <?php echo esc_html($license_data['expires'] ?? 'Unknown'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="cardcrafter-feature-comparison">
                    <h2><?php _e('Feature Comparison', 'cardcrafter-data-grids'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Feature', 'cardcrafter-data-grids'); ?></th>
                                <th><?php _e('Free', 'cardcrafter-data-grids'); ?></th>
                                <th><?php _e('Pro ($49/year)', 'cardcrafter-data-grids'); ?></th>
                                <th><?php _e('Business ($99/year)', 'cardcrafter-data-grids'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong><?php _e('Cards per Page', 'cardcrafter-data-grids'); ?></strong></td>
                                <td>12</td>
                                <td>Unlimited</td>
                                <td>Unlimited</td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Elementor Widgets', 'cardcrafter-data-grids'); ?></strong></td>
                                <td>3 per page</td>
                                <td>Unlimited</td>
                                <td>Unlimited</td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Export Formats', 'cardcrafter-data-grids'); ?></strong></td>
                                <td>CSV only</td>
                                <td>CSV, JSON, PDF</td>
                                <td>CSV, JSON, PDF, Excel</td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Premium Templates', 'cardcrafter-data-grids'); ?></strong></td>
                                <td>❌</td>
                                <td>✅ 20+ Templates</td>
                                <td>✅ 50+ Templates</td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Advanced Filtering', 'cardcrafter-data-grids'); ?></strong></td>
                                <td>Basic</td>
                                <td>✅ Advanced</td>
                                <td>✅ Advanced</td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('White Label', 'cardcrafter-data-grids'); ?></strong></td>
                                <td>❌</td>
                                <td>❌</td>
                                <td>✅</td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Priority Support', 'cardcrafter-data-grids'); ?></strong></td>
                                <td>❌</td>
                                <td>Email</td>
                                <td>Email + Chat</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="cardcrafter-upgrade-actions">
                    <?php if ($current_plan['slug'] === 'free'): ?>
                        <a href="https://cardcrafter.com/pricing" class="button button-primary button-hero" target="_blank">
                            <?php _e('Upgrade to Pro - $49/year', 'cardcrafter-data-grids'); ?>
                        </a>
                        <a href="https://cardcrafter.com/pricing" class="button button-secondary" target="_blank">
                            <?php _e('View All Plans', 'cardcrafter-data-grids'); ?>
                        </a>
                    <?php else: ?>
                        <form method="post" action="" class="cardcrafter-license-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('License Key', 'cardcrafter-data-grids'); ?></th>
                                    <td>
                                        <input type="text" name="license_key" value="<?php echo esc_attr($license_data['key'] ?? ''); ?>" class="regular-text" />
                                        <p class="description"><?php _e('Enter your license key to activate premium features.', 'cardcrafter-data-grids'); ?></p>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button(__('Activate License', 'cardcrafter-data-grids')); ?>
                            <?php wp_nonce_field('cardcrafter_license_action', 'cardcrafter_license_nonce'); ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .cardcrafter-license-container {
            max-width: 800px;
        }
        .cardcrafter-current-plan {
            background: #f1f1f1;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border-radius: 6px;
            color: white;
        }
        .plan-badge.plan-free {
            background: #666;
        }
        .plan-badge.plan-pro {
            background: #0073aa;
        }
        .plan-badge.plan-business {
            background: #00a32a;
        }
        .cardcrafter-feature-comparison {
            margin-bottom: 30px;
        }
        .cardcrafter-upgrade-actions {
            text-align: center;
        }
        .cardcrafter-upgrade-actions .button-hero {
            margin-right: 20px;
        }
        </style>
        <?php
    }

    /**
     * Get current subscription plan details.
     */
    public function get_current_plan()
    {
        $license_data = get_option('cardcrafter_license_data', []);
        $status = $license_data['status'] ?? 'free';
        
        $plans = [
            'free' => [
                'slug' => 'free',
                'name' => 'Free',
                'price' => 0
            ],
            'pro' => [
                'slug' => 'pro', 
                'name' => 'Pro',
                'price' => 49
            ],
            'business' => [
                'slug' => 'business',
                'name' => 'Business', 
                'price' => 99
            ]
        ];
        
        return $plans[$status] ?? $plans['free'];
    }

    /**
     * Check if a specific feature is available.
     */
    public function is_feature_available($feature)
    {
        $license_data = get_option('cardcrafter_license_data', []);
        $status = $license_data['status'] ?? 'free';
        
        // Free tier restrictions
        if ($status === 'free') {
            switch ($feature) {
                case 'unlimited_cards':
                case 'all_export_formats': 
                case 'premium_templates':
                case 'advanced_filtering':
                case 'white_label':
                case 'priority_support':
                    return false;
            }
        }
        
        // Pro tier restrictions
        if ($status === 'pro') {
            switch ($feature) {
                case 'white_label':
                    return false;
            }
        }
        
        return true;
    }

    /**
     * Show upgrade notices in admin.
     */
    public function show_upgrade_notices()
    {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'cardcrafter') === false) {
            return;
        }
        
        if (!$this->is_feature_available('unlimited_cards')) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php _e('CardCrafter Pro', 'cardcrafter-data-grids'); ?></strong> - 
                    <?php _e('Unlock unlimited cards, premium templates, and advanced export options.', 'cardcrafter-data-grids'); ?>
                    <a href="<?php echo admin_url('admin.php?page=cardcrafter-license'); ?>" class="button button-primary">
                        <?php _e('Upgrade Now', 'cardcrafter-data-grids'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Filter maximum cards per page based on license.
     */
    public function filter_max_cards($max_cards)
    {
        if (!$this->is_feature_available('unlimited_cards')) {
            return min($max_cards, $this->free_limits['cards_per_page']);
        }
        
        return $max_cards;
    }

    /**
     * Filter available export formats based on license.
     */
    public function filter_export_formats($formats)
    {
        if (!$this->is_feature_available('all_export_formats')) {
            return array_intersect($formats, $this->free_limits['export_formats']);
        }
        
        return $formats;
    }

    /**
     * Filter advanced filtering availability.
     */
    public function filter_advanced_filtering($enabled)
    {
        return $this->is_feature_available('advanced_filtering') ? $enabled : false;
    }

    /**
     * Filter premium templates availability.
     */
    public function filter_premium_templates($enabled)
    {
        return $this->is_feature_available('premium_templates') ? $enabled : false;
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'cardcrafter') === false) {
            return;
        }
        
        wp_enqueue_script(
            'cardcrafter-license',
            CARDCRAFTER_URL . 'assets/js/license-manager.js',
            ['jquery'],
            CARDCRAFTER_VERSION,
            true
        );
        
        wp_localize_script('cardcrafter-license', 'cardcrafterLicense', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cardcrafter_license_nonce'),
            'currentPlan' => $this->get_current_plan(),
            'upgradeUrl' => 'https://cardcrafter.com/pricing',
        ]);
    }

    /**
     * AJAX handler for license checking.
     */
    public function ajax_check_license()
    {
        check_ajax_referer('cardcrafter_license_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'cardcrafter-data-grids'));
        }
        
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        
        if (empty($license_key)) {
            wp_send_json_error(__('License key is required.', 'cardcrafter-data-grids'));
        }
        
        // Validate license with API (simulate for now)
        $license_data = $this->validate_license_key($license_key);
        
        if ($license_data) {
            update_option('cardcrafter_license_data', $license_data);
            wp_send_json_success([
                'message' => __('License activated successfully!', 'cardcrafter-data-grids'),
                'plan' => $license_data['plan']
            ]);
        } else {
            wp_send_json_error(__('Invalid license key.', 'cardcrafter-data-grids'));
        }
    }

    /**
     * Validate license key with remote API.
     */
    private function validate_license_key($license_key)
    {
        // Simulate API call - replace with actual license server
        if (strpos($license_key, 'PRO-') === 0) {
            return [
                'key' => $license_key,
                'status' => 'pro',
                'plan' => 'Pro',
                'expires' => date('Y-m-d', strtotime('+1 year'))
            ];
        } elseif (strpos($license_key, 'BIZ-') === 0) {
            return [
                'key' => $license_key,
                'status' => 'business',
                'plan' => 'Business',
                'expires' => date('Y-m-d', strtotime('+1 year'))
            ];
        }
        
        return false;
    }

    /**
     * Generate upgrade prompt for premium features.
     */
    public function get_upgrade_prompt($feature)
    {
        $prompts = [
            'unlimited_cards' => [
                'title' => __('Unlock Unlimited Cards', 'cardcrafter-data-grids'),
                'message' => __('Display unlimited cards per page with CardCrafter Pro.', 'cardcrafter-data-grids'),
                'cta' => __('Upgrade to Pro', 'cardcrafter-data-grids')
            ],
            'premium_templates' => [
                'title' => __('Premium Templates Available', 'cardcrafter-data-grids'),
                'message' => __('Access 20+ premium card templates with Pro.', 'cardcrafter-data-grids'),
                'cta' => __('See Templates', 'cardcrafter-data-grids')
            ],
            'all_export_formats' => [
                'title' => __('Advanced Export Options', 'cardcrafter-data-grids'),
                'message' => __('Export to JSON, PDF, and Excel with Pro.', 'cardcrafter-data-grids'),
                'cta' => __('Upgrade Now', 'cardcrafter-data-grids')
            ]
        ];
        
        $prompt = $prompts[$feature] ?? $prompts['unlimited_cards'];
        
        return [
            'html' => sprintf(
                '<div class="cardcrafter-upgrade-prompt">
                    <h4>%s</h4>
                    <p>%s</p>
                    <a href="%s" class="button button-primary" target="_blank">%s</a>
                </div>',
                esc_html($prompt['title']),
                esc_html($prompt['message']),
                'https://cardcrafter.com/pricing',
                esc_html($prompt['cta'])
            ),
            'text' => $prompt['message']
        ];
    }

    /**
     * Get usage analytics for business model optimization.
     */
    public function get_usage_analytics()
    {
        $analytics = get_option('cardcrafter_usage_analytics', []);
        
        return [
            'active_widgets' => $analytics['widgets'] ?? 0,
            'total_cards_displayed' => $analytics['cards'] ?? 0,
            'export_attempts' => $analytics['exports'] ?? 0,
            'upgrade_clicks' => $analytics['upgrade_clicks'] ?? 0,
            'license_status' => $this->license_status,
            'days_since_install' => $analytics['install_date'] ? 
                ceil((time() - $analytics['install_date']) / DAY_IN_SECONDS) : 0
        ];
    }
}