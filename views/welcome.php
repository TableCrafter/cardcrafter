<?php
// Get demo URLs
$team_url = CARDCRAFTER_URL . 'demo-data/team.json';
$products_url = CARDCRAFTER_URL . 'demo-data/products.json';
$portfolio_url = CARDCRAFTER_URL . 'demo-data/portfolio.json';
?>

<div class="wrap">
    <style>
        .cc-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        /* Header Section - Clean & Simple */
        .cc-header {
            margin-bottom: 48px;
        }
        .cc-badge {
            background: #f3f4f6;
            color: #374151;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 12px;
        }
        .cc-title {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: #111827;
        }
        .cc-subtitle {
            color: #6b7280;
            font-size: 16px;
            margin: 0;
        }
        
        /* Demo Section - No Nested Divs */
        .cc-demo {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 48px;
        }
        .cc-demo h2 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: #111827;
        }
        .cc-demo p {
            color: #6b7280;
            margin: 0 0 24px 0;
        }
        
        /* Controls - Simple Grid */
        .cc-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .cc-control label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }
        .cc-control select,
        .cc-control input[type="text"],
        .cc-control input[type="url"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        .cc-control input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            transform: scale(1.3);
        }
        
        /* Demo Preview - Clean */
        .cc-preview {
            min-height: 400px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fafafa;
        }
        
        /* Features - Simple Cards */
        .cc-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }
        .cc-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
        }
        .cc-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 8px 0 8px 0;
            color: #111827;
        }
        .cc-card p {
            color: #6b7280;
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
        }
        .cc-icon {
            font-size: 20px;
        }
        
        /* Sidebar - Clean */
        .cc-sidebar {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
        }
        
        /* Layout */
        .cc-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 32px;
        }
        
        @media (max-width: 1024px) {
            .cc-layout {
                grid-template-columns: 1fr;
            }
        }
        
        /* Loading */
        .cc-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 80px 24px;
            color: #6b7280;
        }
        .cc-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 12px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <div class="cc-container">
        <!-- Header -->
        <header class="cc-header">
            <div class="cc-badge">🚀 Just Activated</div>
            <h1 class="cc-title">Welcome to CardCrafter</h1>
            <p class="cc-subtitle">Transform any data into beautiful, responsive card layouts</p>
        </header>

        <!-- Main Layout -->
        <div class="cc-layout">
            <!-- Main Content -->
            <main>
                <!-- Demo Section -->
                <section class="cc-demo">
                    <h2>✨ Live Demo</h2>
                    <p>Try different layouts and datasets below</p>
                    
                    <!-- Controls -->
                    <div class="cc-controls">
                        <div class="cc-control">
                            <label for="dataset">Dataset</label>
                            <select id="dataset">
                                <option value="<?php echo esc_url($team_url); ?>">👥 Team Directory</option>
                                <option value="<?php echo esc_url($products_url); ?>">🛍️ Product Showcase</option>
                                <option value="<?php echo esc_url($portfolio_url); ?>">🎨 Portfolio Gallery</option>
                            </select>
                        </div>
                        <div class="cc-control">
                            <label for="layout">Layout</label>
                            <select id="layout">
                                <option value="grid">Grid</option>
                                <option value="masonry">Masonry</option>
                                <option value="list">List</option>
                            </select>
                        </div>
                        <div class="cc-control">
                            <label for="columns">Columns</label>
                            <select id="columns">
                                <option value="2">2 Columns</option>
                                <option value="3" selected>3 Columns</option>
                                <option value="4">4 Columns</option>
                            </select>
                        </div>
                        <div class="cc-control">
                            <label>
                                <input type="checkbox" id="search" checked> Enable Search
                            </label>
                        </div>
                    </div>
                    
                    <!-- Preview -->
                    <div id="demo-container" class="cc-preview">
                        <div class="cc-loading">
                            <div class="cc-spinner"></div>
                            <p>Loading demo...</p>
                        </div>
                    </div>
                </section>
                
                <!-- Features -->
                <section class="cc-features">
                    <div class="cc-card">
                        <div class="cc-icon">🎨</div>
                        <h3>Multiple Layouts</h3>
                        <p>Grid, masonry, and list layouts. All responsive and customizable.</p>
                    </div>
                    <div class="cc-card">
                        <div class="cc-icon">📊</div>
                        <h3>Any Data Source</h3>
                        <p>JSON APIs, WordPress posts, WooCommerce products, or CSV files.</p>
                    </div>
                    <div class="cc-card">
                        <div class="cc-icon">🔍</div>
                        <h3>Live Search</h3>
                        <p>Real-time search and filtering built-in.</p>
                    </div>
                    <div class="cc-card">
                        <div class="cc-icon">📱</div>
                        <h3>Mobile Ready</h3>
                        <p>Perfect on all devices with responsive breakpoints.</p>
                    </div>
                    <div class="cc-card">
                        <div class="cc-icon">⚡</div>
                        <h3>Fast Performance</h3>
                        <p>Smart caching and optimized code for speed.</p>
                    </div>
                    <div class="cc-card">
                        <div class="cc-icon">🔧</div>
                        <h3>WordPress Native</h3>
                        <p>Display WordPress posts and custom fields easily.</p>
                    </div>
                </section>
            </main>
            
            <!-- Sidebar -->
            <aside class="cc-sidebar">
                <h3 style="margin-top: 0;">Getting Started</h3>
                <p>Use the shortcode to add cards to any page:</p>
                <code style="display: block; background: #f3f4f6; padding: 12px; border-radius: 4px; margin: 12px 0; font-size: 12px;">
                    [cardcrafter source="your-data.json"]
                </code>
                
                <h4>Quick Examples</h4>
                <ul style="margin: 0; padding-left: 0; list-style: none;">
                    <li><a href="#" class="demo-link" data-url="<?php echo esc_url($team_url); ?>" style="display: block; padding: 8px 12px; text-decoration: none; border-radius: 4px; margin-bottom: 4px; color: #374151; border: 1px solid #e5e7eb;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">👥 Team directory</a></li>
                    <li><a href="#" class="demo-link" data-url="<?php echo esc_url($products_url); ?>" style="display: block; padding: 8px 12px; text-decoration: none; border-radius: 4px; margin-bottom: 4px; color: #374151; border: 1px solid #e5e7eb;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">🛍️ Product showcase</a></li>
                    <li><a href="#" class="demo-link" data-url="<?php echo esc_url($portfolio_url); ?>" style="display: block; padding: 8px 12px; text-decoration: none; border-radius: 4px; margin-bottom: 4px; color: #374151; border: 1px solid #e5e7eb;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">🎨 Portfolio gallery</a></li>
                    <li style="opacity: 0.6;"><span style="display: block; padding: 8px 12px; color: #9ca3af; font-style: italic;">📝 Blog post grid (WordPress only)</span></li>
                </ul>
            </aside>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let demoInstance = null;
        
        function loadDemo() {
            const container = $('#demo-container');
            const dataset = $('#dataset').val();
            const layout = $('#layout').val();
            const columns = $('#columns').val();
            const enableSearch = $('#search').is(':checked');
            
            console.log('Loading demo with:', { dataset, layout, columns, enableSearch });
            
            container.html('<div class="cc-loading"><div class="cc-spinner"></div><p>Loading cards...</p></div>');
            
            if (typeof window.CardCrafter === 'undefined') {
                container.html('<div class="cc-loading"><p style="color: #ef4444;">CardCrafter library not loaded. Please refresh.</p></div>');
                return;
            }
            
            // Destroy previous instance first
            if (demoInstance) {
                try {
                    demoInstance.destroy();
                } catch(e) {
                    console.log('Error destroying previous instance:', e);
                }
                demoInstance = null;
            }
            
            // Clear container and use consistent ID
            container.empty();
            const containerId = '#demo-container';
            
            const config = {
                selector: containerId,
                source: dataset,
                layout: layout,
                columns: parseInt(columns),
                itemsPerPage: 6,
                search: enableSearch,
                fields: {
                    image: 'image',
                    title: 'title',
                    subtitle: 'subtitle',
                    description: 'description',
                    link: 'link'
                }
            };
            
            console.log('Creating CardCrafter with config:', config);
            
            try {
                demoInstance = new CardCrafter(config);
                console.log('CardCrafter instance created successfully');
            } catch(e) {
                console.error('Error creating CardCrafter instance:', e);
                container.html('<div class="cc-loading"><p style="color: #ef4444;">Error loading demo: ' + e.message + '</p></div>');
            }
        }
        
        // Event handlers
        $('#dataset, #layout, #columns, #search').on('change', function() {
            console.log('Form control changed:', this.id, this.value || this.checked);
            loadDemo();
        });
        
        // Sidebar demo links
        $('.demo-link').on('click', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            console.log('Demo link clicked:', url);
            $('#dataset').val(url);
            loadDemo();
        });
        
        // Auto-load demo
        console.log('Setting up auto-load demo...');
        setTimeout(function() {
            console.log('Auto-loading demo...');
            loadDemo();
        }, 1000);
    });
    </script>
</div>