# PivotPHP v2.2.1 - Framework Overview

**Version:** 2.2.1 (Patch)
**Release Date:** 2026-10-09
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v2.2.1 is a **patch release** that fixes a static-file-serving regression introduced
when `StaticFileManager` was decoupled into `pivotphp/core-routing`, plus PSR-12 style
violations in test files. No public-API change.

### Release Highlights

- ✅ **`Application::staticFiles()` fixed** — removed the stale `$this` argument passed to
  `StaticFileManager::registerDirectory()`, whose signature changed after the decoupling.
- ✅ **PSR-12** — multi-line function-call violations in 4 test files corrected.
- ✅ **PHPStan Level 9** — back to zero errors (117 files).

---

## 📊 Technical Metrics

| Metric | v2.2.1 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` (dual support) |
| **PSR-15** | Compliant |
| **PHPStan** | Level 9 (0 errors) |
| **Coding style** | PSR-12 |
| **Tests** | 1093 CI + 34 integration |

### Performance

No performance change — this patch only corrects a broken static-file call and test-file
formatting.

---

## 🔧 Compatibility

Fully backward compatible with v2.2.0. No breaking changes.

## 📚 Related Documentation

- `CHANGELOG.md` — detailed change log.
- `docs/releases/FRAMEWORK_OVERVIEW_v2.2.0.md` — the 2.2.0 (Route Syntax & DX Edition) overview.
