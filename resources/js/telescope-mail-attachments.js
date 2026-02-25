/**
 * Telescope Mail Attachments - Vue Component Override
 *
 * Replaces the mail-preview route component with an enhanced version
 * that includes an attachments table. Works with Telescope's Vue 2 SPA
 * by overriding the route component via Vue Router's matcher.
 */
(function () {
    'use strict';

    /**
     * Format a list of email addresses (replicates lodash _.chain logic).
     */
    function formatAddresses(addresses) {
        if (!addresses) return '';
        var result = [];
        for (var email in addresses) {
            if (addresses.hasOwnProperty(email)) {
                var name = addresses[email];
                result.push((name ? name + ' <' + email + '>' : email));
            }
        }
        return result.join(', ');
    }

    /**
     * Format bytes into a human-readable string.
     */
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * The enhanced mail preview component template.
     * Reproduces the original Telescope mail preview template and adds
     * an Attachments row after the Download row.
     */
    var template = ''
        + '<preview-screen title="Mail Details" resource="mail" :id="$route.params.id">'
        +   '<template slot="table-parameters" slot-scope="slotProps">'
        +     '<tr>'
        +       '<td class="table-fit text-muted">Mailable</td>'
        +       '<td>'
        +         '{{ slotProps.entry.content.mailable }}'
        +         '<span class="badge badge-secondary ml-2" v-if="slotProps.entry.content.queued"> Queued </span>'
        +       '</td>'
        +     '</tr>'
        +     '<tr>'
        +       '<td class="table-fit text-muted">From</td>'
        +       '<td>{{ formatAddresses(slotProps.entry.content.from) }}</td>'
        +     '</tr>'
        +     '<tr>'
        +       '<td class="table-fit text-muted">To</td>'
        +       '<td>{{ formatAddresses(slotProps.entry.content.to) }}</td>'
        +     '</tr>'
        +     '<tr v-if="slotProps.entry.content.replyTo">'
        +       '<td class="table-fit text-muted">Reply-To</td>'
        +       '<td>{{ formatAddresses(slotProps.entry.content.replyTo) }}</td>'
        +     '</tr>'
        +     '<tr v-if="slotProps.entry.content.cc">'
        +       '<td class="table-fit text-muted">CC</td>'
        +       '<td>{{ formatAddresses(slotProps.entry.content.cc) }}</td>'
        +     '</tr>'
        +     '<tr v-if="slotProps.entry.content.bcc">'
        +       '<td class="table-fit text-muted">BCC</td>'
        +       '<td>{{ formatAddresses(slotProps.entry.content.bcc) }}</td>'
        +     '</tr>'
        +     '<tr>'
        +       '<td class="table-fit text-muted">Subject</td>'
        +       '<td>{{ slotProps.entry.content.subject }}</td>'
        +     '</tr>'
        +     '<tr>'
        +       '<td class="table-fit text-muted">Download</td>'
        +       '<td>'
        +         '<a :href="Telescope.basePath + \'/telescope-api/mail/\' + $route.params.id + \'/download\'">'
        +           'Download .eml file'
        +         '</a>'
        +       '</td>'
        +     '</tr>'
        // --- Attachments row (addition) ---
        +     '<tr v-if="slotProps.entry.content.attachments && slotProps.entry.content.attachments.length">'
        +       '<td class="table-fit text-muted">Attachments</td>'
        +       '<td>'
        +         '<table class="table table-sm mb-0">'
        +           '<thead><tr><th>Filename</th><th>Size</th><th>Type</th></tr></thead>'
        +           '<tbody>'
        +             '<tr v-for="(attachment, index) in slotProps.entry.content.attachments" :key="index">'
        +               '<td>'
        +                 '<a v-if="attachment.content" :href="attachmentUrl(index)">{{ attachment.filename }}</a>'
        +                 '<span v-else>{{ attachment.filename }}</span>'
        +               '</td>'
        +               '<td>{{ formatBytes(attachment.size) }}</td>'
        +               '<td>{{ attachment.mime_type }}</td>'
        +             '</tr>'
        +           '</tbody>'
        +         '</table>'
        +       '</td>'
        +     '</tr>'
        // --- End attachments row ---
        +   '</template>'
        +   '<div slot="after-attributes-card" slot-scope="slotProps" class="mt-5">'
        +     '<div class="card">'
        +       '<iframe '
        +         ':src="Telescope.basePath + \'/telescope-api/mail/\' + $route.params.id + \'/preview\'" '
        +         'width="100%" height="400" style="border:none;"'
        +       '></iframe>'
        +     '</div>'
        +   '</div>'
        + '</preview-screen>';

    /**
     * The enhanced mail preview component definition.
     */
    var EnhancedMailPreview = {
        template: template,
        methods: {
            formatAddresses: formatAddresses,
            formatBytes: formatBytes,
            attachmentUrl: function (index) {
                return Telescope.basePath + '/telescope-api/mail/' + this.$route.params.id + '/attachments/' + index;
            }
        }
    };

    /**
     * Replace the mail-preview route component.
     */
    function overrideRoute() {
        var el = document.getElementById('telescope');
        if (!el || !el.__vue__) return false;

        var app = el.__vue__;
        var router = app.$router;
        if (!router) return false;

        var routes = router.options.routes;
        if (!routes) return false;

        for (var i = 0; i < routes.length; i++) {
            if (routes[i].name === 'mail-preview') {
                routes[i].component = EnhancedMailPreview;
                break;
            }
        }

        // Reset the matcher so the router picks up the new component
        try {
            var freshRouter = new router.constructor({ mode: 'history', routes: [] });
            router.matcher = freshRouter.matcher;
            router.addRoutes(routes);
        } catch (e) {
            console.error('[telescope-mail-attachments] Failed to reset router matcher:', e);
            return false;
        }

        return true;
    }

    /**
     * Poll until Telescope's Vue app is mounted, then override the route.
     */
    function init() {
        var attempts = 0;
        var maxAttempts = 50; // 5 seconds max

        var interval = setInterval(function () {
            attempts++;
            if (overrideRoute() || attempts >= maxAttempts) {
                clearInterval(interval);
            }
        }, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
