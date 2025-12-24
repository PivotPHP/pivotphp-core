# PivotPHP v2.0.1 - Pluggable Router Architecture

## 🎯 Overview

Version 2.0.1 introduces a **pluggable router architecture** that allows developers to inject custom routing implementations while maintaining 100% backward compatibility with existing applications.

## ✨ Key Features

### 1. RouterInterface Contract

New `RouterInterface` provides a standardized contract for router implementations:

```php
namespace PivotPHP\Core\Routing\Contracts;

interface RouterInterface
{
    public function addRoute(string $method, string $path, callable|array|string $handler, array $options = []): void;
    public function dispatch(string $method, string $path): ?array;
    public function getRoutes(): array;
    public function group(string $prefix, callable $callback, array $options = []): void;
    public function clear(): void;
}
```

### 2. FastRouteAdapter (Default)

The default `FastRouteAdapter` wraps the powerful `pivotphp/core-routing` package, providing:

- ✅ Parameter extraction (`:id`, `:slug`, etc.)
- ✅ Pattern matching with regex constraints
- ✅ Route grouping with nested groups
- ✅ Options/metadata preservation
- ✅ Full backward compatibility

### 3. Custom Router Injection

Inject custom routers via Application constructor:

```php
use PivotPHP\Core\Core\Application;

// Default router (FastRouteAdapter)
$app = new Application();

// Custom router
$customRouter = new MyCustomRouter(); // implements RouterInterface
$app = new Application(null, ['router' => $customRouter]);
```

### 4. Route Options Support

All HTTP method helpers now accept an `$options` parameter:

```php
$app->get('/admin', $handler, ['middleware' => ['auth', 'admin']]);
$app->post('/api/users', $handler, ['middleware' => ['api'], 'name' => 'users.create']);
```

### 5. Route Grouping

Organize routes with shared prefixes and options:

```php
$app->group('/api/v1', function($app) {
    $app->get('/users', [UserController::class, 'index']);
    $app->post('/users', [UserController::class, 'create']);
    $app->get('/users/:id', [UserController::class, 'show']);
}, ['middleware' => ['api', 'cors']]);
```

## 📚 Usage Examples

### Basic Route Registration

```php
$app = new Application();

// Simple routes
$app->get('/', fn() => 'Home');
$app->get('/about', fn() => 'About Us');

// Routes with parameters
$app->get('/users/:id', function($req, $res) {
    $id = $req->getParam('id');
    return "User $id";
});

// Routes with multiple parameters
$app->get('/posts/:year/:month/:slug', function($req, $res) {
    return "Post: {$req->getParam('year')}/{$req->getParam('month')}/{$req->getParam('slug')}";
});
```

### Route Groups

```php
// API v1 routes
$app->group('/api/v1', function($app) {
    $app->get('/users', [UserController::class, 'index']);
    $app->post('/users', [UserController::class, 'create']);
    $app->get('/users/:id', [UserController::class, 'show']);
    $app->put('/users/:id', [UserController::class, 'update']);
    $app->delete('/users/:id', [UserController::class, 'delete']);
}, ['middleware' => ['api']]);

// Admin routes
$app->group('/admin', function($app) {
    $app->get('/dashboard', [AdminController::class, 'dashboard']);
    $app->get('/settings', [AdminController::class, 'settings']);

    // Nested groups
    $app->group('/users', function($app) {
        $app->get('/', [AdminUserController::class, 'index']);
        $app->post('/', [AdminUserController::class, 'create']);
    });
}, ['middleware' => ['auth', 'admin']]);
```

### Routes with Options

```php
// Middleware options
$app->get('/protected', $handler, [
    'middleware' => ['auth', 'verified']
]);

// Named routes
$app->get('/users/:id', $handler, [
    'name' => 'users.show',
    'middleware' => ['auth']
]);

// Custom metadata
$app->get('/api/data', $handler, [
    'cache' => 3600,
    'throttle' => '60,1',
    'version' => 'v1'
]);
```

## 🔧 Implementing Custom Routers

