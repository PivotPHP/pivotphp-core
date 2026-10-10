<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Middleware;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

/**
 * Callable middlewares `fn ($req, $res, $next)`: `$next` forwards a modified request and runs
 * the rest of the pipeline exactly once.
 */
class CallableMiddlewareNextTest extends TestCase
{
    private Application $app;
    private int $routeHits = 0;

    protected function setUp(): void
    {
        $this->app = new Application(__DIR__ . '/../../..');
        $this->app->get(
            '/t',
            function ($req, $res) {
                $this->routeHits++;

                return $res->json(['tenant' => $req->psr7()->getAttribute('tenant')]);
            }
        );
    }

    public function testNextForwardsTheModifiedRequest(): void
    {
        $this->app->use(fn ($req, $res, $next) => $next($req->withAttribute('tenant', 'acme')));

        $response = $this->app->handle(new ServerRequest('GET', '/t'));

        $this->assertSame('{"tenant":"acme"}', (string) $response->getBody());
    }

    public function testRouteRunsOnceWhenMiddlewareDoesNotReturnNext(): void
    {
        $this->app->use(
            function ($req, $res, $next): void {
                $next();
            }
        );

        $response = $this->app->handle(new ServerRequest('GET', '/t'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $this->routeHits);
    }

    public function testShortCircuitSkipsTheRoute(): void
    {
        $this->app->use(fn ($req, $res, $next) => $res->status(403)->json(['error' => 'blocked']));

        $response = $this->app->handle(new ServerRequest('GET', '/t'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(0, $this->routeHits);
    }
}
