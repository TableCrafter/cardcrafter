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

        this.items = []; // Stores the original fetched data
        this.filteredItems = []; // Stores items after search/filter operations
        this.gridWrapper = null; // Will hold the DOM element for the card grid
        this.searchTimeout = null; // Debounce timeout for search
        this.searchCache = {}; // Cache search results for better performance
        
        // Pagination properties
        this.currentPage = 1;
        this.itemsPerPage = this.options.itemsPerPage || 12; // Default 12 items per page
        this.paginationWrapper = null; // Will hold pagination controls

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
     */
    CardCrafter.prototype.renderLayout = function () {
        this.container.innerHTML = '';
        
        // render toolbar
        this.renderToolbar();

        // create grid wrapper
        this.gridWrapper = this.createEl('div', 'cardcrafter-grid-wrapper');
        this.container.appendChild(this.gridWrapper);

        // create pagination wrapper
        this.paginationWrapper = this.createEl('div', 'cardcrafter-pagination-wrapper');
        this.container.appendChild(this.paginationWrapper);

        // initial sort and render
        this.sortItems('default');
        this.renderPaginatedCards();
    };

    /**
     * Render the Search and Sort Toolbar
     */
    CardCrafter.prototype.renderToolbar = function () {
        var self = this;
        var toolbar = this.createEl('div', 'cardcrafter-toolbar');

        // Search Input
        var searchWrapper = this.createEl('div', 'cardcrafter-search-wrapper');
        var searchInput = this.createEl('input', 'cardcrafter-search-input', {
            type: 'search',
            placeholder: 'Search items...'
        });

        searchInput.addEventListener('input', function (e) {
            self.debouncedSearch(e.target.value);
        });

        searchWrapper.appendChild(searchInput);
        toolbar.appendChild(searchWrapper);

        // Sort Dropdown
        var sortWrapper = this.createEl('div', 'cardcrafter-sort-wrapper');
        var sortSelect = this.createEl('select', 'cardcrafter-sort-select');
        
        var options = [
            { value: 'default', text: 'Default Order' },
            { value: 'az', text: 'Name (A-Z)' },
            { value: 'za', text: 'Name (Z-A)' }
        ];

        options.forEach(function(opt) {
            var option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
            sortSelect.appendChild(option);
        });

        sortSelect.addEventListener('change', function (e) {
            self.sortItems(e.target.value);
            self.renderPaginatedCards();
        });

        sortWrapper.appendChild(sortSelect);
        toolbar.appendChild(sortWrapper);

        // Items Per Page Dropdown
        var perPageWrapper = this.createEl('div', 'cardcrafter-per-page-wrapper');
        var perPageLabel = this.createEl('label', 'cardcrafter-per-page-label');
        perPageLabel.textContent = 'Items: ';
        var perPageSelect = this.createEl('select', 'cardcrafter-per-page-select');
        
        var perPageOptions = [
            { value: '6', text: '6 per page' },
            { value: '12', text: '12 per page' },
            { value: '24', text: '24 per page' },
            { value: '50', text: '50 per page' },
            { value: '100', text: '100 per page' }
        ];

        perPageOptions.forEach(function(opt) {
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
        });

        perPageWrapper.appendChild(perPageLabel);
        perPageWrapper.appendChild(perPageSelect);
        toolbar.appendChild(perPageWrapper);

        // Export Dropdown
        var exportWrapper = this.createEl('div', 'cardcrafter-export-wrapper');
        var exportButton = this.createEl('button', 'cardcrafter-export-button');
        exportButton.textContent = 'Export Data';
        exportButton.type = 'button';
        
        var exportDropdown = this.createEl('div', 'cardcrafter-export-dropdown');
        exportDropdown.style.display = 'none';
        
        var exportOptions = [
            { value: 'csv', text: 'Export as CSV', icon: '📊' },
            { value: 'json', text: 'Export as JSON', icon: '📄' },
            { value: 'pdf', text: 'Export as PDF', icon: '📋' }
        ];

        exportOptions.forEach(function(opt) {
            var exportItem = self.createEl('button', 'cardcrafter-export-item');
            exportItem.innerHTML = opt.icon + ' ' + opt.text;
            exportItem.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.exportData(opt.value);
                exportDropdown.style.display = 'none';
            });
            exportDropdown.appendChild(exportItem);
        });

        exportButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var isVisible = exportDropdown.style.display === 'block';
            exportDropdown.style.display = isVisible ? 'none' : 'block';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!exportWrapper.contains(e.target)) {
                exportDropdown.style.display = 'none';
            }
        });

        exportWrapper.appendChild(exportButton);
        exportWrapper.appendChild(exportDropdown);
        toolbar.appendChild(exportWrapper);

        this.container.appendChild(toolbar);
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
        var currentSort = this.container.querySelector('.cardcrafter-sort-select').value;
        this.sortItems(currentSort);

        // Reset to first page when search changes
        this.currentPage = 1;
        this.renderPaginatedCards();
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
     * Render cards from data
     */
    CardCrafter.prototype.renderCards = function (items) {
        var self = this;
        
        if (!items.length) {
            this.gridWrapper.innerHTML = '<div class="cardcrafter-no-results"><p>No items found matching your search.</p></div>';
            return;
        }

        // Create grid container
        var grid = document.createElement('div');
        grid.className = 'cardcrafter-grid cardcrafter-layout-' + this.options.layout + ' cardcrafter-cols-' + this.options.columns;

        // Performance optimization: Use DocumentFragment for batch DOM operations
        var fragment = document.createDocumentFragment();
        
        // Generate cards
        items.forEach(function (item, index) {
            var card = self.createCard(item, index);
            fragment.appendChild(card);
        });
        
        grid.appendChild(fragment);
        this.gridWrapper.innerHTML = '';
        this.gridWrapper.appendChild(grid);
    };

    /**
     * Render paginated cards with pagination controls
     */
    CardCrafter.prototype.renderPaginatedCards = function () {
        var totalItems = this.filteredItems.length;
        var totalPages = Math.ceil(totalItems / this.itemsPerPage);
        
        // Calculate current page items
        var startIndex = (this.currentPage - 1) * this.itemsPerPage;
        var endIndex = startIndex + this.itemsPerPage;
        var currentPageItems = this.filteredItems.slice(startIndex, endIndex);
        
        // Render the current page of cards
        this.renderCards(currentPageItems);
        
        // Render pagination controls
        this.renderPaginationControls(totalPages, totalItems);
    };

    /**
     * Render pagination controls
     */
    CardCrafter.prototype.renderPaginationControls = function (totalPages, totalItems) {
        var self = this;
        
        if (!this.paginationWrapper) return;
        
        this.paginationWrapper.innerHTML = '';
        
        // Don't show pagination if only one page or no items
        if (totalPages <= 1) return;
        
        var pagination = this.createEl('div', 'cardcrafter-pagination');
        
        // Results info
        var startItem = ((this.currentPage - 1) * this.itemsPerPage) + 1;
        var endItem = Math.min(this.currentPage * this.itemsPerPage, totalItems);
        var resultsInfo = this.createEl('div', 'cardcrafter-pagination-info');
        resultsInfo.textContent = 'Showing ' + startItem + '-' + endItem + ' of ' + totalItems + ' items';
        pagination.appendChild(resultsInfo);
        
        // Pagination controls container
        var controls = this.createEl('div', 'cardcrafter-pagination-controls');
        
        // Previous button
        var prevBtn = this.createEl('button', 'cardcrafter-pagination-btn cardcrafter-pagination-prev');
        prevBtn.textContent = 'Previous';
        prevBtn.disabled = this.currentPage === 1;
        prevBtn.addEventListener('click', function() {
            if (self.currentPage > 1) {
                self.currentPage--;
                self.renderPaginatedCards();
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
            if (i === this.currentPage) {
                pageBtn.classList.add('cardcrafter-pagination-current');
            }
            pageBtn.addEventListener('click', this.createPageClickHandler(i));
            controls.appendChild(pageBtn);
        }
        
        // Next button
        var nextBtn = this.createEl('button', 'cardcrafter-pagination-btn cardcrafter-pagination-next');
        nextBtn.textContent = 'Next';
        nextBtn.disabled = this.currentPage === totalPages;
        nextBtn.addEventListener('click', function() {
            if (self.currentPage < totalPages) {
                self.currentPage++;
                self.renderPaginatedCards();
            }
        });
        controls.appendChild(nextBtn);
        
        pagination.appendChild(controls);
        this.paginationWrapper.appendChild(pagination);
    };

    /**
     * Create page click handler (closure to capture page number)
     */
    CardCrafter.prototype.createPageClickHandler = function(pageNumber) {
        var self = this;
        return function() {
            self.currentPage = pageNumber;
            self.renderPaginatedCards();
        };
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
        card.className = 'cardcrafter-card';
        card.setAttribute('data-index', index);

        // Card inner wrapper (for animations)
        var cardInner = document.createElement('div');
        cardInner.className = 'cardcrafter-card-inner';

        // Image section
        var imageSection = document.createElement('div');
        imageSection.className = 'cardcrafter-card-image';
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
        content.className = 'cardcrafter-card-content';

        // Title
        var titleEl = document.createElement('h3');
        titleEl.className = 'cardcrafter-card-title';
        titleEl.textContent = title;
        content.appendChild(titleEl);

        // Subtitle
        if (subtitle) {
            var subtitleEl = document.createElement('p');
            subtitleEl.className = 'cardcrafter-card-subtitle';
            subtitleEl.textContent = subtitle;
            content.appendChild(subtitleEl);
        }

        // Description
        if (description) {
            var descEl = document.createElement('p');
            descEl.className = 'cardcrafter-card-description';
            // Truncate if too long
            descEl.textContent = description.length > 150 ? description.substring(0, 147) + '...' : description;
            content.appendChild(descEl);
        }

        // Link/Button
        if (link) {
            var linkEl = document.createElement('a');
            linkEl.className = 'cardcrafter-card-link';
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

    /**
     * Export Data in Various Formats
     */
    CardCrafter.prototype.exportData = function (format) {
        var self = this;
        var exportData = this.prepareExportData();
        var filename = this.generateFilename(format);
        
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
     * Show Export Success Message
     */
    CardCrafter.prototype.showExportSuccess = function (filename, mimeType) {
        var message = document.createElement('div');
        message.className = 'cardcrafter-export-success';
        message.innerHTML = '✅ Successfully exported ' + filename;
        message.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 20px; border-radius: 6px; z-index: 9999; font-weight: 500;';
        
        document.body.appendChild(message);
        
        setTimeout(function() {
            if (message.parentNode) {
                message.parentNode.removeChild(message);
            }
        }, 3000);
    };

    /**
     * Show Export Error Message
     */
    CardCrafter.prototype.showExportError = function (errorMsg) {
        var message = document.createElement('div');
        message.className = 'cardcrafter-export-error';
        message.innerHTML = '❌ Export failed: ' + errorMsg;
        message.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #dc2626; color: white; padding: 12px 20px; border-radius: 6px; z-index: 9999; font-weight: 500;';
        
        document.body.appendChild(message);
        
        setTimeout(function() {
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
