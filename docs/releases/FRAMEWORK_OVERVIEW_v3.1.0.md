# PivotPHP v3.1.0 - Framework Overview

**Version:** 3.1.0 (Minor)
**Release Date:** 2026-10-10
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v3.1.0 adopts the **simplified core-routing 2.0** (no cache/plugins/statistics — the
router now does one thing well: register, compile and match) and isolates the static router
state between `Application` instances.

### Release Highlights

- 🔗 **`pivotphp/core-routing` `^2.0`** — dependency bumped from `^1.2`; the router keeps the
  same consumer API (`identify()`, `getRoutes()`, verbs) but drops the removed cache/plugin
  surface.
- 🧼 **Router state isolation** — `Application::registerCoreServices()` now calls
  `Router::clear()` on boot, so multiple `Application` instances in the same process no longer
  share routes (SPEC-076 mitigation).

---

## 📊 Technical Metrics

| Metric | v3.1.0 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` |
| **PSR-15** | middleware HTTP |
| **PSR-3** | `Logging\PsrLogger` |
| **PSR-14** | `Events\EventDispatcher` |
| **PHPStan** | Level 9 |
| **Coding style** | PSR-12 |
| **core-routing** | `^2.0` (simplified) |
| **Tests** | 805 CI + 24 integração |

---

## 🔧 Changes

### Dependency

| Package | Before | After |
|---|---|---|
| `pivotphp/core-routing` | `^1.2` | `^2.0` |

### Behavior

- `Router::clear()` is now called during service registration, isolating static route state
  between `Application` instances in the same process (long-running workers, tests).

---

## 📚 Related Documentation

- `CHANGELOG.md`.
- `docs/releases/FRAMEWORK_OVERVIEW_v3.0.0.md` — previous overview.
- [SPEC-086 — Simplificar o core-routing](https://github.com/PivotPHP/pivotphp-specs/blob/main/SPECS/SPEC-086-simplify-router.md).
- [SPEC-076 — Router global static state](https://github.com/PivotPHP/pivotphp-specs/blob/main/SPECS/SPEC-076-router-global-static-state.md).
