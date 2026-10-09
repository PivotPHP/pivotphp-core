<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Utils;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Utils\CallableResolver;
use InvalidArgumentException;

class CallableResolverTest extends TestCase
{
    public function testResolveClosures(): void
    {
        $closure = function () {
            return 'closure result';
        };

        $resolved = CallableResolver::resolve($closure);
        $this->assertSame($closure, $resolved);
        $this->assertEquals('closure result', $resolved());
    }

    public function testResolveStringFunctions(): void
    {
        // Test built-in function
        $resolved = CallableResolver::resolve('strlen');
        $this->assertEquals('strlen', $resolved);
        $this->assertEquals(5, $resolved('hello'));

        // Test another built-in function
        $resolved = CallableResolver::resolve('strtoupper');
        $this->assertEquals('strtoupper', $resolved);
        $this->assertEquals('HELLO', $resolved('hello'));
    }

    public function testResolveNonExistentStringFunction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Function 'nonexistent_function' does not exist");

        CallableResolver::resolve('nonexistent_function');
    }

    public function testResolveArrayCallableWithStaticMethod(): void
    {
        $resolved = CallableResolver::resolve([self::class, 'staticTestMethod']);
        $this->assertEquals('static method called', $resolved());
    }

    public function testResolveArrayCallableWithInstanceMethod(): void
    {
        $instance = new TestCallableClass();
        $resolved = CallableResolver::resolve([$instance, 'instanceMethod']);
        $this->assertEquals('instance method called', $resolved());
    }

    public function testArrayCallableWithNonExistentClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Class 'NonExistentClass' does not exist");

        CallableResolver::resolve(['NonExistentClass', 'method']);
    }

    public function testArrayCallableWithNonExistentStaticMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Route handler validation failed: Static method");

        CallableResolver::resolve([self::class, 'nonExistentMethod']);
    }

    public function testArrayCallableWithNonExistentInstanceMethod(): void
    {
        $instance = new TestCallableClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Route handler validation failed: Method");

        CallableResolver::resolve([$instance, 'nonExistentMethod']);
    }

    public function testArrayCallableWithNonStaticMethod(): void
    {
        // [Classe::class, 'métodoDeInstância'] resolve de forma lazy (closure).
        $resolved = CallableResolver::resolve([TestCallableClass::class, 'instanceMethod']);

        $this->assertInstanceOf(\Closure::class, $resolved);
        $this->assertEquals('instance method called', $resolved());
    }

    public function testArrayCallableWithPrivateMethod(): void
    {
        $instance = new TestCallableClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("is not public");

        CallableResolver::resolve([$instance, 'privateMethod']);
    }

    public function testArrayCallableWithNonStringMethod(): void
    {
        $instance = new TestCallableClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Array callable second element must be a string method name");

        CallableResolver::resolve([$instance, 123]);
    }

    public function testArrayCallableWithInvalidFirstElement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Array callable first element must be a class name string or object instance");

        CallableResolver::resolve([123, 'method']);
    }

    public function testArrayCallableWithWrongLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Handler must be a callable");

        CallableResolver::resolve(['single_element']);
    }

    public function testResolveInvalidTypes(): void
    {
        $invalidTypes = [
            123,
            12.34,
            true,
            null,
            (object) ['key' => 'value'],
            tmpfile()
        ];

        foreach ($invalidTypes as $invalid) {
            try {
                CallableResolver::resolve($invalid);
                $this->fail('Expected InvalidArgumentException for type: ' . gettype($invalid));
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Handler must be a callable', $e->getMessage());
            }
        }
    }

    public function testIsCallable(): void
    {
        // Valid callables
        $this->assertTrue(
            CallableResolver::isCallable(
                function () {
                }
            )
        );
        $this->assertTrue(CallableResolver::isCallable('strlen'));
        $this->assertTrue(CallableResolver::isCallable([self::class, 'staticTestMethod']));
        $this->assertTrue(CallableResolver::isCallable([new TestCallableClass(), 'instanceMethod']));

        // Invalid callables
        $this->assertFalse(CallableResolver::isCallable('nonexistent_function'));
        $this->assertFalse(CallableResolver::isCallable([self::class, 'nonExistentMethod']));
        $this->assertFalse(CallableResolver::isCallable(123));
        $this->assertFalse(CallableResolver::isCallable(null));
        $this->assertFalse(CallableResolver::isCallable(['single_element']));
    }

    public function testCall(): void
    {
        // Test with closure
        $closure = function ($a, $b) {
            return $a + $b;
        };
        $result = CallableResolver::call($closure, 5, 3);
        $this->assertEquals(8, $result);

        // Test with string function
        $result = CallableResolver::call('strlen', 'hello');
        $this->assertEquals(5, $result);

        // Test with static method
        $result = CallableResolver::call([self::class, 'staticTestMethodWithArgs'], 'test', 123);
        $this->assertEquals('test-123', $result);

        // Test with instance method
        $instance = new TestCallableClass();
        $result = CallableResolver::call([$instance, 'instanceMethodWithArgs'], 'hello', 'world');
        $this->assertEquals('hello world', $result);
    }

    public function testCallWithInvalidHandler(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CallableResolver::call('nonexistent_function', 'arg');
    }

    public function testGetTypeDescription(): void
    {
        // Use reflection to test private method via public method that uses it
        try {
            CallableResolver::resolve(new TestCallableClass());
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('object(', $e->getMessage());
            $this->assertStringContainsString('TestCallableClass', $e->getMessage());
        }

        try {
            CallableResolver::resolve([1, 2, 3]);
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('array(3 elements)', $e->getMessage());
        }

        try {
            CallableResolver::resolve(123);
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('integer', $e->getMessage());
        }

        $resource = tmpfile();
        try {
            CallableResolver::resolve($resource);
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('resource(', $e->getMessage());
        }
        fclose($resource);
    }

    public function testAlreadyValidCallable(): void
    {
        // Test that already valid callables are returned as-is
        $builtinCallable = 'is_array';
        $resolved = CallableResolver::resolve($builtinCallable);
        $this->assertEquals($builtinCallable, $resolved);
        $this->assertTrue($resolved([1, 2, 3]));
        $this->assertFalse($resolved('string'));
    }

    public function testEdgeCasesForArrayCallables(): void
    {
        // Test empty array
        $this->expectException(InvalidArgumentException::class);
        CallableResolver::resolve([]);
    }

    public function testReflectionEdgeCases(): void
    {
        // Test protected method
        $instance = new TestCallableClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("is not public");

        CallableResolver::resolve([$instance, 'protectedMethod']);
    }

    public function testStringCallableEdgeCases(): void
    {
        // Test various string functions to cover resolveStringCallable method fully
        $this->assertTrue(CallableResolver::isCallable('strtolower'));
        $this->assertTrue(CallableResolver::isCallable('count'));
        $this->assertTrue(CallableResolver::isCallable('array_merge'));
        $this->assertTrue(CallableResolver::isCallable('trim'));

        // Test invalid function names with different patterns
        $this->assertFalse(CallableResolver::isCallable('this_function_definitely_does_not_exist'));
        $this->assertFalse(CallableResolver::isCallable('invalid.function.name'));
        $this->assertFalse(CallableResolver::isCallable(''));
    }

    public function testGetTypeDescriptionForResources(): void
    {
        // Create different types of resources to test getTypeDescription method
        $fileResource = tmpfile();

        try {
            CallableResolver::resolve($fileResource);
            $this->fail('Should have thrown InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('resource(stream)', $e->getMessage());
        } finally {
            fclose($fileResource);
        }
    }

    public function testEmptyArrayCallable(): void
    {
        // Test specifically empty array to reach all branches
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Handler must be a callable");

        CallableResolver::resolve([]);
    }

    public function testArrayCallableWithThreeElements(): void
    {
        // Test array with 3 elements (not exactly 2)
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Handler must be a callable");

        CallableResolver::resolve(['one', 'two', 'three']);
    }

    public function testResolveStringCallableDirectly(): void
    {
        // Test resolveStringCallable method by using string functions
        $resolved = CallableResolver::resolve('is_string');
        $this->assertTrue($resolved('hello'));
        $this->assertFalse($resolved(123));

        $resolved = CallableResolver::resolve('is_numeric');
        $this->assertTrue($resolved('123'));
        $this->assertFalse($resolved('hello'));
    }

    public function testComplexObjectInTypeDescription(): void
    {
        // Test with a complex object to ensure getTypeDescription works
        $complexObject = new \DateTime();

        try {
            CallableResolver::resolve($complexObject);
            $this->fail('Should have thrown InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('object(DateTime)', $e->getMessage());
        }
    }

    public function testGetTypeDescriptionForAllTypes(): void
    {
        // Test all possible types in getTypeDescription method
        // Note: strings are handled differently (as function names), so we test non-string types
        $testCases = [
            'integer' => 42,
            'double' => 3.14,
            'boolean' => true,
            'NULL' => null,
        ];

        foreach ($testCases as $expectedType => $value) {
            try {
                CallableResolver::resolve($value);
                $this->fail("Should have thrown InvalidArgumentException for type: $expectedType");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString($expectedType, $e->getMessage());
            }
        }

        // Test string type separately as it goes through different path
        try {
            CallableResolver::resolve('nonexistent_function_name_12345');
            $this->fail("Should have thrown InvalidArgumentException for string");
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('does not exist', $e->getMessage());
        }
    }

    public function testCallMethodWithDifferentArguments(): void
    {
        // Test call method with various argument patterns

        // Test with no arguments
        $result = CallableResolver::call('phpversion');
        $this->assertIsString($result);

        // Test with single argument
        $result = CallableResolver::call('strlen', 'test');
        $this->assertEquals(4, $result);

        // Test with multiple arguments
        $result = CallableResolver::call('str_replace', 'l', 'X', 'hello');
        $this->assertEquals('heXXo', $result);

        // Test with array callable and arguments
        $result = CallableResolver::call([self::class, 'staticTestMethodWithArgs'], 'prefix', 999);
        $this->assertEquals('prefix-999', $result);

        // Test with instance method and arguments
        $instance = new TestCallableClass();
        $result = CallableResolver::call([$instance, 'instanceMethodWithArgs'], 'arg1', 'arg2');
        $this->assertEquals('arg1 arg2', $result);
    }

    public function testComprehensiveCallableTypesForFullCoverage(): void
    {
        // Test all permutations to ensure full coverage

        // 1. Test resolve with various built-in PHP functions
        $functions = ['trim', 'strtoupper', 'is_array', 'count', 'strlen'];
        foreach ($functions as $func) {
            $this->assertTrue(CallableResolver::isCallable($func));
            $resolved = CallableResolver::resolve($func);
            $this->assertEquals($func, $resolved);
        }

        // 2. Test static method calls with different classes
        $this->assertTrue(CallableResolver::isCallable([self::class, 'staticTestMethod']));
        $this->assertTrue(CallableResolver::isCallable([TestCallableClass::class, 'staticMethodInClass']));

        // 3. Test instance method calls
        $instance = new TestCallableClass();
        $this->assertTrue(CallableResolver::isCallable([$instance, 'instanceMethod']));

        // 4. Test edge case: array with exact 2 elements but wrong types
        $this->assertFalse(CallableResolver::isCallable([123, 456]));
        $this->assertFalse(CallableResolver::isCallable([null, 'method']));

        // 5. Comprehensive type checking for error messages
        $invalidTypes = [
            'float' => 3.14159,
            'boolean_false' => false,
            'resource' => tmpfile(),
        ];

        foreach ($invalidTypes as $typeName => $value) {
            $this->assertFalse(CallableResolver::isCallable($value));
            try {
                CallableResolver::resolve($value);
                $this->fail("Should fail for $typeName");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Handler must be a callable', $e->getMessage());
            }
        }

        // Close the resource
        foreach ($invalidTypes as $value) {
            if (is_resource($value)) {
                fclose($value);
            }
        }
    }

    public function testSpecificReturnPathCoverage(): void
    {
        // Test to ensure we hit the specific return statements that are missing coverage

        // 1. Test static method return path (line 93) - Force execution through the return path
        $staticCallable = CallableResolver::resolve([TestCallableClass::class, 'staticMethodInClass']);
        $this->assertIsArray($staticCallable);
        $this->assertEquals('static method in class called', call_user_func($staticCallable));

        // Alternative static method test
        $staticCallable2 = CallableResolver::resolve([self::class, 'staticTestMethod']);
        $this->assertEquals('static method called', call_user_func($staticCallable2));

        // 2. Test instance method return path (line 115) - Force execution through return path
        $instance = new TestCallableClass();
        $instanceCallable = CallableResolver::resolve([$instance, 'instanceMethod']);
        $this->assertIsArray($instanceCallable);
        $this->assertEquals('instance method called', call_user_func($instanceCallable));

        // Alternative instance method test
        $instance2 = new TestCallableClass();
        $instanceCallable2 = CallableResolver::resolve([$instance2, 'instanceMethodWithArgs']);
        $this->assertEquals('test args', call_user_func($instanceCallable2, 'test', 'args'));

        // 3. Test string function return path (line 134) - Multiple function calls
        $builtinFunctions = ['strlen', 'strtoupper', 'strtolower', 'trim', 'count'];
        foreach ($builtinFunctions as $funcName) {
            $stringCallable = CallableResolver::resolve($funcName);
            $this->assertEquals($funcName, $stringCallable);
            $this->assertTrue(is_callable($stringCallable));
        }

        // Specific execution tests for string callables
        $this->assertEquals(5, CallableResolver::resolve('strlen')('hello'));
        $this->assertEquals('WORLD', CallableResolver::resolve('strtoupper')('world'));
        $this->assertEquals(3, CallableResolver::resolve('count')([1, 2, 3]));
    }

    // Static test method for testing
    public static function staticTestMethod(): string
    {
        return 'static method called';
    }

    public static function staticTestMethodWithArgs(string $arg1, int $arg2): string
    {
        return $arg1 . '-' . $arg2;
    }
}

// Test class for callable resolution tests
class TestCallableClass
{
    public function instanceMethod(): string
    {
        return 'instance method called';
    }

    public function instanceMethodWithArgs(string $arg1, string $arg2): string
    {
        return $arg1 . ' ' . $arg2;
    }

    private function privateMethod(): string
    {
        return 'private method called';
    }

    protected function protectedMethod(): string
    {
        return 'protected method called';
    }

    public static function staticMethodInClass(): string
    {
        return 'static method in class called';
    }
}
