<?php

namespace PivotPHP\Core\Tests\Support;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Support\Str;

class StrTest extends TestCase
{
    protected function setUp(): void
    {
        // Suppress E_USER_DEPRECATED from deprecated Str methods under test
        set_error_handler(static function (int $errno): bool {
            return $errno === E_USER_DEPRECATED;
        }, E_ALL);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    public function testCamel(): void
    {
        $this->assertEquals('expressPhp', Str::camel('express_php'));
        $this->assertEquals('expressPhp', Str::camel('express-php'));
        $this->assertEquals('expressPhp', Str::camel('Express Php'));
    }

    public function testStudly(): void
    {
        $this->assertEquals('ExpressPhp', Str::studly('express_php'));
        $this->assertEquals('ExpressPhp', Str::studly('express-php'));
        $this->assertEquals('ExpressPhp', Str::studly('express php'));
    }

    public function testSnake(): void
    {
        $this->assertEquals('express_php', Str::snake('ExpressPhp'));
        $this->assertEquals('express_php', Str::snake('expressPhp'));
    }

    public function testKebab(): void
    {
        $this->assertEquals('express-php', Str::kebab('ExpressPhp'));
        $this->assertEquals('express-php', Str::kebab('expressPhp'));
    }

    public function testLimit(): void
    {
        $this->assertEquals('Hello...', Str::limit('Hello World', 5));
        $this->assertEquals('Hello World', Str::limit('Hello World', 20));
        $this->assertEquals('Hello***', Str::limit('Hello World', 5, '***'));
    }

    public function testRandom(): void
    {
        $random1 = Str::random(10);
        $random2 = Str::random(10);

        $this->assertEquals(10, strlen($random1));
        $this->assertEquals(10, strlen($random2));
        $this->assertNotEquals($random1, $random2);
    }

    public function testStartsWith(): void
    {
        $this->assertTrue(Str::startsWith('PivotPHP', 'Pivot'));
        $this->assertFalse(Str::startsWith('PivotPHP', 'PHP'));
    }

    public function testEndsWith(): void
    {
        $this->assertTrue(Str::endsWith('PivotPHP', 'PHP'));
        $this->assertFalse(Str::endsWith('PivotPHP', 'Express'));
    }

    public function testContains(): void
    {
        $this->assertTrue(Str::contains('PivotPHP Framework', 'PHP'));
        $this->assertFalse(Str::contains('PivotPHP Framework', 'Laravel'));
    }

    public function testUcfirst(): void
    {
        $this->assertEquals('Helix', Str::ucfirst('helix'));
        $this->assertEquals('ÁBCD', Str::ucfirst('áBCD'));
    }

    public function testTitle(): void
    {
        $this->assertEquals('Helix Php Framework', Str::title('helix php framework'));
    }

    public function testAscii(): void
    {
        $this->assertEquals('Joao', Str::ascii('João'));
        $this->assertEquals('cafe', Str::ascii('café'));
        $this->assertEquals('nao', Str::ascii('não'));
    }
}
