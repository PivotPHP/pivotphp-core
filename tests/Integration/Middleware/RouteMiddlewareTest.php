<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Routing\Router\Router;

/**
 * Cobre que middlewares de rota/grupo são executados (SPEC-038).
 */
class RouteMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        Router::clear();
    }

    protected function tearDown(): void
    {
        Router::clear();
    }

    public function testRouteMiddlewareExecutesAndBlocksHandler(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        Router::get(
            '/protected',
            function ($req, $res) {
                return $res->json(['secret' => true]);
            },
            [],
            function ($req, $res, $next) {
                return $res->status(403)->json(['error' => 'forbidden']);
            }
        );

        $response = $app->handle(new Request('GET', '/protected', '/protected'));

        $this->assertSame(403, $response->getStatusCode());

        $body = json_decode($response->getBodyAsString(), true);
        $this->assertSame('forbidden', $body['error']);
    }

    public function testGroupMiddlewareExecutes(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        Router::group(
            '/admin',
            function () {
                Router::get(
                    '/painel',
                    function ($req, $res) {
                        return $res->json(['ok' => true]);
                    }
                );
            },
            [
                function ($req, $res, $next) {
                    return $res->status(401)->json(['error' => 'unauthorized']);
                },
            ]
        );

        $response = $app->handle(new Request('GET', '/admin/painel', '/admin/painel'));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRouteMiddlewarePassesThroughWhenNextCalled(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        Router::get(
            '/allowed',
            function ($req, $res) {
                return $res->json(['ok' => true]);
            },
            [],
            function ($req, $res, $next) {
                return $next($req, $res);
            }
        );

        $response = $app->handle(new Request('GET', '/allowed', '/allowed'));

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getBodyAsString(), true);
        $this->assertTrue($body['ok']);
    }
}
