# Migration Guide - PivotPHP Core v2.0.0

## 📋 Overview

This guide helps you migrate from PivotPHP Core v1.x to v2.0.0.

**Version**: v2.0.0 - Modular Routing & Legacy Cleanup Edition
**Release Date**: November 15, 2025
**Type**: ⚠️ **BREAKING RELEASE**

### What's New

- ✅ **Cleaner Codebase**: 18% code reduction (11,871 lines removed)
- ✅ **Modern Namespaces**: Eliminated legacy PSR-15 aliases
- ✅ **Modular Routing**: Pluggable routing architecture (backward compatible)
- ✅ **Focused Architecture**: Removed 30 legacy test files
- ✅ **Better Performance**: 59% fewer aliases to autoload
- ✅ **Zero Regressions**: All 5,548 tests passing (100%)

### Breaking Changes Summary

- ❌ **Removed 110 legacy aliases** (PSR-15, Simple*, v1.1.x)
- ❌ **Removed 4 deprecated classes** (OpenApiExporter, SimpleTrafficClassifier, Legacy implementations)
- ❌ **Removed 26 legacy test files** (10,486 lines)
- ✅ **All functionality preserved** through proper namespaces

---

## 🚨 Breaking Changes

### 1. PSR-15 Middleware Namespace Changes

**Impact**: HIGH - Affects all middleware imports
**Effort**: LOW - Simple find & replace

#### What Changed

The legacy `PivotPHP\Core\Http\Psr15\Middleware\*` aliases have been removed. Use the correct modern namespaces:

```php
// ❌ OLD (v1.x) - Will not work
use PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\ErrorMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\CsrfMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\XssMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\SecurityHeadersMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\AuthMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\RateLimitMiddleware;
use PivotPHP\Core\Http\Psr15\Middleware\CacheMiddleware;

// ✅ NEW (v2.0.0) - Correct namespaces
use PivotPHP\Core\Middleware\Http\CorsMiddleware;
use PivotPHP\Core\Middleware\Http\ErrorMiddleware;
use PivotPHP\Core\Middleware\Security\CsrfMiddleware;
use PivotPHP\Core\Middleware\Security\XssMiddleware;
use PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware;
use PivotPHP\Core\Middleware\Security\AuthMiddleware;
use PivotPHP\Core\Middleware\Performance\RateLimitMiddleware;
use PivotPHP\Core\Middleware\Performance\CacheMiddleware;
```

#### Migration Steps

1. **Find all PSR-15 imports** in your codebase:
   ```bash
   grep -r "use PivotPHP\\\\Core\\\\Http\\\\Psr15" src/
   ```

2. **Replace with correct namespaces**:
   ```bash
   # HTTP Middleware
   sed -i 's/PivotPHP\\Core\\Http\\Psr15\\Middleware\\CorsMiddleware/PivotPHP\\Core\\Middleware\\Http\\CorsMiddleware/g' **/*.php
   sed -i 's/PivotPHP\\Core\\Http\\Psr15\\Middleware\\ErrorMiddleware/PivotPHP\\Core\\Middleware\\Http\\ErrorMiddleware/g' **/*.php

   # Security Middleware
   sed -i 's/PivotPHP\\Core\\Http\\Psr15\\Middleware\\CsrfMiddleware/PivotPHP\\Core\\Middleware\\Security\\CsrfMiddleware/g' **/*.php
   sed -i 's/PivotPHP\\Core\\Http\\Psr15\\Middleware\\XssMiddleware/PivotPHP\\Core\\Middleware\\Security\\XssMiddleware/g' **/*.php
   sed -i 's/PivotPHP\\Core\\Http\\Psr15\\Middleware\\SecurityHeadersMiddleware/PivotPHP\\Core\\Middleware\\Security\\SecurityHeadersMiddleware/g' **/*.php
   sed -i 's/PivotPHP\\Core\\Http\\Psr15\\Middleware\\AuthMiddleware/PivotPHP\\Core\\Middleware\\Security\\AuthMiddleware/g' **/*.php

   # Performance Middleware
   sed -i 's/PivotPHP\\Core\\Http\\Psr15\\Middleware\\RateLimitMiddleware/PivotPHP\\Core\\Middleware\\Performance\\RateLimitMiddleware/g' **/*.php
   sed -i 's/PivotPHP\\Core\\Http\\Psr15\\Middleware\\CacheMiddleware/PivotPHP\\Core\\Middleware\\Performance\\CacheMiddleware/g' **/*.php
   ```

