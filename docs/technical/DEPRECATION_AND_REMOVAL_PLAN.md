# Deprecation and Removal Plan

**Version:** 2.0.0 → 3.0.0
**Date:** 2026-05-29
**Status:** Active

## Overview

This document establishes the official deprecation and removal schedule for identified dead code, duplicates, and design violations in PivotPHP Core. The plan follows a two-version cycle:

- **v2.1.0** — Deprecation announced: `@deprecated` annotations added, `trigger_error(E_USER_DEPRECATED)` calls inserted, documentation updated.
- **v3.0.0** — Breaking removal: deprecated code deleted, tests updated, aliases removed.

No item is removed without first completing a full deprecation cycle with at least one minor release between announcement and removal.

---

## Summary Table

| ID | Item | File | Type | Deprecated in | Removed in | Impact | Progresso |
|---|---|---|---|---|---|---|---|
| ITEM-001 | `PivotPHP\Core\Core\Container` | `src/Core/Container.php` | Class | v2.1.0 | v3.0.0 | 1 test file | `@deprecated` + `trigger_error` em `getInstance()` aplicados. Aguardando v3.0.0. |
| ITEM-002 | `Request::getIp()` | `src/Http/Request.php:994` | Method | v2.1.0 | v3.0.0 | 1 src file (RateLimiter) | `@deprecated` ja existia; `trigger_error` adicionado; `RateLimiter.php:68` atualizado para `ip()`. |
| ITEM-003 | `PivotPHP\Core\Middleware\LoadShedder` | `src/Middleware/LoadShedder.php` | Class | v2.1.0 | v3.0.0 | 1 src alias, 1 test file | `@deprecated` + `trigger_error` em `__construct()` e `handle()` aplicados. |
| ITEM-004 | `PivotPHP\Core\Middleware\Performance\RateLimitMiddleware` | `src/Middleware/Performance/RateLimitMiddleware.php` | Class | v2.1.0 | v3.0.0 | 1 validation script, 2 test files | `@deprecated` + `trigger_error` aplicados; FQN errado em `RateLimitMiddlewareTestPsr15.php` corrigido. |
| ITEM-005 | `Str::startsWith/endsWith/contains` | `src/Support/Str.php:80-99` | 3 Methods | v2.1.0 | v3.0.0 | 1 test file (6 assertions) | `@deprecated` + `trigger_error` nos 3 metodos aplicados. |
| ITEM-006 | `PivotPHP\Core\Providers\Logger` | `src/Providers/Logger.php` | Class | v2.1.0 | v3.0.0 | 1 src file (LoggingServiceProvider) | `PsrLogger` criado em `src/Logging/`; `LoggingServiceProvider` atualizado; `@deprecated` + `trigger_error` em `Providers\Logger` aplicados; `Logging\Logger.php` morto removido. |
| ITEM-007 | `PivotPHP\Core\Providers\EventDispatcher` | `src/Providers/EventDispatcher.php` | Class | v2.1.0 | v3.0.0 | `Providers/` como namespace incorreto | `@deprecated` aplicado em `Providers\EventDispatcher`; `Events\EventDispatcher` atualizado para PSR-14 e absorve responsabilidade. |
| ITEM-008 | `PivotPHP\Core\Providers\ListenerProvider` | `src/Providers/ListenerProvider.php` | Class | v2.1.0 | v3.0.0 | `Providers/` como namespace incorreto | `@deprecated` aplicado; `Events\ListenerProvider` e o substituto canonico. |

---

## Items

---

### [ITEM-001] Class: `PivotPHP\Core\Core\Container`

| Property | Value |
|---|---|
| File | `src/Core/Container.php` |
| Lines | 1–478 |
| Substitute | `PivotPHP\Core\Providers\Container` |
| Deprecation | v2.1.0 |
| Removal | v3.0.0 |
| Has `@deprecated`? | **Sim** (adicionado na refatoracao 2026-05-29) |

**Progresso (2026-05-29)**

- `@deprecated v2.1.0 Use \PivotPHP\Core\Providers\Container instead.` adicionado na classe.
- `trigger_error(...)` adicionado em `getInstance()`.
- Aguardando v3.0.0 para remocao do arquivo e migracao dos testes.

**Problem**