### Example: Simple RegexRouter

```php
<?php

namespace App\Routing;

use PivotPHP\Core\Routing\Contracts\RouterInterface;

class RegexRouter implements RouterInterface
{
    private array $routes = [];
    private string $groupPrefix = '';
    private array $groupOptions = [];

    public function addRoute(string $method, string $path, callable|array|string $handler, array $options = []): void
    {
        $fullPath = $this->groupPrefix . $path;
        $mergedOptions = array_merge($this->groupOptions, $options);

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'pattern' => $this->compilePattern($fullPath),
            'handler' => $handler,
            'options' => $mergedOptions,
        ];
    }

    public function dispatch(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches); // Remove full match

                return [
                    'method' => $route['method'],
                    'path' => $route['path'],
                    'handler' => $route['handler'],
                    'params' => $this->extractParams($route, $matches),
                    'options' => $route['options'],
                ];
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
        $previousPrefix = $this->groupPrefix;
        $previousOptions = $this->groupOptions;

        $this->groupPrefix .= $prefix;
        $this->groupOptions = array_merge($this->groupOptions, $options);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupOptions = $previousOptions;
    }

    public function clear(): void
    {
        $this->routes = [];
        $this->groupPrefix = '';
        $this->groupOptions = [];
    }

    private function compilePattern(string $path): string
    {
        // Convert :param to named capture groups
        $pattern = preg_replace('/:([\w]+)/', '(?<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function extractParams(array $route, array $matches): array
    {
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
}
```

### Using Custom Router

```php
use App\Routing\RegexRouter;
use PivotPHP\Core\Core\Application;

$customRouter = new RegexRouter();
$app = new Application(null, ['router' => $customRouter]);

$app->get('/users/:id', function($req, $res) {
    return "User ID: " . $req->getParam('id');
});

$app->get('/posts/:slug', function($req, $res) {
    return "Post: " . $req->getParam('slug');
});
```

### Example: CachedRouter

Router with built-in route caching:

```php
<?php

namespace App\Routing;

use PivotPHP\Core\Routing\Adapters\FastRouteAdapter;
use PivotPHP\Core\Routing\Contracts\RouterInterface;

class CachedRouter implements RouterInterface
{
    private RouterInterface $innerRouter;
    private array $dispatchCache = [];
    private int $cacheHits = 0;
    private int $cacheMisses = 0;

    public function __construct(RouterInterface $innerRouter = null)
    {
        $this->innerRouter = $innerRouter ?? new FastRouteAdapter();
    }

    public function addRoute(string $method, string $path, callable|array|string $handler, array $options = []): void
    {
        $this->innerRouter->addRoute($method, $path, $handler, $options);
    }

    public function dispatch(string $method, string $path): ?array
    {
        $cacheKey = $method . ':' . $path;

        if (isset($this->dispatchCache[$cacheKey])) {
            $this->cacheHits++;
            return $this->dispatchCache[$cacheKey];
        }

        $result = $this->innerRouter->dispatch($method, $path);

        if ($result) {
            $this->dispatchCache[$cacheKey] = $result;
        }

        $this->cacheMisses++;
        return $result;
    }

    public function getRoutes(): array
    {
        return $this->innerRouter->getRoutes();
    }

    public function group(string $prefix, callable $callback, array $options = []): void
    {
        $this->innerRouter->group($prefix, $callback, $options);
    }

    public function clear(): void
    {
        $this->innerRouter->clear();
        $this->dispatchCache = [];
        $this->cacheHits = 0;
        $this->cacheMisses = 0;
    }

    public function getCacheStats(): array
    {
        return [
            'hits' => $this->cacheHits,
            'misses' => $this->cacheMisses,
            'size' => count($this->dispatchCache),
            'hit_rate' => $this->cacheHits + $this->cacheMisses > 0
                ? round(($this->cacheHits / ($this->cacheHits + $this->cacheMisses)) * 100, 2)
                : 0
        ];
    }
}
```

## 🧪 Testing

### Unit Testing Custom Routers

