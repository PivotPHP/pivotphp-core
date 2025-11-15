<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Pool\SimplePoolManager;

class SimplePoolManagerTest extends TestCase
{
    private SimplePoolManager $poolManager;

    protected function setUp(): void
    {
        $this->poolManager = SimplePoolManager::getInstance();
        $this->poolManager->clearAll();
        $this->poolManager->enable();
    }

    public function testSingletonPattern(): void
    {
        $instance1 = SimplePoolManager::getInstance();
        $instance2 = SimplePoolManager::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testRentReturnsNullForEmptyPool(): void
    {
        $object = $this->poolManager->rent('request');

        $this->assertNull($object);
    }

    public function testReturnAndRentObject(): void
    {
        $testObject = new \stdClass();
        $testObject->id = 'test';

        $this->poolManager->return('request', $testObject);
        $rentedObject = $this->poolManager->rent('request');

        $this->assertSame($testObject, $rentedObject);
        $this->assertEquals('test', $rentedObject->id);
    }

    public function testPoolSizeLimit(): void
    {
        $this->poolManager->setMaxPoolSize(2);

        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $this->poolManager->return('request', $obj1);
        $this->poolManager->return('request', $obj2);
        $this->poolManager->return('request', $obj3); // Should be ignored

        $this->assertEquals(2, $this->poolManager->getPoolSize('request'));
    }

    public function testDisablePooling(): void
    {
        $testObject = new \stdClass();

        $this->poolManager->disable();
        $this->poolManager->return('request', $testObject);

        $this->assertEquals(0, $this->poolManager->getPoolSize('request'));

        $rentedObject = $this->poolManager->rent('request');
        $this->assertNull($rentedObject);
    }

    public function testClearSpecificPool(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $this->poolManager->return('request', $obj1);
        $this->poolManager->return('response', $obj2);

        $this->assertEquals(1, $this->poolManager->getPoolSize('request'));
        $this->assertEquals(1, $this->poolManager->getPoolSize('response'));

        $this->poolManager->clearPool('request');

        $this->assertEquals(0, $this->poolManager->getPoolSize('request'));
        $this->assertEquals(1, $this->poolManager->getPoolSize('response'));
    }

    public function testClearAllPools(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $this->poolManager->return('request', $obj1);
        $this->poolManager->return('response', $obj2);

        $this->poolManager->clearAll();

        $this->assertEquals(0, $this->poolManager->getPoolSize('request'));
        $this->assertEquals(0, $this->poolManager->getPoolSize('response'));
    }

    public function testHasPool(): void
    {
        $this->assertTrue($this->poolManager->hasPool('request'));
        $this->assertTrue($this->poolManager->hasPool('response'));
        $this->assertTrue($this->poolManager->hasPool('stream'));
        $this->assertFalse($this->poolManager->hasPool('nonexistent'));
    }

    public function testGetStats(): void
    {
        $this->poolManager->setMaxPoolSize(10);

        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $this->poolManager->return('request', $obj1);
        $this->poolManager->return('response', $obj2);

        $stats = $this->poolManager->getStats();

        $this->assertIsArray($stats);
        $this->assertTrue($stats['enabled']);
        $this->assertEquals(10, $stats['max_pool_size']);
        $this->assertArrayHasKey('pools', $stats);

        $this->assertEquals(1, $stats['pools']['request']['size']);
        $this->assertEquals(0.1, $stats['pools']['request']['utilization']);
        $this->assertEquals(1, $stats['pools']['response']['size']);
        $this->assertEquals(0.1, $stats['pools']['response']['utilization']);
    }

    public function testStatsWhenDisabled(): void
    {
        $this->poolManager->disable();
        $stats = $this->poolManager->getStats();

        $this->assertFalse($stats['enabled']);
    }
}
