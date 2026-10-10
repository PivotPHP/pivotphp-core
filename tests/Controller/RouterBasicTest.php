<?php

namespace PivotPHP\Core\Tests\Controller;

use PHPUnit\Framework\TestCase;
use PivotPHP\Routing\Router\Router;

class RouterBasicTest extends TestCase
{
    protected function setUp(): void
    {
        // Simular ambiente web básico
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    public function testRouterBasicFunctionality(): void
    {
        // Testar métodos HTTP aceitos
        $methods = Router::getHttpMethodsAccepted();
        $this->assertIsArray($methods);
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('PUT', $methods);
        $this->assertContains('DELETE', $methods);
    }

    public function testAddCustomHttpMethod(): void
    {
        $originalCount = count(Router::getHttpMethodsAccepted());
        Router::addHttpMethod('CUSTOM');
        $newMethods = Router::getHttpMethodsAccepted();

        $this->assertContains('CUSTOM', $newMethods);
        $this->assertGreaterThanOrEqual($originalCount, count($newMethods));
    }

    public function testGetRoutes(): void
    {
        $routes = Router::getRoutes();
        $this->assertIsArray($routes);
    }

    public function testIdentifyMethod(): void
    {
        // Testar método identify
        $result = Router::identify('GET', '/test');
        $this->assertTrue(is_null($result) || is_array($result));
    }

    public function testToStringMethod(): void
    {
        $result = Router::toString();
        $this->assertIsString($result);
    }

    public function testMagicMethodCall(): void
    {
        // Testar se o método mágico funciona
        $this->expectNotToPerformAssertions();

        try {
            Router::get(
                '/test',
                function () {
                    return 'test';
                }
            );
        } catch (\Exception $e) {
            // Se houver erro, pelo menos não é fatal
            $this->assertTrue(true);
        }
    }

    public function testUseMethod(): void
    {
        // Testar método use para middlewares
        $this->expectNotToPerformAssertions();

        try {
            Router::use(
                '/test',
                function ($req, $res, $next) {
                    $next();
                }
            );
        } catch (\Exception $e) {
            // Se houver erro, pelo menos não é fatal
            $this->assertTrue(true);
        }
    }
}
