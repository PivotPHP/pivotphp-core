<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Nyholm\Psr7\ServerRequest;
use PivotPHP\Core\Tests\Integration\Routing\ExampleController;

/**
 * Simple example demonstrating array callable usage
 */
class ArrayCallableExampleTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application(__DIR__ . '/../../..');
        $this->setupExampleRoutes();
        $this->app->boot();
    }

    private function setupExampleRoutes(): void
    {
        $controller = new ExampleController();

        // ✅ Example 1: Instance method array callable
        $this->app->get('/health', [$controller, 'healthCheck']);

        // ✅ Example 2: Static method array callable
        $this->app->get('/api/info', [ExampleController::class, 'getApiInfo']);

        // ✅ Example 3: Array callable with parameters
        $this->app->get('/users/:id', [$controller, 'getUserById']);
    }

    /**
     * @test
     * Example usage: $app->get('/health', [$controller, 'healthCheck'])
     */
    public function testHealthCheckArrayCallable(): void
    {
        $request = new ServerRequest('GET', '/health');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertEquals('ok', $body['status']);
        $this->assertIsInt($body['timestamp']);
        $this->assertIsNumeric($body['memory_usage_mb']);
    }

    /**
     * @test
     * Example usage: $app->get('/api/info', [Controller::class, 'staticMethod'])
     */
    public function testStaticMethodArrayCallable(): void
    {
        $request = new ServerRequest('GET', '/api/info');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertEquals('1.0', $body['api_version']);
        $this->assertEquals('PivotPHP', $body['framework']);
        $this->assertEquals(Application::VERSION, $body['version']);
    }

    /**
     * @test
     * Example usage: $app->get('/users/:id', [$controller, 'getUserById'])
     */
    public function testParameterizedArrayCallable(): void
    {
        $request = new ServerRequest('GET', '/users/12345');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertEquals('12345', $body['user_id']);
        $this->assertEquals('User 12345', $body['name']);
        $this->assertTrue($body['active']);
    }

    /**
     * @test
     * Performance comparison: closure vs array callable
     */
}
