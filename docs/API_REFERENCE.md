# PivotPHP Core - API Reference

**Version:** 1.2.0
**Last Updated:** July 2025

> ⚠️ **Nota**: Este projeto é mantido por apenas uma pessoa e pode não receber atualizações constantemente. Ideal para provas de conceito, protótipos e estudos, mas não recomendado para aplicações críticas de produção.

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

$app->get('/', function ($req, $res) {
    return $res->json(['message' => 'Hello, World!']);
});

$app->run();
```

## Application Class

### Constructor

```php
new Application(?string $basePath = null, ?string $configPath = null)
```

**Parameters:**
- `$basePath` - Base directory path (default: auto-detected)
- `$configPath` - Configuration directory path (default: `$basePath/config`)

### HTTP Methods

#### GET Routes
```php
$app->get(string $path, callable $handler): self
```

#### POST Routes
```php
$app->post(string $path, callable $handler): self
```

#### PUT Routes
```php
$app->put(string $path, callable $handler): self
```

#### DELETE Routes
```php
$app->delete(string $path, callable $handler): self
```

#### PATCH Routes
```php
$app->patch(string $path, callable $handler): self
```

#### OPTIONS Routes
```php
$app->options(string $path, callable $handler): self
```

#### Multiple Methods
```php
$app->route(array $methods, string $path, callable $handler): self
```

### Route Parameters

#### Basic Parameters
```php
$app->get('/users/:id', function ($req, $res) {
    $id = $req->param('id');
    return $res->json(['user_id' => $id]);
});
```

#### Regex Constraints
```php
// Numeric ID only
$app->get('/users/:id<\\d+>', $handler);

// Slug pattern
$app->get('/posts/:slug<[a-z0-9-]+>', $handler);

// Date format
$app->get('/archive/:date<\\d{4}-\\d{2}-\\d{2}>', $handler);
```

#### Predefined Shortcuts
```php
$app->get('/categories/:slug<slug>', $handler);   // [a-zA-Z0-9-_]+
$app->get('/objects/:id<uuid>', $handler);        // UUID format
$app->get('/posts/:date<date>', $handler);        // YYYY-MM-DD
$app->get('/names/:name<alpha>', $handler);       // [a-zA-Z]+
$app->get('/codes/:code<alnum>', $handler);       // [a-zA-Z0-9]+
```

### Middleware

#### Global Middleware
```php
$app->use(callable $middleware): self
```

#### Route-Specific Middleware
```php
$app->get('/protected', $authMiddleware, function ($req, $res) {
    return $res->json(['protected' => 'data']);
});
```

#### Multiple Middleware
```php
$app->post('/api/data',
    $corsMiddleware,
    $authMiddleware,
    $validationMiddleware,
    function ($req, $res) {
        // Handler logic
    }
);
```

### Application Lifecycle

#### Manual Boot
```php
$app->boot(): self
```

#### Run Application
```php
$app->run(): void
```

**Note:** `boot()` is called automatically by `run()` if not called explicitly.

## Request Object

### Basic Properties
```php
$req->method(): string           // HTTP method
$req->uri(): string             // Request URI
$req->ip(): string              // Client IP
$req->userAgent(): ?string      // User agent
```

### Parameters
```php
$req->param(string $key): ?string                    // Route parameter
$req->params(): array                                // All route parameters
$req->get(string $key, mixed $default = null): mixed // Query parameter
$req->query(): array                                 // All query parameters
```

### Headers
```php
$req->header(string $name): ?string  // Single header
$req->headers(): array               // All headers
```

### Body Data
```php
$req->body(): string                     // Raw body
$req->getBodyAsStdClass(): \stdClass     // JSON as object
$req->input(string $key, mixed $default = null): mixed // JSON property
```

### Cookies
```php
$req->cookie(string $name): ?string  // Single cookie
$req->cookies(): array               // All cookies
```

### Files
```php
$req->file(string $name): ?array     // Single uploaded file
$req->files(): array                 // All uploaded files
```

### Express.js Compatibility
```php
$req->param('id')           // Route parameter
$req->query()               // Query parameters
$req->get('param')          // Query parameter
$req->header('Accept')      // Request header
$req->ip()                  // Client IP
```

## Response Object

### Basic Response
```php
$res->send(string $content): self               // Send plain text
$res->html(string $html): self                  // Send HTML
$res->json(mixed $data, int $flags = 0): self  // Send JSON
$res->status(int $code): self                   // Set status code
```

### Headers
```php
$res->header(string $name, string $value): self  // Set header
$res->headers(array $headers): self              // Set multiple headers
```

### Cookies
```php
$res->cookie(string $name, string $value, array $options = []): self
```

**Cookie Options:**
- `expires` - Expiration timestamp
- `path` - Cookie path
- `domain` - Cookie domain
- `secure` - HTTPS only
- `httponly` - HTTP only access
- `samesite` - SameSite policy

### Redirects
```php
$res->redirect(string $url, int $status = 302): self
```

### File Downloads
```php
$res->download(string $path, ?string $name = null): self
$res->attachment(string $filename): self
```

### Express.js Compatibility
```php
$res->json($data)              // Send JSON response
$res->send($content)           // Send response
$res->status(404)              // Set status code
$res->header('Content-Type', 'application/json')
$res->cookie('session', 'value')
$res->redirect('/login')
```

## Route Handler Formats

### ✅ Supported Formats

#### Anonymous Functions (Recommended)
```php
$app->get('/users', function($req, $res) {
    return $res->json(['users' => []]);
});
```

#### Array Callable
```php
$app->get('/users', [UserController::class, 'index']);
```

#### Named Functions
```php
function getUsersHandler($req, $res) {
    return $res->json(['users' => []]);
}
$app->get('/users', 'getUsersHandler');
```

### ❌ NOT Supported

#### String Format (Does NOT work)
```php
// This will cause a TypeError!
$app->get('/users', 'UserController@index');
```

**Use this instead:**
```php
$app->get('/users', [UserController::class, 'index']);
```

## Performance Features

### Performance Mode (v1.2.0+)
```php
use PivotPHP\Core\Performance\PerformanceMode;

