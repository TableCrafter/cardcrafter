/**
 * CardCrafter Elementor Frontend Integration
 * 
 * Handles frontend initialization of CardCrafter widgets within Elementor pages.
 * Ensures proper loading, AJAX compatibility, and responsive behavior.
 * 
 * @version 1.8.0
 */

(function ($) {
    'use strict';

    /**
     * CardCrafter Elementor Frontend Handler
     */
    var CardCrafterElementorHandler = {

        /**
         * Initialize the handler
         */
        init: function () {
            // Handle Elementor frontend
            $(window).on('elementor/frontend/init', this.onElementorFrontendInit.bind(this));
            
            // Handle AJAX content loading
            $(document).on('cardcrafter:widget_loaded', this.onWidgetLoaded.bind(this));
            
            // Handle responsive changes
            $(window).on('resize', this.debounce(this.handleResize.bind(this), 250));
        },

        /**
         * Handle Elementor frontend initialization
         */
        onElementorFrontendInit: function () {
            // Register widget handlers
            elementorFrontend.hooks.addAction('frontend/element_ready/cardcrafter-data-grids.default', this.initWidget.bind(this));
            
            // Handle Elementor Pro features
            if (typeof elementorProFrontend !== 'undefined') {
                this.handleElementorPro();
            }
        },

        /**
         * Initialize individual CardCrafter widgets
         */
        initWidget: function ($scope) {
            var $widget = $scope.find('.cardcrafter-elementor-widget');
            
            if (!$widget.length) {
                return;
            }

            // Get widget settings
            var widgetId = $widget.attr('id');
            var config = $widget.data('config');
            
            if (!config || typeof CardCrafter === 'undefined') {
                console.warn('CardCrafter: Widget configuration or library not found');
                return;
            }

            // Initialize CardCrafter
            this.initCardCrafter(widgetId, config);
            
            // Handle Elementor animations
            this.handleElementorAnimations($scope);
            
            // Handle responsive columns
            this.handleResponsiveColumns($widget, config);
            
            // Trigger widget loaded event
            $(document).trigger('cardcrafter:widget_loaded', [$widget, config]);
        },

        /**
         * Initialize CardCrafter instance
         */
        initCardCrafter: function (widgetId, config) {
            try {
                var instance = new CardCrafter({
                    selector: '#' + widgetId,
                    source: config.source || null,
                    layout: config.layout || 'grid',
                    columns: config.columns || 3,
                    fields: config.fields || {},
                    itemsPerPage: config.itemsPerPage || 12,
                    wpDataMode: config.wpDataMode || false,
                    data: config.data || null
                });

                // Store instance for future reference
                $('#' + widgetId).data('cardcrafter-instance', instance);
                
            } catch (error) {
                console.error('CardCrafter: Failed to initialize widget', error);
                this.showError(widgetId, error.message);
            }
        },

        /**
         * Handle Elementor animations
         */
        handleElementorAnimations: function ($scope) {
            var $animatedElements = $scope.find('.cardcrafter-card');
            
            // Add stagger delay for entrance animations
            $animatedElements.each(function (index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
            });

            // Trigger animations when in viewport
            if (typeof window.IntersectionObserver !== 'undefined') {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            $(entry.target).addClass('cardcrafter-animated');
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '50px'
                });

                $animatedElements.each(function () {
                    observer.observe(this);
                });
            }
        },

        /**
         * Handle responsive columns
         */
        handleResponsiveColumns: function ($widget, config) {
            var $grid = $widget.find('.cardcrafter-grid');
            
            if (!$grid.length) {
                return;
            }

            // Get Elementor's current device mode
            var deviceMode = this.getCurrentDeviceMode();
            var columns = this.getResponsiveColumns(config, deviceMode);
            
            // Apply responsive columns
            $grid.removeClass(function (index, className) {
                return (className.match(/(^|\s)cardcrafter-cols-\S+/g) || []).join(' ');
            }).addClass('cardcrafter-cols-' + columns);
        },

        /**
         * Get current device mode
         */
        getCurrentDeviceMode: function () {
            if (typeof elementorFrontend !== 'undefined' && elementorFrontend.getCurrentDeviceMode) {
                return elementorFrontend.getCurrentDeviceMode();
            }
            
            // Fallback device detection
            var width = $(window).width();
            if (width <= 767) return 'mobile';
            if (width <= 1024) return 'tablet';
            return 'desktop';
        },

        /**
         * Get responsive columns based on device
         */
        getResponsiveColumns: function (config, deviceMode) {
            var columns = config.columns || 3;
            
            // Handle responsive columns if configured
            if (config.columns_tablet && deviceMode === 'tablet') {
                columns = config.columns_tablet;
            } else if (config.columns_mobile && deviceMode === 'mobile') {
                columns = config.columns_mobile;
            }
            
            return columns;
        },

        /**
         * Handle Elementor Pro features
         */
        handleElementorPro: function () {
            // Theme Builder compatibility
            if (typeof elementorProFrontend.modules.themeBuilder !== 'undefined') {
                elementorProFrontend.hooks.addAction('frontend/element_ready/cardcrafter-data-grids.default', this.handleThemeBuilder.bind(this));
            }

            // Popup compatibility
            if (typeof elementorProFrontend.modules.popup !== 'undefined') {
                $(document).on('elementor/popup/show', this.handlePopupShow.bind(this));
            }
        },

        /**
         * Handle Theme Builder
         */
        handleThemeBuilder: function ($scope) {
            // Ensure CardCrafter works in theme builder contexts
            setTimeout(function () {
                $scope.find('.cardcrafter-elementor-widget').each(function () {
                    var $widget = $(this);
                    if (!$widget.data('cardcrafter-instance')) {
                        // Re-initialize if needed
                        var config = $widget.data('config');
                        if (config) {
                            CardCrafterElementorHandler.initCardCrafter($widget.attr('id'), config);
                        }
                    }
                });
            }, 100);
        },

        /**
         * Handle popup show events
         */
        handlePopupShow: function (event, id, instance) {
            var $popup = $('#elementor-popup-modal-' + id);
            var $widgets = $popup.find('.cardcrafter-elementor-widget');
            
            $widgets.each(function () {
                var $widget = $(this);
                var config = $widget.data('config');
                
                if (config && !$widget.data('cardcrafter-instance')) {
                    CardCrafterElementorHandler.initCardCrafter($widget.attr('id'), config);
                }
            });
        },

        /**
         * Handle widget loaded event
         */
        onWidgetLoaded: function (event, $widget, config) {
            // Custom event handling for third-party integrations
            console.log('CardCrafter widget loaded:', $widget.attr('id'));
            
            // Analytics tracking
            this.trackWidgetUsage(config);
        },

        /**
         * Handle window resize
         */
        handleResize: function () {
            $('.cardcrafter-elementor-widget').each(function () {
                var $widget = $(this);
                var config = $widget.data('config');
                
                if (config) {
                    CardCrafterElementorHandler.handleResponsiveColumns($widget, config);
                }
            });
        },

        /**
         * Show error message
         */
        showError: function (widgetId, message) {
            var $container = $('#' + widgetId);
            var errorHtml = '<div class="cardcrafter-error-state">' +
                '<div style="padding: 40px; text-align: center; border: 1px solid #fee2e2; background: #fef2f2; border-radius: 8px;">' +
                '<div style="font-size: 24px; margin-bottom: 10px;">⚠️</div>' +
                '<h3 style="margin: 0 0 10px 0; color: #991b1b;">CardCrafter Widget Error</h3>' +
                '<p style="margin: 0; color: #b91c1c; font-size: 14px;">' + message + '</p>' +
                '</div>' +
                '</div>';
            
            $container.html(errorHtml);
        },

        /**
         * Track widget usage for analytics
         */
        trackWidgetUsage: function (config) {
            // Track usage if analytics available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'cardcrafter_elementor_widget', {
                    widget_layout: config.layout || 'grid',
                    widget_data_mode: config.wpDataMode ? 'wordpress' : 'external',
                    widget_items_count: (config.data && config.data.length) || 0
                });
            }
        },

        /**
         * Debounce helper
         */
        debounce: function (func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function () {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    /**
     * Initialize when document ready
     */
    $(document).ready(function () {
        CardCrafterElementorHandler.init();
    });

    /**
     * Expose handler globally for extensions
     */
    window.CardCrafterElementorHandler = CardCrafterElementorHandler;

})(jQuery);