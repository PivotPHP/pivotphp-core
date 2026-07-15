<?php

declare(strict_types=1);

namespace PivotPHP\Core\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Event Dispatcher
 *
 * Simple and effective event handling for the microframework.
 * Provides basic event functionality without unnecessary complexity.
 * Implements PSR-14 EventDispatcherInterface.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * PSR-14 listener provider (optional)
     */
    private ?ListenerProviderInterface $listenerProvider = null;

    /**
     * Event listeners (simple string-based dispatch)
     */
    private array $listeners = [];

    public function __construct(?ListenerProviderInterface $listenerProvider = null)
    {
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * Register a listener for an event
     */
    public function listen(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch a PSR-14 event object.
     *
     * {@inheritdoc}
     */
    public function dispatch(object $event): object
    {
        if ($this->listenerProvider !== null) {
            $listeners = $this->listenerProvider->getListenersForEvent($event);

            foreach ($listeners as $listener) {
                if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                    break;
                }

                $listener($event);
            }
        }

        return $event;
    }

    /**
     * Fire a simple string-named event with optional data array.
     *
     * This method implements the lightweight string-based event system built into
     * this dispatcher (via `listen()`). It is intentionally separate from the
     * PSR-14 `dispatch(object): object` method, which operates on typed event objects
     * and requires a `ListenerProviderInterface`.
     *
     * Use `fire()` for simple internal hooks. Use `dispatch()` for PSR-14 interoperable
     * events that can be shared across packages.
     *
     * @param array<mixed> $data
     * @since 2.0.0
     */
    public function fire(string $event, array $data = []): bool
    {
        if (!isset($this->listeners[$event])) {
            return false;
        }

        foreach ($this->listeners[$event] as $listener) {
            $result = $listener($data);

            // If listener returns false, stop propagation
            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove listeners for an event
     */
    public function removeListeners(string $event): void
    {
        unset($this->listeners[$event]);
    }

    /**
     * Get all registered events
     *
     * @return array<string>
     */
    public function getEvents(): array
    {
        return array_keys($this->listeners);
    }

    /**
     * Get listener count for an event
     */
    public function getListenerCount(string $event): int
    {
        return count($this->listeners[$event] ?? []);
    }

    /**
     * Clear all listeners
     */
    public function clearAll(): void
    {
        $this->listeners = [];
    }
}
