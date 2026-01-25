/**
 * CardCrafter - JSON to Card Layouts
 * A lightweight JavaScript library for rendering JSON data as beautiful card grids.
 *
 * @version 1.13.0
 * @license GPLv2 or later
 *
 * WCAG 2.1 AA Accessibility Features:
 * - Full keyboard navigation (Arrow keys, Home, End, Tab)
 * - ARIA landmarks, roles, and live regions
 * - Screen reader announcements for dynamic content
 * - Focus management and visible focus indicators
 * - Skip links for card grid navigation
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
            search: true,
            filters: true,
            pagination: true,
            showDescription: true,
            showButtons: true,
            showImage: true,
            enableExport: true,
            cardStyle: 'default',
            itemsPerPage: 6,
            // Accessibility options
            enableAccessibility: true,
            ariaLabel: 'Card Grid',
            announceChanges: true,
            fields: {
                image: 'image',
                title: 'title',
                subtitle: 'subtitle',
                description: 'description',
                link: 'link'
            }
        }, options);

        if (!this.options.selector && !this.options.source) {
            console.error('CardCrafter: selector and source are required');
            return;
        }

        this.container = document.querySelector(this.options.selector);
        if (!this.container) {
            console.error('CardCrafter: Container not found:', this.options.selector);
            return;
        }

        this.items = []; // Stores the original fetched data
        this.filteredItems = []; // Stores items after search/filter operations
        this.gridWrapper = null; // Will hold the DOM element for the card grid
        this.searchTimeout = null; // Debounce timeout for search
        this.searchCache = {}; // Cache search results for better performance

        // Pagination properties
        this.currentPage = 1;
        this.itemsPerPage = this.options.itemsPerPage || 12; // Default 12 items per page
        this.paginationWrapper = null; // Will hold pagination controls

        // Accessibility properties
        this.liveRegion = null; // ARIA live region for announcements
        this.focusedCardIndex = -1; // Currently focused card index
        this.uniqueId = 'cc-' + Math.random().toString(36).substr(2, 9); // Unique ID for ARIA relationships

        this.init();
    }

    /**
     * DOM Elements creation helper
     */
    CardCrafter.prototype.createEl = function (tag, className, attributes) {
        var el = document.createElement(tag);
        if (className) el.className = className;
        if (attributes) {
            for (var key in attributes) {
                if (attributes.hasOwnProperty(key)) {
                    el.setAttribute(key, attributes[key]);
                }
            }
        }
        return el;
    };

    /**
     * Initialize the card grid
     */
    CardCrafter.prototype.init = function () {
        this.container.innerHTML = '<div class="cardcrafter-loading"><div class="cardcrafter-spinner"></div><p>Loading cards...</p></div>';
        
        // WordPress data mode - data passed directly
        if (this.options.wpDataMode && this.options.data) {
            this.processData(this.options.data);
        } else {
            this.fetchData();
        }
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
                
                // Check if response is actually JSON by looking at content-type
                var contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // If not JSON, get text to see what we actually received
                    return response.text().then(function(text) {
                        if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                            throw new Error('Received HTML page instead of JSON data. This usually indicates a WordPress error. Check WordPress error logs.');
                        } else if (text.trim().startsWith('0')) {
                            throw new Error('WordPress returned "0" - check if AJAX action is registered properly.');
                        } else {
                            throw new Error('Invalid JSON response. Received: ' + text.substring(0, 100) + '...');
                        }
                    });
                }
                
                return response.json();
            })
            .then(function (response) {
                var data;
                // Handle WP AJAX response format
                if (response.success !== undefined) {
                    if (response.success) {
                        data = response.data;
                    } else {
                        throw new Error(response.data || 'Unknown error fetching data');
                    }
                } else {
                    // Standard direct JSON fetch
                    data = response;
                }

                // Store raw items
                self.items = Array.isArray(data) ? data : (data.items || data.data || data.results || [data]);
                self.filteredItems = self.items.slice(); // Copy for filtering

                self.renderLayout();
            })
            .catch(function (error) {
                console.error('CardCrafter fetch error:', error);
                self.renderError(error);
            });
    };

    /**
     * Render the main layout structure (Toolbar + Grid + Pagination)
     * Implements WCAG 2.1 AA accessibility requirements
     */
    CardCrafter.prototype.renderLayout = function () {
        this.container.innerHTML = '';

        // Add accessibility attributes to main container
        this.container.setAttribute('role', 'region');
        this.container.setAttribute('aria-label', this.options.ariaLabel || 'Card Grid');

        // Create skip link for keyboard users
        if (this.options.enableAccessibility !== false) {
            this.renderSkipLink();
        }

        // Render toolbar
        this.renderToolbar();

        // Create grid wrapper with accessibility attributes
        this.gridWrapper = this.createEl('div', 'cardcrafter-grid-wrapper');
        this.gridWrapper.setAttribute('id', this.uniqueId + '-grid');
        this.container.appendChild(this.gridWrapper);

        // Create pagination wrapper
        this.paginationWrapper = this.createEl('div', 'cardcrafter-pagination-wrapper');
        this.container.appendChild(this.paginationWrapper);

        // Create ARIA live region for announcements
        if (this.options.enableAccessibility !== false) {
            this.renderLiveRegion();
        }

        // Initial sort and render
        this.sortItems('default');
        this.renderPaginatedCards();

        // Set up keyboard navigation
        if (this.options.enableAccessibility !== false) {
            this.setupKeyboardNavigation();
        }
    };

    /**
     * Render skip link for keyboard navigation (WCAG 2.4.1)
     */
    CardCrafter.prototype.renderSkipLink = function () {
        var skipLink = this.createEl('a', 'cardcrafter-skip-link cardcrafter-sr-only cardcrafter-sr-focusable');
        skipLink.href = '#' + this.uniqueId + '-grid';
        skipLink.textContent = 'Skip to card grid';
        skipLink.addEventListener('click', function (e) {
            e.preventDefault();
            var grid = document.getElementById(this.getAttribute('href').slice(1));
            if (grid) {
                grid.focus();
                grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        this.container.insertBefore(skipLink, this.container.firstChild);
    };

    /**
     * Render ARIA live region for dynamic announcements (WCAG 4.1.3)
     */
    CardCrafter.prototype.renderLiveRegion = function () {
        this.liveRegion = this.createEl('div', 'cardcrafter-sr-only');
        this.liveRegion.setAttribute('role', 'status');
        this.liveRegion.setAttribute('aria-live', 'polite');
        this.liveRegion.setAttribute('aria-atomic', 'true');
        this.liveRegion.setAttribute('id', this.uniqueId + '-announcements');
        this.container.appendChild(this.liveRegion);
    };

    /**
     * Announce message to screen readers via ARIA live region
     * @param {string} message - The message to announce
     */
    CardCrafter.prototype.announce = function (message) {
        if (!this.liveRegion || this.options.announceChanges === false) return;

        // Clear and set with small delay to ensure announcement
        this.liveRegion.textContent = '';
        var self = this;
        setTimeout(function () {
            self.liveRegion.textContent = message;
        }, 100);
    };

    /**
     * Set up keyboard navigation for card grid (WCAG 2.1.1)
     */
    CardCrafter.prototype.setupKeyboardNavigation = function () {
        var self = this;

        this.gridWrapper.setAttribute('tabindex', '0');
        this.gridWrapper.setAttribute('role', 'list');
        this.gridWrapper.setAttribute('aria-label', 'Card list - Use arrow keys to navigate');

        this.gridWrapper.addEventListener('keydown', function (e) {
            self.handleKeyboardNavigation(e);
        });
    };

    /**
     * Handle keyboard navigation within card grid
     * @param {KeyboardEvent} e - Keyboard event
     */
    CardCrafter.prototype.handleKeyboardNavigation = function (e) {
        var cards = this.gridWrapper.querySelectorAll('.cardcrafter-card');
        if (!cards.length) return;

        var columns = parseInt(this.options.columns) || 3;
        var handled = false;

        switch (e.key) {
            case 'ArrowRight':
                this.focusedCardIndex = Math.min(this.focusedCardIndex + 1, cards.length - 1);
                handled = true;
                break;
            case 'ArrowLeft':
                this.focusedCardIndex = Math.max(this.focusedCardIndex - 1, 0);
                handled = true;
                break;
            case 'ArrowDown':
                this.focusedCardIndex = Math.min(this.focusedCardIndex + columns, cards.length - 1);
                handled = true;
                break;
            case 'ArrowUp':
                this.focusedCardIndex = Math.max(this.focusedCardIndex - columns, 0);
                handled = true;
                break;
            case 'Home':
                this.focusedCardIndex = 0;
                handled = true;
                break;
            case 'End':
                this.focusedCardIndex = cards.length - 1;
                handled = true;
                break;
            case 'Enter':
            case ' ':
                if (this.focusedCardIndex >= 0 && cards[this.focusedCardIndex]) {
                    var link = cards[this.focusedCardIndex].querySelector('.cardcrafter-card-link');
                    if (link) {
                        link.click();
                        handled = true;
                    }
                }
                break;
        }

        if (handled) {
            e.preventDefault();
            this.focusCard(this.focusedCardIndex);
        }
    };

    /**
     * Focus a specific card by index
     * @param {number} index - Card index to focus
     */
    CardCrafter.prototype.focusCard = function (index) {
        var cards = this.gridWrapper.querySelectorAll('.cardcrafter-card');
        if (!cards.length || index < 0 || index >= cards.length) return;

        // Remove focus from all cards
        cards.forEach(function (card) {
            card.classList.remove('cardcrafter-card-focused');
            card.setAttribute('tabindex', '-1');
        });

        // Focus the target card
        var targetCard = cards[index];
        targetCard.classList.add('cardcrafter-card-focused');
        targetCard.setAttribute('tabindex', '0');
        targetCard.focus();

        // Announce card info to screen reader
        var title = targetCard.querySelector('.cardcrafter-card-title');
        if (title) {
            this.announce('Card ' + (index + 1) + ' of ' + cards.length + ': ' + title.textContent);
        }
    };

    /**
     * Render the Search and Sort Toolbar (WCAG compliant)
     */
    CardCrafter.prototype.renderToolbar = function () {
        var self = this;
        var toolbar = this.createEl('div', 'cardcrafter-toolbar');

        // Add accessibility attributes to toolbar
        toolbar.setAttribute('role', 'toolbar');
        toolbar.setAttribute('aria-label', 'Card grid controls');

        // Search Input - only if search is enabled
        if (this.options.search !== false) {
            var searchWrapper = this.createEl('div', 'cardcrafter-search-wrapper');

            // Create label for accessibility (visually hidden)
            var searchLabel = this.createEl('label', 'cardcrafter-sr-only');
            searchLabel.setAttribute('for', this.uniqueId + '-search');
            searchLabel.textContent = 'Search cards';
            searchWrapper.appendChild(searchLabel);

            var searchInput = this.createEl('input', 'cardcrafter-search-input', {
                type: 'search',
                placeholder: 'Search items...',
                id: this.uniqueId + '-search',
                'aria-describedby': this.uniqueId + '-search-hint'
            });
            searchInput.setAttribute('role', 'searchbox');
            searchInput.setAttribute('aria-label', 'Search cards');

            // Add search hint for screen readers
            var searchHint = this.createEl('span', 'cardcrafter-sr-only');
            searchHint.setAttribute('id', this.uniqueId + '-search-hint');
            searchHint.textContent = 'Type to filter cards. Results update automatically.';
            searchWrapper.appendChild(searchHint);

            searchInput.addEventListener('input', function (e) {
                self.debouncedSearch(e.target.value);
            });

            searchWrapper.appendChild(searchInput);
            toolbar.appendChild(searchWrapper);
        }

        // Sort Dropdown - only if filters are enabled
        if (this.options.filters !== false) {
            var sortWrapper = this.createEl('div', 'cardcrafter-sort-wrapper');

            // Create label for accessibility
            var sortLabel = this.createEl('label', 'cardcrafter-sr-only');
            sortLabel.setAttribute('for', this.uniqueId + '-sort');
            sortLabel.textContent = 'Sort cards by';
            sortWrapper.appendChild(sortLabel);

            var sortSelect = this.createEl('select', 'cardcrafter-sort-select', {
                id: this.uniqueId + '-sort'
            });
            sortSelect.setAttribute('aria-label', 'Sort cards by');

            var options = [
                { value: 'default', text: 'Default Order' },
                { value: 'az', text: 'Name (A-Z)' },
                { value: 'za', text: 'Name (Z-A)' }
            ];

            options.forEach(function (opt) {
                var option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                sortSelect.appendChild(option);
            });

            sortSelect.addEventListener('change', function (e) {
                self.sortItems(e.target.value);
                self.renderPaginatedCards();

                // Announce sort change to screen readers
                var selectedText = e.target.options[e.target.selectedIndex].text;
                self.announce('Cards sorted: ' + selectedText);
            });

            sortWrapper.appendChild(sortSelect);
            toolbar.appendChild(sortWrapper);
        }

        // Items Per Page Dropdown - only if pagination is enabled
        if (this.options.pagination !== false) {
            var perPageWrapper = this.createEl('div', 'cardcrafter-per-page-wrapper');
            var perPageLabel = this.createEl('label', 'cardcrafter-per-page-label');
            perPageLabel.textContent = 'Items: ';
            perPageLabel.setAttribute('for', this.uniqueId + '-per-page');

            var perPageSelect = this.createEl('select', 'cardcrafter-per-page-select', {
                id: this.uniqueId + '-per-page'
            });
            perPageSelect.setAttribute('aria-label', 'Items per page');

            var perPageOptions = [
                { value: '6', text: '6 per page' },
                { value: '12', text: '12 per page' },
                { value: '24', text: '24 per page' },
                { value: '50', text: '50 per page' },
                { value: '100', text: '100 per page' }
            ];

            perPageOptions.forEach(function (opt) {
                var option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                if (opt.value == self.itemsPerPage) {
                    option.selected = true;
                }
                perPageSelect.appendChild(option);
            });

            perPageSelect.addEventListener('change', function (e) {
                self.itemsPerPage = parseInt(e.target.value);
                self.currentPage = 1; // Reset to first page
                self.renderPaginatedCards();

                // Announce change to screen readers
                self.announce('Showing ' + e.target.value + ' items per page');
            });

            perPageWrapper.appendChild(perPageLabel);
            perPageWrapper.appendChild(perPageSelect);
            toolbar.appendChild(perPageWrapper);
        }

        // Export Dropdown - only if export is enabled (with full accessibility)
        if (this.options.enableExport !== false) {
            var exportWrapper = this.createEl('div', 'cardcrafter-export-wrapper');
            var exportButton = this.createEl('button', 'cardcrafter-export-button');
            exportButton.textContent = 'Export Data';
            exportButton.type = 'button';
            exportButton.setAttribute('aria-expanded', 'false');
            exportButton.setAttribute('aria-haspopup', 'true');
            exportButton.setAttribute('aria-controls', this.uniqueId + '-export-menu');

            var exportDropdown = this.createEl('div', 'cardcrafter-export-dropdown');
            exportDropdown.style.display = 'none';
            exportDropdown.setAttribute('id', this.uniqueId + '-export-menu');
            exportDropdown.setAttribute('role', 'menu');
            exportDropdown.setAttribute('aria-label', 'Export options');

            var exportOptions = [
                { value: 'csv', text: 'Export as CSV', icon: '📊' },
                { value: 'json', text: 'Export as JSON', icon: '📄' },
                { value: 'pdf', text: 'Export as PDF', icon: '📋' }
            ];

            exportOptions.forEach(function (opt, index) {
                var exportItem = self.createEl('button', 'cardcrafter-export-item');
                exportItem.innerHTML = opt.icon + ' ' + opt.text;
                exportItem.setAttribute('role', 'menuitem');
                exportItem.setAttribute('tabindex', index === 0 ? '0' : '-1');

                exportItem.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.exportData(opt.value);
                    self.closeExportDropdown(exportButton, exportDropdown);
                    exportButton.focus();
                });

                exportItem.addEventListener('keydown', function (e) {
                    self.handleExportMenuKeyboard(e, exportDropdown, exportButton);
                });

                exportDropdown.appendChild(exportItem);
            });

            exportButton.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var isVisible = exportDropdown.style.display === 'block';
                if (isVisible) {
                    self.closeExportDropdown(exportButton, exportDropdown);
                } else {
                    self.openExportDropdown(exportButton, exportDropdown);
                }
            });

            exportButton.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    self.openExportDropdown(exportButton, exportDropdown);
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (!exportWrapper.contains(e.target)) {
                    self.closeExportDropdown(exportButton, exportDropdown);
                }
            });

            // Close on Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && exportDropdown.style.display === 'block') {
                    self.closeExportDropdown(exportButton, exportDropdown);
                    exportButton.focus();
                }
            });

            exportWrapper.appendChild(exportButton);
            exportWrapper.appendChild(exportDropdown);
            toolbar.appendChild(exportWrapper);
        }

        // Only append toolbar if it has child elements
        if (toolbar.children.length > 0) {
            this.container.appendChild(toolbar);
        }
    };

    /**
     * Open export dropdown with accessibility support
     */
    CardCrafter.prototype.openExportDropdown = function (button, dropdown) {
        dropdown.style.display = 'block';
        button.setAttribute('aria-expanded', 'true');

        // Focus first menu item
        var firstItem = dropdown.querySelector('[role="menuitem"]');
        if (firstItem) {
            firstItem.focus();
        }
    };

    /**
     * Close export dropdown with accessibility support
     */
    CardCrafter.prototype.closeExportDropdown = function (button, dropdown) {
        dropdown.style.display = 'none';
        button.setAttribute('aria-expanded', 'false');
    };

    /**
     * Handle keyboard navigation within export menu (WCAG 2.1.1)
     */
    CardCrafter.prototype.handleExportMenuKeyboard = function (e, dropdown, button) {
        var items = dropdown.querySelectorAll('[role="menuitem"]');
        var currentIndex = Array.from(items).indexOf(document.activeElement);

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                var nextIndex = (currentIndex + 1) % items.length;
                items[nextIndex].focus();
                break;
            case 'ArrowUp':
                e.preventDefault();
                var prevIndex = (currentIndex - 1 + items.length) % items.length;
                items[prevIndex].focus();
                break;
            case 'Home':
                e.preventDefault();
                items[0].focus();
                break;
            case 'End':
                e.preventDefault();
                items[items.length - 1].focus();
                break;
            case 'Escape':
                e.preventDefault();
                this.closeExportDropdown(button, dropdown);
                button.focus();
                break;
            case 'Tab':
                this.closeExportDropdown(button, dropdown);
                break;
        }
    };

    /**
     * Debounced Search with Performance Optimization
     */
    CardCrafter.prototype.debouncedSearch = function (query) {
        var self = this;
        
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Set new timeout for debouncing
        this.searchTimeout = setTimeout(function() {
            self.handleSearch(query);
        }, 300); // 300ms debounce delay
    };

    /**
     * Handle Search Input
     */
    CardCrafter.prototype.handleSearch = function (query) {
        var self = this;
        var q = query.toLowerCase().trim();

        // Check cache first for performance
        if (this.searchCache[q]) {
            this.filteredItems = this.searchCache[q].slice();
        } else {
            if (!q) {
                this.filteredItems = this.items.slice();
            } else {
                // Performance optimization: pre-compute searchable text for each item
                this.filteredItems = this.items.filter(function (item) {
                    // Cache searchable text for this item if not already cached
                    if (!item._searchableText) {
                        var title = (self.getNestedValue(item, self.options.fields.title) || '').toLowerCase();
                        var desc = (self.getNestedValue(item, self.options.fields.description) || '').toLowerCase();
                        var sub = (self.getNestedValue(item, self.options.fields.subtitle) || '').toLowerCase();
                        item._searchableText = title + ' ' + desc + ' ' + sub;
                    }

                    return item._searchableText.includes(q);
                });
            }

            // Cache the results (limit cache size to prevent memory bloat)
            if (Object.keys(this.searchCache).length > 50) {
                this.searchCache = {}; // Reset cache when it gets too large
            }
            this.searchCache[q] = this.filteredItems.slice();
        }

        // Re-apply current sort
        var sortSelect = this.container.querySelector('.cardcrafter-sort-select');
        if (sortSelect) {
            this.sortItems(sortSelect.value);
        }

        // Reset to first page when search changes
        this.currentPage = 1;
        this.focusedCardIndex = -1; // Reset focus
        this.renderPaginatedCards();

        // Announce search results to screen readers
        if (q) {
            var count = this.filteredItems.length;
            var message = count === 0
                ? 'No cards found matching "' + query + '"'
                : count + ' card' + (count !== 1 ? 's' : '') + ' found matching "' + query + '"';
            this.announce(message);
        } else if (this.items.length > 0) {
            this.announce('Showing all ' + this.items.length + ' cards');
        }
    };

    /**
     * Sort Items
     */
    CardCrafter.prototype.sortItems = function (sortType) {
        var self = this;
        
        if (sortType === 'default') {
            // If default, we can't easily restore original order unless we stored indices or cloned original.
            // But since handleSearch resets filteredItems from .items (which is original order), 
            // we just need to not sort if default, assuming .items is preserved in order.
            // If the user searches then goes back to default, handleSearch resets from this.items.
            // So we just don't sort here.
            return; 
        }

        this.filteredItems.sort(function (a, b) {
            var titleA = (self.getNestedValue(a, self.options.fields.title) || '').toLowerCase();
            var titleB = (self.getNestedValue(b, self.options.fields.title) || '').toLowerCase();

            if (sortType === 'az') {
                return titleA.localeCompare(titleB);
            } else if (sortType === 'za') {
                return titleB.localeCompare(titleA);
            }
            return 0;
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
     * Render cards from data (with accessibility enhancements)
     */
    CardCrafter.prototype.renderCards = function (items) {
        var self = this;

        if (!items.length) {
            var noResults = this.createEl('div', 'cardcrafter-no-results');
            noResults.setAttribute('role', 'status');
            noResults.innerHTML = '<p>No items found matching your search.</p>';
            this.gridWrapper.innerHTML = '';
            this.gridWrapper.appendChild(noResults);
            return;
        }

        // Create grid container with accessibility
        var grid = document.createElement('div');
        grid.className = 'cardcrafter-grid cardcrafter-layout-' + this.options.layout + ' cardcrafter-cols-' + this.options.columns;
        grid.setAttribute('role', 'list');
        grid.setAttribute('aria-label', 'Card list with ' + items.length + ' items');

        // Performance optimization: Use DocumentFragment for batch DOM operations
        var fragment = document.createDocumentFragment();

        // Generate cards with proper indexing for accessibility
        var startIndex = (this.currentPage - 1) * this.itemsPerPage;
        items.forEach(function (item, index) {
            var card = self.createCard(item, startIndex + index);
            fragment.appendChild(card);
        });

        grid.appendChild(fragment);
        this.gridWrapper.innerHTML = '';
        this.gridWrapper.appendChild(grid);

        // Reset focus state for new content
        this.focusedCardIndex = -1;
    };

    /**
     * Render paginated cards with pagination controls
     */
    CardCrafter.prototype.renderPaginatedCards = function () {
        var totalItems = this.filteredItems.length;
        var currentPageItems;
        
        // If pagination is disabled, show all items
        if (this.options.pagination === false) {
            currentPageItems = this.filteredItems;
            this.renderCards(currentPageItems);
            this.renderPaginationControls(0, totalItems); // 0 pages = no pagination controls
            return;
        }
        
        var totalPages = Math.ceil(totalItems / this.itemsPerPage);
        
        // Calculate current page items
        var startIndex = (this.currentPage - 1) * this.itemsPerPage;
        var endIndex = startIndex + this.itemsPerPage;
        currentPageItems = this.filteredItems.slice(startIndex, endIndex);
        
        // Render the current page of cards
        this.renderCards(currentPageItems);
        
        // Render pagination controls
        this.renderPaginationControls(totalPages, totalItems);
    };

    /**
     * Render pagination controls (WCAG compliant)
     */
    CardCrafter.prototype.renderPaginationControls = function (totalPages, totalItems) {
        var self = this;

        if (!this.paginationWrapper) return;

        this.paginationWrapper.innerHTML = '';

        // Don't show pagination if disabled or only one page or no items
        if (this.options.pagination === false || totalPages <= 1) return;

        // Create navigation landmark for pagination
        var pagination = this.createEl('nav', 'cardcrafter-pagination');
        pagination.setAttribute('role', 'navigation');
        pagination.setAttribute('aria-label', 'Card pagination');

        // Results info with live region
        var startItem = ((this.currentPage - 1) * this.itemsPerPage) + 1;
        var endItem = Math.min(this.currentPage * this.itemsPerPage, totalItems);
        var resultsInfo = this.createEl('div', 'cardcrafter-pagination-info');
        resultsInfo.setAttribute('aria-live', 'polite');
        resultsInfo.setAttribute('aria-atomic', 'true');
        resultsInfo.textContent = 'Showing ' + startItem + '-' + endItem + ' of ' + totalItems + ' items';
        pagination.appendChild(resultsInfo);

        // Pagination controls container
        var controls = this.createEl('div', 'cardcrafter-pagination-controls');
        controls.setAttribute('role', 'group');
        controls.setAttribute('aria-label', 'Page navigation');

        // Previous button with accessibility
        var prevBtn = this.createEl('button', 'cardcrafter-pagination-btn cardcrafter-pagination-prev');
        prevBtn.textContent = 'Previous';
        prevBtn.setAttribute('aria-label', 'Go to previous page');
        prevBtn.disabled = this.currentPage === 1;
        if (this.currentPage === 1) {
            prevBtn.setAttribute('aria-disabled', 'true');
        }
        prevBtn.addEventListener('click', function () {
            if (self.currentPage > 1) {
                self.currentPage--;
                self.renderPaginatedCards();
                self.announce('Page ' + self.currentPage + ' of ' + totalPages);
            }
        });
        controls.appendChild(prevBtn);

        // Page numbers (show max 5 page numbers)
        var startPage = Math.max(1, this.currentPage - 2);
        var endPage = Math.min(totalPages, startPage + 4);

        // Adjust start if we're near the end
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (var i = startPage; i <= endPage; i++) {
            var pageBtn = this.createEl('button', 'cardcrafter-pagination-btn cardcrafter-pagination-number');
            pageBtn.textContent = i;
            pageBtn.setAttribute('aria-label', 'Page ' + i + ' of ' + totalPages);

            if (i === this.currentPage) {
                pageBtn.classList.add('cardcrafter-pagination-current');
                pageBtn.setAttribute('aria-current', 'page');
            }
            pageBtn.addEventListener('click', this.createPageClickHandler(i, totalPages));
            controls.appendChild(pageBtn);
        }

        // Next button with accessibility
        var nextBtn = this.createEl('button', 'cardcrafter-pagination-btn cardcrafter-pagination-next');
        nextBtn.textContent = 'Next';
        nextBtn.setAttribute('aria-label', 'Go to next page');
        nextBtn.disabled = this.currentPage === totalPages;
        if (this.currentPage === totalPages) {
            nextBtn.setAttribute('aria-disabled', 'true');
        }
        nextBtn.addEventListener('click', function () {
            if (self.currentPage < totalPages) {
                self.currentPage++;
                self.renderPaginatedCards();
                self.announce('Page ' + self.currentPage + ' of ' + totalPages);
            }
        });
        controls.appendChild(nextBtn);

        pagination.appendChild(controls);
        this.paginationWrapper.appendChild(pagination);
    };

    /**
     * Create page click handler (closure to capture page number)
     */
    CardCrafter.prototype.createPageClickHandler = function (pageNumber, totalPages) {
        var self = this;
        return function () {
            self.currentPage = pageNumber;
            self.focusedCardIndex = -1; // Reset focus
            self.renderPaginatedCards();

            // Announce page change to screen readers
            if (totalPages) {
                self.announce('Page ' + pageNumber + ' of ' + totalPages);
            }
        };
    };

    /**
     * Create a single card element (WCAG compliant)
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

        // Generate unique IDs for ARIA relationships
        var cardId = this.uniqueId + '-card-' + index;
        var titleId = cardId + '-title';
        var descId = cardId + '-desc';

        // Create card element with accessibility attributes
        var card = document.createElement('article');
        card.className = 'cardcrafter-card';
        card.setAttribute('role', 'listitem');
        card.setAttribute('data-index', index);
        card.setAttribute('tabindex', '-1'); // Make focusable for keyboard nav
        card.setAttribute('aria-labelledby', titleId);
        if (description) {
            card.setAttribute('aria-describedby', descId);
        }

        // Card inner wrapper (for animations)
        var cardInner = document.createElement('div');
        cardInner.className = 'cardcrafter-card-inner';

        // Image section - only if showImage is not false
        if (this.options.showImage !== false && image) {
            var imageSection = document.createElement('div');
            imageSection.className = 'cardcrafter-card-image';
            var img = document.createElement('img');
            img.src = image || this.getPlaceholderImage(title);
            // More descriptive alt text for accessibility
            img.alt = subtitle ? title + ' - ' + subtitle : title;
            img.loading = 'lazy';
            img.onerror = function () {
                this.src = self.getPlaceholderImage(title);
            };
            imageSection.appendChild(img);
            cardInner.appendChild(imageSection);
        }

        // Content section
        var content = document.createElement('div');
        content.className = 'cardcrafter-card-content';

        // Title with unique ID for ARIA
        var titleEl = document.createElement('h3');
        titleEl.className = 'cardcrafter-card-title';
        titleEl.id = titleId;
        titleEl.textContent = title;
        content.appendChild(titleEl);

        // Subtitle
        if (subtitle) {
            var subtitleEl = document.createElement('p');
            subtitleEl.className = 'cardcrafter-card-subtitle';
            subtitleEl.textContent = subtitle;
            content.appendChild(subtitleEl);
        }

        // Description - only if showDescription is not false
        if (description && this.options.showDescription !== false) {
            var descEl = document.createElement('p');
            descEl.className = 'cardcrafter-card-description';
            descEl.id = descId;
            // Truncate if too long
            descEl.textContent = description.length > 150 ? description.substring(0, 147) + '...' : description;
            content.appendChild(descEl);
        }

        // Link/Button - only if showButtons is not false
        if (link && this.options.showButtons !== false) {
            var linkEl = document.createElement('a');
            linkEl.className = 'cardcrafter-card-link';
            linkEl.href = link;
            linkEl.textContent = 'Learn More';
            linkEl.target = '_blank';
            linkEl.rel = 'noopener noreferrer';
            // Accessible link text that includes card title
            linkEl.setAttribute('aria-label', 'Learn more about ' + title);
            content.appendChild(linkEl);
        }

        cardInner.appendChild(content);
        card.appendChild(cardInner);

        return card;
    };

    /**
     * Export Data in Various Formats
     */
    CardCrafter.prototype.exportData = function (format) {
        var self = this;
        var exportData = this.prepareExportData();
        var filename = this.generateFilename(format);

        // Announce export starting
        this.announce('Exporting ' + exportData.length + ' cards as ' + format.toUpperCase());

        try {
            switch (format) {
                case 'csv':
                    this.exportAsCSV(exportData, filename);
                    break;
                case 'json':
                    this.exportAsJSON(exportData, filename);
                    break;
                case 'pdf':
                    this.exportAsPDF(exportData, filename);
                    break;
                default:
                    throw new Error('Unsupported export format: ' + format);
            }

            // Track export for analytics
            this.trackExport(format, exportData.length);

        } catch (error) {
            console.error('CardCrafter Export Error:', error);
            this.showExportError(error.message);
            this.announce('Export failed: ' + error.message);
        }
    };

    /**
     * Prepare Data for Export
     */
    CardCrafter.prototype.prepareExportData = function () {
        var self = this;
        var fields = this.options.fields;
        
        // Use filtered items (respects current search/sort)
        return this.filteredItems.map(function(item, index) {
            var exportItem = {};
            
            // Standard fields
            exportItem.title = self.getNestedValue(item, fields.title) || 'Untitled';
            exportItem.subtitle = self.getNestedValue(item, fields.subtitle) || '';
            exportItem.description = self.getNestedValue(item, fields.description) || '';
            exportItem.link = self.getNestedValue(item, fields.link) || '';
            exportItem.image = self.getNestedValue(item, fields.image) || '';
            
            // Include additional data fields (like WordPress data)
            if (item.id) exportItem.id = item.id;
            if (item.post_type) exportItem.post_type = item.post_type;
            if (item.author) exportItem.author = item.author;
            if (item.date) exportItem.date = item.date;
            
            // Include any custom fields
            for (var key in item) {
                if (item.hasOwnProperty(key) && 
                    !exportItem.hasOwnProperty(key) && 
                    key !== '_searchableText' &&
                    !key.startsWith('_')) {
                    exportItem[key] = item[key];
                }
            }
            
            return exportItem;
        });
    };

    /**
     * Export as CSV
     */
    CardCrafter.prototype.exportAsCSV = function (data, filename) {
        if (!data.length) {
            throw new Error('No data to export');
        }
        
        // Get all unique keys from all items
        var allKeys = {};
        data.forEach(function(item) {
            Object.keys(item).forEach(function(key) {
                allKeys[key] = true;
            });
        });
        
        var headers = Object.keys(allKeys);
        var csvContent = headers.map(this.escapeCSVField).join(',') + '\n';
        
        data.forEach(function(item) {
            var row = headers.map(function(header) {
                var value = item[header] || '';
                return this.escapeCSVField(value);
            }.bind(this));
            csvContent += row.join(',') + '\n';
        }.bind(this));
        
        this.downloadFile(csvContent, filename, 'text/csv');
    };

    /**
     * Export as JSON
     */
    CardCrafter.prototype.exportAsJSON = function (data, filename) {
        var jsonContent = JSON.stringify({
            metadata: {
                exported_at: new Date().toISOString(),
                total_items: data.length,
                source: this.options.source || 'WordPress',
                layout: this.options.layout,
                cardcrafter_version: '1.7.0'
            },
            items: data
        }, null, 2);
        
        this.downloadFile(jsonContent, filename, 'application/json');
    };

    /**
     * Export as PDF (Simple text-based PDF)
     */
    CardCrafter.prototype.exportAsPDF = function (data, filename) {
        // Simple PDF creation without external libraries
        // This creates a basic text-based PDF structure
        var pdfContent = this.createSimplePDF(data);
        this.downloadFile(pdfContent, filename, 'application/pdf');
    };

    /**
     * Create Simple PDF Content
     */
    CardCrafter.prototype.createSimplePDF = function (data) {
        // Basic PDF structure - for production, would use a proper PDF library
        var content = '%PDF-1.4\n';
        content += '1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n';
        content += '2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n';
        content += '3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>endobj\n';
        content += '4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n';
        
        var text = 'CardCrafter Export - ' + new Date().toLocaleDateString() + '\\n\\n';
        
        data.forEach(function(item, index) {
            text += (index + 1) + '. ' + item.title + '\\n';
            if (item.subtitle) text += '   ' + item.subtitle + '\\n';
            if (item.description) text += '   ' + item.description.substring(0, 100) + '...\\n';
            if (item.link) text += '   URL: ' + item.link + '\\n';
            text += '\\n';
        });
        
        content += '5 0 obj<</Length ' + text.length + '>>stream\nBT /F1 12 Tf 50 750 Td (' + text + ') Tj ET\nendstream\nendobj\n';
        content += 'xref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000053 00000 n \n0000000100 00000 n \n0000000200 00000 n \n0000000300 00000 n \n';
        content += 'trailer<</Size 6/Root 1 0 R>>\nstartxref\n400\n%%EOF';
        
        return content;
    };

    /**
     * Escape CSV Field
     */
    CardCrafter.prototype.escapeCSVField = function (field) {
        if (typeof field !== 'string') {
            field = String(field);
        }
        
        // Escape quotes and wrap in quotes if necessary
        if (field.includes('"') || field.includes(',') || field.includes('\n')) {
            field = '"' + field.replace(/"/g, '""') + '"';
        }
        
        return field;
    };

    /**
     * Generate Export Filename
     */
    CardCrafter.prototype.generateFilename = function (format) {
        var timestamp = new Date().toISOString().slice(0, 19).replace(/[:.]/g, '-');
        var baseName = 'cardcrafter-export-' + timestamp;
        return baseName + '.' + format;
    };

    /**
     * Download File
     */
    CardCrafter.prototype.downloadFile = function (content, filename, mimeType) {
        var blob = new Blob([content], { type: mimeType });
        var url = window.URL.createObjectURL(blob);
        
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Clean up the blob URL
        window.URL.revokeObjectURL(url);
        
        // Show success message
        this.showExportSuccess(filename, mimeType);
    };

    /**
     * Track Export for Analytics
     */
    CardCrafter.prototype.trackExport = function (format, itemCount) {
        // Track export usage for future improvements
        if (typeof gtag !== 'undefined') {
            gtag('event', 'cardcrafter_export', {
                export_format: format,
                item_count: itemCount
            });
        }
    };

    /**
     * Show Export Success Message (accessible)
     */
    CardCrafter.prototype.showExportSuccess = function (filename, mimeType) {
        var self = this;
        var message = document.createElement('div');
        message.className = 'cardcrafter-export-success';
        message.setAttribute('role', 'alert');
        message.setAttribute('aria-live', 'assertive');
        message.innerHTML = '✅ Successfully exported ' + filename;
        message.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 20px; border-radius: 6px; z-index: 9999; font-weight: 500;';

        document.body.appendChild(message);

        // Announce success
        this.announce('Export complete: ' + filename);

        setTimeout(function () {
            if (message.parentNode) {
                message.parentNode.removeChild(message);
            }
        }, 3000);
    };

    /**
     * Show Export Error Message (accessible)
     */
    CardCrafter.prototype.showExportError = function (errorMsg) {
        var message = document.createElement('div');
        message.className = 'cardcrafter-export-error';
        message.setAttribute('role', 'alert');
        message.setAttribute('aria-live', 'assertive');
        message.innerHTML = '❌ Export failed: ' + errorMsg;
        message.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #dc2626; color: white; padding: 12px 20px; border-radius: 6px; z-index: 9999; font-weight: 500;';

        document.body.appendChild(message);

        setTimeout(function () {
            if (message.parentNode) {
                message.parentNode.removeChild(message);
            }
        }, 5000);
    };

    /**
     * Process WordPress or external data
     */
    CardCrafter.prototype.processData = function (data) {
        this.items = Array.isArray(data) ? data : (data.items || data.data || data.results || [data]);
        this.filteredItems = this.items.slice();
        this.renderLayout();
    };

    /**
     * Render Error State
     */
    CardCrafter.prototype.renderError = function (error) {
        var self = this;
        var errorMsg = error.message || 'Check your internet connection or data source URL.';

        this.container.innerHTML =
            '<div class="cardcrafter-error-state" style="padding: 40px; text-align: center; border: 1px solid #fee2e2; background: #fef2f2; border-radius: 8px;">' +
            '<div style="font-size: 24px; margin-bottom: 10px;">⚠️</div>' +
            '<h3 style="margin: 0 0 10px 0; color: #991b1b;">Unable to load cards</h3>' +
            '<p style="margin: 0 0 20px 0; color: #b91c1c; font-size: 14px;">' + errorMsg + '</p>' +
            '<button class="cardcrafter-retry-button" style="background: #991b1b; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600;">' +
            'Retry Loading' +
            '</button>' +
            '</div>';

        var retryBtn = this.container.querySelector('.cardcrafter-retry-button');
        if (retryBtn) {
            retryBtn.addEventListener('click', function () {
                self.init();
            });
        }
    };

    // Expose to global scope
    global.CardCrafter = CardCrafter;

})(typeof window !== 'undefined' ? window : this);
