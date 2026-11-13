<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Events\EventDispatcher as EventsEventDispatcher;
use PivotPHP\Core\Events\ListenerProvider as EventsListenerProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Event Service Provider
 */
class EventServiceProvider extends ServiceProvider
{
    private ?\Closure $listenerProviderFactory = null;

    private ?\Closure $dispatcherFactory = null;

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->listenerProviderFactory = static fn (): ListenerProviderInterface => new EventsListenerProvider();

        $this->dispatcherFactory = function (): EventDispatcherInterface {
            /** @var ListenerProviderInterface $listenerProvider */
            $listenerProvider = $this->app->resolve(ListenerProviderInterface::class);
            return new EventsEventDispatcher($listenerProvider);
        };

        $this->app->singleton(ListenerProviderInterface::class, $this->listenerProviderFactory);
        $this->app->singleton(EventDispatcherInterface::class, $this->dispatcherFactory);

        // Aliases
        $this->app->alias('events', EventDispatcherInterface::class);
        $this->app->alias('listeners', ListenerProviderInterface::class);
    }

    /**
     * Indica que o sistema de eventos padrão ainda não foi usado: nem o dispatcher nem o listener
     * provider foram resolvidos ou substituídos, logo não existe listener registrado.
     *
     * Enquanto for verdadeiro, despachar um evento não tem efeito observável.
     */
    public function isIdle(): bool
    {
        if ($this->dispatcherFactory === null || $this->listenerProviderFactory === null) {
            return false;
        }

        $container = $this->app->getContainer();

        return $container->isPendingFactory('events', $this->dispatcherFactory)
            && $container->isPendingFactory(EventDispatcherInterface::class, $this->dispatcherFactory)
            && $container->isPendingFactory('listeners', $this->listenerProviderFactory)
            && $container->isPendingFactory(ListenerProviderInterface::class, $this->listenerProviderFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return [
            EventDispatcherInterface::class,
            ListenerProviderInterface::class,
            'events',
            'listeners'
        ];
    }
}
