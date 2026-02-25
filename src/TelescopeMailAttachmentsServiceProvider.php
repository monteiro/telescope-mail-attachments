<?php

namespace Monteiro\TelescopeMailAttachments;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TelescopeMailAttachmentsServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/telescope-mail-attachments.php',
            'telescope-mail-attachments'
        );
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/telescope-mail-attachments.php' => config_path('telescope-mail-attachments.php'),
            ], 'telescope-mail-attachments-config');
        }

        if (! config('telescope.enabled')) {
            return;
        }

        $this->registerMiddleware();

        $this->app->booted(function () {
            $this->registerRoutes();
        });
    }

    /**
     * Register the package routes.
     *
     * Telescope registers a catch-all `{view?}` route and mail routes that
     * reference the original MailWatcher. We remove those and re-register
     * them pointing to our controllers so the watcher status is correct.
     */
    protected function registerRoutes(): void
    {
        $options = [
            'prefix' => config('telescope.path', 'telescope'),
            'middleware' => 'telescope',
        ];

        if ($domain = config('telescope.domain')) {
            $options['domain'] = $domain;
        }

        $telescopePath = config('telescope.path', 'telescope');
        $catchAll = $this->removeTelescopeRoutes($telescopePath);

        Route::group($options, function () {
            Route::post('/telescope-api/mail', [Http\Controllers\MailController::class, 'index']);
            Route::get('/telescope-api/mail/{telescopeEntryId}', [Http\Controllers\MailController::class, 'show']);
            Route::get(
                '/telescope-api/mail/{telescopeEntryId}/attachments/{index}',
                [Http\Controllers\MailAttachmentController::class, 'show']
            );
        });

        // Re-add the catch-all route so it matches last
        if ($catchAll) {
            $currentRoutes = $this->app['router']->getRoutes();
            $currentRoutes->add($catchAll);
            $currentRoutes->refreshNameLookups();
            $currentRoutes->refreshActionLookups();
        }
    }

    /**
     * Remove Telescope's mail routes and catch-all route from the route collection.
     */
    protected function removeTelescopeRoutes(string $telescopePath): ?RoutingRoute
    {
        $routes = $this->app['router']->getRoutes();
        $catchAll = null;

        $mailUris = [
            $telescopePath.'/telescope-api/mail',
            $telescopePath.'/telescope-api/mail/{telescopeEntryId}',
        ];

        $routesToRemove = [];

        foreach ($routes->getRoutes() as $route) {
            if ($route->uri() === $telescopePath.'/{view?}' && in_array('GET', $route->methods())) {
                $catchAll = $route;
                $routesToRemove[] = $route;
            } elseif (in_array($route->uri(), $mailUris)) {
                $routesToRemove[] = $route;
            }
        }

        if (empty($routesToRemove)) {
            return null;
        }

        $newRoutes = new RouteCollection;

        foreach ($routes->getRoutes() as $route) {
            if (! in_array($route, $routesToRemove, true)) {
                $newRoutes->add($route);
            }
        }

        $this->app['router']->setRoutes($newRoutes);

        return $catchAll;
    }

    /**
     * Register the middleware that injects JavaScript into Telescope's pages.
     */
    protected function registerMiddleware(): void
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app['router'];

        $router->pushMiddlewareToGroup('telescope', Http\Middleware\InjectJavaScript::class);
    }
}
