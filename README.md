# Telescope Mail Attachments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/monteiro/telescope-mail-attachments.svg?style=flat-square)](https://packagist.org/packages/monteiro/telescope-mail-attachments)

Mail attachment support for [Laravel Telescope](https://github.com/laravel/telescope). Captures email attachments (filename, size, MIME type, content) during mail recording, stores them in Telescope entries, and provides download endpoints + UI.

## Installation

```bash
composer require monteiro/telescope-mail-attachments
```

## Configuration

In your `config/telescope.php`, replace `MailWatcher` with `MailAttachmentWatcher`:

```php
'watchers' => [
    // Laravel\Telescope\Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),
    Monteiro\TelescopeMailAttachments\MailAttachmentWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),
    // ... other watchers
],
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=telescope-mail-attachments-config
```

### Config Options

| Option | Default | Description |
|--------|---------|-------------|
| `store_content` | `true` | Store base64-encoded attachment content. Set to `false` to only store metadata (filename, size, MIME type) and reduce storage usage. |

You can also set `TELESCOPE_MAIL_ATTACHMENTS_STORE_CONTENT=false` in your `.env`.

## Features

- Paperclip badge with attachment count on the mail index page
- Attachments table with download links on the mail preview page
- Download endpoint for individual attachments
- Optional content storage toggle to manage database size

## Screenshot

<img width="2370" height="1632" alt="Captura de ecrã 2026-02-25, às 16 11 14" src="https://github.com/user-attachments/assets/f72c5cac-bfca-4157-ab2b-417a73ae5475" />


## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
