<?php

namespace PivotPHP\Core\Tests\Middleware\Performance;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Performance\RateLimitMiddleware;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RateLimitMiddlewareTest extends TestCase
{
    public function testRateLimitMiddlewareBasicFunctionality(): void
    {
        $middleware = new RateLimitMiddleware();

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

    public function testRateLimitMiddlewareWithHeaders(): void
    {
        $middleware = new RateLimitMiddleware(['limit' => 100, 'window' => 3600]);

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
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testRateLimitMiddlewareWithCustomKey(): void
    {
        $middleware = new RateLimitMiddleware(['key' => 'custom-key']);

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

    public function testRateLimitMiddlewareSkipOption(): void
    {
        $middleware = new RateLimitMiddleware(['skip' => true]);

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
        $this->assertEmpty($result->getHeaderLine('X-RateLimit-Limit'));
    }
}
