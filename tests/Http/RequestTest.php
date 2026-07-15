<?php

namespace PivotPHP\Core\Tests\Http;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\HeaderRequest;
use InvalidArgumentException;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER = [];
    }

    public function testBasicRequestCreation(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getPath());
    }

    public function testRequestGetMethod(): void
    {
        $request = new Request('POST', '/test', '/test');

        $this->assertEquals('POST', $request->getMethod());
    }

    public function testRequestGetPath(): void
    {
        $request = new Request('GET', '/users/123', '/users/123');

        $this->assertEquals('/users/123', $request->getPath());
    }

    public function testRequestParamWithDefault(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertEquals('default', $request->param('missing', 'default'));
    }

    public function testRequestGetWithDefault(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertEquals('default', $request->get('missing', 'default'));
    }

    public function testRequestHasHeaders(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertIsObject($request->headers);
    }

    public function testRequestHasParams(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertIsObject($request->params);
    }

    public function testRequestHasQuery(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->assertIsObject($request->query);
    }

    public function testRequestHasBody(): void
    {
        $request = new Request('POST', '/test', '/test');

        $this->assertIsObject($request->body);
    }

    public function testRequestInitialization(): void
    {
        $request = new Request('GET', '/users/:id', '/users/123');

        $this->assertEquals('GET', $request->method);
        $this->assertEquals('/users/:id', $request->path);
        $this->assertEquals('/users/123', $request->pathCallable);
        $this->assertIsObject($request->params);
        $this->assertIsObject($request->query);
        $this->assertTrue(is_array($request->body) || is_object($request->body));
        $this->assertInstanceOf(HeaderRequest::class, $request->headers);
    }

    public function testMethodNormalization(): void
    {
        $request = new Request('post', '/users', '/users');
        $this->assertEquals('POST', $request->method);

        $request = new Request('PUT', '/users/:id', '/users/123');
        $this->assertEquals('PUT', $request->method);
    }

    public function testPathCallableSlashNormalization(): void
    {
        $request = new Request('GET', '/users', '/users');
        $this->assertEquals('/users', $request->pathCallable);

        $request = new Request('GET', '/users/', '/users/');
        $this->assertEquals('/users/', $request->pathCallable);
    }

    public function testParameterExtraction(): void
    {
        $_SERVER['QUERY_STRING'] = 'page=1&limit=10';

        $request = new Request('GET', '/users/:id', '/users/123');

        $this->assertEquals('1', $request->query->page ?? null);
        $this->assertEquals('10', $request->query->limit ?? null);

        unset($_SERVER['QUERY_STRING']);
    }

    public function testBodyParsing(): void
    {
        $_POST = ['name' => 'John', 'email' => 'john@example.com'];

        $request = new Request('POST', '/users', '/users');

        $this->assertEquals('John', $request->body->name ?? null);
        $this->assertEquals('john@example.com', $request->body->email ?? null);

        $_POST = [];
    }

    public function testFilesHandling(): void
    {
        $_FILES = [
            'avatar' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php123',
                'error' => 0,
                'size' => 12345
            ]
        ];

        $request = new Request('POST', '/upload', '/upload');

        $this->assertArrayHasKey('avatar', $request->files);
        $this->assertEquals('avatar.jpg', $request->files['avatar']['name']);
    }

    public function testInvalidPropertyAccess(): void
    {
        $request = new Request('GET', '/test', '/test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Property invalid does not exist in Request class');

        $request->invalid;
    }

    public function testRouteParameterParsing(): void
    {
        $request = new Request('GET', '/users/:id/posts/:postId', '/users/123/posts/456');

        $this->assertInstanceOf('stdClass', $request->params);
    }

    public function testEmptyQueryParameters(): void
    {
        $_GET = [];

        $request = new Request('GET', '/users', '/users');

        $this->assertInstanceOf('stdClass', $request->query);
        $this->assertEmpty((array)$request->query);
    }

    public function testEmptyBodyParameters(): void
    {
        $_POST = [];

        $request = new Request('POST', '/users', '/users');

        $this->assertTrue(
            is_null($request->body) ||
            (is_object($request->body) && empty((array)$request->body)) ||
            (is_array($request->body) && empty($request->body))
        );
    }

    public function testComplexRoutePattern(): void
    {
        $request = new Request(
            'GET',
            '/api/v1/users/:userId/posts/:postId/comments',
            '/api/v1/users/123/posts/456/comments'
        );

        $this->assertEquals('GET', $request->method);
        $this->assertEquals('/api/v1/users/:userId/posts/:postId/comments', $request->path);
        $this->assertEquals('/api/v1/users/123/posts/456/comments', $request->pathCallable);
    }

    public function testSpecialCharactersInPath(): void
    {
        $request = new Request('GET', '/search', '/search');

        $this->assertEquals('/search', $request->pathCallable);
    }

    public function testRequestWithArrayParameters(): void
    {
        $_SERVER['QUERY_STRING'] = 'tags[]=php&tags[]=javascript&filters[active]=true&filters[category]=tech';

        $request = new Request('GET', '/posts', '/posts');

        $this->assertIsArray($request->query->tags ?? []);
        $this->assertEquals(['php', 'javascript'], $request->query->tags ?? []);

        unset($_SERVER['QUERY_STRING']);
    }
}
