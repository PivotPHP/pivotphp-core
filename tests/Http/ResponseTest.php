<?php

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Response;

class ResponseTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();
        $this->response->setTestMode(true);
    }

    public function testBasicResponseCreation(): void
    {
        $response = new Response();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testResponseWithStatus(): void
    {
        $response = new Response();
        $response->status(404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testResponseJson(): void
    {
        $response = new Response();
        $data = ['message' => 'success'];

        $result = $response->json($data);

        $this->assertSame($response, $result);
        $this->assertEquals('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    public function testResponseText(): void
    {
        $response = new Response();

        $result = $response->text('Hello World');

        $this->assertSame($response, $result);
        $this->assertEquals('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    public function testResponseHtml(): void
    {
        $response = new Response();

        $result = $response->html('<h1>Hello</h1>');

        $this->assertSame($response, $result);
        $this->assertEquals('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    public function testResponseWithHeader(): void
    {
        $response = new Response();

        $result = $response->header('X-Custom', 'value');

        $this->assertSame($response, $result);
        $this->assertEquals('value', $response->getHeaderLine('X-Custom'));
    }

    public function testResponseRedirect(): void
    {
        $response = new Response();

        $result = $response->redirect('/new-path');

        $this->assertSame($response, $result);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/new-path', $response->getHeaderLine('Location'));
    }

    public function testResponseRedirectWithCustomStatus(): void
    {
        $response = new Response();

        $result = $response->redirect('/new-path', 301);

        $this->assertSame($response, $result);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/new-path', $response->getHeaderLine('Location'));
    }

    public function testResponseInitialization(): void
    {
        $this->assertInstanceOf(Response::class, $this->response);
    }

    public function testStatusMethod(): void
    {
        $result = $this->response->status(404);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame($this->response, $result);
        $this->assertEquals(404, $this->response->getStatusCode());
    }

    public function testHeaderMethod(): void
    {
        $result = $this->response->header('Content-Type', 'application/json');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame($this->response, $result);

        $headers = $this->response->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', $headers['Content-Type']);
    }

    public function testJsonResponse(): void
    {
        $data = ['message' => 'Hello World', 'status' => 'success'];

        $result = $this->response->json($data);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(json_encode($data), $this->response->getBody());

        $headers = $this->response->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertStringContainsString('application/json', $headers['Content-Type']);
    }

    public function testTextResponse(): void
    {
        $text = 'Hello World';

        $result = $this->response->text($text);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($text, $this->response->getBody());

        $headers = $this->response->getHeaders();
        $this->assertEquals('text/plain; charset=utf-8', $headers['Content-Type']);
    }

    public function testHtmlResponse(): void
    {
        $html = '<h1>Hello World</h1><p>This is HTML content</p>';

        $result = $this->response->html($html);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($html, $this->response->getBody());

        $headers = $this->response->getHeaders();
        $this->assertEquals('text/html; charset=utf-8', $headers['Content-Type']);
    }

    public function testMethodChaining(): void
    {
        $data = ['message' => 'Created successfully'];

        $result = $this->response
            ->status(201)
            ->header('X-Custom-Header', 'custom-value')
            ->json($data);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(json_encode($data), $this->response->getBody());
        $this->assertEquals(201, $this->response->getStatusCode());

        $headers = $this->response->getHeaders();
        $this->assertEquals('custom-value', $headers['X-Custom-Header']);
    }

    public function testComplexJsonResponse(): void
    {
        $complexData = [
            'users' => [
                ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2
            ],
            'meta' => [
                'timestamp' => '2023-01-01T00:00:00Z',
                'version' => '1.0.0'
            ]
        ];

        $this->response->json($complexData);

        $this->assertEquals(json_encode($complexData), $this->response->getBody());
    }

    public function testEmptyJsonResponse(): void
    {
        $this->response->json([]);

        $this->assertEquals('[]', $this->response->getBody());
    }

    public function testNullJsonResponse(): void
    {
        $this->response->json(null);

        $this->assertEquals('null', $this->response->getBody());
    }

    public function testBooleanJsonResponse(): void
    {
        $response1 = new Response();
        $response1->setTestMode(true);
        $response1->json(true);
        $this->assertEquals('true', $response1->getBody());

        $response2 = new Response();
        $response2->setTestMode(true);
        $response2->json(false);
        $this->assertEquals('false', $response2->getBody());
    }

    public function testNumericJsonResponse(): void
    {
        $response1 = new Response();
        $response1->setTestMode(true);
        $response1->json(42);
        $this->assertEquals('42', $response1->getBody());

        $response2 = new Response();
        $response2->setTestMode(true);
        $response2->json(3.14);
        $this->assertEquals('3.14', $response2->getBody());
    }

    public function testStringJsonResponse(): void
    {
        $this->response->json('Hello World');

        $this->assertEquals('"Hello World"', $this->response->getBody());
    }

    public function testEmptyTextResponse(): void
    {
        $this->response->text('');

        $this->assertEquals('', $this->response->getBody());
    }

    public function testMultilineTextResponse(): void
    {
        $text = "Line 1\nLine 2\nLine 3";

        $this->response->text($text);

        $this->assertEquals($text, $this->response->getBody());
    }

    public function testHtmlWithSpecialCharacters(): void
    {
        $html = '<div>Special chars: &amp; &lt; &gt; &quot; &#39;</div>';

        $this->response->html($html);

        $this->assertEquals($html, $this->response->getBody());
    }

    public function testMultipleHeaders(): void
    {
        $result = $this->response
            ->header('Content-Type', 'application/json')
            ->header('X-Custom-Header', 'custom-value')
            ->header('Cache-Control', 'no-cache');

        $this->assertInstanceOf(Response::class, $result);

        $headers = $this->response->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('X-Custom-Header', $headers);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('custom-value', $headers['X-Custom-Header']);
        $this->assertEquals('no-cache', $headers['Cache-Control']);
    }

    public function testStatusCodes(): void
    {
        $response1 = new Response();
        $response1->status(200);
        $this->assertEquals(200, $response1->getStatusCode());

        $response2 = new Response();
        $response2->status(404);
        $this->assertEquals(404, $response2->getStatusCode());

        $response3 = new Response();
        $response3->status(500);
        $this->assertEquals(500, $response3->getStatusCode());

        $response4 = new Response();
        $response4->status(201);
        $this->assertEquals(201, $response4->getStatusCode());
    }

    public function testTestModeToggle(): void
    {
        $this->assertTrue($this->response->isTestMode());

        $this->response->setTestMode(false);
        $this->assertFalse($this->response->isTestMode());

        $this->response->setTestMode(true);
        $this->assertTrue($this->response->isTestMode());
    }
}
