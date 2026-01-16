<?php
/**
 * CardCrafter Elementor Integration Manager
 * 
 * Handles Elementor plugin integration, widget registration, and compatibility.
 * 
 * Business Impact: Enables seamless Elementor integration for 18+ million Elementor websites.
 * Market Opportunity: First WordPress card plugin with native Elementor widget support.
 * 
 * @since 1.8.0
 * @package CardCrafter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * CardCrafter Elementor Manager Class
 * 
 * Manages all Elementor-related functionality and widget registration.
 */
class CardCrafter_Elementor_Manager
{
    /**
     * Instance of this class.
     */
    private static $instance = null;

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
     * Initialize Elementor integration.
     */
    private function init()
    {
        // Check if Elementor is active
        if (!$this->is_elementor_active()) {
            return;
        }

        // Hook into Elementor
        add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'register_widget_categories']);
        add_action('elementor/frontend/after_register_scripts', [$this, 'register_frontend_scripts']);
        add_action('elementor/frontend/after_register_styles', [$this, 'register_frontend_styles']);
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'register_editor_scripts']);
        
        // Initialize dynamic tags manager
        add_action('elementor/init', [$this, 'init_dynamic_tags_manager']);
        
        // Add plugin action links for Elementor
        add_filter('plugin_action_links_' . plugin_basename(CARDCRAFTER_PATH . 'cardcrafter.php'), [$this, 'add_elementor_action_links']);
    }

    /**
     * Check if Elementor is active and compatible.
     */
    private function is_elementor_active()
    {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            return false;
        }

        // Check minimum Elementor version
        if (!version_compare(ELEMENTOR_VERSION, '3.0.0', '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return false;
        }

        // Check minimum PHP version for Elementor compatibility
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
            return false;
        }

        return true;
    }

    /**
     * Register CardCrafter widgets with Elementor.
     */
    public function register_widgets()
    {
        // Load widget file
        require_once CARDCRAFTER_PATH . 'elementor/class-cardcrafter-elementor-widget.php';

        // Register widget
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new CardCrafter_Elementor_Widget());
    }

    /**
     * Register CardCrafter widget categories.
     */
    public function register_widget_categories($elements_manager)
    {
        $elements_manager->add_category(
            'cardcrafter',
            [
                'title' => __('CardCrafter', 'cardcrafter-data-grids'),
                'icon' => 'fa fa-th-large',
            ]
        );
    }

    /**
     * Initialize dynamic tags manager for Elementor Pro integration.
     */
    public function init_dynamic_tags_manager()
    {
        // Check if Elementor Pro is available
        if (!defined('ELEMENTOR_PRO_VERSION')) {
            return;
        }

        // Load and initialize the dynamic tags manager
        require_once CARDCRAFTER_PATH . 'elementor/class-cardcrafter-dynamic-tags-manager.php';
        CardCrafter_Dynamic_Tags_Manager::get_instance();
    }

    /**
     * Register frontend scripts for Elementor.
     */
    public function register_frontend_scripts()
    {
        // CardCrafter core script
        wp_register_script(
            'cardcrafter-frontend',
            CARDCRAFTER_URL . 'assets/js/cardcrafter.js',
            ['jquery'],
            CARDCRAFTER_VERSION,
            true
        );

        // Elementor-specific frontend script
        wp_register_script(
            'cardcrafter-elementor-frontend',
            CARDCRAFTER_URL . 'assets/js/elementor-frontend.js',
            ['cardcrafter-frontend', 'elementor-frontend'],
            CARDCRAFTER_VERSION,
            true
        );
    }

    /**
     * Register frontend styles for Elementor.
     */
    public function register_frontend_styles()
    {
        // CardCrafter core styles
        wp_register_style(
            'cardcrafter-style',
            CARDCRAFTER_URL . 'assets/css/cardcrafter.css',
            [],
            CARDCRAFTER_VERSION
        );

        // Elementor-specific styles
        wp_register_style(
            'cardcrafter-elementor-style',
            CARDCRAFTER_URL . 'assets/css/elementor.css',
            ['cardcrafter-style'],
            CARDCRAFTER_VERSION
        );
    }

    /**
     * Register editor scripts for Elementor backend.
     */
    public function register_editor_scripts()
    {
        // Elementor editor script for enhanced preview
        wp_enqueue_script(
            'cardcrafter-elementor-editor',
            CARDCRAFTER_URL . 'assets/js/elementor-editor.js',
            ['elementor-editor'],
            CARDCRAFTER_VERSION,
            true
        );

        // Localize script for editor
        wp_localize_script('cardcrafter-elementor-editor', 'cardcrafterElementor', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cardcrafter_elementor_nonce'),
            'i18n' => [
                'previewLoading' => __('Loading preview...', 'cardcrafter-data-grids'),
                'previewError' => __('Preview unavailable', 'cardcrafter-data-grids'),
            ]
        ]);
    }

    /**
     * Add Elementor-related action links to plugin page.
     */
    public function add_elementor_action_links($links)
    {
        if ($this->is_elementor_active()) {
            $elementor_link = '<a href="' . admin_url('post.php?post=' . get_option('elementor_active_kit', 0) . '&action=edit') . '">' . __('Elementor Widgets', 'cardcrafter-data-grids') . '</a>';
            array_unshift($links, $elementor_link);
        }

        return $links;
    }

    /**
     * Admin notice for minimum Elementor version.
     */
    public function admin_notice_minimum_elementor_version()
    {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'cardcrafter-data-grids'),
            '<strong>' . esc_html__('CardCrafter Data Grids', 'cardcrafter-data-grids') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'cardcrafter-data-grids') . '</strong>',
            '3.0.0'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice for minimum PHP version.
     */
    public function admin_notice_minimum_php_version()
    {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: PHP 3: Required PHP version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'cardcrafter-data-grids'),
            '<strong>' . esc_html__('CardCrafter Data Grids', 'cardcrafter-data-grids') . '</strong>',
            '<strong>' . esc_html__('PHP', 'cardcrafter-data-grids') . '</strong>',
            '7.4'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Get Elementor integration status.
     */
    public function get_integration_status()
    {
        return [
            'elementor_active' => $this->is_elementor_active(),
            'elementor_version' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null,
            'widget_registered' => did_action('elementor/widgets/widgets_registered'),
            'category_registered' => did_action('elementor/elements/categories_registered'),
        ];
    }

    /**
     * Check if current page is being edited with Elementor.
     */
    public function is_elementor_editor()
    {
        return \Elementor\Plugin::$instance->editor->is_edit_mode();
    }

    /**
     * Check if current page is Elementor preview.
     */
    public function is_elementor_preview()
    {
        return \Elementor\Plugin::$instance->preview->is_preview_mode();
    }

    /**
     * Get Elementor widget usage analytics.
     */
    public function get_widget_usage_stats()
    {
        global $wpdb;

        // Query to find pages using CardCrafter Elementor widget
        $query = "
            SELECT COUNT(*) as usage_count
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_elementor_data' 
            AND meta_value LIKE '%cardcrafter-data-grids%'
        ";

        $usage_count = $wpdb->get_var($query);

        return [
            'widget_usage_count' => intval($usage_count),
            'last_checked' => current_time('mysql'),
        ];
    }

    /**
     * Enqueue scripts and styles on Elementor frontend.
     */
    public function enqueue_frontend_assets()
    {
        if (\Elementor\Plugin::$instance->frontend->has_elementor_in_page()) {
            wp_enqueue_script('cardcrafter-frontend');
            wp_enqueue_script('cardcrafter-elementor-frontend');
            wp_enqueue_style('cardcrafter-style');
            wp_enqueue_style('cardcrafter-elementor-style');
        }
    }
}