`Core\Container` is a singleton IoC container with reflection-based autowiring, tagging, and `call()`. It is never instantiated by `Application`. The application imports and instantiates `PivotPHP\Core\Providers\Container` (PSR-11 compliant, the actual production container). `Core\Container` is dead code with an incompatible API surface.

Key differences:
- `Core\Container`: `getInstance()`, `make()`, `call()`, `tag()`, `tagged()`, `bound()` — singleton, private constructor.
- `Providers\Container`: `get()`, `has()`, `bind()`, `singleton()`, `instance()`, `alias()` — PSR-11, public constructor.

**References found**

```
# src/ — zero production references
# tests/:
tests/Core/ContainerTest.php  — tests the dead container (573 lines, testing code that has no effect on production)
```

**Migration for users**

1. Replace `use PivotPHP\Core\Core\Container` with `use PivotPHP\Core\Providers\Container`.
2. Replace `Container::getInstance()` with `new Container()` or `$app->make(ContainerInterface::class)`.
3. `make()` with autowiring → use service providers with explicit bindings (`$container->bind(...)`).
4. `tag()` / `tagged()` → no equivalent; use named bindings.
5. `call()` → no equivalent; resolve dependencies manually.

**Actions for v2.1.0**

- Add class-level `@deprecated v2.1.0 Use \PivotPHP\Core\Providers\Container instead.`
- Add `trigger_error(...)` in `getInstance()`.
- Suppress deprecation in `tests/Core/ContainerTest.php` during transition.

**Actions for v3.0.0**

- Delete `src/Core/Container.php`.
- Delete `tests/Core/ContainerTest.php` (or migrate assertions to test `Providers\Container`).
- No aliases exist in `aliases.php` or `aliases-performance-tools.php`.

---

### [ITEM-002] Method: `Request::getIp()`

| Property | Value |
|---|---|
| File | `src/Http/Request.php` |
| Lines | 990–999 |
| Substitute | `Request::ip()` |
| Deprecation | v2.1.0 |
| Removal | v3.0.0 |
| Has `@deprecated`? | **Sim** (presente desde v2.0.0) |

**Progresso (2026-05-29)**

- `@deprecated` ja existia desde v2.0.0.
- `trigger_error('Request::getIp() is deprecated. Use Request::ip() instead.', E_USER_DEPRECATED)` adicionado no corpo do metodo.
- `src/Middleware/RateLimiter.php:68` atualizado: `$request->getIp()` substituido por `$request->ip()`.

**Problem**

`getIp()` delegates to `ip()` since v2.0.0. The historic implementation read `HTTP_X_FORWARDED_FOR` without validating IP ranges, making it spoofable for rate-limiting and access-control. `ip()` applies `FILTER_VALIDATE_IP` with `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`. Both methods now return the same value, but `getIp()` carries the misleading historic name.

**References found**

```
src/Middleware/RateLimiter.php:68   return $request->getIp();  ← must be fixed in v2.1.0
src/Http/Request.php:994            @deprecated annotation (already present)
```

**Migration for users**

```php
// Before:
$ip = $request->getIp();

// After:
$ip = $request->ip();
```

**Actions for v2.1.0**

- Add `trigger_error('Request::getIp() is deprecated. Use Request::ip() instead.', E_USER_DEPRECATED)` inside the method body.
- Fix `src/Middleware/RateLimiter.php:68`: change `$request->getIp()` to `$request->ip()`.

**Actions for v3.0.0**

- Delete the `getIp()` method from `src/Http/Request.php`.
- Verify `RateLimiter.php` was already updated (ITEM-003 dependency).

---

### [ITEM-003] Class: `PivotPHP\Core\Middleware\LoadShedder`

| Property | Value |
|---|---|
| File | `src/Middleware/LoadShedder.php` |
| Lines | 1–150 |
| Substitute | `PivotPHP\Core\Middleware\RateLimiter` |
| Deprecation | v2.1.0 |
| Removal | v3.0.0 |
| Has `@deprecated`? | **Sim** (adicionado na refatoracao 2026-05-29) |

**Progresso (2026-05-29)**

- `@deprecated v2.1.0 Use \PivotPHP\Core\Middleware\RateLimiter instead.` adicionado na classe.
- `trigger_error(...)` adicionado em `__construct()` e `handle()`.
- Alias `'load-shedder'` em `Application::$middlewareAliases` marcado com comentario `// @deprecated v2.1.0 — removed in v3.0.0`.

**Problem**

