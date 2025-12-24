<?php

namespace PivotPHP\Core\Tests\Http;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RequestAttributesTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        $this->request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');
    }

    public function testSetAndGetAttribute(): void
    {
        $this->request->setAttribute('user', 'testUser');
        $this->assertEquals('testUser', $this->request->getAttribute('user'));
    }

    public function testGetAttributeWithDefault(): void
    {
        $this->assertEquals('default', $this->request->getAttribute('nonexistent', 'default'));
    }

    public function testHasAttribute(): void
    {
        $this->request->setAttribute('test', 'value');
        $this->assertTrue($this->request->hasAttribute('test'));
        $this->assertFalse($this->request->hasAttribute('nonexistent'));
    }

    public function testRemoveAttribute(): void
    {
        $this->request->setAttribute('temp', 'value');
        $this->assertTrue($this->request->hasAttribute('temp'));

        $this->request->removeAttribute('temp');
        $this->assertFalse($this->request->hasAttribute('temp'));
    }

    public function testGetAllAttributes(): void
    {
        $this->request->setAttribute('attr1', 'value1');
        $this->request->setAttribute('attr2', 'value2');

        $attributes = $this->request->getAttributes();
        $this->assertArrayHasKey('attr1', $attributes);
        $this->assertArrayHasKey('attr2', $attributes);
        $this->assertEquals('value1', $attributes['attr1']);
        $this->assertEquals('value2', $attributes['attr2']);
    }

    public function testSetMultipleAttributes(): void
    {
        $attributes = [
            'user' => 'testUser',
            'session' => 'testSession',
            'role' => 'admin',
        ];

        $this->request->setAttributes($attributes);

        $this->assertEquals('testUser', $this->request->getAttribute('user'));
        $this->assertEquals('testSession', $this->request->getAttribute('session'));
        $this->assertEquals('admin', $this->request->getAttribute('role'));
    }

    public function testMagicGetAttribute(): void
    {
        $this->request->setAttribute('user', 'testUser');
        $this->assertEquals('testUser', $this->request->user);
    }

    public function testMagicSetAttribute(): void
    {
        $this->request->user = 'testUser';
        $this->assertEquals('testUser', $this->request->getAttribute('user'));
    }

    public function testMagicIsset(): void
    {
        $this->request->setAttribute('user', 'testUser');
        $this->assertTrue(isset($this->request->user));
        $this->assertFalse(isset($this->request->nonexistent));
    }

    public function testMagicUnset(): void
    {
        $this->request->setAttribute('temp', 'value');
        $this->assertTrue(isset($this->request->temp));

        unset($this->request->temp);
        $this->assertFalse(isset($this->request->temp));
    }

    public function testCannotOverrideNativeProperty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot override native property: method');

        $this->request->setAttribute('method', 'POST');
    }

    public function testCannotSetNativePropertyMagically(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot override native property: method');

        $this->request->method = 'POST';
    }

    public function testCannotUnsetNativeProperty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot unset native property: method');

        unset($this->request->method);
    }

    public function testFluentInterface(): void
    {
        $result = $this->request->setAttribute('attr1', 'value1')
                                ->setAttribute('attr2', 'value2')
                                ->removeAttribute('attr1');

        $this->assertSame($this->request, $result);
        $this->assertFalse($this->request->hasAttribute('attr1'));
        $this->assertTrue($this->request->hasAttribute('attr2'));
    }
}
