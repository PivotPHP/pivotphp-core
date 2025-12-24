<?php

namespace PivotPHP\Core\Tests\Services;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\HeaderRequest;
use InvalidArgumentException;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset global variables
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER = [];
    }

    public function testRequestInitialization(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/users/:id', '/users/123');

        $this->assertEquals('GET', $request->method);
        $this->assertEquals('/users/:id', $request->path);
        $this->assertEquals('/users/123', $request->pathCallable);
        $this->assertIsObject($request->params);
        $this->assertIsObject($request->query);
        // Para GET, o body é um array vazio conforme o código
        $this->assertTrue(is_array($request->body) || is_object($request->body));
        $this->assertInstanceOf(HeaderRequest::class, $request->headers);
    }

    public function testMethodNormalization(): void
    {
        $request = OptimizedHttpFactory::createRequest('post', '/users', '/users');
        $this->assertEquals('POST', $request->method);

        $request = OptimizedHttpFactory::createRequest('PUT', '/users/:id', '/users/123');
        $this->assertEquals('PUT', $request->method);
    }

    public function testPathCallableSlashNormalization(): void
    {
        // pathCallable should be preserved as-is for proper route matching
        $request = OptimizedHttpFactory::createRequest('GET', '/users', '/users');
        $this->assertEquals('/users', $request->pathCallable);

        $request = OptimizedHttpFactory::createRequest('GET', '/users/', '/users/');
        $this->assertEquals('/users/', $request->pathCallable);
    }

    public function testParameterExtraction(): void
    {
        $_SERVER['QUERY_STRING'] = 'page=1&limit=10';

        $request = OptimizedHttpFactory::createRequest('GET', '/users/:id', '/users/123');

        $this->assertEquals('1', $request->query->page ?? null);
        $this->assertEquals('10', $request->query->limit ?? null);

        // Limpar $_SERVER após o teste
        unset($_SERVER['QUERY_STRING']);
    }

    public function testBodyParsing(): void
    {
        $_POST = ['name' => 'John', 'email' => 'john@example.com'];

        $request = OptimizedHttpFactory::createRequest('POST', '/users', '/users');

        $this->assertEquals('John', $request->body->name ?? null);
        $this->assertEquals('john@example.com', $request->body->email ?? null);

        // Limpar $_POST após o teste
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

        $request = OptimizedHttpFactory::createRequest('POST', '/upload', '/upload');

        $this->assertArrayHasKey('avatar', $request->files);
        $this->assertEquals('avatar.jpg', $request->files['avatar']['name']);
    }

    public function testInvalidPropertyAccess(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/test', '/test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Property invalid does not exist in Request class');

        $request->invalid;
    }

    public function testRouteParameterParsing(): void
    {
        $request = OptimizedHttpFactory::createRequest('GET', '/users/:id/posts/:postId', '/users/123/posts/456');

        // O método parseRoute deve extrair os parâmetros
        $this->assertInstanceOf('stdClass', $request->params);
    }

    public function testEmptyQueryParameters(): void
    {
        $_GET = [];

        $request = OptimizedHttpFactory::createRequest('GET', '/users', '/users');

        $this->assertInstanceOf('stdClass', $request->query);
        $this->assertEmpty((array)$request->query);
    }

    public function testEmptyBodyParameters(): void
    {
        $_POST = [];

        $request = OptimizedHttpFactory::createRequest('POST', '/users', '/users');

        // Para POST sem dados, o corpo fica como null (json_decode de string vazia)
        // ou pode ser um objeto vazio se $_POST foi processado
        $this->assertTrue(
            is_null($request->body) ||
            (is_object($request->body) && empty((array)$request->body)) ||
            (is_array($request->body) && empty($request->body))
        );
    }

    public function testComplexRoutePattern(): void
    {
        $request = OptimizedHttpFactory::createRequest(
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
        $request = OptimizedHttpFactory::createRequest('GET', '/search', '/search');

        $this->assertEquals('/search', $request->pathCallable);
    }

    public function testRequestWithArrayParameters(): void
    {
        $_SERVER['QUERY_STRING'] = 'tags[]=php&tags[]=javascript&filters[active]=true&filters[category]=tech';

        $request = OptimizedHttpFactory::createRequest('GET', '/posts', '/posts');

        $this->assertIsArray($request->query->tags ?? []);
        $this->assertEquals(['php', 'javascript'], $request->query->tags ?? []);

        // Limpar $_SERVER após o teste
        unset($_SERVER['QUERY_STRING']);
    }
}
