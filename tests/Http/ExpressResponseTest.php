<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\ExpressResponse;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Psr7\Stream;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Comprehensive test suite for ExpressResponse adapter
 */
class ExpressResponseTest extends TestCase
{
    private ExpressResponse $response;

    protected function setUp(): void
    {
        // Create basic response
        $this->response = OptimizedHttpFactory::createResponse();
        $this->response->setTestMode(true);

        // Clear JSON pool
        JsonBufferPool::clearPools();
    }

    protected function tearDown(): void
    {
        // Clean up
        JsonBufferPool::clearPools();
    }

    // ========================================================================
    // BASIC EXPRESS.JS METHODS
    // ========================================================================

    public function testStatusSetsStatusCode(): void
    {
        $this->response->status(404);

        $this->assertEquals(404, $this->response->getStatusCode());
    }

    public function testStatusReturnsThis(): void
    {
        $result = $this->response->status(200);

        $this->assertSame($this->response, $result);
    }

    public function testHeaderSetsResponseHeader(): void
    {
        $this->response->header('X-Custom-Header', 'CustomValue');

        $this->assertEquals('CustomValue', $this->response->getHeaderLine('X-Custom-Header'));
    }

    public function testHeaderReturnsThis(): void
    {
        $result = $this->response->header('X-Test', 'value');

        $this->assertSame($this->response, $result);
    }

    public function testJsonSetsContentTypeHeader(): void
    {
        $this->response->json(['message' => 'test']);

        $this->assertStringContainsString('application/json', $this->response->getHeaderLine('Content-Type'));
    }

    public function testJsonEncodesArrayData(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $this->response->json($data);

        $body = (string) $this->response->getBody();
        $decoded = json_decode($body, true);

        $this->assertEquals($data, $decoded);
    }

    public function testJsonEncodesObjectData(): void
    {
        $data = (object) ['name' => 'Jane', 'role' => 'admin'];
        $this->response->json($data);

        $body = (string) $this->response->getBody();
        $decoded = json_decode($body);

        $this->assertEquals('Jane', $decoded->name);
        $this->assertEquals('admin', $decoded->role);
    }

    public function testJsonReturnsThis(): void
    {
        $result = $this->response->json(['test' => 'data']);

        $this->assertSame($this->response, $result);
    }

    public function testTextSetsContentTypeHeader(): void
    {
        $this->response->text('Hello World');

        $this->assertStringContainsString('text/plain', $this->response->getHeaderLine('Content-Type'));
    }

    public function testTextSetsBodyContent(): void
    {
        $this->response->text('Hello World');

        $body = (string) $this->response->getBody();
        $this->assertEquals('Hello World', $body);
    }

    public function testTextReturnsThis(): void
    {
        $result = $this->response->text('test');

        $this->assertSame($this->response, $result);
    }

    public function testHtmlSetsContentTypeHeader(): void
    {
        $this->response->html('<h1>Title</h1>');

        $this->assertStringContainsString('text/html', $this->response->getHeaderLine('Content-Type'));
    }

    public function testHtmlSetsBodyContent(): void
    {
        $this->response->html('<p>Content</p>');

        $body = (string) $this->response->getBody();
        $this->assertEquals('<p>Content</p>', $body);
    }

    public function testHtmlReturnsThis(): void
    {
        $result = $this->response->html('<div>test</div>');

        $this->assertSame($this->response, $result);
    }

    // ========================================================================
    // REDIRECT
    // ========================================================================

    public function testRedirectSetsLocationHeader(): void
    {
        $this->response->redirect('/new-path');

        $this->assertEquals('/new-path', $this->response->getHeaderLine('Location'));
    }

    public function testRedirectSetsDefaultStatusCode(): void
    {
        $this->response->redirect('/new-path');

        $this->assertEquals(302, $this->response->getStatusCode());
    }

    public function testRedirectSetsCustomStatusCode(): void
    {
        $this->response->redirect('/permanent', 301);

        $this->assertEquals(301, $this->response->getStatusCode());
    }

    public function testRedirectReturnsThis(): void
    {
        $result = $this->response->redirect('/test');

        $this->assertSame($this->response, $result);
    }

