<?php
/**
 * Plugin Name: CardCrafter – Data-Driven Card Grids
 * Plugin URI: https://github.com/TableCrafter/cardcrafter-data-grids
 * Description: Transform JSON data into beautiful, responsive card grids. Perfect for team directories, product showcases, and portfolio displays.
 * Version: 1.7.0
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

define('CARDCRAFTER_VERSION', '1.7.0');
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
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('CardCrafter', 'cardcrafter-data-grids'); ?></h1>
            <p><?php esc_html_e('Transform JSON data into beautiful card layouts.', 'cardcrafter-data-grids'); ?></p>
            <hr class="wp-header-end">

            <div class="cardcrafter-admin-layout" style="display: flex; gap: 20px; margin-top: 20px; align-items: flex-start;">

                <!-- Sidebar Controls -->
                <div class="cardcrafter-sidebar" style="flex: 0 0 350px;">
                    <!-- Configuration Card -->
                    <div class="card" style="margin: 0 0 20px 0; max-width: none;">
                        <h2><?php esc_html_e('Settings', 'cardcrafter-data-grids'); ?></h2>
                        <div style="margin-bottom: 15px;">
                            <label for="cardcrafter-preview-url"
                                style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Data Source URL', 'cardcrafter-data-grids'); ?></label>
                            <input type="text" id="cardcrafter-preview-url" class="widefat"
                                placeholder="https://api.example.com/data.json">
                            <p class="description">
                                <?php esc_html_e('Must be a publicly accessible JSON endpoint.', 'cardcrafter-data-grids'); ?>
                            </p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="cardcrafter-layout"
                                style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Layout Style', 'cardcrafter-data-grids'); ?></label>
                            <select id="cardcrafter-layout" class="widefat">
                                <option value="grid"><?php esc_html_e('Grid (Default)', 'cardcrafter-data-grids'); ?></option>
                                <option value="masonry"><?php esc_html_e('Masonry', 'cardcrafter-data-grids'); ?></option>
                                <option value="list"><?php esc_html_e('List View', 'cardcrafter-data-grids'); ?></option>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="cardcrafter-columns"
                                style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Columns', 'cardcrafter-data-grids'); ?></label>
                            <select id="cardcrafter-columns" class="widefat">
                                <option value="2">2</option>
                                <option value="3" selected>3</option>
                                <option value="4">4</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button id="cardcrafter-preview-btn" class="button button-primary button-large"
                                style="flex: 1;"><?php esc_html_e('Preview Cards', 'cardcrafter-data-grids'); ?></button>
                        </div>
                    </div>

                    <!-- Usage info -->
                    <div class="card" style="margin: 0 0 20px 0; max-width: none;">
                        <h2><?php esc_html_e('Usage', 'cardcrafter-data-grids'); ?></h2>
                        <p><?php esc_html_e('Copy the shortcode below to use these cards:', 'cardcrafter-data-grids'); ?></p>
                        <code id="cardcrafter-shortcode-display"
                            style="display: block; padding: 10px; background: #f0f0f1; margin: 10px 0; font-size: 12px; word-break: break-all;">[cardcrafter-data-grids source="..."]</code>
                        <button id="cardcrafter-copy-shortcode" class="button button-secondary"
                            style="width: 100%;"><?php esc_html_e('Copy Shortcode', 'cardcrafter-data-grids'); ?></button>
                    </div>

                    <!-- Demos -->
                    <div class="card" style="margin: 0; max-width: none;">
                        <h2><?php esc_html_e('Quick Demos', 'cardcrafter-data-grids'); ?></h2>
                        <p><?php esc_html_e('Click a dataset to load:', 'cardcrafter-data-grids'); ?></p>
                        <ul class="cardcrafter-demo-links" style="margin: 0;">
                            <li style="margin-bottom: 8px;"><a href="#" class="button" style="width: 100%; text-align: left;"
                                    data-url="<?php echo esc_url($team_url); ?>">👥
                                    <?php esc_html_e('Team Directory', 'cardcrafter-data-grids'); ?></a></li>
                            <li style="margin-bottom: 8px;"><a href="#" class="button" style="width: 100%; text-align: left;"
                                    data-url="<?php echo esc_url($products_url); ?>">🛍️
                                    <?php esc_html_e('Product Showcase', 'cardcrafter-data-grids'); ?></a></li>
                            <li style="margin-bottom: 8px;"><a href="#" class="button" style="width: 100%; text-align: left;"
                                    data-url="<?php echo esc_url($portfolio_url); ?>">🎨
                                    <?php esc_html_e('Portfolio Gallery', 'cardcrafter-data-grids'); ?></a></li>
                            <li style="margin-bottom: 0;"><a href="#" class="button" style="width: 100%; text-align: left;"
                                    data-url="<?php echo esc_url(CARDCRAFTER_URL . 'demo-data/acf-examples.json'); ?>">📝
                                    <?php esc_html_e('WordPress + ACF Examples', 'cardcrafter-data-grids'); ?></a></li>
                        </ul>
                    </div>
                </div>

                <!-- Main Preview Area -->
                <div class="cardcrafter-preview-area" style="flex: 1; min-width: 0;">
                    <div class="card"
                        style="margin: 0; max-width: none; min-height: 500px; display: flex; flex-direction: column;">
                        <h2 style="border-bottom: 1px solid #f0f0f1; padding-bottom: 15px; margin-bottom: 15px; margin-top: 0;">
                            <?php esc_html_e('Live Preview', 'cardcrafter-data-grids'); ?>
                        </h2>

                        <div id="cardcrafter-preview-wrap"
                            style="flex: 1; overflow: auto; background: #f9f9f9; padding: 20px; border-radius: 4px;">
                            <div id="cardcrafter-preview-container"
                                style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                                <div style="text-align: center;">
                                    <span class="dashicons dashicons-grid-view"
                                        style="font-size: 48px; width: 48px; height: 48px; color: #ddd;"></span>
                                    <p><?php esc_html_e('Select a demo or enter a URL to generate cards.', 'cardcrafter-data-grids'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WordPress Integration Examples -->
            <div class="card" style="margin-top: 20px;">
                <h2>🚀 WordPress Native Integration Examples</h2>
                <p>CardCrafter v1.6.0 introduces WordPress native data support. Use these shortcodes to display your WordPress content as cards:</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                        <h3 style="margin: 0 0 10px 0; color: #1d2327;">📝 Basic WordPress Posts</h3>
                        <code style="display: block; background: #f6f7f7; padding: 10px; border-radius: 3px; margin: 5px 0;">[cardcrafter-data-grids post_type="post"]</code>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Display blog posts with automatic featured images and excerpts.</p>
                    </div>
                    
                    <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                        <h3 style="margin: 0 0 10px 0; color: #1d2327;">🛍️ WooCommerce Products</h3>
                        <code style="display: block; background: #f6f7f7; padding: 10px; border-radius: 3px; margin: 5px 0;">[cardcrafter-data-grids post_type="product"]</code>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Show products with thumbnails and automatic pricing.</p>
                    </div>
                    
                    <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                        <h3 style="margin: 0 0 10px 0; color: #1d2327;">📂 Category Filtering</h3>
                        <code style="display: block; background: #f6f7f7; padding: 10px; border-radius: 3px; margin: 5px 0;">[cardcrafter-data-grids post_type="post" wp_query="category_name=news"]</code>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Display posts from specific categories.</p>
                    </div>
                    
                    <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                        <h3 style="margin: 0 0 10px 0; color: #1d2327;">👤 Author Filtering</h3>
                        <code style="display: block; background: #f6f7f7; padding: 10px; border-radius: 3px; margin: 5px 0;">[cardcrafter-data-grids post_type="post" wp_query="author=5"]</code>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Show posts from specific authors.</p>
                    </div>
                    
                    <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                        <h3 style="margin: 0 0 10px 0; color: #1d2327;">⭐ Featured Content</h3>
                        <code style="display: block; background: #f6f7f7; padding: 10px; border-radius: 3px; margin: 5px 0;">[cardcrafter-data-grids post_type="product" wp_query="meta_key=featured&meta_value=yes"]</code>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Filter by custom meta fields and values.</p>
                    </div>
                    
                    <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                        <h3 style="margin: 0 0 10px 0; color: #1d2327;">🎯 ACF Integration</h3>
                        <code style="display: block; background: #f6f7f7; padding: 10px; border-radius: 3px; margin: 5px 0;">[cardcrafter-data-grids post_type="team" subtitle_field="job_title"]</code>
                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Use ACF fields as card content automatically.</p>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #e8f4fd; border: 1px solid #72aee6; border-radius: 4px;">
                    <h4 style="margin: 0 0 10px 0; color: #1d2327;">💡 Pro Tips:</h4>
                    <ul style="margin: 0;">
                        <li><strong>ACF Support:</strong> All ACF fields are automatically available - just reference them by field name</li>
                        <li><strong>Featured Images:</strong> WordPress post thumbnails are automatically used as card images</li>
                        <li><strong>Permalinks:</strong> Card links automatically point to the WordPress post/page</li>
                        <li><strong>Complex Queries:</strong> Use any WordPress query parameter in wp_query attribute</li>
                    </ul>
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
}

// Initialize
if (!defined('WP_INT_TEST')) {
    CardCrafter::get_instance();
}
