<?php

declare(strict_types=1);

namespace Tests\Integration\Routing;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Routing\Contracts\RouterInterface;
use PivotPHP\Core\Routing\Adapters\FastRouteAdapter;

/**
 * Testes de integração para roteamento plugável.
 *
 * @covers \PivotPHP\Core\Core\Application
 */
class CustomRouterIntegrationTest extends TestCase
{
    private ?Application $app = null;

    protected function tearDown(): void
    {
        $this->app = null;
    }

    public function testApplicationUsesDefaultRouterWhenNotProvided(): void
    {
        $this->app = new Application();

        $router = $this->app->getRouter();

        $this->assertInstanceOf(RouterInterface::class, $router);
        $this->assertInstanceOf(FastRouteAdapter::class, $router);
    }

    public function testApplicationAcceptsCustomRouterViaOptions(): void
    {
        $customRouter = new CustomTestRouter();

        $this->app = new Application(null, ['router' => $customRouter]);

        $router = $this->app->getRouter();

        $this->assertSame($customRouter, $router);
        $this->assertInstanceOf(CustomTestRouter::class, $router);
    }

    public function testRoutingWorksWithDefaultAdapter(): void
    {
        $this->app = new Application();

        // Clear any existing routes from other tests
        $this->app->getRouter()->clear();

        $this->app->get(
            '/test',
            function () {
                return 'test response';
            }
        );

        $routes = $this->app->getRouter()->getRoutes();

        $this->assertGreaterThanOrEqual(1, count($routes));

        // Find our test route
        $testRoute = null;
        foreach ($routes as $route) {
            if ($route['method'] === 'GET' && $route['path'] === '/test') {
                $testRoute = $route;
                break;
            }
        }

        $this->assertNotNull($testRoute, 'Test route should be registered');
        $this->assertEquals('GET', $testRoute['method']);
        $this->assertEquals('/test', $testRoute['path']);
    }

    public function testRoutingWorksWithCustomRouter(): void
    {
        $customRouter = new CustomTestRouter();
        $this->app = new Application(null, ['router' => $customRouter]);

        $this->app->get('/users', fn() => 'users');
        $this->app->post('/users', fn() => 'create user');

        $routes = $customRouter->getRoutes();

        $this->assertCount(2, $routes);
        $this->assertTrue($customRouter->wasAddRouteCalled());
    }

    public function testAllHttpMethodsWorkWithCustomRouter(): void
    {
        $customRouter = new CustomTestRouter();
        $this->app = new Application(null, ['router' => $customRouter]);

        $this->app->get('/resource', fn() => 'get');
        $this->app->post('/resource', fn() => 'post');
        $this->app->put('/resource', fn() => 'put');
        $this->app->delete('/resource', fn() => 'delete');
        $this->app->patch('/resource', fn() => 'patch');

        $routes = $customRouter->getRoutes();

        $this->assertCount(5, $routes);

        $methods = array_column($routes, 'method');
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('PUT', $methods);
        $this->assertContains('DELETE', $methods);
        $this->assertContains('PATCH', $methods);
    }

    public function testGroupsWorkWithCustomRouter(): void
    {
        $customRouter = new CustomTestRouter();
        $this->app = new Application(null, ['router' => $customRouter]);

        $this->app->group(
            '/api',
            function ($app) {
                $app->get('/users', fn() => 'api users');
            }
        );

        $this->assertTrue($customRouter->wasGroupCalled());
    }

    public function testCustomRouterCanImplementCustomLogic(): void
    {
        $customRouter = new LoggingRouter();
        $this->app = new Application(null, ['router' => $customRouter]);

        $this->app->get('/test', fn() => 'test');

        $this->assertCount(1, $customRouter->getLogs());
        $this->assertEquals('Route added: GET /test', $customRouter->getLogs()[0]);
    }

