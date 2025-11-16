<?php

namespace PivotPHP\Core\Tests\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Http\ErrorMiddleware;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class ErrorMiddlewareTest extends TestCase
{
    public function testErrorHandling(): void
    {
        $middleware = new ErrorMiddleware();
        $request = new ServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                throw new \RuntimeException('Erro de teste');
            }
        };
        $response = $middleware->process($request, $handler);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Erro de teste', (string)$response->getBody());
    }
}
