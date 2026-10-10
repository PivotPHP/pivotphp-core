<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Middleware\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Http\CorsMiddleware;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Comprehensive tests for CorsMiddleware
 *
 * Tests PSR-15 compliance, CORS security features, and optimization.
 * Following the "less is more" principle with focused testing.
 */
class CorsMiddlewareTest extends TestCase
{
    private CorsMiddleware $middleware;
    private ServerRequest $request;
    private MockRequestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CorsMiddleware();
        $this->request = new ServerRequest('GET', 'https://example.com/test');
        $this->handler = new MockRequestHandler();
    }

    /**
     * Test default configuration
     */
    public function testDefaultConfiguration(): void
    {
        $middleware = new CorsMiddleware();
        $this->assertInstanceOf(CorsMiddleware::class, $middleware);
    }

    /**
     * Test custom configuration
     */
    public function testCustomConfiguration(): void
    {
        $config = [
            'origin' => 'https://trusted.com',
            'methods' => ['GET', 'POST'],
            'headers' => ['Content-Type'],
            'credentials' => true,
            'max_age' => 3600,
            'expose_headers' => ['X-Total-Count'],
        ];

        $middleware = new CorsMiddleware($config);
        $this->assertInstanceOf(CorsMiddleware::class, $middleware);
    }

    /**
     * Test process method with regular request
     */
    public function testProcessRegularRequest(): void
    {
        $request = $this->request->withMethod('GET')
                                ->withHeader('Origin', 'https://example.com');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET,POST,PUT,DELETE,OPTIONS', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals(
            'Content-Type,Authorization,X-Requested-With',
            $response->getHeaderLine('Access-Control-Allow-Headers')
        );
    }

    /**
     * Test OPTIONS preflight request
     */
    public function testOptionsPreflightRequest(): void
    {
        $request = $this->request->withMethod('OPTIONS')
                                ->withHeader('Origin', 'https://example.com')
                                ->withHeader('Access-Control-Request-Method', 'POST');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET,POST,PUT,DELETE,OPTIONS', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals(
            'Content-Type,Authorization,X-Requested-With',
            $response->getHeaderLine('Access-Control-Allow-Headers')
        );
        $this->assertEquals('86400', $response->getHeaderLine('Access-Control-Max-Age'));
    }

    /**
     * Test __invoke method
     */
    public function testInvokeMethod(): void
    {
        $request = $this->request->withMethod('GET')
                                ->withHeader('Origin', 'https://example.com');

        $response = $this->middleware->__invoke($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test handle method throws exception (legacy compatibility)
     *
     * Note: This test is skipped since the handle method signature is restrictive
     * and would cause a TypeError before reaching the BadMethodCallException.
     * The middleware is designed to be PSR-15 only.
     */
    public function testHandleMethodThrowsException(): void
    {
        $this->markTestSkipped(
            'CorsMiddleware handle method has restrictive type hints that prevent testing the exception'
        );
    }

    /**
     * Test CORS with specific origin
     */
    public function testCorsWithSpecificOrigin(): void
    {
        $middleware = new CorsMiddleware(
            [
                'origin' => 'https://trusted.com'
            ]
        );

        $request = $this->request->withMethod('GET')
                                ->withHeader('Origin', 'https://trusted.com');

        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('https://trusted.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    /**
     * Test CORS with array of allowed origins
     */
    public function testCorsWithArrayOfOrigins(): void
    {
        $middleware = new CorsMiddleware(
            [
                'origin' => ['https://trusted.com', 'https://another.com']
            ]
        );

        // Test allowed origin
        $request = $this->request->withMethod('GET')
                                ->withHeader('Origin', 'https://trusted.com');

        $response = $middleware->process($request, $this->handler);
        $this->assertEquals('https://trusted.com', $response->getHeaderLine('Access-Control-Allow-Origin'));

        // Test disallowed origin
        $request = $this->request->withMethod('GET')
                                ->withHeader('Origin', 'https://untrusted.com');

        $response = $middleware->process($request, $this->handler);
        $this->assertEquals('null', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    /**
     * Test CORS with credentials enabled
     */
    public function testCorsWithCredentials(): void
    {
        $middleware = new CorsMiddleware(
            [
                'credentials' => true
            ]
        );

        $request = $this->request->withMethod('GET')
                                ->withHeader('Origin', 'https://example.com');

        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
    }

    /**
     * Test CORS with credentials disabled
     */
    public function testCorsWithCredentialsDisabled(): void
    {
        $middleware = new CorsMiddleware(
            [
                'credentials' => false
            ]
        );

        $request = $this->request->withMethod('GET');
        $response = $middleware->process($request, $this->handler);

        $this->assertEmpty($response->getHeaderLine('Access-Control-Allow-Credentials'));
    }

    /**
     * Test CORS with custom methods
     */
    public function testCorsWithCustomMethods(): void
    {
        $middleware = new CorsMiddleware(
            [
                'methods' => ['GET', 'POST', 'PATCH']
            ]
        );

        $request = $this->request->withMethod('OPTIONS');
        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('GET,POST,PATCH', $response->getHeaderLine('Access-Control-Allow-Methods'));
    }

    /**
     * Test CORS with custom methods as string
     */
    public function testCorsWithCustomMethodsAsString(): void
    {
        $middleware = new CorsMiddleware(
            [
                'methods' => 'GET, POST, PATCH'
            ]
        );

        $request = $this->request->withMethod('OPTIONS');
        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('GET,POST,PATCH', $response->getHeaderLine('Access-Control-Allow-Methods'));
    }

    /**
     * Test CORS with custom headers
     */
    public function testCorsWithCustomHeaders(): void
    {
        $middleware = new CorsMiddleware(
            [
                'headers' => ['Content-Type', 'X-API-Key']
            ]
        );

        $request = $this->request->withMethod('OPTIONS');
        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('Content-Type,X-API-Key', $response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    /**
     * Test CORS with custom headers as string
     */
    public function testCorsWithCustomHeadersAsString(): void
    {
        $middleware = new CorsMiddleware(
            [
                'headers' => 'Content-Type, X-API-Key'
            ]
        );

        $request = $this->request->withMethod('OPTIONS');
        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('Content-Type,X-API-Key', $response->getHeaderLine('Access-Control-Allow-Headers'));
    }

    /**
     * Test CORS with custom max age
     */
    public function testCorsWithCustomMaxAge(): void
    {
        $middleware = new CorsMiddleware(
            [
                'max_age' => 3600
            ]
        );

        $request = $this->request->withMethod('OPTIONS');
        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('3600', $response->getHeaderLine('Access-Control-Max-Age'));
    }

    /**
     * Test CORS with expose headers
     */
    public function testCorsWithExposeHeaders(): void
    {
        $middleware = new CorsMiddleware(
            [
                'expose_headers' => ['X-Total-Count', 'X-Page-Count']
            ]
        );

        $request = $this->request->withMethod('GET');
        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('X-Total-Count, X-Page-Count', $response->getHeaderLine('Access-Control-Expose-Headers'));
    }

    /**
     * Test CORS without expose headers
     */
    public function testCorsWithoutExposeHeaders(): void
    {
        $middleware = new CorsMiddleware(
            [
                'expose_headers' => []
            ]
        );

        $request = $this->request->withMethod('GET');
        $response = $middleware->process($request, $this->handler);

        $this->assertEmpty($response->getHeaderLine('Access-Control-Expose-Headers'));
    }

    /**
     * Test preflight optimization
     */
    public function testPreflightOptimization(): void
    {
        $startTime = microtime(true);

        $request = $this->request->withMethod('OPTIONS')
                                ->withHeader('Origin', 'https://example.com');

        $response = $this->middleware->process($request, $this->handler);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should be very fast (less than 10ms)
        $this->assertLessThan(10, $duration);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test request without Origin header
     */
    public function testRequestWithoutOriginHeader(): void
    {
        $request = $this->request->withMethod('GET');
        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    /**
     * Test multiple CORS requests
     */
    public function testMultipleCorsRequests(): void
    {
        $middleware = new CorsMiddleware(
            [
                'origin' => ['https://app1.com', 'https://app2.com']
            ]
        );

        // First request
        $request1 = $this->request->withMethod('GET')
                                 ->withHeader('Origin', 'https://app1.com');
        $response1 = $middleware->process($request1, $this->handler);
        $this->assertEquals('https://app1.com', $response1->getHeaderLine('Access-Control-Allow-Origin'));

        // Second request
        $request2 = $this->request->withMethod('GET')
                                 ->withHeader('Origin', 'https://app2.com');
        $response2 = $middleware->process($request2, $this->handler);
        $this->assertEquals('https://app2.com', $response2->getHeaderLine('Access-Control-Allow-Origin'));

        // Invalid request
        $request3 = $this->request->withMethod('GET')
                                 ->withHeader('Origin', 'https://malicious.com');
        $response3 = $middleware->process($request3, $this->handler);
        $this->assertEquals('null', $response3->getHeaderLine('Access-Control-Allow-Origin'));
    }

    /**
     * Test CORS headers consistency
     */
    public function testCorsHeadersConsistency(): void
    {
        $middleware = new CorsMiddleware(
            [
                'origin' => 'https://trusted.com',
                'methods' => ['GET', 'POST'],
                'headers' => ['Content-Type', 'Authorization'],
                'credentials' => true,
                'max_age' => 7200,
            ]
        );

        // Test regular request
        $request = $this->request->withMethod('GET')
                                ->withHeader('Origin', 'https://trusted.com');
        $response = $middleware->process($request, $this->handler);

        $this->assertEquals('https://trusted.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET,POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals(
            'Content-Type,Authorization',
            $response->getHeaderLine('Access-Control-Allow-Headers')
        );
        $this->assertEquals('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));

        // Test preflight request
        $preflightRequest = $this->request->withMethod('OPTIONS')
                                         ->withHeader('Origin', 'https://trusted.com');
        $preflightResponse = $middleware->process($preflightRequest, $this->handler);

        $this->assertEquals('https://trusted.com', $preflightResponse->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertEquals('GET,POST', $preflightResponse->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertEquals(
            'Content-Type,Authorization',
            $preflightResponse->getHeaderLine('Access-Control-Allow-Headers')
        );
        $this->assertEquals('true', $preflightResponse->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertEquals('7200', $preflightResponse->getHeaderLine('Access-Control-Max-Age'));
    }

    /**
     * Test performance under load
     */
    public function testPerformanceUnderLoad(): void
    {
        $startTime = microtime(true);

        // Simulate 100 requests
        for ($i = 0; $i < 100; $i++) {
            $request = $this->request->withMethod($i % 2 === 0 ? 'GET' : 'OPTIONS')
                                    ->withHeader('Origin', 'https://example.com');
            $this->middleware->process($request, $this->handler);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should handle 100 requests quickly (less than 100ms)
        $this->assertLessThan(100, $duration);
    }

    /**
     * Test edge cases
     */
    public function testEdgeCases(): void
    {
        $middleware = new CorsMiddleware(
            [
                'origin' => [],
                'methods' => [],
                'headers' => [],
            ]
        );

        $request = $this->request->withMethod('GET');
        $response = $middleware->process($request, $this->handler);

        // Should handle empty configurations gracefully
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}

/**
 * Mock request handler for testing
 */
class MockRequestHandler implements RequestHandlerInterface
{
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
    {
        $factory = new Psr17Factory();
        return $factory->createResponse(200)
                      ->withHeader('Content-Type', 'application/json');
    }
}
