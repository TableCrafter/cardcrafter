<?php
/**
 * Plugin Name: CardCrafter – JSON to Card Layouts
 * Plugin URI: https://github.com/fahdi/cardcrafter
 * Description: Transform JSON data into beautiful, responsive card grids. Perfect for team directories, product showcases, and portfolio displays.
 * Version: 1.0.0
 * Author: Fahd Murtaza
 * Author URI: https://github.com/fahdi
 * License: GPLv2 or later
 * Text Domain: cardcrafter
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CARDCRAFTER_VERSION', '1.0.0');
define('CARDCRAFTER_URL', plugin_dir_url(__FILE__));
define('CARDCRAFTER_PATH', plugin_dir_path(__FILE__));

class CardCrafter {
    
    private static $instance = null;
    
    /**
     * Get singleton instance.
     * 
     * @return CardCrafter
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor.
     */
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        add_shortcode('cardcrafter', array($this, 'render_cards'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     * Add admin menu page.
     */
    public function add_admin_menu() {
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
    public function render_admin_page() {
        // Enqueue assets for the preview
        wp_enqueue_script('cardcrafter-lib');
        wp_enqueue_style('cardcrafter-style');
        
        $team_url = CARDCRAFTER_URL . 'demo-data/team.json';
        $products_url = CARDCRAFTER_URL . 'demo-data/products.json';
        $portfolio_url = CARDCRAFTER_URL . 'demo-data/portfolio.json';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('CardCrafter', 'cardcrafter'); ?></h1>
            <p><?php esc_html_e('Transform JSON data into beautiful card layouts.', 'cardcrafter'); ?></p>
            <hr class="wp-header-end">

            <div class="cc-admin-layout" style="display: flex; gap: 20px; margin-top: 20px; align-items: flex-start;">
                
                <!-- Sidebar Controls -->
                <div class="cc-sidebar" style="flex: 0 0 350px;">
                    <!-- Configuration Card -->
                    <div class="card" style="margin: 0 0 20px 0; max-width: none;">
                        <h2><?php esc_html_e('Settings', 'cardcrafter'); ?></h2>
                        <div style="margin-bottom: 15px;">
                            <label for="cc-preview-url" style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Data Source URL', 'cardcrafter'); ?></label>
                            <input type="text" id="cc-preview-url" class="widefat" placeholder="https://api.example.com/data.json">
                            <p class="description"><?php esc_html_e('Must be a publicly accessible JSON endpoint.', 'cardcrafter'); ?></p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="cc-layout" style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Layout Style', 'cardcrafter'); ?></label>
                            <select id="cc-layout" class="widefat">
                                <option value="grid"><?php esc_html_e('Grid (Default)', 'cardcrafter'); ?></option>
                                <option value="masonry"><?php esc_html_e('Masonry', 'cardcrafter'); ?></option>
                                <option value="list"><?php esc_html_e('List View', 'cardcrafter'); ?></option>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label for="cc-columns" style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e('Columns', 'cardcrafter'); ?></label>
                            <select id="cc-columns" class="widefat">
                                <option value="2">2</option>
                                <option value="3" selected>3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button id="cc-preview-btn" class="button button-primary button-large" style="flex: 1;"><?php esc_html_e('Preview Cards', 'cardcrafter'); ?></button>
                        </div>
                    </div>

                    <!-- Usage info -->
                     <div class="card" style="margin: 0 0 20px 0; max-width: none;">
                        <h2><?php esc_html_e('Usage', 'cardcrafter'); ?></h2>
                        <p><?php esc_html_e('Copy the shortcode below to use these cards:', 'cardcrafter'); ?></p>
                        <code id="cc-shortcode-display" style="display: block; padding: 10px; background: #f0f0f1; margin: 10px 0; font-size: 12px; word-break: break-all;">[cardcrafter source="..."]</code>
                        <button id="cc-copy-shortcode" class="button button-secondary" style="width: 100%;"><?php esc_html_e('Copy Shortcode', 'cardcrafter'); ?></button>
                     </div>

                    <!-- Demos -->
                    <div class="card" style="margin: 0; max-width: none;">
                        <h2><?php esc_html_e('Quick Demos', 'cardcrafter'); ?></h2>
                        <p><?php esc_html_e('Click a dataset to load:', 'cardcrafter'); ?></p>
                        <ul class="cc-demo-links" style="margin: 0;">
                            <li style="margin-bottom: 8px;"><a href="#" class="button" style="width: 100%; text-align: left;" data-url="<?php echo esc_url($team_url); ?>">👥 <?php esc_html_e('Team Directory', 'cardcrafter'); ?></a></li>
                            <li style="margin-bottom: 8px;"><a href="#" class="button" style="width: 100%; text-align: left;" data-url="<?php echo esc_url($products_url); ?>">🛍️ <?php esc_html_e('Product Showcase', 'cardcrafter'); ?></a></li>
                            <li style="margin-bottom: 0;"><a href="#" class="button" style="width: 100%; text-align: left;" data-url="<?php echo esc_url($portfolio_url); ?>">🎨 <?php esc_html_e('Portfolio Gallery', 'cardcrafter'); ?></a></li>
                        </ul>
                    </div>
                </div>

                <!-- Main Preview Area -->
                <div class="cc-preview-area" style="flex: 1; min-width: 0;">
                    <div class="card" style="margin: 0; max-width: none; min-height: 500px; display: flex; flex-direction: column;">
                        <h2 style="border-bottom: 1px solid #f0f0f1; padding-bottom: 15px; margin-bottom: 15px; margin-top: 0;"><?php esc_html_e('Live Preview', 'cardcrafter'); ?></h2>
                        
                        <div id="cc-preview-wrap" style="flex: 1; overflow: auto; background: #f9f9f9; padding: 20px; border-radius: 4px;">
                            <div id="cc-preview-container" style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                                <div style="text-align: center;">
                                    <span class="dashicons dashicons-grid-view" style="font-size: 48px; width: 48px; height: 48px; color: #ddd;"></span>
                                    <p><?php esc_html_e('Select a demo or enter a URL to generate cards.', 'cardcrafter'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const urlInput = document.getElementById('cc-preview-url');
                const layoutSelect = document.getElementById('cc-layout');
                const columnsSelect = document.getElementById('cc-columns');
                const previewBtn = document.getElementById('cc-preview-btn');
                const copyBtn = document.getElementById('cc-copy-shortcode');
                const shortcodeDisplay = document.getElementById('cc-shortcode-display');
                const container = document.getElementById('cc-preview-container');
                const demoLinks = document.querySelectorAll('.cc-demo-links a');

                // Update shortcode display
                function updateShortcode() {
                    const url = urlInput.value.trim() || 'URL';
                    const layout = layoutSelect.value;
                    const columns = columnsSelect.value;
                    shortcodeDisplay.innerText = `[cardcrafter source="${url}" layout="${layout}" columns="${columns}"]`;
                }

                urlInput.addEventListener('input', updateShortcode);
                layoutSelect.addEventListener('change', updateShortcode);
                columnsSelect.addEventListener('change', updateShortcode);

                // Load demo URL on click
                demoLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        urlInput.value = this.dataset.url;
                        updateShortcode();
                        previewBtn.click();
                    });
                });

                // Preview functionality
                previewBtn.addEventListener('click', function() {
                    const url = urlInput.value.trim();
                    if (!url) {
                        alert('<?php echo esc_js(__('Please enter a valid URL', 'cardcrafter')); ?>');
                        return;
                    }

                    // Reset container
                    container.innerHTML = '';
                    container.style.display = 'block';
                    
                    if (typeof CardCrafter !== 'undefined') {
                        const cardId = 'cc-preview-' + Date.now();
                        container.innerHTML = `<div id="${cardId}" class="cardcrafter-container"><?php esc_html_e('Loading cards...', 'cardcrafter'); ?></div>`;
                        
                        new CardCrafter({
                            selector: '#' + cardId,
                            source: url,
                            layout: layoutSelect.value,
                            columns: parseInt(columnsSelect.value)
                        });
                    } else {
                        container.innerHTML = '<div class="notice notice-error inline"><p><?php esc_html_e('CardCrafter library not loaded.', 'cardcrafter'); ?></p></div>';
                    }
                });

                // Copy shortcode functionality
                copyBtn.addEventListener('click', function() {
                    const text = shortcodeDisplay.innerText;
                    
                    const copyToClipboard = async (text) => {
                        try {
                            if (navigator.clipboard && window.isSecureContext) {
                                await navigator.clipboard.writeText(text);
                            } else {
                                throw new Error('Clipboard API unavailable');
                            }
                        } catch (err) {
                            const textArea = document.createElement("textarea");
                            textArea.value = text;
                            textArea.style.position = "fixed";
                            textArea.style.left = "-9999px";
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();
                            try {
                                document.execCommand('copy');
                                textArea.remove();
                            } catch (e) {
                                console.error('Copy failed', e);
                                textArea.remove();
                                alert('<?php echo esc_js(__('Failed to copy to clipboard. Please copy manually.', 'cardcrafter')); ?>');
                                return;
                            }
                        }
                        
                        const originalText = copyBtn.innerText;
                        copyBtn.innerText = '<?php echo esc_js(__('Copied!', 'cardcrafter')); ?>';
                        setTimeout(() => copyBtn.innerText = originalText, 2000);
                    };

                    copyToClipboard(text);
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Register frontend assets.
     */
    public function register_assets() {
        wp_register_script(
            'cardcrafter-lib',
            CARDCRAFTER_URL . 'assets/js/cardcrafter.js',
            array(),
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
    public function render_cards($atts) {
        $atts = shortcode_atts(array(
            'source' => '',
            'id' => 'cc-' . uniqid(),
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
        
        // Enqueue assets only when shortcode is used
        wp_enqueue_script('cardcrafter-lib');
        wp_enqueue_style('cardcrafter-style');
        
        // Build config object
        $config = array(
            'source' => $atts['source'],
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
        
        // Output container
        ob_start();
        ?>
        <div id="<?php echo esc_attr($atts['id']); ?>" class="cardcrafter-container" data-config='<?php echo esc_attr(wp_json_encode($config)); ?>'>
            <?php esc_html_e('Loading CardCrafter...', 'cardcrafter'); ?>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof CardCrafter !== 'undefined') {
                    new CardCrafter({
                        selector: '#<?php echo esc_js($atts['id']); ?>',
                        source: '<?php echo esc_js($atts['source']); ?>',
                        layout: '<?php echo esc_js($atts['layout']); ?>',
                        columns: <?php echo esc_js($atts['columns']); ?>,
                        fields: <?php echo wp_json_encode($config['fields']); ?>
                    });
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize
CardCrafter::get_instance();
