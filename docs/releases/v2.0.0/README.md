# PivotPHP v2.0.0 Documentation

**Version:** 2.0.0 (Legacy Cleanup Edition)
**Release Date:** January 2025
**Status:** ✅ Released

---

## 📚 Documentation Index

### Getting Started

1. **[RELEASE_NOTES.md](RELEASE_NOTES.md)** - Complete release notes
   - Overview and key statistics
   - Breaking changes summary
   - Migration checklist
   - Troubleshooting guide

2. **[MIGRATION_GUIDE_v2.0.0.md](MIGRATION_GUIDE_v2.0.0.md)** - Comprehensive migration guide
   - Detailed breaking changes
   - Automated migration script
   - Step-by-step migration process
   - Common issues and solutions

3. **[FRAMEWORK_OVERVIEW.md](FRAMEWORK_OVERVIEW.md)** - Technical deep dive
   - Architecture changes
   - Performance analysis
   - Design principles
   - Future roadmap

---

## 🎯 Quick Links

### For Upgrading Applications

- **Start Here:** [MIGRATION_GUIDE_v2.0.0.md](MIGRATION_GUIDE_v2.0.0.md#quick-start)
- **Automated Script:** [MIGRATION_GUIDE_v2.0.0.md#automated-migration-script](MIGRATION_GUIDE_v2.0.0.md#automated-migration-script)
- **Breaking Changes:** [MIGRATION_GUIDE_v2.0.0.md#breaking-changes](MIGRATION_GUIDE_v2.0.0.md#breaking-changes)
- **Troubleshooting:** [RELEASE_NOTES.md#troubleshooting](RELEASE_NOTES.md#troubleshooting)

### For Understanding Changes

- **Code Metrics:** [FRAMEWORK_OVERVIEW.md#technical-metrics](FRAMEWORK_OVERVIEW.md#technical-metrics)
- **Architecture:** [FRAMEWORK_OVERVIEW.md#architecture-changes](FRAMEWORK_OVERVIEW.md#architecture-changes)
- **Performance Impact:** [FRAMEWORK_OVERVIEW.md#performance-analysis](FRAMEWORK_OVERVIEW.md#performance-analysis)
- **Design Decisions:** [FRAMEWORK_OVERVIEW.md#design-principles](FRAMEWORK_OVERVIEW.md#design-principles)

### For Planning Migration

- **Risk Assessment:** [FRAMEWORK_OVERVIEW.md#migration-impact-analysis](FRAMEWORK_OVERVIEW.md#migration-impact-analysis)
- **Timeline:** [FRAMEWORK_OVERVIEW.md#recommended-timeline](FRAMEWORK_OVERVIEW.md#recommended-timeline)
- **Adoption Strategy:** [FRAMEWORK_OVERVIEW.md#adoption-strategy](FRAMEWORK_OVERVIEW.md#adoption-strategy)

---

## 📊 Release Summary

### What Changed

- ✅ **18% Code Reduction** - Removed 11,871 lines
- ✅ **110 Aliases Eliminated** - Clean namespaces
- ✅ **30 Files Removed** - Deprecated components
- ✅ **100% Test Coverage** - 5,548 tests passing
- ✅ **59% Faster Autoload** - Performance improvement
- ✅ **Routing Externalized** - Moved to `pivotphp/core-routing` package

### Breaking Changes Categories

1. **PSR-15 Middleware Namespaces** - `Http\Psr15\Middleware\*` → `Middleware\*`
2. **Simple* Prefix Removal** - `SimpleRateLimitMiddleware` → `RateLimitMiddleware`
3. **OpenAPI System** - `OpenApiExporter` → `ApiDocumentationMiddleware`
4. **Performance Components** - `DynamicPoolManager` removed
5. **Traffic Classification** - `SimpleTrafficClassifier` removed
6. **Legacy v1.1.x Aliases** - All 74 aliases removed
7. **Modular Routing Phase 1** - Routing extracted to external package (backward compatible)

### Migration Effort Estimate

| Application Type | Complexity | Time Required | Automation Level |
|-----------------|------------|---------------|------------------|
| **Basic API** | Low | 15-30 min | 95% automated |
| **Standard App** | Medium | 1-2 hours | 80% automated |
| **Complex System** | High | 4-8 hours | 60% automated |

---

## 🚀 Getting Started

### 1. Review Documentation

Read through the migration guide to understand what's changing:

```bash
# Read migration guide
cat docs/releases/v2.0.0/MIGRATION_GUIDE_v2.0.0.md

# Check your codebase for deprecated patterns
grep -r "use PivotPHP\\Core\\Http\\Psr15\\Middleware" src/
grep -r "SimpleRateLimitMiddleware\|SimpleCsrfMiddleware" src/
grep -r "OpenApiExporter\|DynamicPoolManager" src/
```

### 2. Run Automated Migration

Use the provided migration script:

```bash
# Backup your code first!
git checkout -b feature/upgrade-v2.0.0

# Run automated migration
find src/ -type f -name "*.php" -exec sed -i \
  's/use PivotPHP\\Core\\Http\\Psr15\\Middleware\\/use PivotPHP\\Core\\Middleware\\/g' {} \;

# Test immediately
composer test
```

### 3. Manual Cleanup

Fix any edge cases the automation missed:

```bash
# Update OpenApiExporter usage
# Replace with ApiDocumentationMiddleware

# Update DynamicPoolManager
# Replace with ObjectPool

# Run tests after each change
composer test
```

### 4. Update Dependencies

```bash
# Update to v2.0.0
composer require pivotphp/core:^2.0

# Verify installation
composer show pivotphp/core
```

---

## 📖 Documentation Structure

```
docs/releases/v2.0.0/
├── README.md                      # This file (index)
├── RELEASE_NOTES.md               # Official release notes
├── MIGRATION_GUIDE_v2.0.0.md      # Detailed migration guide
└── FRAMEWORK_OVERVIEW.md          # Technical deep dive
```

### Document Purposes

**README.md (this file)**
- Navigation hub for v2.0.0 documentation
- Quick links to specific topics
- High-level release summary

**RELEASE_NOTES.md**
- Official release announcement
- Key changes and improvements
- Troubleshooting common issues
- Credits and acknowledgments

**MIGRATION_GUIDE_v2.0.0.md**
- Complete migration instructions
- Automated migration script
- Step-by-step process
- Edge case handling

**FRAMEWORK_OVERVIEW.md**
- Architecture analysis
- Performance benchmarks
- Design philosophy
- Future roadmap

---

## 🎯 Common Use Cases

### "I just want to upgrade quickly"

1. Read: [Quick Start](MIGRATION_GUIDE_v2.0.0.md#quick-start)
2. Run: [Automated Migration Script](MIGRATION_GUIDE_v2.0.0.md#automated-migration-script)
3. Test: `composer test`

### "I need to understand what changed"

1. Read: [Breaking Changes](MIGRATION_GUIDE_v2.0.0.md#breaking-changes)
2. Review: [Architecture Changes](FRAMEWORK_OVERVIEW.md#architecture-changes)
3. Check: [Impact Analysis](FRAMEWORK_OVERVIEW.md#migration-impact-analysis)

### "I'm having migration issues"

1. Check: [Troubleshooting Guide](RELEASE_NOTES.md#troubleshooting)
2. Review: [Common Issues](MIGRATION_GUIDE_v2.0.0.md#troubleshooting)
3. Search: [GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)

### "I want to plan migration timeline"

1. Read: [Migration Impact Analysis](FRAMEWORK_OVERVIEW.md#migration-impact-analysis)
2. Review: [Recommended Timeline](FRAMEWORK_OVERVIEW.md#recommended-timeline)
3. Plan: [Adoption Strategy](FRAMEWORK_OVERVIEW.md#adoption-strategy)

---

## ⚡ Key Decisions Summary

### Why Remove Aliases?

**Problem:** 110 aliases created confusion and autoload overhead

**Solution:** Eliminate all aliases, enforce single namespace per class

**Impact:** 59% faster autoloading, clearer documentation

### Why Breaking Changes?

**Problem:** Technical debt compounds over time

**Solution:** Use major version (v2.0) for clean break

**Impact:** Short-term migration effort, long-term maintainability

### Why Remove DynamicPoolManager?

**Problem:** Enterprise complexity inappropriate for educational microframework

**Solution:** Focus on simple `ObjectPool` for common cases

**Impact:** Simpler architecture, easier to understand

---

## 📈 Metrics at a Glance

### Code Quality

| Metric | v1.2.0 | v2.0.0 | Change |
|--------|--------|--------|--------|
| Lines of Code | 66,548 | 54,677 | -18% |
| Source Files | 187 | 157 | -30 files |
| Aliases | 110 | 0 | -100% |
| PHPStan Level | 9 | 9 | ✅ |
| Test Coverage | 100% | 100% | ✅ |

### Performance

| Metric | v1.2.0 | v2.0.0 | Change |
|--------|--------|--------|--------|
| Autoload Time | 15ms | 6ms | -59% |
| Memory | 1.61MB | 1.45MB | -10% |
| HTTP Throughput | 44k ops/s | 44k ops/s | ✅ |

---

## 🔗 External Resources

- **Main Repository:** [github.com/PivotPHP/pivotphp-core](https://github.com/PivotPHP/pivotphp-core)
- **Packagist:** [packagist.org/packages/pivotphp/core](https://packagist.org/packages/pivotphp/core)
- **Issue Tracker:** [GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)
- **Main Changelog:** [CHANGELOG.md](../../CHANGELOG.md)
- **Examples:** [examples/](../../examples/)

---

## 📞 Support

### Getting Help

1. **Documentation:** Read this documentation set thoroughly
2. **Examples:** Check [examples/](../../examples/) for working code
3. **Issues:** Search [existing issues](https://github.com/PivotPHP/pivotphp-core/issues)
4. **New Issue:** Open issue with [migration] tag

### Reporting Bugs

If you encounter issues during migration:

1. Check [Troubleshooting Guide](RELEASE_NOTES.md#troubleshooting)
2. Search [GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)
3. Open new issue with:
   - PHP version
   - PivotPHP version (before/after)
   - Error message
   - Minimal reproducible example

---

## 🎉 What's Next?

After successful migration to v2.0.0, you can:

- ✅ Enjoy cleaner, faster codebase
- ✅ Benefit from improved IDE autocomplete
- ✅ Build on stable v2.x foundation
- ✅ Prepare for v2.1.0 features (Q2 2025)

See [Future Roadmap](FRAMEWORK_OVERVIEW.md#future-roadmap) for upcoming features.

---

**Happy Migrating! 🚀**

*PivotPHP v2.0.0 - Simplicity through Elimination*
