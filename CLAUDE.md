# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

PivotPHP Core is a high-performance PHP microframework inspired by Express.js, designed for building APIs and web applications. Current version: 2.0.1 (Simplicity Edition - Simplicidade sobre Otimização Prematura).

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
composer examples:basic        # Basic framework usage
composer examples:auth        # Authentication example
composer examples:middleware  # Middleware example
```

### v1.2.0 Simplicity Edition Features
```php
// Array callable support (PHP 8.4+ compatible)
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [$controller, 'store']);
// Router methods now accept callable|array union types

// NOVO v1.2.0: Documentação OpenAPI/Swagger Automática
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;

$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',        // JSON OpenAPI endpoint
    'swagger_path' => '/swagger',  // Swagger UI interface
    'base_url' => 'http://localhost:8080'
]));

// Suas rotas com documentação PHPDoc
$app->get('/users', function($req, $res) {
    /**
     * @summary List all users
     * @description Returns a list of all users in the system
     * @tags Users
     * @response 200 array List of users
     */
    return $res->json(['users' => User::all()]);
});

// Acesse: http://localhost:8080/swagger (Interface Swagger UI)
// Acesse: http://localhost:8080/docs (JSON OpenAPI 3.0.0)

// Optimized object pooling (+116% performance improvement)
// Request pool reuse: 0% → 100%
// Response pool reuse: 0% → 99.9%
// Framework throughput: 20,400 → 44,092 ops/sec

// Organized middleware structure (v1.1.2)
use PivotPHP\Core\Middleware\Security\CsrfMiddleware;
use PivotPHP\Core\Middleware\Performance\RateLimitMiddleware;
use PivotPHP\Core\Middleware\Http\CorsMiddleware;

// Backward compatibility maintained via aliases
use PivotPHP\Core\Http\Psr15\Middleware\CsrfMiddleware; // Still works
use PivotPHP\Core\Support\Arr; // Still works, now points to Utils\Arr

// Consolidated utilities
use PivotPHP\Core\Utils\Arr;
$result = Arr::get($array, 'nested.key', 'default');
$shuffled = Arr::shuffle($array); // Preserves keys

// JSON optimization (v1.1.1 feature, maintained)
use PivotPHP\Core\Json\Pool\JsonBufferPool;
$json = JsonBufferPool::encodeWithPool($data);
$stats = JsonBufferPool::getStatistics();

// High-performance mode (v1.1.0 feature, maintained)
use PivotPHP\Core\Performance\HighPerformanceMode;
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
```

## Code Architecture

### Project Structure
```
pivotphp-core/
├── src/                    # Framework source code
│   ├── Core/              # Application core, container, services
│   ├── Http/              # HTTP layer (Request, Response, PSR-7)
│   ├── Routing/           # Router and route management
│   ├── Middleware/        # Middleware system (Security, Performance, HTTP, Core)
│   ├── Providers/         # Service providers
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
- **Container**: Dependency injection container at the heart of the framework (`src/Core/Container.php`)
- **Event-Driven**: Event dispatcher with hooks system for extensibility

### Key Components
1. **Application Core** (`src/Core/Application.php`): Main application class that bootstraps the framework
   - Version constant: `Application::VERSION`
   - Middleware aliases mapping for compatibility

2. **Router** (`src/Routing/Router.php`): High-performance routing with middleware support
   - Supports regex constraints: `/users/:id<\d+>`
   - Predefined shortcuts: `slug`, `uuid`, `date`, etc.

3. **Middleware Pipeline** (`src/Middleware/`): PSR-15 compliant middleware system organized by responsibility
   - **Security**: `src/Middleware/Security/` - AuthMiddleware, CsrfMiddleware, XssMiddleware, SecurityHeadersMiddleware
   - **Performance**: `src/Middleware/Performance/` - CacheMiddleware, RateLimitMiddleware
   - **HTTP**: `src/Middleware/Http/` - CorsMiddleware, ErrorMiddleware
   - **Core**: `src/Middleware/Core/` - BaseMiddleware, MiddlewareInterface
   - **Advanced**: LoadShedder, TrafficClassifier

4. **HTTP Layer** (`src/Http/`): PSR-7 hybrid implementation
   - Express.js style API with PSR-7 compliance
   - Object pooling via `OptimizedHttpFactory` and `DynamicPoolManager`

5. **Performance Components**:
   - **JSON Optimization**: `JsonBufferPool`, `JsonBuffer` (v1.1.1)
   - **Pool Management**: `DynamicPoolManager` (consolidated in v1.1.2)
   - **Memory Management**: `MemoryManager`
   - **Performance Monitoring**: `PerformanceMonitor` (unified in v1.1.2)
   - **Distributed Coordination**: `DistributedPoolManager`

### v1.1.4 Major Improvements & v1.1.2 Architectural Foundation
v1.1.4 delivers major performance breakthroughs built on the v1.1.2 consolidated architecture:

