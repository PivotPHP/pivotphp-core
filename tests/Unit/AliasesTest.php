<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Cobre os aliases de compatibilidade registrados em src/aliases.php.
 */
class AliasesTest extends TestCase
{
    public function testApplicationAliasResolvesToCanonicalClass(): void
    {
        $this->assertTrue(class_exists('PivotPHP\Core\Application'));

        $reflection = new \ReflectionClass('PivotPHP\Core\Application');
        $this->assertSame('PivotPHP\Core\Core\Application', $reflection->getName());
    }
}
