# Migration Guide - PivotPHP Core v1.1.4

**From:** v1.1.3 and earlier
**To:** v1.1.4
**Migration Type:** Infrastructure (Non-Breaking)
**Effort:** Low (mostly optional)

## 🎯 Migration Overview

PivotPHP Core v1.1.4 introduces **infrastructure improvements** that are designed to be **non-breaking**. Most changes are internal optimizations that improve developer experience without affecting application code.

## ⚠️ Required Changes

### 1. VERSION File Requirement

**What Changed:** All scripts now require a VERSION file in the project root.

**Action Required:**
```bash
# Create VERSION file if it doesn't exist
echo "1.1.4" > VERSION

# Verify format (must be X.Y.Z)
cat VERSION
# Should output: 1.1.4
```

**Why:** Eliminates hardcoded versions and enables automatic version detection.

### 2. Script Path Updates (If Using Custom CI/CD)

**What Changed:** Some scripts were removed/consolidated.

**Action Required:** Update any custom CI/CD or scripts that reference:

#### ❌ Removed Scripts:
```bash
# Replace these references:
scripts/quality-check-v114.sh        → scripts/quality/quality-check.sh
scripts/validate_all_v114.sh         → scripts/validation/validate_all.sh
scripts/quick-quality-check.sh       → scripts/quality/quality-check.sh
scripts/simple_pre_release.sh        → scripts/release/prepare_release.sh
scripts/quality-gate.sh              → scripts/quality/quality-check.sh
scripts/quality-metrics.sh           → scripts/quality/quality-check.sh
scripts/test-php-versions-quick.sh   → scripts/testing/test-all-php-versions.sh
scripts/ci-validation.sh             → scripts/quality/quality-check.sh
scripts/setup-precommit.sh           → (one-time setup, remove)
scripts/adapt-psr7-v1.php            → (specific utility, remove)
```

#### ✅ Updated References:
```yaml
# In GitHub Actions (.github/workflows/*.yml):
# OLD:
run: ./scripts/quality-gate.sh

# NEW:
run: scripts/quality/quality-check.sh
```

## 🔄 Recommended Changes

### 1. Adopt New Version Management

**New Feature:** Semantic version management with automation.

**Migration Steps:**
```bash
# Instead of manually editing version files:
# OLD WAY:
# vim composer.json  # manually edit version
# git commit -m "bump version"

# NEW WAY:
scripts/release/version-bump.sh patch   # 1.1.4 → 1.1.5
scripts/release/version-bump.sh minor   # 1.1.4 → 1.2.0
scripts/release/version-bump.sh major   # 1.1.4 → 2.0.0
```

**Benefits:**
- Automatic VERSION file updates
- Automatic composer.json updates (if present)
- Automatic git commit and tag creation
- Semantic version validation

### 2. Use Consolidated Quality Checks

**New Feature:** Single script for all quality validations.

**Migration Steps:**
```bash
# Instead of running multiple scripts:
# OLD WAY:
scripts/quality-check-v114.sh
scripts/quick-quality-check.sh
scripts/validation/validate_all.sh

# NEW WAY:
scripts/quality/quality-check.sh  # Consolidates all quality checks
```

**Benefits:**
- Single command for comprehensive validation
- Automatic version detection
- Consistent output formatting
- Better error handling

### 3. Update Development Workflow

**New Feature:** Streamlined development commands.

**Migration Steps:**

#### Daily Development:
```bash
# Before commit:
scripts/quality/quality-check.sh

# Before push (optional):
scripts/validation/validate_all.sh
```

#### Release Preparation:
```bash
# 1. Version bump
scripts/release/version-bump.sh [patch|minor|major]

# 2. Release preparation
scripts/release/prepare_release.sh

# 3. Final release (if validation passes)
scripts/release/release.sh
```

## 📚 Documentation Updates

### 1. Read New Guides

**New Documentation Available:**
- `docs/VERSIONING_GUIDE.md` - Complete versioning guide (315 lines)
- `scripts/README.md` - Updated script reference
- `docs/releases/FRAMEWORK_OVERVIEW_v1.1.4.md` - Full release overview

**Action:** Review these guides to understand new capabilities.

### 2. Update Team Documentation

**If you have team documentation** referencing old scripts, update it:

