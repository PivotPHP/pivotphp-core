# PivotPHP Core - Migration Guide

## 📋 Current Migration Documentation

**For detailed migration instructions, please refer to the official release documentation:**

### 🔄 Latest Breaking Release: v2.0.0 ⚠️
**[Complete Migration Guide →](releases/v2.0.0/MIGRATION_GUIDE_v2.0.0.md)**

**Migration highlights:**
- **🗑️ Legacy Cleanup**: 18% code reduction (11,871 lines removed)
- **📦 Namespace Modernization**: 110 legacy aliases removed
- **🚀 Performance**: 59% fewer aliases to autoload
- **⚠️ Breaking Changes**: Required namespace updates for middleware
- **✅ Zero Regressions**: 5,548 tests passing at the time of the v2.0.0 release (100%) — the
  suite has changed size since; see `CHANGELOG.md` for the current count.

### ⚠️ Current version is v2.1.1, not v2.0.0

This guide (and the version-specific guides linked below) only covers up to v2.0.0. Two more
releases shipped after it, **without further breaking changes** to public APIs — they don't
have dedicated migration guides because there's nothing to migrate, but you should still be
aware of them:

- **v2.1.0** — fixed response double-emission, pooled-object data leaks in async runtimes
  (Swoole/ReactPHP/FrankenPHP), and started a deprecation cycle (`Core\Container`,
  `Middleware\LoadShedder`/`RateLimitMiddleware`, `Request::getIp()`,
  `Providers\Logger`/`EventDispatcher` — all still work via deprecation aliases, removal
  planned for v3.0.0).
- **v2.1.1** — fixed a real incompatibility with `psr/http-message` `^2.0` that could cause a
  fatal error on every request if Composer resolved to that version (despite
  `composer.json` already declaring support for it since 2.1.0).

See [`CHANGELOG.md`](../CHANGELOG.md) for the full, authoritative list of changes in both
releases.

### 📚 Version-Specific Migration Guides

| From Version | Migration Guide | Effort Level |
|--------------|----------------|--------------|
| **v1.x → v2.0.0** | [v2.0.0 Migration Guide](releases/v2.0.0/MIGRATION_GUIDE_v2.0.0.md) | **Medium** ⚠️ BREAKING |
| **v1.1.3** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Low** (mostly optional) |
| **v1.1.2** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Low** (infrastructure only) |
| **v1.1.1** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Low** (backward compatible) |
| **v1.1.0** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Medium** (multiple versions) |
| **v1.0.x** | [v1.1.4 Migration Guide](releases/v1.1.4/MIGRATION_GUIDE.md) | **Medium** (feature changes) |

### 🎯 Quick Migration Checklist

#### ⚠️ Required Actions (v2.0.0) - BREAKING CHANGES:
- [ ] **Update PSR-15 middleware imports** (8 classes - see migration guide)
- [ ] **Remove "Simple*" prefixes** (7 classes - PerformanceMode, LoadShedder, etc.)
- [ ] **Replace OpenApiExporter** with ApiDocumentationMiddleware
- [ ] **Update DynamicPoolManager** → PoolManager
- [ ] **Run tests**: `composer test`
- [ ] **Regenerate autoloader**: `composer dump-autoload`

#### ✅ Recommended Actions (v2.0.0):
- [ ] **Use migration script** (provided in v2.0.0 migration guide)
- [ ] **Review cleanup analysis** ([docs/v2.0.0-cleanup-analysis.md](v2.0.0-cleanup-analysis.md))
- [ ] **Update IDE configuration** for new namespaces
- [ ] **Review updated examples** in `examples/` directory

### 📖 Additional Resources

- **[Versioning Guide](VERSIONING_GUIDE.md)** - Complete semantic versioning guidance
- **[Framework Overview v1.1.4](releases/FRAMEWORK_OVERVIEW_v1.1.4.md)** - Complete release overview
- **[Release Notes v1.1.4](releases/v1.1.4/RELEASE_NOTES.md)** - Detailed release notes
- **[Changelog](../CHANGELOG.md)** - Complete version history

### 🆘 Migration Support

If you encounter migration issues:

1. **Check the specific migration guide** for your version
2. **Review error messages** (now in Portuguese for clarity)
3. **Consult the troubleshooting section** in the migration guide
5. **Create GitHub issue**: https://github.com/PivotPHP/pivotphp-core/issues

---

**Note**: This general migration guide has been replaced by version-specific documentation for better accuracy and detail. Please use the appropriate version-specific guide above.
