<?php

namespace PivotPHP\Core\Tests;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Routing\Router\Router;

class ApplicationTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new Application();

        // Reset global state
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        // Clean any output buffers that might have been opened
        while (ob_get_level() > 1) { // Manter pelo menos 1 nível para PHPUnit
            ob_end_clean();
        }

        // Limpar output buffer se tiver conteúdo
        if (ob_get_length()) {
            ob_clean();
        }
    }

    public function testAppInitialization(): void
    {
        $this->assertInstanceOf(Application::class, $this->app);
    }

    public function testRouterMethodsExist(): void
    {
        // Test that essential methods exist in Application
        $this->assertTrue(method_exists($this->app, 'use'));
        $this->assertTrue(method_exists($this->app, 'setBaseUrl'));
        $this->assertTrue(method_exists($this->app, 'getBaseUrl'));
        $this->assertTrue(method_exists($this->app, 'get'));
        $this->assertTrue(method_exists($this->app, 'post'));
        $this->assertTrue(method_exists($this->app, 'put'));
        $this->assertTrue(method_exists($this->app, 'delete'));
    }

    public function testUseMethod(): void
    {
        $middleware = function ($req, $res, $next) {
            $next();
        };

        // use() method returns void, not the app instance
        $this->app->use($middleware);
        $this->assertInstanceOf(Application::class, $this->app);
    }

    public function testSetBaseUrl(): void
    {
        $baseUrl = 'https://api.example.com';
        $this->app->setBaseUrl($baseUrl);
        $this->assertEquals($baseUrl, $this->app->getBaseUrl());
    }

    public function testRouteRegistration(): void
    {
        $handler = function ($req, $res) {
            $res->json(['message' => 'Hello World']);
        };

        // These methods are handled by __call and return values from Router
        $this->app->get('/test', $handler);
        $this->app->post('/users', $handler);
        $this->app->put('/users/:id', $handler);
        $this->app->delete('/users/:id', $handler);

        // If no exception is thrown, the methods work correctly
        $this->assertTrue(true);
    }

    public function testCallMagicMethod(): void
    {
        $handler = function ($req, $res) {
            $res->json(['message' => 'Test']);
        };

        // Test that HTTP methods can be called via __call
        try {
            $this->app->get('/test', $handler);
            $this->app->post('/test', $handler);
            $this->app->put('/test', $handler);
            $this->app->delete('/test', $handler);
            $this->assertTrue(true); // If no exception, methods work
        } catch (\BadMethodCallException $e) {
            $this->fail('HTTP methods should be callable via __call');
        }
    }

    public function testBaseUrlHandling(): void
    {
        // Test with trailing slash
        $this->app->setBaseUrl('https://api.example.com/');
        $this->assertEquals('https://api.example.com', $this->app->getBaseUrl());

        // Test without trailing slash
        $this->app->setBaseUrl('https://api.example.com');
        $this->assertEquals('https://api.example.com', $this->app->getBaseUrl());

        // Test with null
        $app2 = new Application();
        $this->assertNull($app2->getBaseUrl());
    }

    public function testConstructorWithBaseUrl(): void
    {
        $basePath = '/path/to/app';
        $app = new Application($basePath);

        // O construtor recebe basePath, não baseUrl
        // Para testar baseUrl, precisamos usar setBaseUrl
        $baseUrl = 'https://test.example.com';
        $app->setBaseUrl($baseUrl);
        $this->assertEquals($baseUrl, $app->getBaseUrl());
    }
}