```markdown
<!-- OLD documentation -->
Run quality checks:
./scripts/quality-check-v114.sh

<!-- NEW documentation -->
Run quality checks:
scripts/quality/quality-check.sh
```

## 🔧 Troubleshooting Migration

### Common Issues and Solutions:

#### Issue 1: "VERSION file not found"
```bash
❌ ERRO CRÍTICO: Arquivo VERSION não encontrado
```

**Solution:**
```bash
# Create VERSION file in project root
echo "1.1.4" > VERSION
```

#### Issue 2: "Invalid version format"
```bash
❌ ERRO CRÍTICO: Formato de versão inválido
```

**Solution:**
```bash
# Check VERSION file content
cat VERSION
# Must be in X.Y.Z format (e.g., 1.1.4)

# Fix if needed
echo "1.1.4" > VERSION
```

#### Issue 3: Script not found
```bash
❌ scripts/quality-gate.sh: command not found
```

**Solution:**
```bash
# Use consolidated script instead
scripts/quality/quality-check.sh
```

#### Issue 4: Project root not found
```bash
❌ Project root not found
```

**Solution:**
```bash
# Run scripts from project root directory
cd /path/to/pivotphp-core
scripts/quality/quality-check.sh

# Or use absolute path to VERSION file
```

## 🧪 Validation Steps

### 1. Verify Migration Success:

```bash
# Test VERSION file detection
scripts/quality/quality-check.sh --version  # Should show v1.1.4

# Test script execution
scripts/quality/quality-check.sh            # Should run without errors

# Test version management
scripts/release/version-bump.sh patch --dry-run  # Should show next version
```

### 2. Validate CI/CD:

```bash
# Test GitHub Actions locally (if using act)
act -j quality-check

# Or commit and verify GitHub Actions pass
git add . && git commit -m "test: validate v1.1.4 migration"
git push origin feature/test-migration
```

### 3. Team Validation:

```bash
# Each team member should verify:
1. git pull latest changes
2. Check VERSION file exists: cat VERSION
3. Run quality check: scripts/quality/quality-check.sh
4. Verify no errors related to missing scripts
```

## 📊 Migration Checklist

### ✅ Infrastructure:
- [ ] VERSION file exists in project root with format X.Y.Z
- [ ] No references to removed scripts in custom CI/CD
- [ ] GitHub Actions workflows updated (if customized)
- [ ] Team documentation updated with new script names

### ✅ Development:
- [ ] Team familiar with new script commands
- [ ] Local development workflow tested
- [ ] Version bump script tested (dry-run)
- [ ] Quality check script tested

### ✅ Validation:
- [ ] All existing tests still pass
- [ ] New consolidated scripts work correctly
- [ ] CI/CD pipeline functions without errors
- [ ] No broken references to old scripts

## 🎯 Benefits After Migration

### Immediate Benefits:
- **Fewer scripts to remember** (15 vs 25)
- **Automatic version detection** eliminates manual errors
- **Consistent commands** across all environments
- **Better error messages** for easier troubleshooting

### Long-term Benefits:
- **Easier maintenance** with less script duplication
- **Reliable versioning** with semantic validation
- **Improved onboarding** with clearer documentation
- **Future-proof infrastructure** for framework evolution

## 🔮 Future Considerations

### Upcoming Changes (v1.1.5+):
- Further script optimizations based on feedback
- Additional automation opportunities
- Enhanced version management features

### Deprecated Features:
- **No features deprecated** in v1.1.4
- **Backward compatibility maintained** for smooth transition
- **Gradual adoption encouraged** but not forced

## 🆘 Getting Help

### Resources:
- **Documentation:** `docs/VERSIONING_GUIDE.md`
- **Script Reference:** `scripts/README.md`
- **Release Overview:** `docs/releases/FRAMEWORK_OVERVIEW_v1.1.4.md`

### Community:
- **GitHub Issues:** https://github.com/PivotPHP/pivotphp-core/issues
- **GitHub Discussions:** https://github.com/PivotPHP/pivotphp-core/discussions

### Support:
If you encounter migration issues:
1. Check this guide first
2. Review error messages (now in Portuguese for clarity)
3. Consult updated documentation
4. Ask in GitHub Discussions
5. Create GitHub issue if needed

---

**Migration to v1.1.4 - Simple, Safe, Beneficial** 🚀
