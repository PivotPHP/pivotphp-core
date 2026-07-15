<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Simple PSR-14 compliant event dispatcher implementation
 *
 * @deprecated v2.1.0 Use \PivotPHP\Core\Events\EventDispatcher instead.
 */
class EventDispatcher implements EventDispatcherInterface
{
    private ListenerProviderInterface $listenerProvider;

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        trigger_error(
            'PivotPHP\\Core\\Providers\\EventDispatcher is deprecated. Use PivotPHP\\Core\\Events\\EventDispatcher instead.',
            E_USER_DEPRECATED
        );
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event): object
    {
        $listeners = $this->listenerProvider->getListenersForEvent($event);

        foreach ($listeners as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }
}
