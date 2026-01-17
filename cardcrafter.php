<?php
/**
 * Plugin Name: CardCrafter – Data-Driven Card Grids
 * Plugin URI: https://github.com/TableCrafter/cardcrafter-data-grids
 * Description: Transform JSON data into beautiful, responsive card grids. Perfect for team directories, product showcases, and portfolio displays.
 * Version: 1.10.0
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

define('CARDCRAFTER_VERSION', '1.10.0');
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
        
        // Welcome Screen
        add_action('admin_init', array($this, 'welcome_redirect'));
        
        // Gutenberg Block Support
        add_action('init', array($this, 'register_block'));

        // Secure Proxy Handlers
        add_action('wp_ajax_cardcrafter_proxy_fetch', array($this, 'ajax_proxy_fetch'));
        add_action('wp_ajax_nopriv_cardcrafter_proxy_fetch', array($this, 'ajax_proxy_fetch'));

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
        add_option('cc_do_activation_redirect', true);
    }

    /**
     * Welcome page redirect handler.
     */
    public function welcome_redirect()
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
        wp_safe_redirect(admin_url('admin.php?page=cardcrafter-welcome'));
        exit;
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

        add_submenu_page(
            'cardcrafter',
            __('Welcome to CardCrafter', 'cardcrafter-data-grids'),
            __('Welcome', 'cardcrafter-data-grids'),
            'manage_options',
            'cardcrafter-welcome',
            array($this, 'render_welcome_page')
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
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('CardCrafter', 'cardcrafter-data-grids'); ?></h1>
            <p><?php esc_html_e('Transform JSON data into beautiful card layouts.', 'cardcrafter-data-grids'); ?></p>
            <hr class="wp-header-end">

            <div class="cc-admin-layout" style="display: flex; gap: 20px; margin-top: 20px; align-items: flex-start;">

                <div class="cc-sidebar" style="flex: 0 0 380px;">
                    <div class="card">
                        <h2 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 20px;">🚀</span>
                            <?php esc_html_e('Quick Start Demos', 'cardcrafter-data-grids'); ?>
                        </h2>
                        <p style="margin-bottom: 15px;">
                            <?php esc_html_e('Click any dataset below to instantly load a demo card layout:', 'cardcrafter-data-grids'); ?>
                        </p>
                        <ul class="cc-demo-links" style="margin: 0;">
                            <li style="margin-bottom: 8px;"><a href="#" class="button button-large" style="width: 100%; text-align: left; background: white; border: 1px solid #c3c4c7; color: #1d2327; font-weight: 600; transition: all 0.2s ease;" onmouseover="this.style.background='#0073aa'; this.style.color='white'" onmouseout="this.style.background='white'; this.style.color='#1d2327'"
                                    data-url="<?php echo esc_url($team_url); ?>">👥
                                    <?php esc_html_e('Team Directory Cards', 'cardcrafter-data-grids'); ?></a></li>
                            <li style="margin-bottom: 8px;"><a href="#" class="button button-large" style="width: 100%; text-align: left; background: white; border: 1px solid #c3c4c7; color: #1d2327; font-weight: 600; transition: all 0.2s ease;" onmouseover="this.style.background='#0073aa'; this.style.color='white'" onmouseout="this.style.background='white'; this.style.color='#1d2327'"
                                    data-url="<?php echo esc_url($products_url); ?>">🛍️
                                    <?php esc_html_e('Product Showcase Cards', 'cardcrafter-data-grids'); ?></a></li>
                            <li style="margin-bottom: 0;"><a href="#" class="button button-large" style="width: 100%; text-align: left; background: white; border: 1px solid #c3c4c7; color: #1d2327; font-weight: 600; transition: all 0.2s ease;" onmouseover="this.style.background='#0073aa'; this.style.color='white'" onmouseout="this.style.background='white'; this.style.color='#1d2327'"
                                    data-url="<?php echo esc_url($portfolio_url); ?>">🎨
                                    <?php esc_html_e('Portfolio Gallery Cards', 'cardcrafter-data-grids'); ?></a></li>
                        </ul>
                        <div style="margin-top: 12px; padding: 8px 12px; background: #f0f6fc; border-radius: 3px; border: 1px solid #c3c4c7;">
                            <p style="margin: 0; font-size: 12px; color: #646970; text-align: center;">
                                ↑ <strong>Instant Demo:</strong> No setup required! Each dataset shows different card layouts.
                            </p>
                        </div>
                    </div>

                    <div class="card" style="margin: 0 0 20px 0; max-width: none;">
                        <h2><?php esc_html_e('Settings', 'cardcrafter-data-grids'); ?></h2>
                        <div style="margin-bottom: 15px;">
                            <label for="cc-preview-url"
                                style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Data Source URL', 'cardcrafter-data-grids'); ?></label>

                            <div style="display: flex; gap: 5px; margin-bottom: 8px;">
                                <input type="text" id="cc-preview-url" class="widefat"
                                    placeholder="https://api.example.com/data.json" style="flex: 1;"
                                    value="<?php echo isset($_GET['demo_url']) ? esc_attr($_GET['demo_url']) : ''; ?>">
                            </div>

                            <div style="display: flex; gap: 5px;">
                                <button id="cc-upload-json-btn" class="button button-secondary" type="button" style="flex: 1;">
                                    <span class="dashicons dashicons-upload"
                                        style="margin-right: 4px; vertical-align: middle;"></span>
                                    <?php esc_html_e('Upload JSON File', 'cardcrafter-data-grids'); ?>
                                </button>
                                <button id="cc-wp-posts-btn" class="button button-secondary" type="button" style="flex: 1;"
                                    title="<?php esc_attr_e('Use WordPress posts as cards', 'cardcrafter-data-grids'); ?>">
                                    <span class="dashicons dashicons-wordpress"
                                        style="margin-right: 4px; vertical-align: middle;"></span>
                                    <?php esc_html_e('WP Posts', 'cardcrafter-data-grids'); ?>
                                </button>
                            </div>

                            <p class="description" style="margin-top: 5px;">
                                <?php esc_html_e('Enter a remote URL, upload a JSON file, or use WordPress posts.', 'cardcrafter-data-grids'); ?>
                            </p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="cc-layout"
                                style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Layout Style', 'cardcrafter-data-grids'); ?></label>
                            <select id="cc-layout" class="widefat">
                                <option value="grid"><?php esc_html_e('Grid Layout', 'cardcrafter-data-grids'); ?></option>
                                <option value="masonry"><?php esc_html_e('Masonry Layout', 'cardcrafter-data-grids'); ?></option>
                                <option value="list"><?php esc_html_e('List Layout', 'cardcrafter-data-grids'); ?></option>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px; display: flex; gap: 20px;">
                            <div style="flex: 1;">
                                <label for="cc-columns"
                                    style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Columns', 'cardcrafter-data-grids'); ?></label>
                                <select id="cc-columns" class="widefat">
                                    <option value="2">2 Columns</option>
                                    <option value="3" selected>3 Columns</option>
                                    <option value="4">4 Columns</option>
                                    <option value="5">5 Columns</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 15px;">
                            <label style="font-weight: 600; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" id="cc-enable-search" checked>
                                <?php esc_html_e('Enable Search', 'cardcrafter-data-grids'); ?>
                            </label>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button id="cc-preview-btn" class="button button-primary button-large"
                                style="flex: 1;"><?php esc_html_e('Preview Cards', 'cardcrafter-data-grids'); ?></button>
                        </div>
                    </div>

                    <div class="card" style="margin: 0; max-width: none;">
                        <h2><?php esc_html_e('Usage', 'cardcrafter-data-grids'); ?></h2>
                        <p><?php esc_html_e('Copy the shortcode below to use this card layout:', 'cardcrafter-data-grids'); ?></p>
                        <code id="cc-shortcode-display"
                            style="display: block; padding: 10px; background: #f0f0f1; margin: 10px 0; word-break: break-all;">[cardcrafter source="..."]</code>
                        <button id="cc-copy-shortcode" class="button button-secondary"
                            style="width: 100%;"><?php esc_html_e('Copy Shortcode', 'cardcrafter-data-grids'); ?></button>
                    </div>
                </div>

                <div class="cc-preview-area" style="flex: 1; min-width: 600px; max-width: none;">
                    <div class="card"
                        style="margin: 0; max-width: none; min-height: 500px; display: flex; flex-direction: column;">
                        <h2 style="border-bottom: 1px solid #f0f0f1; padding-bottom: 15px; margin-bottom: 15px; margin-top: 0; display: flex; align-items: center; justify-content: space-between;">
                            <span><?php esc_html_e('Live Preview', 'cardcrafter-data-grids'); ?></span>
                            <small style="font-weight: normal; color: #666; font-size: 13px;">Try search, layouts & interact with cards</small>
                        </h2>

                        <div id="cc-preview-wrap" style="flex: 1; overflow: auto; background: #fff;">
                            <div id="cc-preview-container"
                                style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666; min-height: 400px;">
                                <div style="text-align: center;">
                                    <span class="dashicons dashicons-grid-view"
                                        style="font-size: 48px; width: 48px; height: 48px; color: #ddd;"></span>
                                    <p style="margin: 16px 0 8px; font-size: 16px; color: #333;">
                                        <?php esc_html_e('Ready to generate your cards!', 'cardcrafter-data-grids'); ?>
                                    </p>
                                    <p style="margin: 0; font-size: 14px; color: #666;">
                                        <?php esc_html_e('👈 Click a Quick Start Demo or enter your own URL', 'cardcrafter-data-grids'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
     * Render the welcome page.
     */
    public function render_welcome_page()
    {
        // Enqueue assets for the welcome page
        wp_enqueue_script('cardcrafter-lib');
        wp_enqueue_style('cardcrafter-style');
        
        include CARDCRAFTER_PATH . 'views/welcome.php';
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