#### Middleware Organization
```
src/Middleware/
├── Security/              # Security-focused middlewares
│   ├── AuthMiddleware.php
│   ├── CsrfMiddleware.php
│   ├── SecurityHeadersMiddleware.php
│   └── XssMiddleware.php
├── Performance/           # Performance-focused middlewares
│   ├── CacheMiddleware.php
│   └── RateLimitMiddleware.php
├── Http/                 # HTTP protocol middlewares
│   ├── CorsMiddleware.php
│   └── ErrorMiddleware.php
└── Core/                 # Base middleware infrastructure
    ├── BaseMiddleware.php
    └── MiddlewareInterface.php
```

#### v1.1.4 Performance Optimizations
- **Object Pool Crisis Fixed**: Revolutionized pool reuse from 0% to 100% (Request) and 99.9% (Response)
- **Array Callable Support**: Full PHP 8.4+ compatibility with `callable|array` union types in Router
- **Framework Performance**: +116% improvement (20,400 → 44,092 ops/sec)
- **Test Suite Stabilization**: 100% PSR-12 compliance, PHPUnit 10 compatibility, zero violations

#### v1.1.2 Foundation (Eliminated Duplications)
- **Support/Arr.php**: Removed, consolidated into `Utils/Arr.php`
- **PerformanceMonitor**: Consolidated from multiple locations into `Performance/PerformanceMonitor.php`
- **DynamicPool**: Unified as `DynamicPoolManager` in `Http/Pool/`

#### Backward Compatibility
- **12 automatic aliases** maintain 100% compatibility with existing code
- Old namespace imports continue working transparently
- Migration to new structure is optional but recommended

### Request/Response Adapter Pattern Architecture (v2.0.1 - Dec 2025)

**Major Architectural Improvement**: The HTTP layer has been migrated from inheritance to adapter pattern:

**New Architecture**:
- `ExpressRequest` composes `ServerRequestInterface` (composition over inheritance)
- `ExpressResponse` composes `ResponseInterface` (composition over inheritance)
- **Single source of truth**: All data stored exclusively in PSR-7 objects
- **Zero duplication**: No data synchronization issues
- **Full PSR-7 compliance**: Via delegation pattern
- **Express.js API preserved**: Developer-friendly methods maintained

**Performance** (AdapterPatternBenchmark.php):
- Request Creation: **30,793 ops/sec** (0.032 ms)
- Response Creation: **366,555 ops/sec** (0.003 ms)
- Full Cycle: **38,984 ops/sec** (0.026 ms)
- Memory per Object: **2.32 KB**

**Backward Compatibility**:
- 100% backward compatible via type aliases
- Middleware uses: `use ExpressRequest as Request; use ExpressResponse as Response;`
- All existing code works without modification

**Documentation**: [Adapter Pattern Architecture](docs/technical/http/adapter-pattern-architecture.md)

### Testing Approach
- Tests organized by domain in `tests/` directory (see phpunit.xml for test suites)
- Three main test suites: Core Tests, Security Tests, and full Express PHP Test Suite
- Each major component has its own test suite
- Integration tests verify component interaction
- **v1.1.2 Achievement**: 100% test success rate (430/430 tests passing)
- Enhanced test maintainability with constants instead of hardcoded values
- Comprehensive stress testing in `tests/Stress/`
- JSON optimization tests in `tests/Json/Pool/`

### Code Style Requirements
- PHP 8.1+ features are used throughout
- Strict typing is enforced
- **PHPStan Level 9** must pass (zero errors tolerance)
- **PSR-12** coding standard via PHP_CodeSniffer
- All new code must include proper type declarations

### Performance Considerations
- Framework optimized for high throughput (48,323 ops/sec average in v1.1.2)
- v1.1.0 achieves 25x faster Request/Response creation with pooling
- v1.1.1 provides automatic JSON optimization with 161K ops/sec (small), 17K ops/sec (medium), 1.7K ops/sec (large)
- v1.1.2 maintains performance while reducing codebase size by 3.1%
- Benchmark any performance-critical changes using `composer benchmark`
- Avoid unnecessary object creation in hot paths
- Use lazy loading for optional dependencies

## Route Handler Syntax

PivotPHP Core supports the following route handler syntaxes (v1.1.4 adds full array callable support):

### ✅ Supported Syntaxes
```php
// Closure/Anonymous function (Recommended)
$app->get('/users', function($req, $res) {
    return $res->json(['users' => []]);
});

// Array callable with class (NEW: Enhanced in v1.1.4)
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

**v1.1.4 Improvements**: Router methods now use `callable|array` union types for PHP 8.4+ strict typing compatibility.

**Important**: The framework validates that all handlers are `callable`. Strings in the format `Controller@method` are not considered callable by PHP and will result in a TypeError.

**Migration**: Replace `'Controller@method'` with `[Controller::class, 'method']` in all documentation examples.

## Development Workflow

1. Before committing, run `./scripts/pre-commit` or `./scripts/validation/validate_all.sh`
2. All tests must pass before pushing changes
3. Static analysis must pass at Level 9
4. Code style must comply with PSR-12
5. For releases, use `./scripts/release/prepare_release.sh` followed by `./scripts/release/release.sh`

### Array Callable Testing (v1.1.4)
When implementing array callable routes, verify compatibility:

```bash
# Test array callable functionality
vendor/bin/phpunit tests/Unit/Routing/RouterArrayCallableTest.php
vendor/bin/phpunit tests/Integration/Routing/ArrayCallableIntegrationTest.php

