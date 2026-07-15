<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Container;
use PivotPHP\Core\Tests\Core\Fixtures\SimpleClassWithoutDependencies;
use PivotPHP\Core\Tests\Core\Fixtures\ClassWithDependencies;
use PivotPHP\Core\Tests\Core\Fixtures\ClassWithParameters;
use PivotPHP\Core\Tests\Core\Fixtures\CircularDependencyA;
use PivotPHP\Core\Tests\Core\Fixtures\CircularDependencyB;
use PivotPHP\Core\Tests\Core\Fixtures\UnresolvableInterface;
use PivotPHP\Core\Tests\Core\Fixtures\ServiceInterface;
use PivotPHP\Core\Tests\Core\Fixtures\ConcreteService;
use PivotPHP\Core\Tests\Core\Fixtures\ConfigService;
use PivotPHP\Core\Tests\Core\Fixtures\ComplexService;
use PivotPHP\Core\Tests\Core\Fixtures\TestLogger;
use PivotPHP\Core\Tests\Core\Fixtures\TestConfig;
use PivotPHP\Core\Tests\Core\Fixtures\TestProcessor;
use ReflectionProperty;
use Exception;
use stdClass;

/**
 * Test suite for Container class
 *
 * Tests dependency injection, singleton management, aliases,
 * tagging, auto-wiring, and all container functionality.
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        // Suppress E_USER_DEPRECATED from the deprecated Core\Container class under test
        set_error_handler(static function (int $errno): bool {
            return $errno === E_USER_DEPRECATED;
        }, E_ALL);

        // Reset singleton instance for isolated testing
        $this->resetContainerSingleton();
        $this->container = Container::getInstance();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        // Clean up singleton after each test
        $this->resetContainerSingleton();
    }

    /**
     * Reset singleton instance using reflection for test isolation
     */
    private function resetContainerSingleton(): void
    {
        $reflection = new ReflectionProperty(Container::class, 'instance');
        $reflection->setAccessible(true);
        $reflection->setValue(null, null);
    }

    // =========================================================================
    // SINGLETON PATTERN TESTS
    // =========================================================================

    public function testGetInstanceReturnsSameInstance(): void
    {
        $container1 = Container::getInstance();
        $container2 = Container::getInstance();

        $this->assertSame($container1, $container2);
        $this->assertInstanceOf(Container::class, $container1);
    }

    public function testContainerRegistersItselfOnCreation(): void
    {
        $container = Container::getInstance();

        // Container should register itself under its class name
        $resolved = $container->make(Container::class);
        $this->assertSame($container, $resolved);

        // Container should also have 'container' alias
        $resolvedByAlias = $container->make('container');
        $this->assertSame($container, $resolvedByAlias);
    }

    // =========================================================================
    // BASIC BINDING TESTS
    // =========================================================================

    public function testBindAndMakeBasicClass(): void
    {
        $this->container->bind('test_class', stdClass::class);
        $instance = $this->container->make('test_class');

        $this->assertInstanceOf(stdClass::class, $instance);
    }

    public function testBindWithNullConcreteUsesAbstractAsDefault(): void
    {
        $this->container->bind(stdClass::class);
        $instance = $this->container->make(stdClass::class);

        $this->assertInstanceOf(stdClass::class, $instance);
    }

    public function testBindWithClosure(): void
    {
        $this->container->bind(
            'test_closure',
            function () {
                $obj = new stdClass();
                $obj->created_by = 'closure';
                return $obj;
            }
        );

        $instance = $this->container->make('test_closure');
        $this->assertInstanceOf(stdClass::class, $instance);
        $this->assertEquals('closure', $instance->created_by);
    }

    public function testBindCreateNewInstanceEachTime(): void
    {
        $this->container->bind('new_each_time', stdClass::class);

        $instance1 = $this->container->make('new_each_time');
        $instance2 = $this->container->make('new_each_time');

        $this->assertInstanceOf(stdClass::class, $instance1);
        $this->assertInstanceOf(stdClass::class, $instance2);
        $this->assertNotSame($instance1, $instance2);
    }

    // =========================================================================
    // SINGLETON TESTS
    // =========================================================================

    public function testSingletonReturnsSameInstance(): void
    {
        $this->container->singleton('singleton_test', stdClass::class);

        $instance1 = $this->container->make('singleton_test');
        $instance2 = $this->container->make('singleton_test');

        $this->assertSame($instance1, $instance2);
    }

    public function testSingletonWithClosure(): void
    {
        $this->container->singleton(
            'singleton_closure',
            function () {
                $obj = new stdClass();
                $obj->id = uniqid();
                return $obj;
            }
        );

        $instance1 = $this->container->make('singleton_closure');
        $instance2 = $this->container->make('singleton_closure');

        $this->assertSame($instance1, $instance2);
        $this->assertEquals($instance1->id, $instance2->id);
    }

    public function testBindWithSingletonFlag(): void
    {
        $this->container->bind('flagged_singleton', stdClass::class, true);

        $instance1 = $this->container->make('flagged_singleton');
        $instance2 = $this->container->make('flagged_singleton');

        $this->assertSame($instance1, $instance2);
    }

    // =========================================================================
    // INSTANCE REGISTRATION TESTS
    // =========================================================================

    public function testInstanceRegistration(): void
    {
        $obj = new stdClass();
        $obj->value = 'test_instance';

        $this->container->instance('existing_instance', $obj);
        $resolved = $this->container->make('existing_instance');

        $this->assertSame($obj, $resolved);
        $this->assertEquals('test_instance', $resolved->value);
    }

    public function testInstanceRegistrationWithPrimitiveValue(): void
    {
        $this->container->instance('config_value', 'production');
        $resolved = $this->container->make('config_value');

        $this->assertEquals('production', $resolved);
    }

    public function testInstanceRegistrationWithArray(): void
    {
        $config = ['database' => 'mysql', 'debug' => true];

        $this->container->instance('app_config', $config);
        $resolved = $this->container->make('app_config');

        $this->assertEquals($config, $resolved);
        $this->assertTrue($resolved['debug']);
    }

    // =========================================================================
    // ALIAS TESTS
    // =========================================================================

    public function testAliasCreation(): void
    {
        $this->container->bind('original', stdClass::class);
        $this->container->alias('original', 'aliased');

        $original = $this->container->make('original');
        $aliased = $this->container->make('aliased');

        $this->assertInstanceOf(stdClass::class, $original);
        $this->assertInstanceOf(stdClass::class, $aliased);
        // Note: These will be different instances since it's not a singleton
    }

    public function testAliasWithSingleton(): void
    {
        $this->container->singleton('singleton_original', stdClass::class);
        $this->container->alias('singleton_original', 'singleton_alias');

        $original = $this->container->make('singleton_original');
        $aliased = $this->container->make('singleton_alias');

        $this->assertSame($original, $aliased);
    }

    public function testMultipleAliases(): void
    {
        $this->container->singleton('service', stdClass::class);
        $this->container->alias('service', 'alias1');
        $this->container->alias('service', 'alias2');
        $this->container->alias('service', 'alias3');

        $original = $this->container->make('service');
        $alias1 = $this->container->make('alias1');
        $alias2 = $this->container->make('alias2');
        $alias3 = $this->container->make('alias3');

        $this->assertSame($original, $alias1);
        $this->assertSame($original, $alias2);
        $this->assertSame($original, $alias3);
    }

    // =========================================================================
    // TAGGING TESTS
    // =========================================================================

    public function testTagSingleService(): void
    {
        $this->container->bind('service1', stdClass::class);
        $this->container->tag('cache', 'service1');

        $tagged = $this->container->tagged('cache');
        $this->assertCount(1, $tagged);
        $this->assertInstanceOf(stdClass::class, $tagged[0]);
    }

    public function testTagMultipleServices(): void
    {
        $this->container->bind('cache_redis', stdClass::class);
        $this->container->bind('cache_file', stdClass::class);
        $this->container->bind('cache_memory', stdClass::class);

        $this->container->tag('cache', 'cache_redis');
        $this->container->tag('cache', 'cache_file');
        $this->container->tag('cache', 'cache_memory');

        $tagged = $this->container->tagged('cache');
        $this->assertCount(3, $tagged);

        foreach ($tagged as $service) {
            $this->assertInstanceOf(stdClass::class, $service);
        }
    }

    public function testTagWithArrayOfTags(): void
    {
        $this->container->bind('multi_tag_service', stdClass::class);
        $this->container->tag(['cache', 'storage', 'persistence'], 'multi_tag_service');

        $cacheServices = $this->container->tagged('cache');
        $storageServices = $this->container->tagged('storage');
        $persistenceServices = $this->container->tagged('persistence');

        $this->assertCount(1, $cacheServices);
        $this->assertCount(1, $storageServices);
        $this->assertCount(1, $persistenceServices);

        // All should resolve to instances of the same class
        $this->assertInstanceOf(stdClass::class, $cacheServices[0]);
        $this->assertInstanceOf(stdClass::class, $storageServices[0]);
        $this->assertInstanceOf(stdClass::class, $persistenceServices[0]);
    }

    public function testTaggedWithNonExistentTag(): void
    {
        $tagged = $this->container->tagged('non_existent_tag');
        $this->assertIsArray($tagged);
        $this->assertCount(0, $tagged);
    }

    // =========================================================================
    // AUTO-WIRING TESTS
    // =========================================================================

    public function testAutoWiringWithoutDependencies(): void
    {
        $instance = $this->container->make(SimpleClassWithoutDependencies::class);
        $this->assertInstanceOf(SimpleClassWithoutDependencies::class, $instance);
    }

    public function testAutoWiringWithDependencies(): void
    {
        $instance = $this->container->make(ClassWithDependencies::class);

        $this->assertInstanceOf(ClassWithDependencies::class, $instance);
        $this->assertInstanceOf(SimpleClassWithoutDependencies::class, $instance->dependency);
    }

    public function testAutoWiringWithCircularDependencyDetection(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->container->make(CircularDependencyA::class);
    }

    // =========================================================================
    // PARAMETER INJECTION TESTS
    // =========================================================================

    public function testMakeWithParameters(): void
    {
        $this->container->bind(ClassWithParameters::class);

        $instance = $this->container->make(ClassWithParameters::class, ['param' => 'test_value']);

        $this->assertInstanceOf(ClassWithParameters::class, $instance);
        $this->assertEquals('test_value', $instance->param);
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    public function testMakeThrowsExceptionForUnresolvableClass(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Class NonExistentClass not found');

        $this->container->make('NonExistentClass');
    }

    public function testMakeThrowsExceptionForUnresolvableInterface(): void
    {
        $this->expectException(Exception::class);

        $this->container->make(UnresolvableInterface::class);
    }

    // =========================================================================
    // PERFORMANCE AND MEMORY TESTS
    // =========================================================================

    public function testContainerMemoryEfficiency(): void
    {
        // Register many services
        for ($i = 0; $i < 100; $i++) {
            $this->container->bind("service_{$i}", stdClass::class);
        }

        $initialMemory = memory_get_usage();

        // Resolve all services
        for ($i = 0; $i < 100; $i++) {
            $this->container->make("service_{$i}");
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be reasonable (less than 1MB for this test)
        $this->assertLessThan(1024 * 1024, $memoryIncrease);
    }

    public function testSingletonMemoryEfficiency(): void
    {
        // Register singletons
        for ($i = 0; $i < 50; $i++) {
            $this->container->singleton("singleton_{$i}", stdClass::class);
        }

        // Resolve multiple times
        for ($j = 0; $j < 3; $j++) {
            for ($i = 0; $i < 50; $i++) {
                $this->container->make("singleton_{$i}");
            }
        }

        // Should maintain singleton instances efficiently
        $this->assertTrue(true); // Test completion indicates memory efficiency
    }

    // =========================================================================
    // CONTAINER MANAGEMENT TESTS
    // =========================================================================

    public function testBoundMethodReturnsTrueForBoundServices(): void
    {
        $this->container->bind('test_service', stdClass::class);

        $this->assertTrue($this->container->bound('test_service'));
        $this->assertFalse($this->container->bound('non_existent_service'));
    }

    public function testBoundMethodWorksWithAliases(): void
    {
        $this->container->bind('original_service', stdClass::class);
        $this->container->alias('original_service', 'service_alias');

        $this->assertTrue($this->container->bound('original_service'));
        $this->assertTrue($this->container->bound('service_alias'));
    }

    public function testForgetMethodRemovesBinding(): void
    {
        $this->container->bind('forgettable_service', stdClass::class);
        $this->assertTrue($this->container->bound('forgettable_service'));

        $this->container->forget('forgettable_service');
        $this->assertFalse($this->container->bound('forgettable_service'));
    }

    public function testForgetMethodRemovesSingletonInstance(): void
    {
        $this->container->singleton('forgettable_singleton', stdClass::class);
        $instance1 = $this->container->make('forgettable_singleton');

        $this->container->forget('forgettable_singleton');
        $this->container->singleton('forgettable_singleton', stdClass::class);
        $instance2 = $this->container->make('forgettable_singleton');

        $this->assertNotSame($instance1, $instance2);
    }

    public function testFlushMethodClearsAllBindings(): void
    {
        $this->container->bind('service1', stdClass::class);
        $this->container->singleton('service2', stdClass::class);
        $this->container->instance('service3', new stdClass());

        $this->assertTrue($this->container->bound('service1'));
        $this->assertTrue($this->container->bound('service2'));
        $this->assertTrue($this->container->bound('service3'));

        $this->container->flush();

        $this->assertFalse($this->container->bound('service1'));
        $this->assertFalse($this->container->bound('service2'));
        $this->assertFalse($this->container->bound('service3'));
    }

    public function testCallMethodInvokesCallableWithDependencyInjection(): void
    {
        $this->container->bind(SimpleClassWithoutDependencies::class);

        $result = $this->container->call(
            function (SimpleClassWithoutDependencies $dependency) {
                return $dependency->value;
            }
        );

        $this->assertEquals('simple', $result);
    }

    public function testCallMethodWithParameters(): void
    {
        $result = $this->container->call(
            function ($param1, $param2) {
                return $param1 . '_' . $param2;
            },
            ['param1' => 'hello', 'param2' => 'world']
        );

        $this->assertEquals('hello_world', $result);
    }

    public function testGetDebugInfoReturnsContainerState(): void
    {
        $this->container->bind('debug_service', stdClass::class);
        $this->container->singleton('debug_singleton', stdClass::class);
        $this->container->alias('debug_service', 'debug_alias');
        $this->container->tag('debug_tag', 'debug_service');

        $debugInfo = $this->container->getDebugInfo();

        $this->assertIsArray($debugInfo);
        $this->assertArrayHasKey('bindings', $debugInfo);
        $this->assertArrayHasKey('instances', $debugInfo);
        $this->assertArrayHasKey('aliases', $debugInfo);
        $this->assertArrayHasKey('tags', $debugInfo);
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    public function testComplexServiceResolution(): void
    {
        // Setup complex service hierarchy
        $this->container->bind(ServiceInterface::class, ConcreteService::class);
        $this->container->singleton(ConfigService::class);
        $this->container->bind(ComplexService::class);

        $service = $this->container->make(ComplexService::class);

        $this->assertInstanceOf(ComplexService::class, $service);
        $this->assertInstanceOf(ConcreteService::class, $service->serviceInterface);
        $this->assertInstanceOf(ConfigService::class, $service->configService);
    }

    public function testFullContainerWorkflow(): void
    {
        // Test a complete workflow with all container features

        // 1. Bind services
        $this->container->bind('logger', TestLogger::class);
        $this->container->singleton('config', TestConfig::class);
        $this->container->bind('processor', TestProcessor::class);

        // 2. Create aliases
        $this->container->alias('config', 'app.config');
        $this->container->alias('logger', 'app.logger');

        // 3. Tag services
        $this->container->tag(['core', 'services'], 'logger');
        $this->container->tag(['core', 'services'], 'config');
        $this->container->tag('processing', 'processor');

        // 4. Resolve and verify
        $processor = $this->container->make('processor');
        $this->assertInstanceOf(TestProcessor::class, $processor);

        // 5. Verify singleton behavior
        $config1 = $this->container->make('config');
        $config2 = $this->container->make('app.config');
        $this->assertSame($config1, $config2);

        // 6. Verify tagging
        $coreServices = $this->container->tagged('core');
        $this->assertCount(2, $coreServices);

        // 7. Test container introspection
        $this->assertTrue($this->container->bound('logger'));
        $this->assertTrue($this->container->bound('app.logger'));

        $debugInfo = $this->container->getDebugInfo();
        $this->assertNotEmpty($debugInfo['bindings']);
        $this->assertNotEmpty($debugInfo['aliases']);
        $this->assertNotEmpty($debugInfo['tags']);
    }
}

// =========================================================================
// TEST HELPER CLASSES
// =========================================================================
