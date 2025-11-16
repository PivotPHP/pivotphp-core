# PivotPHP Core v1.1.4 Documentation

Welcome to the complete documentation for **PivotPHP Core v1.1.4** - a high-performance, lightweight PHP microframework inspired by Express.js, designed for building APIs and web applications with exceptional speed and simplicity.

## 🚀 Quick Navigation

### Essential Guides
- **[Quick Start](quick-start.md)** - Get running in 5 minutes
- **[API Reference](API_REFERENCE.md)** - Complete API documentation
- **[Migration Guide](MIGRATION_GUIDE.md)** - Upgrading from previous versions

### Core Guides
- **[Architecture Guide](guides/architecture.md)** - v1.1.3 architecture overview
- **[Performance Guide](guides/performance.md)** - Optimization and benchmarks
- **[Testing Guide](guides/testing.md)** - Testing strategies and examples

### Reference Materials
- **[Examples Catalog](reference/examples.md)** - Complete examples collection
- **[Middleware Reference](reference/middleware.md)** - All available middleware
- **[Routing Reference](reference/routing.md)** - Routing patterns and constraints
- **[Configuration Reference](reference/configuration.md)** - Framework configuration

## 📚 Learning Paths

### 👶 **Beginner Path** (New to PivotPHP)
1. [Quick Start](quick-start.md) - Basic setup and hello world
2. [Basic Usage Examples](implementations/usage_basic.md) - Simple API creation
3. [Routing Guide](technical/routing/SYNTAX_GUIDE.md) - URL routing patterns
4. [Request/Response](technical/http/README.md) - Handling HTTP

### 🏃 **Intermediate Path** (Building Production APIs)
1. [Middleware Usage](implementations/usage_with_middleware.md) - Security and performance middleware
2. [Authentication](technical/authentication/README.md) - JWT and API key auth
3. [Testing](guides/testing.md) - Unit and integration testing
4. [Performance Optimization](guides/performance.md) - Object pooling and optimization

### 🚀 **Advanced Path** (Framework Extension)
1. [Architecture Guide](guides/architecture.md) - Framework internals
2. [Custom Middleware](implementations/usage_with_custom_middleware.md) - Building custom components
3. [Service Providers](technical/providers/README.md) - Dependency injection
4. [Extensions](technical/extensions/README.md) - Framework extensions

## ✨ v1.1.4 Highlights

### 🔧 **Infrastructure Consolidation**
Complete infrastructure optimization and automation:
```bash
# Scripts reduced from 25 to 15 (40% reduction)
scripts/quality/quality-check.sh    # Consolidated validation
scripts/release/version-bump.sh     # Automatic version management
```

### 📦 **Automatic Version Management**
- **VERSION file requirement** - Single source of truth
- **Automatic version detection** - No more hardcoded versions
- **Strict validation** - X.Y.Z semantic versioning enforced
- **Portuguese error messages** - Clear developer feedback

### 🚀 **GitHub Actions Optimization**
- **25% workflow reduction** (4 → 3 workflows)
- **Consolidated scripts** - No more duplicate functionality
- **Fixed repository URLs** - Corrected from express-php to pivotphp-core
- **Enhanced validation** - Consistent across all workflows

### 📚 **Comprehensive Documentation**
- **315-line versioning guide** - Complete semantic versioning guidance
- **Static file managers documentation** - Two managers with clear use cases
- **Release documentation** - Complete v1.1.4 documentation suite
- **Zero breaking changes** - 100% backward compatibility maintained

## 🔧 Framework Status

- **Current Version**: v1.1.4 (Infrastructure Consolidation & Automation Edition)
- **PHP Requirements**: 8.1+ with strict typing
- **Production Ready**: Enterprise-grade quality with type safety
- **Community**: [GitHub](https://github.com/PivotPHP)

## 🧩 Ecosystem

### Official Extensions
- **[Cycle ORM Extension](https://github.com/PivotPHP/pivotphp-cycle-orm)** - Database integration
- **[ReactPHP Extension](https://github.com/PivotPHP/pivotphp-reactphp)** - Async runtime

### Community Resources
- **[Benchmarks Repository](https://github.com/PivotPHP/pivotphp-benchmarks)** - Performance testing
- **[Examples Collection](examples/)** - Practical usage examples

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
