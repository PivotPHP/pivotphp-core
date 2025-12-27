# Adapter Pattern Architecture

**Date**: December 27, 2025
**Version**: 2.0.1
**Status**: Implemented and Benchmarked

## Overview

The PivotPHP HTTP layer has been migrated from an inheritance-based architecture to a composition-based adapter pattern. This architectural change provides better separation of concerns, eliminates data duplication, and improves maintainability while preserving full backward compatibility.

## Architecture Evolution

### Before: Inheritance-Based Hybrid

```php
// Legacy approach - Multiple inheritance, data duplication
class Request extends ServerRequest {
    private string $path;           // ❌ Duplicates URI data
    private string $method;         // ❌ Duplicates method data
    private array $params = [];     // ❌ Duplicates attributes
    private ?stdClass $body = null; // ❌ Duplicates parsed body

    // Mixed Express.js and PSR-7 methods in single class
}

class Response extends Response {
    private int $statusCode;        // ❌ Duplicates status
    private array $headers = [];    // ❌ Duplicates headers
    // ... more duplication
}
```

**Problems**:
- 🔴 **Data Duplication**: Same data stored in both parent PSR-7 and child properties
- 🔴 **Sync Issues**: Risk of PSR-7 and Express.js data getting out of sync
- 🔴 **Tight Coupling**: Inheritance creates rigid class hierarchies
- 🔴 **Testing Complexity**: Hard to mock or test in isolation

### After: Composition-Based Adapters

```php
// New approach - Composition, single source of truth
class ExpressRequest {
    private ServerRequestInterface $psr7Request;  // ✅ Single source of truth

    // Express.js API delegates to PSR-7
    public function param(string $key): mixed {
        $params = $this->psr7Request->getAttribute('route_params', []);
        return $params[$key] ?? null;
    }

    public function get(string $key, $default = null): mixed {
        $queryParams = $this->psr7Request->getQueryParams();
        return $queryParams[$key] ?? $default;
    }

    // Implements ServerRequestInterface via delegation
    public function getAttribute($name, $default = null) {
        return $this->psr7Request->getAttribute($name, $default);
    }
}

class ExpressResponse {
    private ResponseInterface $psr7Response;  // ✅ Single source of truth

    // Express.js API creates new PSR-7 instances
    public function json(array $data): self {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $stream = Psr7Pool::getStream($json);

        $newResponse = $this->psr7Response
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);

        $this->psr7Response = $newResponse;
        return $this;
    }

    // Implements ResponseInterface via delegation
    public function getStatusCode(): int {
        return $this->psr7Response->getStatusCode();
    }
}
```

**Benefits**:
- ✅ **Zero Duplication**: All data stored once in PSR-7 objects
- ✅ **Perfect Sync**: No synchronization issues possible
- ✅ **Loose Coupling**: Composition allows flexible architecture
- ✅ **Easy Testing**: Simple to mock and test components
- ✅ **PSR-7 Compliance**: Full standard compliance via delegation
- ✅ **Developer Experience**: Express.js API preserved

## Implementation Details

### Request Adapter (ExpressRequest)

**Core Responsibilities**:
1. Wrap PSR-7 ServerRequestInterface
2. Provide Express.js-style convenience methods
3. Delegate all PSR-7 operations to composed object
4. Extract route parameters from attributes
5. Parse query/body/file data from PSR-7 methods

**Key Methods**:

```php
// Route parameters (from PSR-7 attributes)
public function param(string $key, mixed $default = null): mixed
public function getParams(): array

// Query parameters (from PSR-7 query params)
public function get(string $key, mixed $default = null): mixed
public function query(string $key, mixed $default = null): mixed

// Body parameters (from PSR-7 parsed body)
public function input(string $key, mixed $default = null): mixed
public function post(string $key, mixed $default = null): mixed

// Express.js magic properties
public function __get(string $name): mixed  // $req->path, $req->method, etc.

// Full PSR-7 compliance via delegation
public function getAttribute($name, $default = null)
public function getQueryParams(): array
public function getParsedBody()
// ... all other ServerRequestInterface methods
```

**Route Parameter Extraction**:

```php
// Pattern: /users/:id/posts/:postId
// Path: /users/123/posts/456

// OptimizedHttpFactory extracts params and stores as single attribute
$params = ['id' => '123', 'postId' => '456'];
$psr7Request = $psr7Request->withAttribute('route_params', $params);

// ExpressRequest retrieves from PSR-7 attribute
$id = $request->param('id');        // "123"
$postId = $request->param('postId'); // "456"
$all = $request->getParams();        // ['id' => '123', 'postId' => '456']
```

### Response Adapter (ExpressResponse)

**Core Responsibilities**:
1. Wrap PSR-7 ResponseInterface
2. Provide Express.js-style chainable methods
3. Delegate all PSR-7 operations to composed object
4. Handle immutability via PSR-7 `withX()` methods
5. Manage response emission

**Key Methods**:

