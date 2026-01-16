<?php
/**
 * CardCrafter Meta Dynamic Tag
 * 
 * Provides WordPress post meta integration for CardCrafter Elementor widgets.
 * 
 * @since 1.8.0
 * @package CardCrafter
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;

class CardCrafter_Meta_Tag extends Tag
{
    public function get_name()
    {
        return 'cardcrafter-meta-field';
    }

    public function get_title()
    {
        return __('Post Meta', 'cardcrafter-data-grids');
    }

    public function get_group()
    {
        return 'cardcrafter';
    }

    public function get_categories()
    {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
        ];
    }

    protected function _register_controls()
    {
        $this->add_control(
            'meta_key',
            [
                'label' => __('Meta Key', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'description' => __('Enter the meta key name', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'fallback_value',
            [
                'label' => __('Fallback Value', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'description' => __('Value to display if meta is empty', 'cardcrafter-data-grids'),
            ]
        );
    }

    public function render()
    {
        $meta_key = $this->get_settings('meta_key');
        $fallback = $this->get_settings('fallback_value');

        if (empty($meta_key)) {
            echo $fallback ?: '';
            return;
        }

        $meta_value = get_post_meta(get_the_ID(), $meta_key, true);
        
        if (empty($meta_value)) {
            echo $fallback ?: '';
            return;
        }

        if (is_array($meta_value)) {
            echo implode(', ', $meta_value);
        } else {
            echo esc_html($meta_value);
        }
    }
}