# PivotPHP Core v1.1.3 - Examples Reference

This comprehensive guide showcases all available examples in the PivotPHP Core framework, organized by complexity and use case.

## 🎯 Quick Navigation

### 🆕 v1.1.3 New Features
- [Array Callables Demo](../../examples/07-advanced/array-callables.php) - NEW! Array callable syntax
- [Performance Showcase](../../examples/07-advanced/performance-v1.1.3.php) - NEW! +116% performance improvements

### 📚 Learning Path
- [Hello World](../../examples/01-basics/hello-world.php) - Start here
- [Basic CRUD](../../examples/01-basics/basic-routes.php) - Essential operations
- [Array Callables](../../examples/07-advanced/array-callables.php) - Modern syntax
- [Performance Demo](../../examples/07-advanced/performance-v1.1.3.php) - Optimization showcase

## 📁 Complete Examples Catalog

### 01-basics - Foundation Examples

#### hello-world.php
**Purpose**: Simplest possible PivotPHP application
**Features**: Basic routing, JSON response
**Run**: `php -S localhost:8000 examples/01-basics/hello-world.php`

```php
$app = new Application();
$app->get('/', function($req, $res) {
    return $res->json(['message' => 'Hello, PivotPHP!']);
});
$app->run();
```

#### basic-routes.php
**Purpose**: Complete CRUD operations
**Features**: GET, POST, PUT, DELETE methods
**Run**: `php -S localhost:8000 examples/01-basics/basic-routes.php`

```bash
# Test CRUD operations
curl http://localhost:8000/users                           # GET all
curl -X POST http://localhost:8000/users \
     -H "Content-Type: application/json" \
     -d '{"name":"John"}'                                   # CREATE
curl http://localhost:8000/users/1                         # GET one
curl -X PUT http://localhost:8000/users/1 \
     -H "Content-Type: application/json" \
     -d '{"name":"John Updated"}'                           # UPDATE
curl -X DELETE http://localhost:8000/users/1               # DELETE
```

#### request-response.php
**Purpose**: Advanced Request/Response handling
**Features**: Headers, body parsing, parameter extraction
**Run**: `php -S localhost:8000 examples/01-basics/request-response.php`

#### json-api.php
**Purpose**: JSON API with validation
**Features**: Structured responses, error handling
**Run**: `php -S localhost:8000 examples/01-basics/json-api.php`

### 02-routing - Advanced Routing

#### regex-routing.php
**Purpose**: Custom regex route patterns
**Features**: Parameter validation, custom constraints
**Run**: `php -S localhost:8000 examples/02-routing/regex-routing.php`

```bash
curl http://localhost:8000/users/123                       # Numeric ID only
curl http://localhost:8000/products/my-awesome-product     # Slug format
curl http://localhost:8000/api/v1/posts/2024/01/15        # Date format
```

#### route-parameters.php
**Purpose**: Parameter handling and query strings
**Features**: Required/optional params, wildcards
**Run**: `php -S localhost:8000 examples/02-routing/route-parameters.php`

#### route-groups.php
**Purpose**: Route grouping with shared middleware
**Features**: Group prefixes, middleware inheritance
**Run**: `php -S localhost:8000 examples/02-routing/route-groups.php`

#### route-constraints.php
**Purpose**: Advanced parameter constraints
**Features**: Custom validation, error handling
**Run**: `php -S localhost:8000 examples/02-routing/route-constraints.php`

#### static-files.php
**Purpose**: Demonstrates both static file serving and optimized static routes
**Features**: `$app->staticFiles()` for file serving, `$app->static()` for pre-compiled responses
**Run**: `php -S localhost:8000 examples/02-routing/static-files.php`

```bash
# Static file serving (from disk)
curl http://localhost:8000/public/test.json              # File from disk
curl http://localhost:8000/assets/app.css               # CSS file from disk

# Static routes (pre-compiled responses)
curl http://localhost:8000/api/static/health            # Optimized response
curl http://localhost:8000/api/static/version           # Pre-compiled data
curl http://localhost:8000/static-info                  # Implementation details
```

### 03-middleware - Middleware Systems

#### custom-middleware.php
**Purpose**: Building custom middleware
**Features**: Logging, validation, transformation
**Run**: `php -S localhost:8000 examples/03-middleware/custom-middleware.php`

#### middleware-stack.php
**Purpose**: Complex middleware stacks
**Features**: Execution order, pipeline management
**Run**: `php -S localhost:8000 examples/03-middleware/middleware-stack.php`

#### auth-middleware.php
**Purpose**: Authentication systems
**Features**: JWT, API Key, Session, Basic Auth
**Run**: `php -S localhost:8000 examples/03-middleware/auth-middleware.php`

#### cors-middleware.php
**Purpose**: CORS configuration
**Features**: Dynamic policies, preflight handling
**Run**: `php -S localhost:8000 examples/03-middleware/cors-middleware.php`

### 04-api - Complete API Examples

#### rest-api.php
**Purpose**: Production-ready RESTful API
**Features**: Pagination, filtering, validation, error handling
**Run**: `php -S localhost:8000 examples/04-api/rest-api.php`

```bash
# Test RESTful API features
curl "http://localhost:8000/api/v1/products?page=1&limit=10"
curl "http://localhost:8000/api/v1/products?category=electronics&sort=price"
curl -X POST http://localhost:8000/api/v1/products \
     -H "Content-Type: application/json" \
     -d '{"name":"New Product","price":99.99}'
```

### 05-performance - Performance Optimization

#### high-performance.php
**Purpose**: Performance features showcase
**Features**: Object pooling, JSON optimization, monitoring
**Run**: `php -S localhost:8000 examples/05-performance/high-performance.php`

