<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Providers\ServiceProvider;
use PivotPHP\Core\Support\HookManager;

/**
 * Hook Service Provider
 *
 * Provides hook management capabilities for extensibility
 */
class HookServiceProvider extends ServiceProvider
{
    /**
     * Register hook services
     */
    public function register(): void
    {
        // Register HookManager as singleton
        $this->app->singleton(
            HookManager::class,
            function () {
                return new HookManager($this->app);
            }
        );

        // Create alias for easier access
        $this->app->getContainer()->alias('hooks', HookManager::class);
    }

    /**
     * Boot hook services
     */
    public function boot(): void
    {
        // Register core hooks
        $this->registerCoreHooks();
    }

    /**
     * Register core application hooks
     */
    protected function registerCoreHooks(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->app->make(HookManager::class);

        // The only hooks fired by the core itself; applications may fire their own
        // with doAction()/applyFilter().
        $hooks->doAction('app.registered');
        $hooks->doAction('app.booting');
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            HookManager::class,
            'hooks'
        ];
    }
}