    // ========================================================================
    // COOKIES
    // ========================================================================

    public function testCookieReturnsThis(): void
    {
        $result = $this->response->cookie('name', 'value');

        $this->assertSame($this->response, $result);
    }

    public function testClearCookieReturnsThis(): void
    {
        $result = $this->response->clearCookie('name');

        $this->assertSame($this->response, $result);
    }

    // ========================================================================
    // ERROR AND SUCCESS RESPONSES
    // ========================================================================

    public function testErrorSetsStatusCode(): void
    {
        $this->response->error(404);

        $this->assertEquals(404, $this->response->getStatusCode());
    }

    public function testErrorSetsDefaultMessage(): void
    {
        $this->response->error(404);

        $body = (string) $this->response->getBody();
        $decoded = json_decode($body, true);

        $this->assertEquals('Not Found', $decoded['error']);
        $this->assertEquals(404, $decoded['code']);
    }

    public function testErrorSetsCustomMessage(): void
    {
        $this->response->error(400, 'Invalid input');

        $body = (string) $this->response->getBody();
        $decoded = json_decode($body, true);

        $this->assertEquals('Invalid input', $decoded['error']);
    }

    public function testErrorReturnsThis(): void
    {
        $result = $this->response->error(500);

        $this->assertSame($this->response, $result);
    }

    public function testSuccessReturnsMessageOnly(): void
    {
        $this->response->success();

        $body = (string) $this->response->getBody();
        $decoded = json_decode($body, true);

        $this->assertEquals('Success', $decoded['message']);
        $this->assertArrayNotHasKey('data', $decoded);
    }

    public function testSuccessReturnsMessageWithData(): void
    {
        $this->response->success(['id' => 1], 'Created');

        $body = (string) $this->response->getBody();
        $decoded = json_decode($body, true);

        $this->assertEquals('Created', $decoded['message']);
        $this->assertEquals(['id' => 1], $decoded['data']);
    }

    public function testSuccessReturnsThis(): void
    {
        $result = $this->response->success();

        $this->assertSame($this->response, $result);
    }

    // ========================================================================
    // SEND METHOD (AUTO DETECTION)
    // ========================================================================

    public function testSendDetectsArrayAsJson(): void
    {
        $this->response->send(['test' => 'data']);

        $contentType = $this->response->getHeaderLine('Content-Type');
        $this->assertStringContainsString('application/json', $contentType);
    }

    public function testSendDetectsObjectAsJson(): void
    {
        $this->response->send((object) ['test' => 'data']);

        $contentType = $this->response->getHeaderLine('Content-Type');
        $this->assertStringContainsString('application/json', $contentType);
    }

    public function testSendDetectsStringAsText(): void
    {
        $this->response->send('Plain text');

        $contentType = $this->response->getHeaderLine('Content-Type');
        $this->assertStringContainsString('text/plain', $contentType);
    }

    public function testSendReturnsThis(): void
    {
        $result = $this->response->send('test');

        $this->assertSame($this->response, $result);
    }

    // ========================================================================
    // STREAMING METHODS
    // ========================================================================

    public function testSetStreamBufferSizeChangesBufferSize(): void
    {
        $result = $this->response->setStreamBufferSize(16384);

        $this->assertSame($this->response, $result);
    }

    public function testStartStreamEnablesStreamingMode(): void
    {
        $this->response->startStream();

        $this->assertTrue($this->response->isStreaming());
    }

    public function testStartStreamSetsHeaders(): void
    {
        $this->response->startStream('text/event-stream');

        $this->assertEquals('text/event-stream', $this->response->getHeaderLine('Content-Type'));
        $this->assertEquals('no-cache', $this->response->getHeaderLine('Cache-Control'));
        $this->assertEquals('keep-alive', $this->response->getHeaderLine('Connection'));
    }

    public function testStartStreamReturnsThis(): void
    {
        $result = $this->response->startStream();

        $this->assertSame($this->response, $result);
    }

    public function testWriteReturnsThis(): void
    {
        $this->response->startStream();
        $result = $this->response->write('data');

        $this->assertSame($this->response, $result);
    }

    public function testWriteJsonEncodesAndWrites(): void
    {
        $this->response->startStream();
        $result = $this->response->writeJson(['test' => 'data']);

        $this->assertSame($this->response, $result);
    }

