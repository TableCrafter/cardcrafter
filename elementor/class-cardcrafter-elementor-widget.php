<?php
/**
 * CardCrafter Elementor Widget
 * 
 * Professional Elementor widget integration for CardCrafter data grids.
 * Enables drag-and-drop card grid creation within Elementor's visual editor.
 * 
 * Business Impact: Unlocks 18+ million Elementor-powered websites for CardCrafter adoption.
 * 
 * @since 1.8.0
 * @package CardCrafter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

/**
 * CardCrafter Elementor Widget Class
 * 
 * Provides native Elementor integration with professional controls and live preview.
 */
class CardCrafter_Elementor_Widget extends Widget_Base
{
    /**
     * Get widget name.
     */
    public function get_name()
    {
        return 'cardcrafter-data-grids';
    }

    /**
     * Get widget title.
     */
    public function get_title()
    {
        return __('CardCrafter Data Grids', 'cardcrafter-data-grids');
    }

    /**
     * Get widget icon.
     */
    public function get_icon()
    {
        return 'eicon-gallery-grid';
    }

    /**
     * Get widget categories.
     */
    public function get_categories()
    {
        return ['general', 'cardcrafter'];
    }

    /**
     * Get widget keywords for search.
     */
    public function get_keywords()
    {
        return ['cards', 'data', 'grid', 'json', 'api', 'table', 'directory', 'portfolio'];
    }

    /**
     * Widget dependencies.
     */
    public function get_script_depends()
    {
        return ['cardcrafter-frontend'];
    }

    /**
     * Widget styles.
     */
    public function get_style_depends()
    {
        return ['cardcrafter-style'];
    }

