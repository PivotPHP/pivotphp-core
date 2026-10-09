# PivotPHP v2.3.3 - Framework Overview

**Version:** 2.3.3 (Patch)
**Release Date:** 2026-10-09
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v2.3.3 fixes route parameter handling: values are URL-decoded and only canonical
integers are cast to `int` (CEPs, floats and huge integers stay strings).

### Release Highlights

- ✅ **Route params** — `rawurldecode()` + inteiros canônicos (SPEC-042).

### Before / After

| Entrada | v2.3.2 | v2.3.3 |
|---|---|---|
| `01310100` | `1310100` (int) | `"01310100"` (string) |
| `1.5` | `1` (int) | `"1.5"` (string) |
| `99999999999999999999` | overflow int | string |
| `%2012` | `"%2012"` | `" 12"` (decodificado) |
| `42` | `42` (int) | `42` (int) |

---

## 📊 Technical Metrics

| Metric | v2.3.3 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` |
| **PHPStan** | Level 9 |
| **Coding style** | PSR-12 |
| **Tests** | 1113 CI + 46 integration |

### Performance

No performance change.

---

## 🔧 Compatibility

Backward compatible for canonical integer parameters (unchanged); other values now remain
strings instead of being cast.

## 📚 Related Documentation

- `CHANGELOG.md`.
- `docs/releases/FRAMEWORK_OVERVIEW_v2.3.2.md` — previous overview.