// Enable performance mode
PerformanceMode::enable(PerformanceMode::PROFILE_PRODUCTION);

// Check status
$status = PerformanceMode::getStatus();

// Disable
PerformanceMode::disable();
```

**Performance Profiles:**
- `PROFILE_DEVELOPMENT` - Development optimization
- `PROFILE_PRODUCTION` - Production optimization
- `PROFILE_TEST` - Test optimization

### JSON Optimization (v1.1.1+)
```php
use PivotPHP\Core\Json\Pool\JsonBufferPool;

// Manual JSON encoding with pooling
$json = JsonBufferPool::encodeWithPool($data);

// Configure pool
JsonBufferPool::configure([
    'max_pool_size' => 200,
    'default_capacity' => 8192
]);

// Get statistics
$stats = JsonBufferPool::getStatistics();
```

**Automatic Optimization:**
- Arrays with 10+ elements use pooling
- Objects with 5+ properties use pooling
- Strings >1KB use pooling
- Smaller data uses traditional `json_encode()`

## Middleware Development

### Basic Middleware Structure
```php
$middleware = function ($req, $res, $next) {
    // Pre-processing

    $response = $next($req, $res); // Continue to next middleware

    // Post-processing

    return $response;
};
```

### Early Response (Skip Chain)
```php
$authMiddleware = function ($req, $res, $next) {
    if (!$req->header('Authorization')) {
        return $res->status(401)->json(['error' => 'Unauthorized']);
    }

    return $next($req, $res);
};
```

### Modifying Request/Response
```php
$enrichMiddleware = function ($req, $res, $next) {
    // Add data to request
    $req->startTime = microtime(true);

    $response = $next($req, $res);

    // Add headers to response
    $duration = microtime(true) - $req->startTime;
    $res->header('X-Response-Time', $duration . 'ms');

    return $response;
};
```

## Error Handling

### Custom Error Handler
```php
$app->use(function ($req, $res, $next) {
    try {
        return $next($req, $res);
    } catch (Exception $e) {
        return $res->status(500)->json([
            'error' => 'Internal Server Error',
            'message' => $e->getMessage()
        ]);
    }
});
```

### HTTP Exceptions
```php
use PivotPHP\Core\Exceptions\HttpException;

throw new HttpException(404, 'Resource not found');
```

## Configuration

### Environment-based Config
```php
// config/app.php
return [
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
];

// Access in application
$debug = $app->config('app.debug');
```

### Custom Configuration
```php
$app->config('custom.setting', 'value');
$value = $app->config('custom.setting');
```

## Container & Dependency Injection

### Service Binding
```php
$app->bind('logger', function($container) {
    return new Logger();
});

// Singleton
$app->singleton('cache', function($container) {
    return new Cache();
});
```

### Service Resolution
```php
$logger = $app->make('logger');
$cache = $app->get('cache');
```

### Automatic Resolution
```php
class UserController {
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
}

// Automatically injects Logger
$app->get('/users', [UserController::class, 'index']);
```

## Version Information

```php
Application::VERSION  // Current version string
```

## PSR Compliance

- **PSR-7** - HTTP Message Interface (hybrid implementation)
- **PSR-11** - Container Interface
- **PSR-12** - Extended Coding Style Guide
- **PSR-14** - Event Dispatcher
- **PSR-15** - HTTP Server Request Handlers

## Examples

Complete working examples are available in the `/examples` directory:
- **01-basics** - Hello World, CRUD, Request/Response, JSON API
- **02-routing** - Regex, Parameters, Groups, Constraints
- **03-middleware** - Custom, Stack, Auth, CORS
- **04-api** - RESTful API with pagination and validation
- **05-performance** - High-performance mode demonstrations
- **06-security** - JWT authentication system

## Performance Benchmarks

**Latest Results (v1.1.3-dev):**
- JSON Optimization: 161K ops/sec (small), 17K ops/sec (medium), 1.7K ops/sec (large)
- Request Creation: 28,693 ops/sec
- Response Creation: 131,351 ops/sec
- Object Pooling: 24,161 ops/sec
- Route Processing: 31,699 ops/sec

## Migration Notes

### From v1.1.2 to v1.1.3
- All existing code continues to work
- New JSON pooling optimizations are automatic
- Enhanced error handling provides better validation messages
- All test constants are now properly defined

### Breaking Changes
- None - full backward compatibility maintained

## Community & Support

- **GitHub**: https://github.com/PivotPHP/pivotphp-core
- **Issues**: https://github.com/PivotPHP/pivotphp-core/issues
- **Examples**: Ready-to-run examples in `/examples` directory

---
**PivotPHP Core v1.1.3-dev** - Express.js for PHP 🐘⚡
