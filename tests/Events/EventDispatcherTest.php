<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Events;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Events\EventDispatcher;
use PivotPHP\Core\Events\ListenerProvider;

/**
 * EventDispatcher implements two intentionally separate mechanisms:
 * - dispatch(object): object — PSR-14, backed by a ListenerProviderInterface.
 * - fire(string, array): bool / listen(string, callable) — lightweight
 *   string-based events, unrelated to the PSR-14 path and to HookManager
 *   (which has its own listener bookkeeping wired directly into a
 *   ListenerProvider). These tests cover both independently.
 */
class EventDispatcherTest extends TestCase
{
    public function testDispatchInvokesListenerProviderListeners(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $received = null;
        $provider->addListener(\stdClass::class, function ($event) use (&$received) {
            $received = $event;
        });

        $event = new \stdClass();
        $result = $dispatcher->dispatch($event);

        $this->assertSame($event, $received);
        $this->assertSame($event, $result);
    }

    public function testDispatchWithoutListenerProviderIsNoop(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new \stdClass();

        $result = $dispatcher->dispatch($event);

        $this->assertSame($event, $result);
    }

    public function testFireInvokesStringBasedListeners(): void
    {
        $dispatcher = new EventDispatcher();

        $received = null;
        $dispatcher->listen('user.created', function ($data) use (&$received) {
            $received = $data;
        });

        $result = $dispatcher->fire('user.created', ['id' => 1]);

        $this->assertTrue($result);
        $this->assertSame(['id' => 1], $received);
    }

    public function testFireReturnsFalseWhenNoListenersRegistered(): void
    {
        $dispatcher = new EventDispatcher();

        $this->assertFalse($dispatcher->fire('nothing.registered'));
    }

    public function testFireStopsPropagationWhenListenerReturnsFalse(): void
    {
        $dispatcher = new EventDispatcher();

        $calls = [];
        $dispatcher->listen('pipeline', function () use (&$calls) {
            $calls[] = 'first';
            return false;
        });
        $dispatcher->listen('pipeline', function () use (&$calls) {
            $calls[] = 'second';
        });

        $result = $dispatcher->fire('pipeline');

        $this->assertFalse($result);
        $this->assertSame(['first'], $calls);
    }

    public function testFireAndDispatchAreIndependentListenerSets(): void
    {
        // Registering a PSR-14 listener must not make fire() see it, and
        // vice versa — they are deliberately separate systems.
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $psr14Called = false;
        $provider->addListener(\stdClass::class, function () use (&$psr14Called) {
            $psr14Called = true;
        });

        $dispatcher->fire('stdClass');

        $this->assertFalse($psr14Called);
        $this->assertSame(0, $dispatcher->getListenerCount('stdClass'));
    }

    public function testRemoveListenersClearsOnlyGivenEvent(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->listen('a', fn() => true);
        $dispatcher->listen('b', fn() => true);

        $dispatcher->removeListeners('a');

        $this->assertSame(0, $dispatcher->getListenerCount('a'));
        $this->assertSame(1, $dispatcher->getListenerCount('b'));
    }

    public function testGetEventsListsRegisteredEventNames(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->listen('a', fn() => true);
        $dispatcher->listen('b', fn() => true);

        $this->assertSame(['a', 'b'], $dispatcher->getEvents());
    }

    public function testClearAllRemovesEveryListener(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->listen('a', fn() => true);
        $dispatcher->listen('b', fn() => true);

        $dispatcher->clearAll();

        $this->assertSame([], $dispatcher->getEvents());
    }
}
