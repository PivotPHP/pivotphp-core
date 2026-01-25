<?php

declare(strict_types=1);

namespace PivotPHP\Core\Support;

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Events\Hook;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Hook Manager for flexible extension points
 *
 * Provides WordPress-style hooks (actions and filters) for easy
 * extensibility without requiring deep knowledge of PSR-14 events.
 */
class HookManager
{
    /**
     * Application instance
     */
    protected Application $app;

    /**
     * Event dispatcher
     */
    protected EventDispatcherInterface $dispatcher;

    /**
     * Registered listeners by hook name
     *
     * @var array<string, array<array{callback: callable, priority: int}>>
     */
    protected array $listeners = [];

    /**
     * Referências dos listeners PSR-14 registrados por hook
     * @var array<string, callable|null>
     */
    protected array $psrListeners = [];

    /**
     * Create hook manager
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app->make(EventDispatcherInterface::class);
        $this->dispatcher = $dispatcher;
    }

    /**
     * Add action hook (performs actions, doesn't modify data)
     *
     * @param callable $callback
     */
    public function addAction(
        string $hook,
        callable $callback,
        int $priority = 10
    ): void {
        $this->addListener($hook, $callback, $priority);
    }

    /**
     * Add filter hook (modifies and returns data)
     *
     * @param callable $callback
     */
    public function addFilter(
        string $hook,
        callable $callback,
        int $priority = 10
    ): void {
        $this->addListener($hook, $callback, $priority);
    }

    /**
     * Execute action hook
     *
     * @param array<string, mixed> $context
     */
    public function doAction(string $hook, array $context = []): void
    {
        $event = Hook::action($hook, $context);
        $this->dispatcher->dispatch($event);
    }

    /**
     * Apply filter hook (returns potentially modified data)
     *
     * @param mixed $data
     * @param array<string, mixed> $context
     * @return mixed
     */
    public function applyFilter(
        string $hook,
        mixed $data,
        array $context = []
    ): mixed {
        $event = Hook::filter($hook, $data, $context);
        $this->dispatcher->dispatch($event);
        return $event->getData();
    }

    /**
     * Remove action/filter
     *
     * @param callable $callback
     */
    public function removeHook(string $hook, callable $callback): bool
    {
        if (!isset($this->listeners[$hook])) {
            return false;
        }

        $this->listeners[$hook] = array_filter(
            $this->listeners[$hook],
            fn($listener) => is_array($listener) && $listener['callback'] !== $callback
        );

        // Re-register with event system
        $this->reRegisterListeners($hook);

        return true;
    }

    /**
     * Check if hook has listeners
     */
    public function hasListeners(string $hook): bool
    {
        return !empty($this->listeners[$hook]);
    }

    /**
     * Get listener count for hook
     */
    public function getListenerCount(string $hook): int
    {
        return count($this->listeners[$hook] ?? []);
    }

    /**
     * Get all registered hooks
     *
     * @return array<string>
     */
    public function getRegisteredHooks(): array
    {
        return array_keys($this->listeners);
    }

    /**
     * Add listener with priority
     *
     * @param callable $callback
     */
    protected function addListener(
        string $hook,
        callable $callback,
        int $priority
    ): void {
        if (!isset($this->listeners[$hook])) {
            $this->listeners[$hook] = [];
        }

        $this->listeners[$hook][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Sort by priority
        usort(
            $this->listeners[$hook],
            function ($a, $b) {
                return $a['priority'] <=> $b['priority'];
            }
        );

        // Register with PSR-14 event system
        $this->registerWithEventSystem($hook);
    }

    /**
     * Register hook with PSR-14 event system
     */
    protected function registerWithEventSystem(string $hook): void
    {
        /** @var \PivotPHP\Core\Providers\ListenerProvider $listenerProvider */
        $listenerProvider = $this->app->make('listeners');

        // Remove listener antigo, se existir
        if (isset($this->psrListeners[$hook]) && $this->psrListeners[$hook] !== null) {
            $listenerProvider->removeListener(Hook::class, $this->psrListeners[$hook]);
        }

        // Cria e registra novo listener
        $closure = function (Hook $event) use ($hook) {
            if ($event->getName() !== $hook) {
                return;
            }
            if (!isset($this->listeners[$hook])) {
                return;
            }
            foreach ($this->listeners[$hook] as $listenerData) {
                if ($event->isPropagationStopped()) {
                    break;
                }
                if (!is_array($listenerData)) {
                    continue;
                }
                $callback = $listenerData['callback'];
                if ($event->getData() !== null) {
                    $result = $callback($event->getData(), $event->getContext());
                    if ($result !== null) {
                        $event->setData($result);
                    }
                } else {
                    $callback($event->getContext());
                }
            }
        };
        $listenerProvider->addListener(Hook::class, $closure);
        $this->psrListeners[$hook] = $closure;
    }

    /**
     * Re-register listeners for a hook (used when removing listeners)
     */
    protected function reRegisterListeners(string $hook): void
    {
        $this->registerWithEventSystem($hook);
    }

    /**
     * Get hook statistics
     *
     * @return array{hooks: int, listeners: int, by_hook: array<string, int>}
     */
    public function getStats(): array
    {
        $totalListeners = 0;
        $byHook = [];

        foreach ($this->listeners as $hook => $listeners) {
            $count = count($listeners);
            $byHook[$hook] = $count;
            $totalListeners += $count;
        }

        return [
            'hooks' => count($this->listeners),
            'listeners' => $totalListeners,
            'by_hook' => $byHook
        ];
    }
}
