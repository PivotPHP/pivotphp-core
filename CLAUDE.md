# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

PivotPHP Core is a high-performance PHP microframework inspired by Express.js, designed for building APIs and web applications. Current version: **2.0.0** (Legacy Cleanup Edition - Simplicity through Elimination).

**Documentação oficial em PT-BR:** `website/pt/docs/` (internacionalização EN planejada)

## Essential Commands

### Development Workflow
```bash
# Run comprehensive validation (includes all checks)
./scripts/validation/validate_all.sh

# Multi-PHP version testing (RECOMMENDED for releases)
composer docker:test-all        # Test all PHP versions (8.1-8.4) via Docker
composer docker:test-quality    # Test all versions + quality checks

# Quality checks
composer quality:check    # Run all quality checks
composer phpstan         # Static analysis (Level 9)
composer cs:check        # PSR-12 code style check
composer cs:fix          # Auto-fix code style issues

# Testing
composer test                    # Run all tests
composer test:ci                 # CI/CD tests (excludes integration for clean output)
composer test:integration        # Integration tests (for pre-push validation)
composer test:security          # Security-specific tests
composer test:auth             # Authentication tests
composer benchmark             # Performance benchmarks

# Run a single test file
vendor/bin/phpunit tests/Core/ApplicationTest.php

# Run tests with specific group
vendor/bin/phpunit --group stress
vendor/bin/phpunit --exclude-group stress,integration

# Run specific test suites (see phpunit.xml for full list)
vendor/bin/phpunit --testsuite=Core      # Core framework tests
vendor/bin/phpunit --testsuite=Security  # Security tests
vendor/bin/phpunit --testsuite=Performance # Performance tests
vendor/bin/phpunit --testsuite=Unit      # Unit tests only
vendor/bin/phpunit --testsuite=Fast      # Fast tests (excludes stress)
vendor/bin/phpunit --testsuite=CI        # CI tests (excludes integration & stress)
vendor/bin/phpunit --testsuite=Integration # Integration tests
vendor/bin/phpunit --testsuite=Stress    # Stress tests only

# Additional validation commands
php ./scripts/quality/validate-psr12.php    # PSR-12 validation (standalone)
php ./scripts/utils/switch-psr7-version.php --check  # Check PSR-7 version

# Pre-commit and release
./scripts/pre-commit             # Run pre-commit validations
./scripts/release/prepare_release.sh 1.1.3  # Prepare release for version 1.1.3
./scripts/release/release.sh            # Create release after preparation

# Quality validation (recommended before commits)
./scripts/quality/quality-check.sh       # Comprehensive quality validation (uses CI tests)
./scripts/pre-push              # Pre-push validation (includes integration tests)

# CI/CD specific commands
composer quality:ci             # CI-optimized quality check (no integration tests)
composer prepush:validate      # Pre-push validation with integration tests

# CI/CD Strategy (Optimized)
composer ci:validate            # Quick CI validation (PHP 8.1 only)
composer quality:gate           # Quality gate assessment
```

### CI/CD Optimization Strategy

**GitHub Actions**: Optimized for speed with critical validations only (PHP 8.1)
- ⚡ **Fast CI/CD**: ~2-3 minutes vs ~10-15 minutes previously
- 🎯 **Critical checks**: Syntax, PHPStan Level 9, PSR-12, Security, Performance baseline
- 🚀 **Breaking changes detection**: Immediate feedback on critical issues

**Local Comprehensive Testing**: Full validation via Docker
```bash
# Before major releases or complex changes
composer docker:test-all        # All PHP versions (8.1, 8.2, 8.3, 8.4)
composer docker:test-quality    # All versions + extended quality metrics
```

### Running Examples
```bash
composer examples:hello-world      # Hello World (01-basics)
composer examples:basic-routes     # Basic CRUD routes (01-basics)
composer examples:jwt-auth         # JWT authentication (06-security)
composer examples:array-callables  # Array callable syntax (07-advanced)
composer examples:performance      # Performance mode (05-performance)
composer examples:rest-api         # Complete REST API (04-api)
```

