<?php
/**
 * CardCrafter ACF Dynamic Tag
 * 
 * Provides ACF field integration for CardCrafter Elementor widgets.
 * Enables dynamic content population from ACF fields.
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
 * CardCrafter ACF Dynamic Tag Class
 */
class CardCrafter_ACF_Tag extends Tag
{
    /**
     * Get tag name.
     */
    public function get_name()
    {
        return 'cardcrafter-acf-field';
    }

    /**
     * Get tag title.
     */
    public function get_title()
    {
        return __('ACF Field', 'cardcrafter-data-grids');
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
            'field_key',
            [
                'label' => __('ACF Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_acf_field_options(),
                'description' => __('Select the ACF field to display', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'field_format',
            [
                'label' => __('Format', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'raw',
                'options' => [
                    'raw' => __('Raw Value', 'cardcrafter-data-grids'),
                    'formatted' => __('Formatted Value', 'cardcrafter-data-grids'),
                    'label' => __('Choice Label', 'cardcrafter-data-grids'),
                ],
                'description' => __('How to display the field value', 'cardcrafter-data-grids'),
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
                    'field_format' => 'formatted',
                ],
                'description' => __('Select image size for image fields', 'cardcrafter-data-grids'),
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
        // Check if ACF is available
        if (!function_exists('get_field')) {
            echo $this->get_settings('fallback_value') ?: '';
            return;
        }

        $field_key = $this->get_settings('field_key');
        $field_format = $this->get_settings('field_format');
        $image_size = $this->get_settings('image_size');
        $fallback = $this->get_settings('fallback_value');

        if (empty($field_key)) {
            echo $fallback ?: '';
            return;
        }

        $field_value = get_field($field_key);

        if (empty($field_value)) {
            echo $fallback ?: '';
            return;
        }

        // Process field value based on format
        $output = $this->process_field_value($field_value, $field_format, $image_size);
        
        echo $output ?: $fallback ?: '';
    }

    /**
     * Process field value based on format and type.
     */
    private function process_field_value($field_value, $format, $image_size)
    {
        // Handle image fields
        if (is_array($field_value) && isset($field_value['url'])) {
            // ACF image array
            if ($format === 'formatted') {
                return isset($field_value['sizes'][$image_size]) 
                    ? $field_value['sizes'][$image_size] 
                    : $field_value['url'];
            }
            return $field_value['url'];
        }

        // Handle image ID
        if (is_numeric($field_value) && wp_attachment_is_image($field_value)) {
            if ($format === 'formatted') {
                return wp_get_attachment_image_url($field_value, $image_size);
            }
            return wp_get_attachment_image_url($field_value, 'full');
        }

        // Handle choice fields (select, radio, checkbox)
        if (is_array($field_value)) {
            if ($format === 'label') {
                // Get field object to access choices
                $field_object = get_field_object($field_key);
                if (isset($field_object['choices'])) {
                    $labels = [];
                    foreach ((array)$field_value as $value) {
                        $labels[] = $field_object['choices'][$value] ?? $value;
                    }
                    return implode(', ', $labels);
                }
            }
            return implode(', ', (array)$field_value);
        }

        // Handle date/time fields
        if ($format === 'formatted') {
            $field_object = get_field_object($field_key);
            if (isset($field_object['type'])) {
                switch ($field_object['type']) {
                    case 'date_picker':
                        return date_i18n(get_option('date_format'), strtotime($field_value));
                    case 'time_picker':
                        return date_i18n(get_option('time_format'), strtotime($field_value));
                    case 'date_time_picker':
                        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($field_value));
                }
            }
        }

        // Handle link fields
        if (is_array($field_value) && isset($field_value['url'])) {
            return $field_value['url'];
        }

        // Return raw value
        return is_string($field_value) ? $field_value : '';
    }

    /**
     * Get ACF field options.
     */
    private function get_acf_field_options()
    {
        $options = ['' => __('Select ACF Field...', 'cardcrafter-data-grids')];

        if (!function_exists('acf_get_field_groups')) {
            return $options;
        }

        $field_groups = acf_get_field_groups();
        foreach ($field_groups as $group) {
            $fields = acf_get_fields($group['key']);
            if ($fields) {
                foreach ($fields as $field) {
                    $options[$field['name']] = $field['label'] . ' (' . $field['type'] . ')';
                }
            }
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