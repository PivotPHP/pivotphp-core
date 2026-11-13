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
    private ?\Closure $hookManagerFactory = null;

    /**
     * Register hook services
     */
    public function register(): void
    {
        $this->hookManagerFactory = fn (): HookManager => new HookManager($this->app);

        // Register HookManager as singleton
        $this->app->singleton(HookManager::class, $this->hookManagerFactory);

        // Create alias for easier access
        $this->app->getContainer()->alias('hooks', HookManager::class);
    }

    /**
     * Boot hook services
     */
    public function boot(): void
    {
        // Sem HookManager em uso e sem listener PSR-14, os hooks do core não têm quem os ouça:
        // não instancia o HookManager nem o sistema de eventos só para disparar no vazio (SPEC-116).
        if ($this->hooksAreUnused()) {
            return;
        }

        // Register core hooks
        $this->registerCoreHooks();
    }

    private function hooksAreUnused(): bool
    {
        if ($this->hookManagerFactory === null || !$this->app->isEventSystemIdle()) {
            return false;
        }

        $container = $this->app->getContainer();

        return $container->isPendingFactory('hooks', $this->hookManagerFactory)
            && $container->isPendingFactory(HookManager::class, $this->hookManagerFactory);
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
