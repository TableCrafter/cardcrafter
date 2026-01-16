<?php
/**
 * CardCrafter Taxonomy Dynamic Tag
 * 
 * Provides WordPress taxonomy integration for CardCrafter Elementor widgets.
 * 
 * @since 1.8.0
 * @package CardCrafter
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;

class CardCrafter_Taxonomy_Tag extends Tag
{
    public function get_name()
    {
        return 'cardcrafter-taxonomy';
    }

    public function get_title()
    {
        return __('Taxonomy Terms', 'cardcrafter-data-grids');
    }

    public function get_group()
    {
        return 'cardcrafter';
    }

    public function get_categories()
    {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
        ];
    }

    protected function _register_controls()
    {
        $this->add_control(
            'taxonomy',
            [
                'label' => __('Taxonomy', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_taxonomies(),
                'description' => __('Select the taxonomy to display', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'separator',
            [
                'label' => __('Separator', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'default' => ', ',
                'description' => __('Separator between multiple terms', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'link_terms',
            [
                'label' => __('Link Terms', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __('Make terms clickable links', 'cardcrafter-data-grids'),
            ]
        );
    }

    public function render()
    {
        $taxonomy = $this->get_settings('taxonomy');
        $separator = $this->get_settings('separator') ?: ', ';
        $link_terms = $this->get_settings('link_terms') === 'yes';

        if (empty($taxonomy)) {
            return;
        }

        $terms = get_the_terms(get_the_ID(), $taxonomy);
        
        if (!$terms || is_wp_error($terms)) {
            return;
        }

        $term_links = [];
        foreach ($terms as $term) {
            if ($link_terms) {
                $term_links[] = '<a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a>';
            } else {
                $term_links[] = esc_html($term->name);
            }
        }

        echo implode($separator, $term_links);
    }

    private function get_taxonomies()
    {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $options = ['' => __('Select Taxonomy...', 'cardcrafter-data-grids')];

        foreach ($taxonomies as $taxonomy) {
            $options[$taxonomy->name] = $taxonomy->label;
        }

        return $options;
    }
}