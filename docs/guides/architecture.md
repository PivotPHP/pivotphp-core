# PivotPHP Core v1.1.3 Architecture Guide (Historical)

> ⚠️ **Documento histórico:** este guia descreve a arquitetura da v1.1.3 (2025). A estrutura
> de middleware (Security/Performance/Http/Core) ainda é válida na v2.1.1 atual, mas o guia
> **não reflete** mudanças arquiteturais posteriores — em especial a externalização do
> roteador para o pacote `pivotphp/core-routing` na v2.0.0 (o "Router Architecture" abaixo
> descreve um roteador que hoje vive fora deste repositório, ver `CLAUDE.md`), o Legacy
> Cleanup da v2.0.0, e o ciclo de depreciação iniciado na v2.1.0
> (`Core\Container`, `LoadShedder`/`RateLimitMiddleware`, `Request::getIp()`,
> `Providers\Logger`/`EventDispatcher`). Para o estado atual da arquitetura, veja
> [`CLAUDE.md`](../../CLAUDE.md), [`README.md`](../../README.md) e
> [`docs/API_REFERENCE.md`](../API_REFERENCE.md).

This guide provides a comprehensive overview of PivotPHP Core v1.1.3 architecture, highlighting the significant improvements made in this release following our **ARCHITECTURAL_GUIDELINES** principle of "Simplicidade sobre Otimização Prematura" (Simplicity over Premature Optimization).

## 🏗️ Architecture Overview

PivotPHP Core follows a **modular, event-driven architecture** inspired by Express.js while maintaining strict PHP typing and PSR compliance.

```
┌─────────────────────────────────────────────────────────────┐
│                      Application Layer                      │
├─────────────────────────────────────────────────────────────┤
│  Bootstrap │ Config │ Service Providers │ Event Dispatcher │
├─────────────────────────────────────────────────────────────┤
│                     Middleware Pipeline                     │
├─────────────────────────────────────────────────────────────┤
│   Security   │  Performance  │    HTTP    │      Core      │
├─────────────────────────────────────────────────────────────┤
│                        HTTP Layer                          │
├─────────────────────────────────────────────────────────────┤
│   Request    │   Response    │ PSR-7/15  │  Object Pools  │
├─────────────────────────────────────────────────────────────┤
│                       Routing System                       │
├─────────────────────────────────────────────────────────────┤
│  URL Router  │ Route Cache │ Parameters │ Array Callables │
├─────────────────────────────────────────────────────────────┤
│                     Core Components                        │
├─────────────────────────────────────────────────────────────┤
│  Container   │   Events   │   Cache   │  Memory Manager  │
└─────────────────────────────────────────────────────────────┘
```

## 🎯 v1.1.3 Architectural Improvements

### 1. **Middleware Architecture Reorganization**

**Before v1.1.3** (Scattered):
```
src/Http/Psr15/Middleware/
├── CorsMiddleware.php
├── ErrorMiddleware.php  
├── SecurityMiddleware.php
└── [Mixed responsibilities]
```

**v1.1.3** (Organized by Responsibility):
```
src/Middleware/
├── Security/              # Security-focused middleware
│   ├── AuthMiddleware.php
│   ├── CsrfMiddleware.php
│   ├── SecurityHeadersMiddleware.php
│   └── XssMiddleware.php
├── Performance/           # Performance-focused middleware
│   ├── CacheMiddleware.php
│   └── RateLimitMiddleware.php
├── Http/                 # HTTP protocol middleware
│   ├── CorsMiddleware.php
│   └── ErrorMiddleware.php
└── Core/                 # Base middleware infrastructure
    ├── BaseMiddleware.php
    └── MiddlewareInterface.php
```

**Benefits:**
- ✅ **Clear separation of concerns**
- ✅ **Intuitive organization**
- ✅ **Easier maintenance**
- ✅ **100% backward compatibility** via automatic aliases

### 2. **Over-Engineering Elimination**

Following ARCHITECTURAL_GUIDELINES, we moved over-engineered features to `experimental/`:

