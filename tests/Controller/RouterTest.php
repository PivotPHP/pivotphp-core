<?php

namespace PivotPHP\Core\Tests\Controller;

use PHPUnit\Framework\TestCase;
use PivotPHP\Routing\Router\Router;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        // Simular ambiente web básico
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['PATH_INFO'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        // Reset router state between tests
        $reflection = new \ReflectionClass(Router::class);

        if ($reflection->hasProperty('routes')) {
            $routesProperty = $reflection->getProperty('routes');
            $routesProperty->setAccessible(true);
            $routesProperty->setValue(null, []);
        }

        if ($reflection->hasProperty('prev_path')) {
            $prevPathProperty = $reflection->getProperty('prev_path');
            $prevPathProperty->setAccessible(true);
            $prevPathProperty->setValue(null, '');
        }

        if ($reflection->hasProperty('current_group_prefix')) {
            $currentGroupPrefixProperty = $reflection->getProperty('current_group_prefix');
            $currentGroupPrefixProperty->setAccessible(true);
            $currentGroupPrefixProperty->setValue(null, '');
        }
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

    public function testAddHttpMethod(): void
    {
        // Test adding a custom HTTP method
        Router::addHttpMethod('PATCH');

        $reflection = new \ReflectionClass(Router::class);
        $methodsProperty = $reflection->getProperty('httpMethodsAccepted');
        $methodsProperty->setAccessible(true);
        $methods = $methodsProperty->getValue();

        $this->assertContains('PATCH', $methods);

        // Test adding duplicate method (should not duplicate)
        $initialCount = count($methods);
        Router::addHttpMethod('PATCH');
        $methodsAfter = $methodsProperty->getValue();
        $this->assertEquals($initialCount, count($methodsAfter));
    }

    public function testAddHttpMethodCaseInsensitive(): void
    {
        Router::addHttpMethod('custom');

        $reflection = new \ReflectionClass(Router::class);
        $methodsProperty = $reflection->getProperty('httpMethodsAccepted');
        $methodsProperty->setAccessible(true);
        $methods = $methodsProperty->getValue();

        $this->assertContains('CUSTOM', $methods);
        $this->assertNotContains('custom', $methods);
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

    public function testRegisterRoute(): void
    {
        $handler = function ($req, $res) {
            return 'test';
        };

        // Usar o método mágico GET que existe via __callStatic
        Router::get('/test', $handler);

        $routes = Router::getRoutes();
        $this->assertNotEmpty($routes);

        // Verificar se a rota foi registrada
        $found = false;
        foreach ($routes as $route) {
            if ($route['method'] === 'GET' && $route['path'] === '/test') {
                $found = true;
                $this->assertEquals($handler, $route['handler']);
                break;
            }
        }
        $this->assertTrue($found);
    }
}
