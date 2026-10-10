# Providers no PivotPHP

Os Service Providers são o ponto central para registrar e configurar serviços na aplicação. Eles implementam o padrão de Injeção de Dependências (PSR-11) e permitem organizar a inicialização de componentes.

## Conceitos Fundamentais

### O que são Service Providers?

Service Providers são classes que:
- **Registram serviços** no container de dependências
- **Configuram** como os serviços devem ser criados
- **Fazem bootstrap** de funcionalidades da aplicação
- **Organizam** a inicialização de componentes

### Container PSR-11

O PivotPHP usa um container que implementa PSR-11:
- **Injeção de dependências** automática
- **Singleton** e instâncias temporárias
- **Aliases** para facilitar acesso
- **Factory functions** para criação customizada

## Criando Service Providers

### Provider Básico

```php
<?php

use PivotPHP\Core\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Registrar serviços no container
     */
    public function register(): void
    {
        // Registrar configuração do banco
        $this->app->singleton('db.config', function($app) {
            return [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'database' => $_ENV['DB_NAME'] ?? 'app',
                'username' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? '',
                'charset' => 'utf8mb4'
            ];
        });

        // Registrar conexão do banco
        $this->app->singleton('database', function($app) {
            $config = $app->get('db.config');
            return new PDO(
                "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        });

        // Alias para facilitar acesso
        $this->app->alias('db', 'database');
    }

    /**
     * Bootstrap após todos os providers serem registrados
     */
    public function boot(): void
    {
        // Executar migrations ou seeds se necessário
        if ($_ENV['APP_ENV'] === 'development') {
            $this->runMigrations();
        }
    }

    /**
     * Executar migrations
     */
    private function runMigrations(): void
    {
        $db = $this->app->get('database');

        // Verificar se tabela existe
        $query = $db->query("SHOW TABLES LIKE 'users'");
        if ($query->rowCount() === 0) {
            $db->exec("
                CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
    }
}
```

### Provider com Múltiplos Serviços

```php
class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository
        $this->app->singleton(UserRepository::class, function($app) {
            return new UserRepository($app->get('database'));
        });

        // Service
        $this->app->singleton(UserService::class, function($app) {
            return new UserService(
                $app->get(UserRepository::class),
                $app->get('logger')
            );
        });

        // Validator
        $this->app->singleton(UserValidator::class);

        // Controller
        $this->app->bind(UserController::class, function($app) {
            return new UserController(
                $app->get(UserService::class),
                $app->get(UserValidator::class)
            );
        });
    }
}
```

## Registrando Providers

### No Constructor da Aplicação

```php
use PivotPHP\Core\Core\Application;

$app = new Application(__DIR__);

// Registrar providers customizados
$app->register(DatabaseServiceProvider::class);
$app->register(UserServiceProvider::class);
$app->register(CacheServiceProvider::class);
$app->register(MailServiceProvider::class);
```

### Via Configuração

```php
// config/app.php
return [
    'providers' => [
        DatabaseServiceProvider::class,
        UserServiceProvider::class,
        CacheServiceProvider::class,
        MailServiceProvider::class,

        // Providers condicionais
        $_ENV['APP_ENV'] === 'development' ? DebugServiceProvider::class : null,
        $_ENV['ENABLE_CACHE'] === 'true' ? RedisServiceProvider::class : FileServiceProvider::class,
    ]
];
```

### Providers Automáticos

```php
// Os providers básicos são registrados automaticamente:
// - ContainerServiceProvider
// - EventServiceProvider
// - LoggingServiceProvider
// - HookServiceProvider
// - ExtensionServiceProvider
```

## Usando o Container

### Resolvendo Dependências

```php
// Na aplicação
$app->get('/api/users', function($req, $res) use ($app) {
    $userService = $app->get(UserService::class);
    $users = $userService->getAllUsers();

    return $res->json($users);
});

// Em controllers
class UserController
{
    public function __construct(
        private UserService $userService,
        private UserValidator $validator
    ) {}

    public function index($req, $res)
    {
        $users = $this->userService->getAllUsers();
        return $res->json($users);
    }

    public function store($req, $res)
    {
        $errors = $this->validator->validate($req->psr7()->getParsedBody());

        if (!empty($errors)) {
            return $res->status(422)->json(['errors' => $errors]);
        }

        $user = $this->userService->createUser($req->psr7()->getParsedBody());
        return $res->status(201)->json($user);
    }
}
```

### Tipos de Binding

```php
class MyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton - uma instância para toda aplicação
        $this->app->singleton('cache', function($app) {
            return new FileCache('/tmp/cache');
        });

        // Bind - nova instância a cada resolução
        $this->app->bind('mailer', function($app) {
            return new Mailer($app->get('mail.config'));
        });

        // Instance - registrar instância específica
        $this->app->instance('config', new Config(__DIR__ . '/config'));

        // Alias - criar apelido para serviço
        $this->app->alias('db', 'database');
        $this->app->alias('log', 'logger');
    }
}
```

## Patterns de Providers

### Repository Pattern

```php
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Interface binding
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);

        // Repository com dependências
        $this->app->singleton(UserRepository::class, function($app) {
            return new UserRepository(
                $app->get('database'),
                $app->get('cache')
            );
        });
    }
}

// Uso
class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}
}
```

### Service Layer Pattern

```php
class ServiceLayerProvider extends ServiceProvider
{
    public function register(): void
    {
        // Services
        $this->app->singleton(UserService::class, function($app) {
            return new UserService(
                $app->get(UserRepositoryInterface::class),
                $app->get(EventDispatcherInterface::class),
                $app->get('logger')
            );
        });

        $this->app->singleton(OrderService::class, function($app) {
            return new OrderService(
                $app->get(OrderRepositoryInterface::class),
                $app->get(UserService::class),
                $app->get(PaymentService::class)
            );
        });
    }
}
```