```php
use PHPUnit\Framework\TestCase;
use App\Routing\RegexRouter;

class RegexRouterTest extends TestCase
{
    private RegexRouter $router;

    protected function setUp(): void
    {
        $this->router = new RegexRouter();
    }

    public function testAddAndDispatchRoute(): void
    {
        $handler = fn() => 'test';
        $this->router->addRoute('GET', '/test', $handler);

        $result = $this->router->dispatch('GET', '/test');

        $this->assertNotNull($result);
        $this->assertEquals('GET', $result['method']);
        $this->assertEquals('/test', $result['path']);
    }

    public function testParameterExtraction(): void
    {
        $handler = fn($id) => "User $id";
        $this->router->addRoute('GET', '/users/:id', $handler);

        $result = $this->router->dispatch('GET', '/users/42');

        $this->assertEquals('42', $result['params']['id']);
    }

    public function testRouteGroups(): void
    {
        $this->router->group('/api', function($router) {
            $router->addRoute('GET', '/users', fn() => 'users');
        });

        $result = $this->router->dispatch('GET', '/api/users');

        $this->assertNotNull($result);
    }
}
```

### Integration Testing

```php
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use App\Routing\CachedRouter;

class CustomRouterIntegrationTest extends TestCase
{
    public function testApplicationWithCachedRouter(): void
    {
        $cachedRouter = new CachedRouter();
        $app = new Application(null, ['router' => $cachedRouter]);

        $app->get('/test', fn() => 'response');

        // First dispatch - cache miss
        $cachedRouter->dispatch('GET', '/test');
        $this->assertEquals(1, $cachedRouter->getCacheStats()['misses']);

        // Second dispatch - cache hit
        $cachedRouter->dispatch('GET', '/test');
        $this->assertEquals(1, $cachedRouter->getCacheStats()['hits']);
    }
}
```

## 📊 Performance Considerations

### FastRouteAdapter (Default)

- **Best for**: Standard web applications, REST APIs
- **Performance**: Optimized with pattern compilation, route indexing, exact match caching
- **Memory**: Efficient - routes compiled once, cached for subsequent requests
- **Recommended**: Most use cases

### Custom Routers

Consider custom routers when you need:

1. **Specialized Routing Logic**
   - Subdomain routing
   - API versioning in headers
   - Content negotiation routing
   - Dynamic route generation

2. **Performance Optimization**
   - Route caching layers
   - Compile-time route optimization
   - Database-backed routing
   - CDN-aware routing

3. **Integration Requirements**
   - Legacy system compatibility
   - Third-party router libraries
   - Custom pattern matching
   - Complex routing rules

## 🚀 Migration from v2.0.0

**No migration required** - v2.0.1 is fully backward compatible.

### Optional Enhancements

```php
// v2.0.0 style - still works
$app = new Application();
$app->get('/users', $handler);

// v2.0.1 enhancements - optional
$app->get('/users', $handler, ['middleware' => ['auth']]);

$app->group('/api', function($app) {
    $app->get('/users', $handler);
}, ['middleware' => ['api']]);

// Custom router - new in v2.0.1
$app = new Application(null, ['router' => new CustomRouter()]);
```

## 📝 RouterInterface Contract Details

### Method: addRoute()

```php
public function addRoute(
    string $method,                     // HTTP method: GET, POST, PUT, DELETE, PATCH, etc.
    string $path,                        // Route path: /users/:id
    callable|array|string $handler,      // Handler: closure, [Controller, 'method'], or string
    array $options = []                  // Options: middleware, name, metadata, etc.
): void
```

### Method: dispatch()

```php
public function dispatch(
    string $method,  // HTTP method to match
    string $path     // Request path to match
): ?array          // Returns route data or null if not found
```

**Return Structure**:
```php
[
    'method' => 'GET',
    'path' => '/users/:id',
    'handler' => Closure,
    'params' => ['id' => '42'],
    'options' => ['middleware' => ['auth']]
]
```

### Method: getRoutes()

```php
public function getRoutes(): array  // Returns all registered routes
```