```php
// Status
public function status(int $code): self

// Headers
public function header(string $name, string $value): self

// JSON responses
public function json(array $data): self

// Text responses
public function send(string $content): self

// Redirects
public function redirect(string $url, int $status = 302): self

// Full PSR-7 compliance via delegation
public function getStatusCode(): int
public function withStatus($code, $reasonPhrase = ''): ResponseInterface
public function getHeaders(): array
// ... all other ResponseInterface methods
```

**Immutability Handling**:

```php
// PSR-7 requires immutability - withX() methods return new instances
public function header(string $name, string $value): self {
    // Create new PSR-7 response with header
    $newResponse = $this->psr7Response->withHeader($name, $value);

    // Update internal reference
    $this->psr7Response = $newResponse;

    // Return $this for Express.js-style chaining
    return $this;
}

// This allows chainable Express.js API:
$response->status(200)->header('X-Custom', 'value')->json(['success' => true]);
```

## Object Pooling Integration

The adapter pattern works seamlessly with PivotPHP's object pooling system:

```php
// OptimizedHttpFactory creates pooled PSR-7 objects
$psr7Request = Psr7Pool::getServerRequest($method, $uri, $stream, $headers, '1.1', $serverParams);

// Wrap in Express adapter
$request = new ExpressRequest($psr7Request, $path, $pathCallable);

// After use, objects can be returned to pool
Psr7Pool::returnServerRequest($psr7Request);
```

**Pool Benefits**:
- Request pool reuse: ~100%
- Response pool reuse: ~99.9%
- Significant reduction in object allocations
- Lower garbage collection pressure

## Performance Benchmarks

### Benchmark Results (PHP 8.4.8)

```
Iterations: 10,000 per test
Architecture: Composition over Inheritance + PSR-7 Delegation

📥 ExpressRequest Creation:  30,793 ops/sec (0.032 ms avg)
📤 ExpressResponse Creation: 366,555 ops/sec (0.003 ms avg)
⚡ Request Operations:       62,772 ops/sec (15.93 μs avg)
⚡ Response Operations:      44,701 ops/sec (22.37 μs avg)
🔄 Full Cycle:               38,984 ops/sec (0.026 ms avg)
💾 Memory per Object:        2.32 KB
```

### Performance Analysis

**Request Creation**: 30,793 ops/sec
- Includes PSR-7 ServerRequest creation from pool
- Header extraction from $_SERVER
- Route parameter parsing
- Query/body parameter attachment

**Response Creation**: 366,555 ops/sec
- Extremely fast due to efficient pooling
- Minimal overhead from adapter wrapper
- Ready for immediate use

**Full Cycle**: 38,984 ops/sec
- Complete request-response lifecycle
- Includes both creation and operations
- Demonstrates real-world performance

**Memory Efficiency**: 2.32 KB per object
- Compact memory footprint
- Single source of truth reduces overhead
- Efficient pooling reduces allocations

## Backward Compatibility

### Type Aliases

All existing code continues to work via type aliases in middleware and other components:

```php
// In middleware files
use PivotPHP\Core\Http\ExpressRequest as Request;
use PivotPHP\Core\Http\ExpressResponse as Response;

// Existing code works unchanged
public function handle(Request $request, Response $response, callable $next): Response
{
    // All existing code works identically
    $id = $request->param('id');
    return $response->json(['id' => $id]);
}
```

### Migration Path

**No Breaking Changes**:
- ✅ All existing Express.js API methods preserved
- ✅ All existing PSR-7 methods work identically
- ✅ Type aliases maintain backward compatibility
- ✅ Middleware doesn't require changes
- ✅ Route handlers don't require changes

**Recommended Update** (Optional):

```php
// Old (still works)
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

// New (recommended for clarity)
use PivotPHP\Core\Http\ExpressRequest;
use PivotPHP\Core\Http\ExpressResponse;
```

## Testing

### Test Coverage

**ExpressRequestTest.php**: 67 tests
- Route parameter extraction
- Query parameter handling
- POST body parsing
- File upload handling
- Header access
- IP detection
- PSR-7 compliance
- Immutability verification

**ExpressResponseTest.php**: 88 tests
- Status code handling
- Header manipulation
- JSON encoding
- Redirect functionality
- Cookie management
- Streaming responses
- SSE (Server-Sent Events)
- PSR-7 compliance
- Immutability verification

**Total**: 155 adapter tests, 100% passing

### Example Test

```php
public function testParamExtractsRouteParameters(): void
{
    $request = OptimizedHttpFactory::createRequest('GET', '/users/:id', '/users/123');

    $this->assertEquals('123', $request->param('id'));
    $this->assertNull($request->param('missing'));
    $this->assertEquals('default', $request->param('missing', 'default'));
}

public function testJsonSetsBothContentTypeAndBody(): void
{
    $response = OptimizedHttpFactory::createResponse();
    $response->setTestMode(true);
    $response->disableAutoEmit(true);

    $data = ['message' => 'Hello', 'status' => 'success'];
    $response->json($data);

    $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    $this->assertJson((string) $response->getBody());
}
```

