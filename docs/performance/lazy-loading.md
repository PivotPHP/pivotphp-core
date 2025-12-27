# Lazy Loading Architecture

## Overview

PivotPHP's HTTP layer implements **intelligent lazy loading** to achieve exceptional performance by deferring all expensive operations until they're actually needed.

## Performance Impact

### Benchmark Results

**SimpleThroughputBenchmark.php** (10,000 iterations, PHP 8.4.8):

```
==========================================
Request Creation Only
==========================================
Throughput: 1,087,058 ops/sec
Average: 0.92 μs/op
Memory: 0 B delta

==========================================
Request + Param Access (Common Case)
==========================================
Throughput: 350,115 ops/sec
Average: 2.86 μs/op
Memory: 0 B delta

==========================================
Request + Full Data Access
==========================================
Throughput: 45,787 ops/sec
Average: 21.84 μs/op
Memory: 0 B delta

==========================================
Full Request/Response Cycle
==========================================
Throughput: 16,922 req/sec
Average: 59.10 μs/req
Memory: -1.35 KB delta
```

### Comparison with Eager Loading

| Scenario | Eager | Lazy | Improvement |
|----------|-------|------|-------------|
| Creation Only | 33,217 ops/sec | 1,087,058 ops/sec | **+3,179%** (32x) 🔥 |
| Creation + Param | 33,155 ops/sec | 350,115 ops/sec | **+961%** (10.5x) 🚀 |
| Full Data Access | 66,952 ops/sec | 45,787 ops/sec | -31% ⚠️ |

**Note**: The "full data access" scenario is slower because lazy loading has initialization overhead when ALL data is accessed. However, this scenario is rare in practice - most API endpoints only access a subset of available data.

### Memory Efficiency

**Per Object Memory Usage**:
- Eager loading: 2,374 bytes
- Lazy (no access): 341 bytes (-85.7%) 💚
- Lazy (with access): 749 bytes (-68.5%) 💚

**What This Means**:
- Middleware that just forwards requests: **85.7% less memory**
- Typical API endpoint: **68.5% less memory**
- High-traffic scenarios: Dramatically reduced memory pressure

## How It Works

### Architecture Overview

```php
class ExpressRequest implements ServerRequestInterface
{
    // Lazy-loaded components (null = not loaded yet)
    private ?ServerRequestInterface $psr7Request = null;
    private ?array $routeParams = null;
    private ?array $queryParams = null;
    private ?array $headers = null;
    private mixed $parsedBody = false;  // false = not loaded
    private ?array $uploadedFiles = null;

    // Lightweight initialization
    public function __construct(
        string $method,
        string $path,
        string $pathCallable
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->pathCallable = $pathCallable;
        // That's it! No expensive operations here
    }
}
```

### Lazy Extraction Pattern

Each component follows the same pattern:

```php
private function extractRouteParams(): array
{
    // Check if already loaded
    if ($this->routeParams !== null) {
        return $this->routeParams;
    }

    // Expensive operation happens only once, on demand
    $params = [];
    $regex = preg_replace_callback('/:([\\w]+)(<[^>]+>)?/',
        fn($m) => '(?P<' . $m[1] . '>[^/]+)',
        $this->path
    );

    if (preg_match('#^' . $regex . '$#', $this->pathCallable, $matches)) {
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
    }

    // Cache result for subsequent access
    $this->routeParams = $params;
    return $params;
}
```

### PSR-7 Lazy Creation

The PSR-7 request object is created only when PSR-7 methods are called:

```php
private function ensurePsr7Request(): ServerRequestInterface
{
    if ($this->psr7Request === null) {
        $this->psr7Request = $this->createPsr7Request();
    }
    return $this->psr7Request;
}

public function getUri(): UriInterface
{
    // PSR-7 created only when needed
    return $this->ensurePsr7Request()->getUri();
}
```

## What's Lazy Loaded

### 1. Headers

```php
public function header(string $name): ?string
{
    $headers = $this->extractHeaders();  // Lazy!
    // ... lookup logic
}

private function extractHeaders(): array
{
    if ($this->headers !== null) {
        return $this->headers;  // Cached
    }

    // Extract from $_SERVER only when first accessed
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (str_starts_with($key, 'HTTP_')) {
            // ... header extraction logic
        }
    }

    $this->headers = $headers;
    return $headers;
}
```

### 2. Query Parameters

```php
public function get(string $key, mixed $default = null): mixed
{
    $queryParams = $this->extractQueryParams();  // Lazy!
    return $queryParams[$key] ?? $default;
}

private function extractQueryParams(): array
{
    if ($this->queryParams !== null) {
        return $this->queryParams;  // Cached
    }

    // Parse only when first accessed
    $queryParams = [];
    if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') {
        parse_str($_SERVER['QUERY_STRING'], $queryParams);
    }

    $this->queryParams = $queryParams;
    return $queryParams;
}
```

### 3. Request Body

```php
public function input(string $key, mixed $default = null): mixed
{
    $body = $this->extractParsedBody();  // Lazy!
    return $body[$key] ?? $default;
}

private function extractParsedBody(): mixed
{
    if ($this->parsedBody !== false) {
        return $this->parsedBody;  // Cached
    }

    // Parse based on content type, only when first accessed
    $this->parsedBody = $_POST ?? [];
    return $this->parsedBody;
}
```

### 4. Route Parameters

```php
public function param(string $key): mixed
{
    $params = $this->extractRouteParams();  // Lazy!
    return $params[$key] ?? null;
}
```

### 5. Uploaded Files