### Factory Pattern

```php
class FactoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Factory para diferentes tipos de cache
        $this->app->singleton('cache.factory', function($app) {
            return new CacheFactory([
                'file' => fn() => new FileCache('/tmp/cache'),
                'redis' => fn() => new RedisCache($app->get('redis')),
                'memory' => fn() => new MemoryCache()
            ]);
        });

        // Cache padrão baseado na configuração
        $this->app->singleton('cache', function($app) {
            $factory = $app->get('cache.factory');
            $driver = $app->get('config')->get('cache.driver', 'file');
            return $factory->create($driver);
        });
    }
}
```

## Providers Avançados

### Provider Condicional

```php
class ConditionalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar serviços baseado em ambiente
        if ($this->app->get('config')->get('app.env') === 'production') {
            $this->registerProductionServices();
        } else {
            $this->registerDevelopmentServices();
        }

        // Registrar baseado em feature flags
        if ($this->app->get('config')->get('features.advanced_logging')) {
            $this->registerAdvancedLogging();
        }
    }

    private function registerProductionServices(): void
    {
        $this->app->singleton('logger', function($app) {
            return new ProductionLogger($app->get('config')->get('logging'));
        });
    }

    private function registerDevelopmentServices(): void
    {
        $this->app->singleton('logger', function($app) {
            return new DevelopmentLogger();
        });

        // Debug tools apenas em desenvolvimento
        $this->app->singleton('debugger', DebuggerService::class);
    }
}
```

### Provider com Configuração Externa

```php
class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Carregar configuração específica
        $apiConfig = $this->loadApiConfig();

        $this->app->instance('api.config', $apiConfig);

        // Registrar clientes de API
        foreach ($apiConfig['clients'] as $name => $config) {
            $this->app->singleton("api.client.{$name}", function($app) use ($config) {
                return new ApiClient($config);
            });
        }
    }

    private function loadApiConfig(): array
    {
        $configPath = $this->app->basePath('config/api.php');

        if (!file_exists($configPath)) {
            return ['clients' => []];
        }

        return require $configPath;
    }
}
```

### Provider com Auto-Discovery

```php
class AutoDiscoveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Descobrir e registrar automaticamente controllers
        $this->autoRegisterControllers();

        // Descobrir e registrar middlewares
        $this->autoRegisterMiddlewares();
    }

    private function autoRegisterControllers(): void
    {
        $controllerPath = $this->app->basePath('src/Controllers');

        if (!is_dir($controllerPath)) {
            return;
        }

        $files = glob($controllerPath . '/*Controller.php');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);

            if (class_exists($className)) {
                $this->app->bind($className, function($app) use ($className) {
                    return $app->make($className);
                });
            }
        }
    }

    private function getClassNameFromFile(string $file): string
    {
        $basename = basename($file, '.php');
        return "App\\Controllers\\{$basename}";
    }
}
```

## Testando Providers

### Provider de Teste

```php
class TestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Mock database para testes
        $this->app->singleton('database', function($app) {
            return new InMemoryDatabase();
        });

        // Mock mailer
        $this->app->singleton('mailer', function($app) {
            return new TestMailer();
        });

        // Cache em memória para testes rápidos
        $this->app->singleton('cache', function($app) {
            return new ArrayCache();
        });
    }
}

// Uso em testes
class UserServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $this->app = new Application();
        $this->app->register(TestServiceProvider::class);
        $this->app->boot();
    }

    public function testCreateUser(): void
    {
        $userService = $this->app->get(UserService::class);
        $user = $userService->createUser(['name' => 'Test', 'email' => 'test@test.com']);

        $this->assertNotNull($user);
        $this->assertEquals('Test', $user['name']);
    }
}
```

## Debugging Providers

### Listando Serviços Registrados

```php
$app->get('/debug/services', function($req, $res) use ($app) {
    $container = $app->getContainer();

    // Não há método público para listar providers; verifique os serviços diretamente
    return $res->json([
        'sample_services' => [
            'database' => $container->has('database'),
            'logger' => $container->has('logger'),
            'cache' => $container->has('cache')
        ]
    ]);
});
```

### Provider de Debug

```php
class DebugServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('debug', function($app) {
            return new DebugService($app);
        });
    }

    public function boot(): void
    {
        if ($_ENV['APP_DEBUG'] ?? false) {
            $this->registerDebugRoutes();
        }
    }

    private function registerDebugRoutes(): void
    {
        $this->app->get('/_debug/container', function($req, $res) {
            $debug = $this->app->get('debug');
            return $res->json($debug->getContainerInfo());
        });
    }
}
```

## Boas Práticas

### Organização

1. **Um provider por domínio** (User, Order, Payment)
2. **Providers específicos** para diferentes ambientes
3. **Configuração externa** para providers complexos
4. **Auto-discovery** para reduzir configuração manual

### Performance

1. **Use singleton** para serviços pesados
2. **Lazy loading** com factory functions
3. **Registre apenas o necessário** em cada ambiente
4. **Cache configurações** complexas

### Manutenibilidade

1. **Documente dependências** de cada provider
2. **Use interfaces** para facilitar testes
3. **Mantenha providers focados** em uma responsabilidade
4. **Teste providers isoladamente**

Os Service Providers são fundamentais para manter uma aplicação PivotPHP organizada, testável e performática. Use-os para centralizar a configuração de serviços e manter a separação de responsabilidades.
