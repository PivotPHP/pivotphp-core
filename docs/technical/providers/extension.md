# Criando Extensões com Providers

As extensões do PivotPHP permitem criar funcionalidades reutilizáveis que podem ser facilmente integradas em diferentes projetos. Use providers para criar extensões robustas e modulares.

## Conceitos de Extensões

### O que são Extensões?

Extensões são pacotes que:
- **Estendem funcionalidades** do framework
- **São reutilizáveis** entre projetos
- **Têm auto-discovery** via Composer
- **Seguem padrões** estabelecidos
- **São facilmente configuráveis**

### Componentes de uma Extensão

1. **Service Provider** - Registra serviços
2. **Configuração** - Arquivos de config
3. **Assets** - Recursos estáticos
4. **Documentação** - Guias de uso
5. **Testes** - Suite de testes

## Estrutura de uma Extensão

### Estrutura de Diretórios

```
minha-extensao/
├── composer.json
├── README.md
├── src/
│   ├── MyExtensionServiceProvider.php
│   ├── Controllers/
│   │   └── ExtensionController.php
│   ├── Middleware/
│   │   └── ExtensionMiddleware.php
│   ├── Services/
│   │   └── ExtensionService.php
│   └── Config/
│       └── extension.php
├── config/
│   └── extension.php
├── resources/
│   ├── views/
│   └── assets/
├── tests/
│   └── ExtensionTest.php
└── migrations/
    └── create_extension_tables.php
```

### Composer.json

```json
{
    "name": "vendor/pivotphp-core-extension",
    "description": "Uma extensão incrível para PivotPHP",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Seu Nome",
            "email": "seu@email.com"
        }
    ],
    "require": {
        "php": "^8.1",
        "pivotphp-core/framework": "^2.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "Vendor\\PivotPhpExtension\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Vendor\\PivotPhpExtension\\Tests\\": "tests/"
        }
    },
    "extra": {
        "pivotphp-core": {
            "providers": [
                "Vendor\\PivotPhpExtension\\MyExtensionServiceProvider"
            ],
            "config": {
                "extension": "config/extension.php"
            },
            "migrations": "migrations/",
            "assets": "resources/assets/"
        }
    }
}
```

## Service Provider da Extensão

### Provider Básico

```php
<?php

namespace Vendor\PivotPhpExtension;

use PivotPHP\Core\Providers\ServiceProvider;
use PivotPHP\Core\Routing\Router;

class MyExtensionServiceProvider extends ServiceProvider
{
    /**
     * Registrar serviços da extensão
     */
    public function register(): void
    {
        // Registrar configuração
        $this->registerConfig();

        // Registrar serviços
        $this->registerServices();

        // Registrar middleware
        $this->registerMiddleware();
    }

    /**
     * Bootstrap da extensão
     */
    public function boot(): void
    {
        // Registrar rotas
        $this->registerRoutes();

        // Publicar assets
        $this->publishAssets();

        // Executar migrations
        $this->runMigrations();
    }

    /**
     * Registrar configuração
     */
    private function registerConfig(): void
    {
        $configPath = __DIR__ . '/Config/extension.php';

        if (file_exists($configPath)) {
            $config = require $configPath;
            $this->app->get('config')->merge('extension', $config);
        }
    }

    /**
     * Registrar serviços
     */
    private function registerServices(): void
    {
        $this->app->singleton(ExtensionService::class, function($app) {
            return new ExtensionService(
                $app->get('config')->get('extension'),
                $app->get('database'),
                $app->get('logger')
            );
        });

        // Alias para facilitar acesso
        $this->app->alias('extension', ExtensionService::class);
    }

    /**
     * Registrar middleware
     */
    private function registerMiddleware(): void
    {
        $this->app->singleton(ExtensionMiddleware::class);
    }

    /**
     * Registrar rotas da extensão
     */
    private function registerRoutes(): void
    {
        Router::group('/extension', function() {
            Router::get('/', [ExtensionController::class, 'index']);
            Router::get('/status', [ExtensionController::class, 'status']);
            Router::post('/action', [ExtensionController::class, 'action']);
        }, [ExtensionMiddleware::class]);
    }

    /**
     * Publicar assets
     */
    private function publishAssets(): void
    {
        $assetsPath = __DIR__ . '/../resources/assets';
        $publicPath = $this->app->basePath('public/vendor/extension');

        if (is_dir($assetsPath) && !is_dir($publicPath)) {
            $this->copyDirectory($assetsPath, $publicPath);
        }
    }

    /**
     * Executar migrations
     */
    private function runMigrations(): void
    {
        if ($this->app->get('config')->get('extension.auto_migrate', false)) {
            $migrationPath = __DIR__ . '/../migrations';

            if (is_dir($migrationPath)) {
                $this->runMigrationsFromPath($migrationPath);
            }
        }
    }

    /**
     * Copiar diretório recursivamente
     */
    private function copyDirectory(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $files = scandir($source);

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $sourcePath = $source . '/' . $file;
                $destPath = $dest . '/' . $file;

                if (is_dir($sourcePath)) {
                    $this->copyDirectory($sourcePath, $destPath);
                } else {
                    copy($sourcePath, $destPath);
                }
            }
        }
    }
}
```