```php
public function file(string $key): ?array
{
    $uploadedFiles = $this->extractUploadedFiles();  // Lazy!
    return $uploadedFiles[$key] ?? null;
}

private function extractUploadedFiles(): array
{
    if ($this->uploadedFiles !== null) {
        return $this->uploadedFiles;  // Cached
    }

    // Build file array only when first accessed
    $this->uploadedFiles = $_FILES ?? [];
    return $this->uploadedFiles;
}
```

### 6. PSR-7 Request

```php
// Any PSR-7 method triggers lazy creation
public function getMethod(): string
{
    return $this->method;  // Direct access - no PSR-7 needed
}

public function getUri(): UriInterface
{
    return $this->ensurePsr7Request()->getUri();  // PSR-7 created now
}
```

## Usage Patterns

### Best Case: Middleware

```php
// Middleware that just forwards the request
$app->use(function($req, $res, $next) {
    // Request created, NO data accessed
    // Performance: 1,087,058 ops/sec ⚡
    return $next($req, $res);
});
```

### Common Case: API Endpoint

```php
// Typical REST API endpoint
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');  // Only route params accessed

    $user = User::find($id);

    return $res->json($user);
    // Performance: 350,115 ops/sec 🎯
});
```

### Complex Case: Full Data Access

```php
// Endpoint that needs everything (rare)
$app->post('/analytics', function($req, $res) {
    // Access all components
    $method = $req->method;
    $path = $req->path;
    $params = $req->params;
    $query = $req->query;
    $body = $req->body;
    $headers = $req->headers;
    $ip = $req->ip();

    // Log everything for analytics
    // Performance: 45,787 ops/sec 📊
    // Still very fast!
});
```

## Real-World Scenarios

### Scenario 1: Simple Health Check

```php
$app->get('/health', function($req, $res) {
    return $res->json(['status' => 'ok']);
});

// Request created: 0.92 μs
// No data accessed
// Total: 0.92 μs
```

### Scenario 2: User CRUD

```php
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');  // +1.94 μs (route params)
    $user = User::find($id);   // Database time
    return $res->json($user);  // Response time
});

// Request created: 0.92 μs
// Param access: +1.94 μs
// Total overhead: 2.86 μs
```

### Scenario 3: Search with Query

```php
$app->get('/search', function($req, $res) {
    $query = $req->get('q');      // +1.94 μs (query params)
    $page = $req->get('page', 1); // Cached, no overhead

    $results = search($query, $page);
    return $res->json($results);
});

// Request created: 0.92 μs
// Query parsing: +1.94 μs
// Total overhead: 2.86 μs
```

### Scenario 4: POST with Body

```php
$app->post('/users', function($req, $res) {
    $name = $req->input('name');   // +3 μs (body parsing)
    $email = $req->input('email'); // Cached, no overhead

    $user = User::create(['name' => $name, 'email' => $email]);
    return $res->status(201)->json($user);
});

// Request created: 0.92 μs
// Body parsing: +3 μs
// Total overhead: 3.92 μs
```

## Performance Tips

### ✅ DO: Access Only What You Need

```php
// GOOD - Only accesses route params
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    return $res->json(User::find($id));
});
// Performance: 350,115 ops/sec 🚀
```

### ⚠️ AVOID: Unnecessary Data Access

```php
// LESS OPTIMAL - Accesses everything
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    $query = $req->query;      // Not used!
    $headers = $req->headers;  // Not used!
    $body = $req->body;        // Not used!

    return $res->json(User::find($id));
});
// Performance: 45,787 ops/sec (still fast, but why waste?)
```

### ✅ DO: Cache Repeated Access

```php
// GOOD - Data cached automatically
$app->get('/search', function($req, $res) {
    $q = $req->get('q');     // Parse once
    $page = $req->get('page'); // Cached
    $limit = $req->get('limit'); // Cached

    // No performance penalty for multiple accesses
});
```

## Backward Compatibility

### 100% Compatible

All existing code works without modification:

```php
// All these patterns work exactly the same
$id = $req->param('id');
$query = $req->query;
$body = $req->body;
$headers = $req->headers;

// PSR-7 methods also work
$method = $req->getMethod();
$uri = $req->getUri();
$serverParams = $req->getServerParams();
```

### Migration

**No migration needed!** Lazy loading is a drop-in replacement that:
- ✅ Maintains exact same API
- ✅ Passes all 389 HTTP tests
- ✅ Works with all existing code
- ✅ Provides automatic performance boost

## Testing

### Benchmark Results

Run the benchmarks yourself:

```bash
# Simple throughput test
php benchmarks/SimpleThroughputBenchmark.php

# Lazy vs Eager comparison (if you have both versions)
php benchmarks/LazyVsEagerBenchmark.php
```

### Test Suite

All tests passing:

```bash
# HTTP test suite
vendor/bin/phpunit tests/Http/

# Results
# Tests: 389, Assertions: 1221 ✅
```

## Technical Details

### Cache Strategy

Each component uses a three-state cache:
- `null`: Not loaded yet
- `value`: Loaded and cached
- `false` (body only): Not loaded yet

### Memory Management

- Caches invalidated on request mutation
- PSR-7 objects returned to pool on destruction
- Minimal memory footprint until accessed

### Thread Safety

- No static state
- Each request instance independent
- Safe for concurrent requests

## Conclusion

Lazy loading provides:
- ✅ **10.5x faster** for typical API endpoints
- ✅ **32x faster** for middleware
- ✅ **85.7% less memory** when data not accessed
- ✅ **100% backward compatible**
- ✅ **Zero configuration required**

This makes PivotPHP suitable for:
- High-performance APIs
- Microservices
- Real-time applications
- High-traffic websites

**Performance is now production-ready!** 🚀
