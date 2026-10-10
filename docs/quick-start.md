# Quick Start Guide

Get up and running with PivotPHP Core 4.x in under 5 minutes. This guide covers installation, a
first API, routing, security and testing.

> Prefer the fastest path? `composer create-project pivotphp/skeleton my-api` scaffolds a complete
> project (config, routes, controllers, tests).

## 🚀 Installation

### Prerequisites

- **PHP 8.1+** with extensions: `json`, `mbstring`
- **Composer**

### Install via Composer

```bash
composer require pivotphp/core
```

This pulls the core plus its companions: `pivotphp/core-routing` (routing engine),
`pivotphp/http` (PSR-7/PSR-17 + Express facade) and `pivotphp/security` (security middlewares).

## 🔥 Your First API

Create `index.php`:

```php
<?php
require_once 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// Basic route
$app->get('/', fn ($req, $res) => $res->json(['message' => 'Hello, PivotPHP!']));

// Route with a parameter
$app->get('/users/:id', fn ($req, $res) => $res->json([
    'user_id' => $req->param('id'),
    'name' => 'John Doe',
]));

// JSON POST endpoint — handlers receive ExpressRequest/ExpressResponse
$app->post('/users', function ($req, $res) {
    $data = $req->json();               // decoded JSON body (array)
    return $res->status(201)->json(['message' => 'User created', 'data' => $data]);
});

$app->run();
```

Handlers receive `PivotPHP\Http\ExpressRequest`/`ExpressResponse` (the Express facade over PSR-7)
and must return the response. Read input with `$req->param()`, `$req->query()`, `$req->input()` or
`$req->json()`; reach the underlying PSR-7 message with `$req->psr7()`.

### Test Your API

```bash
php -S localhost:8080

curl http://localhost:8080/                    # {"message":"Hello, PivotPHP!"}
curl http://localhost:8080/users/123           # {"user_id":"123","name":"John Doe"}
curl -X POST -H "Content-Type: application/json" \
     -d '{"name":"Alice"}' \
     http://localhost:8080/users               # {"message":"User created","data":{"name":"Alice"}}
```

## 🎯 Array Callable Routes

Register controllers as array callables (`[Controller::class, 'method']`) — supported from
**PHP 8.1**, and recommended for Symfony/container-style code. The legacy `'Controller@method'`
string form is **not** supported (throws `TypeError`).

```php
class UserController
{
    public function index($req, $res)
    {
        return $res->json(['users' => []]);
    }

    public function show($req, $res)
    {
        return $res->json(['user' => ['id' => $req->param('id')]]);
    }
}

$app->get('/users', [UserController::class, 'index']);
$app->get('/users/:id', [UserController::class, 'show']);
```

## 🛡️ Adding Security

Security middlewares come from [`pivotphp/security`](https://github.com/PivotPHP/pivotphp-security)
(pulled in with the core). Middlewares that build responses take a PSR-17
`ResponseFactoryInterface`:

```php
use PivotPHP\Http\Factory\Psr17Factory;
use PivotPHP\Security\Cors\{CorsConfig, CorsMiddleware};
use PivotPHP\Security\Headers\SecurityHeadersMiddleware;

$factory = new Psr17Factory();

$app->use(new SecurityHeadersMiddleware());   // needs `composer require bepsvpt/secure-headers`
$app->use(new CorsMiddleware($factory, new CorsConfig(
    allowedOrigins: ['https://yourfrontend.com'],
    allowedMethods: ['GET', 'POST', 'PUT', 'DELETE'],
    allowedHeaders: ['Content-Type', 'Authorization'],
)));
```

Recommended order: `TrustedProxy → SecurityHeaders → CORS → RateLimit → (body parsing) → CSRF → JWT
→ routes`. Invalid settings (e.g. `'*'` with credentials) throw at boot. For CSRF, JWT, rate
limiting and trusted proxies, see the
[pivotphp/security README](https://github.com/PivotPHP/pivotphp-security#readme).

## 🔍 Route Patterns

```php
// Parameters
$app->get('/users/:id', $handler);
$app->get('/files/{name}', $handler);               // { } syntax also supported

// Regex constraints
$app->get('/users/:id<\d+>', $handler);             // only numeric IDs
$app->get('/posts/:slug<[a-z0-9-]+>', $handler);

// Shortcuts
$app->get('/posts/:date<date>', $handler);          // YYYY-MM-DD
$app->get('/files/:uuid<uuid>', $handler);          // UUID

// Multiple parameters
$app->get('/users/:userId/posts/:postId<\d+>', $handler);
```

Optional parameters (`:param?`) are **not** supported (throws at registration).

## 🔧 Configuration

`Application` reads `config/` (see the skeleton for the layout). Load it explicitly when you don't
use the skeleton:

```php
$app = new Application(__DIR__);
$app->getConfig()->setConfigPath(__DIR__ . '/config')->loadAll();

$debug = $app->getConfig()->get('app.debug', false);
```

With `app.debug` **off** (default), errors return a generic JSON body; with it **on**, the response
includes the exception message, file, line and trace — never enable it in production.

## 🧪 Testing Your API

Drive the real pipeline with `$app->handle()` (a PSR-7 `ServerRequestInterface`):

```php
<?php
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

class BasicTest extends TestCase
{
    public function testBasicRoute(): void
    {
        $app = new Application();
        $app->get('/test', fn ($req, $res) => $res->json(['status' => 'ok']));

        $response = $app->handle(new ServerRequest('GET', '/test'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"status":"ok"}', (string) $response->getBody());
    }
}
```

## 🚀 Next Steps

1. **[API Reference](API_REFERENCE.md)** — methods and types
2. **[Migration Guide](MIGRATION_GUIDE.md)** — moving from 3.x to 4.0
3. **[pivotphp/security](https://github.com/PivotPHP/pivotphp-security)** — CORS, headers, CSRF, JWT, rate limiting
4. **[OpenAPI/Swagger](API_REFERENCE.md)** — `ApiDocumentationMiddleware` generates a spec from your routes
5. **[Examples](../examples/)** — runnable examples
6. **[Skeleton](https://github.com/PivotPHP/pivotphp-skeleton)** — full project template

## ⚠️ About the Project

**PivotPHP Core is maintained by a single person** and may not receive frequent updates. It is ideal
for prototypes and proofs of concept, not for critical production systems that require 24/7 support.

---

**That's it!** You now have a solid base for building APIs with PivotPHP Core 4.x. 🎉
