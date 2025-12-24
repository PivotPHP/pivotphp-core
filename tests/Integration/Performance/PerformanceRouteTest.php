<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Performance;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

/**
 * Performance route integration test
 * Tests the /performance/json/:size route functionality
 */
class PerformanceRouteTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application(__DIR__ . '/../../..');
        $this->setupPerformanceRoutes();
        $this->app->boot();
    }

    private function setupPerformanceRoutes(): void
    {
        // Register the performance JSON route
        $this->app->get(
            '/performance/json/:size',
            function ($req, $res) {
                $size = $req->param('size');

            // Validate size parameter
                if (!in_array($size, ['small', 'medium', 'large'])) {
                    return $res->status(400)->json(
                        [
                            'error' => 'Invalid size parameter',
                            'message' => 'Size must be one of: small, medium, large',
                            'provided' => $size
                        ]
                    );
                }

            // Generate performance test data based on size
                $startTime = microtime(true);
                $data = $this->generateTestData($size);
                $generationTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

                return $res->json(
                    [
                        'size' => $size,
                        'count' => count($data),
                        'generation_time_ms' => round($generationTime, 3),
                        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                        'data' => $data
                    ]
                );
            }
        );

        // Add additional performance test routes
        $this->app->get(
            '/performance/test/:type',
            function ($req, $res) {
                $type = $req->param('type');

                switch ($type) {
                    case 'memory':
                        return $res->json(
                            [
                                'type' => 'memory',
                                'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                                'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                                'limit' => ini_get('memory_limit')
                            ]
                        );

                    case 'time':
                        $start = microtime(true);
                        // Simulate some work
                        for ($i = 0; $i < 10000; $i++) {
                            $dummy = md5((string)$i);
                        }
                        $end = microtime(true);

                        return $res->json(
                            [
                                'type' => 'time',
                                'execution_time_ms' => round(($end - $start) * 1000, 3),
                                'iterations' => 10000
                            ]
                        );

                    default:
                        return $res->status(400)->json(
                            [
                                'error' => 'Invalid test type',
                                'valid_types' => ['memory', 'time']
                            ]
                        );
                }
            }
        );
    }

    private function generateTestData(string $size): array
    {
        switch ($size) {
            case 'small':
                return array_fill(
                    0,
                    10,
                    [
                        'id' => rand(1, 1000),
                        'name' => 'Test Item',
                        'timestamp' => date('Y-m-d H:i:s')
                    ]
                );

            case 'medium':
                return array_fill(
                    0,
                    100,
                    [
                        'id' => rand(1, 1000),
                        'name' => 'Test Item',
                        'description' => 'This is a medium-sized test item with more data',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'metadata' => [
                            'category' => 'test',
                            'priority' => rand(1, 5),
                            'tags' => ['performance', 'test', 'medium']
                        ]
                    ]
                );

            case 'large':
                return array_fill(
                    0,
                    1000,
                    [
                        'id' => rand(1, 10000),
                        'name' => 'Test Item',
                        'description' => 'This is a large test item with extensive data for performance testing',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'metadata' => [
                            'category' => 'test',
                            'priority' => rand(1, 5),
                            'tags' => ['performance', 'test', 'large'],
                            'extended_data' => [
                                'field1' => str_repeat('data', 50),
                                'field2' => str_repeat('test', 25),
                                'field3' => array_fill(0, 10, rand(1, 100))
                            ]
                        ],
                        'additional_info' => [
                            'created_by' => 'system',
                            'version' => '1.0.0',
                            'checksum' => md5(uniqid()),
                            'extra' => str_repeat('x', 100)
                        ]
                    ]
                );

            default:
                return [];
        }
    }

    /**
     * @test
     */
    public function testPerformanceJsonSmallRoute(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/performance/json/:size', '/performance/json/small');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertIsArray($body);
        $this->assertEquals('small', $body['size']);
        $this->assertEquals(10, $body['count']);
        $this->assertArrayHasKey('generation_time_ms', $body);
        $this->assertArrayHasKey('memory_usage_mb', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(10, $body['data']);
    }

    /**
     * @test
     */
    public function testPerformanceJsonMediumRoute(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/performance/json/:size', '/performance/json/medium');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertIsArray($body);
        $this->assertEquals('medium', $body['size']);
        $this->assertEquals(100, $body['count']);
        $this->assertArrayHasKey('generation_time_ms', $body);
        $this->assertArrayHasKey('memory_usage_mb', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(100, $body['data']);
    }

    /**
     * @test
     */
    public function testPerformanceJsonLargeRoute(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/performance/json/:size', '/performance/json/large');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertIsArray($body);
        $this->assertEquals('large', $body['size']);
        $this->assertEquals(1000, $body['count']);
        $this->assertArrayHasKey('generation_time_ms', $body);
        $this->assertArrayHasKey('memory_usage_mb', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(1000, $body['data']);
    }

    /**
     * @test
     */
    public function testPerformanceJsonInvalidSize(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/performance/json/:size', '/performance/json/invalid');
        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
        $this->assertEquals('Invalid size parameter', $body['error']);
        $this->assertEquals('invalid', $body['provided']);
    }

    /**
     * @test
     */
    public function testPerformanceMemoryTest(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/performance/test/:type', '/performance/test/memory');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertIsArray($body);
        $this->assertEquals('memory', $body['type']);
        $this->assertArrayHasKey('current_usage_mb', $body);
        $this->assertArrayHasKey('peak_usage_mb', $body);
        $this->assertArrayHasKey('limit', $body);
        $this->assertIsNumeric($body['current_usage_mb']);
        $this->assertIsNumeric($body['peak_usage_mb']);
    }

    /**
     * @test
     */
    public function testPerformanceTimeTest(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/performance/test/:type', '/performance/test/time');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertIsArray($body);
        $this->assertEquals('time', $body['type']);
        $this->assertArrayHasKey('execution_time_ms', $body);
        $this->assertArrayHasKey('iterations', $body);
        $this->assertEquals(10000, $body['iterations']);
        $this->assertIsNumeric($body['execution_time_ms']);
        $this->assertGreaterThan(0, $body['execution_time_ms']);
    }

    /**
     * @test
     */
    public function testRouteParameterExtraction(): void
    {
        // Test that parameters are correctly extracted by the router
        $request = OptimizedHttpFactory::createRequest('GET', '/performance/json/:size', '/performance/json/small');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        // Verify the route was matched and parameter extracted correctly
        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertEquals('small', $body['size']);
    }

    /**
     * @test
     */
    public function testJsonResponseStructure(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/performance/json/:size', '/performance/json/medium');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = $response->getBody();
        $body = json_decode(is_string($responseBody) ? $responseBody : $responseBody->__toString(), true);
        $this->assertIsArray($body);

        // Verify required fields
        $requiredFields = ['size', 'count', 'generation_time_ms', 'memory_usage_mb', 'data'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $body, "Missing required field: {$field}");
        }

        // Verify data structure
        $this->assertIsArray($body['data']);
        if (!empty($body['data'])) {
            $firstItem = $body['data'][0];
            $this->assertArrayHasKey('id', $firstItem);
            $this->assertArrayHasKey('name', $firstItem);
            $this->assertArrayHasKey('timestamp', $firstItem);
        }
    }
}
