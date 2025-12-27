<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\ExpressRequest;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Uri;
use PivotPHP\Core\Http\Psr7\Stream;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Comprehensive test suite for ExpressRequest adapter
 */
class ExpressRequestTest extends TestCase
{
    private ExpressRequest $request;

    protected function setUp(): void
    {
        // Clean globals
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        $_COOKIE = [];
        $_FILES = [];

        // Clear PSR-7 pool to avoid cross-test contamination
        \PivotPHP\Core\Http\Pool\Psr7Pool::clearPools();

        // Create basic request
        $this->request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');
    }

    protected function tearDown(): void
    {
        // Clean up
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        $_COOKIE = [];
        $_FILES = [];
    }

    // ========================================================================
    // BASIC PROPERTIES
    // ========================================================================

    public function testRequestHasBasicProperties(): void
    {
        $this->assertIsObject($this->request->params);
        $this->assertIsObject($this->request->query);
        $this->assertIsObject($this->request->body);
    }

    public function testRequestMethodAccess(): void
    {
        $request = OptimizedHttpFactory::createRequest('POST', '/users', '/users');
        $this->assertEquals('POST', $request->method);
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testRequestPathAccess(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/users/:id', '/users/123');
        $this->assertEquals('/users/:id', $request->path);
        $this->assertEquals('/users/:id', $request->getPath());
        $this->assertEquals('/users/123', $request->pathCallable);
        $this->assertEquals('/users/123', $request->getPathCallable());
    }

    // ========================================================================
    // ROUTE PARAMETERS
    // ========================================================================

    public function testParamExtractsRouteParameters(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/users/:id', '/users/123');

        $this->assertEquals('123', $request->param('id'));
        $this->assertEquals(123, $request->param('id'));
    }

    public function testParamReturnsDefaultWhenNotFound(): void
    {
        $this->assertNull($this->request->param('nonexistent'));
        $this->assertEquals('default', $this->request->param('nonexistent', 'default'));
    }

    public function testGetParamIsSameAsParam(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/posts/:slug', '/posts/hello-world');

        $this->assertEquals('hello-world', $request->getParam('slug'));
        $this->assertEquals($request->param('slug'), $request->getParam('slug'));
    }

    public function testGetParamsReturnsAllParameters(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/users/:id/posts/:postId', '/users/42/posts/99');

        $params = $request->getParams();
        $this->assertInstanceOf(\stdClass::class, $params);
        $this->assertEquals('42', $params->id);
        $this->assertEquals('99', $params->postId);
    }

    public function testParamsPropertyAccess(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/users/:id', '/users/456');

        $this->assertEquals('456', $request->params->id);
    }

    // ========================================================================
    // QUERY PARAMETERS
    // ========================================================================

    public function testGetExtractsQueryParameters(): void
    {
        $_SERVER['QUERY_STRING'] = 'page=2&limit=10';
        $request = OptimizedHttpFactory::createRequest('GET', '/users', '/users');

        $this->assertEquals('2', $request->get('page'));
        $this->assertEquals('10', $request->get('limit'));
    }

    public function testGetReturnsDefaultWhenNotFound(): void
    {
        $this->assertNull($this->request->get('missing'));
        $this->assertEquals('default', $this->request->get('missing', 'default'));
    }

    public function testGetQueryIsSameAsGet(): void
    {
        $_SERVER['QUERY_STRING'] = 'search=test';
        $request = OptimizedHttpFactory::createRequest('GET', '/search', '/search');

        $this->assertEquals($request->get('search'), $request->getQuery('search'));
    }

    public function testGetQuerysReturnsAllQueryParams(): void
    {
        $_SERVER['QUERY_STRING'] = 'foo=bar&baz=qux';
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $query = $request->getQuerys();
        $this->assertInstanceOf(\stdClass::class, $query);
        $this->assertEquals('bar', $query->foo);
        $this->assertEquals('qux', $query->baz);
    }

    public function testQueryPropertyAccess(): void
    {
        $_SERVER['QUERY_STRING'] = 'name=John&age=30';
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $this->assertEquals('John', $request->query->name);
        $this->assertEquals('30', $request->query->age);
    }

    // ========================================================================
    // BODY / INPUT
    // ========================================================================

    public function testInputExtractsBodyParameters(): void
    {
        $_POST = ['username' => 'john', 'email' => 'john@example.com'];
        $request = OptimizedHttpFactory::createRequest('POST', '/register', '/register');

        $this->assertEquals('john', $request->input('username'));
        $this->assertEquals('john@example.com', $request->input('email'));
    }

    public function testInputReturnsDefaultWhenNotFound(): void
    {
        $request = OptimizedHttpFactory::createRequest('POST', '/test', '/test');

        $this->assertNull($request->input('missing'));
        $this->assertEquals('default', $request->input('missing', 'default'));
    }

    public function testBodyPropertyReturnsObject(): void
    {
        $_POST = ['name' => 'Alice', 'role' => 'admin'];
        $request = OptimizedHttpFactory::createRequest('POST', '/users', '/users');

        $this->assertInstanceOf(\stdClass::class, $request->body);
        $this->assertEquals('Alice', $request->body->name);
        $this->assertEquals('admin', $request->body->role);
    }

    public function testBodyPropertyReturnsEmptyObjectWhenNoBody(): void
    {
        // Ensure no $_POST data
        $_POST = [];
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $this->assertInstanceOf(\stdClass::class, $request->body);
        $this->assertEquals(new \stdClass(), $request->body);
    }

    // ========================================================================
    // HEADERS
    // ========================================================================

    public function testHeaderRetrievesRequestHeader(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'CustomValue';
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $this->assertEquals('CustomValue', $request->header('X-Custom-Header'));
    }

    public function testHeaderIsCaseInsensitive(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $request = OptimizedHttpFactory::createRequest('POST', '/api', '/api');

        $this->assertEquals('application/json', $request->header('content-type'));
        $this->assertEquals('application/json', $request->header('Content-Type'));
        $this->assertEquals('application/json', $request->header('CONTENT-TYPE'));
    }

    public function testHeaderReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->request->header('NonExistent'));
    }

    public function testHeadersPropertyAccess(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent/1.0';
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $this->assertNotNull($request->headers);
    }

    // ========================================================================
    // IP ADDRESS
    // ========================================================================

    public function testIpReturnsRemoteAddr(): void
    {
        // Use a public IP (private IPs and reserved ranges are filtered out by ip() method)
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';  // Google DNS - known public IP
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $this->assertEquals('8.8.8.8', $request->ip());
        $this->assertEquals('8.8.8.8', $request->getIp());
    }

    public function testIpConsidersProxyHeaders(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.45, 198.51.100.1';
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        // Should use the first valid public IP
        $ip = $request->ip();
        $this->assertNotEmpty($ip);
    }

    public function testIpFallsBackToDefaultWhenNotAvailable(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $this->assertEquals('0.0.0.0', $request->ip());
    }

    // ========================================================================
    // USER AGENT
    // ========================================================================

    public function testUserAgentReturnsUserAgentString(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $this->assertEquals('Mozilla/5.0', $request->userAgent());
    }

    public function testUserAgentReturnsEmptyStringWhenNotSet(): void
    {
        $this->assertEquals('', $this->request->userAgent());
    }

    // ========================================================================
    // REQUEST TYPE DETECTION
    // ========================================================================

    public function testIsAjaxDetectsXmlHttpRequest(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $request = OptimizedHttpFactory::createRequest('GET', '/api/data', '/api/data');

        $this->assertTrue($request->isAjax());
    }

    public function testIsAjaxReturnsFalseWhenNotAjax(): void
    {
        $this->assertFalse($this->request->isAjax());
    }

    public function testIsSecureDetectsHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $request = OptimizedHttpFactory::createRequest('GET', '/secure', '/secure');

        $this->assertTrue($request->isSecure());
    }

    public function testIsSecureReturnsFalseForHttp(): void
    {
        $this->assertFalse($this->request->isSecure());
    }

    // ========================================================================
    // URL BUILDING
    // ========================================================================

    public function testFullUrlReturnsCompleteUrl(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/path?query=value';
        $request = OptimizedHttpFactory::createRequest('GET', '/path', '/path');

        $fullUrl = $request->fullUrl();
        $this->assertStringContainsString('example.com', $fullUrl);
        $this->assertStringContainsString('/path', $fullUrl);
    }

    // ========================================================================
    // FILE UPLOADS
    // ========================================================================

    public function testHasFileDetectsUploadedFile(): void
    {
        $_FILES = [
            'document' => [
                'name' => 'test.pdf',
                'type' => 'application/pdf',
                'tmp_name' => '/tmp/phpXXXX',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024
            ]
        ];
        $request = OptimizedHttpFactory::createRequest('POST', '/upload', '/upload');

        $this->assertTrue($request->hasFile('document'));
        $this->assertFalse($request->hasFile('nonexistent'));
    }

    public function testFileRetrievesUploadedFile(): void
    {
        $_FILES = [
            'avatar' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpYYYY',
                'error' => UPLOAD_ERR_OK,
                'size' => 2048
            ]
        ];
        $request = OptimizedHttpFactory::createRequest('POST', '/profile', '/profile');

        $file = $request->file('avatar');
        $this->assertNotNull($file);
    }

    public function testFileReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->request->file('missing'));
    }

    public function testFilesPropertyAccess(): void
    {
        $_FILES = [
            'doc' => [
                'name' => 'document.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phpZZZZ',
                'error' => UPLOAD_ERR_OK,
                'size' => 512
            ]
        ];
        $request = OptimizedHttpFactory::createRequest('POST', '/upload', '/upload');

        $files = $request->files;
        $this->assertIsArray($files);
    }

    // ========================================================================
    // ATTRIBUTES (CUSTOM DATA)
    // ========================================================================

    public function testSetAttributeAddsCustomAttribute(): void
    {
        $this->request->setAttribute('userId', 42);

        $this->assertEquals(42, $this->request->getAttribute('userId'));
    }

    public function testGetAttributeReturnsDefaultWhenNotFound(): void
    {
        $this->assertNull($this->request->getAttribute('missing'));
        $this->assertEquals('default', $this->request->getAttribute('missing', 'default'));
    }

    public function testHasAttributeChecksExistence(): void
    {
        $this->request->setAttribute('role', 'admin');

        $this->assertTrue($this->request->hasAttribute('role'));
        $this->assertFalse($this->request->hasAttribute('missing'));
    }

    public function testRemoveAttributeDeletesAttribute(): void
    {
        $this->request->setAttribute('temp', 'value');
        $this->assertTrue($this->request->hasAttribute('temp'));

        $this->request->removeAttribute('temp');
        $this->assertFalse($this->request->hasAttribute('temp'));
    }

    public function testSetAttributesAddsMultipleAttributes(): void
    {
        $this->request->setAttributes([
            'userId' => 1,
            'role' => 'admin',
            'verified' => true
        ]);

        $this->assertEquals(1, $this->request->getAttribute('userId'));
        $this->assertEquals('admin', $this->request->getAttribute('role'));
        $this->assertTrue($this->request->getAttribute('verified'));
    }

    public function testGetAttributesReturnsAllAttributes(): void
    {
        $this->request->setAttribute('a', 1);
        $this->request->setAttribute('b', 2);

        $attrs = $this->request->getAttributes();
        $this->assertIsArray($attrs);
    }

    // ========================================================================
    // ATTRIBUTE PROTECTION
    // ========================================================================

    public function testCannotOverrideNativePropertyViaSetAttribute(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot override native property: method');

        $this->request->setAttribute('method', 'POST');
    }

    public function testCannotOverrideNativePropertyViaMagicSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot override native property: path');

        $this->request->path = '/new';
    }

    public function testCannotUnsetNativeProperty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot unset native property: params');

        unset($this->request->params);
    }

    // ========================================================================
    // MAGIC METHODS
    // ========================================================================

    public function testMagicGetRetrievesAttribute(): void
    {
        $this->request->setAttribute('customField', 'value');

        $this->assertEquals('value', $this->request->customField);
    }

    public function testMagicSetCreatesAttribute(): void
    {
        $this->request->newField = 'newValue';

        $this->assertEquals('newValue', $this->request->getAttribute('newField'));
    }

    public function testMagicIssetChecksAttribute(): void
    {
        $this->request->setAttribute('exists', true);

        $this->assertTrue(isset($this->request->exists));
        $this->assertFalse(isset($this->request->missing));
    }

    public function testMagicUnsetRemovesAttribute(): void
    {
        $this->request->setAttribute('temp', 'value');
        $this->assertTrue(isset($this->request->temp));

        unset($this->request->temp);
        $this->assertFalse(isset($this->request->temp));
    }

    public function testMagicGetAccessesSpecialProperties(): void
    {
        $request = OptimizedHttpFactory::createRequest('POST', '/test', '/test');

        // Special properties should be accessible
        $this->assertIsString($request->method);
        $this->assertIsString($request->path);
        $this->assertIsObject($request->params);
        $this->assertIsObject($request->query);
        $this->assertIsObject($request->body);
    }

    // ========================================================================
    // PSR-7 COMPLIANCE
    // ========================================================================

    public function testImplementsServerRequestInterface(): void
    {
        $this->assertInstanceOf(\Psr\Http\Message\ServerRequestInterface::class, $this->request);
    }

    public function testGetServerParamsDelegates(): void
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $serverParams = $request->getServerParams();
        $this->assertIsArray($serverParams);
    }

    public function testGetMethodReturnsHttpMethod(): void
    {
        $request = OptimizedHttpFactory::createRequest('PUT', '/resource', '/resource');

        $this->assertEquals('PUT', $request->getMethod());
    }

    public function testGetUriReturnsUriInstance(): void
    {
        $uri = $this->request->getUri();

        $this->assertInstanceOf(\Psr\Http\Message\UriInterface::class, $uri);
    }

    public function testWithMethodReturnsNewInstance(): void
    {
        $newRequest = $this->request->withMethod('POST');

        $this->assertNotSame($this->request, $newRequest);
        $this->assertEquals('GET', $this->request->getMethod());
        $this->assertEquals('POST', $newRequest->getMethod());
    }

    public function testWithUriReturnsNewInstance(): void
    {
        $newUri = new Uri('https://example.com/new-path');
        $newRequest = $this->request->withUri($newUri);

        $this->assertNotSame($this->request, $newRequest);
        $this->assertEquals('https://example.com/new-path', (string) $newRequest->getUri());
    }

    public function testWithHeaderReturnsNewInstance(): void
    {
        $newRequest = $this->request->withHeader('X-Custom', 'value');

        $this->assertNotSame($this->request, $newRequest);
        $this->assertEquals('value', $newRequest->getHeaderLine('X-Custom'));
    }

    public function testGetHeadersReturnsArray(): void
    {
        $headers = $this->request->getHeaders();

        $this->assertIsArray($headers);
    }

    public function testGetHeaderLineReturnsString(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $request = OptimizedHttpFactory::createRequest('GET', '/api', '/api');

        $accept = $request->getHeaderLine('Accept');
        $this->assertIsString($accept);
    }

    public function testWithQueryParamsReturnsNewInstance(): void
    {
        $newRequest = $this->request->withQueryParams(['foo' => 'bar']);

        $this->assertNotSame($this->request, $newRequest);
        $this->assertEquals(['foo' => 'bar'], $newRequest->getQueryParams());
    }

    public function testWithParsedBodyReturnsNewInstance(): void
    {
        $newRequest = $this->request->withParsedBody(['data' => 'value']);

        $this->assertNotSame($this->request, $newRequest);
        $this->assertEquals(['data' => 'value'], $newRequest->getParsedBody());
    }

    public function testWithAttributeReturnsNewInstance(): void
    {
        $newRequest = $this->request->withAttribute('key', 'value');

        $this->assertNotSame($this->request, $newRequest);
        $this->assertEquals('value', $newRequest->getAttribute('key'));
    }

    public function testWithoutAttributeReturnsNewInstance(): void
    {
        $request = $this->request->withAttribute('key', 'value');
        $newRequest = $request->withoutAttribute('key');

        $this->assertNotSame($request, $newRequest);
        $this->assertNull($newRequest->getAttribute('key'));
    }

    // ========================================================================
    // STATIC FACTORY
    // ========================================================================

    public function testCreateFromGlobalsCreatesInstance(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test-path';
        $_SERVER['HTTP_HOST'] = 'example.com';

        $request = ExpressRequest::createFromGlobals();

        $this->assertInstanceOf(ExpressRequest::class, $request);
        $this->assertEquals('GET', $request->getMethod());
    }

    // ========================================================================
    // FLUENT INTERFACE
    // ========================================================================

    public function testSetAttributeReturnsThis(): void
    {
        $result = $this->request->setAttribute('key', 'value');

        $this->assertSame($this->request, $result);
    }

    public function testRemoveAttributeReturnsThis(): void
    {
        $this->request->setAttribute('key', 'value');
        $result = $this->request->removeAttribute('key');

        $this->assertSame($this->request, $result);
    }

    public function testSetAttributesReturnsThis(): void
    {
        $result = $this->request->setAttributes(['a' => 1, 'b' => 2]);

        $this->assertSame($this->request, $result);
    }

    public function testSetPathReturnsThis(): void
    {
        $result = $this->request->setPath('/new-path');

        $this->assertSame($this->request, $result);
        $this->assertEquals('/new-path', $this->request->getPath());
    }
}
