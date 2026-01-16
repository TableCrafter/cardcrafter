<?php
/**
 * CardCrafter Generic Field Dynamic Tag
 * 
 * Provides generic custom field integration for CardCrafter Elementor widgets.
 * Works with any custom field plugin or standard WordPress meta fields.
 * 
 * @since 1.8.0
 * @package CardCrafter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;

/**
 * CardCrafter Generic Field Dynamic Tag Class
 */
class CardCrafter_Field_Tag extends Tag
{
    /**
     * Get tag name.
     */
    public function get_name()
    {
        return 'cardcrafter-custom-field';
    }

    /**
     * Get tag title.
     */
    public function get_title()
    {
        return __('Custom Field', 'cardcrafter-data-grids');
    }

    /**
     * Get tag group.
     */
    public function get_group()
    {
        return 'cardcrafter';
    }

    /**
     * Get tag categories.
     */
    public function get_categories()
    {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY,
        ];
    }

    /**
     * Register tag controls.
     */
    protected function _register_controls()
    {
        $this->add_control(
            'field_plugin',
            [
                'label' => __('Field Plugin', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'meta',
                'options' => $this->get_field_plugin_options(),
                'description' => __('Select the field plugin to use', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'field_key',
            [
                'label' => __('Field Key', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'description' => __('Enter the field key/name', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'field_type',
            [
                'label' => __('Field Type', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'text',
                'options' => [
                    'text' => __('Text', 'cardcrafter-data-grids'),
                    'textarea' => __('Textarea', 'cardcrafter-data-grids'),
                    'number' => __('Number', 'cardcrafter-data-grids'),
                    'email' => __('Email', 'cardcrafter-data-grids'),
                    'url' => __('URL', 'cardcrafter-data-grids'),
                    'image' => __('Image', 'cardcrafter-data-grids'),
                    'gallery' => __('Gallery', 'cardcrafter-data-grids'),
                    'date' => __('Date', 'cardcrafter-data-grids'),
                    'select' => __('Select', 'cardcrafter-data-grids'),
                    'checkbox' => __('Checkbox', 'cardcrafter-data-grids'),
                ],
                'description' => __('Specify the field type for proper formatting', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label' => __('Image Size', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => $this->get_image_sizes(),
                'condition' => [
                    'field_type' => 'image',
                ],
            ]
        );

        $this->add_control(
            'date_format',
            [
                'label' => __('Date Format', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'default' => get_option('date_format'),
                'condition' => [
                    'field_type' => 'date',
                ],
                'description' => __('PHP date format string', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'fallback_value',
            [
                'label' => __('Fallback Value', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'description' => __('Value to display if field is empty', 'cardcrafter-data-grids'),
            ]
        );
    }

    /**
     * Render tag content.
     */
    public function render()
    {
        $field_plugin = $this->get_settings('field_plugin');
        $field_key = $this->get_settings('field_key');
        $field_type = $this->get_settings('field_type');
        $image_size = $this->get_settings('image_size');
        $date_format = $this->get_settings('date_format');
        $fallback = $this->get_settings('fallback_value');

        if (empty($field_key)) {
            echo $fallback ?: '';
            return;
        }

        $field_value = $this->get_field_value($field_key, $field_plugin);

        if (empty($field_value)) {
            echo $fallback ?: '';
            return;
        }

        $output = $this->format_field_value($field_value, $field_type, $image_size, $date_format);
        
        echo $output ?: $fallback ?: '';
    }

    /**
     * Get field value using the specified plugin.
     */
    private function get_field_value($field_key, $plugin)
    {
        $post_id = get_the_ID();

        switch ($plugin) {
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

            default: // meta
                return get_post_meta($post_id, $field_key, true);
        }
    }

    /**
     * Format field value based on type.
     */
    private function format_field_value($field_value, $field_type, $image_size, $date_format)
    {
        switch ($field_type) {
            case 'image':
                if (is_numeric($field_value)) {
                    return wp_get_attachment_image_url($field_value, $image_size);
                }
                if (is_array($field_value) && isset($field_value['url'])) {
                    return $field_value['url'];
                }
                if (filter_var($field_value, FILTER_VALIDATE_URL)) {
                    return $field_value;
                }
                return '';

            case 'gallery':
                if (is_array($field_value)) {
                    $images = [];
                    foreach ($field_value as $image) {
                        if (is_numeric($image)) {
                            $images[] = wp_get_attachment_image_url($image, $image_size);
                        } elseif (is_array($image) && isset($image['url'])) {
                            $images[] = $image['url'];
                        }
                    }
                    return implode(', ', $images);
                }
                return '';

            case 'date':
                if (!empty($date_format)) {
                    return date_i18n($date_format, strtotime($field_value));
                }
                return $field_value;

            case 'url':
                if (is_array($field_value) && isset($field_value['url'])) {
                    return $field_value['url'];
                }
                return $field_value;

            case 'checkbox':
                if (is_array($field_value)) {
                    return implode(', ', $field_value);
                }
                return $field_value ? __('Yes', 'cardcrafter-data-grids') : __('No', 'cardcrafter-data-grids');

            default:
                if (is_array($field_value)) {
                    return implode(', ', $field_value);
                }
                return $field_value;
        }
    }

    /**
     * Get available field plugin options.
     */
    private function get_field_plugin_options()
    {
        $options = [
            'meta' => __('WordPress Meta', 'cardcrafter-data-grids'),
        ];

        if (function_exists('get_field')) {
            $options['acf'] = __('Advanced Custom Fields', 'cardcrafter-data-grids');
        }

        if (function_exists('rwmb_meta')) {
            $options['metabox'] = __('Meta Box', 'cardcrafter-data-grids');
        }

        if (function_exists('types_render_field')) {
            $options['toolset'] = __('Toolset Types', 'cardcrafter-data-grids');
        }

        if (function_exists('jet_engine')) {
            $options['jetengine'] = __('JetEngine', 'cardcrafter-data-grids');
        }

        if (function_exists('pods')) {
            $options['pods'] = __('Pods', 'cardcrafter-data-grids');
        }

        return $options;
    }

    /**
     * Get image sizes.
     */
    private function get_image_sizes()
    {
        $sizes = get_intermediate_image_sizes();
        $options = [];
        
        foreach ($sizes as $size) {
            $options[$size] = ucwords(str_replace('_', ' ', $size));
        }
        
        $options['full'] = __('Full Size', 'cardcrafter-data-grids');
        
        return $options;
    }
}