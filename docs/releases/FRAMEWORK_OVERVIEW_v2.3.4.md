# PivotPHP v2.3.4 - Framework Overview

**Version:** 2.3.4 (Patch)
**Release Date:** 2026-10-09
**PHP Requirements:** 8.1+

---

## 🎯 Executive Summary

PivotPHP v2.3.4 changes the default log filename from `express-php.log` (the old project
name) to `pivotphp.log`.

### Release Highlights

- ✅ **Log filename** — default agora é `pivotphp.log` (SPEC-019).

### Files changed

- `src/Logging/PsrLogger.php` — nova constante `DEFAULT_LOG_FILENAME`.
- `src/Providers/LoggingServiceProvider.php` e `src/Providers/Logger.php` — usam o novo padrão.

---

## 📊 Technical Metrics| Metric | v2.3.4 |
|--------|--------|
| **PSR-7** | `psr/http-message` `^1.1\|^2.0` |
| **PSR-3** | `Logging\PsrLogger` |
| **PHPStan** | Level 9 |
| **Coding style** | PSR-12 |
| **Tests** | 1116 CI + 46 integration |

### Performance

No performance change.

---

## 🔧 Compatibility

Backward compatible. Only the default log filename changed; `LOG_PATH` overrides it. Who
monitors the old file (logrotate, collectors) should set `LOG_PATH` or adjust the path.

## 📚 Related Documentation

- `CHANGELOG.md`.
- `docs/releases/FRAMEWORK_OVERVIEW_v2.3.3.md` — previous overview.
