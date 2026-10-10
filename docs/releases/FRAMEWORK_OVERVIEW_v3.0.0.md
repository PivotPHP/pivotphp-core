# PivotPHP v3.0.0 - Framework Overview

**Version:** 3.0.0 (Major)
**Release Date:** 2026-10-09
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v3.0.0 is a **cleanup release**: it removes all deprecated code, backward-compatibility
aliases, and "premature optimization" components that brought no real gain. The framework now
does one thing well — HTTP + routing + PSR-7/PSR-15 — with a lean, simple codebase.

### Release Highlights

- 🗑️ **Deprecated code removed** — `Core\Container`, `LoadShedder`, `RateLimitMiddleware`,
  `Providers\Logger`, `Providers\EventDispatcher`, `Providers\ListenerProvider`,
  `Request::getIp()`, `Str::startsWith/endsWith/contains`.
- ⚡ **Premature optimization removed** — JSON pooling (`JsonBufferPool`), PSR-7 pooling
  (`Psr7Pool` e afins), `PerformanceMode`, `MemoryManager`, `OptimizedHttpFactory`,
  `MiddlewarePipelineCompiler`, `SerializationCache` — todos mais lentos ou sem uso real.
- 🔗 **Compatibility aliases removed** — `PivotPHP\Core\Routing\*` (use `PivotPHP\Routing\*`)
  e `PivotPHP\Core\Application` (use `PivotPHP\Core\Core\Application`).
- ➕ **`Database::exec()`** — execução de SQL multi-statement (schema/migrações).

---

## 📊 Technical Metrics

| Metric | v3.0.0 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` |
| **PSR-15** | middleware HTTP |
| **PSR-3** | `Logging\PsrLogger` |
| **PSR-14** | `Events\EventDispatcher` |
| **PHPStan** | Level 9 |
| **Coding style** | PSR-12 |
| **Tests** | 805 CI + 24 integração |

---

## 🔧 Breaking Changes

### Classes removed (use the substitute)

| Removed | Substitute |
|---|---|
| `PivotPHP\Core\Core\Container` | `PivotPHP\Core\Providers\Container` |
| `PivotPHP\Core\Middleware\LoadShedder` | `PivotPHP\Core\Middleware\RateLimiter` |
| `PivotPHP\Core\Middleware\Performance\RateLimitMiddleware` | `PivotPHP\Core\Middleware\RateLimiter` |
| `PivotPHP\Core\Providers\Logger` | `PivotPHP\Core\Logging\PsrLogger` |
| `PivotPHP\Core\Providers\EventDispatcher` | `PivotPHP\Core\Events\EventDispatcher` |
| `PivotPHP\Core\Providers\ListenerProvider` | `PivotPHP\Core\Events\ListenerProvider` |
| `PivotPHP\Core\Routing\*` (aliases) | `PivotPHP\Routing\*` |
| `PivotPHP\Core\Application` (alias) | `PivotPHP\Core\Core\Application` |

### Methods removed

| Method | Substitute |
|---|---|
| `Request::getIp()` | `Request::ip()` |
| `Request::setPsr7Pool()` | (removed — no-op) |
| `Response::setPsr7Pool()` | (removed — no-op) |
| `Str::startsWith()` | `str_starts_with()` |
| `Str::endsWith()` | `str_ends_with()` |
| `Str::contains()` | `str_contains()` |

---

## 📚 Related Documentation

- `CHANGELOG.md`.
- `docs/technical/DEPRECATION_AND_REMOVAL_PLAN.md` — the removal plan executed here.
- `docs/releases/FRAMEWORK_OVERVIEW_v2.3.4.md` — previous overview.
