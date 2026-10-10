# Guia do Objeto Application

A classe `Application` é o coração do PivotPHP. Ela gerencia todo o ciclo de vida da aplicação, desde a inicialização até o processamento de requisições.

## Conceitos Fundamentais

A Application funciona como um container principal que:
- **Gerencia dependências** via container PSR-11
- **Registra service providers** para funcionalidades específicas
- **Processa requisições** através do router e middlewares
- **Manipula configurações** da aplicação
- **Trata erros** de forma centralizada

## Criando uma Aplicação

### Inicialização Básica

```php
<?php
require_once 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

// Criar aplicação com caminho base opcional
$app = new Application(__DIR__);

// Definir rotas
$app->get('/', function($req, $res) {
    return $res->json(['message' => 'Hello World!']);
});

// Executar a aplicação
$app->run();
```

> O FQCN da aplicação é `PivotPHP\Core\Core\Application`.

### Configuração Avançada

```php
// Definir caminho base manualmente
$app->setBasePath('/var/www/html');

// Registrar service providers customizados
$app->register(MyCustomServiceProvider::class);

// Configurar middleware global
$app->use(function($req, $res, $next) {
    $res->header('X-Powered-By', 'PivotPHP');
    return $next();
});
```

## Métodos Principais

### Métodos de Roteamento

#### `get(string $path, $handler)`
Registra uma rota GET.

```php
$app->get('/users', function($req, $res) {
    return $res->json($userService->getAll());
});

$app->get('/users/:id', [UserController::class, 'show']);
```

#### `post(string $path, $handler)`
Registra uma rota POST.

```php
$app->post('/users', function($req, $res) {
    $data = $req->psr7()->getParsedBody();
    $user = $userService->create($data);
    return $res->status(201)->json($user);
});
```

#### `put(string $path, $handler)`
Registra uma rota PUT para atualizações completas.

```php
$app->put('/users/:id', function($req, $res) {
    $id = $req->params('id');
    $data = $req->psr7()->getParsedBody();
    $user = $userService->update($id, $data);
    return $res->json($user);
});
```

#### `patch(string $path, $handler)`
Registra uma rota PATCH para atualizações parciais.

```php
$app->patch('/users/:id', function($req, $res) {
    $id = $req->params('id');
    $data = $req->psr7()->getParsedBody();
    $user = $userService->partialUpdate($id, $data);
    return $res->json($user);
});
```

#### `delete(string $path, $handler)`
Registra uma rota DELETE.

```php
$app->delete('/users/:id', function($req, $res) {
    $id = $req->params('id');
    $userService->delete($id);
    return $res->status(204)->send();
});
```

### Métodos de Configuração

#### `use($middleware)`
Adiciona middleware global à aplicação.

```php
// Middleware de função
$app->use(function($req, $res, $next) {
    $start = microtime(true);
    $response = $next();
    $duration = microtime(true) - $start;
    $res->header('X-Response-Time', $duration . 'ms');
    return $response;
});

// Middleware de classe (resolvido pelo container quando registrado)
$app->use(MyAuthMiddleware::class);
// Middleware PSR-15 instanciado (ex.: pivotphp/security)
$app->use(new \PivotPHP\Security\Headers\SecurityHeadersMiddleware());
```

#### `register($provider)`
Registra um service provider.

```php
// Por classe
$app->register(DatabaseServiceProvider::class);

// Por instância
$app->register(new CacheServiceProvider($config));
```

#### `setBasePath(string $path)`
Define o caminho base da aplicação.

```php
$app->setBasePath('/var/www/myapp');

// Isso configura automaticamente:
// - path.base: /var/www/myapp
// - path.config: /var/www/myapp/config
// - path.storage: /var/www/myapp/storage
// - path.public: /var/www/myapp/public
// - path.logs: /var/www/myapp/logs
```

### Métodos de Execução

#### `boot()`
Inicializa a aplicação (chamado automaticamente).

```php
// Manual (normalmente não necessário)
$app->boot();

// O boot é automático ao chamar handle() ou listen()
```

#### `handle(?Request $request = null)`
Processa uma requisição HTTP.

```php
// Processar requisição atual
$response = $app->handle();

// Processar requisição customizada
$request = new Request('GET', '/api/users');
$response = $app->handle($request);
```

#### `run()`
Executa a aplicação processando a requisição atual.

```php
// Processar e enviar resposta
$app->run();
```

Para desenvolvimento, use o servidor embutido do PHP:

```bash
# Iniciar servidor de desenvolvimento
php -S localhost:8000 -t public
```

## Propriedades Importantes

