# PivotPHP Core v1.1.4 - Release Notes

**Release Date:** Janeiro 2025
**Type:** Infrastructure Optimization Release
**Breaking Changes:** None
**Migration Required:** Optional (recommended)

## 🎯 Release Highlights

PivotPHP Core v1.1.4 focuses on **infrastructure consolidation** and **developer experience optimization**. This release eliminates script duplication, implements automatic version detection, and streamlines the development workflow without affecting any core framework functionality.

## 🆕 New Features

### 📦 Automatic Version Management
- **VERSION File Integration:** All scripts now use a central VERSION file
- **Semantic Version Validation:** Enforces X.Y.Z format with strict validation
- **Version Bump Automation:** `scripts/release/version-bump.sh` with patch/minor/major support
- **Git Integration:** Automatic commit and tag creation for version changes

### 🔧 Consolidated Script Infrastructure
- **Shared Library:** `scripts/utils/version-utils.sh` with common functions
- **Single Quality Script:** `scripts/quality/quality-check.sh` consolidates all quality checks
- **Auto-Detection:** Project root and context detection from any directory
- **Error Handling:** Strict validation with clear Portuguese error messages

### 📚 Comprehensive Documentation
- **Versioning Guide:** `docs/VERSIONING_GUIDE.md` with 315 lines of detailed guidance
- **Script Documentation:** Updated `scripts/README.md` with categorized organization
- **Troubleshooting:** Common issues and solutions documentation

## 🔄 Infrastructure Changes

### ✅ Scripts Consolidated (10 removed):
- `quality-check-v114.sh` → Merged into `quality-check.sh`
- `validate_all_v114.sh` → Merged into `validate_all.sh`
- `quick-quality-check.sh` → Functionality integrated
- `simple_pre_release.sh` → Replaced by `prepare_release.sh`
- `quality-gate.sh` → Functionality integrated
- `quality-metrics.sh` → Functionality integrated
- `test-php-versions-quick.sh` → Replaced by `test-all-php-versions.sh`
- `ci-validation.sh` → Functionality integrated
- `setup-precommit.sh` → One-time setup script removed
- `adapt-psr7-v1.php` → Specific utility removed

### 🚀 GitHub Actions Updated:
- **Removed:** `quality-gate.yml` (duplicate functionality)
- **Updated:** `ci.yml` to use consolidated scripts
- **Updated:** `pre-release.yml` with automatic version detection
- **Fixed:** `release.yml` URLs from express-php to pivotphp-core

## 🛠️ Usage Changes

### New Commands Available:
```bash
# Version management
scripts/release/version-bump.sh patch    # 1.1.4 → 1.1.5
scripts/release/version-bump.sh minor    # 1.1.4 → 1.2.0
scripts/release/version-bump.sh major    # 1.1.4 → 2.0.0

# Quality validation (consolidated)
scripts/quality/quality-check.sh         # Replaces multiple scripts

# Release preparation (enhanced)
scripts/release/prepare_release.sh       # Auto-detects version
```

### Deprecated Commands (still work but not recommended):
- Individual quality scripts (use `quality-check.sh` instead)
- Hardcoded version scripts (all removed)

## 📊 Performance Impact

**No Performance Changes:** This release focuses on infrastructure only. All v1.1.3 performance characteristics are maintained:
- JSON Pooling: 161K ops/sec (small), 17K ops/sec (medium), 1.7K ops/sec (large)
- Framework Average: 40,476 ops/sec
- Object Pooling: 24,161 ops/sec

## 🔒 Security

### Enhanced Validation:
- **Strict VERSION file validation** prevents invalid version formats
- **Project context validation** ensures scripts run in correct environment
- **Error messages in Portuguese** reduce security through obscurity
- **No sensitive information** in error outputs

## 📋 Migration Guide

### ⚠️ Required Actions:
1. **Ensure VERSION file exists** in project root with format X.Y.Z
2. **Update any custom scripts** that referenced removed scripts
3. **Review CI/CD pipelines** using removed workflows

### ✅ Recommended Actions:
1. **Use consolidated scripts** for better maintenance
2. **Adopt version-bump.sh** for semantic versioning
3. **Read versioning guide** for best practices
4. **Update local workflows** to use new scripts

### 🔧 Breaking Change Mitigation:
- **Automatic aliases** maintain compatibility for most use cases
- **Gradual migration** - old methods still work during transition
- **Clear error messages** guide users to new commands

## 🧪 Testing

### Validation Performed:
- ✅ All 684 CI tests pass
- ✅ 131 integration tests pass
- ✅ PHPStan Level 9 with 0 errors
- ✅ PSR-12 100% compliance
- ✅ Cross-version PHP testing (8.1-8.4)
- ✅ Script functionality validation
- ✅ GitHub Actions workflow testing

### Test Coverage:
- **Core Framework:** Maintained at ≥30%
- **New Scripts:** 100% functionality tested
- **Integration:** Version detection and context validation tested
- **Error Handling:** All error scenarios validated

## 🐛 Bug Fixes

### Infrastructure Fixes:
- **Fixed:** GitHub Actions referencing non-existent scripts
- **Fixed:** Hardcoded paths in multiple scripts
- **Fixed:** Version inconsistencies across documentation
- **Fixed:** Repository URLs in release workflows
- **Fixed:** Duplicate functionality reducing maintenance burden

### Validation Improvements:
- **Improved:** Error messages now more descriptive and actionable
- **Improved:** Project context detection more robust
- **Improved:** Version format validation stricter and more reliable

## 🔮 Looking Forward

### v1.1.5 (Next Patch):
- Bug fixes based on community feedback
- Documentation improvements
- Minor script optimizations

### v1.2.0 (Next Minor):
- New features maintaining backward compatibility
- Additional middleware options
- Extended integrations

### Infrastructure Evolution:
- Continued focus on developer experience
- Further automation opportunities
- Community-driven improvements

## 📚 Documentation Updates

### New Documentation:
- `docs/VERSIONING_GUIDE.md` - Comprehensive versioning guidance
- `docs/releases/FRAMEWORK_OVERVIEW_v1.1.4.md` - This release overview
- Updated `scripts/README.md` - Complete script reference

### Updated Documentation:
- All scripts now reference correct file paths
- GitHub Actions documentation updated
- Troubleshooting guides expanded

## 🙏 Acknowledgments

This release represents a significant infrastructure improvement that will benefit all PivotPHP Core users through:
- **Reduced complexity** in development workflow
- **Improved reliability** through automated validation
- **Better documentation** for easier onboarding
- **Streamlined maintenance** for long-term sustainability

## 🔗 Resources

- **Full Documentation:** `docs/releases/FRAMEWORK_OVERVIEW_v1.1.4.md`
- **Versioning Guide:** `docs/VERSIONING_GUIDE.md`
- **Script Reference:** `scripts/README.md`
- **Migration Guide:** `docs/MIGRATION_v114.md`

---

**PivotPHP Core v1.1.4 - Building Better Infrastructure for Better Code** 🚀
