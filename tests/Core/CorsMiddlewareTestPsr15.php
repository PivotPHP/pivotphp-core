<?php

namespace PivotPHP\Core\Tests\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class CorsMiddlewareTestPsr15 extends TestCase
{
    public function testCorsHeadersWithDefaultsPsr15(): void
    {
        $middleware = new CorsMiddleware();
        $request = new ServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $response = $middleware->process($request, $handler);
        $this->assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET,POST,PUT,DELETE,OPTIONS', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals(
            'Content-Type,Authorization,X-Requested-With',
            $response->getHeaderLine('Access-Control-Allow-Headers')
        );
    }

    public function testCorsWithCustomOptionsPsr15(): void
    {
        $options = [
            'origin' => 'https://example.com',
            'methods' => ['GET', 'POST'],
            'headers' => ['Content-Type', 'X-API-Key'],
            'credentials' => true
        ];
        $middleware = new CorsMiddleware($options);
        $request = new ServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $response = $middleware->process($request, $handler);
        $this->assertEquals('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET,POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals('Content-Type,X-API-Key', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertEquals('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
    }

    public function testCorsWithAllowedOriginsListPsr15(): void
    {
        $options = [
            'origin' => ['https://app.example.com', 'https://admin.example.com']
        ];
        $request = (new ServerRequest('GET', '/'))->withHeader('Origin', 'https://app.example.com');
        $middleware = new CorsMiddleware($options);
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $response = $middleware->process($request, $handler);
        $this->assertEquals('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testCorsWithDisallowedOriginPsr15(): void
    {
        $options = [
            'origin' => ['https://app.example.com', 'https://admin.example.com']
        ];
        $request = (new ServerRequest('GET', '/'))->withHeader('Origin', 'https://malicious.com');
        $middleware = new CorsMiddleware($options);
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $response = $middleware->process($request, $handler);
        $this->assertEquals('null', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testOptionsPreflightRequestPsr15(): void
    {
        $middleware = new CorsMiddleware();
        $request = new ServerRequest('OPTIONS', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $response = $middleware->process($request, $handler);
        $this->assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET,POST,PUT,DELETE,OPTIONS', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals(
            'Content-Type,Authorization,X-Requested-With',
            $response->getHeaderLine('Access-Control-Allow-Headers')
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}
