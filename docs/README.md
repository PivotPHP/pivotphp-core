# PivotPHP Core v2.1.1 Documentation

Welcome to the complete documentation for **PivotPHP Core v2.1.1** - a high-performance, lightweight PHP microframework inspired by Express.js, designed for building APIs and web applications with exceptional speed and simplicity.

## 🚀 Quick Navigation

### Essential Guides
- **[Quick Start](quick-start.md)** - Get running in 5 minutes
- **[API Reference](API_REFERENCE.md)** - Complete API documentation
- **[Migration Guide](MIGRATION_GUIDE.md)** - Upgrading from previous versions

### Core Guides
- **[Architecture Guide](guides/architecture.md)** - v1.1.3 architecture overview (historical, see banner in the doc)
- **[Performance Guide](performance/README.md)** - Optimization and benchmarks
- **[Testing Guides](testing/)** - Testing strategies and examples (`api_testing.md`, `integration_testing.md`, `middleware_testing.md`, `mocks_and_stubs.md`)

### Reference Materials
- **[Examples Catalog](reference/examples.md)** - Complete examples collection
- **[Middleware Reference](technical/middleware/README.md)** - All available middleware
- **[Routing Reference](technical/routing/router.md)** - Routing patterns and constraints
- **[Application & Configuration](technical/application.md)** - Framework configuration

## 📚 Learning Paths

### 👶 **Beginner Path** (New to PivotPHP)
1. [Quick Start](quick-start.md) - Basic setup and hello world
2. [Basic Usage Examples](implementations/usage_basic.md) - Simple API creation
3. [Routing Guide](technical/routing/SYNTAX_GUIDE.md) - URL routing patterns
4. [Request/Response](technical/http/README.md) - Handling HTTP

### 🏃 **Intermediate Path** (Building Production APIs)
1. [Middleware Usage](implementations/usage_with_middleware.md) - Security and performance middleware
2. [Authentication](technical/authentication/README.md) - JWT and API key auth
3. [Testing](testing/) - Unit and integration testing
4. [Performance Optimization](performance/README.md) - Object pooling and optimization

### 🚀 **Advanced Path** (Framework Extension)
1. [Architecture Guide](guides/architecture.md) - Framework internals
2. [Custom Middleware](implementations/usage_with_custom_middleware.md) - Building custom components
3. [Service Providers](technical/providers/README.md) - Dependency injection
4. [Extensions](technical/extensions/README.md) - Framework extensions

## ✨ What's Current (v2.1.x)

### 🩹 **v2.1.1 — PSR-7 2.0 Compatibility Fix**
- Fixed a real incompatibility with `psr/http-message` 2.0 that could fatal-error every
  request when Composer resolved to that version — retyped ~46 method signatures across
  the PSR-7 implementation to match the real PSR-7 2.0 interfaces.
- No public-API or observable behavior change for documented usage.

### 🔁 **v2.1.0 — Response Emission, Pool Safety & Deprecation Cycle**
- `Application::run()` is now the single, guaranteed response-emission point — fixes a
  double-emit / spurious "body already sent" warning on every request.
- Fixed pooled-object data leaks between requests in concurrent/async runtimes (Swoole,
  ReactPHP, FrankenPHP).
- Started a deprecation cycle (removal planned for v3.0.0) for `Core\Container`,
  `Middleware\LoadShedder`/`RateLimitMiddleware`, `Request::getIp()`, and
  `Providers\Logger`/`EventDispatcher` — see [CHANGELOG.md](../CHANGELOG.md) for the full
  list and replacements.

See the [CHANGELOG](../CHANGELOG.md) for complete release notes.

## 📜 Previous Versions (Historical)

The v2.1.x line above is current. Earlier releases, most recent first:
**v2.0.0** (Legacy Cleanup Edition) → **v1.2.0** (Simplicity Edition) → **v1.1.4**
(Developer Experience) → **v1.1.3** (Performance Breakthrough) and earlier — see the
[CHANGELOG](../CHANGELOG.md) for the complete history.

### v1.1.4 Highlights (historical, not current)

<details>
<summary>Infrastructure consolidation, automatic version management, GitHub Actions optimization</summary>

**Infrastructure Consolidation** — scripts reduced from 25 to 15 (40% reduction):
```bash
scripts/quality/quality-check.sh    # Consolidated validation
scripts/release/version-bump.sh     # Automatic version management
```

**Automatic Version Management** — VERSION file as single source of truth, automatic
version detection, strict X.Y.Z validation.

**GitHub Actions Optimization** — 25% workflow reduction (4 → 3), consolidated scripts,
corrected repository URLs (from the pre-rename express-php project to pivotphp-core).

**Documentation** — a 315-line versioning guide, static file managers documentation,
complete v1.1.4 release documentation suite.

</details>

## 🔧 Framework Status

- **Current Version**: v2.1.1 (PSR-7 2.0 Compatibility Fix)
- **PHP Requirements**: 8.1+ with strict typing
- **Production Ready**: Enterprise-grade quality with type safety
- **Community**: [GitHub](https://github.com/PivotPHP)

## 🧩 Ecosystem

### Official Extensions
- **[Cycle ORM Extension](https://github.com/PivotPHP/pivotphp-cycle-orm)** - Database integration
- **[ReactPHP Extension](https://github.com/PivotPHP/pivotphp-reactphp)** - Async runtime

### Community Resources
- **[Benchmarks Repository](https://github.com/PivotPHP/pivotphp-benchmarks)** - Performance testing
- **[Examples Collection](../examples/)** - Practical usage examples

## 📖 Technical Documentation

### Core Components
- **[Application](technical/application.md)** - Framework bootstrap and lifecycle
- **[HTTP Layer](technical/http/README.md)** - Request/response handling
- **[Routing](technical/routing/README.md)** - URL routing and static file management
- **[Static File Managers](technical/routing/STATIC_FILE_MANAGERS.md)** - Complete static file serving guide
- **[Middleware](technical/middleware/README.md)** - Request/response pipeline

### Advanced Topics
- **[Authentication](technical/authentication/README.md)** - Multi-method authentication
- **[Performance](technical/performance/)** - Object pooling and optimization
- **[JSON Optimization](technical/json/README.md)** - Buffer pooling system
- **[PSR Compatibility](technical/compatibility/)** - PSR-7/PSR-15 compliance

## 🤝 Contributing

Interested in contributing to PivotPHP Core? See our [Contributing Guide](contributing/README.md) for:
- Development setup
- Code style requirements
- Testing procedures
- Pull request process

## 📄 License

PivotPHP Core is open-source software licensed under the [MIT License](../LICENSE).

---

*Built with ❤️ for the PHP community - Combining Express.js simplicity with PHP power*
