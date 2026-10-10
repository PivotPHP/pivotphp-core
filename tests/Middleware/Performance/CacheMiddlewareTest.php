<?php

namespace PivotPHP\Core\Tests\Middleware\Performance;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Performance\CacheMiddleware;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CacheMiddlewareTest extends TestCase
{
    public function testCacheMiddlewareBasicFunctionality(): void
    {
        $middleware = new CacheMiddleware();

        $request = new ServerRequest('GET', '/');
        $response = new Response(200);

        $handler = new class ($response) implements RequestHandlerInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $result = $middleware->process($request, $handler);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testCacheMiddlewareWithCacheHeaders(): void
    {
        $middleware = new CacheMiddleware(3600);

        $request = new ServerRequest('GET', '/');
        $response = new Response(200);

        $handler = new class ($response) implements RequestHandlerInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $result = $middleware->process($request, $handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('max-age=', $result->getHeaderLine('Cache-Control'));
    }

    public function testCacheMiddlewareWorksWithPostRequests(): void
    {
        $middleware = new CacheMiddleware();

        $request = new ServerRequest('POST', '/');
        $response = new Response(200);

        $handler = new class ($response) implements RequestHandlerInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $result = $middleware->process($request, $handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('max-age=300', $result->getHeaderLine('Cache-Control'));
    }

    public function testCacheMiddlewareWithCustomDirectory(): void
    {
        $middleware = new CacheMiddleware(300, '/tmp/test_cache');

        $request = new ServerRequest('GET', '/');
        $response = new Response(200);

        $handler = new class ($response) implements RequestHandlerInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $result = $middleware->process($request, $handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('max-age=300', $result->getHeaderLine('Cache-Control'));
    }
}
