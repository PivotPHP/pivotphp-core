<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\CustomHeaderCollection;
use ReflectionClass;

class CustomHeaderCollectionTest extends TestCase
{
    /**
     * Regression test for the merge logic itself: given a non-empty source
     * of environment headers (what getallheaders() would return under
     * Apache/PHP-FPM), every header not already set by an explicit
     * override must be merged in.
     *
     * getallheaders() can't be reliably mocked here: CustomHeaderCollection
     * guards the call with function_exists('getallheaders'), and that
     * check always resolves against the *global* function regardless of
     * any same-namespace override — and the real getallheaders() doesn't
     * exist at all under the CLI SAPI PHPUnit runs under. Exercising
     * mergeMissingHeaders() directly via reflection tests the exact logic
     * that was broken (the merge was skipped whenever the source was
     * non-empty) without relying on an environment-specific global.
     */
    public function testMergeMissingHeadersAddsHeadersNotAlreadySet(): void
    {
        $collection = new CustomHeaderCollection(['Authorization' => 'Bearer explicit-override']);

        $this->invokeMergeMissingHeaders(
            $collection,
            [
                'Authorization' => 'Bearer from-environment',
                'Cookie' => 'session=abc123',
            ]
        );

        // Explicit constructor override must win over the merged source
        $this->assertSame('Bearer explicit-override', $collection->getHeader('Authorization'));
        // Header only present in the merged source must now be available
        $this->assertTrue($collection->hasHeader('Cookie'));
        $this->assertSame('session=abc123', $collection->getHeader('Cookie'));
    }

    public function testFallsBackToServerHeadersWhenGetallheadersUnavailable(): void
    {
        // Under the CLI SAPI, function_exists('getallheaders') is false,
        // so the constructor always takes the $_SERVER fallback path —
        // this exercises that path end-to-end, for real.
        $_SERVER['HTTP_X_FALLBACK_TEST'] = 'fallback-value';

        try {
            $collection = new CustomHeaderCollection();
            $this->assertSame('fallback-value', $collection->getHeader('X-Fallback-Test'));
        } finally {
            unset($_SERVER['HTTP_X_FALLBACK_TEST']);
        }
    }

    public function testConstructorCustomHeadersTakePriorityOverServerFallback(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer from-server';

        try {
            $collection = new CustomHeaderCollection(['Authorization' => 'Bearer explicit']);
            $this->assertSame('Bearer explicit', $collection->getHeader('Authorization'));
        } finally {
            unset($_SERVER['HTTP_AUTHORIZATION']);
        }
    }

    /**
     * @param array<string, string> $source
     */
    private function invokeMergeMissingHeaders(CustomHeaderCollection $collection, array $source): void
    {
        $reflection = new ReflectionClass($collection);
        $method = $reflection->getMethod('mergeMissingHeaders');
        $method->setAccessible(true);
        $method->invoke($collection, $source);
    }
}
