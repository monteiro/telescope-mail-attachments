<?php

namespace Monteiro\TelescopeMailAttachments\Tests;

use Monteiro\TelescopeMailAttachments\TelescopeMailAttachmentsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Telescope::flushEntries();
        Telescope::$afterStoringHooks = [];
    }

    protected function tearDown(): void
    {
        Telescope::flushEntries();
        Telescope::$afterStoringHooks = [];

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            TelescopeServiceProvider::class,
            TelescopeMailAttachmentsServiceProvider::class,
        ];
    }

    protected function resolveApplicationCore($app)
    {
        parent::resolveApplicationCore($app);

        $app->detectEnvironment(function () {
            return 'self-testing';
        });
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../vendor/laravel/telescope/database/migrations'
        );
    }

    protected function defineEnvironment($app)
    {
        $app->make('config')->set([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'database.default' => 'testbench',
            'mail.driver' => 'array',
            'telescope.enabled' => true,
            'telescope.storage.database.connection' => 'testbench',
            'telescope.watchers' => [
                \Monteiro\TelescopeMailAttachments\MailAttachmentWatcher::class => true,
            ],
            'database.connections.testbench' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        $app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give('testbench');
    }

    protected function loadTelescopeEntries()
    {
        Telescope::store(app(EntriesRepository::class));

        return EntryModel::all();
    }
}