    /**
     * Register widget controls.
     */
    protected function _register_controls()
    {
        // Data Source Section
        $this->start_controls_section(
            'data_source_section',
            [
                'label' => __('Data Source', 'cardcrafter-data-grids'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'data_mode',
            [
                'label' => __('Data Source Type', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'wordpress',
                'options' => [
                    'wordpress' => __('WordPress Posts', 'cardcrafter-data-grids'),
                    'json_url' => __('External JSON API', 'cardcrafter-data-grids'),
                    'custom_json' => __('Custom JSON Data', 'cardcrafter-data-grids'),
                    'demo' => __('Demo Data (Team Directory)', 'cardcrafter-data-grids'),
                ],
                'description' => __('Choose your data source. Demo data provides instant preview.', 'cardcrafter-data-grids'),
            ]
        );

        // WordPress Data Controls
        $this->add_control(
            'post_type',
            [
                'label' => __('Post Type', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'post',
                'options' => $this->get_post_types(),
                'condition' => [
                    'data_mode' => 'wordpress',
                ],
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Number of Posts', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::NUMBER,
                'default' => 12,
                'min' => 1,
                'max' => 100,
                'condition' => [
                    'data_mode' => 'wordpress',
                ],
            ]
        );

        $this->add_control(
            'wp_query_args',
            [
                'label' => __('Query Parameters', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => 'category_name=news&meta_key=featured',
                'description' => __('WordPress query parameters (e.g., category_name=news&author=5)', 'cardcrafter-data-grids'),
                'condition' => [
                    'data_mode' => 'wordpress',
                ],
            ]
        );

        // JSON URL Controls  
        $this->add_control(
            'json_url',
            [
                'label' => __('JSON URL', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://api.example.com/data.json',
                'description' => __('URL to your JSON data endpoint', 'cardcrafter-data-grids'),
                'condition' => [
                    'data_mode' => 'json_url',
                ],
            ]
        );

        // Custom JSON Controls
        $this->add_control(
            'custom_json',
            [
                'label' => __('JSON Data', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::CODE,
                'language' => 'json',
                'rows' => 10,
                'placeholder' => '[{"title":"Item 1","description":"Description 1"}]',
                'description' => __('Paste your JSON data here', 'cardcrafter-data-grids'),
                'condition' => [
                    'data_mode' => 'custom_json',
                ],
            ]
        );

        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section(
            'layout_section',
            [
                'label' => __('Layout & Display', 'cardcrafter-data-grids'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __('Layout Type', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __('Grid', 'cardcrafter-data-grids'),
                    'masonry' => __('Masonry', 'cardcrafter-data-grids'),
                    'list' => __('List', 'cardcrafter-data-grids'),
                ],
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => __('Columns', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'condition' => [
                    'layout!' => 'list',
                ],
            ]
        );

        $this->add_control(
            'items_per_page',
            [
                'label' => __('Items Per Page', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => '12',
                'options' => [
                    '6' => '6',
                    '12' => '12',
                    '24' => '24',
                    '50' => '50',
                    '100' => '100',
                ],
                'description' => __('Number of items to display per page', 'cardcrafter-data-grids'),
            ]
        );

        $this->end_controls_section();

        // Field Mapping Section
        $this->start_controls_section(
            'field_mapping_section',
            [
                'label' => __('Field Mapping', 'cardcrafter-data-grids'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'data_mode!' => 'wordpress',
                ],
            ]
        );

        $this->add_control(
            'title_field',
            [
                'label' => __('Title Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'default' => 'title',
                'description' => __('JSON field name for card titles', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'subtitle_field',
            [
                'label' => __('Subtitle Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'default' => 'subtitle',
                'description' => __('JSON field name for card subtitles', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'description_field',
            [
                'label' => __('Description Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'default' => 'description',
                'description' => __('JSON field name for card descriptions', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'image_field',
            [
                'label' => __('Image Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'default' => 'image',
                'description' => __('JSON field name for card images', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'link_field',
            [
                'label' => __('Link Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'default' => 'link',
                'description' => __('JSON field name for card links', 'cardcrafter-data-grids'),
            ]
        );

        $this->end_controls_section();

        // Dynamic Content Section (Elementor Pro)
        $this->start_controls_section(
            'dynamic_content_section',
            [
                'label' => __('Dynamic Content (Pro)', 'cardcrafter-data-grids'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'data_mode' => 'wordpress',
                ],
            ]
        );

        $this->add_control(
            'enable_dynamic_content',
            [
                'label' => __('Enable Dynamic Content', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __('Use Elementor Pro dynamic tags for field mapping', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'dynamic_content_info',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => sprintf(
                    __('%sElementor Pro Required%s This feature requires Elementor Pro to use dynamic tags and field plugins like ACF, Meta Box, Toolset, etc.', 'cardcrafter-data-grids'),
                    '<strong>',
                    '</strong><br>'
                ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        // Dynamic field mapping controls
        $this->add_control(
            'dynamic_title_source',
            [
                'label' => __('Title Source', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'post_title',
                'options' => $this->get_dynamic_field_options(),
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'dynamic_title_custom',
            [
                'label' => __('Custom Title Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                    'dynamic_title_source' => 'custom_field',
                ],
            ]
        );

        $this->add_control(
            'dynamic_subtitle_source',
            [
                'label' => __('Subtitle Source', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'post_date',
                'options' => $this->get_dynamic_field_options(),
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'dynamic_subtitle_custom',
            [
                'label' => __('Custom Subtitle Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                    'dynamic_subtitle_source' => 'custom_field',
                ],
            ]
        );

        $this->add_control(
            'dynamic_description_source',
            [
                'label' => __('Description Source', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'post_excerpt',
                'options' => $this->get_dynamic_field_options(),
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'dynamic_description_custom',
            [
                'label' => __('Custom Description Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                    'dynamic_description_source' => 'custom_field',
                ],
            ]
        );

        $this->add_control(
            'dynamic_image_source',
            [
                'label' => __('Image Source', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'featured_image',
                'options' => $this->get_dynamic_field_options(),
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'dynamic_image_custom',
            [
                'label' => __('Custom Image Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                    'dynamic_image_source' => 'custom_field',
                ],
            ]
        );

        $this->add_control(
            'dynamic_link_source',
            [
                'label' => __('Link Source', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'default' => 'post_url',
                'options' => $this->get_dynamic_field_options(),
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'dynamic_link_custom',
            [
                'label' => __('Custom Link Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                    'dynamic_link_source' => 'custom_field',
                ],
            ]
        );

        // Advanced filtering controls
        $this->add_control(
            'dynamic_filtering_heading',
            [
                'label' => __('Advanced Filtering', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_by_taxonomy',
            [
                'label' => __('Filter by Taxonomy', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_taxonomies_options(),
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_taxonomy_terms',
            [
                'label' => __('Taxonomy Terms', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXT,
                'description' => __('Comma-separated term slugs or IDs', 'cardcrafter-data-grids'),
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                    'filter_by_taxonomy!' => '',
                ],
            ]
        );

        $this->add_control(
            'filter_by_author',
            [
                'label' => __('Filter by Author', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_authors_options(),
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_by_meta',
            [
                'label' => __('Filter by Meta Field', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => 'meta_key=featured&meta_value=yes',
                'description' => __('Add meta query parameters (one per line)', 'cardcrafter-data-grids'),
                'condition' => [
                    'enable_dynamic_content' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Features Section
        $this->start_controls_section(
            'features_section',
            [
                'label' => __('Interactive Features', 'cardcrafter-data-grids'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label' => __('Show Search', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Display search toolbar for filtering cards', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'show_sort',
            [
                'label' => __('Show Sort Options', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Display sort dropdown (A-Z, Z-A)', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'show_export',
            [
                'label' => __('Show Export Options', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Display data export tools (CSV, JSON, PDF)', 'cardcrafter-data-grids'),
            ]
        );

        $this->add_control(
            'show_pagination',
            [
                'label' => __('Show Pagination', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Display pagination controls', 'cardcrafter-data-grids'),
            ]
        );

        $this->end_controls_section();

        // Card Style Section
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => __('Card Style', 'cardcrafter-data-grids'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .cardcrafter-card',
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label' => __('Border Radius', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .cardcrafter-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_shadow',
                'selector' => '{{WRAPPER}} .cardcrafter-card',
            ]
        );

        $this->add_control(
            'card_padding',
            [
                'label' => __('Padding', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .cardcrafter-card-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'card_gap',
            [
                'label' => __('Gap Between Cards', 'cardcrafter-data-grids'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 24,
                ],
                'selectors' => [
                    '{{WRAPPER}} .cardcrafter-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Typography Section
        $this->start_controls_section(
            'typography_section',
            [
                'label' => __('Typography', 'cardcrafter-data-grids'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'cardcrafter-data-grids'),
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
                ],
                'selector' => '{{WRAPPER}} .cardcrafter-card-title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'subtitle_typography',
                'label' => __('Subtitle Typography', 'cardcrafter-data-grids'),
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_SECONDARY,
                ],
                'selector' => '{{WRAPPER}} .cardcrafter-card-subtitle',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'description_typography',
                'label' => __('Description Typography', 'cardcrafter-data-grids'),
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_TEXT,
                ],
                'selector' => '{{WRAPPER}} .cardcrafter-card-description',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render()
    {
        // Enqueue required scripts and styles
        wp_enqueue_script('cardcrafter-frontend');
        wp_enqueue_script('cardcrafter-elementor-frontend');
        wp_enqueue_style('cardcrafter-style');
        wp_enqueue_style('cardcrafter-elementor-style');
        
        $settings = $this->get_settings_for_display();
        
        // Generate unique container ID
        $container_id = 'cardcrafter-elementor-' . $this->get_id();
        
        // Prepare configuration
        $config = $this->prepare_widget_config($settings);
        
        // Render container
        $this->render_widget_container($container_id, $config, $settings);
    }

    /**
     * Prepare widget configuration for JavaScript.
     */
    private function prepare_widget_config($settings)
    {
        $config = [
            'layout' => $settings['layout'] ?? 'grid',
            'columns' => $settings['columns'] ?? 3,
            'itemsPerPage' => intval($settings['items_per_page'] ?? 12),
            'fields' => [
                'title' => $settings['title_field'] ?? 'title',
                'subtitle' => $settings['subtitle_field'] ?? 'subtitle', 
                'description' => $settings['description_field'] ?? 'description',
                'image' => $settings['image_field'] ?? 'image',
                'link' => $settings['link_field'] ?? 'link',
            ]
        ];

        // Configure data source
        switch ($settings['data_mode']) {
            case 'wordpress':
                $config['wpDataMode'] = true;
                $config['data'] = $this->get_wordpress_data($settings);
                break;
                
            case 'json_url':
                $config['source'] = $settings['json_url']['url'] ?? '';
                break;
                
            case 'custom_json':
                $config['wpDataMode'] = true;
                $config['data'] = json_decode($settings['custom_json'] ?? '[]', true) ?: [];
                break;
                
            case 'demo':
                $config['wpDataMode'] = true;
                $config['data'] = $this->get_demo_data();
                break;
                
            case 'wordpress':
            default:
                $config['wpDataMode'] = true;
                $config['data'] = $this->get_wordpress_data($settings);
                break;
        }

        return $config;
    }

    /**
     * Render the widget container and initialization script.
     */
    private function render_widget_container($container_id, $config, $settings)
    {
        // Add demo banner for demo mode only
        if (($settings['data_mode'] ?? 'wordpress') === 'demo') {
            $this->render_demo_banner();
        }

        // Render container
        echo '<div id="' . esc_attr($container_id) . '" class="cardcrafter-container cardcrafter-elementor-widget" data-config="' . esc_attr(json_encode($config)) . '"></div>';

        // Initialize widget with JavaScript
        $this->render_initialization_script($container_id, $config);
    }

    /**
     * Render demo banner.
     */
    private function render_demo_banner()
    {
        echo '<div class="cardcrafter-demo-banner">
            <div class="cardcrafter-demo-content">
                <span class="cardcrafter-demo-badge">DEMO MODE</span>
                <p>Showing demo team data. Configure your data source in the Elementor settings panel →</p>
            </div>
        </div>';
    }

    /**
     * Render JavaScript initialization.
     */
    private function render_initialization_script($container_id, $config)
    {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof CardCrafter !== 'undefined') {
                new CardCrafter({
                    selector: '#<?php echo esc_js($container_id); ?>',
                    source: <?php echo json_encode($config['source'] ?? null); ?>,
                    layout: <?php echo json_encode($config['layout']); ?>,
                    columns: <?php echo json_encode($config['columns']); ?>,
                    fields: <?php echo json_encode($config['fields']); ?>,
                    itemsPerPage: <?php echo json_encode($config['itemsPerPage']); ?>,
                    wpDataMode: <?php echo json_encode($config['wpDataMode'] ?? false); ?>,
                    data: <?php echo json_encode($config['data'] ?? null); ?>
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Get WordPress post data.
     */
    private function get_wordpress_data($settings)
    {
        $args = [
            'post_type' => $settings['post_type'] ?? 'post',
            'posts_per_page' => intval($settings['posts_per_page'] ?? 12),
            'post_status' => 'publish',
        ];

        // Parse additional query parameters
        if (!empty($settings['wp_query_args'])) {
            parse_str($settings['wp_query_args'], $additional_args);
            $args = array_merge($args, $additional_args);
        }

        // Apply dynamic content filtering
        if ($settings['enable_dynamic_content'] === 'yes') {
            $args = $this->apply_dynamic_filters($args, $settings);
        }

        $posts = get_posts($args);
        $data = [];

        foreach ($posts as $post) {
            if ($settings['enable_dynamic_content'] === 'yes') {
                $data[] = $this->process_post_with_dynamic_content($post, $settings);
            } else {
                $data[] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'subtitle' => get_the_date('F j, Y', $post),
                    'description' => wp_trim_words($post->post_content, 20),
                    'link' => get_permalink($post),
                    'image' => get_the_post_thumbnail_url($post, 'medium'),
                    'post_type' => $post->post_type,
                    'author' => get_the_author_meta('display_name', $post->post_author),
                ];
            }
        }

        return $data;
    }

    /**
     * Apply dynamic filtering to WordPress query.
     */
    private function apply_dynamic_filters($args, $settings)
    {
        // Taxonomy filtering
        if (!empty($settings['filter_by_taxonomy']) && !empty($settings['filter_taxonomy_terms'])) {
            $taxonomy = $settings['filter_by_taxonomy'];
            $terms = array_map('trim', explode(',', $settings['filter_taxonomy_terms']));
            
            $args['tax_query'] = [
                [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms,
                ]
            ];
        }

        // Author filtering
        if (!empty($settings['filter_by_author'])) {
            $authors = is_array($settings['filter_by_author']) 
                ? $settings['filter_by_author'] 
                : [$settings['filter_by_author']];
            $args['author__in'] = $authors;
        }

        // Meta filtering
        if (!empty($settings['filter_by_meta'])) {
            $meta_lines = explode("\n", $settings['filter_by_meta']);
            $meta_query = [];
            
            foreach ($meta_lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                parse_str($line, $meta_params);
                if (!empty($meta_params['meta_key'])) {
                    $meta_query[] = [
                        'key' => $meta_params['meta_key'],
                        'value' => $meta_params['meta_value'] ?? '',
                        'compare' => $meta_params['meta_compare'] ?? '=',
                    ];
                }
            }
            
            if (!empty($meta_query)) {
                $args['meta_query'] = $meta_query;
            }
        }

        return $args;
    }

    /**
     * Process post with dynamic content settings.
     */
    private function process_post_with_dynamic_content($post, $settings)
    {
        $data = [
            'id' => $post->ID,
            'post_type' => $post->post_type,
        ];

        // Process title
        $data['title'] = $this->get_dynamic_field_value(
            $settings['dynamic_title_source'] ?? 'post_title',
            $settings['dynamic_title_custom'] ?? '',
            $post
        );

        // Process subtitle
        $data['subtitle'] = $this->get_dynamic_field_value(
            $settings['dynamic_subtitle_source'] ?? 'post_date',
            $settings['dynamic_subtitle_custom'] ?? '',
            $post
        );

        // Process description
        $data['description'] = $this->get_dynamic_field_value(
            $settings['dynamic_description_source'] ?? 'post_excerpt',
            $settings['dynamic_description_custom'] ?? '',
            $post
        );

        // Process image
        $data['image'] = $this->get_dynamic_field_value(
            $settings['dynamic_image_source'] ?? 'featured_image',
            $settings['dynamic_image_custom'] ?? '',
            $post
        );

        // Process link
        $data['link'] = $this->get_dynamic_field_value(
            $settings['dynamic_link_source'] ?? 'post_url',
            $settings['dynamic_link_custom'] ?? '',
            $post
        );

        // Add author info
        $data['author'] = get_the_author_meta('display_name', $post->post_author);

        return $data;
    }

    /**
     * Get dynamic field value based on source.
     */
    private function get_dynamic_field_value($source, $custom_field, $post)
    {
        switch ($source) {
            case 'post_title':
                return $post->post_title;
                
            case 'post_content':
                return wp_strip_all_tags($post->post_content);
                
            case 'post_excerpt':
                return wp_trim_words($post->post_excerpt ?: $post->post_content, 20);
                
            case 'post_date':
                return get_the_date('F j, Y', $post);
                
            case 'post_author':
                return get_the_author_meta('display_name', $post->post_author);
                
            case 'post_url':
                return get_permalink($post);
                
            case 'featured_image':
                return get_the_post_thumbnail_url($post, 'medium');
                
            case 'custom_field':
                if (empty($custom_field)) return '';
                return get_post_meta($post->ID, $custom_field, true);
                
            default:
                // Handle ACF fields
                if (strpos($source, 'acf_') === 0) {
                    $field_name = substr($source, 4);
                    return function_exists('get_field') ? get_field($field_name, $post->ID) : '';
                }
                
                return '';
        }
    }

    /**
     * Get demo data for instant preview.
     */
    private function get_demo_data()
    {
        return [
            [
                'id' => 1,
                'title' => 'Sarah Johnson',
                'subtitle' => 'Senior Developer',
                'description' => 'Full-stack developer specializing in WordPress and modern JavaScript frameworks.',
                'image' => 'https://images.unsplash.com/photo-1494790108755-2616b9f3a3e0?w=400',
                'link' => '#',
            ],
            [
                'id' => 2,
                'title' => 'Michael Chen',
                'subtitle' => 'UX Designer',
                'description' => 'Creative designer focused on user experience and interface design.',
                'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400',
                'link' => '#',
            ],
            [
                'id' => 3,
                'title' => 'Emily Rodriguez',
                'subtitle' => 'Project Manager',
                'description' => 'Experienced project manager ensuring smooth delivery of complex projects.',
                'image' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400',
                'link' => '#',
            ],
            [
                'id' => 4,
                'title' => 'David Kim',
                'subtitle' => 'DevOps Engineer',
                'description' => 'Infrastructure specialist focused on automation and scalability.',
                'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400',
                'link' => '#',
            ],
            [
                'id' => 5,
                'title' => 'Lisa Thompson',
                'subtitle' => 'Marketing Director',
                'description' => 'Strategic marketing professional driving brand growth and customer engagement.',
                'image' => 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=400',
                'link' => '#',
            ],
            [
                'id' => 6,
                'title' => 'Alex Martinez',
                'subtitle' => 'Data Analyst',
                'description' => 'Analytics expert turning data into actionable business insights.',
                'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400',
                'link' => '#',
            ],
        ];
    }

    /**
     * Get available post types for WordPress data mode.
     */
    private function get_post_types()
    {
        $post_types = get_post_types(['public' => true], 'objects');
        $options = [];
        
        foreach ($post_types as $post_type) {
            if ($post_type->name !== 'attachment') {
                $options[$post_type->name] = $post_type->label;
            }
        }
        
        return $options;
    }

    /**
     * Get dynamic field options for Elementor Pro integration.
     */
    private function get_dynamic_field_options()
    {
        $options = [
            '' => __('None', 'cardcrafter-data-grids'),
            'post_title' => __('Post Title', 'cardcrafter-data-grids'),
            'post_content' => __('Post Content', 'cardcrafter-data-grids'),
            'post_excerpt' => __('Post Excerpt', 'cardcrafter-data-grids'),
            'post_date' => __('Post Date', 'cardcrafter-data-grids'),
            'post_author' => __('Author Name', 'cardcrafter-data-grids'),
            'post_url' => __('Post URL', 'cardcrafter-data-grids'),
            'featured_image' => __('Featured Image', 'cardcrafter-data-grids'),
            'custom_field' => __('Custom Field', 'cardcrafter-data-grids'),
        ];

        // Add ACF fields if available
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups();
            foreach ($field_groups as $group) {
                $fields = acf_get_fields($group['key']);
                if ($fields) {
                    foreach ($fields as $field) {
                        $options['acf_' . $field['name']] = 'ACF: ' . $field['label'];
                    }
                }
            }
        }

        return $options;
    }

    /**
     * Get taxonomies options.
     */
    private function get_taxonomies_options()
    {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $options = ['' => __('Select Taxonomy...', 'cardcrafter-data-grids')];

        foreach ($taxonomies as $taxonomy) {
            $options[$taxonomy->name] = $taxonomy->label;
        }

        return $options;
    }

    /**
     * Get authors options.
     */
    private function get_authors_options()
    {
        $users = get_users(['who' => 'authors']);
        $options = [];

        foreach ($users as $user) {
            $options[$user->ID] = $user->display_name;
        }

        return $options;
    }

    /**
     * Render widget output in the editor.
     */
    protected function content_template()
    {
        ?>
        <#
        var data_mode = settings.data_mode || 'wordpress';
        var badge_text = 'ELEMENTOR PREVIEW';
        var description = 'CardCrafter widget configured. Live preview available on frontend.';
        
        if (data_mode === 'wordpress') {
            badge_text = 'WORDPRESS POSTS';
            description = 'Displaying WordPress posts as cards | Layout: ' + (settings.layout || 'grid') + ' | Columns: ' + (settings.columns || 3);
        } else if (data_mode === 'json_url') {
            badge_text = 'JSON DATA';
            description = 'External JSON source: ' + (settings.json_url ? settings.json_url.url : 'Not configured');
        } else if (data_mode === 'custom_json') {
            badge_text = 'CUSTOM JSON';
            description = 'Using custom JSON data for card display';
        } else if (data_mode === 'demo') {
            badge_text = 'DEMO DATA';
            description = 'Showing sample team directory data';
        }
        #>
        <div class="cardcrafter-elementor-preview">
            <div class="cardcrafter-demo-banner">
                <div class="cardcrafter-demo-content">
                    <span class="cardcrafter-demo-badge">{{ badge_text }}</span>
                    <p>{{ description }}</p>
                </div>
            </div>
            <div class="cardcrafter-preview-grid">
                <div class="cardcrafter-preview-card">
                    <div class="cardcrafter-preview-image"></div>
                    <h3>Sample Card Title</h3>
                    <p class="cardcrafter-preview-subtitle">Sample Subtitle</p>
                    <p class="cardcrafter-preview-description">Sample description text that would appear in your actual cards...</p>
                </div>
                <div class="cardcrafter-preview-card">
                    <div class="cardcrafter-preview-image"></div>
                    <h3>Another Card</h3>
                    <p class="cardcrafter-preview-subtitle">Sample Role</p>
                    <p class="cardcrafter-preview-description">More sample content for the card preview...</p>
                </div>
                <div class="cardcrafter-preview-card">
                    <div class="cardcrafter-preview-image"></div>
                    <h3>Third Card</h3>
                    <p class="cardcrafter-preview-subtitle">Position</p>
                    <p class="cardcrafter-preview-description">Additional sample content for demonstration...</p>
                </div>
            </div>
        </div>
        
        <style>
        .cardcrafter-elementor-preview {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .cardcrafter-preview-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .cardcrafter-preview-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .cardcrafter-preview-image {
            height: 150px;
            background: #e0e0e0;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .cardcrafter-preview-subtitle {
            color: #666;
            margin: 5px 0;
        }
        .cardcrafter-preview-description {
            color: #888;
            font-size: 14px;
        }
        </style>
        <?php
    }
}