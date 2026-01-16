/**
 * CardCrafter License Manager Frontend JavaScript
 * 
 * Handles license activation, upgrade prompts, and freemium UX interactions.
 * Optimized for conversion and user experience.
 * 
 * @version 1.9.0
 */

(function ($) {
    'use strict';

    /**
     * CardCrafter License Manager
     */
    var CardCrafterLicense = {

        /**
         * Initialize license manager
         */
        init: function () {
            this.bindEvents();
            this.initUpgradePrompts();
            this.trackUsageAnalytics();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            // License activation form
            $('.cardcrafter-license-form').on('submit', this.handleLicenseActivation.bind(this));
            
            // Upgrade button clicks
            $(document).on('click', '.cardcrafter-upgrade-btn', this.handleUpgradeClick.bind(this));
            
            // Feature limit warnings
            $(document).on('click', '.cardcrafter-limit-warning', this.showUpgradeModal.bind(this));
            
            // Dismiss upgrade prompts
            $(document).on('click', '.cardcrafter-dismiss-prompt', this.dismissUpgradePrompt.bind(this));
        },

        /**
         * Handle license activation
         */
        handleLicenseActivation: function (e) {
            e.preventDefault();
            
            var $form = $(e.target);
            var licenseKey = $form.find('input[name="license_key"]').val().trim();
            var $submitBtn = $form.find('input[type="submit"]');
            
            if (!licenseKey) {
                this.showMessage('error', 'Please enter a valid license key.');
                return;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true).val('Activating...');
            
            $.ajax({
                url: cardcrafterLicense.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cardcrafter_check_license',
                    license_key: licenseKey,
                    nonce: cardcrafterLicense.nonce
                },
                success: function (response) {
                    if (response.success) {
                        this.showMessage('success', response.data.message);
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        this.showMessage('error', response.data || 'License activation failed.');
                    }
                }.bind(this),
                error: function () {
                    this.showMessage('error', 'Connection error. Please try again.');
                }.bind(this),
                complete: function () {
                    $submitBtn.prop('disabled', false).val('Activate License');
                }
            });
        },

        /**
         * Handle upgrade button clicks
         */
        handleUpgradeClick: function (e) {
            var $btn = $(e.target);
            var feature = $btn.data('feature') || 'general';
            
            // Track upgrade intent
            this.trackEvent('upgrade_click', {
                feature: feature,
                plan: cardcrafterLicense.currentPlan.slug,
                context: $btn.data('context') || 'button'
            });
            
            // Add conversion tracking parameters
            var upgradeUrl = cardcrafterLicense.upgradeUrl + 
                '?utm_source=plugin&utm_medium=upgrade_button&utm_campaign=' + feature;
            
            window.open(upgradeUrl, '_blank');
        },

        /**
         * Initialize upgrade prompts throughout the plugin
         */
        initUpgradePrompts: function () {
            // Add upgrade prompts to various locations
            this.addCardLimitPrompts();
            this.addExportLimitPrompts();
            this.addTemplatePrompts();
        },

        /**
         * Add card limit upgrade prompts
         */
        addCardLimitPrompts: function () {
            // Check if we're on a page with card grids
            $('.cardcrafter-grid').each(function () {
                var $grid = $(this);
                var cardCount = $grid.find('.cardcrafter-card').length;
                
                // Show prompt if approaching free limit
                if (cardCount >= 10 && cardcrafterLicense.currentPlan.slug === 'free') {
                    var promptHtml = '<div class="cardcrafter-limit-prompt">' +
                        '<span class="dashicons dashicons-star-filled"></span>' +
                        '<strong>Approaching Free Limit:</strong> ' +
                        'Unlock unlimited cards with Pro. ' +
                        '<a href="#" class="cardcrafter-upgrade-btn" data-feature="unlimited_cards">Upgrade Now</a>' +
                        '</div>';
                    
                    $grid.before(promptHtml);
                }
            });
        },

        /**
         * Add export limit upgrade prompts
         */
        addExportLimitPrompts: function () {
            // Add to export dropdown if exists
            $('.cardcrafter-export-dropdown').each(function () {
                var $dropdown = $(this);
                
                if (cardcrafterLicense.currentPlan.slug === 'free') {
                    // Add pro format options with upgrade prompts
                    var proFormats = [
                        { format: 'json', label: 'JSON (Pro)', icon: '{}' },
                        { format: 'pdf', label: 'PDF (Pro)', icon: '📄' },
                        { format: 'excel', label: 'Excel (Business)', icon: '📊' }
                    ];
                    
                    proFormats.forEach(function (item) {
                        var optionHtml = '<option value="upgrade-' + item.format + '" class="cardcrafter-pro-option">' +
                            item.icon + ' ' + item.label + '</option>';
                        $dropdown.append(optionHtml);
                    });
                }
            });
            
            // Handle pro option selection
            $(document).on('change', '.cardcrafter-export-dropdown', function () {
                var value = $(this).val();
                if (value.startsWith('upgrade-')) {
                    var format = value.replace('upgrade-', '');
                    CardCrafterLicense.showUpgradeModal(format);
                    $(this).val(''); // Reset selection
                }
            });
        },

        /**
         * Add template upgrade prompts
         */
        addTemplatePrompts: function () {
            // Add to template selectors
            $('.cardcrafter-template-grid').each(function () {
                var $grid = $(this);
                
                if (cardcrafterLicense.currentPlan.slug === 'free') {
                    // Add premium template previews with upgrade prompts
                    var premiumTemplates = [
                        { id: 'modern-card', name: 'Modern Card', preview: 'modern-preview.png' },
                        { id: 'elegant-list', name: 'Elegant List', preview: 'elegant-preview.png' },
                        { id: 'material-grid', name: 'Material Grid', preview: 'material-preview.png' }
                    ];
                    
                    premiumTemplates.forEach(function (template) {
                        var templateHtml = '<div class="cardcrafter-template-item pro-template">' +
                            '<div class="template-preview">' +
                            '<img src="' + cardcrafterLicense.assetsUrl + 'images/' + template.preview + '" alt="' + template.name + '">' +
                            '<div class="template-overlay">' +
                            '<span class="pro-badge">PRO</span>' +
                            '<button class="cardcrafter-upgrade-btn" data-feature="premium_templates">Upgrade to Use</button>' +
                            '</div>' +
                            '</div>' +
                            '<h4>' + template.name + '</h4>' +
                            '</div>';
                        
                        $grid.append(templateHtml);
                    });
                }
            });
        },

        /**
         * Show upgrade modal
         */
        showUpgradeModal: function (feature) {
            feature = feature || 'general';
            
            var modalContent = this.getUpgradeModalContent(feature);
            var modalHtml = '<div class="cardcrafter-upgrade-modal-overlay">' +
                '<div class="cardcrafter-upgrade-modal">' +
                '<div class="modal-header">' +
                '<h3>' + modalContent.title + '</h3>' +
                '<button class="modal-close">&times;</button>' +
                '</div>' +
                '<div class="modal-body">' +
                modalContent.content +
                '</div>' +
                '<div class="modal-footer">' +
                '<button class="button button-primary cardcrafter-upgrade-btn" data-feature="' + feature + '">' +
                modalContent.cta +
                '</button>' +
                '<button class="button cardcrafter-modal-close">Maybe Later</button>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            $('body').append(modalHtml);
            
            // Bind close events
            $('.cardcrafter-upgrade-modal-overlay').on('click', function (e) {
                if (e.target === this || $(e.target).hasClass('modal-close') || $(e.target).hasClass('cardcrafter-modal-close')) {
                    $(this).remove();
                }
            });
            
            // Track modal view
            this.trackEvent('upgrade_modal_view', { feature: feature });
        },

        /**
         * Get upgrade modal content based on feature
         */
        getUpgradeModalContent: function (feature) {
            var content = {
                'unlimited_cards': {
                    title: 'Unlock Unlimited Cards',
                    content: '<p>You\'re currently limited to 12 cards per page with the free version.</p>' +
                        '<p><strong>CardCrafter Pro includes:</strong></p>' +
                        '<ul>' +
                        '<li>✅ Unlimited cards per page</li>' +
                        '<li>✅ Premium card templates</li>' +
                        '<li>✅ Advanced export options</li>' +
                        '<li>✅ Priority email support</li>' +
                        '</ul>',
                    cta: 'Upgrade to Pro - $49/year'
                },
                'premium_templates': {
                    title: 'Premium Templates Available',
                    content: '<p>Unlock 20+ beautifully designed card templates with CardCrafter Pro.</p>' +
                        '<p><strong>Pro templates include:</strong></p>' +
                        '<ul>' +
                        '<li>🎨 Modern & elegant designs</li>' +
                        '<li>📱 Mobile-optimized layouts</li>' +
                        '<li>🎯 Industry-specific templates</li>' +
                        '<li>⚡ Easy customization options</li>' +
                        '</ul>',
                    cta: 'See All Templates - $49/year'
                },
                'export_formats': {
                    title: 'Advanced Export Options',
                    content: '<p>Export your data in professional formats with CardCrafter Pro.</p>' +
                        '<p><strong>Pro export formats:</strong></p>' +
                        '<ul>' +
                        '<li>📄 PDF reports with branding</li>' +
                        '<li>📊 JSON for developers</li>' +
                        '<li>📈 Excel with advanced formatting</li>' +
                        '<li>🔧 Custom export templates</li>' +
                        '</ul>',
                    cta: 'Upgrade for Export Options'
                }
            };
            
            return content[feature] || content['unlimited_cards'];
        },

        /**
         * Dismiss upgrade prompt
         */
        dismissUpgradePrompt: function (e) {
            e.preventDefault();
            
            var $prompt = $(e.target).closest('.cardcrafter-limit-prompt, .cardcrafter-upgrade-notice');
            $prompt.slideUp();
            
            // Track dismissal
            this.trackEvent('upgrade_prompt_dismiss', {
                feature: $prompt.data('feature') || 'general'
            });
        },

        /**
         * Show admin message
         */
        showMessage: function (type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            // Auto-dismiss success messages
            if (type === 'success') {
                setTimeout(function () {
                    $notice.fadeOut();
                }, 5000);
            }
        },

        /**
         * Track usage analytics for business optimization
         */
        trackUsageAnalytics: function () {
            // Track page views on CardCrafter admin pages
            if (window.location.href.indexOf('cardcrafter') !== -1) {
                this.trackEvent('admin_page_view', {
                    page: this.getCurrentAdminPage(),
                    plan: cardcrafterLicense.currentPlan.slug
                });
            }
            
            // Track feature usage
            this.trackFeatureUsage();
        },

        /**
         * Track specific events for conversion optimization
         */
        trackEvent: function (event, data) {
            data = data || {};
            data.timestamp = Date.now();
            data.user_plan = cardcrafterLicense.currentPlan.slug;
            
            // Send to analytics endpoint
            $.post(cardcrafterLicense.ajaxUrl, {
                action: 'cardcrafter_track_event',
                event: event,
                data: JSON.stringify(data),
                nonce: cardcrafterLicense.nonce
            });
        },

        /**
         * Track feature usage patterns
         */
        trackFeatureUsage: function () {
            // Track widget usage
            $('.cardcrafter-grid').each(function () {
                var $grid = $(this);
                var cardCount = $grid.find('.cardcrafter-card').length;
                
                CardCrafterLicense.trackEvent('widget_display', {
                    card_count: cardCount,
                    layout: $grid.data('layout') || 'grid',
                    source: $grid.data('source') || 'unknown'
                });
            });
            
            // Track export attempts
            $('.cardcrafter-export-btn').on('click', function () {
                var format = $(this).data('format');
                CardCrafterLicense.trackEvent('export_attempt', {
                    format: format,
                    plan: cardcrafterLicense.currentPlan.slug
                });
            });
        },

        /**
         * Get current admin page for analytics
         */
        getCurrentAdminPage: function () {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('page') || 'unknown';
        },

        /**
         * Add feature limit indicators to UI
         */
        addLimitIndicators: function () {
            // Add to card count displays
            $('.cardcrafter-card-count').each(function () {
                var $counter = $(this);
                var currentCount = parseInt($counter.text());
                
                if (cardcrafterLicense.currentPlan.slug === 'free' && currentCount >= 10) {
                    $counter.addClass('approaching-limit');
                    $counter.attr('title', 'Approaching free plan limit of 12 cards');
                }
            });
            
            // Add to Elementor widget count
            this.trackElementorWidgetUsage();
        },

        /**
         * Track Elementor widget usage for limits
         */
        trackElementorWidgetUsage: function () {
            if (typeof elementorFrontend !== 'undefined') {
                var widgetCount = $('.elementor-widget-cardcrafter-data-grids').length;
                
                if (cardcrafterLicense.currentPlan.slug === 'free' && widgetCount >= 3) {
                    this.showWidgetLimitWarning();
                }
            }
        },

        /**
         * Show widget limit warning
         */
        showWidgetLimitWarning: function () {
            var warningHtml = '<div class="cardcrafter-widget-limit-warning">' +
                '<span class="dashicons dashicons-warning"></span>' +
                'You have reached the free plan limit of 3 Elementor widgets per page. ' +
                '<a href="#" class="cardcrafter-upgrade-btn" data-feature="unlimited_widgets">Upgrade to Pro</a> for unlimited widgets.' +
                '</div>';
            
            $('.elementor-widget-cardcrafter-data-grids').last().after(warningHtml);
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function () {
        CardCrafterLicense.init();
    });

    /**
     * Expose globally for extensions
     */
    window.CardCrafterLicense = CardCrafterLicense;

})(jQuery);

