<?php
/**
 * CardCrafter Post Data Dynamic Tag
 * 
 * Provides WordPress post data integration for CardCrafter Elementor widgets.
 * 
 * @since 1.8.0
 * @package CardCrafter
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;

class CardCrafter_Post_Data_Tag extends Tag
{
    public function get_name()
    {
        return 'cardcrafter-post-data';
    }

    public function get_title()
    {
        return __('Post Data', 'cardcrafter-data-grids');
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
            'post_field',
            [
                'label' => __('Post Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'post_title',
                'options' => [
                    'post_title' => __('Title', 'cardcrafter-data-grids'),
                    'post_content' => __('Content', 'cardcrafter-data-grids'),
                    'post_excerpt' => __('Excerpt', 'cardcrafter-data-grids'),
                    'post_date' => __('Date', 'cardcrafter-data-grids'),
                    'post_author' => __('Author Name', 'cardcrafter-data-grids'),
                    'post_url' => __('URL', 'cardcrafter-data-grids'),
                    'featured_image' => __('Featured Image', 'cardcrafter-data-grids'),
                ],
            ]
        );

        $this->add_control(
            'excerpt_length',
            [
                'label' => __('Excerpt Length', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::NUMBER,
                'default' => 20,
                'condition' => [
                    'post_field' => 'post_excerpt',
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
                    'post_field' => 'post_date',
                ],
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
                    'post_field' => 'featured_image',
                ],
            ]
        );
    }

    public function render()
    {
        $post_field = $this->get_settings('post_field');
        $post = get_post();
        
        if (!$post) {
            return;
        }

        switch ($post_field) {
            case 'post_title':
                echo get_the_title($post);
                break;
                
            case 'post_content':
                echo wp_strip_all_tags(get_the_content(null, false, $post));
                break;
                
            case 'post_excerpt':
                $length = $this->get_settings('excerpt_length') ?: 20;
                echo wp_trim_words(get_the_excerpt($post), $length);
                break;
                
            case 'post_date':
                $format = $this->get_settings('date_format') ?: get_option('date_format');
                echo get_the_date($format, $post);
                break;
                
            case 'post_author':
                echo get_the_author_meta('display_name', $post->post_author);
                break;
                
            case 'post_url':
                echo get_permalink($post);
                break;
                
            case 'featured_image':
                $size = $this->get_settings('image_size') ?: 'medium';
                echo get_the_post_thumbnail_url($post, $size);
                break;
        }
    }

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