### v2.0.0 Features
```php
// Array callable support (PHP 8.4+ compatible)
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [$controller, 'store']);
// Router methods now accept callable|array union types

// v2.0.0: Documentação OpenAPI/Swagger Automática
// Gera paths a partir das rotas registradas (sem parsing de PHPDoc)
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;

$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',        // JSON OpenAPI endpoint
    'swagger_path' => '/swagger',  // Swagger UI interface
    'base_url' => 'http://localhost:8080'
]));

$app->get('/users', function($req, $res) {
    return $res->json(['users' => User::all()]);
});

// Acesse: http://localhost:8080/swagger (Interface Swagger UI)
// Acesse: http://localhost:8080/docs (JSON OpenAPI 3.0.0)

// Optimized object pooling (+116% performance improvement)
// Request pool reuse: 0% → 100%
// Response pool reuse: 0% → 99.9%
// Framework throughput: 20,400 → 44,092 ops/sec

// Organized middleware structure
use PivotPHP\Core\Middleware\Security\CsrfMiddleware;
use PivotPHP\Core\Middleware\Performance\RateLimitMiddleware;
use PivotPHP\Core\Middleware\Http\CorsMiddleware;

// Consolidated utilities
use PivotPHP\Core\Utils\Arr;
$result = Arr::get($array, 'nested.key', 'default');
$shuffled = Arr::shuffle($array); // Preserves keys

// JSON optimization
use PivotPHP\Core\Json\Pool\JsonBufferPool;
$json = JsonBufferPool::encodeWithPool($data);
$stats = JsonBufferPool::getStatistics();

// Performance mode (simplified class — v2.0.0 promotes PerformanceMode as default)
use PivotPHP\Core\Performance\PerformanceMode;
PerformanceMode::enable(PerformanceMode::PROFILE_PRODUCTION);
```

## Code Architecture

### Project Structure
```
pivotphp-core/
├── src/                    # Framework source code
│   ├── Core/              # Application core, container, services
│   ├── Events/            # Event system: EventDispatcher (PSR-14), ListenerProvider
│   ├── Http/              # HTTP layer (Request, Response, PSR-7, CustomHeaderCollection)
│   ├── Logging/           # PSR-3 logging: PsrLogger
│   ├── Routing/           # Router and route management
│   ├── Middleware/        # Middleware system (Security, Performance, HTTP, Core)
│   ├── Providers/         # Service providers (Container ativo; demais classes em depreciacao v2.1.0)
│   ├── Performance/       # Performance optimization components
│   ├── Json/              # JSON optimization and pooling
│   ├── Utils/             # Utility classes
│   └── Support/           # Support classes
├── tests/                 # Test suites
│   ├── Core/              # Core framework tests
│   ├── Security/          # Security tests
│   ├── Performance/       # Performance tests
│   ├── Integration/       # Integration tests
│   ├── Stress/            # Stress tests
│   └── Unit/              # Unit tests
├── scripts/               # Development scripts
│   ├── validation/        # Validation scripts
│   ├── quality/           # Quality check scripts
│   ├── testing/           # Testing utilities
│   └── release/           # Release management
└── examples/              # Usage examples
```

### Core Framework Structure
- **Service Provider Pattern**: All major components are registered via service providers in `src/Providers/`
- **PSR Standards**: Strict PSR-7 (HTTP messages), PSR-15 (middleware), PSR-12 (coding style) compliance
- **Container**: Dependency injection container ativo e `src/Providers/Container.php` (PSR-11). `src/Core/Container.php` esta depreciado desde v2.1.0 e sera removido em v3.0.0.
- **Event-Driven**: Event dispatcher PSR-14 em `src/Events/EventDispatcher.php` com hooks system para extensibilidade. `src/Providers/EventDispatcher.php` depreciado em v2.1.0.

### Key Components
1. **Application Core** (`src/Core/Application.php`): Main application class that bootstraps the framework
   - Version constant: `Application::VERSION`
   - Middleware aliases mapping for compatibility

