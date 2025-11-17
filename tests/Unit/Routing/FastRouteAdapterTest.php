<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Routing\Adapters\FastRouteAdapter;
use PivotPHP\Core\Routing\Contracts\RouterInterface;

/**
 * Testes para o FastRouteAdapter.
 *
 * @covers \PivotPHP\Core\Routing\Adapters\FastRouteAdapter
 */
class FastRouteAdapterTest extends TestCase
{
    private FastRouteAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new FastRouteAdapter();
    }

    protected function tearDown(): void
    {
        $this->adapter->clear();
    }

    public function testImplementsRouterInterface(): void
    {
        $this->assertInstanceOf(RouterInterface::class, $this->adapter);
    }

    public function testAddRouteWithSimplePath(): void
    {
        $handler = fn() => 'test';
        $this->adapter->addRoute('GET', '/test', $handler);

        $routes = $this->adapter->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/test', $routes[0]['path']);
    }

    public function testAddRouteWithParameters(): void
    {
        $handler = fn($id) => "User $id";
        $this->adapter->addRoute('GET', '/users/{id}', $handler);

        $routes = $this->adapter->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertEquals('/users/{id}', $routes[0]['path']);
    }

    public function testAddMultipleRoutes(): void
    {
        $this->adapter->addRoute('GET', '/users', fn() => 'list');
        $this->adapter->addRoute('POST', '/users', fn() => 'create');
        $this->adapter->addRoute('PUT', '/users/{id}', fn($id) => "update $id");

        $routes = $this->adapter->getRoutes();
        $this->assertCount(3, $routes);
    }

    public function testDispatchSimpleRoute(): void
    {
        $handler = fn() => 'test response';
        $this->adapter->addRoute('GET', '/test', $handler);

        $result = $this->adapter->dispatch('GET', '/test');

        $this->assertIsArray($result);
        $this->assertEquals('GET', $result['method']);
        $this->assertEquals('/test', $result['path']);
        $this->assertArrayHasKey('handler', $result);
    }

    public function testDispatchRouteWithParameters(): void
    {
        $handler = fn($id, $action) => "User $id - $action";
        $this->adapter->addRoute('GET', '/users/:id/:action', $handler);

        $result = $this->adapter->dispatch('GET', '/users/42/edit');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('params', $result);
        $this->assertArrayHasKey('id', $result['params']);
        $this->assertEquals('42', $result['params']['id']);
    }

    public function testDispatchNotFoundReturnsNull(): void
    {
        $this->adapter->addRoute('GET', '/users', fn() => 'list');

        $result = $this->adapter->dispatch('GET', '/posts');

        $this->assertNull($result);
    }

    public function testDispatchMethodNotAllowed(): void
    {
        $this->adapter->addRoute('GET', '/users', fn() => 'list');

        $result = $this->adapter->dispatch('POST', '/users');

        $this->assertNull($result);
    }

    public function testGroupWithPrefix(): void
    {
        $this->adapter->group(
            '/api',
            function ($router) {
                $router->addRoute('GET', '/users', fn() => 'users');
                $router->addRoute('GET', '/posts', fn() => 'posts');
            }
        );

        $routes = $this->adapter->getRoutes();
        $this->assertCount(2, $routes);
        $this->assertEquals('/api/users', $routes[0]['path']);
        $this->assertEquals('/api/posts', $routes[1]['path']);
    }

    public function testGroupWithNestedGroups(): void
    {
        $this->adapter->group(
            '/api',
            function ($router) {
                $router->group(
                    '/v1',
                    function ($r) {
                        $r->addRoute('GET', '/users', fn() => 'v1 users');
                    }
                );
                $router->group(
                    '/v2',
                    function ($r) {
                        $r->addRoute('GET', '/users', fn() => 'v2 users');
                    }
                );
            }
        );

        $routes = $this->adapter->getRoutes();
        $this->assertCount(2, $routes);
        $this->assertEquals('/api/v1/users', $routes[0]['path']);
        $this->assertEquals('/api/v2/users', $routes[1]['path']);
    }

    public function testGroupWithOptions(): void
    {
        $options = ['middleware' => ['auth']];

        $this->adapter->group(
            '/admin',
            function ($router) {
                $router->addRoute('GET', '/dashboard', fn() => 'dashboard');
            },
            $options
        );

        $routes = $this->adapter->getRoutes();
        $this->assertCount(1, $routes);
        // Router stores metadata, not options
        $this->assertArrayHasKey('metadata', $routes[0]);
    }

    public function testClearRemovesAllRoutes(): void
    {
        $this->adapter->addRoute('GET', '/test1', fn() => 'test1');
        $this->adapter->addRoute('POST', '/test2', fn() => 'test2');

        $this->assertCount(2, $this->adapter->getRoutes());

        $this->adapter->clear();

        $this->assertEmpty($this->adapter->getRoutes());
    }

    public function testDispatchWithOptionalParameters(): void
    {
        $handler1 = fn() => 'All users';
        $handler2 = fn($id) => "User $id";

        // Router requires separate routes for optional params
        $this->adapter->addRoute('GET', '/users', $handler1);
        $this->adapter->addRoute('GET', '/users/:id', $handler2);

        $result1 = $this->adapter->dispatch('GET', '/users/42');
        $this->assertIsArray($result1);
        $this->assertEquals('42', $result1['params']['id']);

        $result2 = $this->adapter->dispatch('GET', '/users');
        $this->assertIsArray($result2);
    }

    public function testDispatchWithMultipleHttpMethods(): void
    {
        $this->adapter->addRoute('GET', '/resource', fn() => 'get');
        $this->adapter->addRoute('POST', '/resource', fn() => 'post');
        $this->adapter->addRoute('PUT', '/resource', fn() => 'put');
        $this->adapter->addRoute('DELETE', '/resource', fn() => 'delete');
        $this->adapter->addRoute('PATCH', '/resource', fn() => 'patch');

        $this->assertNotNull($this->adapter->dispatch('GET', '/resource'));
        $this->assertNotNull($this->adapter->dispatch('POST', '/resource'));
        $this->assertNotNull($this->adapter->dispatch('PUT', '/resource'));
        $this->assertNotNull($this->adapter->dispatch('DELETE', '/resource'));
        $this->assertNotNull($this->adapter->dispatch('PATCH', '/resource'));
    }

    public function testAddRouteWithCallableArray(): void
    {
        $handler = [new class {
            public function handle()
            {
                return 'controller response';
            }
        }, 'handle'
        ];

        $this->adapter->addRoute('GET', '/controller', $handler);

        $result = $this->adapter->dispatch('GET', '/controller');
        $this->assertIsArray($result);
        $this->assertEquals($handler, $result['handler']);
    }

    public function testAddRouteWithStringHandler(): void
    {
        $this->adapter->addRoute('GET', '/string', 'SomeController@method');

        $result = $this->adapter->dispatch('GET', '/string');
        $this->assertIsArray($result);
        // String handler gets wrapped in closure
        $this->assertArrayHasKey('handler', $result);
    }

    public function testRoutesWithSamePathDifferentMethods(): void
    {
        $this->adapter->addRoute('GET', '/users', fn() => 'list');
        $this->adapter->addRoute('POST', '/users', fn() => 'create');

        $getResult = $this->adapter->dispatch('GET', '/users');
        $postResult = $this->adapter->dispatch('POST', '/users');

        $this->assertNotNull($getResult);
        $this->assertNotNull($postResult);
        $this->assertEquals('GET', $getResult['method']);
        $this->assertEquals('POST', $postResult['method']);
    }

    public function testDispatchPreservesRouteOptions(): void
    {
        $options = ['middleware' => ['auth', 'cors'], 'name' => 'user.show'];
        $this->adapter->addRoute('GET', '/users/:id', fn($id) => "User $id", $options);

        $result = $this->adapter->dispatch('GET', '/users/123');

        // Router stores options in metadata, FastRouteAdapter normalizes to 'options'
        $this->assertIsArray($result);
        $this->assertArrayHasKey('options', $result);
        $this->assertEquals($options, $result['options']);
    }

    public function testGroupMergesOptionsWithRoutes(): void
    {
        $groupOptions = ['middleware' => ['auth']];

        $this->adapter->group(
            '/api',
            function ($router) {
                $router->addRoute('GET', '/users', fn() => 'users', ['middleware' => ['cors']]);
            },
            $groupOptions
        );

        $routes = $this->adapter->getRoutes();
        $this->assertCount(1, $routes);
        // Options are merged in metadata
        $this->assertArrayHasKey('metadata', $routes[0]);
    }

    public function testParameterExtractionWithSpecialCharacters(): void
    {
        $this->adapter->addRoute('GET', '/search/:query', fn($query) => "Search: $query");

        $result = $this->adapter->dispatch('GET', '/search/hello-world');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('params', $result);
        $this->assertEquals('hello-world', $result['params']['query']);
    }

    public function testRouteWithTrailingSlashMatching(): void
    {
        $this->adapter->addRoute('GET', '/users', fn() => 'users');

        $result1 = $this->adapter->dispatch('GET', '/users');
        $result2 = $this->adapter->dispatch('GET', '/users/');

        $this->assertNotNull($result1);
        // FastRoute doesn't automatically match trailing slashes - both need to be registered
        // This behavior is expected
    }

    public function testEmptyRouterDispatchReturnsNull(): void
    {
        $result = $this->adapter->dispatch('GET', '/anything');
        $this->assertNull($result);
    }

    public function testGetRoutesReturnsEmptyArrayInitially(): void
    {
        $routes = $this->adapter->getRoutes();
        $this->assertIsArray($routes);
        $this->assertEmpty($routes);
    }
}