    public function testStreamFileHandlesNonExistentFile(): void
    {
        $this->response->streamFile('/non/existent/file.txt');

        $this->assertEquals(404, $this->response->getStatusCode());
    }

    public function testStreamFileReturnsThis(): void
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        $result = $this->response->streamFile($tempFile);

        unlink($tempFile);
        $this->assertSame($this->response, $result);
    }

    public function testStreamResourceHandlesInvalidResource(): void
    {
        $this->response->streamResource('not-a-resource');

        $this->assertEquals(500, $this->response->getStatusCode());
    }

    public function testEndStreamDisablesStreaming(): void
    {
        $this->response->startStream();
        $this->assertTrue($this->response->isStreaming());

        $this->response->endStream();
        $this->assertFalse($this->response->isStreaming());
    }

    public function testEndStreamMarksSentAsTrue(): void
    {
        $this->response->startStream();
        $this->response->endStream();

        $this->assertTrue($this->response->isSent());
    }

    public function testEndStreamReturnsThis(): void
    {
        $this->response->startStream();
        $result = $this->response->endStream();

        $this->assertSame($this->response, $result);
    }

    // ========================================================================
    // SERVER-SENT EVENTS (SSE)
    // ========================================================================

    public function testSendEventAutoStartsStreaming(): void
    {
        $this->assertFalse($this->response->isStreaming());

        $this->response->sendEvent(['message' => 'test']);

        $this->assertTrue($this->response->isStreaming());
    }

    public function testSendEventSetsEventStreamContentType(): void
    {
        $this->response->sendEvent(['data' => 'test']);

        $this->assertEquals('text/event-stream', $this->response->getHeaderLine('Content-Type'));
    }

    public function testSendEventReturnsThis(): void
    {
        $result = $this->response->sendEvent(['test' => 'data']);

        $this->assertSame($this->response, $result);
    }

    public function testSendHeartbeatReturnsThis(): void
    {
        $this->response->startStream('text/event-stream');
        $result = $this->response->sendHeartbeat();

        $this->assertSame($this->response, $result);
    }

    // ========================================================================
    // STATUS AND CONTROL
    // ========================================================================

    public function testGetStatusCodeReturnsDefaultStatusCode(): void
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testSetTestModeEnablesTestMode(): void
    {
        $response = OptimizedHttpFactory::createResponse();
        $this->assertFalse($response->isTestMode());

        $response->setTestMode(true);
        $this->assertTrue($response->isTestMode());
    }

    public function testSetTestModeReturnsThis(): void
    {
        $result = $this->response->setTestMode(false);

        $this->assertSame($this->response, $result);
    }

    public function testDisableAutoEmitReturnsThis(): void
    {
        $result = $this->response->disableAutoEmit(true);

        $this->assertSame($this->response, $result);
    }

    public function testIsSentReturnsFalseInitially(): void
    {
        $this->assertFalse($this->response->isSent());
    }

    public function testEmitDoesNotRunInTestMode(): void
    {
        $this->response->setTestMode(true);
        $this->response->emit();

        $this->assertFalse($this->response->isSent());
    }

    public function testResetSentStateResetsFlag(): void
    {
        $this->response->endStream(); // Sets sent = true
        $this->assertTrue($this->response->isSent());

        $this->response->resetSentState();
        $this->assertFalse($this->response->isSent());
    }

    public function testResetSentStateReturnsThis(): void
    {
        $result = $this->response->resetSentState();

        $this->assertSame($this->response, $result);
    }

    // ========================================================================
    // JSON ENCODING
    // ========================================================================

    public function testJsonUsesUnescapedUnicode(): void
    {
        $this->response->json(['emoji' => '🚀', 'text' => 'café']);

        $body = (string) $this->response->getBody();
        $this->assertStringContainsString('🚀', $body);
        $this->assertStringContainsString('café', $body);
    }

    public function testJsonUsesUnescapedSlashes(): void
    {
        $this->response->json(['url' => 'https://example.com/path']);

        $body = (string) $this->response->getBody();
        $this->assertStringContainsString('https://example.com/path', $body);
        $this->assertStringNotContainsString('\\/', $body);
    }

    public function testJsonHandlesLargeData(): void
    {
        $largeData = array_fill(0, 100, ['field' => 'value', 'number' => 123]);
        $this->response->json($largeData);

        $body = (string) $this->response->getBody();
        $decoded = json_decode($body, true);

        $this->assertCount(100, $decoded);
    }

    // ========================================================================
    // STRING CONVERSION
    // ========================================================================

    public function testTextConvertsIntegerToString(): void
    {
        $this->response->text(42);

        $body = (string) $this->response->getBody();
        $this->assertEquals('42', $body);
    }

    public function testTextConvertsFloatToString(): void
    {
        $this->response->text(3.14);

        $body = (string) $this->response->getBody();
        $this->assertEquals('3.14', $body);
    }

    public function testTextConvertsBooleanToString(): void
    {
        $this->response->text(true);

        $body = (string) $this->response->getBody();
        $this->assertEquals('1', $body);
    }

    public function testHtmlConvertsNumberToString(): void
    {
        $this->response->html(123);

        $body = (string) $this->response->getBody();
        $this->assertEquals('123', $body);
    }

    // ========================================================================
    // PSR-7 COMPLIANCE
    // ========================================================================

    public function testImplementsResponseInterface(): void
    {
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $this->response);
    }

    public function testGetReasonPhraseReturnsReasonPhrase(): void
    {
        $this->response->status(404);

        $reason = $this->response->getReasonPhrase();
        $this->assertIsString($reason);
    }

    public function testWithStatusReturnsNewInstance(): void
    {
        $newResponse = $this->response->withStatus(404);

        $this->assertNotSame($this->response, $newResponse);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals(404, $newResponse->getStatusCode());
    }

    public function testWithStatusAcceptsCustomReasonPhrase(): void
    {
        $newResponse = $this->response->withStatus(404, 'Custom Not Found');

        $this->assertEquals('Custom Not Found', $newResponse->getReasonPhrase());
    }

    public function testGetProtocolVersionReturnsVersion(): void
    {
        $version = $this->response->getProtocolVersion();

        $this->assertIsString($version);
    }

    public function testWithProtocolVersionReturnsNewInstance(): void
    {
        $newResponse = $this->response->withProtocolVersion('1.0');

        $this->assertNotSame($this->response, $newResponse);
    }

    public function testGetHeadersReturnsArray(): void
    {
        $this->response->header('X-Test', 'value');

        $headers = $this->response->getHeaders();
        $this->assertIsArray($headers);
    }

    public function testHasHeaderChecksHeaderExistence(): void
    {
        $this->response->header('X-Custom', 'value');

        $this->assertTrue($this->response->hasHeader('X-Custom'));
        $this->assertFalse($this->response->hasHeader('X-NonExistent'));
    }

    public function testGetHeaderReturnsArrayOfValues(): void
    {
        $this->response->header('X-Test', 'value1');

        $values = $this->response->getHeader('X-Test');
        $this->assertIsArray($values);
    }

    public function testGetHeaderLineReturnsString(): void
    {
        $this->response->header('X-Test', 'value');

        $value = $this->response->getHeaderLine('X-Test');
        $this->assertIsString($value);
        $this->assertEquals('value', $value);
    }

    public function testWithHeaderReturnsNewInstance(): void
    {
        $newResponse = $this->response->withHeader('X-New', 'value');

        $this->assertNotSame($this->response, $newResponse);
        $this->assertFalse($this->response->hasHeader('X-New'));
        $this->assertTrue($newResponse->hasHeader('X-New'));
    }

    public function testWithAddedHeaderReturnsNewInstance(): void
    {
        $response = $this->response->withHeader('X-Test', 'value1');
        $newResponse = $response->withAddedHeader('X-Test', 'value2');

        $this->assertNotSame($response, $newResponse);
    }

    public function testWithoutHeaderReturnsNewInstance(): void
    {
        $response = $this->response->withHeader('X-Test', 'value');
        $newResponse = $response->withoutHeader('X-Test');

        $this->assertNotSame($response, $newResponse);
        $this->assertTrue($response->hasHeader('X-Test'));
        $this->assertFalse($newResponse->hasHeader('X-Test'));
    }

    public function testGetBodyReturnsStreamInterface(): void
    {
        $body = $this->response->getBody();

        $this->assertInstanceOf(\Psr\Http\Message\StreamInterface::class, $body);
    }

    public function testWithBodyReturnsNewInstance(): void
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $newResponse = $this->response->withBody($stream);

        $this->assertNotSame($this->response, $newResponse);
    }

    // ========================================================================
    // IMMUTABILITY
    // ========================================================================

    public function testImmutabilityPreservedAcrossMultipleWithCalls(): void
    {
        $response1 = $this->response;
        $response2 = $response1->withStatus(404);
        $response3 = $response2->withHeader('X-Test', 'value');
        $response4 = $response3->withProtocolVersion('1.0');

        // All instances should be different
        $this->assertNotSame($response1, $response2);
        $this->assertNotSame($response2, $response3);
        $this->assertNotSame($response3, $response4);

        // Original should be unchanged
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertFalse($response1->hasHeader('X-Test'));
    }

    // ========================================================================
    // FLUENT INTERFACE (MUTABLE METHODS)
    // ========================================================================

    public function testFluentInterfaceChaining(): void
    {
        $result = $this->response
            ->status(201)
            ->header('X-Custom', 'value')
            ->header('X-Another', 'test');

        $this->assertSame($this->response, $result);
        $this->assertEquals(201, $this->response->getStatusCode());
        $this->assertEquals('value', $this->response->getHeaderLine('X-Custom'));
        $this->assertEquals('test', $this->response->getHeaderLine('X-Another'));
    }

    // ========================================================================
    // EDGE CASES
    // ========================================================================

    public function testJsonWithEmptyArray(): void
    {
        $this->response->json([]);

        $body = (string) $this->response->getBody();
        $this->assertEquals('[]', $body);
    }

    public function testJsonWithEmptyObject(): void
    {
        $this->response->json(new \stdClass());

        $body = (string) $this->response->getBody();
        $this->assertEquals('{}', $body);
    }

    public function testTextWithEmptyString(): void
    {
        $this->response->text('');

        $body = (string) $this->response->getBody();
        $this->assertEquals('', $body);
    }

    public function testHtmlWithEmptyString(): void
    {
        $this->response->html('');

        $body = (string) $this->response->getBody();
        $this->assertEquals('', $body);
    }

    public function testStatusCodeRanges(): void
    {
        // 1xx Informational
        $this->response->status(100);
        $this->assertEquals(100, $this->response->getStatusCode());

        // 2xx Success
        $this->response->status(201);
        $this->assertEquals(201, $this->response->getStatusCode());

        // 3xx Redirection
        $this->response->status(301);
        $this->assertEquals(301, $this->response->getStatusCode());

        // 4xx Client Error
        $this->response->status(404);
        $this->assertEquals(404, $this->response->getStatusCode());

        // 5xx Server Error
        $this->response->status(500);
        $this->assertEquals(500, $this->response->getStatusCode());
    }

    public function testMultipleHeadersWithSameName(): void
    {
        $response = $this->response
            ->withHeader('Set-Cookie', 'cookie1=value1')
            ->withAddedHeader('Set-Cookie', 'cookie2=value2');

        $headers = $response->getHeader('Set-Cookie');
        $this->assertCount(2, $headers);
    }

    // ========================================================================
    // CONTENT TYPE CHARSET
    // ========================================================================

    public function testJsonIncludesCharsetInContentType(): void
    {
        $this->response->json(['test' => 'data']);

        $contentType = $this->response->getHeaderLine('Content-Type');
        $this->assertStringContainsString('charset=utf-8', $contentType);
    }

    public function testTextIncludesCharsetInContentType(): void
    {
        $this->response->text('test');

        $contentType = $this->response->getHeaderLine('Content-Type');
        $this->assertStringContainsString('charset=utf-8', $contentType);
    }

    public function testHtmlIncludesCharsetInContentType(): void
    {
        $this->response->html('<p>test</p>');

        $contentType = $this->response->getHeaderLine('Content-Type');
        $this->assertStringContainsString('charset=utf-8', $contentType);
    }
}