## Componentes da Extensão

### Service Principal

```php
<?php

namespace Vendor\PivotPhpExtension\Services;

class ExtensionService
{
    private array $config;
    private $database;
    private $logger;

    public function __construct(array $config, $database, $logger)
    {
        $this->config = $config;
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * Processar ação principal da extensão
     */
    public function processAction(array $data): array
    {
        $this->logger->info('Processing extension action', $data);

        try {
            // Lógica principal da extensão
            $result = $this->executeBusinessLogic($data);

            // Salvar no banco se necessário
            if ($this->config['save_results'] ?? false) {
                $this->saveResult($result);
            }

            return [
                'success' => true,
                'data' => $result,
                'timestamp' => time()
            ];

        } catch (\Exception $e) {
            $this->logger->error('Extension action failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obter estatísticas da extensão
     */
    public function getStats(): array
    {
        $stmt = $this->database->query(
            "SELECT COUNT(*) as total, AVG(processing_time) as avg_time
             FROM extension_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        return $stmt->fetch();
    }

    /**
     * Lógica de negócio específica
     */
    private function executeBusinessLogic(array $data): array
    {
        // Implementar lógica específica da extensão
        return [
            'processed' => $data,
            'timestamp' => time(),
            'version' => $this->config['version'] ?? '1.0.0'
        ];
    }

    /**
     * Salvar resultado no banco
     */
    private function saveResult(array $result): void
    {
        $stmt = $this->database->prepare(
            "INSERT INTO extension_logs (data, created_at) VALUES (?, NOW())"
        );
        $stmt->execute([json_encode($result)]);
    }
}
```

### Controller

```php
<?php

namespace Vendor\PivotPhpExtension\Controllers;

class ExtensionController
{
    private ExtensionService $extensionService;

    public function __construct(ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    /**
     * Página inicial da extensão
     */
    public function index($req, $res)
    {
        return $res->json([
            'extension' => 'MyExtension',
            'version' => '1.0.0',
            'status' => 'active',
            'endpoints' => [
                'GET /extension/' => 'Extension info',
                'GET /extension/status' => 'Extension status',
                'POST /extension/action' => 'Execute action'
            ]
        ]);
    }

    /**
     * Status da extensão
     */
    public function status($req, $res)
    {
        $stats = $this->extensionService->getStats();

        return $res->json([
            'status' => 'healthy',
            'uptime' => time(),
            'stats' => $stats
        ]);
    }

    /**
     * Executar ação
     */
    public function action($req, $res)
    {
        $data = $req->body;

        // Validação básica
        if (empty($data)) {
            return $res->status(400)->json([
                'error' => 'Data is required'
            ]);
        }

        $result = $this->extensionService->processAction((array)$data);

        if ($result['success']) {
            return $res->json($result);
        }

        return $res->status(500)->json($result);
    }
}
```

### Middleware

```php
<?php

namespace Vendor\PivotPhpExtension\Middleware;

class ExtensionMiddleware
{
    /**
     * Processar requisição
     */
    public function __invoke($req, $res, $next)
    {
        // Verificar se extensão está ativa
        $config = app('config')->get('extension');

        if (!($config['enabled'] ?? true)) {
            return $res->status(503)->json([
                'error' => 'Extension is disabled'
            ]);
        }

        // Verificar rate limiting se configurado
        if ($config['rate_limit'] ?? false) {
            $ip = $req->ip();

            if ($this->isRateLimited($ip)) {
                return $res->status(429)->json([
                    'error' => 'Rate limit exceeded'
                ]);
            }
        }

        // Log da requisição
        $logger = app('logger');
        $logger->info('Extension request', [
            'path' => $req->pathCallable,
            'method' => $req->method,
            'ip' => $req->ip()
        ]);

        return $next();
    }

    /**
     * Verificar rate limiting
     */
    private function isRateLimited(string $ip): bool
    {
        // Implementar lógica de rate limiting
        // Por exemplo, usando cache ou banco de dados
        return false;
    }
}
```

## Configuração da Extensão

### Arquivo de Configuração

