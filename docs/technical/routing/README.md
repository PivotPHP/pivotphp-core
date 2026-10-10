# Routing Documentation

## 📋 Overview

This directory contains comprehensive documentation for PivotPHP Core's routing system, including advanced features, static file management, and routing syntax.

## 📚 Available Documentation

### 🛣️ Core Routing
- **[router.md](router.md)** - Core routing system documentation
- **[SYNTAX_GUIDE.md](SYNTAX_GUIDE.md)** - Route syntax and patterns guide

### 📁 Static File Management
- **[STATIC_FILE_MANAGERS.md](STATIC_FILE_MANAGERS.md)** - Complete guide to static file managers
  - SimpleStaticFileManager vs StaticFileManager comparison
  - Usage examples and best practices
  - Performance benchmarks and optimization tips
  - API reference and troubleshooting

## 🚀 Quick Start

### Basic Routing
```php
use PivotPHP\Core\Core\Application;

$app = new Application();

// Simple routes
$app->get('/', function($req, $res) {
    return $res->json(['message' => 'Hello PivotPHP!']);
});

// Route with parameters
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    return $res->json(['user_id' => $id]);
});
```

### Static File Serving
```php
// Simple approach (best for <100 files)
$app->staticFiles('/assets', 'public/assets');

// Advanced approach (best for larger projects)
use PivotPHP\Routing\Router\StaticFileManager;

StaticFileManager::configure([
    'enable_cache' => true,
    'cache_control_max_age' => 86400
]);

$app->staticFiles('/public', 'public/dist');
```

## 🎯 Route Handler Syntax

### ✅ Supported Syntaxes
```php
// Closure (recommended for simple handlers)
$app->get('/api/status', function($req, $res) {
    return $res->json(['status' => 'ok']);
});

// Array callable (recommended for controllers)
$app->get('/users', [UserController::class, 'index']);

// Named function
$app->get('/health', 'healthCheck');
```

### ❌ NOT Supported
```php
// String format Controller@method - DOES NOT WORK!
$app->get('/users', 'UserController@index'); // TypeError!
```

## 📊 Performance Guide

### Route Optimization
- Use specific routes over wildcards when possible
- Consider route caching for high-traffic applications
- Group similar routes for better organization

### Static File Performance

| File Count | Recommended Manager | Memory Usage | Performance |
|------------|-------------------|--------------|-------------|
| <100 files | SimpleStaticFileManager | Linear | Excellent |
| 100+ files | StaticFileManager | Optimized | Very Good |
| 1000+ files | StaticFileManager + CDN | Minimal | Good |

## 🛡️ Security Features

### Built-in Security
- **Path traversal protection** in static file managers
- **File type validation** with configurable extensions
- **Size limitations** to prevent abuse
- **Access control** through route middleware

### Best Practices
```php
// Secure static file configuration
StaticFileManager::configure([
    'security_check' => true,              // Enable path traversal protection
    'allowed_extensions' => [              // Whitelist file types
        'css', 'js', 'png', 'jpg', 'svg'
    ],
    'max_file_size' => 10485760,          // 10MB limit
]);
```

## 📖 Advanced Topics

### Route Constraints
```php
// Numeric ID constraint
$app->get('/users/:id<\\d+>', [UserController::class, 'show']);

// UUID constraint
$app->get('/items/:uuid<[a-f0-9\\-]{36}>', [ItemController::class, 'show']);

// Custom pattern
$app->get('/archive/:year<\\d{4}>/:month<\\d{2}>', [ArchiveController::class, 'show']);
```

### Middleware Integration
```php
use PivotPHP\Routing\Router\Router;

// Route-specific middleware (4th argument)
Router::get('/admin/dashboard', [AdminController::class, 'dashboard'], [], $requireAdmin);

// Group middleware
Router::group('/api', function (): void {
    Router::get('/users', [UserController::class, 'index']);
    Router::post('/users', [UserController::class, 'store']);
}, [$rateLimit]);
```

## 🔧 Troubleshooting

### Common Issues

#### Route Not Found
1. Check route syntax and parameter patterns
2. Verify handler callable format
3. Ensure proper method (GET, POST, etc.)

#### Static Files Not Served
1. Check file permissions and paths
2. Verify allowed extensions configuration
3. Monitor file size limits

#### Performance Issues
1. Consider switching static file managers
2. Enable caching for production
3. Use route grouping for organization

### Debug Tools
```php
// Get route information
$router = $app->getRouter();
$routes = $router->getRoutes();

// Static file statistics
$stats = StaticFileManager::getStats();
print_r($stats);
```

## 📚 Further Reading

- **[Core Framework Documentation](../../README.md)** - Main documentation
- **[Middleware Guide](../middleware/README.md)** - Middleware system
- **[API Reference](../../API_REFERENCE.md)** - Complete API documentation

---

**PivotPHP Core Routing - Flexible, Fast, and Secure** 🛣️