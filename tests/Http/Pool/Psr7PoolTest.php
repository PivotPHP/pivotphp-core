<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Pool\Psr7Pool;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Response;
use PivotPHP\Core\Http\Psr7\Uri;
use PivotPHP\Core\Http\Psr7\Stream;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Comprehensive tests for Psr7Pool
 *
 * Tests PSR-7 object pooling functionality including object creation,
 * reuse, pool management, and statistics tracking.
 * Following the "less is more" principle with focused, quality testing.
 */
class Psr7PoolTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear pools before each test
        Psr7Pool::clearAll();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        Psr7Pool::clearAll();
        parent::tearDown();
    }

    /**
     * Test ServerRequest creation and pooling
     */
    public function testServerRequestCreationAndPooling(): void
    {
        $uri = new Uri('https://example.com/test');
        $body = Stream::createFromString('test body');
        $headers = ['Content-Type' => 'application/json'];

        // First request should be created
        $request1 = Psr7Pool::getServerRequest('GET', $uri, $body, $headers);
        $this->assertInstanceOf(ServerRequestInterface::class, $request1);
        $this->assertEquals('GET', $request1->getMethod());
        $this->assertEquals($uri, $request1->getUri());
        $this->assertEquals($body, $request1->getBody());

        // Return to pool
        Psr7Pool::returnServerRequest($request1);

        // Second request should be reused
        $request2 = Psr7Pool::getServerRequest('POST', $uri, $body, $headers);
        $this->assertInstanceOf(ServerRequestInterface::class, $request2);
        $this->assertEquals('POST', $request2->getMethod());

        // Verify statistics
        $stats = Psr7Pool::getStats();
        $this->assertEquals(1, $stats['usage']['requests_created']);
        $this->assertEquals(1, $stats['usage']['requests_reused']);
        $this->assertEquals(50.0, $stats['efficiency']['request_reuse_rate']);
    }

    /**
     * Test Response creation and pooling
     */
    public function testResponseCreationAndPooling(): void
    {
        $body = Stream::createFromString('{"message": "success"}');
        $headers = ['Content-Type' => 'application/json'];

        // First response should be created
        $response1 = Psr7Pool::getResponse(200, $headers, $body);
        $this->assertInstanceOf(ResponseInterface::class, $response1);
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals($body, $response1->getBody());
        $this->assertEquals('application/json', $response1->getHeaderLine('Content-Type'));

        // Return to pool
        Psr7Pool::returnResponse($response1);

        // Second response should be reused
        $response2 = Psr7Pool::getResponse(404, ['Content-Type' => 'text/plain'], $body);
        $this->assertInstanceOf(ResponseInterface::class, $response2);
        $this->assertEquals(404, $response2->getStatusCode());
        $this->assertEquals('text/plain', $response2->getHeaderLine('Content-Type'));

        // Verify statistics
        $stats = Psr7Pool::getStats();
        $this->assertEquals(1, $stats['usage']['responses_created']);
        $this->assertEquals(1, $stats['usage']['responses_reused']);
        $this->assertEquals(50.0, $stats['efficiency']['response_reuse_rate']);
    }

    /**
     * Test Uri creation and pooling
     */
    public function testUriCreationAndPooling(): void
    {
        // First URI should be created
        $uri1 = Psr7Pool::getUri('https://example.com/test');
        $this->assertInstanceOf(UriInterface::class, $uri1);
        $this->assertEquals('https', $uri1->getScheme());
        $this->assertEquals('example.com', $uri1->getHost());
        $this->assertEquals('/test', $uri1->getPath());

        // Return to pool
        Psr7Pool::returnUri($uri1);

        // Second URI should be reused
        $uri2 = Psr7Pool::getUri('http://localhost:8080/api?test=1');
        $this->assertInstanceOf(UriInterface::class, $uri2);
        $this->assertEquals('http', $uri2->getScheme());
        $this->assertEquals('localhost', $uri2->getHost());
        $this->assertEquals(8080, $uri2->getPort());
        $this->assertEquals('/api', $uri2->getPath());
        $this->assertEquals('test=1', $uri2->getQuery());

        // Verify statistics
        $stats = Psr7Pool::getStats();
        $this->assertEquals(1, $stats['usage']['uris_created']);
        $this->assertEquals(1, $stats['usage']['uris_reused']);
        $this->assertEquals(50.0, $stats['efficiency']['uri_reuse_rate']);
    }

    /**
     * Test Stream creation and pooling
     */
    public function testStreamCreationAndPooling(): void
    {
        // First stream should be created
        $stream1 = Psr7Pool::getStream('Hello World');
        $this->assertInstanceOf(StreamInterface::class, $stream1);
        $this->assertEquals('Hello World', (string) $stream1);

        // Return to pool
        Psr7Pool::returnStream($stream1);

        // Second stream should be reused
        $stream2 = Psr7Pool::getStream('New content');
        $this->assertInstanceOf(StreamInterface::class, $stream2);
        $this->assertEquals('New content', (string) $stream2);

        // Verify statistics
        $stats = Psr7Pool::getStats();
        $this->assertEquals(1, $stats['usage']['streams_created']);
        $this->assertEquals(1, $stats['usage']['streams_reused']);
        $this->assertEquals(50.0, $stats['efficiency']['stream_reuse_rate']);
    }

    /**
     * Test borrow methods (aliases)
     */
    public function testBorrowMethods(): void
    {
        $request = Psr7Pool::borrowRequest();
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertEquals('GET', $request->getMethod());

        $response = Psr7Pool::borrowResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $uri = Psr7Pool::borrowUri();
        $this->assertInstanceOf(UriInterface::class, $uri);

        $stream = Psr7Pool::borrowStream();
        $this->assertInstanceOf(StreamInterface::class, $stream);

        // Verify objects were created
        $stats = Psr7Pool::getStats();
        $this->assertGreaterThan(0, $stats['usage']['requests_created']);
        $this->assertGreaterThan(0, $stats['usage']['responses_created']);
        $this->assertGreaterThan(0, $stats['usage']['uris_created']);
        $this->assertGreaterThan(0, $stats['usage']['streams_created']);
    }

    /**
     * Test pool size limits
     */
    public function testPoolSizeLimits(): void
    {
        // Fill pools beyond limit
        $objects = [];
        for ($i = 0; $i < 60; $i++) {
            $request = Psr7Pool::getServerRequest('GET', new Uri('/'), Stream::createFromString(''));
            $response = Psr7Pool::getResponse();
            $uri = Psr7Pool::getUri('/');
            $stream = Psr7Pool::getStream('test');

            $objects[] = [$request, $response, $uri, $stream];
        }

        // Return all objects to pools
        foreach ($objects as [$request, $response, $uri, $stream]) {
            Psr7Pool::returnServerRequest($request);
            Psr7Pool::returnResponse($response);
            Psr7Pool::returnUri($uri);
            Psr7Pool::returnStream($stream);
        }

        // Verify pools are limited to max size
        $stats = Psr7Pool::getStats();
        $this->assertLessThanOrEqual(50, $stats['pool_sizes']['requests']);
        $this->assertLessThanOrEqual(50, $stats['pool_sizes']['responses']);
        $this->assertLessThanOrEqual(50, $stats['pool_sizes']['uris']);
        $this->assertLessThanOrEqual(50, $stats['pool_sizes']['streams']);
    }

    /**
     * Test statistics calculations
     */
    public function testStatisticsCalculations(): void
    {
        // Test empty pool statistics
        $stats = Psr7Pool::getStats();
        $this->assertEquals(0, $stats['efficiency']['request_reuse_rate']);
        $this->assertEquals(0, $stats['efficiency']['response_reuse_rate']);
        $this->assertEquals(0, $stats['efficiency']['uri_reuse_rate']);
        $this->assertEquals(0, $stats['efficiency']['stream_reuse_rate']);

        // Create some objects
        $request1 = Psr7Pool::getServerRequest('GET', new Uri('/'), Stream::createFromString(''));
        $request2 = Psr7Pool::getServerRequest('POST', new Uri('/'), Stream::createFromString(''));

        Psr7Pool::returnServerRequest($request1);

        $request3 = Psr7Pool::getServerRequest('PUT', new Uri('/'), Stream::createFromString(''));

        $stats = Psr7Pool::getStats();
        $this->assertEquals(2, $stats['usage']['requests_created']);
        $this->assertEquals(1, $stats['usage']['requests_reused']);
        $this->assertEquals(33.33, round($stats['efficiency']['request_reuse_rate'], 2));
    }

    /**
     * Test URI parsing in resetUri
     */
    public function testUriParsingInReset(): void
    {
        // Create URI with complex components
        $uri1 = Psr7Pool::getUri('https://user:pass@example.com:8080/path?query=value#fragment');
        $this->assertEquals('https', $uri1->getScheme());
        $this->assertEquals('user:pass', $uri1->getUserInfo()); // getUserInfo returns user:pass format
        $this->assertEquals('example.com', $uri1->getHost());
        $this->assertEquals(8080, $uri1->getPort());
        $this->assertEquals('/path', $uri1->getPath());
        $this->assertEquals('query=value', $uri1->getQuery());
        $this->assertEquals('fragment', $uri1->getFragment());

        Psr7Pool::returnUri($uri1);

        // Test with invalid URI
        $uri2 = Psr7Pool::getUri('://invalid-uri');
        $this->assertInstanceOf(UriInterface::class, $uri2);

        // Test with empty URI
        $uri3 = Psr7Pool::getUri('');
        $this->assertInstanceOf(UriInterface::class, $uri3);
    }

    /**
     * Test stream reset functionality
     */
    public function testStreamReset(): void
    {
        // Create stream with content
        $stream = Psr7Pool::getStream('Original content');
        $this->assertEquals('Original content', (string) $stream);

        // Return to pool
        Psr7Pool::returnStream($stream);

        // Get stream with new content
        $stream2 = Psr7Pool::getStream('New content');
        $this->assertEquals('New content', (string) $stream2);

        // Test with non-seekable/non-writable stream
        $nonSeekableStream = new NonSeekableStream('test');
        Psr7Pool::returnStream($nonSeekableStream);

        // Should not be added to pool (because it's not seekable/writable)
        $stats = Psr7Pool::getStats();
        // None should be in pool (stream2 was reused from stream1)
        $this->assertEquals(0, $stats['pool_sizes']['streams']);
    }

    /**
     * Test response header reset
     */
    public function testResponseHeaderReset(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'custom-value'
        ];

        $response = Psr7Pool::getResponse(200, $headers);
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('custom-value', $response->getHeaderLine('X-Custom-Header'));

        Psr7Pool::returnResponse($response);

        // Get response with different headers
        $newHeaders = ['Content-Type' => 'text/plain'];
        $response2 = Psr7Pool::getResponse(404, $newHeaders);

        $this->assertEquals('text/plain', $response2->getHeaderLine('Content-Type'));
        $this->assertEquals('', $response2->getHeaderLine('X-Custom-Header'));
    }

    /**
     * Test warmUp functionality
     */
    public function testWarmUp(): void
    {
        // Initially pools should be empty
        $stats = Psr7Pool::getStats();
        $this->assertEquals(0, $stats['pool_sizes']['requests']);
        $this->assertEquals(0, $stats['pool_sizes']['responses']);
        $this->assertEquals(0, $stats['pool_sizes']['uris']);
        $this->assertEquals(0, $stats['pool_sizes']['streams']);

        // Warm up pools
        Psr7Pool::warmUp();

        // Verify pools are populated (objects get reused during warmup, so final pool size is 1)
        $stats = Psr7Pool::getStats();
        $this->assertEquals(1, $stats['pool_sizes']['requests']);
        $this->assertEquals(1, $stats['pool_sizes']['responses']);
        $this->assertEquals(1, $stats['pool_sizes']['uris']);
        $this->assertEquals(1, $stats['pool_sizes']['streams']);

        // Verify objects were created and reused
        $this->assertEquals(1, $stats['usage']['requests_created']);
        $this->assertEquals(4, $stats['usage']['requests_reused']);
        $this->assertEquals(1, $stats['usage']['responses_created']);
        $this->assertEquals(4, $stats['usage']['responses_reused']);
    }

    /**
     * Test clearAll functionality
     */
    public function testClearAll(): void
    {
        // Create and return some objects
        $request = Psr7Pool::getServerRequest('GET', new Uri('/'), Stream::createFromString(''));
        $response = Psr7Pool::getResponse();
        $uri = Psr7Pool::getUri('/');
        $stream = Psr7Pool::getStream('test');

        Psr7Pool::returnServerRequest($request);
        Psr7Pool::returnResponse($response);
        Psr7Pool::returnUri($uri);
        Psr7Pool::returnStream($stream);

        // Verify pools have objects
        $stats = Psr7Pool::getStats();
        $this->assertGreaterThan(0, $stats['pool_sizes']['requests']);
        $this->assertGreaterThan(0, $stats['pool_sizes']['responses']);
        $this->assertGreaterThan(0, $stats['pool_sizes']['uris']);
        $this->assertGreaterThan(0, $stats['pool_sizes']['streams']);

        // Clear all
        Psr7Pool::clearAll();

        // Verify pools are empty
        $stats = Psr7Pool::getStats();
        $this->assertEquals(0, $stats['pool_sizes']['requests']);
        $this->assertEquals(0, $stats['pool_sizes']['responses']);
        $this->assertEquals(0, $stats['pool_sizes']['uris']);
        $this->assertEquals(0, $stats['pool_sizes']['streams']);

        // Verify statistics are reset
        $this->assertEquals(0, $stats['usage']['requests_created']);
        $this->assertEquals(0, $stats['usage']['requests_reused']);
        $this->assertEquals(0, $stats['usage']['responses_created']);
        $this->assertEquals(0, $stats['usage']['responses_reused']);
    }

    /**
     * Test clearPools alias
     */
    public function testClearPoolsAlias(): void
    {
        $request = Psr7Pool::getServerRequest('GET', new Uri('/'), Stream::createFromString(''));
        Psr7Pool::returnServerRequest($request);

        $stats = Psr7Pool::getStats();
        $this->assertGreaterThan(0, $stats['pool_sizes']['requests']);

        Psr7Pool::clearPools();

        $stats = Psr7Pool::getStats();
        $this->assertEquals(0, $stats['pool_sizes']['requests']);
    }

    /**
     * Test concurrent pool operations
     */
    public function testConcurrentPoolOperations(): void
    {
        // Simulate concurrent access by creating many objects
        $objects = [];
        for ($i = 0; $i < 100; $i++) {
            $request = Psr7Pool::getServerRequest('GET', new Uri('/'), Stream::createFromString(''));
            $response = Psr7Pool::getResponse();
            $objects[] = [$request, $response];
        }

        // Return objects in different order
        shuffle($objects);
        foreach ($objects as [$request, $response]) {
            Psr7Pool::returnServerRequest($request);
            Psr7Pool::returnResponse($response);
        }

        // Verify statistics are consistent
        $stats = Psr7Pool::getStats();
        $this->assertEquals(100, $stats['usage']['requests_created']);
        $this->assertEquals(100, $stats['usage']['responses_created']);
        $this->assertLessThanOrEqual(50, $stats['pool_sizes']['requests']);
        $this->assertLessThanOrEqual(50, $stats['pool_sizes']['responses']);
    }

    /**
     * Test performance with large pool operations
     */
    public function testPerformanceWithLargePoolOperations(): void
    {
        $startTime = microtime(true);

        // Perform many pool operations
        for ($i = 0; $i < 1000; $i++) {
            $request = Psr7Pool::getServerRequest('GET', new Uri('/'), Stream::createFromString(''));
            $response = Psr7Pool::getResponse();

            Psr7Pool::returnServerRequest($request);
            Psr7Pool::returnResponse($response);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should complete within reasonable time
        $this->assertLessThan(1000, $duration); // Less than 1 second

        // Verify high reuse rate
        $stats = Psr7Pool::getStats();
        $this->assertGreaterThan(90, $stats['efficiency']['request_reuse_rate']);
        $this->assertGreaterThan(90, $stats['efficiency']['response_reuse_rate']);
    }

    /**
     * Test edge cases with null and empty values
     */
    public function testEdgeCases(): void
    {
        // Test with null body
        $response = Psr7Pool::getResponse(200, [], null);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        // Test with empty reason phrase
        $response2 = Psr7Pool::getResponse(200, [], null, '1.1', '');
        $this->assertEquals('', $response2->getReasonPhrase());

        // Test with empty headers
        $request = Psr7Pool::getServerRequest('GET', new Uri('/'), Stream::createFromString(''), []);
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertEquals([], $request->getHeaders());

        // Test with empty server params
        $request2 = Psr7Pool::getServerRequest('GET', new Uri('/'), Stream::createFromString(''), [], '1.1', []);
        $this->assertEquals([], $request2->getServerParams());
    }

    /**
     * Test object immutability is maintained
     */
    public function testObjectImmutability(): void
    {
        // Create request
        $uri = new Uri('/original');
        $request = Psr7Pool::getServerRequest('GET', $uri, Stream::createFromString(''));

        // Modify request
        $modifiedRequest = $request->withMethod('POST');

        // Original should remain unchanged
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('POST', $modifiedRequest->getMethod());
        $this->assertNotSame($request, $modifiedRequest);

        // Same with response
        $response = Psr7Pool::getResponse(200);
        $modifiedResponse = $response->withStatus(404);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(404, $modifiedResponse->getStatusCode());
        $this->assertNotSame($response, $modifiedResponse);
    }

    /**
     * A reused pooled ServerRequest must not leak headers or server params from
     * the previous request it served — sensitive data (Authorization, Cookie,
     * REMOTE_ADDR, etc.) from request N must never surface in request N+1.
     */
    public function testResetServerRequestDoesNotLeakHeadersOrServerParamsBetweenReuses(): void
    {
        $uri = new Uri('/first');
        $firstHeaders = ['Authorization' => 'Bearer secret-token', 'X-User-Id' => '42'];
        $firstServerParams = ['REMOTE_ADDR' => '10.0.0.1', 'HTTPS' => 'on'];

        $first = Psr7Pool::getServerRequest(
            'GET',
            $uri,
            Stream::createFromString(''),
            $firstHeaders,
            '1.1',
            $firstServerParams
        );
        $this->assertEquals('Bearer secret-token', $first->getHeaderLine('Authorization'));
        $this->assertEquals('10.0.0.1', $first->getServerParams()['REMOTE_ADDR']);

        Psr7Pool::returnServerRequest($first);

        // Reused instance, different request, no Authorization/X-User-Id this time
        $second = Psr7Pool::getServerRequest(
            'GET',
            new Uri('/second'),
            Stream::createFromString(''),
            ['Content-Type' => 'application/json'],
            '1.1',
            ['REMOTE_ADDR' => '10.0.0.2']
        );

        $this->assertFalse($second->hasHeader('Authorization'));
        $this->assertFalse($second->hasHeader('X-User-Id'));
        $this->assertEquals('application/json', $second->getHeaderLine('Content-Type'));
        $this->assertEquals(['REMOTE_ADDR' => '10.0.0.2'], $second->getServerParams());
    }
}

/**
 * Mock stream for testing non-seekable streams
 */
class NonSeekableStream implements StreamInterface
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return false;
    }

    public function isSeekable(): bool
    {
        return false; // Non-seekable
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new \RuntimeException('Stream is not seekable');
    }

    public function rewind(): void
    {
        throw new \RuntimeException('Stream is not seekable');
    }

    public function isWritable(): bool
    {
        return false; // Non-writable
    }

    public function write(string $string): int
    {
        throw new \RuntimeException('Stream is not writable');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        return $this->content;
    }

    public function getContents(): string
    {
        return $this->content;
    }

    public function getMetadata(?string $key = null)
    {
        return null;
    }

    public function truncate(int $size): void
    {
        throw new \RuntimeException('Stream is not writable');
    }
}
