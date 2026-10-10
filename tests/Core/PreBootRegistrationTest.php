<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Events\RequestReceived;

/**
 * Hooks and event listeners registered right after construction (before boot) must work.
 */
class PreBootRegistrationTest extends TestCase
{
    public function testHooksAndListenersRegisteredBeforeBootAreFired(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $fired = [];

        $app->addAction(
            'app.booting',
            function () use (&$fired): void {
                $fired[] = 'app.booting';
            }
        );
        $app->on(
            RequestReceived::class,
            function () use (&$fired): void {
                $fired[] = 'request.received';
            }
        );
        $app->get('/', fn ($req, $res) => $res->json([]));

        $app->handle(new ServerRequest('GET', '/'));

        $this->assertSame(['app.booting', 'request.received'], $fired);
    }

    public function testApplicationFiltersCanBeAppliedBeforeBoot(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $app->addFilter('price', fn (int $value): int => $value * 2);

        $this->assertSame(20, $app->applyFilter('price', 10));
    }
}
