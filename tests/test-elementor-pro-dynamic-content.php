<?php
/**
 * Test Elementor Pro Dynamic Content Integration
 * 
 * Tests the new dynamic content functionality that enables CardCrafter
 * to work with Elementor Pro dynamic tags and field plugins.
 * 
 * @since 1.8.0
 * @package CardCrafter
 */

class TestElementorProDynamicContent extends WP_UnitTestCase
{
    private $manager;
    private $widget;
    private $test_post_id;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Create test post with meta fields
        $this->test_post_id = $this->factory->post->create([
            'post_title' => 'Test Post for Dynamic Content',
            'post_content' => 'This is test content for dynamic field testing.',
            'post_excerpt' => 'Test excerpt content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ]);
        
        // Add test meta fields
        update_post_meta($this->test_post_id, 'test_subtitle', 'Custom Test Subtitle');
        update_post_meta($this->test_post_id, 'test_description', 'Custom description from meta field');
        update_post_meta($this->test_post_id, 'test_url', 'https://example.com/custom-link');
        update_post_meta($this->test_post_id, 'featured_flag', 'yes');
        
        // Initialize classes if they exist
        if (class_exists('CardCrafter_Dynamic_Tags_Manager')) {
            $this->manager = CardCrafter_Dynamic_Tags_Manager::get_instance();
        }
        
        if (class_exists('CardCrafter_Elementor_Widget')) {
            $this->widget = new CardCrafter_Elementor_Widget();
        }
    }
    
    public function tearDown(): void
    {
        wp_delete_post($this->test_post_id, true);
        parent::tearDown();
    }
    
    /**
     * Test dynamic tags manager instantiation
     */
    public function test_dynamic_tags_manager_instantiation()
    {
        if (!class_exists('CardCrafter_Dynamic_Tags_Manager')) {
            $this->markTestSkipped('Dynamic Tags Manager class not available');
        }
        
        $this->assertInstanceOf('CardCrafter_Dynamic_Tags_Manager', $this->manager);
        $this->assertNotNull($this->manager);
    }
    
    /**
     * Test field plugin detection
     */
    public function test_available_field_plugins_detection()
    {
        if (!$this->manager) {
            $this->markTestSkipped('Dynamic Tags Manager not available');
        }
        
        $plugins = $this->manager->get_available_field_plugins();
        
        $this->assertIsArray($plugins);
        
        // Test ACF detection (if available)
        if (function_exists('get_field')) {
            $this->assertArrayHasKey('acf', $plugins);
            $this->assertEquals('Advanced Custom Fields', $plugins['acf']['name']);
        }
        
        // Test Meta Box detection (if available)
        if (function_exists('rwmb_meta')) {
            $this->assertArrayHasKey('metabox', $plugins);
            $this->assertEquals('Meta Box', $plugins['metabox']['name']);
        }
    }
    
    /**
     * Test dynamic field value retrieval
     */
    public function test_dynamic_field_value_retrieval()
    {
        if (!$this->manager) {
            $this->markTestSkipped('Dynamic Tags Manager not available');
        }
        
        // Test meta field retrieval
        $subtitle = $this->manager->get_field_value('test_subtitle', $this->test_post_id, 'meta');
        $this->assertEquals('Custom Test Subtitle', $subtitle);
        
        $description = $this->manager->get_field_value('test_description', $this->test_post_id, 'meta');
        $this->assertEquals('Custom description from meta field', $description);
        
        $url = $this->manager->get_field_value('test_url', $this->test_post_id, 'meta');
        $this->assertEquals('https://example.com/custom-link', $url);
        
        // Test non-existent field
        $empty = $this->manager->get_field_value('non_existent_field', $this->test_post_id, 'meta');
        $this->assertEquals('', $empty);
    }
    
    /**
     * Test dynamic content processing
     */
    public function test_dynamic_content_processing()
    {
        if (!$this->manager) {
            $this->markTestSkipped('Dynamic Tags Manager not available');
        }
        
        $settings = [
            'dynamic_title_field' => [
                'handler' => 'meta',
                'field_key' => 'test_subtitle'
            ],
            'dynamic_description_field' => [
                'handler' => 'meta',
                'field_key' => 'test_description'
            ]
        ];
        
        $processed = $this->manager->process_dynamic_content($settings, $this->test_post_id);
        
        $this->assertArrayHasKey('title_field', $processed);
        $this->assertArrayHasKey('description_field', $processed);
        $this->assertEquals('Custom Test Subtitle', $processed['title_field']);
        $this->assertEquals('Custom description from meta field', $processed['description_field']);
    }
    
    /**
     * Test image field processing
     */
    public function test_image_field_processing()
    {
        if (!$this->manager) {
            $this->markTestSkipped('Dynamic Tags Manager not available');
        }
        
        // Create a test attachment
        $attachment_id = $this->factory->attachment->create([
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'Test Image'
        ]);
        
        update_post_meta($this->test_post_id, 'test_image_id', $attachment_id);
        
        // Test image ID processing
        $processed_image = $this->manager->get_field_value('test_image_id', $this->test_post_id, 'meta');
        $this->assertEquals($attachment_id, $processed_image);
        
        // Clean up
        wp_delete_attachment($attachment_id, true);
    }
    
    /**
     * Test widget dynamic content options
     */
    public function test_widget_dynamic_content_options()
    {
        if (!$this->widget) {
            $this->markTestSkipped('CardCrafter Elementor Widget not available');
        }
        
        // Use reflection to test private methods
        $reflection = new ReflectionClass($this->widget);
        
        $get_dynamic_options_method = $reflection->getMethod('get_dynamic_field_options');
        $get_dynamic_options_method->setAccessible(true);
        $options = $get_dynamic_options_method->invoke($this->widget);
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('post_title', $options);
        $this->assertArrayHasKey('post_content', $options);
        $this->assertArrayHasKey('post_excerpt', $options);
        $this->assertArrayHasKey('featured_image', $options);
        $this->assertArrayHasKey('custom_field', $options);
    }
    
    /**
     * Test dynamic filtering functionality
     */
    public function test_dynamic_filtering()
    {
        if (!$this->widget) {
            $this->markTestSkipped('CardCrafter Elementor Widget not available');
        }
        
        // Create test posts with different meta values
        $featured_post = $this->factory->post->create([
            'post_title' => 'Featured Post',
            'post_status' => 'publish'
        ]);
        update_post_meta($featured_post, 'featured', 'yes');
        
        $normal_post = $this->factory->post->create([
            'post_title' => 'Normal Post',
            'post_status' => 'publish'
        ]);
        update_post_meta($normal_post, 'featured', 'no');
        
        $reflection = new ReflectionClass($this->widget);
        $apply_filters_method = $reflection->getMethod('apply_dynamic_filters');
        $apply_filters_method->setAccessible(true);
        
        $args = [
            'post_type' => 'post',
            'posts_per_page' => 10
        ];
        
        $settings = [
            'filter_by_meta' => "meta_key=featured\nmeta_value=yes"
        ];
        
        $filtered_args = $apply_filters_method->invoke($this->widget, $args, $settings);
        
        $this->assertArrayHasKey('meta_query', $filtered_args);
        $this->assertIsArray($filtered_args['meta_query']);
        $this->assertEquals('featured', $filtered_args['meta_query'][0]['key']);
        $this->assertEquals('yes', $filtered_args['meta_query'][0]['value']);
        
        // Clean up
        wp_delete_post($featured_post, true);
        wp_delete_post($normal_post, true);
    }
    
    /**
     * Test ACF field processing (if ACF is available)
     */
    public function test_acf_field_processing()
    {
        if (!function_exists('get_field')) {
            $this->markTestSkipped('ACF not available');
        }
        
        if (!$this->manager) {
            $this->markTestSkipped('Dynamic Tags Manager not available');
        }
        
        // Mock ACF field data
        $mock_field_value = 'ACF Test Value';
        
        // Test ACF field retrieval
        $value = $this->manager->get_field_value('test_acf_field', $this->test_post_id, 'acf');
        
        // Since we can't easily mock ACF in unit tests, we test the structure
        $this->assertIsString($value);
    }
    
    /**
     * Test dynamic capabilities reporting
     */
    public function test_dynamic_capabilities()
    {
        if (!$this->manager) {
            $this->markTestSkipped('Dynamic Tags Manager not available');
        }
        
        $capabilities = $this->manager->get_capabilities();
        
        $this->assertIsArray($capabilities);
        $this->assertArrayHasKey('field_plugins', $capabilities);
        $this->assertArrayHasKey('dynamic_tags_supported', $capabilities);
        $this->assertArrayHasKey('supported_field_types', $capabilities);
        
        $this->assertIsArray($capabilities['field_plugins']);
        $this->assertIsBool($capabilities['dynamic_tags_supported']);
        $this->assertIsArray($capabilities['supported_field_types']);
        
        // Test supported field types
        $supported_types = $capabilities['supported_field_types'];
        $this->assertContains('text', $supported_types);
        $this->assertContains('image', $supported_types);
        $this->assertContains('url', $supported_types);
    }
    
    /**
     * Test widget with dynamic content enabled
     */
    public function test_widget_with_dynamic_content()
    {
        if (!$this->widget) {
            $this->markTestSkipped('CardCrafter Elementor Widget not available');
        }
        
        $settings = [
            'data_mode' => 'wordpress',
            'post_type' => 'post',
            'posts_per_page' => 5,
            'enable_dynamic_content' => 'yes',
            'dynamic_title_source' => 'post_title',
            'dynamic_subtitle_source' => 'custom_field',
            'dynamic_subtitle_custom' => 'test_subtitle',
            'dynamic_description_source' => 'post_excerpt',
            'dynamic_image_source' => 'featured_image',
            'dynamic_link_source' => 'post_url'
        ];
        
        $reflection = new ReflectionClass($this->widget);
        $get_data_method = $reflection->getMethod('get_wordpress_data');
        $get_data_method->setAccessible(true);
        
        $data = $get_data_method->invoke($this->widget, $settings);
        
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        
        if (count($data) > 0) {
            $first_item = $data[0];
            $this->assertArrayHasKey('id', $first_item);
            $this->assertArrayHasKey('title', $first_item);
            $this->assertArrayHasKey('subtitle', $first_item);
            $this->assertArrayHasKey('description', $first_item);
            $this->assertArrayHasKey('image', $first_item);
            $this->assertArrayHasKey('link', $first_item);
        }
    }
    
    /**
     * Test error handling for invalid field configurations
     */
    public function test_error_handling_invalid_fields()
    {
        if (!$this->manager) {
            $this->markTestSkipped('Dynamic Tags Manager not available');
        }
        
        // Test with empty field key
        $empty_result = $this->manager->get_field_value('', $this->test_post_id, 'meta');
        $this->assertEquals('', $empty_result);
        
        // Test with invalid handler
        $invalid_result = $this->manager->get_field_value('test_field', $this->test_post_id, 'invalid_handler');
        $this->assertEquals('', $invalid_result);
        
        // Test with non-existent post
        $non_existent_result = $this->manager->get_field_value('test_subtitle', 999999, 'meta');
        $this->assertEquals('', $non_existent_result);
    }
    
    /**
     * Test taxonomy filtering
     */
    public function test_taxonomy_filtering()
    {
        if (!$this->widget) {
            $this->markTestSkipped('CardCrafter Elementor Widget not available');
        }
        
        // Create test category
        $category_id = $this->factory->category->create([
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);
        
        // Create post in category
        $categorized_post = $this->factory->post->create([
            'post_title' => 'Categorized Post',
            'post_status' => 'publish',
            'post_category' => [$category_id]
        ]);
        
        $reflection = new ReflectionClass($this->widget);
        $apply_filters_method = $reflection->getMethod('apply_dynamic_filters');
        $apply_filters_method->setAccessible(true);
        
        $args = [
            'post_type' => 'post',
            'posts_per_page' => 10
        ];
        
        $settings = [
            'filter_by_taxonomy' => 'category',
            'filter_taxonomy_terms' => 'test-category'
        ];
        
        $filtered_args = $apply_filters_method->invoke($this->widget, $args, $settings);
        
        $this->assertArrayHasKey('tax_query', $filtered_args);
        $this->assertIsArray($filtered_args['tax_query']);
        $this->assertEquals('category', $filtered_args['tax_query'][0]['taxonomy']);
        $this->assertEquals(['test-category'], $filtered_args['tax_query'][0]['terms']);
        
        // Clean up
        wp_delete_post($categorized_post, true);
        wp_delete_category($category_id);
    }
}