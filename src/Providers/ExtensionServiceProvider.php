<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Providers\ServiceProvider;
use PivotPHP\Core\Providers\ExtensionManager;

/**
 * Extension Service Provider
 *
 * Provides extension management capabilities to the application
 */
class ExtensionServiceProvider extends ServiceProvider
{
    private ?\Closure $extensionManagerFactory = null;

    /**
     * Register extension services
     */
    public function register(): void
    {
        $this->extensionManagerFactory = fn (): ExtensionManager => new ExtensionManager($this->app);

        // Register ExtensionManager as singleton
        $this->app->singleton(ExtensionManager::class, $this->extensionManagerFactory);

        // Create alias for easier access
        $this->app->getContainer()->alias('extensions', ExtensionManager::class);
    }

    /**
     * Boot extension services
     */
    public function boot(): void
    {
        $container = $this->app->getContainer();

        // Nenhuma extensão vem da configuração; com o ExtensionManager padrão ainda não resolvido
        // não há o que carregar, então ele não é instanciado no boot (SPEC-116).
        if (
            $this->extensionManagerFactory !== null
            && $container->isPendingFactory('extensions', $this->extensionManagerFactory)
            && $container->isPendingFactory(ExtensionManager::class, $this->extensionManagerFactory)
        ) {
            return;
        }

        /** @var ExtensionManager $extensionManager */
        $extensionManager = $this->app->make(ExtensionManager::class);

        // Load extensions from configuration
        $extensionManager->loadFromConfig([]);
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            ExtensionManager::class,
            'extensions'
        ];
    }
}
