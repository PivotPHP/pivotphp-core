# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

PivotPHP Core is a PHP microframework inspired by Express.js, designed for building APIs. Current
version: **5.0.0** (router state per `Application` — SPEC-076). It targets PHP 8.1+ and is
strict PSR-7 (HTTP messages), PSR-15 (middleware) and PSR-12 (style) compliant.

Since the 4.0 line the core does one thing: it wires together the application, the routing engine and
the middleware pipeline. The other responsibilities live in companion packages that are installed as
direct dependencies:

| Package | Responsibility |
|---|---|
| `pivotphp/http` | PSR-7/PSR-17, the `ExpressRequest`/`ExpressResponse` façade, body parsing, SAPI emitter |
| `pivotphp/core-routing` | Route registration, compilation and matching; groups; static files |
| `pivotphp/security` | CORS, security headers, CSRF, JWT, rate limiting, trusted proxies |

This is a single package inside a multi-repository workspace — always run composer/git/phpunit
commands from this directory, not from the workspace root.

## Essential Commands

```bash
# Testing (test suites are Unit and Integration — see phpunit.xml)
composer test                 # Run all tests
composer test:ci              # phpunit via the local binary
composer test:unit            # Unit suite only
composer test:integration     # Integration suite only
composer test:coverage        # Coverage report (XDEBUG_MODE=coverage)

# Run a single test file
vendor/bin/phpunit tests/Core/ApplicationTest.php

# Quality gates
composer phpstan              # Static analysis (Level 9)
composer cs:check             # PSR-12 style check
composer cs:fix               # Auto-fix style
composer quality:check        # phpstan + tests + cs:check
composer quality:ci           # CI-optimised quality check
composer prepush:validate     # phpstan + unit + integration + cs:check:summary

# Validation
composer validate:docs        # ./scripts/validation/validate-docs.sh
composer validate:project     # php scripts/validation/validate_project.php
php scripts/validation/validate_project.php

# Multi-PHP testing (Docker)
composer docker:test-all      # All supported PHP versions (8.1–8.4)
composer docker:test-quality  # All versions + quality checks

# Pre-commit / release
composer precommit:test                          # ./scripts/pre-commit
./scripts/release/prepare_release.sh             # validate the current version for release
./scripts/release/version-bump.sh <major|minor|patch>  # bump VERSION + commit + tag
./scripts/release/release.sh <version> [type]    # create the release commit/tag
```

### Domínio de testes (SPEC-094)

- O `pivotphp-core` é o **dono do framework**: ciclo de vida de `Application` (`boot`, `run`), container PSR-11,
  providers, eventos PSR-14, banco de dados (`Database`), validação e pipeline PSR-15.
- Testes de integração no core cobrem **apenas o wiring** (rota -> handler -> resposta), sem duplicar
  asserções de detalhes internos de mensagens HTTP (que pertencem a `pivotphp/http`) ou de roteamento interno
  (que pertencem a `pivotphp/core-routing`).
- Regra: um teste que passaria mesmo com o `pivotphp/http` quebrado não pertence ao core.

### Running Examples

```bash
composer examples:hello-world     # 01-basics
composer examples:basic-routes    # 01-basics
composer examples:request-response
composer examples:json-api
composer examples:regex-routing   # 02-routing
composer examples:route-parameters
composer examples:cors-middleware # 03-middleware
composer examples:auth-middleware
composer examples:rest-api        # 04-api
composer examples:jwt-auth        # 06-security
composer examples:array-callables # 07-advanced
```

## Entry Point

```php
use PivotPHP\Core\Core\Application;

$app = new Application();                 // or Application::create() / Application::express()

$app->get('/hello/:name', fn ($req, $res) => $res->json(['hello' => $req->param('name')]));

$app->run();                               // SAPI entry point: installs PHP handlers, emits the response
```

`Application::handle(?ServerRequestInterface): ResponseInterface` runs the same pipeline without
emitting (tests, workers). `Application::VERSION` (and the `VERSION` file) hold the current version.

## Route Handler Syntax

Handlers can be a closure, a named function or an array callable:

```php
$app->get('/users', function ($req, $res) {            // closure (recommended)
    return $res->json(['users' => []]);
});

$app->get('/users', [UserController::class, 'index']); // array callable (PHP 8.1+)
$app->post('/users', [$controller, 'store']);          // instance method
```

- Array callables `[Controller::class, 'method']` work since **PHP 8.1** — not 8.4. Instance
  methods are resolved lazily through the container (`bind()`/`singleton()`), otherwise the class is
  instantiated with no required constructor arguments.
- The legacy string form `'Controller@method'` is **not** supported — it is not `callable` under
  PHP and throws a `TypeError` (route signatures are `callable|array`).

## Code Architecture

### Source layout

```
src/
├── Core/            Application, ApplicationInterface, Config, Environment
├── Database/        Database (PDO wrapper; ext-pdo suggested)
├── Events/          PSR-14: EventDispatcher, ListenerProvider, lifecycle events
├── Exceptions/      HttpException, ContextualException, Container/Database exceptions
├── Logging/         PsrLogger (PSR-3)
├── Middleware/      MiddlewareStack, Http/ApiDocumentationMiddleware
├── Providers/       PSR-11 Container + ServiceProviders (extension mechanism)
├── Support/         HookManager, Str
├── Utils/           Arr, CallableResolver, Utils
├── Validation/      Validator
└── functions.php    Global helper functions
```

