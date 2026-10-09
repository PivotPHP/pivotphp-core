<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Psr7;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Psr7\Message;
use PivotPHP\Core\Http\Psr7\Stream;

/**
 * Cobre a validação de header (nome/valor) em withHeader/withAddedHeader (SPEC-043).
 */
class MessageHeaderValidationTest extends TestCase
{
    private Message $message;

    protected function setUp(): void
    {
        parent::setUp();
        $this->message = new Message(Stream::createFromString('body'));
    }

    public function testRejectsCarriageReturnInValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->message->withHeader('X-Test', "ok\r\nSet-Cookie: admin=1");
    }

    public function testRejectsLineFeedInValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->message->withHeader('X-Test', "ok\nSet-Cookie: admin=1");
    }

    public function testRejectsNulInValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->message->withHeader('X-Test', "bad\x00value");
    }

    public function testRejectsInvalidHeaderName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->message->withHeader("X-Bad\r\nInjected", 'v');
    }

    public function testAcceptsValidValues(): void
    {
        $clone = $this->message->withHeader('X-Test', 'simple value');

        $this->assertSame('simple value', $clone->getHeaderLine('X-Test'));
    }

    public function testAcceptsObsTextInValue(): void
    {
        // obs-text = %x80-FF é permitido em field-value.
        $clone = $this->message->withHeader('X-Test', "caf\xC3\xA9");

        $this->assertSame("caf\xC3\xA9", $clone->getHeaderLine('X-Test'));
    }
}
