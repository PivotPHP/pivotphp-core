# PivotPHP Core v2.0.0 - Complete Examples Collection 🚀

This directory contains production-ready examples that demonstrate the full potential of PivotPHP Core v2.0.0, including simplified performance mode and clean architecture.

## 🎯 What's New in v2.0.0

- **Legacy Cleanup**: 18% code reduction (11,871 lines removed)
- **Modern Namespaces**: Eliminated 110 legacy aliases (PSR-15, Simple*, v1.1.x)
- **Better Performance**: 59% fewer aliases to autoload
- **Zero Regressions**: All 5,548 tests passing (100%)
- **Breaking Changes**: Namespace updates required (see migration guide)
- **Cleaner Architecture**: Removed deprecated classes and complex features

## 📁 Examples Structure

### 01-basics - Fundamentals
- **hello-world.php** - Simplest possible example
- **basic-routes.php** - GET, POST, PUT, DELETE with complete CRUD
- **request-response.php** - Advanced Request/Response handling
- **json-api.php** - JSON API with validation and consistent structures

### 02-routing - Advanced Routing
- **regex-routing.php** - Custom regex routes with pattern validation
- **route-parameters.php** - Required, optional parameters and query strings
- **route-groups.php** - Route grouping with shared middleware
- **route-constraints.php** - Advanced constraints and parameter validation
- **static-files.php** - NEW! `$app->staticFiles()` and `$app->static()` demonstration

### 03-middleware - Custom Middleware
- **custom-middleware.php** - Custom middleware (logging, validation, transformation)
- **middleware-stack.php** - Complex stacks and execution order
- **auth-middleware.php** - JWT, API Key, Session and Basic Auth
- **cors-middleware.php** - Advanced CORS with dynamic policies

### 04-api - Complete APIs
- **rest-api.php** - Complete RESTful API with pagination, filters and validation

### 05-performance - Performance & Optimization
- **high-performance.php** - v2.0.0 simplified performance mode, JSON optimization, metrics

### 06-security - Security
- **jwt-auth.php** - Complete JWT system with refresh tokens and authorization

### 07-advanced - v2.0.0 Advanced Features
- **array-callables.php** - Array callable syntax demonstration

## 🚀 Quick Start

### Prerequisites
```bash
# Ensure you're in the project root
cd /path/to/pivotphp-core
composer install
```

### Running Examples

#### v2.0.0 Features
```bash
# Simplified performance mode
php -S localhost:8000 examples/05-performance/high-performance.php
curl http://localhost:8000/enable-high-performance  # Enable simplified performance mode
curl http://localhost:8000/metrics                  # Real-time performance data

# Array callable syntax
php -S localhost:8000 examples/07-advanced/array-callables.php
curl http://localhost:8000/users                    # Instance method callable
curl http://localhost:8000/admin/dashboard          # Static method callable
```

#### 🔥 Popular Examples
```bash
# Hello World - Start here
php -S localhost:8000 examples/01-basics/hello-world.php
curl http://localhost:8000/

# Complete CRUD API
php -S localhost:8000 examples/01-basics/basic-routes.php
curl -X POST http://localhost:8000/users -H "Content-Type: application/json" -d '{"name":"John"}'

# Static files and routes (NEW!)
php -S localhost:8000 examples/02-routing/static-files.php
curl http://localhost:8000/public/test.json          # File serving
curl http://localhost:8000/api/static/health         # Pre-compiled response

# Advanced routing with regex
php -S localhost:8000 examples/02-routing/regex-routing.php
curl http://localhost:8000/users/123
curl http://localhost:8000/products/my-awesome-product

# JWT Authentication
php -S localhost:8000 examples/06-security/jwt-auth.php
curl -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"username":"admin","password":"secret123"}'
```

### Example Structure
Each example file contains:
- ✅ **Complete functional code** - Ready to run
- 📝 **Detailed explanatory comments** - Inline documentation
- 🧪 **Test instructions** - Ready-to-use curl commands
- 🎯 **Real-world use cases** - Practical implementation examples
- ⚡ **v2.0.0 features** - Latest framework capabilities
- 🔒 **Best practices** - Security and performance guidelines

## 🎯 Featured Examples

### Simplified Performance Mode (v2.0.0)
```php
use PivotPHP\Core\Performance\PerformanceMode;

// Simplified performance mode
PerformanceMode::enable(PerformanceMode::PROFILE_PRODUCTION);

$app->get('/api/data', function($req, $res) {
    $largeDataset = Database::getAllRecords(); // 1000+ records
    return $res->json($largeDataset); // Automatically uses buffer pooling!
});
```

### ✅ Array Callables (v2.0.0)
```php
class UserController {
    public function index($req, $res) {
        return $res->json(['users' => User::all()]);
    }
}

// MAINTAINED: Array callable syntax
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [$controller, 'store']);
```

