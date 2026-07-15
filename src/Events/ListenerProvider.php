<?php

declare(strict_types=1);

namespace PivotPHP\Core\Events;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Simple listener provider implementation
 */
class ListenerProvider implements ListenerProviderInterface
{
    /**
     * @var array<string, array<callable>>
     */
    private array $listeners = [];

    /**
     * {@inheritdoc}
     *
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = get_class($event);

        // Return listeners for exact class match
        if (isset($this->listeners[$eventClass])) {
            yield from $this->listeners[$eventClass];
        }

        // Return listeners for parent classes and interfaces
        foreach (class_parents($event) as $parentClass) {
            if (isset($this->listeners[$parentClass])) {
                yield from $this->listeners[$parentClass];
            }
        }

        foreach (class_implements($event) as $interface) {
            if (isset($this->listeners[$interface])) {
                yield from $this->listeners[$interface];
            }
        }
    }

    /**
     * Add a listener for an event
     */
    public function addListener(string $eventType, callable $listener): void
    {
        if (!isset($this->listeners[$eventType])) {
            $this->listeners[$eventType] = [];
        }

        $this->listeners[$eventType][] = $listener;
    }

    /**
     * Remove all listeners for an event type
     */
    public function removeListeners(string $eventType): void
    {
        unset($this->listeners[$eventType]);
    }

    /**
     * Remove um listener específico de um tipo de evento
     */
    public function removeListener(string $eventType, callable $listener): void
    {
        if (!isset($this->listeners[$eventType])) {
            return;
        }
        foreach ($this->listeners[$eventType] as $i => $registered) {
            // Comparação de closures/callables
            if (
                $registered === $listener || (
                    is_object($registered)
                    && is_object($listener)
                    && $registered == $listener
                )
            ) {
                unset($this->listeners[$eventType][$i]);
            }
        }
        // Reindexa o array para evitar buracos
        $this->listeners[$eventType] = array_values($this->listeners[$eventType]);
        // Remove o tipo se não houver mais listeners
        if (empty($this->listeners[$eventType])) {
            unset($this->listeners[$eventType]);
        }
    }

    /**
     * Check if there are listeners for an event type
     */
    public function hasListeners(string $eventType): bool
    {
        return isset($this->listeners[$eventType]) && !empty($this->listeners[$eventType]);
    }

    /**
     * Get all registered event types
     *
     * @return array<string>
     */
    public function getEventTypes(): array
    {
        return array_keys($this->listeners);
    }
}