3. **Test your application**:
   ```bash
   composer test
   ```

---

### 2. "Simple*" Prefix Removal

**Impact**: MEDIUM - Affects performance and memory classes
**Effort**: LOW - Simple renaming

#### What Changed

Redundant "Simple*" aliases have been removed. Use the actual class names:

```php
// ❌ OLD (v1.x) - Will not work
use PivotPHP\Core\Performance\SimplePerformanceMode;
use PivotPHP\Core\Middleware\SimpleLoadShedder;
use PivotPHP\Core\Memory\SimpleMemoryManager;
use PivotPHP\Core\Http\Pool\SimplePoolManager;
use PivotPHP\Core\Performance\SimplePerformanceMonitor;
use PivotPHP\Core\Json\Pool\SimpleJsonBufferPool;
use PivotPHP\Core\Events\SimpleEventDispatcher;

// ✅ NEW (v2.0.0) - Real class names
use PivotPHP\Core\Performance\PerformanceMode;
use PivotPHP\Core\Middleware\LoadShedder;
use PivotPHP\Core\Memory\MemoryManager;
use PivotPHP\Core\Http\Pool\PoolManager;
use PivotPHP\Core\Performance\PerformanceMonitor;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use PivotPHP\Core\Events\EventDispatcher;
```

#### Migration Steps

1. **Find all "Simple*" references**:
   ```bash
   grep -r "Simple" src/ | grep "use "
   ```

2. **Replace with actual names**:
   ```bash
   sed -i 's/SimplePerformanceMode/PerformanceMode/g' **/*.php
   sed -i 's/SimpleLoadShedder/LoadShedder/g' **/*.php
   sed -i 's/SimpleMemoryManager/MemoryManager/g' **/*.php
   sed -i 's/SimplePoolManager/PoolManager/g' **/*.php
   sed -i 's/SimplePerformanceMonitor/PerformanceMonitor/g' **/*.php
   sed -i 's/SimpleJsonBufferPool/JsonBufferPool/g' **/*.php
   sed -i 's/SimpleEventDispatcher/EventDispatcher/g' **/*.php
   ```

---

### 3. OpenApiExporter Removal

**Impact**: HIGH - If you use OpenAPI documentation
**Effort**: LOW - Use middleware instead

#### What Changed

`OpenApiExporter` class has been removed. Use `ApiDocumentationMiddleware` instead:

```php
// ❌ OLD (v1.x) - Class removed
use PivotPHP\Core\Utils\OpenApiExporter;

$routes = $app->getRoutes();
$exporter = new OpenApiExporter($routes);
$spec = $exporter->export();
$app->get('/openapi.json', function($req, $res) use ($spec) {
    return $res->json($spec);
});

// ✅ NEW (v2.0.0) - Use middleware
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;

// Automatic documentation with Swagger UI
$app->use(new ApiDocumentationMiddleware([
    'title' => 'My API',
    'version' => '1.0.0',
    'basePath' => '/api',
    'enableSwagger' => true,  // Swagger UI at /swagger
]));

// Documentation automatically generated!
// OpenAPI spec available at /openapi.json
// Swagger UI available at /swagger
```

#### Migration Steps

1. **Find OpenApiExporter usage**:
   ```bash
   grep -r "OpenApiExporter" src/
   ```

2. **Replace with middleware**:
   - Remove manual `OpenApiExporter` instantiation
   - Add `ApiDocumentationMiddleware` to your application
   - Configure middleware options (title, version, basePath)
   - Access documentation at `/openapi.json` and `/swagger`

3. **Benefits of middleware approach**:
   - ✅ Automatic route discovery
   - ✅ Real-time documentation updates
   - ✅ Built-in Swagger UI
   - ✅ PHPDoc parameter parsing
   - ✅ Zero manual maintenance

---

### 4. DynamicPoolManager → PoolManager

