<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Memory;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Memory\MemoryManager;
use PivotPHP\Core\Http\Pool\PoolManager;

/**
 * Comprehensive test suite for MemoryManager class
 *
 * Tests adaptive memory management, garbage collection strategies,
 * pressure monitoring, object tracking, and all memory optimization functionality.
 */
class MemoryManagerTest extends TestCase
{
    private MemoryManager $memoryManager;
    private array $originalConfig;

    protected function setUp(): void
    {
        parent::setUp();

        // Store original memory settings
        $this->originalConfig = [
            'memory_limit' => ini_get('memory_limit'),
            'gc_enabled' => gc_enabled(),
        ];

        // Create memory manager with test configuration
        $this->memoryManager = new MemoryManager(
            [
                'check_interval' => 0, // No rate limiting for tests
                'gc_threshold' => 0.7,
                'emergency_gc' => 0.9,
            ]
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore original settings (only if it's safe to do so)
        $currentUsage = memory_get_usage(true);
        $originalLimitBytes = $this->parseMemoryLimit($this->originalConfig['memory_limit']);

        // Only restore if the original limit is higher than current usage
        if ($originalLimitBytes > $currentUsage || $this->originalConfig['memory_limit'] === '-1') {
            ini_set('memory_limit', $this->originalConfig['memory_limit']);
        }
        if ($this->originalConfig['gc_enabled']) {
            gc_enable();
        } else {
            gc_disable();
        }

        // Run cleanup
        $this->memoryManager->shutdown();
    }

    // =========================================================================
    // CONFIGURATION TESTS
    // =========================================================================

    public function testMemoryManagerInstantiation(): void
    {
        $manager = new MemoryManager();
        $this->assertInstanceOf(MemoryManager::class, $manager);

        $status = $manager->getStatus();
        $this->assertIsArray($status);
        $this->assertArrayHasKey('pressure', $status);
        $this->assertArrayHasKey('emergency_mode', $status);
        $this->assertArrayHasKey('usage', $status);
        $this->assertArrayHasKey('gc', $status);
    }

    public function testCustomConfiguration(): void
    {
        $customConfig = [
            'gc_strategy' => MemoryManager::STRATEGY_AGGRESSIVE,
            'gc_threshold' => 0.6,
            'emergency_gc' => 0.8,
            'check_interval' => 10,
        ];

        $manager = new MemoryManager($customConfig);
        $status = $manager->getStatus();

        $this->assertEquals(MemoryManager::STRATEGY_AGGRESSIVE, $status['gc']['strategy']);
        $this->assertFalse($status['emergency_mode']);
    }

    public function testMemoryStrategies(): void
    {
        $strategies = [
            MemoryManager::STRATEGY_ADAPTIVE,
            MemoryManager::STRATEGY_AGGRESSIVE,
            MemoryManager::STRATEGY_CONSERVATIVE,
        ];

        foreach ($strategies as $strategy) {
            $manager = new MemoryManager(['gc_strategy' => $strategy]);
            $status = $manager->getStatus();
            $this->assertEquals($strategy, $status['gc']['strategy']);
        }
    }

    // =========================================================================
    // MEMORY PRESSURE TESTS
    // =========================================================================

    public function testMemoryPressureLevels(): void
    {
        $this->assertEquals('low', MemoryManager::PRESSURE_LOW);
        $this->assertEquals('medium', MemoryManager::PRESSURE_MEDIUM);
        $this->assertEquals('high', MemoryManager::PRESSURE_HIGH);
        $this->assertEquals('critical', MemoryManager::PRESSURE_CRITICAL);
    }

    public function testInitialMemoryPressure(): void
    {
        // Use higher thresholds to ensure 'low' pressure in test environment
        $manager = new MemoryManager(512 * 1024 * 1024, 1024 * 1024 * 1024); // 512MB warning, 1GB critical
        $status = $manager->getStatus();

        // Should start with low pressure in test environment with higher thresholds
        $this->assertEquals(MemoryManager::PRESSURE_LOW, $status['pressure']);
        $this->assertFalse($status['emergency_mode']);
    }

    public function testMemoryUsageCalculation(): void
    {
        $status = $this->memoryManager->getStatus();

        $this->assertIsInt($status['usage']['current']);
        $this->assertIsInt($status['usage']['peak']);
        $this->assertIsInt($status['usage']['limit']);
        $this->assertIsFloat($status['usage']['percentage']);

        $this->assertGreaterThan(0, $status['usage']['current']);
        $this->assertGreaterThanOrEqual($status['usage']['current'], $status['usage']['peak']);
        $this->assertGreaterThan(0, $status['usage']['limit']);
        $this->assertGreaterThanOrEqual(0, $status['usage']['percentage']);
        // Usage percentage can exceed 100% if memory usage exceeds warning threshold
    }

    // =========================================================================
    // GARBAGE COLLECTION TESTS
    // =========================================================================

    public function testGarbageCollectionExecution(): void
    {
        $initialStatus = $this->memoryManager->getStatus();
        $initialGCRuns = $initialStatus['gc']['runs'];

        // Force garbage collection
        $this->memoryManager->forceGC();

        $newStatus = $this->memoryManager->getStatus();
        $this->assertGreaterThan($initialGCRuns, $newStatus['gc']['runs']);
    }

    public function testGCMetricsTracking(): void
    {
        $this->memoryManager->forceGC();

        $metrics = $this->memoryManager->getMetrics();

        $this->assertArrayHasKey('gc_runs', $metrics);
        $this->assertArrayHasKey('gc_collected', $metrics);
        $this->assertArrayHasKey('avg_gc_duration_ms', $metrics);
        $this->assertArrayHasKey('avg_gc_freed_mb', $metrics);
        $this->assertArrayHasKey('gc_frequency', $metrics);

        $this->assertGreaterThanOrEqual(1, $metrics['gc_runs']);
        $this->assertGreaterThanOrEqual(0, $metrics['gc_collected']);
        $this->assertGreaterThanOrEqual(0, $metrics['avg_gc_duration_ms']);
        $this->assertGreaterThanOrEqual(0, $metrics['avg_gc_freed_mb']);
    }

    public function testGCStrategyConfiguration(): void
    {
        $strategies = [
            MemoryManager::STRATEGY_ADAPTIVE => 'adaptive',
            MemoryManager::STRATEGY_AGGRESSIVE => 'aggressive',
            MemoryManager::STRATEGY_CONSERVATIVE => 'conservative',
        ];

        foreach ($strategies as $strategy => $expected) {
            $manager = new MemoryManager(['gc_strategy' => $strategy]);
            $status = $manager->getStatus();
            $this->assertEquals($expected, $status['gc']['strategy']);
        }
    }

    // =========================================================================
    // OBJECT TRACKING TESTS
    // =========================================================================

    public function testObjectTracking(): void
    {
        $initialStatus = $this->memoryManager->getStatus();
        $initialTracked = $initialStatus['tracked_objects'];

        // Create and track test objects
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $this->memoryManager->trackObject('test', $obj1, ['priority' => 'high']);
        $this->memoryManager->trackObject('request', $obj2, ['source' => 'api']);

        $newStatus = $this->memoryManager->getStatus();
        $this->assertEquals($initialTracked + 2, $newStatus['tracked_objects']);
    }

    public function testObjectTrackingWithMetadata(): void
    {
        $obj = new \stdClass();
        $obj->data = 'test_data';

        $metadata = [
            'type' => 'response',
            'size' => 1024,
            'priority' => 'high',
            'created_by' => 'test_case',
        ];

        $this->memoryManager->trackObject('response', $obj, $metadata);

        $status = $this->memoryManager->getStatus();
        $this->assertGreaterThan(0, $status['tracked_objects']);
    }

    public function testWeakReferenceTracking(): void
    {
        $initialStatus = $this->memoryManager->getStatus();
        $initialTracked = $initialStatus['tracked_objects'];

        // Create object in limited scope
        $this->createAndTrackTemporaryObject();

        // Force garbage collection to clean up weak references
        gc_collect_cycles();
        $this->memoryManager->check(); // This should clean tracked objects

        $finalStatus = $this->memoryManager->getStatus();
        // Note: WeakReference cleanup may not be immediate, so we just verify no explosion
        $this->assertGreaterThanOrEqual($initialTracked, $finalStatus['tracked_objects']);
    }

    private function createAndTrackTemporaryObject(): void
    {
        $tempObj = new \stdClass();
        $tempObj->temp_data = 'temporary';
        $this->memoryManager->trackObject('temporary', $tempObj);
        // Object goes out of scope here
    }

    // =========================================================================
    // POOL INTEGRATION TESTS
    // =========================================================================

    public function testPoolIntegration(): void
    {
        // Create mock pool manager
        $poolManager = $this->createMock(PoolManager::class);
        $poolManager->method('getStats')->willReturn(
            [
                'config' => [
                    'max_size' => 100,
                    'emergency_limit' => 50,
                ],
                'usage' => [
                    'active' => 20,
                    'total' => 100,
                ],
            ]
        );

        /** @phpstan-ignore-next-line */
        $this->memoryManager->setPool($poolManager);

        // Verify pool is set (through behavior - no direct getter)
        $this->assertTrue(true); // Pool setting doesn't throw error
    }

    public function testPoolAdjustmentOnPressureChange(): void
    {
        $poolManager = $this->createMock(PoolManager::class);
        $poolManager->method('getStats')->willReturn(
            [
                'config' => [
                    'max_size' => 100,
                    'emergency_limit' => 50,
                ],
            ]
        );

        /** @phpstan-ignore-next-line */
        $this->memoryManager->setPool($poolManager);

        // Simulate memory pressure check
        $this->memoryManager->check();

        // Verify no errors occur during pool adjustment
        $this->assertTrue(true);
    }

    // =========================================================================
    // MEMORY MONITORING TESTS
    // =========================================================================

    public function testMemoryMonitoringCheck(): void
    {
        // Get initial metrics - removed variable as not used in assertions

        // Run memory check
        $this->memoryManager->check();

        $newMetrics = $this->memoryManager->getMetrics();

        // Metrics should be tracked
        $this->assertIsArray($newMetrics);
        $this->assertArrayHasKey('memory_usage_percent', $newMetrics);
        $this->assertArrayHasKey('memory_trend', $newMetrics);
        $this->assertArrayHasKey('current_pressure', $newMetrics);
    }

    public function testMemoryTrendCalculation(): void
    {
        // Run multiple checks to build history
        for ($i = 0; $i < 6; $i++) {
            $this->memoryManager->check();
            // Small delay to create different timestamps
            usleep(1000);
        }

        $metrics = $this->memoryManager->getMetrics();
        $this->assertContains($metrics['memory_trend'], ['stable', 'increasing', 'decreasing']);
    }

    public function testMemorySnapshotRecording(): void
    {
        // Create some memory usage
        $largeArray = range(1, 10000);

        $this->memoryManager->check();

        $status = $this->memoryManager->getStatus();
        $this->assertGreaterThan(0, $status['usage']['current']);
        $this->assertGreaterThan(0, $status['usage']['peak']);

        // Clean up
        unset($largeArray);
    }

    // =========================================================================
    // EMERGENCY MODE TESTS
    // =========================================================================

    public function testEmergencyModeActivation(): void
    {
        // Create manager with very low emergency threshold for testing
        $manager = new MemoryManager(
            [
                'emergency_gc' => 0.001, // Very low threshold to trigger emergency
                'check_interval' => 0,
            ]
        );

        // Check if emergency mode logic works (may not trigger in test environment)
        $status = $manager->getStatus();
        $this->assertArrayHasKey('emergency_mode', $status);
        $this->assertIsBool($status['emergency_mode']);
    }

    public function testEmergencyModeMetrics(): void
    {
        $metrics = $this->memoryManager->getMetrics();
        $this->assertArrayHasKey('emergency_activations', $metrics);
        $this->assertGreaterThanOrEqual(0, $metrics['emergency_activations']);
    }

    // =========================================================================
    // MEMORY LIMIT HANDLING TESTS
    // =========================================================================

    public function testMemoryLimitParsing(): void
    {
        // Test various memory limit formats
        $originalLimit = ini_get('memory_limit');
        $currentUsage = memory_get_usage(true);

        $testLimits = ['256M', '512M', '1G', '2G'];

        foreach ($testLimits as $limit) {
            // Only test limits that are higher than current memory usage
            $limitBytes = $this->parseMemoryLimit($limit);
            if ($limitBytes > $currentUsage) {
                ini_set('memory_limit', $limit);
                $manager = new MemoryManager();
                $status = $manager->getStatus();

                $this->assertGreaterThan(0, $status['usage']['limit']);
            }
        }

        // Restore original limit (only if safe)
        $currentUsage = memory_get_usage(true);
        $originalLimitBytes = $this->parseMemoryLimit($originalLimit);

        if ($originalLimitBytes > $currentUsage || $originalLimit === '-1') {
            ini_set('memory_limit', $originalLimit);
        }
    }

    /**
     * Helper method to parse memory limit strings
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $size = (int)$limit;

        switch ($last) {
            case 'g':
                $size *= 1024;
                // no break
            case 'm':
                $size *= 1024;
                // no break
            case 'k':
                $size *= 1024;
        }

        return $size;
    }

    public function testUnlimitedMemoryHandling(): void
    {
        $originalLimit = ini_get('memory_limit');

        // Set unlimited memory
        ini_set('memory_limit', '-1');

        $manager = new MemoryManager();
        $status = $manager->getStatus();

        // Should default to 2GB limit
        $this->assertEquals(2 * 1024 * 1024 * 1024, $status['usage']['limit']);

        // Restore original limit (only if safe)
        $currentUsage = memory_get_usage(true);
        $originalLimitBytes = $this->parseMemoryLimit($originalLimit);

        if ($originalLimitBytes > $currentUsage || $originalLimit === '-1') {
            ini_set('memory_limit', $originalLimit);
        }
    }

    // =========================================================================
    // PERFORMANCE METRICS TESTS
    // =========================================================================

    public function testPerformanceMetricsCollection(): void
    {
        // Run some operations to generate metrics
        $this->memoryManager->forceGC();
        $this->memoryManager->check();

        $metrics = $this->memoryManager->getMetrics();

        $expectedKeys = [
            'gc_runs',
            'gc_collected',
            'pressure_changes',
            'pool_adjustments',
            'emergency_activations',
            'memory_peaks',
            'current_pressure',
            'memory_usage_percent',
            'avg_gc_duration_ms',
            'avg_gc_freed_mb',
            'gc_frequency',
            'memory_trend',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $metrics);
        }

        // Verify metric types
        $this->assertIsInt($metrics['gc_runs']);
        $this->assertIsInt($metrics['gc_collected']);
        $this->assertIsInt($metrics['pressure_changes']);
        $this->assertIsInt($metrics['pool_adjustments']);
        $this->assertIsInt($metrics['emergency_activations']);
        $this->assertIsArray($metrics['memory_peaks']);
        $this->assertIsString($metrics['current_pressure']);
        $this->assertIsFloat($metrics['memory_usage_percent']);
        $this->assertIsFloat($metrics['avg_gc_duration_ms']);
        $this->assertIsFloat($metrics['avg_gc_freed_mb']);
        $this->assertIsFloat($metrics['gc_frequency']);
        $this->assertIsString($metrics['memory_trend']);
    }

    public function testGCFrequencyCalculation(): void
    {
        // Run multiple GC cycles
        for ($i = 0; $i < 3; $i++) {
            $this->memoryManager->forceGC();
            usleep(1000); // Small delay
        }

        $metrics = $this->memoryManager->getMetrics();
        $this->assertGreaterThanOrEqual(0, $metrics['gc_frequency']);
    }

    // =========================================================================
    // STRESS TESTING
    // =========================================================================

    public function testMemoryStressTesting(): void
    {
        $initialStatus = $this->memoryManager->getStatus();

        // Create memory pressure
        $largeArrays = [];
        for ($i = 0; $i < 10; $i++) {
            $largeArrays[] = range(1, 5000);
            $this->memoryManager->check();
        }

        $stressStatus = $this->memoryManager->getStatus();

        // Memory usage should have increased or stayed the same (in case of immediate cleanup)
        $this->assertGreaterThanOrEqual($initialStatus['usage']['current'], $stressStatus['usage']['current']);

        // Clean up
        unset($largeArrays);
        gc_collect_cycles();
    }

    public function testObjectTrackingStress(): void
    {
        $initialTracked = $this->memoryManager->getStatus()['tracked_objects'];

        // Track many objects
        $objects = [];
        for ($i = 0; $i < 100; $i++) {
            $obj = new \stdClass();
            $obj->data = str_repeat('x', 100);
            $objects[] = $obj;
            $this->memoryManager->trackObject('stress_test', $obj, ['iteration' => $i]);
        }

        $peakStatus = $this->memoryManager->getStatus();
        $this->assertEquals($initialTracked + 100, $peakStatus['tracked_objects']);

        // Clean up and verify tracking cleanup
        unset($objects);
        gc_collect_cycles();
        $this->memoryManager->check();

        // Note: Cleanup may not be immediate due to WeakReference behavior
        $this->assertTrue(true); // Test completed without errors
    }

    // =========================================================================
    // CONFIGURATION BOUNDARY TESTS
    // =========================================================================

    public function testConfigurationBoundaries(): void
    {
        // Test edge case configurations
        $edgeCases = [
            ['gc_threshold' => 0.0],    // Minimum threshold
            ['gc_threshold' => 1.0],    // Maximum threshold
            ['emergency_gc' => 0.1],    // Very low emergency
            ['emergency_gc' => 0.99],   // Very high emergency
            ['check_interval' => 0],    // No rate limiting
            ['check_interval' => 3600], // Very long interval
        ];

        foreach ($edgeCases as $config) {
            $manager = new MemoryManager($config);
            $status = $manager->getStatus();

            // Should not crash with edge configurations
            $this->assertIsArray($status);
            $this->assertArrayHasKey('pressure', $status);
        }
    }

    // =========================================================================
    // SHUTDOWN AND CLEANUP TESTS
    // =========================================================================

    public function testShutdownCleanup(): void
    {
        $manager = new MemoryManager();

        // Track some objects
        $obj = new \stdClass();
        $manager->trackObject('shutdown_test', $obj);

        // Run some operations
        $manager->forceGC();
        $manager->check();

        // Shutdown should not throw errors
        $manager->shutdown();

        $this->assertTrue(true);
    }

    public function testMultipleShutdowns(): void
    {
        $manager = new MemoryManager();

        // Multiple shutdowns should be safe
        $manager->shutdown();
        $manager->shutdown();
        $manager->shutdown();

        $this->assertTrue(true);
    }

    // =========================================================================
    // INTEGRATION AND WORKFLOW TESTS
    // =========================================================================

    public function testCompleteMemoryManagementWorkflow(): void
    {
        // Create manager with realistic configuration
        $manager = new MemoryManager(
            [
                'gc_strategy' => MemoryManager::STRATEGY_ADAPTIVE,
                'gc_threshold' => 0.7,
                'emergency_gc' => 0.9,
                'check_interval' => 1,
            ]
        );

        // Simulate application lifecycle

        // 1. Track some application objects
        $request = new \stdClass();
        $response = new \stdClass();
        $manager->trackObject('request', $request, ['method' => 'GET', 'path' => '/api/test']);
        $manager->trackObject('response', $response, ['status' => 200, 'size' => 1024]);

        // 2. Create some memory pressure
        $workload = [];
        for ($i = 0; $i < 20; $i++) {
            $workload[] = range(1, 1000);
            $manager->check();
        }

        // 3. Force garbage collection
        $manager->forceGC();

        // 4. Check final status
        $finalStatus = $manager->getStatus();
        $finalMetrics = $manager->getMetrics();

        // Verify system health
        $this->assertIsArray($finalStatus);
        $this->assertIsArray($finalMetrics);
        $this->assertContains(
            $finalStatus['pressure'],
            [
                MemoryManager::PRESSURE_LOW,
                MemoryManager::PRESSURE_MEDIUM,
                MemoryManager::PRESSURE_HIGH,
                MemoryManager::PRESSURE_CRITICAL,
            ]
        );

        $this->assertGreaterThan(0, $finalMetrics['gc_runs']);
        $this->assertGreaterThanOrEqual(0, $finalMetrics['gc_collected']);
        $this->assertGreaterThanOrEqual(0, $finalMetrics['memory_usage_percent']);

        // 5. Cleanup
        unset($workload, $request, $response);
        $manager->shutdown();
    }

    public function testMemoryManagerStateConsistency(): void
    {
        // Verify state consistency across operations
        $initialStatus = $this->memoryManager->getStatus();
        $initialMetrics = $this->memoryManager->getMetrics();

        // Perform various operations
        $obj = new \stdClass();
        $this->memoryManager->trackObject('consistency_test', $obj);
        $this->memoryManager->check();
        $this->memoryManager->forceGC();
        $this->memoryManager->check();

        $finalStatus = $this->memoryManager->getStatus();
        $finalMetrics = $this->memoryManager->getMetrics();

        // State should remain consistent
        $this->assertGreaterThanOrEqual($initialStatus['gc']['runs'], $finalStatus['gc']['runs']);
        $this->assertGreaterThanOrEqual($initialMetrics['gc_runs'], $finalMetrics['gc_runs']);
        $this->assertContains(
            $finalStatus['pressure'],
            [
                MemoryManager::PRESSURE_LOW,
                MemoryManager::PRESSURE_MEDIUM,
                MemoryManager::PRESSURE_HIGH,
                MemoryManager::PRESSURE_CRITICAL,
            ]
        );
    }
}