2. **Router** (`src/Routing/Router.php`): High-performance routing with middleware support
   - Supports regex constraints: `/users/:id<\d+>`
   - Predefined shortcuts: `slug`, `uuid`, `date`, etc.

3. **Middleware Pipeline** (`src/Middleware/`): PSR-15 compliant middleware system organized by responsibility
   - **Security**: `src/Middleware/Security/` - AuthMiddleware, CsrfMiddleware, XssMiddleware, SecurityHeadersMiddleware
   - **Performance**: `src/Middleware/Performance/` - CacheMiddleware, RateLimitMiddleware (depreciado v2.1.0; usar `RateLimiter`)
   - **HTTP**: `src/Middleware/Http/` - CorsMiddleware, ErrorMiddleware, ApiDocumentationMiddleware
   - **Core**: `src/Middleware/Core/` - BaseMiddleware, MiddlewareInterface
   - **Advanced**: LoadShedder (depreciado v2.1.0; usar `RateLimiter`), TrafficClassifier

4. **HTTP Layer** (`src/Http/`): PSR-7 hybrid implementation
   - Express.js style API with PSR-7 compliance
   - Object pooling via `OptimizedHttpFactory` and `DynamicPoolManager`

5. **Performance Components**:
   - **JSON Optimization**: `JsonBufferPool`, `JsonBuffer`
   - **Pool Management**: `PoolManager` in `Http/Pool/`
   - **Performance Monitoring**: `PerformanceMonitor` in `Performance/`
   - **Performance Mode**: `PerformanceMode` (simplified default, in `Performance/`)

### v2.0.0 Middleware Organization

```
src/Middleware/
├── Security/              # Security-focused middlewares
│   ├── AuthMiddleware.php
│   ├── CsrfMiddleware.php
│   ├── SecurityHeadersMiddleware.php
│   └── XssMiddleware.php
├── Performance/           # Performance-focused middlewares
│   ├── CacheMiddleware.php
│   └── RateLimitMiddleware.php   # @deprecated v2.1.0 — usar RateLimiter
├── Http/                 # HTTP protocol middlewares
│   ├── ApiDocumentationMiddleware.php
│   ├── CorsMiddleware.php
│   └── ErrorMiddleware.php
└── Core/                 # Base middleware infrastructure
    ├── BaseMiddleware.php
    └── MiddlewareInterface.php
# LoadShedder.php (raiz Middleware/) — @deprecated v2.1.0 — usar RateLimiter
```

#### v2.0.0 Key Characteristics
- **Object Pool**: Pool reuse 100% (Request) and 99.9% (Response)
- **Array Callable Support**: Full PHP 8.4+ compatibility with `callable|array` union types in Router
- **Framework Performance**: +116% improvement (20,400 → 44,092 ops/sec) maintained from v1.1.4
- **Legacy Cleanup**: 18% code reduction — eliminated deprecated classes and legacy namespaces
- **PerformanceMode** replaces `HighPerformanceMode` as the default simplified class

### Request/Response Hybrid Design
The framework uses a hybrid approach for PSR-7 compatibility:
- `Request` class implements `ServerRequestInterface` while maintaining Express.js methods
- Legacy `getBody()` renamed to `getBodyAsStdClass()` for backward compatibility
- PSR-7 objects are lazy-loaded for performance

### Testing Approach
- Tests organized by domain in `tests/` directory (see phpunit.xml for test suites)
- Test suites: Core, Security, Performance, Integration, Stress, Unit
- Each major component has its own test suite
- Integration tests verify component interaction
- **v2.0.0**: All 5,548 tests passing (100% success rate)
- Enhanced test maintainability with constants instead of hardcoded values
- JSON optimization tests in `tests/Json/Pool/`

### Code Style Requirements
- PHP 8.1+ features are used throughout
- Strict typing is enforced
- **PHPStan Level 9** must pass (zero errors tolerance)
- **PSR-12** coding standard via PHP_CodeSniffer
- All new code must include proper type declarations

