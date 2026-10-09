<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Tests\Http\Status;

/**
 * Cobre a serialização correta de Response::json() (SPEC-039).
 */
class ResponseJsonSerializationTest extends TestCase
{
    private function json(mixed $data): string
    {
        $response = new Response();
        $response->setTestMode(true);
        $response->json($data);

        return $response->getBodyAsString();
    }

    public function testJsonSerializableIsRespected(): void
    {
        $dto = new class implements JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['full_name' => 'Ana Lima'];
            }
        };

        $this->assertSame('{"user":{"full_name":"Ana Lima"}}', $this->json(['user' => $dto]));
    }

    public function testBackedEnumSerializesToValue(): void
    {
        $this->assertSame('{"status":"active"}', $this->json(['status' => Status::Active]));
    }

    public function testNanThrowsInsteadOfEmptyObject(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->json(['x' => NAN]);
    }

    public function testCycleThrowsInsteadOfExhaustingMemory(): void
    {
        $a = new \stdClass();
        $a->self = $a;

        $this->expectException(\RuntimeException::class);

        $this->json($a);
    }

    public function testInvalidUtf8IsSubstituted(): void
    {
        // \xFF é UTF-8 inválido; deve ser substituído, não falhar.
        $result = $this->json(['name' => "caf\xFF"]);

        $this->assertStringContainsString("\u{FFFD}", $result);
    }
}
