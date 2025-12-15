/**
 * CardCrafter - JSON to Card Layouts
 * A lightweight JavaScript library for rendering JSON data as beautiful card grids.
 * 
 * @version 1.0.0
 * @license GPLv2 or later
 */

(function (global) {
    'use strict';

    /**
     * CardCrafter Constructor
     * @param {Object} options - Configuration options
     */
    function CardCrafter(options) {
        this.options = Object.assign({
            selector: null,
            source: null,
            layout: 'grid',
            columns: 3,
            fields: {
                image: 'image',
                title: 'title',
                subtitle: 'subtitle',
                description: 'description',
                link: 'link'
            }
        }, options);

        if (!this.options.selector || !this.options.source) {
            console.error('CardCrafter: selector and source are required');
            return;
        }

        this.container = document.querySelector(this.options.selector);
        if (!this.container) {
            console.error('CardCrafter: Container not found:', this.options.selector);
            return;
        }

        this.init();
    }

    /**
     * Initialize the card grid
     */
    CardCrafter.prototype.init = function () {
        this.container.innerHTML = '<div class="cc-loading"><div class="cc-spinner"></div><p>Loading cards...</p></div>';
        this.fetchData();
    };

    /**
     * Fetch JSON data from source
     */
    CardCrafter.prototype.fetchData = function () {
        var self = this;

        fetch(this.options.source)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                self.renderCards(data);
            })
            .catch(function (error) {
                console.error('CardCrafter fetch error:', error);
                self.container.innerHTML = '<div class="cc-error"><p>Error loading data: ' + error.message + '</p></div>';
            });
    };

    /**
     * Get nested value from object using dot notation
     */
    CardCrafter.prototype.getNestedValue = function (obj, path) {
        if (!path) return null;
        var keys = path.split('.');
        var value = obj;
        for (var i = 0; i < keys.length; i++) {
            if (value && typeof value === 'object' && keys[i] in value) {
                value = value[keys[i]];
            } else {
                return null;
            }
        }
        return value;
    };

    /**
     * Generate placeholder image URL
     */
    CardCrafter.prototype.getPlaceholderImage = function (text) {
        // Simple SVG placeholder
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">' +
            '<rect fill="#e0e0e0" width="400" height="300"/>' +
            '<text fill="#888" font-family="sans-serif" font-size="24" text-anchor="middle" x="200" y="160">' +
            (text ? text.substring(0, 20) : 'No Image') + '</text></svg>';
        return 'data:image/svg+xml;base64,' + btoa(svg);
    };

    /**
     * Render cards from data
     */
    CardCrafter.prototype.renderCards = function (data) {
        var self = this;
        var items = Array.isArray(data) ? data : (data.items || data.data || data.results || [data]);

        if (!items.length) {
            this.container.innerHTML = '<div class="cc-error"><p>No data found</p></div>';
            return;
        }

        // Create wrapper
        var wrapper = document.createElement('div');
        wrapper.className = 'cc-grid cc-layout-' + this.options.layout + ' cc-cols-' + this.options.columns;

        // Generate cards
        items.forEach(function (item, index) {
            var card = self.createCard(item, index);
            wrapper.appendChild(card);
        });

        this.container.innerHTML = '';
        this.container.appendChild(wrapper);
    };

    /**
     * Create a single card element
     */
    CardCrafter.prototype.createCard = function (item, index) {
        var self = this;
        var fields = this.options.fields;

        // Extract field values
        var image = this.getNestedValue(item, fields.image);
        var title = this.getNestedValue(item, fields.title) || 'Untitled';
        var subtitle = this.getNestedValue(item, fields.subtitle);
        var description = this.getNestedValue(item, fields.description);
        var link = this.getNestedValue(item, fields.link);

        // Create card element
        var card = document.createElement('div');
        card.className = 'cc-card';
        card.setAttribute('data-index', index);

        // Card inner wrapper (for animations)
        var cardInner = document.createElement('div');
        cardInner.className = 'cc-card-inner';

        // Image section
        var imageSection = document.createElement('div');
        imageSection.className = 'cc-card-image';
        var img = document.createElement('img');
        img.src = image || this.getPlaceholderImage(title);
        img.alt = title;
        img.loading = 'lazy';
        img.onerror = function () {
            this.src = self.getPlaceholderImage(title);
        };
        imageSection.appendChild(img);
        cardInner.appendChild(imageSection);

        // Content section
        var content = document.createElement('div');
        content.className = 'cc-card-content';

        // Title
        var titleEl = document.createElement('h3');
        titleEl.className = 'cc-card-title';
        titleEl.textContent = title;
        content.appendChild(titleEl);

        // Subtitle
        if (subtitle) {
            var subtitleEl = document.createElement('p');
            subtitleEl.className = 'cc-card-subtitle';
            subtitleEl.textContent = subtitle;
            content.appendChild(subtitleEl);
        }

        // Description
        if (description) {
            var descEl = document.createElement('p');
            descEl.className = 'cc-card-description';
            // Truncate if too long
            descEl.textContent = description.length > 150 ? description.substring(0, 147) + '...' : description;
            content.appendChild(descEl);
        }

        // Link/Button
        if (link) {
            var linkEl = document.createElement('a');
            linkEl.className = 'cc-card-link';
            linkEl.href = link;
            linkEl.textContent = 'Learn More';
            linkEl.target = '_blank';
            linkEl.rel = 'noopener noreferrer';
            content.appendChild(linkEl);
        }

        cardInner.appendChild(content);
        card.appendChild(cardInner);

        return card;
    };

    // Expose to global scope
    global.CardCrafter = CardCrafter;

})(typeof window !== 'undefined' ? window : this);