### Performance Considerations
- Framework optimized for high throughput (44,092 ops/sec in v2.0.0)
- Object pool reuse 100% (Request) and 99.9% (Response) with lazy loading
- JSON optimization with automatic pooling threshold (256 bytes)
- v2.0.0 reduced codebase by 18% compared to v1.2.0 while maintaining performance
- Benchmark any performance-critical changes using `composer benchmark`
- Avoid unnecessary object creation in hot paths
- Use lazy loading for optional dependencies

## Route Handler Syntax

PivotPHP Core supports the following route handler syntaxes:

### ✅ Supported Syntaxes
```php
// Closure/Anonymous function (Recommended)
$app->get('/users', function($req, $res) {
    return $res->json(['users' => []]);
});

// Array callable with class
$app->get('/users', [UserController::class, 'index']);     // Static/Instance method
$app->post('/users', [$controller, 'store']);              // Instance method
$app->put('/users/:id', [UserController::class, 'update']); // With parameters

// Named function
function getUsersHandler($req, $res) {
    return $res->json(['users' => []]);
}
$app->get('/users', 'getUsersHandler');
```

### ❌ NOT Supported
```php
// String format Controller@method - DOES NOT WORK!
$app->get('/users', 'UserController@index'); // TypeError!
```

**Important**: Router methods use `callable|array` union types for PHP 8.4+ strict typing compatibility. Strings in the format `Controller@method` are not considered callable by PHP and will result in a TypeError.

**Migration**: Replace `'Controller@method'` with `[Controller::class, 'method']` in all code.

## Development Workflow

1. Before committing, run `./scripts/pre-commit` or `./scripts/validation/validate_all.sh`
2. All tests must pass before pushing changes
3. Static analysis must pass at Level 9
4. Code style must comply with PSR-12
5. For releases, use `./scripts/release/prepare_release.sh` followed by `./scripts/release/release.sh`

### Array Callable Testing
When implementing array callable routes, verify compatibility:

```bash
# Test array callable functionality
vendor/bin/phpunit tests/Unit/Routing/ArrayCallableTest.php
vendor/bin/phpunit tests/Integration/Routing/ArrayCallableIntegrationTest.php

# Test parameter routing with array callables
vendor/bin/phpunit tests/Unit/Routing/ParameterRoutingTest.php
```

### Debugging and Troubleshooting
```bash
# Debug specific component
vendor/bin/phpunit tests/Core/ApplicationTest.php --debug

# Run with verbose output
vendor/bin/phpunit --verbose

# Check specific middleware
vendor/bin/phpunit tests/Middleware/Security/ --testdox

# Performance debugging
composer benchmark:simple                    # Quick performance check
vendor/bin/phpunit tests/Performance/ --group performance

# JSON pool debugging
vendor/bin/phpunit tests/Json/Pool/ --testdox
```

### Middleware Development (v1.1.2+)
When creating new middleware, follow the organized structure:

```php
// Security middleware
namespace PivotPHP\Core\Middleware\Security;

// Performance middleware
namespace PivotPHP\Core\Middleware\Performance;

// HTTP protocol middleware
namespace PivotPHP\Core\Middleware\Http;

// Extend base middleware
use PivotPHP\Core\Middleware\Core\BaseMiddleware;
```

### JSON Optimization System

The framework includes a JSON pooling system that improves performance for JSON operations:

#### Automatic Optimization
- **Smart Threshold**: Automatically uses pooling for data above 256 bytes
- **Transparent Fallback**: Small data uses traditional `json_encode()` for optimal performance
- **Zero Configuration**: Works out-of-the-box with existing code

#### Manual Control
```php
// Direct pool usage
$json = JsonBufferPool::encodeWithPool($data);

// Configuration for production workloads
JsonBufferPool::configure([
    'threshold_bytes' => 256,  // Use pool only for data > 256 bytes
    'max_pool_size' => 200,
    'default_capacity' => 8192,
]);

// Real-time monitoring
$stats = JsonBufferPool::getStatistics();
// Returns: reuse_rate, total_operations, current_usage, peak_usage, pool_sizes
```

