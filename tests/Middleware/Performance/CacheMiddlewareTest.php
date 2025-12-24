<?php

namespace PivotPHP\Core\Tests\Middleware\Performance;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Performance\CacheMiddleware;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CacheMiddlewareTest extends TestCase
{
    public function testCacheMiddlewareBasicFunctionality(): void
    {
        $middleware = new CacheMiddleware();

        $request = OptimizedHttpFactory::createRequest('GET', '/', '/');
        $response = new Response();
        $response->status(200);

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

        $request = OptimizedHttpFactory::createRequest('GET', '/', '/');
        $response = new Response();
        $response->status(200);

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

        $request = OptimizedHttpFactory::createRequest('POST', '/', '/');
        $response = new Response();
        $response->status(200);

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

        $request = OptimizedHttpFactory::createRequest('GET', '/', '/');
        $response = new Response();
        $response->status(200);

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