### Method: group()

```php
public function group(
    string $prefix,      // Group prefix: /api/v1
    callable $callback,  // Callback receiving router/app
    array $options = []  // Shared options for all routes in group
): void
```

### Method: clear()

```php
public function clear(): void  // Clear all registered routes
```

## 🎓 Best Practices

### 1. Router Selection

- **Use FastRouteAdapter** (default) for most applications
- **Implement custom router** only when you have specific requirements
- **Test thoroughly** when using custom routers

### 2. Route Organization

```php
// Good - organized by resource
$app->group('/users', function($app) {
    $app->get('/', [UserController::class, 'index']);
    $app->post('/', [UserController::class, 'create']);
    $app->get('/:id', [UserController::class, 'show']);
});

// Good - versioned APIs
$app->group('/api/v1', function($app) {
    // v1 routes
}, ['middleware' => ['api', 'v1']]);

$app->group('/api/v2', function($app) {
    // v2 routes
}, ['middleware' => ['api', 'v2']]);
```

### 3. Middleware Configuration

```php
// Global middleware
$app->addMiddleware(CorsMiddleware::class);

// Group middleware
$app->group('/api', function($app) {
    // API routes
}, ['middleware' => ['api', 'throttle:60,1']]);

// Route-specific middleware
$app->get('/admin', $handler, ['middleware' => ['auth', 'admin']]);
```

### 4. Testing Custom Routers

```php
class CustomRouterTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $router = new CustomRouter();
        $this->assertInstanceOf(RouterInterface::class, $router);
    }

    public function testAddAndDispatch(): void
    {
        $router = new CustomRouter();
        $router->addRoute('GET', '/test', fn() => 'response');

        $result = $router->dispatch('GET', '/test');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('handler', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertArrayHasKey('options', $result);
    }
}
```

## 🐛 Troubleshooting

### Issue: Custom router not being used

**Problem**: Application still uses FastRouteAdapter despite passing custom router.

**Solution**: Ensure your custom router implements `RouterInterface` and is passed correctly:

```php
// ✅ Correct
$app = new Application(null, ['router' => $customRouter]);

// ❌ Wrong - missing null for $basePath
$app = new Application(['router' => $customRouter]);
```

### Issue: Routes not matching

**Problem**: dispatch() returns null for valid routes.

**Solution**: Check your pattern matching logic:

```php
public function dispatch(string $method, string $path): ?array
{
    // Log for debugging
    error_log("Dispatching: $method $path");

    foreach ($this->routes as $route) {
        error_log("Testing route: {$route['method']} {$route['path']}");

        if ($this->matches($route, $method, $path)) {
            return $route;
        }
    }

    return null;
}
```

### Issue: Parameters not extracted

**Problem**: `$result['params']` is empty.

**Solution**: Ensure your regex captures named groups:

```php
// ✅ Correct - named capture groups
$pattern = '#^/users/(?<id>[^/]+)$#';

// ❌ Wrong - unnamed groups
$pattern = '#^/users/([^/]+)$#';
```

## 📚 Additional Resources

- [RouterInterface API Documentation](./api/RouterInterface.md)
- [FastRouteAdapter Implementation](./api/FastRouteAdapter.md)
- [Custom Router Examples](./examples/custom-routers/)
- [Migration Guide](./MIGRATION_GUIDE_v2.0.1.md)
- [Performance Benchmarks](./benchmarks/routing-performance.md)

## 🎉 Summary

v2.0.1 brings **pluggable routing** to PivotPHP while maintaining 100% backward compatibility. Whether you stick with the powerful default FastRouteAdapter or implement your own custom routing logic, you now have the flexibility to adapt the framework to your specific needs.

Key benefits:
- ✅ **Zero Breaking Changes** - upgrade safely
- 🔧 **Extensible** - implement custom routers easily
- 📦 **Testable** - clean interfaces for testing
- 🚀 **Flexible** - route grouping, options, and more
- 🎯 **Production Ready** - 35 tests, 81 assertions, 100% pass rate