`LoadShedder` is a per-IP rate limiter with a sliding window in process memory. It is a feature-incomplete subset of `RateLimiter`:

- Tracks a `$requestCounts` array keyed by `"{ip}:{timestamp}"` — grows indefinitely in long-running servers (memory leak, O(n) lookup per request).
- Strategy constants (`STRATEGY_PRIORITY`, `STRATEGY_CONSERVATIVE`, etc.) exist but are never read — single algorithm only.
- Does not implement PSR-15 `MiddlewareInterface`.
- Is registered in `Application::$middlewareAliases` as `'load-shedder'`.

**References found**

```
src/Core/Application.php:103           'load-shedder' => \PivotPHP\Core\Middleware\LoadShedder::class,
tests/Middleware/SimpleLoadShedderTest.php  — 1 test file
```

**Migration for users**

```php
// Before:
$app->use(new LoadShedder(100, 60));

// After (RateLimiter — equivalent):
use PivotPHP\Core\Middleware\RateLimiter;
$limiter = new RateLimiter([
    'strategy' => RateLimiter::STRATEGY_SLIDING_WINDOW,
    'max_requests' => 100,
    'window_size' => 60,
]);
$app->use(fn($req, $res, $next) => $limiter->handle($req, $res, $next));
```

**Actions for v2.1.0**

- Add class-level `@deprecated v2.1.0 Use \PivotPHP\Core\Middleware\RateLimiter instead.`
- Add `trigger_error(...)` in `__construct()` and `handle()`.
- Mark `'load-shedder'` alias in `Application::$middlewareAliases` with comment: `// @deprecated v2.1.0 — removed in v3.0.0`.

**Actions for v3.0.0**

- Delete `src/Middleware/LoadShedder.php`.
- Remove `'load-shedder'` entry from `Application::$middlewareAliases`.
- Delete `tests/Middleware/SimpleLoadShedderTest.php`.
- No aliases in `aliases.php` or `aliases-performance-tools.php`.

---

### [ITEM-004] Class: `PivotPHP\Core\Middleware\Performance\RateLimitMiddleware`

| Property | Value |
|---|---|
| File | `src/Middleware/Performance/RateLimitMiddleware.php` |
| Lines | 1–95 |
| Substitute | `PivotPHP\Core\Middleware\RateLimiter` |
| Deprecation | v2.1.0 |
| Removal | v3.0.0 |
| Has `@deprecated`? | **Sim** (adicionado na refatoracao 2026-05-29) |

**Progresso (2026-05-29)**

- `@deprecated v2.1.0 Use \PivotPHP\Core\Middleware\RateLimiter. This class uses $_SESSION which violates HTTP statelessness.` adicionado na classe.
- `trigger_error(...)` adicionado em `__construct()`.
- Bug corrigido: `tests/Core/RateLimitMiddlewareTestPsr15.php:6` — FQN errado `PivotPHP\Core\Http\Psr15\Middleware\RateLimitMiddleware` substituido pelo FQN correto `PivotPHP\Core\Middleware\Performance\RateLimitMiddleware`.

**Problem**

`RateLimitMiddleware` implements PSR-15 `MiddlewareInterface` but uses PHP sessions (`$_SESSION`) as its storage backend, violating HTTP statelessness:

- Calls `session_start()` on every request.
- Stores per-IP timestamps in `$_SESSION['rate_limit'][$clientIp][]`.
- Incompatible with ReactPHP, Swoole, CLI testing, and stateless API contexts.

Additionally, `tests/Core/RateLimitMiddlewareTestPsr15.php:6` imports the **wrong FQN** `PivotPHP\Core\Http\Psr15\Middleware\RateLimitMiddleware` (namespace does not exist) — a latent bug introduced when the namespace was reorganized.

**References found**

```
src/Middleware/Performance/RateLimitMiddleware.php:38-81   session_start() + $_SESSION (production)
scripts/validation/validate_project.php:209                'RateLimitMiddleware' reference
tests/Middleware/Performance/RateLimitMiddlewareTest.php   correct FQN
tests/Core/RateLimitMiddlewareTestPsr15.php:6              WRONG FQN — latent bug
```

**Migration for users**

