# PivotPHP v2.3.0 - Framework Overview

**Version:** 2.3.0 (Simplification Edition)
**Release Date:** 2026-10-09
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v2.3.0 removes the "optimization" layers that added complexity without real benefit
under PHP-FPM (the most common PHP deployment): JSON object pooling, PSR-7 object pooling, and
several unused optimization components. The result is a simpler, faster hot path and a smaller
maintenance surface, aligned with the project's "Simplicidade sobre Otimização Prematura"
principle.

### Release Highlights

- ✅ **`Response::json()` usa `json_encode` direto** — o `JsonBufferPool` era sempre mais lento
  (+5% a +78%) e não trazia ganho no modelo processo-por-requisição.
- ✅ **PSR-7 sem pooling** — `Request`/`Response`/`Message` criam objetos direto; fim da
  retenção estática de corpo/headers de requisições anteriores.
- ✅ **Remoção (depreciação) de ~15 componentes de otimização sem uso** — preparando a v3.0.0.
- ✅ **Sem mudança de API pública documentada** — o caminho injetável `JsonOptimizerInterface`
  permanece.

---

## 📊 Technical Metrics

| Metric | v2.3.0 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` (dual support) |
| **PSR-15** | Compliant |
| **PSR-3** | `Logging\PsrLogger` |
| **PSR-11** | `Providers\Container` |
| **PHPStan** | Level 9 |
| **Coding style** | PSR-12 |
| **Tests** | 1093 CI + 34 integration |

### Performance

The hot path (`Response::json()`, header handling) no longer pays the pooling overhead — these
operations are now plain `json_encode`/`strtolower`. The removed pools were measured slower than
their plain alternatives and provided no reuse in a PHP-FPM request lifecycle.

---

## 🔧 Compatibility

- Backward compatible with v2.2.x — no documented public API change.
- Deprecated classes (pooling/optimization) remain available until v3.0.0, when they are removed.

## 📚 Related Documentation

- `docs/technical/DEPRECATION_AND_REMOVAL_PLAN.md` — ITEM-009/010/011.
- `CHANGELOG.md` — detailed change log.
- `docs/releases/FRAMEWORK_OVERVIEW_v2.2.0.md` — previous overview.
