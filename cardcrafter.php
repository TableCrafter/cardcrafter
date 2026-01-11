<?php
/**
 * Plugin Name: CardCrafter – Data-Driven Card Grids
 * Plugin URI: https://github.com/TableCrafter/cardcrafter-data-grids
 * Description: Transform JSON data into beautiful, responsive card grids. Perfect for team directories, product showcases, and portfolio displays.
 * Version: 1.0.0
 * Author: fahdi
 * Author URI: https://github.com/fahdi
 * License: GPLv2 or later
 * Text Domain: cardcrafter
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
Note: Plugin name and slug updated to CardCrafter – Data-Driven Card Grids / cardcrafter-data-grids. 
All functional code remains unchanged. These changes are recommended by an AI and do not replace WordPress.org volunteer review guidance.
*/

define('CARDCRAFTER_VERSION', '1.0.0');
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
            __('CardCrafter', 'cardcrafter'),
            __('CardCrafter', 'cardcrafter'),
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
            <h1 class="wp-heading-inline"><?php esc_html_e('CardCrafter', 'cardcrafter'); ?></h1>
            <p><?php esc_html_e('Transform JSON data into beautiful card layouts.', 'cardcrafter'); ?></p>
            <hr class="wp-header-end">

            <div class="cardcrafter-admin-layout" style="display: flex; gap: 20px; margin-top: 20px; align-items: flex-start;">

                <!-- Sidebar Controls -->
                <div class="cardcrafter-sidebar" style="flex: 0 0 350px;">
                    <!-- Configuration Card -->
                    <div class="card" style="margin: 0 0 20px 0; max-width: none;">
                        <h2><?php esc_html_e('Settings', 'cardcrafter'); ?></h2>
                        <div style="margin-bottom: 15px;">
                            <label for="cardcrafter-preview-url"
                                style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Data Source URL', 'cardcrafter'); ?></label>
                            <input type="text" id="cardcrafter-preview-url" class="widefat"
                                placeholder="https://api.example.com/data.json">
                            <p class="description">
                                <?php esc_html_e('Must be a publicly accessible JSON endpoint.', 'cardcrafter'); ?>
                            </p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="cardcrafter-layout"
                                style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Layout Style', 'cardcrafter'); ?></label>
                            <select id="cardcrafter-layout" class="widefat">
                                <option value="grid"><?php esc_html_e('Grid (Default)', 'cardcrafter'); ?></option>
                                <option value="masonry"><?php esc_html_e('Masonry', 'cardcrafter'); ?></option>
                                <option value="list"><?php esc_html_e('List View', 'cardcrafter'); ?></option>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="cardcrafter-columns"
                                style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Columns', 'cardcrafter'); ?></label>
                            <select id="cardcrafter-columns" class="widefat">
                                <option value="2">2</option>
                                <option value="3" selected>3</option>
                                <option value="4">4</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button id="cardcrafter-preview-btn" class="button button-primary button-large"
                                style="flex: 1;"><?php esc_html_e('Preview Cards', 'cardcrafter'); ?></button>
                        </div>
                    </div>

                    <!-- Usage info -->
                    <div class="card" style="margin: 0 0 20px 0; max-width: none;">
                        <h2><?php esc_html_e('Usage', 'cardcrafter'); ?></h2>
                        <p><?php esc_html_e('Copy the shortcode below to use these cards:', 'cardcrafter'); ?></p>
                        <code id="cardcrafter-shortcode-display"
                            style="display: block; padding: 10px; background: #f0f0f1; margin: 10px 0; font-size: 12px; word-break: break-all;">[cardcrafter-data-grids source="..."]</code>
                        <button id="cardcrafter-copy-shortcode" class="button button-secondary"
                            style="width: 100%;"><?php esc_html_e('Copy Shortcode', 'cardcrafter'); ?></button>
                    </div>

                    <!-- Demos -->
                    <div class="card" style="margin: 0; max-width: none;">
                        <h2><?php esc_html_e('Quick Demos', 'cardcrafter'); ?></h2>
                        <p><?php esc_html_e('Click a dataset to load:', 'cardcrafter'); ?></p>
                        <ul class="cardcrafter-demo-links" style="margin: 0;">
                            <li style="margin-bottom: 8px;"><a href="#" class="button" style="width: 100%; text-align: left;"
                                    data-url="<?php echo esc_url($team_url); ?>">👥
                                    <?php esc_html_e('Team Directory', 'cardcrafter'); ?></a></li>
                            <li style="margin-bottom: 8px;"><a href="#" class="button" style="width: 100%; text-align: left;"
                                    data-url="<?php echo esc_url($products_url); ?>">🛍️
                                    <?php esc_html_e('Product Showcase', 'cardcrafter'); ?></a></li>
                            <li style="margin-bottom: 0;"><a href="#" class="button" style="width: 100%; text-align: left;"
                                    data-url="<?php echo esc_url($portfolio_url); ?>">🎨
                                    <?php esc_html_e('Portfolio Gallery', 'cardcrafter'); ?></a></li>
                        </ul>
                    </div>
                </div>

                <!-- Main Preview Area -->
                <div class="cardcrafter-preview-area" style="flex: 1; min-width: 0;">
                    <div class="card"
                        style="margin: 0; max-width: none; min-height: 500px; display: flex; flex-direction: column;">
                        <h2 style="border-bottom: 1px solid #f0f0f1; padding-bottom: 15px; margin-bottom: 15px; margin-top: 0;">
                            <?php esc_html_e('Live Preview', 'cardcrafter'); ?>
                        </h2>

                        <div id="cardcrafter-preview-wrap"
                            style="flex: 1; overflow: auto; background: #f9f9f9; padding: 20px; border-radius: 4px;">
                            <div id="cardcrafter-preview-container"
                                style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                                <div style="text-align: center;">
                                    <span class="dashicons dashicons-grid-view"
                                        style="font-size: 48px; width: 48px; height: 48px; color: #ddd;"></span>
                                    <p><?php esc_html_e('Select a demo or enter a URL to generate cards.', 'cardcrafter'); ?>
                                    </p>
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
                'validUrl' => __('Please enter a valid URL', 'cardcrafter'),
                'loading' => __('Loading cards...', 'cardcrafter'),
                'libNotLoaded' => __('CardCrafter library not loaded.', 'cardcrafter'),
                'copyFailed' => __('Failed to copy to clipboard. Please copy manually.', 'cardcrafter'),
                'copied' => __('Copied!', 'cardcrafter')
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
            'id' => 'cardcrafter-' . uniqid(),
            'layout' => 'grid',
            'columns' => 3,
            'image_field' => 'image',
            'title_field' => 'title',
            'subtitle_field' => 'subtitle',
            'description_field' => 'description',
            'link_field' => 'link'
        ), $atts, 'cardcrafter');

        // Sanitize inputs
        $atts['source'] = esc_url_raw($atts['source']);
        $atts['layout'] = sanitize_key($atts['layout']);
        $atts['columns'] = absint($atts['columns']);

        if (empty($atts['source'])) {
            return '<p>' . esc_html__('Error: CardCrafter requires a "source" attribute.', 'cardcrafter') . '</p>';
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
            <div class="cardcrafter-loading">
                <div class="cardcrafter-spinner"></div>
                <p><?php esc_html_e('Loading CardCrafter...', 'cardcrafter'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

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

        // 2. Fetch and Unslash URL (Compliance: MissingUnslash)
        $url = isset($_REQUEST['url']) ? esc_url_raw(wp_unslash($_REQUEST['url'])) : '';

        if (empty($url) || !$this->is_safe_url($url)) {
            wp_send_json_error('Invalid or unsafe URL.');
        }

        $cache_key = 'cardcrafter_cache_' . md5($url);
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            wp_send_json_success($cached_data);
        }

        $response = wp_remote_get($url, array('timeout' => 15));
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
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
            $response = wp_remote_get($url, array('timeout' => 10));
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
     * SSRF Prevention Helper.
     */
    private function is_safe_url(string $url): bool
    {
        // Use wp_parse_url for consistency (Compliance: parse_url discourage)
        $url_parts = wp_parse_url($url);
        $host = isset($url_parts['host']) ? $url_parts['host'] : '';

        if (!$host)
            return false;
        if (in_array(strtolower($host), array('localhost', '127.0.0.1', '[::1]')))
            return false;
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $is_private = !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($is_private)
                return false;
        }
        return true;
    }

}

// Initialize
CardCrafter::get_instance();