## Running Benchmarks

### Execute Benchmark

```bash
# Run adapter pattern benchmark
php /home/cfernandes/pivotphp/pivotphp-core/benchmarks/AdapterPatternBenchmark.php

# Results saved to:
# benchmarks/reports/adapter_pattern_YYYY-MM-DD_HH-MM-SS.json
```

### Benchmark Structure

```php
class AdapterPatternBenchmark {
    private int $iterations = 10000;

    public function run(): void {
        $this->warmup();
        $this->benchmarkRequestCreation();
        $this->benchmarkResponseCreation();
        $this->benchmarkRequestOperations();
        $this->benchmarkResponseOperations();
        $this->benchmarkFullCycle();
        $this->benchmarkMemoryEfficiency();
        $this->displayResults();
        $this->saveResults();
    }
}
```

## Design Patterns

### Adapter Pattern

**Intent**: Convert the interface of a class into another interface clients expect.

**Application**:
- ExpressRequest adapts ServerRequestInterface to Express.js API
- ExpressResponse adapts ResponseInterface to Express.js API

**Benefits**:
- Interface compatibility without modifying PSR-7
- Clean separation of concerns
- Easy to test and maintain

### Delegation Pattern

**Intent**: Forward method calls to a composed object.

**Application**:
- All PSR-7 methods delegate to composed $psr7Request/$psr7Response
- Preserves PSR-7 compliance
- Maintains immutability guarantees

**Example**:
```php
public function getAttribute($name, $default = null) {
    return $this->psr7Request->getAttribute($name, $default);
}
```

### Composition Over Inheritance

**Intent**: Favor object composition over class inheritance.

**Application**:
- ExpressRequest HAS-A ServerRequestInterface (composition)
- Not IS-A ServerRequest (inheritance)
- Provides flexibility to change PSR-7 implementation

## Best Practices

### When Using Adapters

1. **Always use factory methods**:
   ```php
   // ✅ Recommended
   $request = OptimizedHttpFactory::createRequest('GET', '/users', '/users');

   // ❌ Avoid (no pooling, no parameter extraction)
   $request = new ExpressRequest($psr7Request, '/users', '/users');
   ```

2. **Leverage type hints**:
   ```php
   // ✅ Use adapter types for clarity
   public function handle(ExpressRequest $req, ExpressResponse $res): ExpressResponse

   // ✅ Or use PSR-7 types for interoperability
   public function handle(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
   ```

3. **Test with pools enabled**:
   ```php
   protected function setUp(): void {
       Psr7Pool::clearPools();  // Ensure clean state
   }
   ```

### Performance Tips

1. **Reuse responses in tests**:
   ```php
   $response = OptimizedHttpFactory::createResponse();
   $response->setTestMode(true);
   $response->disableAutoEmit(true);  // Prevent header emission
   ```

2. **Batch operations**:
   ```php
   // ✅ Chain operations
   $response->status(200)->header('X-Custom', 'value')->json($data);

   // ❌ Less efficient (more intermediate objects)
   $response->status(200);
   $response->header('X-Custom', 'value');
   $response->json($data);
   ```

## Future Considerations

### Potential Enhancements

1. **Typed Route Parameters**:
   ```php
   $id = $request->param('id', type: 'int');  // Auto-cast to int
   ```

2. **Validation Integration**:
   ```php
   $validated = $request->validate([
       'email' => 'required|email',
       'name' => 'required|string'
   ]);
   ```

3. **Request/Response Events**:
   ```php
   $request->on('param.missing', function($key) {
       // Handle missing parameter
   });
   ```

## Conclusion

The adapter pattern migration represents a significant architectural improvement:

✅ **Zero Data Duplication**: Single source of truth eliminates sync issues
✅ **PSR-7 Compliance**: Full standard compliance via delegation
✅ **Developer Experience**: Express.js API preserved for convenience
✅ **Performance**: Excellent throughput with efficient pooling
✅ **Maintainability**: Composition over inheritance improves flexibility
✅ **Backward Compatible**: No breaking changes, seamless upgrade

This architecture provides a solid foundation for future enhancements while maintaining the developer-friendly Express.js-style API that makes PivotPHP productive and enjoyable to use.

## References

- [Benchmark: benchmarks/AdapterPatternBenchmark.php](../../../benchmarks/AdapterPatternBenchmark.php)
- [ExpressRequest: src/Http/ExpressRequest.php](../../../src/Http/ExpressRequest.php)
- [ExpressResponse: src/Http/ExpressResponse.php](../../../src/Http/ExpressResponse.php)
- [Tests: tests/Http/ExpressRequestTest.php](../../../tests/Http/ExpressRequestTest.php)
- [Tests: tests/Http/ExpressResponseTest.php](../../../tests/Http/ExpressResponseTest.php)
- [PSR-7: HTTP Message Interfaces](https://www.php-fig.org/psr/psr-7/)
- [PSR-15: HTTP Server Request Handlers](https://www.php-fig.org/psr/psr-15/)
