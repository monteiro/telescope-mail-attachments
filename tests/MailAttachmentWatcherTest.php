<?php

use Illuminate\Support\Facades\Mail;
use Laravel\Telescope\EntryType;
use Monteiro\TelescopeMailAttachments\Tests\TestCase;

uses(TestCase::class);

it('records mail with attachments', function () {
    Mail::raw('Telescope is amazing!', function ($message) {
        $message->from('from@laravel.com')
            ->to('to@laravel.com')
            ->subject('Check this out!')
            ->attachData('attachment content', 'document.pdf', [
                'mime' => 'application/pdf',
            ]);
    });

    $entry = $this->loadTelescopeEntries()->first();

    expect($entry->type)->toBe(EntryType::MAIL);
    expect($entry->content['attachments'])->toHaveCount(1);
    expect($entry->content['attachments'][0]['filename'])->toBe('document.pdf');
    expect($entry->content['attachments'][0]['mime_type'])->toBe('application/pdf');
    expect($entry->content['attachments'][0]['size'])->toBe(strlen('attachment content'));
    expect($entry->content['attachments'][0]['content'])->toBe(base64_encode('attachment content'));
});

it('records mail without attachments', function () {
    Mail::raw('Telescope is amazing!', function ($message) {
        $message->from('from@laravel.com')
            ->to('to@laravel.com')
            ->subject('Check this out!');
    });

    $entry = $this->loadTelescopeEntries()->first();

    expect($entry->type)->toBe(EntryType::MAIL);
    expect($entry->content['attachments'])->toBe([]);
});

it('records multiple attachments', function () {
    Mail::raw('Telescope is amazing!', function ($message) {
        $message->from('from@laravel.com')
            ->to('to@laravel.com')
            ->subject('Check this out!')
            ->attachData('pdf content', 'document.pdf', ['mime' => 'application/pdf'])
            ->attachData('image content', 'photo.jpg', ['mime' => 'image/jpeg']);
    });

    $entry = $this->loadTelescopeEntries()->first();

    expect($entry->content['attachments'])->toHaveCount(2);
    expect($entry->content['attachments'][0]['filename'])->toBe('document.pdf');
    expect($entry->content['attachments'][1]['filename'])->toBe('photo.jpg');
});

it('omits content when store_content is disabled', function () {
    config()->set('telescope-mail-attachments.store_content', false);

    Mail::raw('Telescope is amazing!', function ($message) {
        $message->from('from@laravel.com')
            ->to('to@laravel.com')
            ->subject('Check this out!')
            ->attachData('attachment content', 'document.pdf', [
                'mime' => 'application/pdf',
            ]);
    });

    $entry = $this->loadTelescopeEntries()->first();

    expect($entry->content['attachments'])->toHaveCount(1);
    expect($entry->content['attachments'][0]['filename'])->toBe('document.pdf');
    expect($entry->content['attachments'][0]['size'])->toBe(strlen('attachment content'));
    expect($entry->content['attachments'][0])->not->toHaveKey('content');
});