```bash
curl http://localhost:8000/enable-high-performance?profile=HIGH
curl http://localhost:8000/performance/metrics
curl http://localhost:8000/performance/json-test
```

### 06-security - Security Features

#### jwt-auth.php
**Purpose**: Complete JWT authentication system
**Features**: Login, refresh tokens, protected routes
**Run**: `php -S localhost:8000 examples/06-security/jwt-auth.php`

```bash
# Test JWT authentication flow
curl -X POST http://localhost:8000/auth/login \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"secret123"}'

# Use token from response
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/protected/profile
```

### 07-advanced - v1.1.3 New Features ✨

#### array-callables.php
**Purpose**: Array callable syntax demonstration
**Features**: Instance methods, static methods, controller organization
**Run**: `php -S localhost:8000 examples/07-advanced/array-callables.php`

```php
// NEW: Array callable syntax
class UserController {
    public function index($req, $res) {
        return $res->json(['users' => User::all()]);
    }
}

$app->get('/users', [UserController::class, 'index']);      // Static
$app->post('/users', [$controller, 'store']);               // Instance
```

```bash
# Test array callables
curl http://localhost:8000/users                           # Instance method
curl http://localhost:8000/admin/dashboard                 # Static method
curl -X POST http://localhost:8000/users \
     -H "Content-Type: application/json" \
     -d '{"name":"John","email":"john@example.com"}'
```

#### performance-v1.1.3.php
**Purpose**: v1.1.3 performance improvements showcase
**Features**: +116% framework improvement, object pool metrics
**Run**: `php -S localhost:8000 examples/07-advanced/performance-v1.1.3.php`

```bash
# Test performance features
curl http://localhost:8000/performance/metrics             # Real-time metrics
curl http://localhost:8000/performance/json/large          # JSON optimization
curl http://localhost:8000/performance/stress-test         # Framework stress test
curl http://localhost:8000/performance/pool-stats          # Object pool stats
curl http://localhost:8000/performance/benchmark           # Framework comparison
```

## 🎯 Use Case Examples

### Quick Prototyping
**Start with**: hello-world.php → basic-routes.php
**Time**: 5-10 minutes
**Use case**: Rapid API prototyping

### Production API
**Path**: basic-routes.php → rest-api.php → jwt-auth.php
**Time**: 1-2 hours
**Use case**: Full-featured production API

### High Performance Application
**Path**: high-performance.php → performance-v1.1.3.php
**Time**: 30 minutes
**Use case**: Performance-critical applications

### Modern PHP Development
**Path**: array-callables.php
**Time**: 15 minutes
**Use case**: Clean, modern PHP 8.4+ syntax

## 📊 Performance Examples Summary

### Framework Performance (v1.1.3)
- **Baseline (v1.1.2)**: 20,400 ops/sec
- **Current (v1.1.3)**: 44,092 ops/sec
- **Improvement**: +116%
- **Demo**: performance-v1.1.3.php

### JSON Optimization
- **Small datasets**: 505K ops/sec
- **Medium datasets**: 119K ops/sec
- **Large datasets**: 214K ops/sec
- **Demo**: performance-v1.1.3.php → /performance/json/{size}

### Object Pool Efficiency
- **Request pool reuse**: 0% → 100%
- **Response pool reuse**: 0% → 99.9%
- **Demo**: performance-v1.1.3.php → /performance/pool-stats

## 🔧 Testing Guidelines

### Basic Testing
```bash
# 1. Start example server
php -S localhost:8000 examples/path/to/example.php

# 2. Test in another terminal
curl http://localhost:8000/
```

### Advanced Testing
```bash
# POST with JSON
curl -X POST http://localhost:8000/endpoint \
     -H "Content-Type: application/json" \
     -d '{"key":"value"}'

# Authentication header
curl -H "Authorization: Bearer TOKEN" \
     http://localhost:8000/protected/endpoint

# Query parameters
curl "http://localhost:8000/api/data?page=1&limit=10&sort=name"
```

### Performance Testing
```bash
# Simple load test
for i in {1..100}; do
  curl -s http://localhost:8000/api/endpoint > /dev/null
done

# Benchmark with ab (if available)
ab -n 1000 -c 10 http://localhost:8000/api/endpoint
```

## 🎓 Learning Recommendations

### Beginner (New to PivotPHP)
1. **hello-world.php** - Understand basic structure
2. **basic-routes.php** - Learn CRUD operations
3. **request-response.php** - Master request handling
4. **json-api.php** - Build proper APIs

### Intermediate (Building Production Apps)
1. **rest-api.php** - Complete API patterns
2. **auth-middleware.php** - Security implementation
3. **cors-middleware.php** - Cross-origin setup
4. **jwt-auth.php** - Authentication systems

### Advanced (Framework Mastery)
1. **array-callables.php** - Modern syntax patterns
2. **performance-v1.1.3.php** - Performance optimization
3. **custom-middleware.php** - Extending the framework
4. **middleware-stack.php** - Complex architectures

## 🆘 Troubleshooting Examples

### Common Issues

#### Autoload Path Errors
```php
// Correct path from examples directory
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
```

#### Port Already in Use
```bash
# Use different port
php -S localhost:8001 examples/path/to/example.php
```

#### JSON Content-Type Issues
```bash
# Always include Content-Type for JSON
curl -X POST http://localhost:8000/api/endpoint \
     -H "Content-Type: application/json" \
     -d '{"data":"value"}'
```

### Getting Help
- **Example not working?** Check the inline comments for setup instructions
- **Found a bug?** [Report on GitHub](https://github.com/PivotPHP/pivotphp-core/issues)

---

**Total Examples**: 17 comprehensive examples covering all framework features
**Updated for**: PivotPHP Core v1.1.3 with latest performance improvements
