# PivotPHP v2.3.1 - Framework Overview

**Version:** 2.3.1 (Patch)
**Release Date:** 2026-10-09
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v2.3.1 fixes the middleware execution pipeline: PSR-15 middlewares now work with
`$app->use()`, global middlewares run before route resolution (including 404/preflight),
route/group middlewares are actually executed (closing an authentication-bypass), controllers
are resolved per request, and `ApiDocumentationMiddleware` serves `/docs` and `/swagger`.

### Release Highlights

- ✅ **PSR-15 middlewares via `$app->use()`** (SPEC-023).
- ✅ **Global middlewares before routing** (SPEC-040).
- ✅ **Route/group middlewares executed** — fixes auth bypass (SPEC-038).
- ✅ **Controller resolved per request** — respects `bind`/`singleton` (SPEC-041).
- ✅ **`ApiDocumentationMiddleware`** serving `/docs` + `/swagger` (SPEC-022).

---

## 📊 Technical Metrics

| Metric | v2.3.1 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` |
| **PSR-15** | Compliant (middlewares adaptáveis a `$app->use()`) |
| **PHPStan** | Level 9 |
| **Coding style** | PSR-12 |
| **Tests** | 1091 CI + 41 integration |

### Performance

No performance change — correctness fixes to the middleware pipeline.

---

## 🔧 Compatibility

Backward compatible with v2.3.0. The observable behavior change is that route/group middlewares
now execute (previously silently ignored).

## 📚 Related Documentation

- `CHANGELOG.md`.
- `docs/releases/FRAMEWORK_OVERVIEW_v2.3.0.md` — previous overview.