```php
// Before:
use PivotPHP\Core\Middleware\Performance\RateLimitMiddleware;
$app->use(new RateLimitMiddleware(['max' => 100, 'windowMs' => 900000]));

// After:
use PivotPHP\Core\Middleware\RateLimiter;
$limiter = new RateLimiter([
    'strategy' => RateLimiter::STRATEGY_SLIDING_WINDOW,
    'max_requests' => 100,
    'window_size' => 900,   // windowMs / 1000
]);
$app->use(fn($req, $res, $next) => $limiter->handle($req, $res, $next));
```

**Actions for v2.1.0**

- Add `@deprecated v2.1.0 Use \PivotPHP\Core\Middleware\RateLimiter. This class uses $_SESSION which violates HTTP statelessness.`
- Add `trigger_error(...)` in `__construct()`.
- **Fix latent bug** in `tests/Core/RateLimitMiddlewareTestPsr15.php:6`: update import to `PivotPHP\Core\Middleware\Performance\RateLimitMiddleware`.

**Actions for v3.0.0**

- Delete `src/Middleware/Performance/RateLimitMiddleware.php`.
- Delete `tests/Middleware/Performance/RateLimitMiddlewareTest.php`.
- Delete `tests/Core/RateLimitMiddlewareTestPsr15.php`.
- Update `scripts/validation/validate_project.php:209` to remove the class reference.
- Audit `"ext-session"` in `composer.json`: keep if `CsrfMiddleware` or `Utils.php` still use `session_start()`.

---

### [ITEM-005] Methods: `Str::startsWith()`, `Str::endsWith()`, `Str::contains()`

| Property | Value |
|---|---|
| File | `src/Support/Str.php` |
| Lines | `startsWith`: 80–83 / `endsWith`: 88–91 / `contains`: 96–99 |
| Substitute | `str_starts_with()`, `str_ends_with()`, `str_contains()` (PHP 8.0+) |
| Deprecation | v2.1.0 |
| Removal | v3.0.0 |
| Has `@deprecated`? | **Sim** (adicionado na refatoracao 2026-05-29) |
| Note | The `Str` **class** is NOT deprecated — only these 3 methods |

**Progresso (2026-05-29)**

- `@deprecated v2.1.0 Use native str_starts_with() instead.` (e equivalentes) adicionados nos 3 metodos.
- `trigger_error(...)` adicionado no corpo de cada um dos 3 metodos.

**Problem**

PHP 8.0 (November 2020) introduced `str_starts_with()`, `str_ends_with()`, and `str_contains()` as native functions. PivotPHP Core requires PHP >= 8.1, so these functions are always available. The wrapper methods duplicate functionality already in the language with no added value.

Zero callers exist in `src/`. The framework itself already uses the native PHP 8 functions throughout `src/Http/`, `src/Middleware/`, etc.

**References found**

```
tests/Support/StrTest.php:55-68   6 assertions across 3 methods (only callers)
# src/ — zero production references
```

**Migration for users**

```php
// Before:
use PivotPHP\Core\Support\Str;
Str::startsWith($url, '/api');
Str::endsWith($filename, '.php');
Str::contains($message, 'error');

// After (native PHP 8.0+):
str_starts_with($url, '/api');
str_ends_with($filename, '.php');
str_contains($message, 'error');
```

**Actions for v2.1.0**

- Add `@deprecated v2.1.0 Use native str_starts_with() instead.` to each method's PHPDoc.
- Add `trigger_error(...)` at the start of each method body.
- Mark the 6 test assertions in `tests/Support/StrTest.php` with `@group deprecated` or suppress the warning.

**Actions for v3.0.0**

- Remove the three methods from `src/Support/Str.php`.
- Remove the corresponding test assertions from `tests/Support/StrTest.php`.

---

### [ITEM-006] Class: `PivotPHP\Core\Providers\Logger`

| Property | Value |
|---|---|
| File | `src/Providers/Logger.php` |
| Lines | 1–148 |
| Substitute | `PivotPHP\Core\Logging\PsrLogger` |
| Deprecation | v2.1.0 |
| Removal | v3.0.0 |
| Has `@deprecated`? | **Sim** (adicionado na refatoracao 2026-05-29) |

**Progresso (2026-05-29)**

- `src/Logging/PsrLogger.php` criado com a implementacao migrada de `Providers\Logger` e namespace `PivotPHP\Core\Logging`.
- `LoggingServiceProvider` atualizado para usar `new \PivotPHP\Core\Logging\PsrLogger($logPath)`.
- `@deprecated v2.1.0 Use \PivotPHP\Core\Logging\PsrLogger instead.` adicionado em `Providers\Logger`.
- `trigger_error(...)` adicionado em `Providers\Logger::__construct()`.
- `src/Logging/Logger.php` (codigo morto, sem referencias) removido.

