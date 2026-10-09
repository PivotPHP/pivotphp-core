<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Testa que Response::json() NÃO usa mais o JsonBufferPool por padrão
 * (SPEC-044 — o pool é mais lento que json_encode e não traz ganho em PHP-FPM).
 */
class JsonPoolingThresholdsTest extends TestCase
{
    /**
     * Test data size that guarantees pooling would have been triggered
     * under the old behavior.
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
     * Test that pooling constants are properly defined (component deprecated, not removed).
     */
    public function testPoolingConstantsExist(): void
    {
        $reflection = new \ReflectionClass(JsonBufferPool::class);
        $this->assertTrue($reflection->hasConstant('POOLING_ARRAY_THRESHOLD'));
        $this->assertTrue($reflection->hasConstant('POOLING_OBJECT_THRESHOLD'));
        $this->assertTrue($reflection->hasConstant('POOLING_STRING_THRESHOLD'));

        $this->assertEquals(10, JsonBufferPool::POOLING_ARRAY_THRESHOLD);
        $this->assertEquals(5, JsonBufferPool::POOLING_OBJECT_THRESHOLD);
        $this->assertEquals(1024, JsonBufferPool::POOLING_STRING_THRESHOLD);
    }

    /**
     * Test that Response::json() does not use JsonBufferPool by default.
     */
    public function testResponseDoesNotUsePoolingByDefault(): void
    {
        $response = new Response();
        $response->setTestMode(true);

        $mediumArray = array_fill(0, self::TEST_DATA_SIZE, 'item');
        $response->json($mediumArray);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $stats['total_operations'], 'Response::json() should not use the pool');
    }

    /**
     * Test that Response::json() output matches json_encode.
     */
    public function testResponseMatchesJsonEncode(): void
    {
        $testData = [
            'items' => array_fill(0, self::TEST_DATA_SIZE, 'test'),
            'nested' => ['a' => 1, 'b' => [1, 2, 3]],
        ];

        $response = new Response();
        $response->setTestMode(true);
        $response->json($testData);

        $this->assertSame(
            json_encode($testData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response->getBodyAsString()
        );
    }

    /**
     * Test that thresholds are reasonable for performance
     */
    public function testThresholdsAreReasonable(): void
    {
        $this->assertGreaterThanOrEqual(5, JsonBufferPool::POOLING_ARRAY_THRESHOLD);
        $this->assertLessThanOrEqual(50, JsonBufferPool::POOLING_ARRAY_THRESHOLD);

        $this->assertGreaterThanOrEqual(3, JsonBufferPool::POOLING_OBJECT_THRESHOLD);
        $this->assertLessThanOrEqual(20, JsonBufferPool::POOLING_OBJECT_THRESHOLD);

        $this->assertGreaterThanOrEqual(512, JsonBufferPool::POOLING_STRING_THRESHOLD);
        $this->assertLessThanOrEqual(4096, JsonBufferPool::POOLING_STRING_THRESHOLD);
    }

    /**
     * Test that shouldUseJsonPooling() returns false without an injected optimizer.
     */
    public function testShouldUseJsonPoolingDisabledWithoutOptimizer(): void
    {
        $reflection = new \ReflectionClass(Response::class);
        $shouldUsePoolingMethod = $reflection->getMethod('shouldUseJsonPooling');
        $shouldUsePoolingMethod->setAccessible(true);

        $response = new Response();

        $arrayAtThreshold = array_fill(0, self::TEST_DATA_SIZE, 'item');
        $objectAtThreshold = new \stdClass();
        for ($i = 0; $i < JsonBufferPool::POOLING_OBJECT_THRESHOLD; $i++) {
            $objectAtThreshold->{"prop{$i}"} = "value{$i}";
        }
        $stringAtThreshold = str_repeat('x', JsonBufferPool::POOLING_STRING_THRESHOLD + 1);

        $this->assertFalse($shouldUsePoolingMethod->invoke($response, $arrayAtThreshold));
        $this->assertFalse($shouldUsePoolingMethod->invoke($response, $objectAtThreshold));
        $this->assertFalse($shouldUsePoolingMethod->invoke($response, $stringAtThreshold));
    }
}
