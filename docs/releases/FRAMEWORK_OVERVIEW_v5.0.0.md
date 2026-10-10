# PivotPHP v5.0.0 - Framework Overview

**Version:** 5.0.0 (Major)
**Release Date:** 2026-10-10
**PHP Requirements:** 8.1+

## Summary

Major release: the router keeps its state per instance (SPEC-076).

- **Isolated applications**: each `Application` owns its own `Router` (`pivotphp/core-routing` 3.0).
  Two applications in the same process no longer share routes, groups, route middlewares or static
  files.
- **Routing API on `Application`**: `get`, `post`, `put`, `delete`, `patch`, `options`, `head`, `any`,
  `match` and `group`, all accepting `$metadata` and route middlewares.
- **Router in the request**: `handle()` stores the application's router in the `Router::class` request
  attribute; `ApiDocumentationMiddleware` documents the routes of the application that handles the request.

## Breaking changes

Static calls on `PivotPHP\Routing\Router\Router` (`Router::get()`, `Router::group()`, `Router::clear()`)
no longer work. Register routes through the `Application`. See the "4.x → 5.0" section of the
[Migration Guide](../MIGRATION_GUIDE.md) and the [CHANGELOG](../../CHANGELOG.md).
