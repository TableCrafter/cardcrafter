<?php
/**
 * CardCrafter Elementor Pro Dynamic Tags Manager
 * 
 * Enables integration with Elementor Pro's dynamic content system.
 * Provides dynamic tags for CardCrafter data fields, ACF integration, and custom post types.
 * 
 * Business Impact: Unlocks 18+ million Elementor Pro users and enterprise market segment.
 * Market Opportunity: Enables integration with ACF, Meta Box, Toolset, JetEngine stack.
 * 
 * @since 1.8.0
 * @package CardCrafter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Elementor\Core\DynamicTags\Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Data_Tag;

/**
 * CardCrafter Dynamic Tags Manager Class
 * 
 * Manages registration and handling of dynamic tags for CardCrafter Elementor integration.
 */
class CardCrafter_Dynamic_Tags_Manager
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
     * Initialize dynamic tags integration.
     */
    private function init()
    {
        // Check if Elementor Pro is active
        if (!$this->is_elementor_pro_active()) {
            return;
        }

        // Register dynamic tags
        add_action('elementor/dynamic_tags/register_tags', [$this, 'register_dynamic_tags']);
        
        // Register dynamic tag groups
        add_action('elementor/dynamic_tags/register_groups', [$this, 'register_dynamic_tag_groups']);
    }

    /**
     * Check if Elementor Pro is active and compatible.
     */
    private function is_elementor_pro_active()
    {
        return defined('ELEMENTOR_PRO_VERSION') && version_compare(ELEMENTOR_PRO_VERSION, '3.0.0', '>=');
    }

    /**
     * Register dynamic tag groups.
     */
    public function register_dynamic_tag_groups($dynamic_tags_manager)
    {
        $dynamic_tags_manager->register_group(
            'cardcrafter',
            [
                'title' => __('CardCrafter', 'cardcrafter-data-grids'),
            ]
        );
    }

    /**
     * Register dynamic tags.
     */
    public function register_dynamic_tags($dynamic_tags_manager)
    {
        // Load dynamic tag classes
        require_once CARDCRAFTER_PATH . 'elementor/dynamic-tags/cardcrafter-field-tag.php';
        require_once CARDCRAFTER_PATH . 'elementor/dynamic-tags/cardcrafter-acf-tag.php';
        require_once CARDCRAFTER_PATH . 'elementor/dynamic-tags/cardcrafter-meta-tag.php';
        require_once CARDCRAFTER_PATH . 'elementor/dynamic-tags/cardcrafter-post-data-tag.php';
        require_once CARDCRAFTER_PATH . 'elementor/dynamic-tags/cardcrafter-taxonomy-tag.php';

        // Register tags
        $dynamic_tags_manager->register_tag(new CardCrafter_Field_Tag());
        $dynamic_tags_manager->register_tag(new CardCrafter_ACF_Tag());
        $dynamic_tags_manager->register_tag(new CardCrafter_Meta_Tag());
        $dynamic_tags_manager->register_tag(new CardCrafter_Post_Data_Tag());
        $dynamic_tags_manager->register_tag(new CardCrafter_Taxonomy_Tag());
    }

    /**
     * Get available field plugins.
     */
    public function get_available_field_plugins()
    {
        $plugins = [];

        // Advanced Custom Fields
        if (function_exists('get_field')) {
            $plugins['acf'] = [
                'name' => 'Advanced Custom Fields',
                'handler' => 'acf'
            ];
        }

        // Meta Box
        if (function_exists('rwmb_meta')) {
            $plugins['metabox'] = [
                'name' => 'Meta Box',
                'handler' => 'metabox'
            ];
        }

        // Toolset
        if (function_exists('types_render_field')) {
            $plugins['toolset'] = [
                'name' => 'Toolset Types',
                'handler' => 'toolset'
            ];
        }

        // JetEngine
        if (function_exists('jet_engine')) {
            $plugins['jetengine'] = [
                'name' => 'JetEngine',
                'handler' => 'jetengine'
            ];
        }

        // Pods
        if (function_exists('pods')) {
            $plugins['pods'] = [
                'name' => 'Pods',
                'handler' => 'pods'
            ];
        }

        return $plugins;
    }

    /**
     * Get field value using appropriate handler.
     */
    public function get_field_value($field_key, $post_id = null, $handler = 'acf')
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        switch ($handler) {
            case 'acf':
                return function_exists('get_field') ? get_field($field_key, $post_id) : '';

            case 'metabox':
                return function_exists('rwmb_meta') ? rwmb_meta($field_key, '', $post_id) : '';

            case 'toolset':
                return function_exists('types_render_field') ? types_render_field($field_key, ['raw' => true], $post_id) : '';

            case 'jetengine':
                if (function_exists('jet_engine')) {
                    $field_value = get_post_meta($post_id, $field_key, true);
                    return jet_engine()->listings->get_field_value($field_value, $field_key);
                }
                return '';

            case 'pods':
                if (function_exists('pods')) {
                    $pod = pods('post', $post_id);
                    return $pod ? $pod->display($field_key) : '';
                }
                return '';

            default:
                return get_post_meta($post_id, $field_key, true);
        }
    }

    /**
     * Get dynamic field options for a specific handler.
     */
    public function get_dynamic_field_options($handler = 'acf', $post_type = 'post')
    {
        $options = ['' => __('Select Field...', 'cardcrafter-data-grids')];

        switch ($handler) {
            case 'acf':
                if (function_exists('acf_get_field_groups')) {
                    $field_groups = acf_get_field_groups(['post_type' => $post_type]);
                    foreach ($field_groups as $group) {
                        $fields = acf_get_fields($group['key']);
                        foreach ($fields as $field) {
                            $options[$field['name']] = $field['label'];
                        }
                    }
                }
                break;

            case 'metabox':
                // Meta Box field discovery
                if (function_exists('rwmb_get_field_settings')) {
                    global $wpdb;
                    $meta_keys = $wpdb->get_col("
                        SELECT DISTINCT meta_key 
                        FROM {$wpdb->postmeta} 
                        WHERE meta_key NOT LIKE '\_%' 
                        LIMIT 50
                    ");
                    foreach ($meta_keys as $key) {
                        $options[$key] = ucwords(str_replace('_', ' ', $key));
                    }
                }
                break;

            case 'toolset':
                // Toolset field discovery
                if (function_exists('wpcf_admin_fields_get_groups')) {
                    $groups = wpcf_admin_fields_get_groups();
                    foreach ($groups as $group) {
                        if (isset($group['fields'])) {
                            foreach ($group['fields'] as $field_key => $field) {
                                $options[$field_key] = $field['name'];
                            }
                        }
                    }
                }
                break;

            default:
                // Generic meta field discovery
                global $wpdb;
                $meta_keys = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT pm.meta_key 
                    FROM {$wpdb->postmeta} pm
                    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                    WHERE p.post_type = %s 
                    AND pm.meta_key NOT LIKE '\_%'
                    LIMIT 50
                ", $post_type));
                
                foreach ($meta_keys as $key) {
                    $options[$key] = ucwords(str_replace('_', ' ', $key));
                }
                break;
        }

        return $options;
    }

    /**
     * Process dynamic content for CardCrafter widget.
     */
    public function process_dynamic_content($settings, $post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        // Process dynamic field mappings
        $dynamic_fields = [
            'title_field' => $settings['dynamic_title_field'] ?? '',
            'subtitle_field' => $settings['dynamic_subtitle_field'] ?? '',
            'description_field' => $settings['dynamic_description_field'] ?? '',
            'image_field' => $settings['dynamic_image_field'] ?? '',
            'link_field' => $settings['dynamic_link_field'] ?? ''
        ];

        $processed_data = [];

        foreach ($dynamic_fields as $field_type => $field_config) {
            if (empty($field_config)) continue;

            // Parse field configuration
            $handler = $field_config['handler'] ?? 'meta';
            $field_key = $field_config['field_key'] ?? '';
            
            if (empty($field_key)) continue;

            // Get field value
            $field_value = $this->get_field_value($field_key, $post_id, $handler);
            
            // Process based on field type
            switch ($field_type) {
                case 'image_field':
                    $processed_data[$field_type] = $this->process_image_field($field_value, $handler);
                    break;
                    
                case 'link_field':
                    $processed_data[$field_type] = $this->process_link_field($field_value, $post_id);
                    break;
                    
                default:
                    $processed_data[$field_type] = is_array($field_value) ? implode(', ', $field_value) : $field_value;
                    break;
            }
        }

        return $processed_data;
    }

    /**
     * Process image field value.
     */
    private function process_image_field($field_value, $handler)
    {
        if (empty($field_value)) {
            return '';
        }

        // Handle different image field formats
        if (is_numeric($field_value)) {
            // Image ID
            return wp_get_attachment_image_url($field_value, 'medium');
        }

        if (is_array($field_value)) {
            // ACF image array format
            if (isset($field_value['url'])) {
                return $field_value['url'];
            }
            if (isset($field_value['ID'])) {
                return wp_get_attachment_image_url($field_value['ID'], 'medium');
            }
        }

        if (is_string($field_value) && filter_var($field_value, FILTER_VALIDATE_URL)) {
            // Direct URL
            return $field_value;
        }

        return '';
    }

    /**
     * Process link field value.
     */
    private function process_link_field($field_value, $post_id)
    {
        if (empty($field_value)) {
            return get_permalink($post_id); // Default to post permalink
        }

        // Handle ACF link field format
        if (is_array($field_value) && isset($field_value['url'])) {
            return $field_value['url'];
        }

        if (is_string($field_value) && filter_var($field_value, FILTER_VALIDATE_URL)) {
            return $field_value;
        }

        return get_permalink($post_id);
    }

    /**
     * Get dynamic content capabilities.
     */
    public function get_capabilities()
    {
        return [
            'field_plugins' => $this->get_available_field_plugins(),
            'elementor_pro_version' => defined('ELEMENTOR_PRO_VERSION') ? ELEMENTOR_PRO_VERSION : null,
            'dynamic_tags_supported' => $this->is_elementor_pro_active(),
            'supported_field_types' => [
                'text', 'textarea', 'number', 'email', 'url', 'image', 'gallery', 
                'select', 'checkbox', 'radio', 'true_false', 'date', 'time', 'datetime'
            ]
        ];
    }
}