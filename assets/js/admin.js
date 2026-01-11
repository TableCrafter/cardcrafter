document.addEventListener('DOMContentLoaded', function () {
    const urlInput = document.getElementById('cardcrafter-preview-url');
    const layoutSelect = document.getElementById('cardcrafter-layout');
    const columnsSelect = document.getElementById('cardcrafter-columns');
    const previewBtn = document.getElementById('cardcrafter-preview-btn');
    const copyBtn = document.getElementById('cardcrafter-copy-shortcode');
    const shortcodeDisplay = document.getElementById('cardcrafter-shortcode-display');
    const container = document.getElementById('cardcrafter-preview-container');
    const demoLinks = document.querySelectorAll('.cardcrafter-demo-links a');

    if (!urlInput) return;

    // Update shortcode display
    function updateShortcode() {
        const url = urlInput.value.trim() || 'URL';
        const layout = layoutSelect.value;
        const columns = columnsSelect.value;
        shortcodeDisplay.innerText = `[cardcrafter-wp-grid-layouts source="${url}" layout="${layout}" columns="${columns}"]`;
    }

    urlInput.addEventListener('input', updateShortcode);
    layoutSelect.addEventListener('change', updateShortcode);
    columnsSelect.addEventListener('change', updateShortcode);

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

            // Use the secure proxy for admin previews too
            const proxyUrl = `${cardcrafterAdmin.ajaxurl}?action=cardcrafter_proxy_fetch&url=${encodeURIComponent(url)}&nonce=${cardcrafterAdmin.nonce}`;

            new CardCrafter({
                selector: '#' + cardId,
                source: proxyUrl,
                layout: layoutSelect.value,
                columns: parseInt(columnsSelect.value)
            });
        } else {
            container.innerHTML = `<div class="notice notice-error inline"><p>${cardcrafterAdmin.i18n.libNotLoaded}</p></div>`;
        }
    });

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
