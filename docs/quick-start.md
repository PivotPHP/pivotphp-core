# Quick Start Guide

Get up and running with PivotPHP Core v1.2.0 in under 5 minutes! This guide will walk you through installation, basic setup, and creating your first API endpoints.

## 🚀 Installation

### Prerequisites
- **PHP 8.1+** with extensions: `json`, `mbstring`
- **Composer** for dependency management

### Install via Composer

```bash
composer require pivotphp/core
```

## 🔥 Your First API

Create a new file `index.php`:

```php
<?php
require_once 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

// Create application instance
$app = new Application();

// Basic route
$app->get('/', function($req, $res) {
    return $res->json(['message' => 'Hello, PivotPHP!']);
});

// API endpoint with parameters
$app->get('/users/:id', function($req, $res) {
    $userId = $req->param('id');
    return $res->json(['user_id' => $userId, 'name' => 'John Doe']);
});

// JSON POST endpoint
$app->post('/users', function($req, $res) {
    $userData = $req->getBodyAsStdClass();
    return $res->status(201)->json([
        'message' => 'User created',
        'data' => $userData
    ]);
});

// Run the application
$app->run();
```

### Test Your API

```bash
# Start PHP development server
php -S localhost:8080

# Test endpoints
curl http://localhost:8080/                    # {"message":"Hello, PivotPHP!"}
curl http://localhost:8080/users/123           # {"user_id":"123","name":"John Doe"}
curl -X POST -H "Content-Type: application/json" \
     -d '{"name":"Alice"}' \
     http://localhost:8080/users               # {"message":"User created","data":{"name":"Alice"}}
```

## 🎯 v1.2.0 New Features

### Array Callable Routes (NEW!)

Use array callables with PHP 8.4+ compatibility:

```php
class UserController
{
    public function index($req, $res)
    {
        return $res->json(['users' => User::all()]);
    }

    public function show($req, $res)
    {
        $id = $req->param('id');
        return $res->json(['user' => User::find($id)]);
    }
}

// Register routes with array callable syntax
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/:id', [UserController::class, 'show']);
```

### Automatic Performance Optimization

Object pooling and JSON optimization work automatically:

```php
// Large JSON responses are automatically optimized
$app->get('/api/data', function($req, $res) {
    $largeDataset = Database::getAllRecords(); // 1000+ records
    return $res->json($largeDataset); // Automatically uses buffer pooling!
});
```

## 🛡️ Adding Security

Add essential security middleware:

```php
use PivotPHP\Core\Middleware\Security\{CsrfMiddleware, SecurityHeadersMiddleware};
use PivotPHP\Core\Middleware\Http\CorsMiddleware;

// Security middleware
$app->use(new SecurityHeadersMiddleware());
$app->use(new CorsMiddleware([
    'allowed_origins' => ['https://yourfrontend.com'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization']
]));

// CSRF protection for forms
$app->use(new CsrfMiddleware([
    'exclude_paths' => ['/api/*'] // Exclude API routes
]));
```

## 🔍 Route Patterns

PivotPHP supports powerful routing patterns:

```php
// Basic parameters
$app->get('/users/:id', $handler);

// Regex constraints
$app->get('/users/:id<\\d+>', $handler);           // Only numeric IDs
$app->get('/posts/:slug<[a-z0-9-]+>', $handler);   // Slug format

// Predefined patterns
$app->get('/posts/:date<date>', $handler);          // YYYY-MM-DD format
$app->get('/files/:uuid<uuid>', $handler);          // UUID format

// Multiple parameters
$app->get('/users/:userId/posts/:postId<\\d+>', $handler);
```

## 🔧 Configuration

Create `config/app.php` for application settings:

```php
return [
    'debug' => false,
    'timezone' => 'UTC',
    'cache' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../storage/cache'
    ],
    'cors' => [
        'enabled' => true,
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'max_age' => 86400
    ]
];
```

Load configuration in your application:

```php
use PivotPHP\Core\Core\Config;

$config = new Config(__DIR__ . '/config');
$app = new Application($config);
```

## 📊 Performance Monitoring

Enable performance monitoring for production:

```php
use PivotPHP\Core\Performance\PerformanceMode;

// Enable performance mode
PerformanceMode::enable(PerformanceMode::PROFILE_PRODUCTION);

// Get performance metrics
$app->get('/metrics', function($req, $res) {
    $monitor = PerformanceMode::getMonitor();
    $metrics = $monitor->getPerformanceMetrics();
    return $res->json($metrics);
});
```

## 🧪 Testing Your API

Create `tests/BasicTest.php`:

```php
<?php
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

class BasicTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
        $this->app->get('/test', function($req, $res) {
            return $res->json(['status' => 'ok']);
        });
    }

    public function testBasicRoute(): void
    {
        // Test implementation here
        $this->assertTrue(true); // Placeholder
    }
}
```

## 🚀 Próximos Passos para Provas de Conceito

Agora que você tem uma API básica funcionando, explore recursos para enriquecer seus protótipos:

1. **[API Reference](API_REFERENCE.md)** - Referência completa dos métodos
2. **[Middleware Guide](reference/middleware.md)** - Segurança e performance para demos
3. **[Authentication](technical/authentication/README.md)** - JWT e API key para protótipos seguros
4. **[Documentação Automática](examples/api_documentation_example.php)** - Swagger para apresentações
5. **[Examples](reference/examples.md)** - Exemplos práticos e casos de uso

## 🧪 Expandindo Protótipos

Para expandir suas provas de conceito:

```php
// Adicionar autenticação JWT para demos
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'demo_secret_key'
]));

// Documentação automática (essencial para apresentações)
$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',
    'swagger_path' => '/swagger'
]));

// Middleware de segurança para protótipos profissionais
$app->use(new SecurityHeadersMiddleware());
$app->use(new CorsMiddleware(['allowed_origins' => ['*']]));
```

## 🆘 Suporte e Aprendizado

- **[Documentação](README.md)** - Documentação completa
- **[GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)** - Relatar problemas e sugerir melhorias
- **[Examples Repository](examples/)** - Exemplos práticos para aprendizado

## ⚠️ Importante: Sobre o Projeto

**PivotPHP Core é mantido por apenas uma pessoa** e pode não receber atualizações constantemente. Este guia é ideal para criar protótipos e provas de conceito, mas não é recomendado para sistemas de produção críticos que exigem suporte 24/7.

---

**Parabéns!** Agora você tem uma base sólida para criar provas de conceito e protótipos com PivotPHP Core v1.2.0. 🎉