**Moved to experimental/**:
- `JsonPrecompiler.php` (660 lines) - Complex eval() usage
- `RoutePrecompiler.php` (779 lines) - Academic optimization
- `StaticRouteCodeGenerator.php` (593 lines) - Premature optimization

**Impact:**
- ✅ **2,032 lines of complexity removed** from core
- ✅ **Zero security risks** from eval() usage
- ✅ **Maintained practical features** (StaticFileManager, etc.)
- ✅ **Preserved functionality** for existing users

### 3. **Performance Architecture Revolution**

#### Object Pool Optimization
**Before v1.1.3:**
- Request pool reuse: **0%**
- Response pool reuse: **0%**
- Framework throughput: **20,400 ops/sec**

**v1.1.3 Improvements:**
- Request pool reuse: **100%** ✅
- Response pool reuse: **99.9%** ✅  
- Framework throughput: **44,092 ops/sec** (+116%) ✅

#### Pool Architecture:
```php
┌─────────────────────────────────────────┐
│            DynamicPoolManager           │
├─────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────────┐   │
│  │ Request     │  │ Response        │   │
│  │ Pool        │  │ Pool            │   │
│  │ 100% reuse  │  │ 99.9% reuse     │   │
│  └─────────────┘  └─────────────────┘   │
├─────────────────────────────────────────┤
│          Smart Pool Warming             │
│     (Pre-allocation on startup)         │
└─────────────────────────────────────────┘
```

## 🔧 Core Components

### 1. **Application Bootstrap**

```php
namespace PivotPHP\Core\Core;

class Application
{
    // v1.1.3: Enhanced with array callable support
    public function get(string $path, callable|array $handler): void
    public function post(string $path, callable|array $handler): void
    public function put(string $path, callable|array $handler): void
    public function delete(string $path, callable|array $handler): void
}
```

**Key Features:**
- ✅ **Array callable support** (PHP 8.4+ compatible)
- ✅ **Service provider registration**
- ✅ **Event-driven lifecycle**
- ✅ **Middleware pipeline management**

### 2. **Dependency Injection Container**

```php
namespace PivotPHP\Core\Core;

class Container
{
    // PSR-11 compliant container with lazy loading
    public function get(string $id): mixed
    public function has(string $id): bool
    public function singleton(string $id, callable $factory): void
    public function bind(string $id, callable $factory): void
}
```

**Features:**
- ✅ **PSR-11 compliance**
- ✅ **Lazy loading**
- ✅ **Circular dependency detection**
- ✅ **Singleton management**

### 3. **Event System**

```php
namespace PivotPHP\Core\Events;

class EventDispatcher
{
    // PSR-14 compliant event dispatcher
    public function dispatch(object $event): object
    public function listen(string $event, callable $listener): void
}
```

**Events:**
- `ApplicationStarted` - Application initialization
- `RequestReceived` - Incoming request
- `ResponseSent` - Outgoing response
- Custom events for extensions

## 🚀 Router Architecture

### Array Callable Support (v1.1.3)

```php
// Traditional closure (still supported)
$app->get('/users', function($req, $res) {
    return $res->json(['users' => []]);
});

// NEW: Array callable syntax
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [$controller, 'store']);

// With parameters
$app->get('/users/:id', [UserController::class, 'show']);
```

**Implementation:**
```php
namespace PivotPHP\Core\Routing;

class Router 
{
    // v1.1.3: callable|array union type for PHP 8.4+ compatibility
    public function addRoute(string $method, string $path, callable|array $handler): void
    {
        // Validates callable or array format
        if (is_array($handler) && !is_callable($handler)) {
            throw new InvalidArgumentException('Invalid array callable format');
        }
        
        // Store route with proper handler validation
        $this->routes[] = new Route($method, $path, $handler);
    }
}
```

### Route Caching & Performance

```php
┌─────────────────────────────────────┐
│           Route Cache               │
├─────────────────────────────────────┤
│  ┌─────────────┐ ┌───────────────┐  │
│  │ Static      │ │ Dynamic       │  │
│  │ Routes      │ │ Routes        │  │
│  │ (O(1) lookup)│ │ (Regex cache) │  │
│  └─────────────┘ └───────────────┘  │
├─────────────────────────────────────┤
│        Parameter Extraction         │
│     (Optimized for performance)     │
└─────────────────────────────────────┘
```

## 🔒 Security Architecture

### Layered Security Approach

```php
┌─────────────────────────────────────────┐
│              Input Layer                │
├─────────────────────────────────────────┤
│  XSS Prevention │ Input Validation      │
├─────────────────────────────────────────┤
│            Authentication               │
├─────────────────────────────────────────┤
│  JWT │ API Keys │ Custom Auth Methods   │
├─────────────────────────────────────────┤
│              Authorization              │
├─────────────────────────────────────────┤
│  CSRF Protection │ Rate Limiting        │
├─────────────────────────────────────────┤
│              Output Layer               │
├─────────────────────────────────────────┤
│  Security Headers │ Content-Type        │
└─────────────────────────────────────────┘
```

### Security Middleware Stack

```php
use PivotPHP\Core\Middleware\Security\{
    SecurityHeadersMiddleware,
    XssMiddleware,
    CsrfMiddleware,
    AuthMiddleware
};

// Automatic security headers
$app->use(new SecurityHeadersMiddleware([
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000'
]));

// XSS protection
$app->use(new XssMiddleware());

// CSRF protection
$app->use(new CsrfMiddleware());
```

## 🎯 Performance Architecture

### JSON Optimization System

```php
┌─────────────────────────────────────────┐
│           JSON Buffer Pool              │
├─────────────────────────────────────────┤
│  ┌──────────┐ ┌──────────┐ ┌─────────┐  │
│  │  Small   │ │ Medium   │ │  Large  │  │
│  │ (2KB)    │ │ (8KB)    │ │ (32KB)  │  │
│  │ 161K/sec │ │ 17K/sec  │ │ 1.7K/s  │  │
│  └──────────┘ └──────────┘ └─────────┘  │
├─────────────────────────────────────────┤
│         Automatic Selection             │
│      (Based on data size/complexity)    │
└─────────────────────────────────────────┘
```

**Automatic Optimization:**
- **Smart detection**: Arrays 10+ elements, objects 5+ properties
- **Transparent fallback**: Small data uses traditional `json_encode()`
- **Zero configuration**: Works out-of-the-box

### Memory Management

```php
namespace PivotPHP\Core\Memory;

class MemoryManager
{
    // Intelligent memory pressure monitoring
    public function getMemoryPressure(): float
    public function optimizeMemoryUsage(): void
    public function getMemoryStats(): array
}
```

## 🧩 Extension Architecture

### Service Provider Pattern

```php
namespace PivotPHP\Core\Providers;

abstract class ServiceProvider
{
    // Registration phase
    abstract public function register(): void;
    
    // Boot phase (after all providers registered)
    public function boot(): void {}
}
```

**Example Extension:**
```php
class CustomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('custom.service', function() {
            return new CustomService();
        });
    }
    
    public function boot(): void
    {
        // Access other services after registration
        $router = $this->container->get('router');
        $router->addMiddleware(new CustomMiddleware());
    }
}
```

## 📊 Quality Metrics

### Type Safety (PHPStan Level 9)
```
┌─────────────────────────────────────┐
│         PHPStan Level 9             │
├─────────────────────────────────────┤
│  ✅ 100% type coverage              │
│  ✅ Zero mixed types                │
│  ✅ Strict parameter validation     │
│  ✅ Return type enforcement         │
└─────────────────────────────────────┘
```

### Test Coverage
- **684+ tests** with comprehensive assertions
- **Core, Integration, Performance** test suites
- **Multi-PHP validation** (8.1-8.4)
- **Docker-based CI/CD** testing

### Performance Validation
- **Object pooling**: 100% reuse rate
- **Framework throughput**: 44,092 ops/sec (+116%)
- **Memory efficiency**: Optimized garbage collection
- **Multi-version compatibility**: PHP 8.1-8.4

## 🔬 Experimental Features (Historical, v1.1.3)

> ⚠️ The `experimental/` directory described below no longer exists in the current
> repository (verified: `experimental/` is absent as of v2.1.1). This section is kept for
> historical context only.

Features moved to `experimental/` directory (not production-ready, as of v1.1.3):

```php
experimental/
├── Json/
│   └── JsonPrecompiler.php     # Advanced JSON precompilation
├── Routing/
│   ├── RoutePrecompiler.php    # Route code generation
│   └── StaticRouteCodeGenerator.php # Static route optimization
└── README.md                   # Usage warnings and alternatives
```

**Why experimental?**
- Complex implementations with diminishing returns
- Security risks (eval() usage)
- Violation of simplicity principles
- Academic interest rather than practical value

## 🚀 Future Architecture

> ⚠️ Per this project's documentation policy, planned/roadmap items don't belong in
> user-facing docs. The "Planned Improvements" list that was here (WebSocket, GraphQL, etc.)
> was speculative content from the v1.1.3-era guide and has been removed — none of those
> items are implemented as of v2.1.1. For actual planned work, see GitHub Issues/Discussions.

### Architectural Principles (v1.1.3+)
- ✅ **Simplicity over premature optimization**
- ✅ **Type safety over convenience**
- ✅ **Performance through optimization, not complexity**
- ✅ **Backward compatibility always**
- ✅ **Community-driven development**

---

This architecture guide reflects PivotPHP Core v1.1.3's maturity as a **production-ready microframework** that balances **high performance** with **architectural simplicity**, following modern PHP best practices while maintaining the familiar Express.js development experience.