document.addEventListener('DOMContentLoaded', function () {
    const urlInput = document.getElementById('cc-preview-url');
    const layoutSelect = document.getElementById('cc-layout');
    const columnsSelect = document.getElementById('cc-columns');
    const previewBtn = document.getElementById('cc-preview-btn');
    const copyBtn = document.getElementById('cc-copy-shortcode');
    const shortcodeDisplay = document.getElementById('cc-shortcode-display');
    const container = document.getElementById('cc-preview-container');
    const demoLinks = document.querySelectorAll('.cc-demo-card');

    if (!urlInput) return;

    // Update shortcode display
    function updateShortcode() {
        const url = urlInput.value.trim() || 'URL';
        const layout = layoutSelect.value;
        const columns = columnsSelect.value;
        const enableSearch = document.getElementById('cc-enable-search');
        const enableFilters = document.getElementById('cc-enable-filters');
        const showDescription = document.getElementById('cc-show-description');
        const showButtons = document.getElementById('cc-show-buttons');
        const enableExport = document.getElementById('cc-enable-export');
        const showImage = document.getElementById('cc-show-image');
        const cardStyle = document.getElementById('cc-card-style');
        const enablePagination = document.getElementById('cc-enable-pagination');
        const itemsPerPage = document.getElementById('cc-items-per-page');
        
        let shortcode = `[cardcrafter source="${url}" layout="${layout}" columns="${columns}"`;
        
        // Add optional parameters only if they differ from defaults
        if (enableSearch && !enableSearch.checked) {
            shortcode += ` search="false"`;
        }
        if (enableFilters && !enableFilters.checked) {
            shortcode += ` filters="false"`;
        }
        if (showDescription && !showDescription.checked) {
            shortcode += ` show_description="false"`;
        }
        if (showButtons && !showButtons.checked) {
            shortcode += ` show_cta="false"`;
        }
        if (enableExport && !enableExport.checked) {
            shortcode += ` export="false"`;
        }
        if (showImage && !showImage.checked) {
            shortcode += ` show_image="false"`;
        }
        if (cardStyle && cardStyle.value !== 'default') {
            shortcode += ` style="${cardStyle.value}"`;
        }
        if (enablePagination && !enablePagination.checked) {
            shortcode += ` pagination="false"`;
        }
        if (itemsPerPage && itemsPerPage.value !== '6') {
            shortcode += ` items_per_page="${itemsPerPage.value}"`;
        }
        
        shortcode += `]`;
        shortcodeDisplay.innerText = shortcode;
    }

    urlInput.addEventListener('input', updateShortcode);
    layoutSelect.addEventListener('change', updateShortcode);
    columnsSelect.addEventListener('change', updateShortcode);

    // Add event listeners for new controls
    const additionalControls = [
        'cc-enable-search',
        'cc-enable-filters',
        'cc-show-description', 
        'cc-show-buttons',
        'cc-enable-export',
        'cc-show-image',
        'cc-card-style',
        'cc-enable-pagination',
        'cc-items-per-page'
    ];

    additionalControls.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function() {
                updateShortcode();
                // Auto-trigger preview when display options change
                if (urlInput.value.trim()) {
                    previewBtn.click();
                }
            });
        }
    });

    // Load demo URL on click
    demoLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            urlInput.value = this.dataset.url;
            updateShortcode();
            previewBtn.click();
        });
    });

    // Preview functionality
    previewBtn.addEventListener('click', function () {
        const url = urlInput.value.trim();
        if (!url) {
            alert(cardcrafterAdmin.i18n.validUrl);
            return;
        }

        // Reset container
        container.innerHTML = '';
        container.style.display = 'block';

        if (typeof CardCrafter !== 'undefined') {
            const cardId = 'cardcrafter-preview-' + Date.now();
            container.innerHTML = `<div id="${cardId}" class="cardcrafter-container">${cardcrafterAdmin.i18n.loading}</div>`;

            // Get additional options
            const enableSearch = document.getElementById('cc-enable-search');
            const enableFilters = document.getElementById('cc-enable-filters');
            const showDescription = document.getElementById('cc-show-description');
            const showButtons = document.getElementById('cc-show-buttons');
            const enableExport = document.getElementById('cc-enable-export');
            const showImage = document.getElementById('cc-show-image');
            const cardStyle = document.getElementById('cc-card-style');
            const enablePagination = document.getElementById('cc-enable-pagination');
            const itemsPerPage = document.getElementById('cc-items-per-page');

            // Determine source URL based on whether it's wp_posts or external URL
            let sourceUrl;
            if (url === 'wp_posts') {
                // For WordPress posts, use a special AJAX endpoint
                sourceUrl = `${cardcrafterAdmin.ajaxurl}?action=cardcrafter_wp_posts_preview&nonce=${cardcrafterAdmin.nonce}`;
            } else {
                // For external URLs, use the secure proxy
                sourceUrl = `${cardcrafterAdmin.ajaxurl}?action=cardcrafter_proxy_fetch&url=${encodeURIComponent(url)}&nonce=${cardcrafterAdmin.nonce}`;
            }

            new CardCrafter({
                selector: '#' + cardId,
                source: sourceUrl,
                layout: layoutSelect.value,
                columns: parseInt(columnsSelect.value),
                search: enableSearch ? enableSearch.checked : true,
                filters: enableFilters ? enableFilters.checked : true,
                showDescription: showDescription ? showDescription.checked : true,
                showButtons: showButtons ? showButtons.checked : true,
                enableExport: enableExport ? enableExport.checked : true,
                showImage: showImage ? showImage.checked : true,
                cardStyle: cardStyle ? cardStyle.value : 'default',
                pagination: enablePagination ? enablePagination.checked : true,
                itemsPerPage: itemsPerPage ? parseInt(itemsPerPage.value) : 6
            });
        } else {
            container.innerHTML = `<div class="notice notice-error inline"><p>${cardcrafterAdmin.i18n.libNotLoaded}</p></div>`;
        }
    });

    // Use WP Posts functionality
    const wpPostsBtn = document.getElementById('cc-wp-posts-btn');
    if (wpPostsBtn) {
        wpPostsBtn.addEventListener('click', function() {
            // Set the source to use WordPress posts
            urlInput.value = 'wp_posts';
            updateShortcode();
            
            // Auto-trigger preview
            previewBtn.click();
        });
    }

    // Copy shortcode functionality
    copyBtn.addEventListener('click', function () {
        const text = shortcodeDisplay.innerText;

        const copyToClipboard = async (text) => {
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    throw new Error('Clipboard API unavailable');
                }
            } catch (err) {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-9999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    textArea.remove();
                } catch (e) {
                    console.error('Copy failed', e);
                    textArea.remove();
                    alert(cardcrafterAdmin.i18n.copyFailed);
                    return;
                }
            }

            const originalText = copyBtn.innerText;
            copyBtn.innerText = cardcrafterAdmin.i18n.copied;
            setTimeout(() => copyBtn.innerText = originalText, 2000);
        };

        copyToClipboard(text);
    });
});
