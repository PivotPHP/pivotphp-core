<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Request;

/**
 * Cobre Request::withHeader/withAddedHeader/withoutHeader (SPEC-024).
 */
class RequestHeaderMutatorsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
    }

    public function testWithHeaderReflectsChangeOnCloneOnly(): void
    {
        $original = new Request('GET', '/x', '/x');
        $clone = $original->withHeader('X-Test', '1');

        $this->assertSame('1', $clone->getHeaderLine('X-Test'));
        $this->assertSame('', $original->getHeaderLine('X-Test'));
    }

    public function testWithAddedHeaderAccumulates(): void
    {
        $request = (new Request('GET', '/x', '/x'))
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('Accept', 'application/xml');

        $this->assertSame(
            ['application/json', 'application/xml'],
            $request->getHeader('Accept')
        );
    }

    public function testWithoutHeaderRemovesHeader(): void
    {
        $_SERVER['HTTP_X_B'] = 'b';
        $request = new Request('GET', '/x', '/x');

        $this->assertSame('b', $request->getHeaderLine('X-B'));

        $clone = $request->withoutHeader('X-B');
        $this->assertSame('', $clone->getHeaderLine('X-B'));
        $this->assertSame('b', $request->getHeaderLine('X-B'));
    }

    public function testHeaderNamesAreCaseInsensitive(): void
    {
        $request = (new Request('GET', '/x', '/x'))->withHeader('X-Test', 'v');

        $this->assertSame('v', $request->getHeaderLine('x-test'));
        $this->assertSame('v', $request->getHeaderLine('X-TEST'));
    }
}
