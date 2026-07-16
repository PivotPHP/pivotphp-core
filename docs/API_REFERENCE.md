# PivotPHP Core - API Reference

**Version:** 2.1.1

> ⚠️ **Nota de revisão:** este documento foi originalmente escrito para a v1.1.3/v1.2.0. A
> assinatura do construtor de `Application` e alguns exemplos foram corrigidos para refletir
> a v2.1.1 atual, mas números de performance e algumas seções ("Migration Notes") ainda
> refletem o estado da v1.1.3 e não foram revalidados — tratar como histórico onde indicado.

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
new Application(?string $basePath = null)
```

**Parameters:**
- `$basePath` - Base directory path (default: auto-detected). There is no `$configPath`
  parameter — configuration is not passed to the constructor (see "Configuration" below).

### HTTP Methods

Route handlers accept `callable|array` — either a closure/named function or an array
callable (`[Controller::class, 'method']`).

#### GET Routes
```php
$app->get(string $path, callable|array $handler): self
```

#### POST Routes
```php
$app->post(string $path, callable|array $handler): self
```

#### PUT Routes
```php
$app->put(string $path, callable|array $handler): self
```

#### DELETE Routes
```php
$app->delete(string $path, callable|array $handler): self
```

#### PATCH Routes
```php
$app->patch(string $path, callable|array $handler): self
```

> **Nota:** `Application` expõe apenas `get()`, `post()`, `put()`, `delete()` e `patch()`.
> Não existem métodos `options()`, `head()`, `any()` ou `route()` em `Application` — o Router
> subjacente (`pivotphp/core-routing`) suporta esses verbos internamente, mas eles não são
> expostos como métodos de conveniência em `Application` (ver `src/Core/Application.php`).

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
$app->use(mixed $middleware): self
```

`$app->middleware($middleware, array $options = [])` is an alias for `use()` — both register
**global** middleware (they call `Application::use()` internally), not per-route middleware.

> **Nota:** `Application::get()`/`post()`/`put()`/`delete()`/`patch()` têm a assinatura
> `(string $path, callable|array $handler)` — apenas 2 parâmetros. Passar um middleware como
> argumento extra (`$app->get('/protegido', $middleware, $handler)`) resulta em
> `ArgumentCountError`; essa sintaxe não é suportada por `Application`. O Router subjacente
> (`pivotphp/core-routing`) aceita middlewares por rota/grupo via `Router::add()` /
> `Router::group($prefix, $callback, $middlewares)`, mas `Application` não expõe um wrapper
> para isso — use middleware global via `use()`/`middleware()`, ou chame o `Router` estático
> diretamente (`PivotPHP\Core\Routing\Router::group(...)`) se precisar de escopo por rota/grupo.

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

> **Nota de revisão:** a seção abaixo foi corrigida para refletir `src/Http/Request.php` e
> `src/Http/Response.php` (v2.1.1). Diversos itens da versão anterior deste documento
> descreviam métodos que não existem (ex.: `uri()`, `query()`, `headers()`, `body()`,
> `cookies()`, `files()` como chamadas de método) — vários desses dados são, na verdade,
> **propriedades mágicas** (via `__get`), não métodos, e alguns têm tipos diferentes dos
> documentados anteriormente (ex.: `$req->body` é `stdClass`, não `string`).

### Basic Properties / Methods
```php
$req->method                    // string - HTTP method (property, via __get)
$req->getUri(): UriInterface    // PSR-7 method - Request URI (não existe uri(): string)
$req->ip(): string              // Client IP
$req->userAgent(): string       // User agent
```

### Parameters
```php
$req->param(string $key, mixed $default = null): mixed  // Route parameter
$req->params                                             // stdClass - all route parameters (property)
$req->get(string $key, mixed $default = null): mixed    // Query parameter
$req->query                                              // stdClass - all query parameters (property)
```

### Headers
```php
$req->header(string $name): ?string   // Single header
$req->headers                         // HeaderRequest object (property, not array)
$req->headers->getAllHeaders(): array // All headers as array
```