### Container de Dependências

```php
// Acessar o container
$container = $app->getContainer();

// Registrar serviços
$container->instance('database', new Database($config));
$container->singleton(UserService::class);

// Resolver dependências
$userService = $container->get(UserService::class);
```

### Configurações

```php
// Acessar configurações
$config = $app->getConfig();
$debug = $config->get('app.debug', false);

// Definir configurações
$config->set('app.timezone', 'America/Sao_Paulo');
```

### Router

```php
// Acessar o router
$router = $app->getRouter();

// Registrar grupo de rotas
$router->group(['prefix' => 'api/v1'], function($router) {
    $router->get('/users', [UserController::class, 'index']);
    $router->post('/users', [UserController::class, 'store']);
});
```

## Ciclo de Vida da Aplicação

### 1. Criação e Configuração

```php
$app = new Application(__DIR__);
$app->setBasePath(__DIR__);
$app->register(MyServiceProvider::class);
```

### 2. Definição de Rotas e Middlewares

```php
$app->use(MyAuthMiddleware::class);
$app->get('/api/users', [UserController::class, 'index']);
```

### 3. Boot (Inicialização)

```php
// Automático ou manual
$app->boot(); // Carrega config, registra providers, etc.
```

### 4. Processamento de Requisições

```php
$app->run(); // Processa requisição e envia resposta
```

## Padrões e Boas Práticas

### Estrutura de Arquivos Recomendada

```
projeto/
├── config/
│   ├── app.php
│   ├── database.php
│   └── cache.php
├── public/
│   └── index.php
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Providers/
├── storage/
│   └── logs/
└── vendor/
```

### Service Providers

Organize funcionalidades em service providers:

```php
class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('database', function($app) {
            $config = $app->get('config')->get('database');
            return new Database($config);
        });
    }
}
```

### Configuração de Ambiente

Use arquivos `.env` para configurações sensíveis:

```bash
# .env
APP_ENV=production
APP_DEBUG=false
DB_HOST=localhost
DB_DATABASE=myapp
```

### Tratamento de Erros

Configure tratamento centralizado de erros:

```php
$app->use(function($req, $res, $next) {
    try {
        return $next();
    } catch (ValidationException $e) {
        return $res->status(422)->json(['errors' => $e->getErrors()]);
    } catch (Exception $e) {
        $logger = $this->app->get('logger');
        $logger->error($e->getMessage(), ['exception' => $e]);

        return $res->status(500)->json([
            'error' => 'Internal Server Error'
        ]);
    }
});
```

## Integração com PSR Standards

A Application suporta diversos padrões PSR:

- **PSR-3**: Logging com LoggerInterface
- **PSR-7**: HTTP Message Interfaces
- **PSR-11**: Container Interface
- **PSR-14**: Event Dispatcher
- **PSR-15**: HTTP Server Request Handlers

```php
// Exemplo de uso PSR
$container = $app->getContainer(); // PSR-11
$logger = $container->get(LoggerInterface::class); // PSR-3
$eventDispatcher = $container->get(EventDispatcherInterface::class); // PSR-14
```

## Extensibilidade

### Hooks e Eventos

```php
// Hook (ações/filtros da aplicação)
$app->addAction('order.created', fn (array $context) => notify($context['id']));
$app->doAction('order.created', ['id' => 42]);

// Evento PSR-14
$app->on(UserCreated::class, fn (UserCreated $event) => audit($event));
$app->dispatchEvent(new UserCreated($user));
```

### Extensões

```php
$app->register(MyCustomProvider::class);                     // service provider
$app->registerExtension('custom', MyCustomProvider::class);  // provider registrado com nome
```

Detalhes: [extensions/README.md](extensions/README.md).

## Performance e Otimização

### Dicas de Performance

1. **Use Service Providers**: Para lazy loading de serviços
2. **Configure Cache**: Para rotas e configurações
3. **Otimize Middlewares**: Ordene por prioridade de uso
4. **Profile Requests**: Use ferramentas de profiling

```php
// Exemplo de middleware de profiling
$app->use(function($req, $res, $next) {
    $start = microtime(true);
    $response = $next();
    $time = (microtime(true) - $start) * 1000;

    if ($time > 100) { // Log requests lentas
        $logger = $this->app->get('logger');
        $logger->warning('Slow request', [
            'path' => $req->path(),
            'time' => $time
        ]);
    }

    return $response;
});
```

A classe Application é projetada para ser simples de usar, mas poderosa o suficiente para aplicações complexas. Use os padrões recomendados para manter seu código organizado e performático.