## Current Version Status

- **Current Version**: 2.0.0 (Legacy Cleanup Edition - Simplicity through Elimination)
- **Release Date**: 2025-07-21 (Quality & Maintainability Release)
- **Previous Versions**: 1.1.4 (Developer Experience), 1.1.3 (Performance Breakthrough), 1.1.2 (Consolidation), 1.1.1 (JSON Optimization), 1.1.0 (High-Performance)
- **Tests Status**: 684 CI tests + 131 integration tests (100% success rate), architectural simplification
- **Performance**: +116% framework improvement (20,400 → 44,092 ops/sec), 100% object pool reuse
- **Code Quality**: PHPStan Level 9, PSR-12 100% compliant, **zero IDE warnings**, enhanced readability
- **Architecture**: Simple classes as core defaults, deprecated complex classes removed, automatic OpenAPI/Swagger documentation
- **Compatibility**: 100% backward compatible via automatic aliases
- **Key Features**: ApiDocumentationMiddleware for automatic OpenAPI/Swagger generation, simplified core classes, enhanced developer experience

### Key Development Scripts
```bash
# Most frequently used commands
./scripts/validation/validate_all.sh          # Comprehensive project validation
./scripts/pre-commit                          # Pre-commit validation
composer quality:check                        # Quality checks (PHPStan + tests + style)
composer test                                 # Run all tests
composer cs:fix                               # Auto-fix code style
```

## Important Notes

- The framework prioritizes performance, security, and developer experience
- All HTTP components are PSR-7/PSR-15 compliant
- Service providers are the primary extension mechanism
- The event system allows for deep customization without modifying core code
- Documentation updates should be made in the `/docs` directory when adding features

### v2.0.0 Key Changes
- **🎯 Simplicity Edition**: Simple classes promoted to core defaults (PerformanceMode, LoadShedder, MemoryManager, etc.)
- **🏗️ Legacy Cleanup**: Deprecated complex classes and legacy aliases removed (breaking change vs v1.x)
- **📖 Automatic OpenAPI/Swagger Documentation**: New `ApiDocumentationMiddleware` for automatic documentation generation
- **🔄 100% Backward Compatibility**: All existing code continues to work via automatic aliases
- **⚡ Performance Maintained**: All v1.1.4 performance improvements preserved

#### 🏗️ **Architectural Simplification**
Following the "Simplicidade sobre Otimização Prematura" principle:

- **✅ Simple Classes as Core**: `PerformanceMode`, `LoadShedder`, `MemoryManager`, `PoolManager`, etc. are now the default implementations
- **✅ Clean Removal**: Deprecated and complex classes removed — codebase reduced by 18%
- **✅ Automatic Documentation**: `ApiDocumentationMiddleware` provides automatic OpenAPI/Swagger generation
- **✅ Zero Breaking Changes**: All existing code continues to work without modification through aliases
- **✅ Clean Architecture**: Focused on essential functionality without unnecessary complexity

**Key Principle**: "Simplicidade sobre Otimização Prematura" - Simple, correct code over complex "optimized" code.

#### 📖 **Automatic OpenAPI/Swagger Documentation**
The v2.0.0 introduces `ApiDocumentationMiddleware` that automatically:
- Generates OpenAPI 3.0.0 specification from all registered routes
- Provides `/docs` endpoint with JSON OpenAPI
- Provides `/swagger` endpoint with Swagger UI interface
- Generates basic path entries from route method and path (no PHPDoc parsing)
- Requires zero configuration to work

```php
// Enable automatic documentation in 3 lines
$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',
    'swagger_path' => '/swagger'
]));
```

### Architectural Foundation (v2.0.0)
- Organized middleware structure with Security, Performance, Http, and Core namespaces
- 18% code reduction — legacy aliases and deprecated classes removed
- All performance optimizations from v1.1.4 are preserved
