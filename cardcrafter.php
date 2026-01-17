<?php
/**
 * Plugin Name: CardCrafter – Data-Driven Card Grids
 * Plugin URI: https://github.com/TableCrafter/cardcrafter-data-grids
 * Description: Transform JSON data into beautiful, responsive card grids. Perfect for team directories, product showcases, and portfolio displays.
 * Version: 1.12.0
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

define('CARDCRAFTER_VERSION', '1.12.0');
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
        add_option('cc_show_activation_notice', true);
        add_option('cc_do_activation_redirect', true);
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
     * Show activation notice on admin page.
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
        
        ?>
        <div class="notice notice-success is-dismissible" id="cc-activation-notice">
            <p><strong>🎉 CardCrafter Activated Successfully!</strong></p>
            <p>Welcome to CardCrafter! Try the Quick Start demos below to see how easy it is to create beautiful card layouts from any JSON data source.</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#cc-activation-notice').on('click', '.notice-dismiss', function() {
                $.post(ajaxurl, {
                    action: 'cc_dismiss_activation_notice',
                    nonce: '<?php echo wp_create_nonce('cc_dismiss_notice'); ?>'
                });
            });
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
        if (!empty($atts['wp_query']) || (!empty($atts['post_type']) && empty($atts['source']))) {
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
            data-config='<?php echo esc_attr(wp_json_encode($config)); ?>'>
            <?php if ($demo_mode): ?>
                <div class="cardcrafter-demo-banner">
                    <div class="cardcrafter-demo-content">
                        <span class="cardcrafter-demo-badge">🚀 Demo Mode</span>
                        <p>This is sample team data. <strong><a href="#" class="cardcrafter-try-own-data">Try Your Own Data →</a></strong></p>
                    </div>
                </div>
            <?php endif; ?>
            <div class="cardcrafter-loading">
                <div class="cardcrafter-spinner"></div>
                <p><?php esc_html_e('Loading CardCrafter...', 'cardcrafter-data-grids'); ?></p>
            </div>
        </div>
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
     * Render WordPress native data as cards
     */
    public function render_wordpress_data($atts)
    {
        // Build WP_Query arguments
        $query_args = array(
            'post_type' => $atts['post_type'],
            'posts_per_page' => $atts['posts_per_page'],
            'post_status' => 'publish',
            'meta_query' => array(),
            'tax_query' => array()
        );

        // Parse custom wp_query string (e.g., "category=news&author=5")
        if (!empty($atts['wp_query'])) {
            parse_str($atts['wp_query'], $custom_args);
            $query_args = array_merge($query_args, $custom_args);
        }

        // Execute WordPress query
        $posts = get_posts($query_args);
        
        if (empty($posts)) {
            return '<div class="cardcrafter-no-results"><p>No WordPress posts found matching your criteria.</p></div>';
        }

        // Convert WordPress posts to CardCrafter data format
        $card_data = array();
        foreach ($posts as $post) {
            $featured_image = get_the_post_thumbnail_url($post->ID, 'medium');
            
            $card_item = array(
                'id' => $post->ID,
                'title' => get_the_title($post->ID),
                'subtitle' => get_the_date('F j, Y', $post->ID),
                'description' => wp_trim_words(get_the_excerpt($post->ID), 20, '...'),
                'link' => get_permalink($post->ID),
                'image' => $featured_image ?: $this->get_placeholder_image(get_the_title($post->ID)),
                'post_type' => $post->post_type,
                'author' => get_the_author_meta('display_name', $post->post_author)
            );

            // Add custom fields support
            $custom_fields = get_fields($post->ID); // ACF support
            if ($custom_fields) {
                $card_item = array_merge($card_item, $custom_fields);
            }

            $card_data[] = $card_item;
        }

        // Convert to JSON for JavaScript
        $json_data = wp_json_encode($card_data);
        $wp_data_mode = true;

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
            data-config='<?php echo esc_attr(wp_json_encode($config)); ?>'>
            <div class="cardcrafter-wp-banner">
                <div class="cardcrafter-wp-content">
                    <span class="cardcrafter-wp-badge">📝 WordPress Data</span>
                    <p>Showing <?php echo count($card_data); ?> <?php echo esc_html($atts['post_type']); ?>(s) from your site</p>
                </div>
            </div>
            <div class="cardcrafter-loading">
                <div class="cardcrafter-spinner"></div>
                <p><?php esc_html_e('Loading WordPress content...', 'cardcrafter-data-grids'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
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
}

// Register activation hook
register_activation_hook(__FILE__, array('CardCrafter', 'activate'));

// Initialize
if (!defined('WP_INT_TEST')) {
    CardCrafter::get_instance();
}
