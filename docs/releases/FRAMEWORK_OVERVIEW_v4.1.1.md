# PivotPHP v4.1.1 - Framework Overview

**Version:** 4.1.1 (Patch)
**Release Date:** 2026-10-10
**PHP Requirements:** 8.1+

## Summary

Patch release on the 4.1 line fixing parameter binding in the database layer.

### Fixed

- **`Database` binds parameters with their native PDO type** (SPEC-104):
  `select`/`selectOne`/`insert`/`update`/`delete`/`statement` used `PDOStatement::execute($bindings)`,
  which bound every value as `PARAM_STR`. Now `int`/`bool`/`null` bind as `PARAM_INT`/`PARAM_BOOL`/`PARAM_NULL`.
  This fixes `LIMIT ?`/`OFFSET ?` on strict drivers (PostgreSQL rejects a text-typed `LIMIT`) and avoids
  an opaque `SQLSTATE[HY000]: General error: 20 datatype mismatch` for out-of-range values.

No API changes. See the [CHANGELOG](../../CHANGELOG.md) and
[FRAMEWORK_OVERVIEW_v4.1.0.md](FRAMEWORK_OVERVIEW_v4.1.0.md).