**Impact**: LOW - Only if you use pool management
**Effort**: LOW - Simple renaming

#### What Changed

Legacy `DynamicPoolManager` and `DynamicPool` aliases removed:

```php
// ❌ OLD (v1.x) - Aliases removed
use PivotPHP\Core\Http\Pool\DynamicPoolManager;
use PivotPHP\Core\Http\Pool\DynamicPool;
use PivotPHP\Core\Http\Psr7\Pool\DynamicPoolManager;

// ✅ NEW (v2.0.0) - Use PoolManager
use PivotPHP\Core\Http\Pool\PoolManager;
```

#### Migration Steps

```bash
sed -i 's/DynamicPoolManager/PoolManager/g' **/*.php
sed -i 's/DynamicPool/PoolManager/g' **/*.php
```

---

### 5. SimpleTrafficClassifier Removal

**Impact**: LOW - Feature was rarely used
**Effort**: MEDIUM - Implement custom if needed

#### What Changed

`SimpleTrafficClassifier` has been removed as it was too complex for a microframework:

```php
// ❌ OLD (v1.x) - Class removed
use PivotPHP\Core\Middleware\SimpleTrafficClassifier;

$app->use(new SimpleTrafficClassifier([
    'maxRequestsPerSecond' => 100,
    'burstLimit' => 20,
]));

// ✅ NEW (v2.0.0) - Use LoadShedder or implement custom
use PivotPHP\Core\Middleware\LoadShedder;

$app->use(new LoadShedder([
    'maxLoad' => 100,
    'strategy' => 'priority',  // or 'random', 'oldest', 'adaptive'
]));

// Or implement your own lightweight classifier
class CustomTrafficMiddleware {
    public function __invoke($request, $response, $next) {
        // Your custom logic here
        return $next($request, $response);
    }
}
```

---

### 6. Legacy Application & Arr Aliases

**Impact**: LOW - Only if using old namespaces
**Effort**: LOW - Update imports

#### What Changed

```php
// ❌ OLD (v1.x)
use PivotPHP\Core\Application;  // Removed
use PivotPHP\Core\Support\Arr;  // Removed
use PivotPHP\Core\Monitoring\PerformanceMonitor;  // Removed

// ✅ NEW (v2.0.0)
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Utils\Arr;
use PivotPHP\Core\Performance\PerformanceMonitor;
```

---

## ✅ Migration Checklist

### Required Actions

- [ ] **Update PSR-15 middleware imports** (8 classes)
- [ ] **Remove "Simple*" prefixes** (7 classes)
- [ ] **Replace OpenApiExporter** with ApiDocumentationMiddleware
- [ ] **Update DynamicPoolManager** → PoolManager
- [ ] **Replace SimpleTrafficClassifier** (if used)
- [ ] **Update Application namespace** (if needed)
- [ ] **Update Arr namespace** (if needed)
- [ ] **Run tests**: `composer test`
- [ ] **Run static analysis**: `composer phpstan`
- [ ] **Regenerate autoloader**: `composer dump-autoload`

### Recommended Actions

- [ ] **Review** [docs/v2.0.0-cleanup-analysis.md](../../v2.0.0-cleanup-analysis.md)
- [ ] **Update documentation** to reference v2.0.0 classes
- [ ] **Update IDE** configuration for new namespaces
- [ ] **Review examples** in `examples/` directory

---

## 🛠️ Automated Migration Script

We've created a migration script to help automate the process:

