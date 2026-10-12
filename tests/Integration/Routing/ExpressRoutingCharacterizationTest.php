<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Testes de caracterização da sintaxe ExpressJS e pipeline de resolução — SPEC-114 Fase 1.
 */
class ExpressRoutingCharacterizationTest extends TestCase
{
    private function createApp(): Application
    {
        $app = new Application(__DIR__ . '/../../..');
        $app->boot();
        return $app;
    }

    public function testExpressStyleHandlerAndJson(): void
    {
        $app = $this->createApp();
        $app->get('/api/ping', fn ($req, $res) => $res->json(['pong' => true]));

        $response = $app->handle(new ServerRequest('GET', '/api/ping'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(['pong' => true], $data);
    }

    public function testRouteParamsAccessInExpressRequest(): void
    {
        $app = $this->createApp();
        $app->get('/users/:id', fn ($req, $res) => $res->json(['userId' => $req->param('id')]));

        $response = $app->handle(new ServerRequest('GET', '/users/99'));

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(['userId' => '99'], $data);
    }

    public function testVariadicRouteMiddlewaresExecution(): void
    {
        $app = $this->createApp();
        $trace = [];

        $mw1 = function ($req, $res, $next) use (&$trace) {
            $trace[] = 'mw1';
            return $next($req, $res);
        };

        $mw2 = function ($req, $res, $next) use (&$trace) {
            $trace[] = 'mw2';
            return $next($req, $res);
        };

        $app->get(
            '/chained',
            function ($req, $res) use (&$trace) {
                $trace[] = 'handler';
                return $res->json(['trace' => $trace]);
            },
            [],
            $mw1,
            $mw2
        );

        $response = $app->handle(new ServerRequest('GET', '/chained'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['mw1', 'mw2', 'handler'], $trace);
    }

    public function testPsr15MiddlewareInRoute(): void
    {
        $app = $this->createApp();

        $psr15Mw = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response->withHeader('X-Custom-Header', 'PivotPHP');
            }
        };

        $app->use($psr15Mw);
        $app->get('/psr15', fn ($req, $res) => $res->json(['ok' => true]));

        $response = $app->handle(new ServerRequest('GET', '/psr15'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('PivotPHP', $response->getHeaderLine('X-Custom-Header'));
    }

    public function testExplicitOptionsRouteExecutesHandler(): void
    {
        $app = $this->createApp();
        $app->options('/custom-options', fn ($req, $res) => $res->status(200)->json(['custom' => 'options']));

        $response = $app->handle(new ServerRequest('OPTIONS', '/custom-options'));

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(['custom' => 'options'], $data);
    }

    public function testImplicitOptionsReturns204WithAllowHeader(): void
    {
        $app = $this->createApp();
        $app->get('/implicit-options', fn ($req, $res) => $res->json(['ok' => true]));
        $app->post('/implicit-options', fn ($req, $res) => $res->json(['ok' => true]));

        $response = $app->handle(new ServerRequest('OPTIONS', '/implicit-options'));

        $this->assertSame(204, $response->getStatusCode());
        $allow = $response->getHeaderLine('Allow');
        $this->assertStringContainsString('GET', $allow);
        $this->assertStringContainsString('POST', $allow);
        $this->assertStringContainsString('HEAD', $allow);
    }

    public function testMethodNotAllowedReturns405WithAllowHeader(): void
    {
        $app = $this->createApp();
        $app->get('/only-get', fn ($req, $res) => $res->json(['ok' => true]));

        $response = $app->handle(new ServerRequest('DELETE', '/only-get'));

        $this->assertSame(405, $response->getStatusCode());
        $allow = $response->getHeaderLine('Allow');
        $this->assertStringContainsString('GET', $allow);
        $this->assertStringContainsString('HEAD', $allow);
    }

    public function testNotFoundReturns404ForNonExistentRoute(): void
    {
        $app = $this->createApp();
        $app->get('/real-route', fn ($req, $res) => $res->json(['ok' => true]));

        $response = $app->handle(new ServerRequest('GET', '/ghost-route'));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGroupRoutesWithInheritedMiddlewares(): void
    {
        $app = $this->createApp();
        $order = [];

        $groupMw = function ($req, $res, $next) use (&$order) {
            $order[] = 'group_mw';
            return $next($req, $res);
        };

        $app->group('/v1', function ($group) use (&$order) {
            $group->get('/test', function ($req, $res) use (&$order) {
                $order[] = 'action';
                return $res->json(['order' => $order]);
            });
        }, [$groupMw]);

        $response = $app->handle(new ServerRequest('GET', '/v1/test'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['group_mw', 'action'], $order);
    }
}
