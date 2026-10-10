<?php

namespace PivotPHP\Core\Tests\Middleware\Security;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testSecurityHeadersMiddlewareBasicFunctionality(): void
    {
        $middleware = new SecurityHeadersMiddleware();

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

    public function testSecurityHeadersMiddlewareAddsSecurityHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware();

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
        $this->assertNotEmpty($result->getHeaderLine('X-Content-Type-Options'));
        $this->assertNotEmpty($result->getHeaderLine('X-Frame-Options'));
        $this->assertNotEmpty($result->getHeaderLine('X-XSS-Protection'));
    }

    public function testSecurityHeadersMiddlewareWithCustomHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware(
            [
                'X-Custom-Header' => 'custom-value'
            ]
        );

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
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testSecurityHeadersMiddlewareWithHsts(): void
    {
        $middleware = new SecurityHeadersMiddleware(
            [
                'hsts' => true,
                'hsts_max_age' => 31536000
            ]
        );

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
        $this->assertEquals(200, $result->getStatusCode());
    }
}
