<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Integration tests for the PSR-15 middleware pipeline.
 *
 * @group integration
 * @group middleware
 */
class MiddlewareStackIntegrationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application();
    }

    /**
     * @param callable(ServerRequestInterface, RequestHandlerInterface): ResponseInterface $process
     */
    private function middleware(callable $process): MiddlewareInterface
    {
        return new class ($process) implements MiddlewareInterface {
            /** @var callable */
            private $process;

            public function __construct(callable $process)
            {
                $this->process = $process;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return ($this->process)($request, $handler);
            }
        };
    }

    public function testMiddlewareExecutionOrder(): void
    {
        $log = [];

        foreach (['auth', 'logging', 'cors'] as $name) {
            $this->app->use($this->middleware(function ($request, $handler) use ($name, &$log) {
                $log[] = "{$name}_start";
                $response = $handler->handle($request);
                $log[] = "{$name}_end";
                return $response;
            }));
        }

        $this->app->get('/api/test', function ($req, $res) use (&$log) {
            $log[] = 'route_handler';
            return $res->json(['status' => 'success']);
        });

        $this->app->boot();
        $response = $this->app->handle(new ServerRequest('GET', '/api/test'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            ['auth_start', 'logging_start', 'cors_start', 'route_handler', 'cors_end', 'logging_end', 'auth_end'],
            $log
        );
    }

    public function testMiddlewareErrorHandling(): void
    {
        $handled = false;

        $this->app->use($this->middleware(function ($request, $handler) use (&$handled) {
            try {
                return $handler->handle($request);
            } catch (\Exception $e) {
                $handled = true;
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => $e->getMessage()]));
            }
        }));

        $this->app->use($this->middleware(static function ($request, $handler): ResponseInterface {
            throw new \Exception('Middleware error');
        }));

        $this->app->get('/error', fn ($req, $res) => $res->json(['ok' => true]));

        $this->app->boot();
        $response = $this->app->handle(new ServerRequest('GET', '/error'));

        $this->assertTrue($handled);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'Middleware error'], json_decode((string) $response->getBody(), true));
    }

    public function testRequestAndResponseModification(): void
    {
        // Middleware that modifies the request (PSR-7 immutable, returns new).
        $this->app->use($this->middleware(static function ($request, $handler): ResponseInterface {
            $request = $request
                ->withAttribute('user_id', 123)
                ->withAttribute('authenticated', true);

            return $handler->handle($request);
        }));

        // Middleware that modifies the response.
        $this->app->use($this->middleware(static function ($request, $handler): ResponseInterface {
            return $handler->handle($request)
                ->withHeader('X-Custom-Header', 'middleware-added')
                ->withHeader('X-Request-ID', uniqid());
        }));

        $this->app->get('/modify', function ($req, $res) {
            $psr7 = $req->psr7();

            return $res->json([
                'user_id' => $psr7->getAttribute('user_id'),
                'authenticated' => $psr7->getAttribute('authenticated'),
            ]);
        });

        $this->app->boot();
        $response = $this->app->handle(new ServerRequest('GET', '/modify'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware-added', $response->getHeaderLine('X-Custom-Header'));
        $this->assertNotEmpty($response->getHeaderLine('X-Request-ID'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(123, $body['user_id']);
        $this->assertTrue($body['authenticated']);
    }

    public function testMiddlewareEarlyTermination(): void
    {
        $routeCalled = false;

        $this->app->use($this->middleware(static function ($request, $handler): ResponseInterface {
            return new Response(403, ['Content-Type' => 'application/json'], '{"error":"forbidden"}');
        }));

        $this->app->get('/secured', function ($req, $res) use (&$routeCalled) {
            $routeCalled = true;
            return $res->json(['ok' => true]);
        });

        $this->app->boot();
        $response = $this->app->handle(new ServerRequest('GET', '/secured'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($routeCalled);
    }

    public function testMiddlewareAcrossHttpMethods(): void
    {
        $seen = [];

        $this->app->use($this->middleware(function ($request, $handler) use (&$seen) {
            $seen[] = $request->getMethod();
            return $handler->handle($request);
        }));

        $this->app->get('/m', fn ($req, $res) => $res->json(['m' => 'get']));
        $this->app->post('/m', fn ($req, $res) => $res->json(['m' => 'post']));

        $this->app->boot();
        $this->app->handle(new ServerRequest('GET', '/m'));
        $this->app->handle(new ServerRequest('POST', '/m'));

        $this->assertSame(['GET', 'POST'], $seen);
    }

    public function testEmptyMiddlewareStack(): void
    {
        $this->app->get('/health', fn ($req, $res) => $res->json(['status' => 'ok']));

        $this->app->boot();
        $response = $this->app->handle(new ServerRequest('GET', '/health'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
