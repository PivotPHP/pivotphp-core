<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Middleware\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Core\BaseMiddleware;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Exceptions\HttpException;

/**
 * Tests for BaseMiddleware
 *
 * This class is critical as it's the base for all middleware in the framework.
 * Following the "less is more" principle, focusing on essential functionality.
 */
class BaseMiddlewareTest extends TestCase
{
    private TestableBaseMiddleware $middleware;
    private Request $request;
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TestableBaseMiddleware();
        $this->request = new Request('GET', '/test', '/test');
        $this->response = (new Response())->setTestMode(true);
    }

    /**
     * Test middleware implementation
     */
    public function testMiddlewareImplementation(): void
    {
        $this->assertInstanceOf(BaseMiddleware::class, $this->middleware);
        $this->assertTrue(method_exists($this->middleware, 'handle'));
        $this->assertTrue(method_exists($this->middleware, '__invoke'));
    }

    /**
     * Test __invoke method calls handle
     */
    public function testInvokeCallsHandle(): void
    {
        $called = false;
        $next = function ($req, $res) use (&$called) {
            $called = true;
            return $res;
        };

        $result = $this->middleware->__invoke($this->request, $this->response, $next);

        $this->assertTrue($called);
        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * Test handle method with next callable
     */
    public function testHandleMethodWithNext(): void
    {
        $called = false;
        $next = function ($req, $res) use (&$called) {
            $called = true;
            return $res->json(['handled' => true]);
        };

        $result = $this->middleware->handle($this->request, $this->response, $next);

        $this->assertTrue($called);
        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * Test getHeader with Request object
     */
    public function testGetHeaderWithRequest(): void
    {
        $this->request->setHeaders(['X-Test-Header' => 'test-value']);

        $header = $this->middleware->getHeaderPublic($this->request, 'X-Test-Header');
        $this->assertEquals('test-value', $header);
    }

    /**
     * Test getHeader with default value
     */
    public function testGetHeaderWithDefault(): void
    {
        $header = $this->middleware->getHeaderPublic($this->request, 'Non-Existent-Header', 'default-value');
        $this->assertEquals('default-value', $header);
    }

    /**
     * Test getHeader with generic object (for testing)
     */
    public function testGetHeaderWithGenericObject(): void
    {
        $genericRequest = new \stdClass();
        $genericRequest->headers = ['X-Test-Header' => 'generic-value'];

        $header = $this->middleware->getHeaderPublic($genericRequest, 'X-Test-Header');
        $this->assertEquals('generic-value', $header);
    }

    /**
     * Test getHeader with object headers
     */
    public function testGetHeaderWithObjectHeaders(): void
    {
        $genericRequest = new \stdClass();
        $genericRequest->headers = new \stdClass();
        $genericRequest->headers->{'X-Test-Header'} = 'object-value';

        $header = $this->middleware->getHeaderPublic($genericRequest, 'X-Test-Header');
        $this->assertEquals('object-value', $header);
    }

    /**
     * Test getHeader fallback to $_SERVER
     */
    public function testGetHeaderFallbackToServer(): void
    {
        $_SERVER['HTTP_X_TEST_HEADER'] = 'server-value';

        $genericRequest = new \stdClass();
        $header = $this->middleware->getHeaderPublic($genericRequest, 'X-Test-Header');

        $this->assertEquals('server-value', $header);

        // Cleanup
        unset($_SERVER['HTTP_X_TEST_HEADER']);
    }

    /**
     * Test getHeader with hyphenated header name
     */
    public function testGetHeaderWithHyphenatedName(): void
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

        $genericRequest = new \stdClass();
        $header = $this->middleware->getHeaderPublic($genericRequest, 'Content-Type');

        $this->assertEquals('application/json', $header);

        // Cleanup
        unset($_SERVER['HTTP_CONTENT_TYPE']);
    }

    /**
     * Test isAjaxRequest with Request object
     */
    public function testIsAjaxRequestWithRequest(): void
    {
        // Test non-AJAX request
        $this->assertFalse($this->middleware->isAjaxRequestPublic($this->request));

        // Test AJAX request
        $this->request->setHeaders(['X-Requested-With' => 'XMLHttpRequest']);
        $this->assertTrue($this->middleware->isAjaxRequestPublic($this->request));
    }

    /**
     * Test isAjaxRequest with generic object
     */
    public function testIsAjaxRequestWithGenericObject(): void
    {
        $genericRequest = new \stdClass();
        $genericRequest->headers = ['X-Requested-With' => 'XMLHttpRequest'];

        $this->assertTrue($this->middleware->isAjaxRequestPublic($genericRequest));
    }

    /**
     * Test isAjaxRequest returns false for non-AJAX
     */
    public function testIsAjaxRequestReturnsFalseForNonAjax(): void
    {
        $genericRequest = new \stdClass();
        $genericRequest->headers = ['X-Requested-With' => 'Regular'];

        $this->assertFalse($this->middleware->isAjaxRequestPublic($genericRequest));
    }

    /**
     * Test getClientIp
     */
    public function testGetClientIp(): void
    {
        $ip = $this->middleware->getClientIpPublic($this->request);
        $this->assertIsString($ip);
        // IP should be either a valid IP or 'unknown'
        $this->assertTrue(filter_var($ip, FILTER_VALIDATE_IP) !== false || $ip === 'unknown');
    }

    /**
     * Test isSecureRequest
     */
    public function testIsSecureRequest(): void
    {
        $isSecure = $this->middleware->isSecureRequestPublic($this->request);
        $this->assertIsBool($isSecure);
    }

    /**
     * Test respondWithError throws HttpException
     */
    public function testRespondWithErrorThrowsHttpException(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Test error message');
        $this->expectExceptionCode(400);

        $this->middleware->respondWithErrorPublic($this->response, 400, 'Test error message');
    }

    /**
     * Test respondWithError with custom data
     */
    public function testRespondWithErrorWithCustomData(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Custom error');
        $this->expectExceptionCode(422);

        $this->middleware->respondWithErrorPublic($this->response, 422, 'Custom error', ['field' => 'value']);
    }

    /**
     * Test respondWithSuccess
     */
    public function testRespondWithSuccess(): void
    {
        $result = $this->middleware->respondWithSuccessPublic($this->response);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $body = json_decode($result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Success', $body['message']);
    }

    /**
     * Test respondWithSuccess with custom data
     */
    public function testRespondWithSuccessWithCustomData(): void
    {
        $customData = ['user' => 'john', 'id' => 123];
        $result = $this->middleware->respondWithSuccessPublic($this->response, $customData);

        $this->assertInstanceOf(Response::class, $result);

        $body = json_decode($result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Success', $body['message']);
        $this->assertEquals($customData, $body['data']);
    }

    /**
     * Test respondWithSuccess with custom message
     */
    public function testRespondWithSuccessWithCustomMessage(): void
    {
        $result = $this->middleware->respondWithSuccessPublic($this->response, null, 'Custom success message');

        $body = json_decode($result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Custom success message', $body['message']);
        $this->assertArrayNotHasKey('data', $body);
    }

    /**
     * Test respondWithSuccess with both custom data and message
     */
    public function testRespondWithSuccessWithCustomDataAndMessage(): void
    {
        $customData = ['items' => [1, 2, 3]];
        $result = $this->middleware->respondWithSuccessPublic($this->response, $customData, 'Items retrieved');

        $body = json_decode($result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Items retrieved', $body['message']);
        $this->assertEquals($customData, $body['data']);
    }

    /**
     * Test edge cases for header handling
     */
    public function testHeaderHandlingEdgeCases(): void
    {
        // Test with null request
        $header = $this->middleware->getHeaderPublic(null, 'X-Test');
        $this->assertNull($header);

        // Test with empty header name
        $header = $this->middleware->getHeaderPublic($this->request, '');
        $this->assertNull($header);

        // Test with numeric header value
        $this->request->setHeaders(['X-Numeric' => '123']);
        $header = $this->middleware->getHeaderPublic($this->request, 'X-Numeric');
        $this->assertEquals('123', $header);
    }

    /**
     * Test server header transformation
     */
    public function testServerHeaderTransformation(): void
    {
        // Test various header name transformations
        $_SERVER['HTTP_CONTENT_TYPE'] = 'text/html';
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'custom-value';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token';

        $genericRequest = new \stdClass();

        $this->assertEquals('text/html', $this->middleware->getHeaderPublic($genericRequest, 'Content-Type'));
        $this->assertEquals('custom-value', $this->middleware->getHeaderPublic($genericRequest, 'X-Custom-Header'));
        $this->assertEquals('Bearer token', $this->middleware->getHeaderPublic($genericRequest, 'Authorization'));

        // Cleanup
        unset($_SERVER['HTTP_CONTENT_TYPE'], $_SERVER['HTTP_X_CUSTOM_HEADER'], $_SERVER['HTTP_AUTHORIZATION']);
    }

    /**
     * Test case sensitivity in header handling
     */
    public function testHeaderCaseSensitivity(): void
    {
        $this->request->setHeaders(['x-test-header' => 'lowercase']);

        $header = $this->middleware->getHeaderPublic($this->request, 'X-Test-Header');
        $this->assertEquals('lowercase', $header);
    }

    /**
     * Test middleware chain compatibility
     */
    public function testMiddlewareChainCompatibility(): void
    {
        $step1 = false;
        $step2 = false;

        $next = function ($req, $res) use (&$step1, &$step2) {
            $step1 = true;
            return function ($req, $res) use (&$step2) {
                $step2 = true;
                return $res;
            };
        };

        $result = $this->middleware->handle($this->request, $this->response, $next);

        $this->assertTrue($step1);
        $this->assertIsCallable($result);
    }
}

/**
 * Testable implementation of BaseMiddleware for testing
 */
class TestableBaseMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        return $next($request, $response);
    }

    // Public wrappers for testing protected methods
    public function getHeaderPublic($request, string $header, $default = null)
    {
        return self::getHeader($request, $header, $default);
    }

    public function isAjaxRequestPublic($request): bool
    {
        return $this->isAjaxRequest($request);
    }

    public function getClientIpPublic(Request $request): string
    {
        return $this->getClientIp($request);
    }

    public function isSecureRequestPublic(Request $request): bool
    {
        return $this->isSecureRequest($request);
    }

    public function respondWithErrorPublic(
        Response $response,
        int $statusCode,
        string $message,
        array $data = []
    ): Response {
        return $this->respondWithError($response, $statusCode, $message, $data);
    }

    public function respondWithSuccessPublic(Response $response, $data = null, string $message = 'Success'): Response
    {
        return $this->respondWithSuccess($response, $data, $message);
    }
}
