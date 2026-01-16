document.addEventListener('DOMContentLoaded', function () {
    const containers = document.querySelectorAll('.cardcrafter-container');

    containers.forEach(container => {
        if (typeof CardCrafter !== 'undefined') {
            const config = JSON.parse(container.dataset.config);

            new CardCrafter({
                selector: '#' + container.id,
                source: config.source,
                layout: config.layout,
                columns: parseInt(config.columns),
                fields: config.fields,
                itemsPerPage: config.itemsPerPage || 12,
                wpDataMode: config.wpDataMode || false,
                data: config.data || null
            });
        }
    });
});