```bash
#!/bin/bash
# migrate-to-v2.sh

echo "🚀 Migrating to PivotPHP Core v2.0.0..."

# 1. PSR-15 Middleware
echo "📦 Updating PSR-15 middleware namespaces..."
find src/ tests/ -type f -name "*.php" -exec sed -i \
  -e 's|PivotPHP\\Core\\Http\\Psr15\\Middleware\\CorsMiddleware|PivotPHP\\Core\\Middleware\\Http\\CorsMiddleware|g' \
  -e 's|PivotPHP\\Core\\Http\\Psr15\\Middleware\\ErrorMiddleware|PivotPHP\\Core\\Middleware\\Http\\ErrorMiddleware|g' \
  -e 's|PivotPHP\\Core\\Http\\Psr15\\Middleware\\CsrfMiddleware|PivotPHP\\Core\\Middleware\\Security\\CsrfMiddleware|g' \
  -e 's|PivotPHP\\Core\\Http\\Psr15\\Middleware\\XssMiddleware|PivotPHP\\Core\\Middleware\\Security\\XssMiddleware|g' \
  -e 's|PivotPHP\\Core\\Http\\Psr15\\Middleware\\SecurityHeadersMiddleware|PivotPHP\\Core\\Middleware\\Security\\SecurityHeadersMiddleware|g' \
  -e 's|PivotPHP\\Core\\Http\\Psr15\\Middleware\\AuthMiddleware|PivotPHP\\Core\\Middleware\\Security\\AuthMiddleware|g' \
  -e 's|PivotPHP\\Core\\Http\\Psr15\\Middleware\\RateLimitMiddleware|PivotPHP\\Core\\Middleware\\Performance\\RateLimitMiddleware|g' \
  -e 's|PivotPHP\\Core\\Http\\Psr15\\Middleware\\CacheMiddleware|PivotPHP\\Core\\Middleware\\Performance\\CacheMiddleware|g' \
  {} \;

# 2. Simple* prefixes
echo "🔄 Removing Simple* prefixes..."
find src/ tests/ -type f -name "*.php" -exec sed -i \
  -e 's|SimplePerformanceMode|PerformanceMode|g' \
  -e 's|SimpleLoadShedder|LoadShedder|g' \
  -e 's|SimpleMemoryManager|MemoryManager|g' \
  -e 's|SimplePoolManager|PoolManager|g' \
  -e 's|SimplePerformanceMonitor|PerformanceMonitor|g' \
  -e 's|SimpleJsonBufferPool|JsonBufferPool|g' \
  -e 's|SimpleEventDispatcher|EventDispatcher|g' \
  {} \;

# 3. DynamicPoolManager
echo "♻️  Updating PoolManager references..."
find src/ tests/ -type f -name "*.php" -exec sed -i \
  -e 's|DynamicPoolManager|PoolManager|g' \
  -e 's|DynamicPool|PoolManager|g' \
  {} \;

# 4. Other aliases
echo "📚 Updating other namespace references..."
find src/ tests/ -type f -name "*.php" -exec sed -i \
  -e 's|PivotPHP\\Core\\Application|PivotPHP\\Core\\Core\\Application|g' \
  -e 's|PivotPHP\\Core\\Support\\Arr|PivotPHP\\Core\\Utils\\Arr|g' \
  -e 's|PivotPHP\\Core\\Monitoring\\PerformanceMonitor|PivotPHP\\Core\\Performance\\PerformanceMonitor|g' \
  {} \;

# 5. Regenerate autoloader
echo "🔧 Regenerating autoloader..."
composer dump-autoload

# 6. Run tests
echo "✅ Running tests..."
composer test

echo "🎉 Migration complete! Please review changes and commit."
```

**Usage**:
```bash
chmod +x migrate-to-v2.sh
./migrate-to-v2.sh
```

---

## 🧪 Testing Your Migration

After migration, run these validation steps:

### 1. Static Analysis
```bash
composer phpstan
```
**Expected**: Zero errors at Level 9

### 2. Code Style
```bash
composer cs:check
```
**Expected**: 100% PSR-12 compliant

### 3. Unit Tests
```bash
composer test
```
**Expected**: All tests passing

### 4. Integration Tests
```bash
php examples/01-basics/hello-world.php
# In another terminal:
curl http://localhost:8000/
```
**Expected**: Server runs without errors

---

## 📊 Migration Impact

### Before Migration (v1.x)
- 187 lines in aliases.php
- Confusing "Simple*" naming
- Legacy PSR-15 namespace pollution
- Deprecated OpenApiExporter manual usage

### After Migration (v2.0.0)
- 77 lines in aliases.php (59% reduction)
- Clear, direct class names
- Modern, organized namespaces
- Automatic API documentation middleware

### Performance Impact
- ✅ **Autoloading**: 59% fewer aliases = faster class resolution
- ✅ **Memory**: Smaller alias map = less memory overhead
- ✅ **Clarity**: No confusion about which class to use

---