### Key components

1. **Application** (`src/Core/Application.php`) — bootstraps the framework, owns the PSR-11
   container, its **own** routing engine instance (`PivotPHP\Routing\Router\Router`, from
   `pivotphp/core-routing` 3.x — no state shared between applications)
   and the global `MiddlewareStack`. It registers six core service providers in its constructor
   (`Container`, `Event`, `Logging`, `Hook`, `Extension`, `Routing`), configures error handling and
   dispatches lifecycle events (`ApplicationStarted`, `RequestReceived`, `ResponseSent`).

2. **Middleware** (`src/Middleware/`) — PSR-15 compliant. `$app->use($middleware)` (alias
   `middleware()`) registers a **global** middleware that runs in registration order around
   routing (including for 404/OPTIONS). Both `MiddlewareInterface` instances and callables
   `fn ($req, $res, $next)` are accepted; `$req` is the PSR-7 `ServerRequestInterface` and `$next`
   runs the rest of the pipeline once. Route/group middleware is provided by the underlying
   `pivotphp/core-routing` router (`$app->group(...)`, or `...$middlewares` after `$metadata` on each
   verb). The only middleware shipped in this package is
   `Http\ApiDocumentationMiddleware`.

3. **HTTP layer** — provided by `pivotphp/http`. Route handlers receive
   `PivotPHP\Http\ExpressRequest`/`ExpressResponse`; the underlying PSR-7 message is reachable with
   `$req->psr7()`. The body is parsed before the pipeline runs.

4. **Security** — provided by `pivotphp/security`. There are **no** security middlewares in the
   core: `CorsMiddleware`, `SecurityHeadersMiddleware`, `CsrfMiddleware`, `JwtAuthMiddleware`,
   `RateLimitMiddleware` and `TrustedProxyMiddleware` all live in that package.

5. **Validation** (`src/Validation/Validator.php`) — string-rule validator
   (`'required|email|max:255'`). 4.1.0 added the `nullable`/`sometimes` modifier semantics and
   type-aware `min`/`max` (numeric value, `mb_strlen` for strings, item count for arrays). Unknown
   rules throw `\InvalidArgumentException`.

6. **OpenAPI** (`src/Middleware/Http/ApiDocumentationMiddleware.php`) — generates an OpenAPI 3.0.0
   spec from registered routes (no PHPDoc parsing). 4.1.0 added path templating: `:param`,
   `:param<regex>` and `{param<regex>}` are normalised to `{param}` and emitted as `parameters`
   (`in: path`, `required: true`).

7. **Extensions** — service providers (`PivotPHP\Core\Providers\ServiceProvider`) registered with
   `$app->register(MyProvider::class)`, plus a WordPress-style hooks system (`addAction`,
   `addFilter`, `doAction`, `applyFilter`).

### Request / response API (4.x)

- Read input with `$req->json()` (decoded body), `$req->input('field')` (JSON/form then query),
  `$req->query('field')`, `$req->param('id')`, `$req->header('Name')`, `$req->ip()`.
- There is **no** `$req->body` — that was removed in 4.0. See
  [docs/MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md) for the full 3.x → 4.0 mapping.
- Handlers must **return** the response (`return $res->json(...)`).

## Code Style Requirements

- **PHP 8.1+**, `declare(strict_types=1)` throughout.
- **PHPStan Level 9** must pass with zero errors.
- **PSR-12** via PHP_CodeSniffer (`composer cs:check` / `composer cs:fix`).
- No comments unless required; prefer readable, focused methods and single-responsibility classes.

## Development Workflow

1. Run `composer precommit:test` (or `composer quality:check`) before committing.
2. All tests must pass, PHPStan Level 9 must be clean and PSR-12 must comply.
3. Behavior changes require test updates.
4. Documentation (`docs/`, `README.md`, `CHANGELOG.md`) must reflect implemented behavior.
5. Releases: bump with `./scripts/release/version-bump.sh <type>`, validate with
   `./scripts/release/prepare_release.sh`, then finalise with
   `./scripts/release/release.sh <version>` (see
   [docs/VERSIONING_GUIDE.md](docs/VERSIONING_GUIDE.md)).

## Current Version Status

- **Current version**: 5.0.0
- **Code quality**: PHPStan Level 9, PSR-12 compliant
- **Architecture**: application + pipeline + router integration; HTTP, routing and security are
  delegated to `pivotphp/http`, `pivotphp/core-routing` and `pivotphp/security`
- **Companions**: `pivotphp/core-routing` `^3.0`, `pivotphp/http` `^1.0`, `pivotphp/security` `^1.0`
- **Extensions**: `pivotphp/cycle-orm` is **paused** and only targets core 1.x — do not treat it as
  an active integration

## Important Notes

- Do not reintroduce removed 2.x/3.x concepts (object pooling `JsonBuffer`/`PerformanceMode`,
  `OpenApiExporter`, `getBodyAsStdClass`, `$req->body`) except when documenting a migration path.
- Route parameters use `:id` or `{id}`; constraints use `/users/:id<\d+>` and shortcuts such as
  `<slug>`, `<uuid>`, `<date>`.
- `'Controller@method'` must never be used or documented as supported.
