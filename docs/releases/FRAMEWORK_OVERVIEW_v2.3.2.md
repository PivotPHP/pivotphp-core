# PivotPHP v2.3.2 - Framework Overview

**Version:** 2.3.2 (Patch)
**Release Date:** 2026-10-09
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v2.3.2 fixes header validation (CR/LF injection), request header mutators, and
`Response::json()` serialization correctness.

### Release Highlights

- ✅ **Header validation** — `withHeader`/`withAddedHeader` rejeitam CR/LF/NUL (SPEC-043).
- ✅ **Request header mutators** — `withHeader`/`withAddedHeader`/`withoutHeader` aplicam a mudança (SPEC-024).
- ✅ **`Response::json()`** — `JsonSerializable`/enums respeitados; falha de codificação vira erro, não `{}`/200 (SPEC-039).

---

## 📊 Technical Metrics

| Metric | v2.3.2 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` |
| **PHPStan** | Level 9 |
| **Coding style** | PSR-12 |
| **Tests** | 1106 CI + 46 integration |

### Performance

No performance change.

---

## 🔧 Compatibility

Backward compatible with v2.3.1, exceto que `Response::json()` agora lança exceção (500) em
falha de codificação (antes retornava `{}` com 200).

## 📚 Related Documentation

- `CHANGELOG.md`.
- `docs/releases/FRAMEWORK_OVERVIEW_v2.3.1.md` — previous overview.
