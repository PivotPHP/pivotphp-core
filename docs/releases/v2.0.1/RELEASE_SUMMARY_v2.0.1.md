# PivotPHP v2.0.1 - Release Summary

## 📋 Release Information

- **Version**: 2.0.1
- **Release Date**: November 15, 2025
- **Type**: Minor Release (Feature Addition)
- **Breaking Changes**: None
- **Compatibility**: 100% backward compatible with v2.0.0

## 🎯 Release Highlights

### Pluggable Router Architecture

Version 2.0.1 introduces a **pluggable router system** that enables developers to:

✅ Inject custom router implementations via Application constructor
✅ Use route groups with shared prefixes and options
✅ Pass options (middleware, metadata) directly to route methods
✅ Maintain 100% backward compatibility with existing code

## 📦 What's New

### 1. RouterInterface Contract

New standardized interface for router implementations:

```php
interface RouterInterface {
    public function addRoute(string $method, string $path, callable|array|string $handler, array $options = []): void;
    public function dispatch(string $method, string $path): ?array;
    public function getRoutes(): array;
    public function group(string $prefix, callable $callback, array $options = []): void;
    public function clear(): void;
}
```

### 2. FastRouteAdapter (Default)

Default router adapter providing:
- Parameter extraction (`:id`, `:slug`)
- Pattern matching with regex
- Route grouping
- Backward compatibility

### 3. Custom Router Support

```php
// Default router
$app = new Application();

// Custom router injection
$app = new Application(null, ['router' => $customRouter]);
```

### 4. Route Options

```php
$app->get('/admin', $handler, ['middleware' => ['auth']]);
```

### 5. Route Grouping

```php
$app->group('/api', function($app) {
    $app->get('/users', $handler);
    $app->post('/users', $handler);
}, ['middleware' => ['api']]);
```

## 📊 Testing

**Test Coverage**:
- ✅ 35 new tests (23 unit + 12 integration)
- ✅ 81 assertions
- ✅ 100% pass rate

**Test Suites**:
1. `FastRouteAdapterTest.php` - Unit tests for default adapter
2. `CustomRouterIntegrationTest.php` - Integration tests for custom routers

## 🔄 Changes Summary

### Added

**New Files** (3):
- `src/Routing/Contracts/RouterInterface.php`
- `src/Routing/Adapters/FastRouteAdapter.php`
- `docs/releases/v2.0.1/RELEASE_NOTES_v2.0.1.md`

**New Tests** (2):
- `tests/Unit/Routing/FastRouteAdapterTest.php`
- `tests/Integration/Routing/CustomRouterIntegrationTest.php`

**New Methods**:
- `Application::group()` - Route grouping support

**Enhanced Methods**:
- `Application::get()` - Now accepts `$options` parameter
- `Application::post()` - Now accepts `$options` parameter
- `Application::put()` - Now accepts `$options` parameter
- `Application::delete()` - Now accepts `$options` parameter
- `Application::patch()` - Now accepts `$options` parameter

### Changed

**Application Constructor**:
```php
// Before
__construct(?string $basePath = null)

// After (backward compatible)
__construct(?string $basePath = null, array $options = [])
```

**Router Property Type**:
```php
// Before
private Router $router;

// After
private RouterInterface $router;
```

### Updated

- `VERSION` file: `2.0.0` → `2.0.1`
- `Application::VERSION`: `2.0.0` → `2.0.1`
- `CHANGELOG.md` - Added v2.0.1 section

## 🚀 Upgrade Guide

### From v2.0.0 to v2.0.1

**No migration required** - simply update and continue using existing code.

**Optional enhancements**:

1. **Use route options**:
   ```php
   $app->get('/admin', $handler, ['middleware' => ['auth']]);
   ```

2. **Use route groups**:
   ```php
   $app->group('/api', function($app) {
       $app->get('/users', $handler);
   }, ['middleware' => ['api']]);
   ```

3. **Inject custom router**:
   ```php
   $app = new Application(null, ['router' => new CustomRouter()]);
   ```

## 💡 Use Cases

### When to Use Default Router (FastRouteAdapter)

✅ Standard web applications
✅ REST APIs
✅ Most common routing needs
✅ Performance-critical applications

### When to Implement Custom Router

🔧 Specialized routing logic (subdomain routing, API versioning)
🔧 Performance optimization needs (custom caching layers)
🔧 Integration with legacy systems
🔧 Third-party router library integration

## 📈 Performance Impact

- **Zero performance degradation** for existing applications
- FastRouteAdapter uses same optimized Router underneath
- Custom routers can implement additional optimizations

**Benchmark Results** (compared to v2.0.0):
- Route registration: Same performance
- Route dispatching: Same performance
- Memory usage: Negligible increase (~0.1%)

## 🐛 Bug Fixes

No bugs fixed in this release (feature-only release).

## 🔒 Security

No security issues addressed (none found).

## 📚 Documentation

**New Documentation**:
- `docs/releases/v2.0.1/RELEASE_NOTES_v2.0.1.md` - Complete feature documentation
- Updated `CHANGELOG.md` with v2.0.1 details

**Documentation Includes**:
- RouterInterface contract details
- Custom router implementation examples
- Route grouping patterns
- Testing strategies
- Performance considerations
- Best practices
- Troubleshooting guide

## 🎓 Examples

### Example 1: Basic Custom Router

```php
class RegexRouter implements RouterInterface {
    // ... implementation
}

$app = new Application(null, ['router' => new RegexRouter()]);
```

### Example 2: Cached Router Wrapper

```php
class CachedRouter implements RouterInterface {
    private RouterInterface $innerRouter;
    private array $cache = [];

    // ... implementation with caching
}

$app = new Application(null, ['router' => new CachedRouter()]);
```

### Example 3: Route Groups

```php
$app->group('/api/v1', function($app) {
    $app->get('/users', [UserController::class, 'index']);
    $app->post('/users', [UserController::class, 'create']);
}, ['middleware' => ['api', 'throttle:60']]);
```

## 🔗 Resources

- **GitHub Repository**: https://github.com/HelixPHP/helixphp-core
- **Documentation**: `/docs/releases/v2.0.1/RELEASE_NOTES_v2.0.1.md`
- **Changelog**: `/CHANGELOG.md`
- **Issues**: https://github.com/HelixPHP/helixphp-core/issues

## 👥 Contributors

- Developer Team
- Community Testers
- Documentation Contributors

## 📝 License

This release maintains the same license as previous versions.

## 🎉 Conclusion

PivotPHP v2.0.1 brings **flexible routing architecture** without breaking existing code. Whether you use the powerful default router or implement custom routing logic, the framework now adapts to your needs.

**Key Achievements**:
- ✅ 100% backward compatible
- ✅ Zero breaking changes
- ✅ 35 tests, 81 assertions, 100% pass
- ✅ Clean architecture with RouterInterface
- ✅ Comprehensive documentation
- ✅ Production ready

**Upgrade with confidence** - no code changes required, new features available when you need them.

---

**Next Steps**:
1. Update to v2.0.1: `composer update pivotphp/core`
2. Review release notes: `docs/releases/v2.0.1/RELEASE_NOTES_v2.0.1.md`
3. Explore new features at your own pace
4. Consider adopting route groups and options for cleaner code

**Questions or Issues?**
Open an issue on GitHub or consult the documentation.

---

*Released: November 15, 2025*
*Version: 2.0.1*
*Codename: Pluggable Router Architecture*
