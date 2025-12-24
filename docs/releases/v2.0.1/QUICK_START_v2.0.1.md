# PivotPHP v2.0.1 - Quick Start Guide

## 🚀 Installation

```bash
composer require pivotphp/core:^2.0.1
```

## ✨ New in v2.0.1

### 1. Custom Router Injection

```php
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Routing\Contracts\RouterInterface;

// Create your custom router
class MyRouter implements RouterInterface {
    // ... implement interface methods
}

// Inject it into the application
$app = new Application(null, ['router' => new MyRouter()]);
```

### 2. Route Groups

```php
// Group routes with shared prefix
$app->group('/api/v1', function($app) {
    $app->get('/users', [UserController::class, 'index']);
    $app->post('/users', [UserController::class, 'create']);
    $app->get('/users/:id', [UserController::class, 'show']);
});

// Nested groups
$app->group('/admin', function($app) {
    $app->group('/users', function($app) {
        $app->get('/', [AdminUserController::class, 'index']);
        $app->post('/', [AdminUserController::class, 'create']);
    });
}, ['middleware' => ['auth', 'admin']]);
```

### 3. Route Options

```php
// Pass options directly to route methods
$app->get('/protected', $handler, [
    'middleware' => ['auth', 'verified']
]);

$app->post('/api/users', $handler, [
    'middleware' => ['api', 'throttle:60,1'],
    'name' => 'users.create'
]);
```

## 📖 Basic Usage

### Default Router (FastRouteAdapter)

```php
<?php

require 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// Simple routes
$app->get('/', fn() => 'Hello World!');
$app->get('/about', fn() => 'About Us');

// Routes with parameters
$app->get('/users/:id', function($req, $res) {
    $id = $req->getParam('id');
    return $res->json(['user_id' => $id]);
});

// Multiple parameters
$app->get('/posts/:year/:month/:slug', function($req, $res) {
    return $res->json([
        'year' => $req->getParam('year'),
        'month' => $req->getParam('month'),
        'slug' => $req->getParam('slug')
    ]);
});

// HTTP methods
$app->post('/users', fn() => 'Create user');
$app->put('/users/:id', fn() => 'Update user');
$app->delete('/users/:id', fn() => 'Delete user');
$app->patch('/users/:id', fn() => 'Patch user');

$app->run();
```

### With Route Groups

```php
<?php

require 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// Public routes
$app->get('/', fn() => 'Home');
$app->get('/about', fn() => 'About');

// API v1
$app->group('/api/v1', function($app) {
    // Users
    $app->get('/users', [UserController::class, 'index']);
    $app->post('/users', [UserController::class, 'create']);
    $app->get('/users/:id', [UserController::class, 'show']);
    $app->put('/users/:id', [UserController::class, 'update']);
    $app->delete('/users/:id', [UserController::class, 'delete']);

    // Posts
    $app->get('/posts', [PostController::class, 'index']);
    $app->post('/posts', [PostController::class, 'create']);
}, ['middleware' => ['api']]);

// Admin routes
$app->group('/admin', function($app) {
    $app->get('/dashboard', [AdminController::class, 'dashboard']);
    $app->get('/settings', [AdminController::class, 'settings']);
}, ['middleware' => ['auth', 'admin']]);

$app->run();
```

### With Custom Router

```php
<?php

require 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Routing\Contracts\RouterInterface;

// Simple custom router with logging
class LoggingRouter implements RouterInterface
{
    private array $routes = [];
    private array $logs = [];

    public function addRoute(string $method, string $path, $handler, array $options = []): void
    {
        $this->logs[] = "Added route: $method $path";
        $this->routes[] = compact('method', 'path', 'handler', 'options');
    }

    public function dispatch(string $method, string $path): ?array
    {
        $this->logs[] = "Dispatching: $method $path";

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                return $route + ['params' => []];
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

// Use custom router
$customRouter = new LoggingRouter();
$app = new Application(null, ['router' => $customRouter]);

$app->get('/', fn() => 'Home');
$app->get('/about', fn() => 'About');

// Check logs
var_dump($customRouter->getLogs());

$app->run();
```