# Test parameter routing with array callables
vendor/bin/phpunit tests/Examples/ParameterRoutingExampleTest.php
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

# Memory usage analysis
vendor/bin/phpunit tests/Performance/MemoryManagerTest.php

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

### JSON Optimization System (v1.1.1)

The framework includes a sophisticated JSON pooling system that dramatically improves performance for JSON operations:

#### Automatic Optimization
- **Smart Detection**: Automatically uses pooling for datasets that benefit (arrays 10+ elements, objects 5+ properties, strings >1KB)
- **Transparent Fallback**: Small data uses traditional `json_encode()` for optimal performance
- **Zero Configuration**: Works out-of-the-box with existing code

#### Performance Characteristics
- **Throughput**: 161K ops/sec (small), 17K ops/sec (medium), 1.7K ops/sec (large) in Docker testing
- **Reuse Rate**: 100% buffer reuse in high-frequency scenarios
- **Memory Efficiency**: Significant reduction in garbage collection pressure
- **Scalability**: Adaptive pool sizing based on usage patterns

#### Manual Control
```php
// Direct pool usage
$json = JsonBufferPool::encodeWithPool($data);

// Configuration for production workloads
JsonBufferPool::configure([
    'max_pool_size' => 200,
    'default_capacity' => 8192,
    'size_categories' => [
        'small' => 2048,   // 2KB
        'medium' => 8192,  // 8KB
        'large' => 32768,  // 32KB
        'xlarge' => 131072 // 128KB
    ]
]);

// Real-time monitoring
$stats = JsonBufferPool::getStatistics();
// Returns: reuse_rate, total_operations, current_usage, peak_usage, pool_sizes
```

## Current Version Status

- **Current Version**: 1.2.0 (Simplicity Edition - Simplicidade sobre Otimização Prematura)
- **Release Date**: 2025-07-21 (Quality & Maintainability Release)
- **Previous Versions**: 1.1.4 (Developer Experience), 1.1.3 (Performance Breakthrough), 1.1.2 (Consolidation), 1.1.1 (JSON Optimization), 1.1.0 (High-Performance)
- **Tests Status**: 684 CI tests + 131 integration tests (100% success rate), architectural simplification
- **Performance**: +116% framework improvement (20,400 → 44,092 ops/sec), 100% object pool reuse
- **Code Quality**: PHPStan Level 9, PSR-12 100% compliant, **zero IDE warnings**, enhanced readability
- **Architecture**: Simple classes as core defaults, Legacy namespace for complex classes, automatic OpenAPI/Swagger documentation
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

### v1.2.0 Key Changes
- **🎯 Simplicity Edition**: Simple classes promoted to core defaults (PerformanceMode, LoadShedder, MemoryManager, etc.)
- **🏗️ Legacy Architecture**: Complex classes moved to `src/Legacy/` namespace for backward compatibility
- **📖 Automatic OpenAPI/Swagger Documentation**: New `ApiDocumentationMiddleware` for automatic documentation generation
- **🔄 100% Backward Compatibility**: All existing code continues to work via automatic aliases
- **⚡ Performance Maintained**: All v1.1.4 performance improvements preserved

#### 🏗️ **Architectural Simplification**
Following the "Simplicidade sobre Otimização Prematura" principle:

- **✅ Simple Classes as Core**: `PerformanceMode`, `LoadShedder`, `MemoryManager`, `PoolManager`, etc. are now the default implementations
- **✅ Legacy Namespace**: Complex classes moved to `src/Legacy/` for those who need advanced features
- **✅ Automatic Documentation**: `ApiDocumentationMiddleware` provides automatic OpenAPI/Swagger generation
- **✅ Zero Breaking Changes**: All existing code continues to work without modification through aliases
- **✅ Clean Architecture**: Focused on essential functionality without unnecessary complexity

**Key Principle**: "Simplicidade sobre Otimização Prematura" - Simple, correct code over complex "optimized" code.

#### 📖 **Automatic OpenAPI/Swagger Documentation**
The v1.2.0 introduces `ApiDocumentationMiddleware` that automatically:
- Generates OpenAPI 3.0.0 specification from all routes
- Provides `/docs` endpoint with JSON OpenAPI
- Provides `/swagger` endpoint with Swagger UI interface
- Parses PHPDoc comments for route metadata
- Requires zero configuration to work

```php
// Enable automatic documentation in 3 lines
$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',
    'swagger_path' => '/swagger'
]));
```

### Architectural Foundation (v1.1.2+)
- Organized middleware structure while maintaining full backward compatibility
- All performance optimizations from v1.1.1 and v1.1.0 are preserved and enhanced
- Migration to new namespace structure is recommended but optional
