<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;

/**
 * Cobre que middlewares globais executam mesmo sem rota (404/OPTIONS) — SPEC-040.
 */
class GlobalMiddlewareBeforeRoutingTest extends TestCase
{
    public function testGlobalMiddlewareRunsOnNotFound(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->use(
            function ($req, $res, $next) {
                $res->header('X-Global-Mw', 'ran');

                return $next($req, $res);
            }
        );

        $response = $app->handle(new Request('GET', '/does-not-exist', '/does-not-exist'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('ran', $response->getHeaderLine('X-Global-Mw'));
    }

    public function testGlobalMiddlewareCanRespondBeforeRouting(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->use(
            function ($req, $res, $next) {
                if ($req->getMethod() === 'OPTIONS') {
                    return $res->status(204)->json(['ok' => true]);
                }

                return $next($req, $res);
            }
        );

        $response = $app->handle(new Request('OPTIONS', '/api/status', '/api/status'));

        $this->assertSame(204, $response->getStatusCode());
    }
}