```php
<?php

// config/extension.php
return [
    'enabled' => env('EXTENSION_ENABLED', true),
    'version' => '1.0.0',
    'auto_migrate' => env('EXTENSION_AUTO_MIGRATE', false),
    'save_results' => env('EXTENSION_SAVE_RESULTS', true),
    'rate_limit' => env('EXTENSION_RATE_LIMIT', false),
    'cache_ttl' => env('EXTENSION_CACHE_TTL', 3600),

    'database' => [
        'table_prefix' => 'ext_',
        'enable_logging' => true
    ],

    'api' => [
        'timeout' => 30,
        'retries' => 3,
        'base_url' => env('EXTENSION_API_URL')
    ],

    'features' => [
        'advanced_processing' => env('EXTENSION_ADVANCED', false),
        'webhook_support' => env('EXTENSION_WEBHOOKS', false)
    ]
];
```

## Auto-Discovery

### Configuração do Composer

```json
{
    "extra": {
        "pivotphp-core": {
            "providers": [
                "Vendor\\PivotPhpExtension\\MyExtensionServiceProvider"
            ],
            "config": {
                "extension": "config/extension.php"
            },
            "migrations": "migrations/",
            "assets": "resources/assets/",
            "routes": "routes/extension.php"
        }
    }
}
```

### Extension Manager

O PivotPHP automaticamente descobre e registra extensões:

```php
// src/Providers/ExtensionServiceProvider.php (no framework)
class ExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExtensionManager::class);
    }

    public function boot(): void
    {
        $manager = $this->app->get(ExtensionManager::class);
        $manager->discoverExtensions();
        $manager->loadExtensions();
    }
}
```

## Publicação da Extensão

### Comando de Publicação

```php
// Comando para publicar assets e configurações
class PublishExtensionCommand
{
    public function handle()
    {
        $this->publishConfig();
        $this->publishAssets();
        $this->publishMigrations();
    }

    private function publishConfig()
    {
        $source = __DIR__ . '/config/extension.php';
        $dest = base_path('config/extension.php');

        if (!file_exists($dest)) {
            copy($source, $dest);
            echo "Configuration published to config/extension.php\n";
        }
    }

    private function publishAssets()
    {
        $source = __DIR__ . '/resources/assets';
        $dest = public_path('vendor/extension');

        if (is_dir($source)) {
            $this->copyDirectory($source, $dest);
            echo "Assets published to public/vendor/extension\n";
        }
    }
}
```

## Testando Extensões

### Suite de Testes

```php
<?php

namespace Vendor\PivotPhpExtension\Tests;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Vendor\PivotPhpExtension\MyExtensionServiceProvider;

class ExtensionTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
        $this->app->register(MyExtensionServiceProvider::class);
        $this->app->boot();
    }

    public function testExtensionIsRegistered(): void
    {
        $this->assertTrue($this->app->getContainer()->has('extension'));
    }

    public function testExtensionService(): void
    {
        $service = $this->app->get('extension');
        $result = $service->processAction(['test' => 'data']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testExtensionEndpoint(): void
    {
        $response = $this->app->handle(
            new Request('GET', '/extension/')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('MyExtension', $body['extension']);
    }
}
```

## Exemplos de Extensões

### Extensão de Cache

```php
class CacheExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('cache.advanced', function($app) {
            return new AdvancedCacheService(
                $app->get('config')->get('cache_extension'),
                $app->get('database')
            );
        });
    }

    public function boot(): void
    {
        // Registrar middleware de cache automático
        $this->app->use(AutoCacheMiddleware::class);

        // Registrar rotas de gerenciamento
        Router::group('/cache-admin', function() {
            Router::get('/stats', [CacheController::class, 'stats']);
            Router::delete('/clear', [CacheController::class, 'clear']);
        });
    }
}
```

### Extensão de Analytics

```php
class AnalyticsExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('analytics', AnalyticsService::class);
    }

    public function boot(): void
    {
        // Middleware para tracking automático
        $this->app->use(function($req, $res, $next) {
            $start = microtime(true);
            $response = $next();
            $duration = microtime(true) - $start;

            $analytics = app('analytics');
            $analytics->track('request', [
                'path' => $req->pathCallable,
                'method' => $req->method,
                'duration' => $duration,
                'status' => $res->getStatusCode()
            ]);

            return $response;
        });
    }
}
```

## Boas Práticas para Extensões

### Design

1. **Modularidade** - Mantenha componentes independentes
2. **Configurabilidade** - Permita personalização via config
3. **Extensibilidade** - Use interfaces e abstrações
4. **Compatibilidade** - Mantenha backward compatibility

### Performance

1. **Lazy Loading** - Carregue apenas quando necessário
2. **Cache** - Use cache para operações pesadas
3. **Otimização** - Minimize overhead da extensão
4. **Profiling** - Monitore performance

### Manutenibilidade

1. **Documentação** - Documente APIs e configurações
2. **Testes** - Mantenha cobertura de testes alta
3. **Versionamento** - Use versionamento semântico
4. **Changelog** - Documente mudanças

As extensões permitem criar funcionalidades reutilizáveis e modulares para o PivotPHP. Use os padrões estabelecidos para garantir qualidade e compatibilidade.
