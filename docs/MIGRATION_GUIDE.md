# PivotPHP Core - Migration Guide

## 📋 Current Migration Documentation

**For detailed migration instructions, please refer to the official release documentation:**

### 🔄 Latest Version: v2.0.0 ⚠️ BREAKING RELEASE
**[Complete Migration Guide →](releases/v2.0.0/MIGRATION_GUIDE_v2.0.0.md)**

**Migration highlights:**
- **🗑️ Legacy Cleanup**: 18% code reduction (11,871 lines removed)
- **📦 Namespace Modernization**: 110 legacy aliases removed
- **🚀 Performance**: 59% fewer aliases to autoload
- **⚠️ Breaking Changes**: Required namespace updates for middleware
- **✅ Zero Regressions**: All 5,548 tests passing (100%)

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
