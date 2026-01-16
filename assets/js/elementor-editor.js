/**
 * CardCrafter Elementor Editor Integration
 * 
 * Enhances the Elementor editor experience with live preview capabilities,
 * real-time control updates, and improved user interface interactions.
 * 
 * @version 1.8.0
 */

(function ($) {
    'use strict';

    /**
     * CardCrafter Elementor Editor Handler
     */
    var CardCrafterElementorEditor = {

        /**
         * Initialize the editor handler
         */
        init: function () {
            // Wait for Elementor editor to be ready
            $(window).on('elementor:init', this.onElementorInit.bind(this));
        },

        /**
         * Handle Elementor editor initialization
         */
        onElementorInit: function () {
            // Add hooks for editor interactions
            elementor.hooks.addAction('panel/open_editor/widget/cardcrafter-data-grids', this.onWidgetEdit.bind(this));
            
            // Handle control changes
            elementor.channels.editor.on('change', this.onControlChange.bind(this));
            
            // Handle widget preview updates
            elementor.channels.deviceMode.on('change', this.onDeviceModeChange.bind(this));
            
            // Add custom CSS for better editor experience
            this.addEditorStyles();
            
            // Initialize editor enhancements
            this.initEditorEnhancements();
        },

        /**
         * Handle widget edit panel opening
         */
        onWidgetEdit: function (panel, model, view) {
            // Add helpful tips and guidance
            this.addEditorGuidance(panel);
            
            // Initialize data source preview
            this.initDataSourcePreview(model);
            
            // Add real-time field validation
            this.addFieldValidation(panel);
        },

        /**
         * Handle control value changes
         */
        onControlChange: function (controlView, elementView) {
            if (!elementView.model || elementView.model.get('widgetType') !== 'cardcrafter-data-grids') {
                return;
            }

            var controlName = controlView.model.get('name');
            var value = controlView.getControlValue();
            
            // Handle specific control changes
            switch (controlName) {
                case 'data_mode':
                    this.handleDataModeChange(elementView, value);
                    break;
                case 'layout':
                    this.handleLayoutChange(elementView, value);
                    break;
                case 'columns':
                    this.handleColumnsChange(elementView, value);
                    break;
                case 'json_url':
                    this.validateJsonUrl(elementView, value);
                    break;
                case 'custom_json':
                    this.validateCustomJson(elementView, value);
                    break;
            }

            // Trigger preview update with debouncing
            this.debouncePreviewUpdate(elementView);
        },

        /**
         * Handle device mode changes
         */
        onDeviceModeChange: function (deviceMode) {
            // Update responsive previews for all CardCrafter widgets
            $('.elementor-widget-cardcrafter-data-grids').each(function () {
                var elementView = $(this).data('elementView');
                if (elementView) {
                    CardCrafterElementorEditor.updateResponsivePreview(elementView, deviceMode);
                }
            });
        },

        /**
         * Handle data mode changes
         */
        handleDataModeChange: function (elementView, dataMode) {
            var $element = elementView.$el;
            var $preview = $element.find('.cardcrafter-elementor-preview');
            
            // Update preview based on data mode
            switch (dataMode) {
                case 'demo':
                    this.showDemoPreview($preview);
                    break;
                case 'wordpress':
                    this.showWordPressPreview($preview);
                    break;
                case 'json_url':
                    this.showJsonUrlPreview($preview);
                    break;
                case 'custom_json':
                    this.showCustomJsonPreview($preview);
                    break;
            }
        },

        /**
         * Handle layout changes
         */
        handleLayoutChange: function (elementView, layout) {
            var $element = elementView.$el;
            var $preview = $element.find('.cardcrafter-preview-grid');
            
            // Update preview grid class
            $preview.removeClass('cardcrafter-preview-grid-masonry cardcrafter-preview-grid-list')
                    .addClass('cardcrafter-preview-' + layout);
                    
            // Show layout-specific guidance
            this.showLayoutGuidance($element, layout);
        },

        /**
         * Handle columns changes
         */
        handleColumnsChange: function (elementView, columns) {
            var $element = elementView.$el;
            var $preview = $element.find('.cardcrafter-preview-grid');
            
            // Update preview grid columns
            $preview.css('grid-template-columns', 'repeat(' + columns + ', 1fr)');
        },

        /**
         * Validate JSON URL
         */
        validateJsonUrl: function (elementView, url) {
            if (!url || !url.url) {
                return;
            }

            // Basic URL validation
            try {
                new URL(url.url);
                this.showValidationMessage(elementView, 'json_url', 'valid', 'URL format is valid');
                
                // Test URL accessibility (with timeout)
                this.testJsonUrl(elementView, url.url);
            } catch (error) {
                this.showValidationMessage(elementView, 'json_url', 'error', 'Invalid URL format');
            }
        },

        /**
         * Test JSON URL accessibility
         */
        testJsonUrl: function (elementView, url) {
            var self = this;
            
            // Use WordPress AJAX to test URL
            $.ajax({
                url: cardcrafterElementor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cardcrafter_test_url',
                    url: url,
                    nonce: cardcrafterElementor.nonce
                },
                timeout: 5000,
                success: function (response) {
                    if (response.success) {
                        self.showValidationMessage(elementView, 'json_url', 'valid', 'URL is accessible');
                    } else {
                        self.showValidationMessage(elementView, 'json_url', 'warning', response.data || 'URL may not be accessible');
                    }
                },
                error: function () {
                    self.showValidationMessage(elementView, 'json_url', 'warning', 'Cannot verify URL accessibility');
                }
            });
        },

        /**
         * Validate custom JSON
         */
        validateCustomJson: function (elementView, json) {
            if (!json) {
                return;
            }

            try {
                var parsed = JSON.parse(json);
                
                if (!Array.isArray(parsed)) {
                    this.showValidationMessage(elementView, 'custom_json', 'warning', 'JSON should be an array of objects');
                } else if (parsed.length === 0) {
                    this.showValidationMessage(elementView, 'custom_json', 'warning', 'JSON array is empty');
                } else {
                    this.showValidationMessage(elementView, 'custom_json', 'valid', 'Valid JSON with ' + parsed.length + ' items');
                }
            } catch (error) {
                this.showValidationMessage(elementView, 'custom_json', 'error', 'Invalid JSON format: ' + error.message);
            }
        },

        /**
         * Show validation message
         */
        showValidationMessage: function (elementView, controlName, type, message) {
            var $control = elementView.$el.find('[data-setting="' + controlName + '"]').closest('.elementor-control');
            
            // Remove existing validation messages
            $control.find('.cardcrafter-validation').remove();
            
            // Add new validation message
            var iconClass = type === 'valid' ? 'eicon-check' : (type === 'warning' ? 'eicon-warning' : 'eicon-close');
            var colorClass = type === 'valid' ? 'success' : (type === 'warning' ? 'warning' : 'error');
            
            var $message = $('<div class="cardcrafter-validation cardcrafter-validation-' + colorClass + '">' +
                '<i class="' + iconClass + '"></i> ' + message + '</div>');
            
            $control.append($message);
        },

        /**
         * Show demo preview
         */
        showDemoPreview: function ($preview) {
            $preview.find('.cardcrafter-demo-content p').html(
                'Showing demo team data. <strong>Perfect for instant preview!</strong>'
            );
        },

        /**
         * Show WordPress preview
         */
        showWordPressPreview: function ($preview) {
            $preview.find('.cardcrafter-demo-content p').html(
                'Will display your WordPress content. <strong>Configure post type below.</strong>'
            );
        },

        /**
         * Show JSON URL preview
         */
        showJsonUrlPreview: function ($preview) {
            $preview.find('.cardcrafter-demo-content p').html(
                'Will fetch data from external API. <strong>Enter your JSON URL below.</strong>'
            );
        },

        /**
         * Show custom JSON preview
         */
        showCustomJsonPreview: function ($preview) {
            $preview.find('.cardcrafter-demo-content p').html(
                'Will use your custom JSON data. <strong>Paste JSON below.</strong>'
            );
        },

        /**
         * Show layout guidance
         */
        showLayoutGuidance: function ($element, layout) {
            var guidance = {
                'grid': 'Grid layout displays cards in a uniform grid pattern.',
                'masonry': 'Masonry layout adjusts card heights dynamically for varied content.',
                'list': 'List layout displays cards in a single column with more detail.'
            };
            
            // Show guidance tooltip or notification
            this.showTooltip($element, guidance[layout] || '');
        },

        /**
         * Show tooltip
         */
        showTooltip: function ($element, message) {
            // Remove existing tooltips
            $('.cardcrafter-tooltip').remove();
            
            if (!message) return;
            
            var $tooltip = $('<div class="cardcrafter-tooltip">' + message + '</div>');
            $('body').append($tooltip);
            
            // Position and show tooltip
            var offset = $element.offset();
            $tooltip.css({
                top: offset.top - $tooltip.outerHeight() - 10,
                left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
            }).fadeIn();
            
            // Auto-hide after 3 seconds
            setTimeout(function () {
                $tooltip.fadeOut(function () {
                    $tooltip.remove();
                });
            }, 3000);
        },

        /**
         * Add editor guidance
         */
        addEditorGuidance: function (panel) {
            // Add helpful tips to the panel
            setTimeout(function () {
                var $panel = panel.$el || $(panel);
                
                // Add quick start guide
                var $guide = $('<div class="cardcrafter-quick-guide">' +
                    '<h4><i class="eicon-info-circle"></i> Quick Start</h4>' +
                    '<ul>' +
                    '<li><strong>Demo Mode:</strong> Instant preview with sample data</li>' +
                    '<li><strong>WordPress Data:</strong> Use your existing posts/pages</li>' +
                    '<li><strong>JSON URL:</strong> Connect to external APIs</li>' +
                    '<li><strong>Custom JSON:</strong> Paste your own data</li>' +
                    '</ul>' +
                    '</div>');
                
                $panel.find('#elementor-panel-content-wrapper').prepend($guide);
            }, 100);
        },

        /**
         * Initialize data source preview
         */
        initDataSourcePreview: function (model) {
            // Add real-time data preview for different sources
            var dataMode = model.get('settings').get('data_mode');
            
            if (dataMode === 'wordpress') {
                this.loadWordPressDataPreview(model);
            }
        },

        /**
         * Load WordPress data preview
         */
        loadWordPressDataPreview: function (model) {
            var settings = model.get('settings');
            var postType = settings.get('post_type') || 'post';
            
            // Show preview of available WordPress content
            $.ajax({
                url: cardcrafterElementor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cardcrafter_preview_wordpress_data',
                    post_type: postType,
                    nonce: cardcrafterElementor.nonce
                },
                success: function (response) {
                    if (response.success && response.data.count) {
                        CardCrafterElementorEditor.showDataPreviewInfo(
                            'Found ' + response.data.count + ' ' + postType + '(s) available'
                        );
                    }
                }
            });
        },

        /**
         * Show data preview info
         */
        showDataPreviewInfo: function (message) {
            elementor.notifications.showToast({
                message: message,
                type: 'info'
            });
        },

        /**
         * Add field validation
         */
        addFieldValidation: function (panel) {
            // Real-time validation for field mapping
            setTimeout(function () {
                var $panel = panel.$el || $(panel);
                
                $panel.find('[data-setting$="_field"]').on('input', function () {
                    var $input = $(this);
                    var value = $input.val();
                    
                    // Validate field name format
                    if (value && !/^[a-zA-Z_][a-zA-Z0-9_.]*$/.test(value)) {
                        $input.addClass('cardcrafter-field-error');
                        CardCrafterElementorEditor.showFieldError($input, 'Field names should contain only letters, numbers, dots, and underscores');
                    } else {
                        $input.removeClass('cardcrafter-field-error');
                        CardCrafterElementorEditor.hideFieldError($input);
                    }
                });
            }, 200);
        },

        /**
         * Show field error
         */
        showFieldError: function ($input, message) {
            $input.siblings('.cardcrafter-field-error-msg').remove();
            $input.after('<div class="cardcrafter-field-error-msg" style="color: #d63638; font-size: 11px; margin-top: 5px;">' + message + '</div>');
        },

        /**
         * Hide field error
         */
        hideFieldError: function ($input) {
            $input.siblings('.cardcrafter-field-error-msg').remove();
        },

        /**
         * Update responsive preview
         */
        updateResponsivePreview: function (elementView, deviceMode) {
            var $element = elementView.$el;
            var $preview = $element.find('.cardcrafter-preview-grid');
            
            // Adjust preview based on device mode
            var columns = {
                'desktop': 3,
                'tablet': 2,
                'mobile': 1
            };
            
            $preview.css('grid-template-columns', 'repeat(' + columns[deviceMode] + ', 1fr)');
        },

        /**
         * Add custom editor styles
         */
        addEditorStyles: function () {
            var styles = `
                <style id="cardcrafter-editor-styles">
                    .cardcrafter-quick-guide {
                        background: #f8f9fa;
                        border: 1px solid #e0e0e0;
                        border-radius: 8px;
                        padding: 15px;
                        margin-bottom: 20px;
                    }
                    .cardcrafter-quick-guide h4 {
                        margin: 0 0 10px 0;
                        color: #3b82f6;
                        font-size: 14px;
                    }
                    .cardcrafter-quick-guide ul {
                        margin: 0;
                        padding-left: 20px;
                    }
                    .cardcrafter-quick-guide li {
                        margin-bottom: 5px;
                        font-size: 12px;
                        color: #6b7280;
                    }
                    .cardcrafter-validation {
                        font-size: 11px;
                        padding: 5px 8px;
                        border-radius: 4px;
                        margin-top: 5px;
                    }
                    .cardcrafter-validation-success {
                        background: #d1fae5;
                        color: #065f46;
                        border: 1px solid #a7f3d0;
                    }
                    .cardcrafter-validation-warning {
                        background: #fef3c7;
                        color: #92400e;
                        border: 1px solid #fcd34d;
                    }
                    .cardcrafter-validation-error {
                        background: #fee2e2;
                        color: #991b1b;
                        border: 1px solid #fca5a5;
                    }
                    .cardcrafter-field-error {
                        border-color: #ef4444 !important;
                        box-shadow: 0 0 0 1px #ef4444;
                    }
                    .cardcrafter-tooltip {
                        position: absolute;
                        background: #1f2937;
                        color: white;
                        padding: 8px 12px;
                        border-radius: 6px;
                        font-size: 12px;
                        max-width: 250px;
                        z-index: 9999;
                        display: none;
                    }
                    .cardcrafter-tooltip:before {
                        content: '';
                        position: absolute;
                        top: 100%;
                        left: 50%;
                        transform: translateX(-50%);
                        border: 5px solid transparent;
                        border-top-color: #1f2937;
                    }
                </style>
            `;
            
            if (!$('#cardcrafter-editor-styles').length) {
                $('head').append(styles);
            }
        },

        /**
         * Initialize editor enhancements
         */
        initEditorEnhancements: function () {
            // Add keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
            
            // Add context menus
            this.addContextMenus();
        },

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts: function (e) {
            // Ctrl+Shift+C to copy widget settings
            if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
                var selectedElement = elementor.selection.getElements()[0];
                if (selectedElement && selectedElement.model.get('widgetType') === 'cardcrafter-data-grids') {
                    this.copyWidgetSettings(selectedElement);
                    e.preventDefault();
                }
            }
        },

        /**
         * Copy widget settings
         */
        copyWidgetSettings: function (elementView) {
            var settings = elementView.model.get('settings').toJSON();
            
            // Store in clipboard (simplified)
            var settingsJson = JSON.stringify(settings, null, 2);
            this.copyToClipboard(settingsJson);
            
            elementor.notifications.showToast({
                message: 'CardCrafter settings copied to clipboard',
                type: 'success'
            });
        },

        /**
         * Copy to clipboard helper
         */
        copyToClipboard: function (text) {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        },

        /**
         * Add context menus
         */
        addContextMenus: function () {
            // Add right-click context menu for CardCrafter widgets
            elementor.hooks.addAction('panel/open_editor/widget/cardcrafter-data-grids', function (panel, model, view) {
                setTimeout(function () {
                    view.$el.on('contextmenu', function (e) {
                        e.preventDefault();
                        CardCrafterElementorEditor.showContextMenu(e, view);
                    });
                }, 100);
            });
        },

        /**
         * Show context menu
         */
        showContextMenu: function (e, view) {
            var $menu = $('<div class="cardcrafter-context-menu">' +
                '<div class="menu-item" data-action="copy">Copy Settings</div>' +
                '<div class="menu-item" data-action="reset">Reset to Demo</div>' +
                '<div class="menu-item" data-action="export">Export Configuration</div>' +
                '</div>');
            
            $('body').append($menu);
            
            $menu.css({
                position: 'absolute',
                top: e.clientY,
                left: e.clientX,
                background: 'white',
                border: '1px solid #ccc',
                borderRadius: '4px',
                padding: '5px 0',
                boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
                zIndex: 9999
            });
            
            $menu.on('click', '.menu-item', function () {
                var action = $(this).data('action');
                CardCrafterElementorEditor.handleContextMenuAction(action, view);
                $menu.remove();
            });
            
            // Remove menu on click outside
            setTimeout(function () {
                $(document).one('click', function () {
                    $menu.remove();
                });
            }, 100);
        },

        /**
         * Handle context menu actions
         */
        handleContextMenuAction: function (action, view) {
            switch (action) {
                case 'copy':
                    this.copyWidgetSettings(view);
                    break;
                case 'reset':
                    this.resetToDemo(view);
                    break;
                case 'export':
                    this.exportConfiguration(view);
                    break;
            }
        },

        /**
         * Reset widget to demo mode
         */
        resetToDemo: function (view) {
            view.model.get('settings').set('data_mode', 'demo');
            elementor.notifications.showToast({
                message: 'Widget reset to demo mode',
                type: 'success'
            });
        },

        /**
         * Export configuration
         */
        exportConfiguration: function (view) {
            var settings = view.model.get('settings').toJSON();
            var config = {
                widget: 'cardcrafter-data-grids',
                version: '1.8.0',
                settings: settings,
                exported_at: new Date().toISOString()
            };
            
            var blob = new Blob([JSON.stringify(config, null, 2)], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'cardcrafter-config.json';
            a.click();
            URL.revokeObjectURL(url);
        },

        /**
         * Debounce preview updates
         */
        debouncePreviewUpdate: function (elementView) {
            clearTimeout(this.previewUpdateTimeout);
            this.previewUpdateTimeout = setTimeout(function () {
                elementView.render();
            }, 300);
        }
    };

    /**
     * Initialize when document ready
     */
    $(document).ready(function () {
        CardCrafterElementorEditor.init();
    });

    /**
     * Expose editor handler globally
     */
    window.CardCrafterElementorEditor = CardCrafterElementorEditor;

})(jQuery);