**Problem**

Two logger classes exist:

1. **`Providers\Logger`** (148 lines, extends PSR-3 `AbstractLogger`) — active; used by `LoggingServiceProvider`. Wrongly placed under `Providers/` (not a service provider).
2. **`Logging\Logger`** (171 lines, does NOT implement PSR-3) — dead code; never referenced anywhere in `src/`, tests, or examples.

`Providers\Logger` is the active implementation but has a namespace placement violation: it is a concrete service implementation living under `Providers/`. It must be moved to `Logging/`.

**References found**

```
src/Providers/LoggingServiceProvider.php:24   return new \PivotPHP\Core\Providers\Logger($logPath);
# Logging\Logger — zero references anywhere (fully dead)
```

**Migration for users**

```php
// Before (direct instantiation):
use PivotPHP\Core\Providers\Logger;
$logger = new Logger('/path/to/app.log');

// After — resolve via container (preferred):
use Psr\Log\LoggerInterface;
$logger = $app->make(LoggerInterface::class);

// Or — direct instantiation in v2.1.0–v2.x:
use PivotPHP\Core\Logging\PsrLogger;
$logger = new PsrLogger('/path/to/app.log');
```

**Actions for v2.1.0**

1. Create `src/Logging/PsrLogger.php` (copy implementation from `Providers\Logger`, change namespace to `PivotPHP\Core\Logging`).
2. Update `LoggingServiceProvider` to use `new \PivotPHP\Core\Logging\PsrLogger($logPath)`.
3. Add `@deprecated v2.1.0 Use \PivotPHP\Core\Logging\PsrLogger instead.` to `Providers\Logger`.
4. Add `trigger_error(...)` in `Providers\Logger::__construct()`.

**Actions for v3.0.0**

- Delete `src/Providers/Logger.php`.
- Delete `src/Logging/Logger.php` (dead handler-based logger — never used).
- Evaluate removing `src/Logging/FileHandler.php` and `src/Logging/LogHandlerInterface.php` if no remaining callers.

---

---

### [ITEM-007] Class: `PivotPHP\Core\Providers\EventDispatcher`

| Property | Value |
|---|---|
| File | `src/Providers/EventDispatcher.php` |
| Substitute | `PivotPHP\Core\Events\EventDispatcher` |
| Deprecation | v2.1.0 |
| Removal | v3.0.0 |
| Has `@deprecated`? | **Sim** (adicionado na refatoracao 2026-05-29) |
| Impact | Codigo interno; nenhuma referencia externa identificada |

**Contexto**

`Providers\EventDispatcher` era uma implementacao de dispatcher de eventos posicionada no namespace incorreto. Foi depreciada e `Events\EventDispatcher` foi atualizado para implementar PSR-14 (`EventDispatcherInterface`) e absorver integralmente a responsabilidade de despacho de eventos.

**Migration for users**

```php
// Before:
use PivotPHP\Core\Providers\EventDispatcher;

// After:
use PivotPHP\Core\Events\EventDispatcher;
```

**Progresso (2026-05-29)**

- `@deprecated v2.1.0 Use \PivotPHP\Core\Events\EventDispatcher instead.` adicionado em `Providers\EventDispatcher`.
- `Events\EventDispatcher` atualizado para PSR-14 (`Psr\EventDispatcher\EventDispatcherInterface`).
- Aguardando v3.0.0 para remocao de `src/Providers/EventDispatcher.php`.

**Actions for v3.0.0**

- Deletar `src/Providers/EventDispatcher.php`.
- Verificar referencias remanescentes em `Application` e service providers.

---

### [ITEM-008] Class: `PivotPHP\Core\Providers\ListenerProvider`

| Property | Value |
|---|---|
| File | `src/Providers/ListenerProvider.php` |
| Substitute | `PivotPHP\Core\Events\ListenerProvider` |
| Deprecation | v2.1.0 |
| Removal | v3.0.0 |
| Has `@deprecated`? | **Sim** (adicionado na refatoracao 2026-05-29) |
| Impact | Codigo interno; nenhuma referencia externa identificada |

**Contexto**

