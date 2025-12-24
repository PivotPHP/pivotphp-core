<?php

namespace PivotPHP\Core\Tests\Middleware\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Http\ErrorMiddleware;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Exceptions\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

class ErrorMiddlewareTest extends TestCase
{
    public function testErrorMiddlewareHandlesHttpException(): void
    {
        $middleware = new ErrorMiddleware();

        $request = OptimizedHttpFactory::createRequest('GET', '/', '/');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new HttpException(404, 'Not Found');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertStringContainsString('Not Found', (string) $response->getBody());
    }

    public function testErrorMiddlewareHandlesGenericException(): void
    {
        $middleware = new ErrorMiddleware();

        $request = OptimizedHttpFactory::createRequest('GET', '/', '/');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new Exception('Something went wrong');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal Server Error', (string) $response->getBody());
    }

    public function testErrorMiddlewarePassesThroughNormalResponse(): void
    {
        $middleware = new ErrorMiddleware();

        $request = OptimizedHttpFactory::createRequest('GET', '/', '/');
        $expectedResponse = new Response();
        $expectedResponse->status(200);

        $handler = new class ($expectedResponse) implements RequestHandlerInterface {
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

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expectedResponse, $response);
    }

    public function testErrorMiddlewareWithDebugMode(): void
    {
        $middleware = new ErrorMiddleware(true);

        $request = OptimizedHttpFactory::createRequest('GET', '/', '/');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new Exception('Debug message');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Debug message', (string) $response->getBody());
    }
}
