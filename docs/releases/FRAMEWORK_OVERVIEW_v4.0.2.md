# PivotPHP v4.0.2 - Framework Overview

**Version:** 4.0.2 (Patch)
**Release Date:** 2026-10-10
**PHP Requirements:** 8.1+

## Summary

Patch release on the 4.0 line focused on security and data integrity.

- **`Database::transaction()` rolls back on any `Throwable`** (SPEC-073): an `Error` thrown by the callback
  no longer leaves the transaction open.
- **Lifecycle listeners are isolated** (SPEC-085): an exception from a `RequestReceived`/`ResponseSent`
  listener is logged and no longer escapes `Application::handle()`.
- **Per-driver DSN** (SPEC-074): PostgreSQL uses port 5432 by default and no `charset` in the DSN; aliases
  `postgres`/`postgresql` and `mariadb`; unsupported drivers fail with `InvalidArgumentException`.
- Requires `pivotphp/core-routing` 2.2.3 (SPEC-062: static files skip dotfiles and symlinks outside the root).

No API changes. See [FRAMEWORK_OVERVIEW_v4.0.0.md](FRAMEWORK_OVERVIEW_v4.0.0.md) for the 4.0 overview and the
[CHANGELOG](../../CHANGELOG.md).
