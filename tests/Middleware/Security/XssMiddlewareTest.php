<?php

namespace PivotPHP\Core\Tests\Middleware\Security;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Security\XssMiddleware;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class XssMiddlewareTest extends TestCase
{
    private XssMiddleware $middleware;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new XssMiddleware();

        $response = new Response();
        $response->status(200);

        $this->handler = new class ($response) implements RequestHandlerInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    public function testSanitizesScriptTags(): void
    {
        $malicious = '<script>alert("XSS")</script>Hello';
        $clean = XssMiddleware::sanitize($malicious);

        $this->assertEquals('Hello', $clean);
        $this->assertStringNotContainsString('<script>', $clean);
    }

    public function testSanitizesIframeTags(): void
    {
        $malicious = '<iframe src="evil.com"></iframe>Hello';
        $clean = XssMiddleware::sanitize($malicious);

        $this->assertEquals('Hello', $clean);
        $this->assertStringNotContainsString('<iframe>', $clean);
    }

    public function testSanitizesSvgTags(): void
    {
        $malicious = '<svg onload=alert(1)>Hello';
        $clean = XssMiddleware::sanitize($malicious);

        $this->assertStringNotContainsString('<svg>', $clean);
        $this->assertStringNotContainsString('onload', $clean);
    }

    public function testSanitizesEmbedTags(): void
    {
        $malicious = '<embed src="evil.swf">Hello';
        $clean = XssMiddleware::sanitize($malicious);

        $this->assertStringNotContainsString('<embed>', $clean);
    }

    public function testSanitizesObjectTags(): void
    {
        $malicious = '<object data="evil.swf"></object>Hello';
        $clean = XssMiddleware::sanitize($malicious);

        $this->assertEquals('Hello', $clean);
        $this->assertStringNotContainsString('<object>', $clean);
    }

    public function testSanitizesEventHandlers(): void
    {
        $malicious = '<div onclick="alert(1)">Click</div>';
        $clean = XssMiddleware::sanitize($malicious);

        $this->assertStringNotContainsString('onclick', $clean);
    }

    public function testSanitizesJavascriptProtocol(): void
    {
        $malicious = 'javascript:alert(1)';
        $clean = XssMiddleware::sanitize($malicious);

        $this->assertStringNotContainsString('javascript:', $clean);
    }

    public function testSanitizesVbscriptProtocol(): void
    {
        $malicious = 'vbscript:msgbox(1)';
        $clean = XssMiddleware::sanitize($malicious);

        $this->assertStringNotContainsString('vbscript:', $clean);
    }

    public function testSanitizesDataTextHtml(): void
    {
        $malicious = 'data:text/html,<script>alert(1)</script>';
        $clean = XssMiddleware::sanitize($malicious);

        $this->assertStringNotContainsString('data:text/html', $clean);
    }

    public function testCleansUrl(): void
    {
        $this->assertEquals('', XssMiddleware::cleanUrl('javascript:alert(1)'));
        $this->assertEquals('', XssMiddleware::cleanUrl('data:text/html,test'));
        $this->assertEquals('', XssMiddleware::cleanUrl('vbscript:msgbox(1)'));
        $this->assertEquals('https://example.com', XssMiddleware::cleanUrl('https://example.com'));
    }

    public function testDetectsXss(): void
    {
        $this->assertTrue(XssMiddleware::containsXss('<script>alert(1)</script>'));
        $this->assertTrue(XssMiddleware::containsXss('<div onclick="alert(1)">'));
        $this->assertTrue(XssMiddleware::containsXss('javascript:alert(1)'));
        $this->assertFalse(XssMiddleware::containsXss('Hello World'));
    }

    public function testSanitizesRequestBody(): void
    {
        $request = OptimizedHttpFactory::createRequest('POST', '/', '/');
        $request = $request->withParsedBody(
            [
                'name' => 'John<script>alert(1)</script>',
                'email' => 'test@example.com'
            ]
        );

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testSanitizesNestedArrays(): void
    {
        $request = OptimizedHttpFactory::createRequest('POST', '/', '/');
        $request = $request->withParsedBody(
            [
                'user' => [
                    'name' => 'John<script>alert(1)</script>',
                    'profile' => [
                        'bio' => '<iframe src="evil.com"></iframe>Hello'
                    ]
                ]
            ]
        );

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testAddsSecurityHeaders(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/', '/');
        $result = $this->middleware->process($request, $this->handler);

        // Check CSP header
        $this->assertNotEmpty($result->getHeaderLine('Content-Security-Policy'));
        $this->assertStringContainsString("default-src 'self'", $result->getHeaderLine('Content-Security-Policy'));

        // Check other security headers
        $this->assertEquals('nosniff', $result->getHeaderLine('X-Content-Type-Options'));
        $this->assertEquals('DENY', $result->getHeaderLine('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $result->getHeaderLine('X-XSS-Protection'));
        $this->assertNotEmpty($result->getHeaderLine('Referrer-Policy'));
    }

    public function testCanDisableCspHeaders(): void
    {
        $middleware = new XssMiddleware('', false);

        $request = OptimizedHttpFactory::createRequest('GET', '/', '/');
        $result = $middleware->process($request, $this->handler);

        $this->assertEmpty($result->getHeaderLine('Content-Security-Policy'));
    }

    public function testAllowsSpecificTags(): void
    {
        $input = '<p>Hello</p><script>alert(1)</script>';
        $clean = XssMiddleware::sanitize($input, '<p>');

        $this->assertStringContainsString('<p>Hello</p>', $clean);
        $this->assertStringNotContainsString('<script>', $clean);
    }

    public function testHandlesEmptyInput(): void
    {
        $this->assertEquals('', XssMiddleware::sanitize(''));
        $this->assertEquals('', XssMiddleware::sanitize('   '));
    }

    public function testTrimsWhitespace(): void
    {
        $clean = XssMiddleware::sanitize('  Hello World  ');
        $this->assertEquals('Hello World', $clean);
    }
}