## 🎯 Real-World Example

### REST API with Groups and Middleware

```php
<?php

require 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use App\Controllers\{UserController, PostController, CommentController};
use App\Middleware\{AuthMiddleware, CorsMiddleware, RateLimitMiddleware};

$app = new Application(__DIR__);

// Global middleware
$app->addMiddleware(CorsMiddleware::class);

// Public routes
$app->get('/', fn() => 'API v1.0');
$app->get('/health', fn($req, $res) => $res->json(['status' => 'ok']));

// API v1
$app->group('/api/v1', function($app) {

    // Public endpoints
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->post('/auth/register', [AuthController::class, 'register']);

    // Protected endpoints
    $app->group('/users', function($app) {
        $app->get('/', [UserController::class, 'index']);
        $app->get('/:id', [UserController::class, 'show']);
        $app->put('/:id', [UserController::class, 'update']);
        $app->delete('/:id', [UserController::class, 'delete']);
    }, ['middleware' => ['auth']]);

    // Posts
    $app->group('/posts', function($app) {
        $app->get('/', [PostController::class, 'index']);
        $app->get('/:id', [PostController::class, 'show']);
        $app->post('/', [PostController::class, 'create'], ['middleware' => ['auth']]);
        $app->put('/:id', [PostController::class, 'update'], ['middleware' => ['auth']]);
        $app->delete('/:id', [PostController::class, 'delete'], ['middleware' => ['auth']]);

        // Comments on posts
        $app->group('/:postId/comments', function($app) {
            $app->get('/', [CommentController::class, 'index']);
            $app->post('/', [CommentController::class, 'create'], ['middleware' => ['auth']]);
        });
    });

}, ['middleware' => [RateLimitMiddleware::class]]);

// Admin routes
$app->group('/admin', function($app) {
    $app->get('/dashboard', [AdminController::class, 'dashboard']);
    $app->get('/users', [AdminController::class, 'users']);
    $app->get('/statistics', [AdminController::class, 'statistics']);
}, ['middleware' => ['auth', 'admin']]);

$app->run();
```

## 💡 Tips & Tricks

### 1. Route Organization

```php
// Separate route files
// routes/api.php
$app->group('/api/v1', function($app) {
    require __DIR__ . '/api/users.php';
    require __DIR__ . '/api/posts.php';
    require __DIR__ . '/api/comments.php';
}, ['middleware' => ['api']]);

// routes/api/users.php
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [UserController::class, 'create']);
```

### 2. Named Routes (via options)

```php
$app->get('/users/:id', [UserController::class, 'show'], [
    'name' => 'users.show'
]);
```

### 3. Middleware Stacking

```php
$app->get('/admin/users', $handler, [
    'middleware' => ['auth', 'verified', 'admin', 'log']
]);
```

### 4. Testing Custom Routers

```php
use PHPUnit\Framework\TestCase;

class MyRouterTest extends TestCase
{
    public function testRouterWorks()
    {
        $router = new MyRouter();
        $router->addRoute('GET', '/test', fn() => 'response');

        $result = $router->dispatch('GET', '/test');

        $this->assertNotNull($result);
        $this->assertEquals('GET', $result['method']);
    }
}
```

## 🔧 Migration from v2.0.0

No changes required! Just update:

```bash
composer update pivotphp/core
```

All existing code works as-is. New features are optional enhancements.

## 📚 Learn More

- [Complete Release Notes](./RELEASE_NOTES_v2.0.1.md)
- [API Documentation](./api/)
- [Examples](./examples/)
- [GitHub](https://github.com/HelixPHP/helixphp-core)

## 🆘 Need Help?

- Open an issue on [GitHub](https://github.com/HelixPHP/helixphp-core/issues)
- Check the [documentation](./RELEASE_NOTES_v2.0.1.md)
- Review [examples](./examples/)

---

**Happy Coding! 🎉**