### Body Data
```php
$req->body                               // stdClass - parsed body (property, not a raw string)
$req->getBodyAsStdClass(): \stdClass     // JSON as object (alias/explicit accessor)
$req->input(string $key, mixed $default = null): mixed  // JSON property
```

### Cookies
```php
$req->getCookieParams(): array   // PSR-7 method — all cookies (não existe cookie()/cookies() Express-style)
```

### Files
```php
$req->file(string $name): ?array   // Single uploaded file
$req->files                        // array - all uploaded files (property)
```

### Express.js Compatibility
```php
$req->param('id')           // Route parameter
$req->query                 // Query parameters (property)
$req->get('param')          // Query parameter
$req->header('Accept')      // Request header
$req->ip()                  // Client IP
```

## Response Object

### Basic Response
```php
$res->send(mixed $data = ''): self   // Send content
$res->html(mixed $html): self        // Send HTML
$res->json(mixed $data): self        // Send JSON (não aceita $flags — sanitiza/serializa internamente)
$res->status(int $code): self        // Set status code
```

### Headers
```php
$res->header(string $name, string $value): self  // Set a single header
```

> Não existe `$res->headers(array $headers)` para setar múltiplos headers de uma vez —
> chame `header()` uma vez por header.

### Cookies
```php
$res->cookie(
    string $name,
    string $value,
    int $expires = 0,
    string $path = '/',
    string $domain = '',
    bool $secure = false,
    bool $httponly = true
): self
```

> A assinatura usa parâmetros posicionais, não um array `$options` — não existe suporte a
> `samesite` nesse método.

### Redirects
```php
$res->redirect(string $url, int $code = 302): self
```

### File Downloads

Não existem `$res->download()` nem `$res->attachment()` em `Response`. Para servir arquivos
estáticos, use `Application::staticFiles()` / o mecanismo de `StaticFileManager` do framework.

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

`Application` does not have a `config()` method. Configuration is managed through the
`Config` object returned by `$app->getConfig()` (`src/Core/Config.php`); there is no
`$configPath` constructor parameter — set the path explicitly and load files via `Config`.

### Environment-based Config
```php
// config/app.php
return [
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
];

// Bootstrap
$app = new Application(__DIR__);
$app->getConfig()->setConfigPath(__DIR__ . '/config')->loadAll();

// Access in application
$debug = $app->getConfig()->get('app.debug');
```

### Custom Configuration
```php
$app->getConfig()->set('custom.setting', 'value');
$value = $app->getConfig()->get('custom.setting');
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
$cache = $app->make('cache');
```

> **Nota:** use `make()` para resolver serviços do container, não `get()` — `Application::get()`
> tem a assinatura `get(string $path, callable|array $handler)` e é usado para registrar
> rotas HTTP GET, não para resolver bindings.

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

The benchmark figures previously listed here (labeled "v1.1.3-dev") are historical
microbenchmarks and have not been revalidated for 2.1.x. See
[`PERFORMANCE_RESULTS.md`](../PERFORMANCE_RESULTS.md) (also historical, v1.1.4) for the
last recorded cross-framework numbers, and `composer benchmark` to generate current figures
locally.

## Migration Notes

For version-to-version migration steps and breaking changes, see
[`MIGRATION_GUIDE.md`](MIGRATION_GUIDE.md) and [`CHANGELOG.md`](../CHANGELOG.md) — the most
recent breaking-change release is v2.0.0 (Legacy Cleanup Edition); v2.1.0 started a
deprecation cycle (see `CHANGELOG.md`) without removing any public API yet.

## Community & Support

- **GitHub**: https://github.com/PivotPHP/pivotphp-core
- **Issues**: https://github.com/PivotPHP/pivotphp-core/issues
- **Examples**: Ready-to-run examples in `/examples` directory

---
**PivotPHP Core v2.1.1** - Express.js for PHP 🐘⚡
