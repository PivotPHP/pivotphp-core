<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Validation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Validation\Validator;

class ValidatorSemanticsTest extends TestCase
{
    public function testNumericMinEvaluatesNumericValueEvenWhenGivenAsString(): void
    {
        $validator = new Validator(['age' => 'numeric|min:18']);
        $this->assertTrue($validator->validate(['age' => '20']));
        $this->assertEmpty($validator->getErrors());

        $this->assertFalse($validator->validate(['age' => '15']));
        $this->assertArrayHasKey('age', $validator->getErrors());
        $this->assertStringContainsString('deve ser no mínimo 18', $validator->getFirstError() ?? '');
    }

    public function testNumericMaxSupportsDecimals(): void
    {
        $validator = new Validator(['price' => 'numeric|max:9.99']);
        $this->assertTrue($validator->validate(['price' => '9.50']));
        $this->assertTrue($validator->validate(['price' => 9.99]));

        $this->assertFalse($validator->validate(['price' => '10.00']));
        $this->assertStringContainsString('deve ser no máximo 9.99', $validator->getFirstError() ?? '');
    }

    public function testStringMaxMeasuresCharactersNotBytes(): void
    {
        $validator = new Validator(['name' => 'string|max:4']);
        // "João" tem 4 caracteres UTF-8 e 5 bytes
        $this->assertTrue($validator->validate(['name' => 'João']));

        // "Joãos" tem 5 caracteres UTF-8
        $this->assertFalse($validator->validate(['name' => 'Joãos']));
        $this->assertStringContainsString('deve ter no máximo 4 caracteres', $validator->getFirstError() ?? '');
    }

    public function testArrayMinMaxCountsItems(): void
    {
        $validator = new Validator(['tags' => 'min:2|max:3']);
        $this->assertTrue($validator->validate(['tags' => ['php', 'api']]));
        $this->assertTrue($validator->validate(['tags' => ['php', 'api', 'web']]));

        $this->assertFalse($validator->validate(['tags' => ['php']]));
        $this->assertStringContainsString('deve conter pelo menos 2 itens', $validator->getFirstError() ?? '');

        $this->assertFalse($validator->validate(['tags' => ['a', 'b', 'c', 'd']]));
        $this->assertStringContainsString('deve conter no máximo 3 itens', $validator->getFirstError() ?? '');
    }

    public function testOptionalMissingFieldsPassByDefault(): void
    {
        $validator = new Validator(['bio' => 'string|max:10']);
        $this->assertTrue($validator->validate([]));
        $this->assertEmpty($validator->getErrors());
    }

    public function testNullableFieldAllowsNullValue(): void
    {
        $validator = new Validator(['bio' => 'nullable|string|max:10']);
        $this->assertTrue($validator->validate(['bio' => null]));
        $this->assertTrue($validator->validate(['bio' => 'hello']));
        $this->assertFalse($validator->validate(['bio' => 'long text exceeding max limit']));
    }

    public function testInRuleDoesStrictComparisonOnValues(): void
    {
        $validator = new Validator(['status' => 'in:ativo,inativo']);
        $this->assertTrue($validator->validate(['status' => 'ativo']));
        $this->assertTrue($validator->validate(['status' => 'inativo']));

        // Em PHP frouxo, (string)true é "1", não coincide com "ativo" nem "inativo"
        $this->assertFalse($validator->validate(['status' => true]));
        $this->assertFalse($validator->validate(['status' => false]));
        $this->assertFalse($validator->validate(['status' => 1]));
    }

    public function testRegexRuleFailsForNonStringValues(): void
    {
        $validator = new Validator(['code' => 'regex:/^\d{8}$/']);
        $this->assertTrue($validator->validate(['code' => '12345678']));

        // Arrays não devem passar silenciosamente
        $this->assertFalse($validator->validate(['code' => ['12345678']]));
        $this->assertFalse($validator->validate(['code' => 12345678]));
    }

    public function testUnknownRuleThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown validation rule "requried" for field "name".');

        new Validator(['name' => 'requried|string']);
    }

    public function testNonStringRuleThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation rule for "name" must be a string, int given.');

        // @phpstan-ignore-next-line
        new Validator(['name' => [123]]);
    }
}
