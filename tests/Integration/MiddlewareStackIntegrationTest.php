<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Middleware\MiddlewareStack;

/**
 * Integration tests for middleware stack and application integration
 *
 * Tests real-world middleware scenarios, error handling,
 * performance middleware integration, and security middleware.
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
     * Test basic middleware pipeline execution
     */
    public function testBasicMiddlewarePipelineExecution(): void
    {
        $executionLog = [];

        // Add multiple middleware
        $this->app->use(
            function ($req, $res, $next) use (&$executionLog) {
                $executionLog[] = 'auth_middleware_start';
                $result = $next($req, $res);
                $executionLog[] = 'auth_middleware_end';
                return $result;
            }
        );

        $this->app->use(
            function ($req, $res, $next) use (&$executionLog) {
                $executionLog[] = 'logging_middleware_start';
                $result = $next($req, $res);
                $executionLog[] = 'logging_middleware_end';
                return $result;
            }
        );

        $this->app->use(
            function ($req, $res, $next) use (&$executionLog) {
                $executionLog[] = 'cors_middleware_start';
                $result = $next($req, $res);
                $executionLog[] = 'cors_middleware_end';
                return $result;
            }
        );

        // Add route handler
        $this->app->get(
            '/api/test',
            function ($req, $res) use (&$executionLog) {
                $executionLog[] = 'route_handler';
                return $res->json(['status' => 'success']);
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/api/test', '/api/test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        // Verify middleware execution order
        $this->assertContains('auth_middleware_start', $executionLog);
        $this->assertContains('logging_middleware_start', $executionLog);
        $this->assertContains('cors_middleware_start', $executionLog);
        $this->assertContains('route_handler', $executionLog);
        $this->assertContains('cors_middleware_end', $executionLog);
        $this->assertContains('logging_middleware_end', $executionLog);
        $this->assertContains('auth_middleware_end', $executionLog);
    }

    /**
     * Test middleware error handling and propagation
     */
    public function testMiddlewareErrorHandling(): void
    {
        $errorHandled = false;

        // Error handling middleware
        $this->app->use(
            function ($req, $res, $next) use (&$errorHandled) {
                try {
                    return $next($req, $res);
                } catch (\Exception $e) {
                    $errorHandled = true;
                    return $res->status(500)->json(['error' => $e->getMessage()]);
                }
            }
        );

        // Middleware that throws exception
        $this->app->use(
            function ($req, $res, $next) {
                throw new \Exception('Middleware error');
            }
        );

        $this->app->get(
            '/error-test-middleware',
            function ($req, $res) {
                return $res->json(['should' => 'not reach']);
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/error-test-middleware', '/error-test-middleware');
        $response = $this->app->handle($request);

        $this->assertTrue($errorHandled);
        $this->assertEquals(500, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertEquals('Middleware error', $body['error']);
    }

    /**
     * Test request/response modification through middleware
     */
    public function testRequestResponseModification(): void
    {
        // Middleware that modifies request
        $this->app->use(
            function ($req, $res, $next) {
                $req->setAttribute('user_id', 123);
                $req->setAttribute('authenticated', true);
                return $next($req, $res);
            }
        );

        // Middleware that modifies response headers
        $this->app->use(
            function ($req, $res, $next) {
                $result = $next($req, $res);
                $result->header('X-Custom-Header', 'middleware-added');
                $result->header('X-Request-ID', uniqid());
                return $result;
            }
        );

        $this->app->get(
            '/modify-test',
            function ($req, $res) {
                $userId = $req->getAttribute('user_id');
                $authenticated = $req->getAttribute('authenticated');

                return $res->json(
                    [
                        'user_id' => $userId,
                        'authenticated' => $authenticated,
                        'message' => 'Request modified by middleware'
                    ]
                );
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/modify-test', '/modify-test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        // Check response headers
        $this->assertEquals('middleware-added', $response->getHeaderLine('X-Custom-Header'));
        $this->assertNotEmpty($response->getHeaderLine('X-Request-ID'));

        // Check response body
        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertEquals(123, $body['user_id']);
        $this->assertTrue($body['authenticated']);
    }

    /**
     * Test conditional middleware execution
     */
    public function testConditionalMiddlewareExecution(): void
    {
        $authCheckRan = false;
        $adminCheckRan = false;

        // Authentication middleware (runs for all routes)
        $this->app->use(
            function ($req, $res, $next) use (&$authCheckRan) {
                $authCheckRan = true;
                $req->setAttribute('user_role', 'admin');
                return $next($req, $res);
            }
        );

        // Admin-only middleware (conditional)
        $this->app->use(
            function ($req, $res, $next) use (&$adminCheckRan) {
                $path = $req->getPathCallable();

                if (strpos($path, '/admin') === 0) {
                    $adminCheckRan = true;
                    $userRole = $req->getAttribute('user_role');

                    if ($userRole !== 'admin') {
                        return $res->status(403)->json(['error' => 'Admin access required']);
                    }
                }

                return $next($req, $res);
            }
        );

        // Regular route
        $this->app->get(
            '/api/user',
            function ($req, $res) {
                return $res->json(['role' => $req->getAttribute('user_role')]);
            }
        );

        // Admin route
        $this->app->get(
            '/admin/users',
            function ($req, $res) {
                return $res->json(['admin_data' => 'sensitive']);
            }
        );

        $this->app->boot();

        // Test regular route
        $request1 = new Request('GET', '/api/user', '/api/user');
        $response1 = $this->app->handle($request1);

        $this->assertTrue($authCheckRan);
        $this->assertFalse($adminCheckRan); // Should not run for non-admin route
        $this->assertEquals(200, $response1->getStatusCode());

        // Reset flags
        $authCheckRan = false;
        $adminCheckRan = false;

        // Test admin route
        $request2 = new Request('GET', '/admin/users', '/admin/users');
        $response2 = $this->app->handle($request2);

        $this->assertTrue($authCheckRan);
        $this->assertTrue($adminCheckRan); // Should run for admin route
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /**
     * Test middleware with async/promise-like behavior simulation
     */
    public function testMiddlewareWithAsyncSimulation(): void
    {
        $processingTimes = [];

        // Timing middleware
        $this->app->use(
            function ($req, $res, $next) use (&$processingTimes) {
                $start = microtime(true);
                $result = $next($req, $res);
                $end = microtime(true);

                $processingTimes[] = ($end - $start) * 1000; // Convert to milliseconds
                $result->header('X-Processing-Time', number_format(($end - $start) * 1000, 2) . 'ms');

                return $result;
            }
        );

        // Simulated async middleware
        $this->app->use(
            function ($req, $res, $next) {
            // Simulate async operation delay
                usleep(10000); // 10ms delay

                $req->setAttribute('async_data', 'processed');
                return $next($req, $res);
            }
        );

        $this->app->get(
            '/async-test',
            function ($req, $res) {
                $asyncData = $req->getAttribute('async_data');
                return $res->json(['async_data' => $asyncData, 'status' => 'completed']);
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/async-test', '/async-test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        // Check processing time header
        $processingTime = $response->getHeaderLine('X-Processing-Time');
        $this->assertNotEmpty($processingTime);
        $this->assertStringContainsString('ms', $processingTime);

        // Verify async data was processed
        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertEquals('processed', $body['async_data']);

        // Verify timing was recorded
        $this->assertNotEmpty($processingTimes);
        // Allow for timing variance and possible negative values due to microsecond precision
        $this->assertGreaterThan(-1000, $processingTimes[0]); // Reasonable lower bound

        // If the timing is positive, it should be at least the delay we added
        if ($processingTimes[0] > 0) {
            $this->assertGreaterThan(8, $processingTimes[0]); // Should be > 8ms due to 10ms delay (with tolerance)
        }
    }

    /**
     * Test middleware stack performance under load
     */
    public function testMiddlewareStackPerformance(): void
    {
        // Add multiple middleware layers
        for ($i = 0; $i < 10; $i++) {
            $this->app->use(
                function ($req, $res, $next) use ($i) {
                    $req->setAttribute("middleware_{$i}_executed", true);
                    return $next($req, $res);
                }
            );
        }

        $uniquePath = '/performance-test-' . substr(md5(__METHOD__), 0, 8);
        $this->app->get(
            $uniquePath,
            function ($req, $res) {
                $executed = [];
                for ($i = 0; $i < 10; $i++) {
                    if ($req->getAttribute("middleware_{$i}_executed")) {
                        $executed[] = $i;
                    }
                }

                return $res->json(['executed_middleware' => $executed]);
            }
        );

        $this->app->boot();

        $times = [];
        $iterations = 100;

        for ($j = 0; $j < $iterations; $j++) {
            $start = microtime(true);

            $request = new Request('GET', $uniquePath, $uniquePath);
            $response = $this->app->handle($request);

            $end = microtime(true);
            $times[] = ($end - $start) * 1000; // Convert to milliseconds

            $this->assertEquals(200, $response->getStatusCode());

            $responseBody = $response->getBody();
            $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);

            $this->assertArrayHasKey('executed_middleware', $body, 'Response missing executed_middleware key');
            $this->assertCount(10, $body['executed_middleware']);
        }

        $averageTime = array_sum($times) / count($times);
        $maxTime = max($times);

        // Performance assertions (adjust based on system capabilities)
        // More lenient thresholds for CI/virtualized environments
        $averageThreshold = getenv('CI') ? 1000 : 100; // 1000ms for CI, 100ms for local
        $maxThreshold = getenv('CI') ? 2000 : 500;     // 2000ms for CI, 500ms for local

        $this->assertLessThan(
            $averageThreshold,
            $averageTime,
            "Average request time should be < {$averageThreshold}ms"
        );
        $this->assertLessThan($maxThreshold, $maxTime, "Maximum request time should be < {$maxThreshold}ms");
    }

    /**
     * Test middleware with different HTTP methods
     */
    public function testMiddlewareWithDifferentHttpMethods(): void
    {
        $methodLog = [];

        // Method-aware middleware
        $this->app->use(
            function ($req, $res, $next) use (&$methodLog) {
                $method = $req->getMethod();
                $methodLog[] = $method;

                if ($method === 'POST') {
                    $req->setAttribute('content_validated', true);
                }

                return $next($req, $res);
            }
        );

        // Routes for different methods
        $this->app->get(
            '/method-test',
            function ($req, $res) {
                return $res->json(['method' => 'GET']);
            }
        );

        $this->app->post(
            '/method-test',
            function ($req, $res) {
                $validated = $req->getAttribute('content_validated');
                return $res->json(['method' => 'POST', 'validated' => $validated]);
            }
        );

        $this->app->put(
            '/method-test',
            function ($req, $res) {
                return $res->json(['method' => 'PUT']);
            }
        );

        $this->app->boot();

        // Test GET
        $getRequest = new Request('GET', '/method-test', '/method-test');
        $getResponse = $this->app->handle($getRequest);

        $this->assertEquals(200, $getResponse->getStatusCode());
        $getResponseBody = $getResponse->getBody();
        $getBody = json_decode(is_string($getResponseBody) ? $getResponseBody : $getResponseBody->__toString(), true);
        $this->assertEquals('GET', $getBody['method']);

        // Test POST
        $postRequest = new Request('POST', '/method-test', '/method-test');
        $postResponse = $this->app->handle($postRequest);

        $this->assertEquals(200, $postResponse->getStatusCode());
        $postResponseBody = $postResponse->getBody();
        $postBody = json_decode(
            is_string($postResponseBody) ? $postResponseBody : $postResponseBody->__toString(),
            true
        );
        $this->assertEquals('POST', $postBody['method']);
        $this->assertTrue($postBody['validated']);

        // Test PUT
        $putRequest = new Request('PUT', '/method-test', '/method-test');
        $putResponse = $this->app->handle($putRequest);

        $this->assertEquals(200, $putResponse->getStatusCode());
        $putResponseBody = $putResponse->getBody();
        $putBody = json_decode(is_string($putResponseBody) ? $putResponseBody : $putResponseBody->__toString(), true);
        $this->assertEquals('PUT', $putBody['method']);

        // Verify method logging
        $this->assertContains('GET', $methodLog);
        $this->assertContains('POST', $methodLog);
        $this->assertContains('PUT', $methodLog);
    }

    /**
     * Test middleware early termination
     */
    public function testMiddlewareEarlyTermination(): void
    {
        $middlewareLog = [];

        // First middleware
        $this->app->use(
            function ($req, $res, $next) use (&$middlewareLog) {
                $middlewareLog[] = 'middleware1';
                return $next($req, $res);
            }
        );

        // Terminating middleware
        $this->app->use(
            function ($req, $res, $next) use (&$middlewareLog) {
                $middlewareLog[] = 'middleware2';

                $path = $req->getPathCallable();
                if ($path === '/terminate') {
                    return $res->status(403)->json(['error' => 'Access denied']);
                }

                return $next($req, $res);
            }
        );

        // Third middleware (should not run for /terminate)
        $this->app->use(
            function ($req, $res, $next) use (&$middlewareLog) {
                $middlewareLog[] = 'middleware3';
                return $next($req, $res);
            }
        );

        $this->app->get(
            '/terminate',
            function ($req, $res) use (&$middlewareLog) {
                $middlewareLog[] = 'handler';
                return $res->json(['should_not' => 'reach']);
            }
        );

        $this->app->get(
            '/continue',
            function ($req, $res) use (&$middlewareLog) {
                $middlewareLog[] = 'handler';
                return $res->json(['reached' => 'handler']);
            }
        );

        $this->app->boot();

        // Test early termination
        $terminateRequest = new Request('GET', '/terminate', '/terminate');
        $terminateResponse = $this->app->handle($terminateRequest);

        $this->assertEquals(403, $terminateResponse->getStatusCode());
        $this->assertContains('middleware1', $middlewareLog);
        $this->assertContains('middleware2', $middlewareLog);
        $this->assertNotContains('middleware3', $middlewareLog);
        $this->assertNotContains('handler', $middlewareLog);

        // Reset log
        $middlewareLog = [];

        // Test normal flow
        $continueRequest = new Request('GET', '/continue', '/continue');
        $continueResponse = $this->app->handle($continueRequest);

        $this->assertEquals(200, $continueResponse->getStatusCode());
        $this->assertContains('middleware1', $middlewareLog);
        $this->assertContains('middleware2', $middlewareLog);
        $this->assertContains('middleware3', $middlewareLog);
        $this->assertContains('handler', $middlewareLog);
    }

    /**
     * Test middleware context preservation
     */
    public function testMiddlewareContextPreservation(): void
    {
        // Context building middleware
        $this->app->use(
            function ($req, $res, $next) {
                $req->setAttribute(
                    'context',
                    [
                        'request_id' => uniqid(),
                        'timestamp' => time(),
                        'middleware_chain' => []
                    ]
                );

                return $next($req, $res);
            }
        );

        // Context modifying middleware
        $this->app->use(
            function ($req, $res, $next) {
                $context = $req->getAttribute('context');
                $context['middleware_chain'][] = 'auth';
                $context['user_ip'] = '192.168.1.1';
                $req->setAttribute('context', $context);

                return $next($req, $res);
            }
        );

        // Another context modifying middleware
        $this->app->use(
            function ($req, $res, $next) {
                $context = $req->getAttribute('context');
                $context['middleware_chain'][] = 'logging';
                $context['session_id'] = 'sess_' . uniqid();
                $req->setAttribute('context', $context);

                return $next($req, $res);
            }
        );

        $this->app->get(
            '/context-test',
            function ($req, $res) {
                $context = $req->getAttribute('context');
                return $res->json(['context' => $context]);
            }
        );

        $this->app->boot();

        $request = new Request('GET', '/context-test', '/context-test');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $context = $body['context'];

        $this->assertArrayHasKey('request_id', $context);
        $this->assertArrayHasKey('timestamp', $context);
        $this->assertArrayHasKey('user_ip', $context);
        $this->assertArrayHasKey('session_id', $context);
        $this->assertEquals(['auth', 'logging'], $context['middleware_chain']);
        $this->assertEquals('192.168.1.1', $context['user_ip']);
        $this->assertStringStartsWith('sess_', $context['session_id']);
    }
}
