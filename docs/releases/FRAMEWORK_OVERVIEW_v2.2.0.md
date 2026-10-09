# PivotPHP v2.2.0 - Framework Overview

**Version:** 2.2.0 (Route Syntax & DX Edition)
**Release Date:** 2026-10-08
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v2.2.0 is a **minor release** focused on developer experience and routing
flexibility. It adds brace-delimited route parameters (`{id}`) as an alternative to the
colon syntax, resolves instance-method array callables through the DI container, introduces
the Express-style `Request::body()` accessor, and adds SQLite support to the `Database`
wrapper — while keeping full backward compatibility.

> This release also ships the PSR-7 2.0 compatibility fix that was previously recorded
> under `[2.1.1]` but never tagged.

### Release Highlights

- ✅ **Brace route parameters** (`{id}` / `{id<constraint>}`) — equivalent to `:id`.
- ✅ **Array callables `[Classe::class, 'métodoDeInstância']`** resolved via DI container.
- ✅ **`Request::body()`** — Express-style body accessor.
- ✅ **SQLite support** in `Database::connect()`.
- ✅ **`PivotPHP\Core\Application`** backward-compatibility alias.
- ✅ **PSR-7 2.0** (`psr/http-message` `^1.1|^2.0`) dual support (carried from 2.1.1).

---

## 🏗️ What Changed

### 1. Route Parameters — `{id}` syntax

Routes now accept both `:id` and `{id}` (with optional constraint `{id<\d+>}`), backed by
the same constraint/shortcut/ReDoS validation in the routing engine. Requires
`pivotphp/core-routing` `^1.2`.

```php
$app->get('/users/:id', fn($req, $res) => $res->json(['id' => $req->param('id')]));
$app->get('/books/{isbn}', fn($req, $res) => $res->json(['isbn' => $req->param('isbn')]));
```

### 2. Array Callables with Instance Methods

`[UserController::class, 'index']` now resolves the instance through the DI container when
registered, and falls back to lazy instantiation otherwise (previously threw
`InvalidArgumentException` for non-static methods).

### 3. Request Body Access

`Request::body()` returns the parsed body as `stdClass` (alias of `getBodyAsStdClass()`).
`getBodyAsStdClass()` no longer discards the body on `DELETE` requests.

### 4. Database SQLite

`Database::connect()` supports `driver => 'sqlite'` (`database` = path or `:memory:`), in
addition to MySQL/MariaDB/PostgreSQL via host/port.

---

## 📊 Technical Metrics

| Metric | v2.2.0 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` (dual support) |
| **PSR-15** | Compliant |
| **PSR-3** | `Logging\PsrLogger` |
| **PSR-11** | `Providers\Container` |
| **PSR-14** | `Events\EventDispatcher` |
| **PHPStan** | Level 9 |
| **Coding style** | PSR-12 |
| **Tests** | 1092 CI + 34 integration |

### Performance

PivotPHP v2.2.0 maintains the performance characteristics of the v2.x line (object pooling,
JSON optimization, route caching). The new `{id}` syntax compiles to the same regex patterns
as `:id`, so there is no matching-time regression. The array-callable container resolution
happens once at route registration, not per request.

---

## 🔧 Compatibility

- **Backward compatible** with v2.x — `:id` syntax, static array callables, and instance
  array callables all keep working.
- `pivotphp/core-routing` bumped from `^1.0` to `^1.2`.

---

## 📚 Related Documentation

- `CHANGELOG.md` — detailed change log.
- `docs/technical/routing/SYNTAX_GUIDE.md` — route handler and parameter syntax.
- `docs/releases/FRAMEWORK_OVERVIEW_v2.1.1.md` — previous (PSR-7 fix) overview.
