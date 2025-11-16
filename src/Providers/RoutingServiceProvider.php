<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Routing\Router\Router;

/**
 * Routing Service Provider
 *
 * Registers the modular routing system from pivotphp/core-routing package.
 * The Router from core-routing uses static methods, so no singleton is needed.
 *
 * @package PivotPHP\Core\Providers
 * @since 2.0.0
 */
class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Register routing services into the container
     */
    public function register(): void
    {
        // The Router from core-routing is static, so we just register it as an alias
        $this->app->bind(
            'router',
            function () {
                return Router::class;
            }
        );
    }

    /**
     * Bootstrap routing services
     */
    public function boot(): void
    {
        // Router is ready for route registration
        // The modular routing system from core-routing is now available
        // via PivotPHP\Core\Routing\Router (aliased in src/aliases.php)
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            'router',
        ];
    }
}