## 🆘 Troubleshooting

### "Class not found" Errors

**Problem**: `Class 'PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware' not found`

**Solution**: Update namespace to modern location:
```php
use PivotPHP\Core\Middleware\Http\CorsMiddleware;
```

### "Call to undefined method" Errors

**Problem**: Method doesn't exist on new class

**Solution**: Check if you're using the correct class name (remove "Simple*" prefix)

### OpenAPI Documentation Not Working

**Problem**: Routes not appearing in `/openapi.json`

**Solution**: Replace OpenApiExporter with ApiDocumentationMiddleware:
```php
$app->use(new ApiDocumentationMiddleware([
    'title' => 'My API',
    'version' => '1.0.0',
]));
```

### Tests Failing After Migration

**Problem**: Tests using old class names

**Solution**: Update test imports and regenerate autoloader:
```bash
composer dump-autoload
composer test
```

---

## 🔌 New Feature: Modular Routing (No Migration Required)

### Overview

PivotPHP v2.0.0 introduces **pluggable routing architecture** with **zero breaking changes**. The default FastRoute implementation remains unchanged, but you can now swap routing engines.

### Default Behavior (Unchanged)

```php
// Your existing code works exactly the same
$app = new Application();
$app->get('/users', function($req, $res) {
    $res->json(['users' => []]);
});
```

### Optional: Custom Router

```php
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Routing\Adapters\FastRouteAdapter;

// Explicit FastRoute (same as default)
$app = new Application([
    'router' => new FastRouteAdapter()
]);
```

### Current Status (v2.0.0)

**External Package Already Included:**
```bash
# The routing system is now in a separate package
# Already installed as dependency: pivotphp/core-routing
```

**Aliased for Backward Compatibility:**
```php
// These work identically - aliased in src/aliases.php
use PivotPHP\Core\Routing\Router;      // Old namespace (aliased)
use PivotPHP\Routing\Router\Router;    // New namespace (actual)

// Your code works without changes
$app = new Application();
$app->get('/users', function($req, $res) {
    // Uses pivotphp/core-routing package
});
```

### Coming in v2.1.0

**Pluggable Router Injection:**
```php
// Planned feature (not yet implemented)
$app = new Application([
    'router' => new SymfonyRoutingAdapter()  // Custom router
]);
```

### Custom Router Implementation

```php
use PivotPHP\Core\Routing\Contracts\RouterInterface;

class MyCustomRouter implements RouterInterface
{
    public function addRoute(string $method, string $path, $handler): void {
        // Your routing logic
    }

    public function dispatch(string $method, string $path): array {
        // Your dispatch logic
        return ['handler' => $handler, 'params' => []];
    }
}

$app = new Application([
    'router' => new MyCustomRouter()
]);
```

### Benefits

- ✅ **No Breaking Changes** - FastRoute remains default
- ✅ **Pluggable** - Swap routing engines easily
- ✅ **Extensible** - Create custom adapters
- ✅ **Future-Proof** - Support for new routing libraries

**No migration action required** - this is purely additive!

---

## 📚 Additional Resources

- **Changelog**: [CHANGELOG.md](../../../CHANGELOG.md)
- **Release Notes**: [RELEASE_NOTES.md](RELEASE_NOTES.md)
- **Framework Overview**: [FRAMEWORK_OVERVIEW.md](FRAMEWORK_OVERVIEW.md)
- **Cleanup Analysis**: [v2.0.0-cleanup-analysis.md](../../v2.0.0-cleanup-analysis.md)
- **Examples**: [examples/](../../../examples/)
- **GitHub Issues**: [Report migration issues](https://github.com/PivotPHP/pivotphp-core/issues)

---

## 💬 Support

Need help with migration?

1. **Check examples**: `examples/` directory has updated code
2. **Review changelog**: Complete list of changes
3. **Ask community**: [GitHub Discussions](https://github.com/PivotPHP/pivotphp-core/discussions)
4. **Create issue**: [GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)

---

**Migration Time Estimate**: 15-30 minutes for typical application
**Difficulty**: Low to Medium
**Breaking**: Yes, but systematic and well-documented
**Worth It**: Absolutely! Cleaner, faster, more maintainable codebase
