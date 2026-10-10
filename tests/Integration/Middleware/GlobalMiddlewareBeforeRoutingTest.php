<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Cobre que middlewares globais executam mesmo sem rota (404/OPTIONS) — SPEC-040.
 *
 * Asserta apenas o wiring (o middleware executou / respondeu antes do roteamento),
 * não detalhes de mensagem HTTP (domínio do pivotphp/http — SPEC-093).
 */
class GlobalMiddlewareBeforeRoutingTest extends TestCase
{
    public function testGlobalMiddlewareRunsOnNotFound(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $ran = false;

        $app->use(new class (function () use (&$ran): void {
            $ran = true;
        }) implements MiddlewareInterface {
            /** @var \Closure */
            private \Closure $onRun;

            public function __construct(callable $onRun)
            {
                $this->onRun = \Closure::fromCallable($onRun);
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                ($this->onRun)();

                return $handler->handle($request);
            }
        });

        $response = $app->handle(new ServerRequest('GET', '/does-not-exist'));

        $this->assertTrue($ran);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGlobalMiddlewareCanRespondBeforeRouting(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $routeReached = false;

        $app->use(new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                if ($request->getMethod() === 'OPTIONS') {
                    return new Response(204);
                }

                return $handler->handle($request);
            }
        });

        $app->get('/api/status', function ($req, $res) use (&$routeReached) {
            $routeReached = true;

            return $res->json(['ok' => true]);
        });

        $response = $app->handle(new ServerRequest('OPTIONS', '/api/status'));

        $this->assertSame(204, $response->getStatusCode());
        $this->assertFalse($routeReached);
    }
}
