# PivotPHP v4.0.0 - Framework Overview

**Version:** 4.0.0 (Major)
**Release Date:** 2026-10-10
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP 4.0 splits the framework into focused packages. The core keeps the application, the PSR-15
pipeline and the integration with the router; each other responsibility has its own package:

| Package | Responsibility |
|---|---|
| `pivotphp/http` ^1.0 | PSR-7/PSR-17 messages (nyholm/psr7), `ExpressRequest`/`ExpressResponse`, body parsing, SAPI emitter |
| `pivotphp/core-routing` ^2.2 | Route registration, compilation and matching; groups; static files |
| `pivotphp/security` ^1.0 | CORS, security headers, CSRF, JWT (verify/issue), rate limiting, trusted proxies |

### Highlights

- 🔌 **PSR-7/PSR-15 end to end** — the own PSR-7 implementation and the hybrid `Request`/`Response`
  were replaced by `pivotphp/http`.
- 🛡️ **Security as a package** — native security middlewares replaced by `pivotphp/security`, which
  validates configuration at boot and fails closed.
- 🧹 **Correctness over optimization** — removed the PHP-FPM-ineffective `RateLimiter` (security
  flaw), the unsafe `CacheMiddleware`, `ErrorMiddleware`, never-loaded aliases and unused caches.
- 🐛 **Fixes** — callable `$next($request)`, single execution of the pipeline, `400` for malformed
  JSON, `HttpException` headers, `405`/`OPTIONS`/`HEAD` semantics, hooks/listeners registered before
  boot, no global handler changes in `handle()`.
- ✅ **Verified examples** — every example runs under `php -S` in the test suite.

## Upgrading

See [MIGRATION_GUIDE.md](../MIGRATION_GUIDE.md) and the [CHANGELOG](../../CHANGELOG.md).
