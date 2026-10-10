<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Nyholm\Psr7\ServerRequest;
use PivotPHP\Routing\Router\Router;

/**
 * Cobre que middlewares de rota/grupo são executados (SPEC-038).
 */
class RouteMiddlewareTest extends TestCase
{
    public function testRouteMiddlewareExecutesAndBlocksHandler(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->get(
            '/protected',
            function ($req, $res) {
                return $res->json(['secret' => true]);
            },
            [],
            function ($req, $res, $next) {
                return $res->status(403)->json(['error' => 'forbidden']);
            }
        );

        $response = $app->handle(new ServerRequest('GET', '/protected'));

        $this->assertSame(403, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('forbidden', $body['error']);
    }

    public function testGroupMiddlewareExecutes(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->group(
            '/admin',
            function () use ($app) {
                $app->get(
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

        $response = $app->handle(new ServerRequest('GET', '/admin/painel'));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRouteMiddlewarePassesThroughWhenNextCalled(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->get(
            '/allowed',
            function ($req, $res) {
                return $res->json(['ok' => true]);
            },
            [],
            function ($req, $res, $next) {
                return $next($req, $res);
            }
        );

        $response = $app->handle(new ServerRequest('GET', '/allowed'));

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertTrue($body['ok']);
    }
}
