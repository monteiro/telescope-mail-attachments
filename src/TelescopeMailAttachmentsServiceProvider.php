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
     * Register the package route for attachment downloads.
     *
     * Telescope registers a catch-all `{view?}` route that matches any path.
     * We must ensure our more specific route takes priority by temporarily
     * removing the catch-all, registering our route, then re-adding it.
     */
    protected function registerRoutes(): void
    {
        $options = [
            'prefix' => config('telescope.path', 'telescope'),
            'middleware' => config('telescope.middleware', ['web']),
        ];

        if ($domain = config('telescope.domain')) {
            $options['domain'] = $domain;
        }

        // Find and remove Telescope's catch-all route, register ours, then re-add it
        $router = $this->app['router'];
        $routes = $router->getRoutes();
        $catchAll = $this->removeCatchAllRoute($routes);

        Route::group($options, function () {
            Route::get(
                '/telescope-api/mail/{telescopeEntryId}/attachments/{index}',
                [Http\Controllers\MailAttachmentController::class, 'show']
            );
        });

        // Re-add the catch-all route so it matches last
        if ($catchAll) {
            $routes->add($catchAll);
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
        }
    }

    /**
     * Remove Telescope's catch-all route from the route collection.
     */
    protected function removeCatchAllRoute(RouteCollection $routes): ?RoutingRoute
    {
        $telescopePath = config('telescope.path', 'telescope');
        $catchAll = null;

        foreach ($routes->getRoutes() as $route) {
            if ($route->uri() === $telescopePath.'/{view?}' && in_array('GET', $route->methods())) {
                $catchAll = $route;
                break;
            }
        }

        if (! $catchAll) {
            return null;
        }

        // Rebuild the route collection without the catch-all
        $newRoutes = new RouteCollection;

        foreach ($routes->getRoutes() as $route) {
            if ($route !== $catchAll) {
                $newRoutes->add($route);
            }
        }

        // Replace the router's route collection
        $this->app['router']->setRoutes($newRoutes);

        return $catchAll;
    }

    /**
     * Register the middleware that injects JavaScript into Telescope's pages.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->pushMiddlewareToGroup('telescope', Http\Middleware\InjectJavaScript::class);
    }
}
