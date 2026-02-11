/**
 * Telescope Mail Attachments - DOM Injection Script
 *
 * Injects attachment badges on the mail index page and an attachments
 * table on the mail preview page. Uses MutationObserver to detect when
 * Telescope's Vue SPA renders content.
 */
(function () {
    const PROCESSED_ATTR = 'data-attachments-processed';

    /**
     * Get Telescope's base path from the global variable.
     */
    function getBasePath() {
        return (window.Telescope && window.Telescope.path) || '/telescope';
    }

    /**
     * Get the current hash route.
     */
    function getCurrentRoute() {
        return window.location.hash.replace(/^#/, '');
    }

    /**
     * Format bytes into a human-readable string.
     */
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Fetch a mail entry's data from Telescope's API.
     */
    async function fetchMailEntry(entryId) {
        const basePath = getBasePath();
        const response = await fetch(basePath + '/telescope-api/mail/' + entryId);
        if (!response.ok) return null;
        const data = await response.json();
        return data.entry || data;
    }

    /**
     * Create the paperclip SVG icon.
     */
    function createPaperclipIcon() {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        svg.setAttribute('viewBox', '0 0 20 20');
        svg.setAttribute('width', '12');
        svg.setAttribute('height', '12');
        svg.setAttribute('fill', 'currentColor');
        svg.style.verticalAlign = '-1px';

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('fill-rule', 'evenodd');
        path.setAttribute('d', 'M15.621 4.379a3 3 0 0 0-4.242 0l-7 7a3 3 0 0 0 4.241 4.243h.001l.497-.5a.75.75 0 0 1 1.064 1.057l-.498.501-.002.002a4.5 4.5 0 0 1-6.364-6.364l7-7a4.5 4.5 0 0 1 6.368 6.36l-3.455 3.553A2.625 2.625 0 1 1 9.52 9.52l3.45-3.451a.75.75 0 1 1 1.061 1.06l-3.45 3.451a1.125 1.125 0 0 0 1.587 1.595l3.454-3.553a3 3 0 0 0 0-4.242Z');
        path.setAttribute('clip-rule', 'evenodd');

        svg.appendChild(path);
        return svg;
    }

    // =========================================================================
    // Mail Index Page — Attachment badges
    // =========================================================================

    /**
     * Process a single table row on the mail index page.
     * Fetches the entry data and adds an attachment badge if needed.
     */
    async function processIndexRow(row) {
        if (row.hasAttribute(PROCESSED_ATTR)) return;
        row.setAttribute(PROCESSED_ATTR, '1');

        // Find the router-link in the last cell to extract the entry ID
        const link = row.querySelector('td:last-child a[href]');
        if (!link) return;

        const href = link.getAttribute('href');
        const match = href.match(/\/mail\/([^/]+)/);
        if (!match) return;

        const entryId = match[1];
        const entry = await fetchMailEntry(entryId);
        if (!entry || !entry.content || !entry.content.attachments || !entry.content.attachments.length) return;

        // Find the first <td> and add the badge after the "Queued" badge (or after the mailable name)
        const firstTd = row.querySelector('td');
        if (!firstTd) return;

        // Check if badge already exists
        if (firstTd.querySelector('.telescope-mail-attachments-badge')) return;

        const badge = document.createElement('span');
        badge.className = 'badge badge-secondary ml-2 telescope-mail-attachments-badge';
        badge.appendChild(createPaperclipIcon());
        badge.appendChild(document.createTextNode(' ' + entry.content.attachments.length));

        // Insert after the last badge or after the first span
        const existingBadges = firstTd.querySelectorAll('.badge');
        if (existingBadges.length > 0) {
            existingBadges[existingBadges.length - 1].after(badge);
        } else {
            const firstSpan = firstTd.querySelector('span');
            if (firstSpan) {
                firstSpan.after(badge);
            }
        }
    }

    /**
     * Process all rows on the mail index page.
     */
    function processIndexPage() {
        const route = getCurrentRoute();
        if (!route.match(/^\/mail\/?$/)) return;

        const rows = document.querySelectorAll('table tbody tr');
        rows.forEach(processIndexRow);
    }

    // =========================================================================
    // Mail Preview Page — Attachments table
    // =========================================================================

    /**
     * Inject the attachments table on the mail preview page.
     */
    async function processPreviewPage() {
        const route = getCurrentRoute();
        const match = route.match(/^\/mail\/([^/]+)$/);
        if (!match) return;

        // Don't process if already injected
        if (document.querySelector('.telescope-mail-attachments-table')) return;

        const entryId = match[1];

        // Wait for the preview table to render (look for the "Download" row)
        const tables = document.querySelectorAll('table.table');
        let targetTable = null;
        let downloadRow = null;

        for (const table of tables) {
            const rows = table.querySelectorAll('tr');
            for (const row of rows) {
                const td = row.querySelector('td');
                if (td && td.textContent.trim() === 'Download') {
                    targetTable = table;
                    downloadRow = row;
                    break;
                }
            }
            if (downloadRow) break;
        }

        if (!downloadRow) return;

        const entry = await fetchMailEntry(entryId);
        if (!entry || !entry.content || !entry.content.attachments || !entry.content.attachments.length) return;

        const basePath = getBasePath();
        const attachments = entry.content.attachments;

        // Create the attachments row
        const tr = document.createElement('tr');
        tr.className = 'telescope-mail-attachments-table';

        const labelTd = document.createElement('td');
        labelTd.className = 'table-fit text-muted';
        labelTd.textContent = 'Attachments';

        const contentTd = document.createElement('td');

        const innerTable = document.createElement('table');
        innerTable.className = 'table table-sm mb-0';

        // Table header
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        ['Filename', 'Size', 'Type'].forEach(function (text) {
            const th = document.createElement('th');
            th.textContent = text;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        innerTable.appendChild(thead);

        // Table body
        const tbody = document.createElement('tbody');
        attachments.forEach(function (attachment, index) {
            const row = document.createElement('tr');

            const filenameTd = document.createElement('td');
            if (attachment.content) {
                const link = document.createElement('a');
                link.href = basePath + '/telescope-api/mail/' + entryId + '/attachments/' + index;
                link.textContent = attachment.filename;
                filenameTd.appendChild(link);
            } else {
                filenameTd.textContent = attachment.filename;
            }

            const sizeTd = document.createElement('td');
            sizeTd.textContent = formatBytes(attachment.size);

            const typeTd = document.createElement('td');
            typeTd.textContent = attachment.mime_type;

            row.appendChild(filenameTd);
            row.appendChild(sizeTd);
            row.appendChild(typeTd);
            tbody.appendChild(row);
        });
        innerTable.appendChild(tbody);

        contentTd.appendChild(innerTable);
        tr.appendChild(labelTd);
        tr.appendChild(contentTd);

        // Insert after the download row
        downloadRow.after(tr);
    }

    // =========================================================================
    // Observer — Watch for DOM changes in Telescope's SPA
    // =========================================================================

    let debounceTimer = null;

    function handleMutations() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            processIndexPage();
            processPreviewPage();
        }, 200);
    }

    // Start observing once the DOM is ready
    function init() {
        const observer = new MutationObserver(handleMutations);

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });

        // Also listen for hash changes (Vue Router navigation)
        window.addEventListener('hashchange', function () {
            // Small delay to let Vue render the new page
            setTimeout(handleMutations, 300);
        });

        // Initial run
        handleMutations();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
