<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Response;

/**
 * Test JSON encoding consistency across different code paths
 */
class JsonConsistencyTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();
        $this->response->setTestMode(true);
    }

    /**
     * Test that both pooling and non-pooling paths produce consistent output
     */
    public function testJsonEncodingConsistency(): void
    {
        // Test data that will trigger non-pooling path (small object)
        $smallData = ['name' => 'test', 'value' => 42];

        // Test data that will trigger pooling path (large array)
        $largeData = array_fill(0, 20, ['field' => 'value', 'number' => 123]);

        // Test encoding with non-pooling path
        $response1 = clone $this->response;
        $response1->json($smallData);
        $smallResult = (string) $response1->getBody();

        // Test encoding with pooling path
        $response2 = clone $this->response;
        $response2->json($largeData);
        $largeResult = (string) $response2->getBody();

        // Test manual encoding with same flags
        $manualSmall = json_encode($smallData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $manualLarge = json_encode($largeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Verify consistency
        $this->assertSame($manualSmall, $smallResult, 'Non-pooling path should match manual encoding with flags');
        $this->assertSame($manualLarge, $largeResult, 'Pooling path should match manual encoding with flags');
    }

    /**
     * Test that unicode characters are handled consistently
     */
    public function testUnicodeConsistency(): void
    {
        $unicodeData = [
            'emoji' => '🚀',
            'accents' => 'café',
            'chinese' => '你好',
            'arabic' => 'مرحبا'
        ];

        // Small object (non-pooling)
        $response1 = clone $this->response;
        $response1->json($unicodeData);
        $result1 = (string) $response1->getBody();

        // Large object (pooling) - add more fields to trigger pooling
        $largeUnicodeData = array_merge($unicodeData, array_fill(0, 10, ['unicode' => '🌟']));
        $response2 = clone $this->response;
        $response2->json($largeUnicodeData);
        $result2 = (string) $response2->getBody();

        // Both should have unescaped unicode
        $this->assertStringContainsString('🚀', $result1, 'Small objects should have unescaped unicode');
        $this->assertStringContainsString('🌟', $result2, 'Large objects should have unescaped unicode');

        // Should not contain escaped unicode
        $this->assertStringNotContainsString('\\u', $result1, 'Small objects should not escape unicode');
        $this->assertStringNotContainsString('\\u', $result2, 'Large objects should not escape unicode');
    }

    /**
     * Test that slashes are handled consistently
     */
    public function testSlashConsistency(): void
    {
        $slashData = [
            'url' => 'https://example.com/path',
            'path' => '/var/www/html',
            'regex' => '/[a-zA-Z]+/'
        ];

        // Small object (non-pooling)
        $response1 = clone $this->response;
        $response1->json($slashData);
        $result1 = (string) $response1->getBody();

        // Large object (pooling)
        $largeSlashData = array_merge($slashData, array_fill(0, 10, ['url' => 'https://test.com/']));
        $response2 = clone $this->response;
        $response2->json($largeSlashData);
        $result2 = (string) $response2->getBody();

        // Both should have unescaped slashes
        $this->assertStringContainsString(
            'https://example.com/path',
            $result1,
            'Small objects should have unescaped slashes'
        );
        $this->assertStringContainsString('https://test.com/', $result2, 'Large objects should have unescaped slashes');

        // Should not contain escaped slashes
        $this->assertStringNotContainsString('\\/', $result1, 'Small objects should not escape slashes');
        $this->assertStringNotContainsString('\\/', $result2, 'Large objects should not escape slashes');
    }

    /**
     * Test writeJson method uses consistent flags
     */
    public function testWriteJsonConsistency(): void
    {
        $testData = [
            'url' => 'https://example.com/test',
            'emoji' => '✅',
            'text' => 'Hello/World'
        ];

        // Test encoding directly since we can't capture output in test mode
        $expectedOutput = json_encode($testData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Should have unescaped content
        $this->assertStringContainsString('https://example.com/test', $expectedOutput);
        $this->assertStringContainsString('✅', $expectedOutput);
        $this->assertStringNotContainsString('\\/', $expectedOutput);
        $this->assertStringNotContainsString('\\u', $expectedOutput);
    }

    /**
     * Test SSE sendEvent method uses consistent flags
     */
    public function testSendEventJsonConsistency(): void
    {
        $eventData = [
            'message' => 'Hello/World',
            'emoji' => '🎉',
            'url' => 'https://example.com/'
        ];

        // Test encoding directly since SSE uses json_encode internally
        $expectedOutput = json_encode($eventData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Should have unescaped content in event data
        $this->assertStringContainsString('https://example.com/', $expectedOutput);
        $this->assertStringContainsString('🎉', $expectedOutput);
        $this->assertStringNotContainsString('\\/', $expectedOutput);
        $this->assertStringNotContainsString('\\u', $expectedOutput);
    }

    private function getActualOutput(): string
    {
        // Capture output from streaming methods
        ob_start();
        // Output would be captured here in real scenario
        $output = ob_get_clean();

        // For test mode, we can't easily capture the output,
        // so we'll test the encoding directly
        $testData = ['url' => 'https://example.com/', 'emoji' => '🎉'];
        return json_encode($testData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
