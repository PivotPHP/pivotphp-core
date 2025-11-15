<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Memory;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Memory\SimpleMemoryManager;

class SimpleMemoryManagerTest extends TestCase
{
    private SimpleMemoryManager $memoryManager;

    protected function setUp(): void
    {
        $this->memoryManager = new SimpleMemoryManager();
    }

    public function testCheckMemoryUsage(): void
    {
        $usage = $this->memoryManager->checkMemoryUsage();

        $this->assertIsArray($usage);
        $this->assertArrayHasKey('current_usage', $usage);
        $this->assertArrayHasKey('peak_usage', $usage);
        $this->assertArrayHasKey('status', $usage);
        $this->assertArrayHasKey('warning_threshold', $usage);
        $this->assertArrayHasKey('critical_threshold', $usage);

        $this->assertIsInt($usage['current_usage']);
        $this->assertIsInt($usage['peak_usage']);
        $this->assertContains($usage['status'], ['normal', 'warning', 'critical']);
    }

    public function testCustomThresholds(): void
    {
        $manager = new SimpleMemoryManager(1000, 2000);
        $usage = $manager->checkMemoryUsage();

        $this->assertEquals(1000, $usage['warning_threshold']);
        $this->assertEquals(2000, $usage['critical_threshold']);
    }

    public function testPerformGarbageCollection(): void
    {
        $result = $this->memoryManager->performGarbageCollection();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('memory_before', $result);
        $this->assertArrayHasKey('memory_after', $result);
        $this->assertArrayHasKey('memory_freed', $result);
        $this->assertArrayHasKey('cycles_collected', $result);

        $this->assertIsInt($result['memory_before']);
        $this->assertIsInt($result['memory_after']);
        $this->assertIsInt($result['memory_freed']);
        $this->assertIsInt($result['cycles_collected']);
    }

    public function testAutoGcCanBeDisabled(): void
    {
        $this->memoryManager->disableAutoGc();

        // This should not trigger auto GC even with high memory usage
        $usage = $this->memoryManager->checkMemoryUsage();
        $this->assertIsArray($usage);
    }

    public function testAutoGcCanBeEnabled(): void
    {
        $this->memoryManager->disableAutoGc();
        $this->memoryManager->enableAutoGc();

        $usage = $this->memoryManager->checkMemoryUsage();
        $this->assertIsArray($usage);
    }

    public function testGetStats(): void
    {
        $stats = $this->memoryManager->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('memory_peak', $stats);
        $this->assertArrayHasKey('memory_status', $stats);
        $this->assertArrayHasKey('auto_gc_enabled', $stats);
        $this->assertArrayHasKey('formatted_usage', $stats);
        $this->assertArrayHasKey('formatted_peak', $stats);

        $this->assertIsInt($stats['memory_usage']);
        $this->assertIsInt($stats['memory_peak']);
        $this->assertIsString($stats['memory_status']);
        $this->assertIsBool($stats['auto_gc_enabled']);
        $this->assertIsString($stats['formatted_usage']);
        $this->assertIsString($stats['formatted_peak']);
    }

    public function testFormattedMemoryValues(): void
    {
        $stats = $this->memoryManager->getStats();

        // Should contain units (B, KB, MB, GB)
        $this->assertMatchesRegularExpression('/\d+(\.\d+)?\s*(B|KB|MB|GB)/', $stats['formatted_usage']);
        $this->assertMatchesRegularExpression('/\d+(\.\d+)?\s*(B|KB|MB|GB)/', $stats['formatted_peak']);
    }
}
