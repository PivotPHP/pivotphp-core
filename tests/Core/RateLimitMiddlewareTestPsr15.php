<?php

namespace PivotPHP\Core\Tests\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Performance\RateLimitMiddleware;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class RateLimitMiddlewareTestPsr15 extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['rate_limit'] = [];
    }

    public function testAllowsRequestsWithinLimit(): void
    {
        $middleware = new RateLimitMiddleware(['max' => 2, 'windowMs' => 10000]);
        $request = new ServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $response1 = $middleware->process($request, $handler);
        $response2 = $middleware->process($request, $handler);
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
    }

    public function testBlocksRequestsOverLimit(): void
    {
        $middleware = new RateLimitMiddleware(['max' => 1, 'windowMs' => 10000]);
        $request = new ServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $middleware->process($request, $handler); // primeira
        $response = $middleware->process($request, $handler); // segunda, deve bloquear
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('Too many requests', (string)$response->getBody());
    }

    public function testCustomKeyGenerator(): void
    {
        $middleware = new RateLimitMiddleware(
            [
                'max' => 1,
                'windowMs' => 10000,
                'keyGenerator' => function ($request) {
                    return 'custom-key';
                }
            ]
        );
        $request = new ServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $middleware->process($request, $handler);
        $response = $middleware->process($request, $handler);
        $this->assertEquals(429, $response->getStatusCode());
    }
}
