<?php

use Illuminate\Support\Facades\Mail;
use Laravel\Telescope\Http\Middleware\Authorize;
use Monteiro\TelescopeMailAttachments\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->withoutMiddleware(Authorize::class);
});

it('downloads an attachment', function () {
    Mail::raw('Telescope is amazing!', function ($message) {
        $message->from('from@laravel.com')
            ->to('to@laravel.com')
            ->subject('Check this out!')
            ->attachData('attachment content', 'document.pdf', [
                'mime' => 'application/pdf',
            ]);
    });

    $entry = $this->loadTelescopeEntries()->first();

    $response = $this->get('/telescope/telescope-api/mail/'.$entry->uuid.'/attachments/0');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Length', (string) strlen('attachment content'));
    expect($response->content())->toBe('attachment content');
});

it('returns 404 for invalid attachment index', function () {
    Mail::raw('Telescope is amazing!', function ($message) {
        $message->from('from@laravel.com')
            ->to('to@laravel.com')
            ->subject('Check this out!')
            ->attachData('attachment content', 'document.pdf', [
                'mime' => 'application/pdf',
            ]);
    });

    $entry = $this->loadTelescopeEntries()->first();

    $response = $this->get('/telescope/telescope-api/mail/'.$entry->uuid.'/attachments/5');

    $response->assertNotFound();
});

it('returns 404 for entry without attachments', function () {
    Mail::raw('Telescope is amazing!', function ($message) {
        $message->from('from@laravel.com')
            ->to('to@laravel.com')
            ->subject('Check this out!');
    });

    $entry = $this->loadTelescopeEntries()->first();

    $response = $this->get('/telescope/telescope-api/mail/'.$entry->uuid.'/attachments/0');

    $response->assertNotFound();
});

it('returns 404 when store_content is disabled', function () {
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

    $response = $this->get('/telescope/telescope-api/mail/'.$entry->uuid.'/attachments/0');

    $response->assertNotFound();
});

it('handles special characters in filenames', function () {
    Mail::raw('Telescope is amazing!', function ($message) {
        $message->from('from@laravel.com')
            ->to('to@laravel.com')
            ->subject('Check this out!')
            ->attachData('attachment content', 'my report (final).pdf', [
                'mime' => 'application/pdf',
            ]);
    });

    $entry = $this->loadTelescopeEntries()->first();

    $response = $this->get('/telescope/telescope-api/mail/'.$entry->uuid.'/attachments/0');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('my report (final).pdf');
});
