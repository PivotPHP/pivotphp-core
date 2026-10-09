<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Request;

/**
 * Cobre a normalização de parâmetros de rota (SPEC-042) e restrições (SPEC-025).
 */
class RequestParamNormalizationTest extends TestCase
{
    private function param(string $pattern, string $path): mixed
    {
        return (new Request('GET', $pattern, $path))->param('v');
    }

    public function testLeadingZeroStaysString(): void
    {
        $this->assertSame('01310100', $this->param('/cep/:v', '/cep/01310100'));
        $this->assertSame('007', $this->param('/x/:v', '/x/007'));
    }

    public function testFloatStaysString(): void
    {
        $this->assertSame('1.5', $this->param('/x/:v', '/x/1.5'));
    }

    public function testScientificNotationStaysString(): void
    {
        $this->assertSame('1e3', $this->param('/x/:v', '/x/1e3'));
    }

    public function testHugeIntegerStaysString(): void
    {
        $this->assertSame('99999999999999999999', $this->param('/x/:v', '/x/99999999999999999999'));
    }

    public function testCanonicalIntegerIsConverted(): void
    {
        $this->assertSame(42, $this->param('/x/:v', '/x/42'));
        $this->assertSame(-5, $this->param('/x/:v', '/x/-5'));
    }

    public function testUrlEncodedValueIsDecoded(): void
    {
        $this->assertSame(' 12', $this->param('/x/:v', '/x/%2012'));
    }

    public function testConstraintKeepsCleanName(): void
    {
        // :id<\d+> → nome limpo 'id', valor 12 (SPEC-025).
        $request = new Request('GET', '/num/:id<\d+>', '/num/12');

        $this->assertSame(12, $request->param('id'));
    }
}