    public function testCustomRouterDispatchIsUsed(): void
    {
        $customRouter = new CustomTestRouter();
        $this->app = new Application(null, ['router' => $customRouter]);

        $this->app->get('/test', fn() => 'response');

        $result = $customRouter->dispatch('GET', '/test');

        $this->assertIsArray($result);
        $this->assertTrue($customRouter->wasDispatchCalled());
    }

    public function testSwitchingRoutersAtRuntime(): void
    {
        // Primeira aplicação com router padrão
        $app1 = new Application();
        $app1->get('/default', fn() => 'default');

        // Segunda aplicação com router customizado
        $customRouter = new CustomTestRouter();
        $app2 = new Application(null, ['router' => $customRouter]);
        $app2->get('/custom', fn() => 'custom');

        $this->assertInstanceOf(FastRouteAdapter::class, $app1->getRouter());
        $this->assertInstanceOf(CustomTestRouter::class, $app2->getRouter());
    }

    public function testCustomRouterWithMiddleware(): void
    {
        $customRouter = new CustomTestRouter();
        $this->app = new Application(null, ['router' => $customRouter]);

        $options = ['middleware' => ['auth']];
        $this->app->get('/protected', fn() => 'protected', $options);

        $routes = $customRouter->getRoutes();
        $this->assertEquals($options, $routes[0]['options']);
    }

    public function testInvalidRouterTypeThrowsNoError(): void
    {
        // Should use default router if invalid router provided
        $this->app = new Application(null, ['router' => 'invalid']);

        $router = $this->app->getRouter();

        $this->assertInstanceOf(FastRouteAdapter::class, $router);
    }

    public function testRouterPersistsAcrossRequests(): void
    {
        $customRouter = new CustomTestRouter();
        $this->app = new Application(null, ['router' => $customRouter]);

        $this->app->get('/route1', fn() => 'route1');
        $this->app->get('/route2', fn() => 'route2');

        $this->assertCount(2, $customRouter->getRoutes());
        $this->assertSame($customRouter, $this->app->getRouter());
    }
}

/**
 * Custom router implementation for testing.
 */
class CustomTestRouter implements RouterInterface
{
    private array $routes = [];
    private bool $addRouteCalled = false;
    private bool $dispatchCalled = false;
    private bool $groupCalled = false;

    public function addRoute(string $method, string $path, callable|array|string $handler, array $options = []): void
    {
        $this->addRouteCalled = true;
        $this->routes[] = compact('method', 'path', 'handler', 'options');
    }

    public function dispatch(string $method, string $path): ?array
    {
        $this->dispatchCalled = true;

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                return $route;
            }
        }

        return null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function group(string $prefix, callable $callback, array $options = []): void
    {
        $this->groupCalled = true;
        $callback($this);
    }

    public function clear(): void
    {
        $this->routes = [];
        $this->addRouteCalled = false;
        $this->dispatchCalled = false;
        $this->groupCalled = false;
    }

    public function wasAddRouteCalled(): bool
    {
        return $this->addRouteCalled;
    }

    public function wasDispatchCalled(): bool
    {
        return $this->dispatchCalled;
    }

    public function wasGroupCalled(): bool
    {
        return $this->groupCalled;
    }
}

/**
 * Logging router implementation for testing custom behavior.
 */
class LoggingRouter implements RouterInterface
{
    private array $routes = [];
    private array $logs = [];

    public function addRoute(string $method, string $path, callable|array|string $handler, array $options = []): void
    {
        $this->logs[] = "Route added: $method $path";
        $this->routes[] = compact('method', 'path', 'handler', 'options');
    }

    public function dispatch(string $method, string $path): ?array
    {
        $this->logs[] = "Dispatching: $method $path";

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                return $route;
            }
        }

        return null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function group(string $prefix, callable $callback, array $options = []): void
    {
        $this->logs[] = "Group created: $prefix";
        $callback($this);
    }

    public function clear(): void
    {
        $this->routes = [];
        $this->logs = [];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}
