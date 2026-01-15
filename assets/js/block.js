/**
 * CardCrafter Gutenberg Block
 * 
 * Provides a visual block editor interface for creating card grids
 * from JSON data sources with live preview and configuration options.
 */
(function (blocks, editor, components, serverSideRender, element) {
    const el = element.createElement;
    const { InspectorControls } = editor;
    const { PanelBody, TextControl, ToggleControl, SelectControl, ExternalLink } = components;

    blocks.registerBlockType('cardcrafter/data-grid', {
        title: 'CardCrafter',
        description: 'Transform JSON data into beautiful, responsive card grids for portfolios, team directories, and product showcases.',
        icon: el('svg', { 
            width: 24, 
            height: 24, 
            viewBox: '0 0 24 24', 
            fill: 'none',
            style: { color: '#e11d48' }
        },
            // Card grid outline
            el('rect', { 
                x: 3, y: 3, width: 8, height: 8, 
                rx: 1, 
                stroke: 'currentColor', 
                strokeWidth: 2,
                fill: 'currentColor',
                fillOpacity: 0.1
            }),
            el('rect', { 
                x: 13, y: 3, width: 8, height: 8, 
                rx: 1, 
                stroke: 'currentColor', 
                strokeWidth: 2,
                fill: 'currentColor',
                fillOpacity: 0.1
            }),
            el('rect', { 
                x: 3, y: 13, width: 8, height: 8, 
                rx: 1, 
                stroke: 'currentColor', 
                strokeWidth: 2,
                fill: 'currentColor',
                fillOpacity: 0.1
            }),
            el('rect', { 
                x: 13, y: 13, width: 8, height: 8, 
                rx: 1, 
                stroke: 'currentColor', 
                strokeWidth: 2,
                fill: 'currentColor',
                fillOpacity: 0.1
            })
        ),
        category: 'widgets',

        // Define block attributes to persist in database
        attributes: {
            source: { type: 'string', default: '' },
            layout: { type: 'string', default: 'grid' },
            search: { type: 'boolean', default: true },
            sort: { type: 'boolean', default: true },
            cards_per_row: { type: 'number', default: 3 }
        },

        edit: function (props) {
            const { attributes, setAttributes } = props;
            
            const updateSource = (value) => setAttributes({ source: value });
            const updateLayout = (value) => setAttributes({ layout: value });
            const updateSearch = (value) => setAttributes({ search: value });
            const updateSort = (value) => setAttributes({ sort: value });
            const updateCardsPerRow = (value) => setAttributes({ cards_per_row: parseInt(value) || 3 });

            return [
                // Sidebar controls (Inspector)
                el(InspectorControls, { key: 'controls' },
                    el(PanelBody, { title: 'Data Settings', initialOpen: true },
                        el(TextControl, {
                            label: 'JSON Data Source URL',
                            value: attributes.source,
                            onChange: updateSource,
                            help: 'URL to your JSON data source (must be publicly accessible).'
                        }),
                        el(SelectControl, {
                            label: 'Demo Data Sources',
                            value: attributes.source,
                            options: window.cardcrafterData ? window.cardcrafterData.demoUrls : [
                                { label: 'Select a demo...', value: '' },
                                { label: '👥 Team Directory', value: '' },
                                { label: '📦 Product Showcase', value: '' },
                                { label: '🎨 Portfolio Gallery', value: '' }
                            ],
                            onChange: updateSource,
                            help: 'Try different data formats with sample card content.'
                        }),
                        el(SelectControl, {
                            label: 'Layout Type',
                            value: attributes.layout,
                            options: [
                                { label: 'Grid Layout', value: 'grid' },
                                { label: 'Masonry Layout', value: 'masonry' },
                                { label: 'List Layout', value: 'list' }
                            ],
                            onChange: updateLayout,
                            help: 'Choose how cards are arranged: grid, masonry, or vertical list.'
                        }),
                        el(TextControl, {
                            label: 'Cards Per Row',
                            value: attributes.cards_per_row,
                            type: 'number',
                            onChange: updateCardsPerRow,
                            help: 'Number of cards to display per row (1-6, default: 3).'
                        })
                    ),
                    el(PanelBody, { title: 'Interactive Features', initialOpen: false },
                        el(ToggleControl, {
                            label: 'Enable Search',
                            checked: attributes.search,
                            onChange: updateSearch,
                            help: 'Add a search bar to filter cards by content.'
                        }),
                        el(ToggleControl, {
                            label: 'Enable Sorting',
                            checked: attributes.sort,
                            onChange: updateSort,
                            help: 'Allow users to sort cards alphabetically or by custom fields.'
                        }),
                        el('div', { className: 'cc-block-help', style: { marginTop: '20px', borderTop: '1px solid #eee', paddingTop: '15px' } },
                            el('p', null, 'Need help? Check the '),
                            el(ExternalLink, { href: 'https://github.com/TableCrafter/cardcrafter-data-grids' }, 'Documentation')
                        )
                    )
                ),

                // Main visual editor view (Live Preview)
                el('div', { className: props.className, key: 'preview' },
                    attributes.source 
                        ? el(serverSideRender, {
                            block: 'cardcrafter/data-grid',
                            attributes: attributes
                        })
                        : el('div', { 
                            className: 'cardcrafter-block-preview',
                            style: { 
                                padding: '20px', 
                                border: '1px solid #ddd', 
                                borderRadius: '4px',
                                textAlign: 'center',
                                background: '#f9f9f9'
                            }
                        },
                            el('div', { style: { color: '#999' } },
                                el('div', { 
                                    className: 'dashicons dashicons-grid-view',
                                    style: { fontSize: '48px', marginBottom: '10px' }
                                }),
                                el('p', null, 'Configure your data source in the sidebar to create beautiful card grids.'),
                                el('p', { style: { fontSize: '12px' } }, 
                                    'Try one of the demo data sources to get started quickly.'
                                )
                            )
                        )
                )
            ];
        },

        save: function () {
            // Return null to use PHP render callback
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.serverSideRender,
    window.wp.element
);