`Providers\ListenerProvider` foi depreciada junto com `Providers\EventDispatcher` como parte da reorganizacao do namespace `Events/`. `Events\ListenerProvider` e o substituto canonico e implementa `Psr\EventDispatcher\ListenerProviderInterface`.

**Migration for users**

```php
// Before:
use PivotPHP\Core\Providers\ListenerProvider;

// After:
use PivotPHP\Core\Events\ListenerProvider;
```

**Progresso (2026-05-29)**

- `@deprecated v2.1.0 Use \PivotPHP\Core\Events\ListenerProvider instead.` adicionado em `Providers\ListenerProvider`.
- `Events\ListenerProvider` confirmado como substituto em `src/Events/ListenerProvider.php`.
- Aguardando v3.0.0 para remocao de `src/Providers/ListenerProvider.php`.

**Actions for v3.0.0**

- Deletar `src/Providers/ListenerProvider.php`.
- Verificar referencias remanescentes em service providers e `Application`.

---

## Cross-Cutting Concerns

### Aliases files

- **`src/aliases.php`** — Contains routing aliases only. None of the 6 deprecated items referenced. No changes needed.
- **`src/aliases-performance-tools.php`** — Contains performance pool aliases. None of the 6 items referenced. The file itself is marked `@deprecated 2.2.0` and follows its own removal schedule.

### `composer.json` dependency audit

`"ext-session": "*"` is required by:
- `RateLimitMiddleware` (ITEM-004 — being removed)
- `CsrfMiddleware` (retained)
- `Utils.php` (retained)

After removing ITEM-004, `ext-session` remains required due to `CsrfMiddleware` and `Utils`. Do NOT remove it in v3.0.0 without a separate audit.

### `Application::$middlewareAliases`

```php
protected array $middlewareAliases = [
    'load-shedder' => \PivotPHP\Core\Middleware\LoadShedder::class,   // ITEM-003 — remove in v3.0.0
    'rate-limiter' => \PivotPHP\Core\Middleware\RateLimiter::class,   // retained
];
```

---

## Implementation Sequence

### v2.1.0 (Deprecation)

Execute in this order to respect cross-dependencies:

1. **ITEM-006** — Criar `Logging\PsrLogger`, atualizar `LoggingServiceProvider`, deprecar `Providers\Logger`. **Concluido (2026-05-29).**
2. **ITEM-007** — Deprecar `Providers\EventDispatcher`; atualizar `Events\EventDispatcher` para PSR-14. **Concluido (2026-05-29).**
3. **ITEM-008** — Deprecar `Providers\ListenerProvider`; confirmar `Events\ListenerProvider` como substituto. **Concluido (2026-05-29).**
4. **ITEM-005** — Adicionar `@deprecated` + `trigger_error()` nos tres metodos `Str`. **Concluido (2026-05-29).**
5. **ITEM-004** — Deprecar `RateLimitMiddleware`. Corrigir FQN errado em `RateLimitMiddlewareTestPsr15.php`. **Concluido (2026-05-29).**
6. **ITEM-003** — Deprecar `LoadShedder`. Marcar alias `'load-shedder'` como deprecated em `Application`. **Concluido (2026-05-29).**
7. **ITEM-002** — Adicionar `trigger_error()` em `getIp()`. Corrigir `RateLimiter.php:68`. **Concluido (2026-05-29).**
8. **ITEM-001** — Deprecar `Core\Container` (menor risco — zero chamadores em producao). **Concluido (2026-05-29).**

### v3.0.0 (Removal)

Execute in reverse dependency order:

1. **ITEM-002** — Remover `getIp()` (confirmar que `RateLimiter.php` ja foi atualizado).
2. **ITEM-004** — Remover `RateLimitMiddleware`. Atualizar arquivos de teste e script de validacao.
3. **ITEM-003** — Remover `LoadShedder`. Remover `'load-shedder'` de `Application::$middlewareAliases`.
4. **ITEM-005** — Remover os tres metodos de `Str`. Atualizar arquivo de teste.
5. **ITEM-006** — Remover `Providers\Logger`. Avaliar remocao de `FileHandler` e `LogHandlerInterface`.
6. **ITEM-007** — Remover `Providers\EventDispatcher`.
7. **ITEM-008** — Remover `Providers\ListenerProvider`.
8. **ITEM-001** — Remover `Core\Container`. Deletar `tests/Core/ContainerTest.php`.
