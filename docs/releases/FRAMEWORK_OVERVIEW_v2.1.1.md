# PivotPHP v2.1.1 - Framework Overview

**Version:** 2.1.1 (PSR-7 2.0 Compatibility Fix)
**Release Date:** 2026-07-15
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v2.1.1 is a **patch release** that fixes a real incompatibility with
`psr/http-message` 2.0, which `composer.json` had been declaring support for
(`"psr/http-message": "^1.1|^2.0"`) since v2.1.0 without the implementation
actually honoring it. PSR-7 2.0 adds strict return type declarations to the
interface methods, so whenever Composer resolved `psr/http-message` to `^2.0`,
every request hit a fatal `Declaration must be compatible` error.

### Release Highlights

- ✅ **Real PSR-7 2.0 support** — `^1.1` and `^2.0` both work again.
- ✅ **~46 method signatures retyped** across 8 PSR-7 classes.
- ✅ **Pooled-object data leaks fixed** (concurrent/async runtimes: Swoole, ReactPHP, FrankenPHP).
- ✅ **Single response-emission point** (`Application::run()`).
- ✅ **No public-API change** for documented usage — pure compatibility restoration.
- ✅ **1114 tests passing** (1083 CI + 31 integration) at PHPStan level 9.

---

## 🏗️ What Changed

### 1. PSR-7 2.0 Return-Type Compatibility

The following files had their method signatures retyped against the real PSR-7 2.0
interface sources (`php-fig/http-message`, tag `2.0`):

- `Http/Psr7/Message.php`
- `Http/Psr7/Request.php`
- `Http/Psr7/Response.php`
- `Http/Psr7/ServerRequest.php`
- `Http/Psr7/Stream.php`
- `Http/Psr7/Uri.php`
- `Http/Psr7/UploadedFile.php`
- `Http/Response.php` (the Express.js/PSR-7 hybrid class)

### 2. Response Emission & Pool Safety (carried from v2.1.0)

- `json()`/`text()`/`html()` no longer auto-emit; `Application::run()` is the single,
  guaranteed emission point (guarded by `isSent()`).
- `Psr7Pool::resetServerRequest()` now applies `$serverParams` (no more leaking
  `REMOTE_ADDR`/`HTTPS`/auth data between pooled requests).
- `Psr7Pool::resetStream()` no longer reuses streams without `truncate()`.
- `CustomHeaderCollection` no longer discards real headers fetched from
  `getallheaders()`.

### 3. Deprecation Cycle (removal planned for v3.0.0)

- `Core\Container` → `Providers\Container`
- `Middleware\LoadShedder` / `Middleware\Performance\RateLimitMiddleware` → `Middleware\RateLimiter`
- `Request::getIp()` → `Request::ip()`
- `Providers\Logger` → `Logging\PsrLogger`
- `Providers\EventDispatcher` → `Events\EventDispatcher`

---

## 📊 Technical Metrics

| Metric | v2.1.1 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` (dual support) |
| **PSR-15** | Compliant |
| **PSR-3** | `Logging\PsrLogger` |
| **PSR-11** | `Providers\Container` |
| **PSR-14** | `Events\EventDispatcher` |
| **PHPStan** | Level 9 |
| **Coding style** | PSR-12 |
| **Tests** | 1114 (1083 CI + 31 integration) |

### Performance

PivotPHP v2.1.1 maintains the performance characteristics of the v2.x line. The
object-pooling and JSON-optimization systems continue to operate with the same
thresholds and reuse rates as v2.0.0 — no performance regression was introduced by
the return-type corrections (which are compile-time declarations, not runtime
hot-path changes).

---

## 🔧 Compatibility

- **Backward compatible** with all documented v2.x usage via deprecation aliases
  through the v2.x line. Deprecated APIs are removed in v3.0.0.
- The only affected caller is undocumented direct use of `Response::getBody()` in
  test mode expecting a raw string — use `getBodyAsString()` instead.

---

## 📚 Related Documentation

- `docs/technical/INCONSISTENCIES_REPORT.md` — full architectural review.
- `CHANGELOG.md` — detailed change log for v2.1.1 and earlier.
- `docs/releases/FRAMEWORK_OVERVIEW_v2.0.0.md` — previous major release overview.
