# PivotPHP v4.0.1 - Framework Overview

**Version:** 4.0.1 (Patch)
**Release Date:** 2026-10-10
**PHP Requirements:** 8.1+

## Summary

Patch release on the 4.0 line.

- **`.env` loaded before `config/`** (SPEC-101): configuration files that read `$_ENV`/`getenv()` now see
  the values from `.env`. Real environment variables keep precedence.
- Installs `pivotphp/core-routing` 2.2.2 (CI, `Router::use()` no longer changes route paths).

No API changes. See [FRAMEWORK_OVERVIEW_v4.0.0.md](FRAMEWORK_OVERVIEW_v4.0.0.md) for the 4.0 overview and the
[CHANGELOG](../../CHANGELOG.md).