/**
 * Add CSS for upgrade prompts and modals
 */
(function () {
    var styles = `
        <style>
        .cardcrafter-limit-prompt {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cardcrafter-limit-prompt .dashicons {
            color: #856404;
        }
        
        .cardcrafter-upgrade-btn {
            background: #0073aa !important;
            color: white !important;
            border: none !important;
            padding: 6px 12px !important;
            border-radius: 4px !important;
            text-decoration: none !important;
            font-weight: 500 !important;
        }
        
        .cardcrafter-upgrade-btn:hover {
            background: #005a87 !important;
            color: white !important;
        }
        
        .cardcrafter-upgrade-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cardcrafter-upgrade-modal {
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .cardcrafter-upgrade-modal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .cardcrafter-upgrade-modal .modal-header h3 {
            margin: 0;
            color: #23282d;
        }
        
        .cardcrafter-upgrade-modal .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .cardcrafter-upgrade-modal .modal-body {
            padding: 20px;
        }
        
        .cardcrafter-upgrade-modal .modal-body ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        
        .cardcrafter-upgrade-modal .modal-body li {
            margin-bottom: 8px;
        }
        
        .cardcrafter-upgrade-modal .modal-footer {
            padding: 20px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        .cardcrafter-upgrade-modal .modal-footer .button {
            margin-left: 10px;
        }
        
        .cardcrafter-template-item.pro-template {
            position: relative;
            opacity: 0.7;
        }
        
        .cardcrafter-template-item .template-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .cardcrafter-template-item.pro-template:hover .template-overlay {
            opacity: 1;
        }
        
        .cardcrafter-template-item .pro-badge {
            background: #0073aa;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .cardcrafter-pro-option {
            color: #0073aa;
            font-weight: 500;
        }
        
        .cardcrafter-widget-limit-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 12px;
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .approaching-limit {
            color: #856404 !important;
            font-weight: 600 !important;
        }
        </style>
    `;
    
    document.head.insertAdjacentHTML('beforeend', styles);
})();