### 🔐 Robust Security
```php
// JWT with refresh tokens
$app->post('/auth/login', function($req, $res) {
    $credentials = $req->getBodyAsStdClass();
    $tokens = AuthService::authenticate($credentials);
    return $res->json($tokens);
});
```

### 🎯 Advanced Routing
```php
// Regex constraints with parameters
$app->get('/users/:id<\\d+>', function($req, $res) {
    $userId = $req->param('id'); // Guaranteed to be numeric
    return $res->json(['user' => User::find($userId)]);
});
```

## 📊 Performance Showcase

### v2.0.0 Performance
- **Framework Throughput**: 44,092 ops/sec (+116% vs previous architecture)
- **Object Pool Reuse**: 100% (Request), 99.9% (Response)
- **JSON Operations**: Automatic threshold at 256 bytes — small data via `json_encode()`, large data via pooling
- **Docker Validated**: 6,227 req/sec in standardized containers (3rd place competitive)
- **Architecture**: Simplified following "Simplicidade sobre Otimização Prematura", 18% code reduction

### Docker Framework Comparison
| Framework | Performance | Position |
|-----------|-------------|----------|
| Slim 4 | 6,881 req/sec | 1st |
| Lumen | 6,322 req/sec | 2nd |
| **PivotPHP** | **6,227 req/sec** | **3rd** |
| Flight | 3,179 req/sec | 4th |

*PivotPHP: 9.5% behind leader, 96% faster than Flight*

## 🛠️ Available Features Demo

### Express.js-Inspired API
- Familiar and intuitive syntax
- Zero configuration to get started
- PSR-7/PSR-15 compliance

### v2.0.0 Performance Features
- Simplified `PerformanceMode` (replaces `HighPerformanceMode`)
- Automatic JSON buffer pooling with 256-byte threshold
- Object pooling for Request/Response (100%/99.9% reuse)
- Smart garbage collection

### Robust Security
- JWT with refresh tokens
- Flexible authentication middleware
- Dynamic and configurable CORS
- XSS and CSRF protection

### Advanced Routing
- Custom regex with validation
- Optional parameters and wildcards
- Constraints and automatic validation
- Route groups and middleware

### Powerful Middleware
- Configurable middleware stack
- Data transformation and validation
- Integrated logging and monitoring
- Performance and security middleware

### Production-Ready APIs
- Complete RESTful with pagination
- Automatic input validation
- Correct headers and status codes
- Error handling and logging

## 🆘 Support & Documentation

- 📚 **Documentation**: [Complete guides](../docs/README.md)
- 🐛 **Issues**: [GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)
- 🚀 **Quick Start**: [5-minute setup guide](../docs/quick-start.md)

## 📋 Examples Summary

| Category | Key Features Demonstrated |
|----------|---------------------------|
| **01-basics** | Hello World, CRUD, Request/Response, JSON API |
| **02-routing** | Regex, Parameters, Groups, Constraints, Static Files |
| **03-middleware** | Custom, Stack, Complete Auth, CORS |
| **04-api** | Complete REST with pagination and filters |
| **05-performance** | Simplified Performance Mode v2.0.0 |
| **06-security** | Complete JWT with refresh tokens |
| **07-advanced** | Array callables |
| **08-json-optimization** | JSON buffer pooling optimization |
| **09-error-handling** | Enhanced error diagnostics |

## 🔄 Migration from Previous Versions

### From v1.x to v2.0.0
```php
// OLD: HighPerformanceMode (removed in v2.0.0)
use PivotPHP\Core\Performance\HighPerformanceMode;
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME); // Class removed

// NEW: Simplified PerformanceMode
use PivotPHP\Core\Performance\PerformanceMode;
PerformanceMode::enable(PerformanceMode::PROFILE_PRODUCTION);
```

### Breaking Changes (v2.0.0)
- Legacy namespace aliases removed — update `use` statements to current namespaces
- `HighPerformanceMode` replaced by `PerformanceMode`
- Deprecated and complex classes removed from the codebase (18% reduction)
- See [Migration Guide](../docs/MIGRATION_GUIDE.md) for complete list

## 🎯 Best Practices Demonstrated

1. **Security First**: All examples include proper input validation and security headers
2. **Performance Optimized**: Leverages v2.0.0 simplified optimizations
3. **Type Safety**: PHP 8.1+ features with strict typing
4. **PSR Compliance**: PSR-7, PSR-15, PSR-12 standards followed
5. **Real-World Ready**: Production-grade error handling and logging

---

**PivotPHP Core v2.0.0** - Express.js for PHP with simplified architecture! 🐘⚡
**Examples updated:** 2025
