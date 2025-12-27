<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Test that pooling thresholds are centralized and consistent
 */
class JsonPoolingThresholdsTest extends TestCase
{
    /**
     * Test data size that guarantees pooling is triggered.
     * This value is greater than NESTED_ARRAY_CHECK_THRESHOLD (50) to bypass nested structure checks
     * and ensure pooling is always used for consistent test behavior.
     */
    private const TEST_DATA_SIZE = 55;
    protected function setUp(): void
    {
        JsonBufferPool::clearPools();
        JsonBufferPool::resetConfiguration();
    }

    protected function tearDown(): void
    {
        JsonBufferPool::clearPools();
        JsonBufferPool::resetConfiguration();
    }

    /**
     * Test that pooling constants are properly defined
     */
    public function testPoolingConstantsExist(): void
    {
        $reflection = new \ReflectionClass(JsonBufferPool::class);
        $this->assertTrue($reflection->hasConstant('POOLING_ARRAY_THRESHOLD'));
        $this->assertTrue($reflection->hasConstant('POOLING_OBJECT_THRESHOLD'));
        $this->assertTrue($reflection->hasConstant('POOLING_STRING_THRESHOLD'));

        // Verify values are reasonable
        $this->assertEquals(10, JsonBufferPool::POOLING_ARRAY_THRESHOLD);
        $this->assertEquals(5, JsonBufferPool::POOLING_OBJECT_THRESHOLD);
        $this->assertEquals(1024, JsonBufferPool::POOLING_STRING_THRESHOLD);
    }

    /**
     * Test that Response uses centralized thresholds
     */
    public function testResponseUsesPoolingThresholds(): void
    {
        $response = new Response();
        $response->setTestMode(true);

        // Test array threshold - just below threshold should not pool
        $smallArray = array_fill(0, JsonBufferPool::POOLING_ARRAY_THRESHOLD - 1, 'item');
        $response->json($smallArray);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $stats['total_operations'], 'Small arrays should not use pooling');

        // Reset
        JsonBufferPool::clearPools();
        $response = new Response();
        $response->setTestMode(true);

        // Test array threshold - create array that should use pooling (50+ elements)
        $mediumArray = array_fill(0, self::TEST_DATA_SIZE, 'item');
        $response->json($mediumArray);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(1, $stats['total_operations'], 'Arrays at threshold should use pooling');
    }

    /**
     * Test object pooling threshold consistency
     */
    public function testObjectPoolingThreshold(): void
    {
        $response = new Response();
        $response->setTestMode(true);

        // Create object just below threshold
        $smallObject = new \stdClass();
        for ($i = 0; $i < JsonBufferPool::POOLING_OBJECT_THRESHOLD - 1; $i++) {
            $smallObject->{"prop{$i}"} = "value{$i}";
        }

        $response->json($smallObject);
        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $stats['total_operations'], 'Small objects should not use pooling');

        // Reset
        JsonBufferPool::clearPools();
        $response = new Response();
        $response->setTestMode(true);

        // Create object at threshold
        $mediumObject = new \stdClass();
        for ($i = 0; $i < JsonBufferPool::POOLING_OBJECT_THRESHOLD; $i++) {
            $mediumObject->{"prop{$i}"} = "value{$i}";
        }

        $response->json($mediumObject);
        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(1, $stats['total_operations'], 'Objects at threshold should use pooling');
    }

    /**
     * Test string pooling threshold consistency
     */
    public function testStringPoolingThreshold(): void
    {
        $response = new Response();
        $response->setTestMode(true);

        // String just under threshold
        $shortString = str_repeat('x', JsonBufferPool::POOLING_STRING_THRESHOLD - 1);
        $response->json($shortString);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $stats['total_operations'], 'Short strings should not use pooling');

        // Reset
        JsonBufferPool::clearPools();
        $response = new Response();
        $response->setTestMode(true);

        // String over threshold
        $longString = str_repeat('x', JsonBufferPool::POOLING_STRING_THRESHOLD + 1);
        $response->json($longString);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(1, $stats['total_operations'], 'Long strings should use pooling');
    }

    /**
     * Test that thresholds are reasonable for performance
     */
    public function testThresholdsAreReasonable(): void
    {
        // Array threshold should be high enough to avoid pooling small arrays
        $this->assertGreaterThanOrEqual(5, JsonBufferPool::POOLING_ARRAY_THRESHOLD);
        $this->assertLessThanOrEqual(50, JsonBufferPool::POOLING_ARRAY_THRESHOLD);

        // Object threshold should be reasonable for common objects
        $this->assertGreaterThanOrEqual(3, JsonBufferPool::POOLING_OBJECT_THRESHOLD);
        $this->assertLessThanOrEqual(20, JsonBufferPool::POOLING_OBJECT_THRESHOLD);

        // String threshold should be reasonable (around 1KB)
        $this->assertGreaterThanOrEqual(512, JsonBufferPool::POOLING_STRING_THRESHOLD);
        $this->assertLessThanOrEqual(4096, JsonBufferPool::POOLING_STRING_THRESHOLD);
    }

    /**
     * Test consistency between direct pooling and Response pooling
     */
    public function testConsistencyBetweenDirectAndResponsePooling(): void
    {
        $testData = array_fill(0, self::TEST_DATA_SIZE, 'test'); // Use TEST_DATA_SIZE elements to ensure pooling

        // Direct pooling
        JsonBufferPool::clearPools();
        $directResult = JsonBufferPool::encodeWithPool($testData);
        $directStats = JsonBufferPool::getStatistics();

        // Response pooling
        JsonBufferPool::clearPools();
        $response = new Response();
        $response->setTestMode(true);
        $response->json($testData);
        $responseResult = (string) $response->getBody();
        $responseStats = JsonBufferPool::getStatistics();

        // Results should be identical
        $this->assertEquals($directResult, $responseResult);

        // Both should have used pooling
        $this->assertEquals(1, $directStats['total_operations']);
        $this->assertEquals(1, $responseStats['total_operations']);
    }
}
