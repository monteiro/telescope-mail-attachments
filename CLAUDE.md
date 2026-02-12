# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Laravel package that adds mail attachment support to Laravel Telescope. It captures email attachments (filename, size, MIME type, optionally base64 content) during mail recording, provides a download endpoint, and injects a Vue.js UI override into Telescope's mail preview.

**Package:** `monteiro/telescope-mail-attachments`
**Namespace:** `Monteiro\TelescopeMailAttachments`
**Requires:** PHP ^8.1, Laravel Telescope ^5.0

## Commands

- `composer test` — run Pest test suite
- `composer test-coverage` — run tests with coverage
- `composer format` — run Laravel Pint (code formatter)
- Run a single test: `vendor/bin/pest --filter="test name"`

## Architecture

### Core flow

1. **MailAttachmentWatcher** (`src/MailAttachmentWatcher.php`) extends Telescope's `MailWatcher`. Overrides `recordMail()` to extract attachment metadata (and optionally base64 content) from sent mail.
2. **TelescopeMailAttachmentsServiceProvider** bootstraps everything: merges config, pushes `InjectJavaScript` middleware onto the `telescope` middleware group, and registers the download route.
3. **MailAttachmentController** (`src/Http/Controllers/`) serves attachment downloads by reading base64 content from Telescope entries.
4. **InjectJavaScript middleware** (`src/Http/Middleware/`) injects `resources/js/telescope-mail-attachments.js` as a `<script type="module">` before `</body>` on HTML responses.
5. **telescope-mail-attachments.js** — vanilla JS (no build step) that polls for Telescope's Vue app, then replaces the `mail-preview` route component with an enhanced version showing an attachments table with download links.

### Route registration trick

The service provider temporarily removes Telescope's catch-all `/{view?}` route, registers the specific attachment download route (`/telescope/telescope-api/mail/{telescopeEntryId}/attachments/{index}`), then re-adds the catch-all so the specific route takes precedence.

### Configuration

`config/telescope-mail-attachments.php` — single option `store_content` (env: `TELESCOPE_MAIL_ATTACHMENTS_STORE_CONTENT`, default `true`) controls whether base64 attachment content is stored.

## Testing

Uses Pest with Orchestra Testbench. Base `TestCase` sets up SQLite in-memory DB, loads Telescope migrations, and configures `MailAttachmentWatcher`. Tests use `Mail::raw()` with `attachData()` to simulate emails with attachments, then verify Telescope entries.
