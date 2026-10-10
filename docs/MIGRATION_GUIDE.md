# PivotPHP Core — Migration Guide

## 4.x → 5.0

5.0 requires `pivotphp/core-routing` 3.0, whose `Router` keeps its state **per instance** (SPEC-076).
Each `Application` owns its own router, so two applications in the same process (tests, persistent
workers) no longer share routes, groups or middlewares.

| 4.x | 5.0 |
|---|---|
| `Router::get('/x', $h)` / `Router::group(...)` (static) | `$app->get('/x', $h)` / `$app->group('/api', fn () => ..., [$mw])` |
| `Router::getRoutes()`, `Router::identify()` | `$app->getContainer()->get('router')->getRoutes()` / `->identify()` |
| `Router::clear()` between tests | Not needed: create a new `Application` |
| `StaticFileManager::registerDirectory($prefix, $dir)` | `$app->staticFiles($prefix, $dir)` (registers in the app's router) |

- `Application` gained `options()`, `head()`, `any()`, `match()` and `group()`, and every verb accepts
  `$metadata` and route middlewares: `$app->get($path, $handler, $metadata = [], ...$middlewares)`.
- `Application::handle()` stores the application's router in the request attribute `Router::class`;
  `ApiDocumentationMiddleware` reads the routes from it (or from a router passed to its constructor).
- Code outside the application that still needs the old global table can use the deprecated
  `RouterFacade` / `Router::default()` from `pivotphp/core-routing` while it is migrated. Routes
  registered there are **not** seen by an `Application`.

## 3.x → 4.0

4.0 is a breaking release. Read the sections that apply to your application; the
[CHANGELOG](../CHANGELOG.md) lists every change. Guides for older versions live in
[releases/](releases/).

### 1. Dependencies

```bash
composer require pivotphp/core:^4.0
```

`pivotphp/http`, `pivotphp/core-routing` (^2.2) and `pivotphp/security` are installed with the core.
Install the libraries of the security adapters you use (`firebase/php-jwt`, `bepsvpt/secure-headers`,
`yiisoft/csrf`, `symfony/rate-limiter`). `ext-mbstring` is required; `ext-session` is no longer used.

### 2. HTTP: request, response and middleware

| 3.x | 4.0 |
|---|---|
| `PivotPHP\Core\Http\Request` / `Response` (hybrid) | `PivotPHP\Http\ExpressRequest` / `ExpressResponse` in routes; PSR-7 elsewhere |
| `$req->body`, `$req->body()`, `getBodyAsStdClass()` | `$req->input('field')`, `$req->psr7()->getParsedBody()` (array), `$req->json()` |
| `$req->get('q')` | `$req->query('q')` |
| `$req->user = $x` (dynamic properties) | `$next($req->withAttribute('user', $x))` → `$req->psr7()->getAttribute('user')` |
| `$req->uri()`, `$req->headers()` | `$req->path()`, `$req->header('Name')`, `$req->psr7()` |
| `$app->handle()` with no PSR-7 | `$app->handle(ServerRequestInterface $request): ResponseInterface` |
| `BaseMiddleware` / `handle($req, $res, $next)` | PSR-15 `MiddlewareInterface`, or callable `fn ($req, $res, $next)` |

Callable middleware in 4.0:

```php
$app->use(function ($req, $res, $next) {            // $req: PSR-7 ServerRequestInterface
    if ($req->getHeaderLine('X-Key') === '') {
        return $res->error(401, 'Unauthorized');      // short-circuit
    }

    return $next($req->withAttribute('k', 1))         // forward a modified request
        ->withHeader('X-Handled', 'yes');             // change the returned response
});
```

Headers set on `$res` are **not** merged into the response returned by `$next()`.
`$app->use('/path', $middleware)` (path-scoped) does not exist: check the path inside the middleware
or use `$app->group()`.

Routes must **return** the response (`return $res->json(...)`).

### 3. Security → `pivotphp/security`

The core no longer ships security middlewares. `pivotphp/security` (^1.0) is installed as a core
dependency; update imports and configuration:

| Removed from the core | Replacement in `pivotphp/security` |
|---|---|
| `Middleware\Http\CorsMiddleware` | `Cors\CorsMiddleware($responseFactory, new CorsConfig(...))` |
| `Middleware\Security\SecurityHeadersMiddleware` | `Headers\SecurityHeadersMiddleware(new SecurityHeadersConfig(...))` |
| `Middleware\Security\CsrfMiddleware`, `Utils::csrfToken()`, `Utils::checkCsrf()` | `Csrf\CsrfMiddleware($responseFactory, $token)` (tokens from `yiisoft/csrf`) |
| `Middleware\Security\AuthMiddleware` (JWT) | `Jwt\JwtAuthMiddleware($responseFactory, new JwtConfig(...))` |
| `Authentication\JWTHelper` | `Jwt\JwtIssuer` (issue) + `JwtAuthMiddleware` (verify) |
| `Middleware\RateLimiter`, alias `'rate-limiter'` | `RateLimit\RateLimitMiddleware` + `Proxy\TrustedProxyMiddleware` |
| `Middleware\Security\XssMiddleware` | none — escape output, send a CSP |

`$responseFactory` can be `PivotPHP\Http\Factory\Psr17Factory`. The JWT adapter needs
`firebase/php-jwt`, security headers need `bepsvpt/secure-headers`, CSRF needs `yiisoft/csrf` and rate
limiting needs `symfony/rate-limiter` — install only what you use.

**Behaviour changes (all intentional security fixes):**

- CORS: preflight returns `204` from the middleware (never reaches routes); disallowed origins get no
  `Access-Control-Allow-Origin` (previously `null`); requests without `Origin` get no CORS headers;
  `'*'` with credentials is rejected at boot; `Vary: Origin` is sent.
- Security headers: `X-XSS-Protection` is no longer sent; HSTS, a restrictive CSP and
  `Referrer-Policy` are sent by default.
- CSRF: `PUT`, `PATCH` and `DELETE` are protected too; the token is accepted from the body field or the
  `X-CSRF-Token` header; failures return `403` without reaching the route.
- JWT: secrets shorter than 32 bytes (HS256) and unknown algorithms are rejected at boot; claims are in
  the `user` request attribute; `publicPaths` is honoured; failures return `401` with `WWW-Authenticate`.
  Basic/opaque-bearer/API-key authentication: write your own PSR-15 middleware.
- Rate limiting: the old limiter never limited under PHP-FPM (security flaw, SPEC-093); the new one
  needs a shared storage and a lock in production.
- Client IP: `X-Forwarded-For` is honoured only from configured trusted proxies (`client_ip` attribute).

Details and options: [pivotphp/security README](https://github.com/PivotPHP/pivotphp-security#readme).

### 4. Removed without replacement

| Removed | Why / what to do |
|---|---|
| `Middleware\Performance\CacheMiddleware` | Served one user's response to another and failed on every cache hit; cache at the HTTP layer (reverse proxy/CDN) with proper `Vary`/`Cache-Control`. |
| `Middleware\Http\ErrorMiddleware` | The `Application` already converts exceptions (`error_id`, details only with `app.debug`). |
| `Middleware\RateLimiter`, alias `'rate-limiter'` | Never limited under PHP-FPM; use `RateLimitMiddleware` from `pivotphp/security`. |
| `Middleware\Security\XssMiddleware` | Escape output; send a CSP (`SecurityHeadersMiddleware`). |
| `Cache\*`, `Database\PDOConnection`, `Contracts\JsonOptimizerInterface` | Unused; use a PSR-16 cache library and `Database`. |
| `PivotPHP\Core\Routing\*`, `PivotPHP\Core\Application` aliases | Never loaded; use `PivotPHP\Routing\Router\*` and `PivotPHP\Core\Core\Application`. |
| `registerExtension($name, $provider, $config)` third argument | It was ignored. |

### 5. Behaviour changes worth testing

- Unknown method on a known path → `405` + `Allow` (was `404`); `OPTIONS` → `204` + `Allow`; `HEAD`
  uses the `GET` route.
- Malformed JSON body → `400`.
- Hooks, listeners and extensions registered right after `new Application()` now run.
- `handle()` no longer installs PHP error handlers; `run()` does (and restores them).
