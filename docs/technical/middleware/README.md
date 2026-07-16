# 🛡️ Middlewares do PivotPHP

Guia completo dos middlewares disponíveis no framework, suas configurações, uso prático e criação de middlewares customizados.

> **Nota de revisão:** a versão anterior deste documento continha diversas classes e métodos
> fictícios (`SecurityMiddleware`, `ValidationMiddleware`, `CorsMiddleware::development()` /
> `::production()` / `::simple()` / `::benchmark()`, `$app->group()`, entre outros) que nunca
> existiram em `src/`, ou chaves de configuração incorretas. Este documento foi corrigido
> para refletir apenas classes, namespaces e assinaturas reais (v2.1.1).

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Middlewares de Segurança](#middlewares-de-segurança)
- [Middlewares Core](#middlewares-core)
- [Uso Prático](#uso-prático)
- [Criação de Middleware Customizado](#criação-de-middleware-customizado)
- [Performance e Otimização](#performance-e-otimização)
- [Padrões e Boas Práticas](#padrões-e-boas-práticas)

## 🔍 Visão Geral

O PivotPHP oferece uma arquitetura de middleware que suporta tanto o padrão PSR-15
(`MiddlewareInterface::process()`) quanto o padrão Express-style legado
(`PivotPHP\Core\Middleware\Core\MiddlewareInterface::handle($request, $response, $next)`
via `BaseMiddleware`).

### Arquitetura do Sistema

```php
use PivotPHP\Core\Middleware\Security\{SecurityHeadersMiddleware, CsrfMiddleware, AuthMiddleware};
use PivotPHP\Core\Middleware\Http\CorsMiddleware;

// Stack de middleware é executado na ordem de registro
$app->use(new SecurityHeadersMiddleware());  // 1º - Headers de segurança
$app->use(new CorsMiddleware());             // 2º - CORS
$app->use(new AuthMiddleware());             // 3º - Autenticação

// Rota final
$app->get('/api/users', function ($req, $res) {
    // Handler da rota
});
```

> **Nota:** `Application` (`$app`) registra apenas middleware **global** via `use()` (e seu
> alias `middleware()`). Não existe `$app->group()` nem suporte a middleware por
> rota/grupo através de `Application::get()`/`post()`/etc. — essas assinaturas aceitam
> apenas `(string $path, callable|array $handler)`. Para middleware escopado a um prefixo de
> rotas, use o Router estático diretamente:
> `PivotPHP\Core\Routing\Router::group($prefix, $callback, $middlewares)`.

## 🛡️ Middlewares de Segurança

### 1. SecurityHeadersMiddleware
**Localização**: `src/Middleware/Security/SecurityHeadersMiddleware.php`
**Namespace**: `PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware`

Adiciona headers de segurança básicos à resposta. Não aceita opções de configuração.

```php
$app->use(new SecurityHeadersMiddleware());
```

**Headers incluídos** (fixos, não configuráveis):
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`

Proteção CSRF e XSS mais elaborada é fornecida por middlewares separados —
`CsrfMiddleware` e `XssMiddleware` — não por opções de `SecurityHeadersMiddleware`.

### 2. CorsMiddleware
**Localização**: `src/Middleware/Http/CorsMiddleware.php`
**Namespace**: `PivotPHP\Core\Middleware\Http\CorsMiddleware`

Middleware CORS PSR-15. Não possui métodos estáticos de conveniência
(`development()`/`production()`/`simple()` não existem) — configure via array no construtor.

```php
$app->use(new CorsMiddleware([
    'origin' => ['https://meuapp.com', 'https://app.exemplo.com'], // string ou array
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'credentials' => true,
    'max_age' => 86400,
    'expose_headers' => ['X-Total-Count'],
]));
```

Chaves de configuração aceitas: `origin`, `methods`, `headers`, `credentials`, `max_age`,
`expose_headers`. (Chaves como `allowed_origins`/`origins`/`maxAge` de exemplos antigos são
silenciosamente ignoradas.)

### 3. AuthMiddleware
**Localização**: `src/Middleware/Security/AuthMiddleware.php`
**Namespace**: `PivotPHP\Core\Middleware\Security\AuthMiddleware`

Sistema de autenticação multi-método com suporte a JWT, Basic Auth, Bearer Token e callback
customizado. Assinatura real: `__construct(array $config = [], array $publicPaths = [])` —
caminhos públicos são o **segundo argumento posicional**, não uma chave dentro de `$config`.

```php
// Autenticação JWT
$app->use(new AuthMiddleware(
    ['authMethods' => ['jwt'], 'jwtSecret' => 'sua_chave_secreta_aqui'],
    ['/public', '/health'] // caminhos públicos: 2º argumento, não 'excludePaths' em $config
));

// Multi-método (detecta automaticamente)
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'bearer'],
    'jwtSecret' => 'chave_jwt',
    'basicAuthCallback' => 'validateUser',
    'bearerAuthCallback' => 'validateApiKey',
]));
```

Chaves de configuração aceitas: `authMethods`, `jwtSecret`, `basicAuthCallback`,
`bearerAuthCallback`, `customAuthCallback`, além de `header`/`prefix`/`secret` usados
internamente pela extração/validação de token.

### 4. RateLimitMiddleware (depreciado) / RateLimiter

`RateLimitMiddleware` (`src/Middleware/Performance/RateLimitMiddleware.php`) está
**depreciado desde v2.1.0** (usa `$_SESSION`) — veja
[RateLimitMiddleware.md](RateLimitMiddleware.md) para detalhes e o substituto recomendado,
`PivotPHP\Core\Middleware\RateLimiter`.

### 5. Validação de dados

Não existe um `ValidationMiddleware` PSR-15 pronto no framework. Para validação de dados,
use `PivotPHP\Core\Validation\Validator` — veja
[ValidationMiddleware.md](ValidationMiddleware.md) para exemplos, incluindo como envolvê-lo
em seu próprio middleware.

## ⚙️ Middlewares Core

### 1. ErrorMiddleware
**Localização**: `src/Middleware/Http/ErrorMiddleware.php`

Tratamento centralizado de erros e exceções. Consulte o código-fonte para as opções de
configuração atuais antes de usar em produção — verifique a assinatura do construtor na
versão instalada.

### 2. CacheMiddleware
**Localização**: `src/Middleware/Performance/CacheMiddleware.php`

```php
use PivotPHP\Core\Middleware\Performance\CacheMiddleware;

// __construct(int $ttl = 300, string $cacheDir = <diretório temporário>)
$app->use(new CacheMiddleware(3600, __DIR__ . '/storage/cache'));
```

## 🚀 Uso Prático

### Configuração Recomendada para API

```php
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Middleware\Security\{SecurityHeadersMiddleware, AuthMiddleware};
use PivotPHP\Core\Middleware\Http\CorsMiddleware;
use PivotPHP\Core\Middleware\RateLimiter;

$app = new Application();

// 1. Segurança básica
$app->use(new SecurityHeadersMiddleware());

// 2. CORS para frontend
$app->use(new CorsMiddleware(['origin' => 'https://meuapp.com']));

// 3. Rate limiting global (RateLimiter, não o RateLimitMiddleware depreciado)
$app->use(new RateLimiter(['max_requests' => 1000, 'window_size' => 3600]));

// 4. Autenticação — aplicada globalmente; combine com verificação de path
// dentro do próprio middleware/handler se precisar excluir rotas públicas,
// ou use publicPaths (2º argumento do construtor).
$app->use(new AuthMiddleware(
    ['authMethods' => ['jwt'], 'jwtSecret' => $_ENV['JWT_SECRET']],
    ['/health']
));

$app->get('/health', function ($req, $res) {
    return $res->json(['status' => 'ok']);
});
```

## 🔧 Criação de Middleware Customizado

### Estrutura Básica (padrão Express-style do framework)

```php
<?php

namespace App\Middleware;

use PivotPHP\Core\Middleware\Core\BaseMiddleware;

class CustomMiddleware extends BaseMiddleware
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'defaultOption' => 'value',
            'enabled' => true,
        ], $config);
    }

    public function handle($request, $response, callable $next)
    {
        if (!$this->config['enabled']) {
            return $next($request, $response);
        }

        $result = $next($request, $response);

        if ($response instanceof \PivotPHP\Core\Http\Response) {
            $response->header('X-Custom-Header', 'processed');
        }

        return $result;
    }
}
```

`BaseMiddleware` (`src/Middleware/Core/BaseMiddleware.php`) implements
`PivotPHP\Core\Middleware\Core\MiddlewareInterface` and is `__invoke`-able, so instances can
be passed directly to `$app->use()`.

### Middleware PSR-15 Customizado

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Psr15CustomMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('processed_by', static::class);
        $response = $handler->handle($request);
        return $response->withHeader('X-Processed-By', 'CustomMiddleware');
    }
}
```

Os middlewares reais de `src/Middleware/Security/` e `src/Middleware/Http/` seguem este
padrão PSR-15 (`process()`), não o padrão Express-style `handle()`.

## ⚡ Performance e Otimização

O framework inclui `PivotPHP\Core\Middleware\MiddlewareStack` (`src/Middleware/MiddlewareStack.php`)
com utilitários para compilar e monitorar pipelines de middleware:

```php
use PivotPHP\Core\Middleware\MiddlewareStack;

$stats = MiddlewareStack::getStats();
// Retorna estatísticas por pipeline: 'executions', 'total_execution_time_ms',
// 'avg_execution_time_ms' (chaveadas pelo cache key do pipeline)

MiddlewareStack::detectRedundantMiddlewares($middlewares); // detecta duplicatas
MiddlewareStack::clearCache();
```

> Os exemplos anteriores desta seção citavam chaves de retorno (`cache_hit_rate`,
> `compiled_pipelines`) e um método `CorsMiddleware::benchmark()` que não existem — foram
> removidos. Confira `src/Middleware/MiddlewareStack.php` diretamente para o conjunto
> completo de métodos antes de depender deles em produção.

## 📋 Padrões e Boas Práticas

### 1. Ordem de Middleware

```php
$app->use(new SecurityHeadersMiddleware());  // Headers de segurança cedo
$app->use(new CorsMiddleware());             // CORS antes de autenticação
$app->use(new RateLimiter());                // Rate limit antes de processamento pesado
$app->use(new AuthMiddleware());             // Autenticação
```

### 2. Testes de Middleware

```php
// tests/Middleware/CustomMiddlewareTest.php
class CustomMiddlewareTest extends TestCase
{
    public function testMiddlewareProcessesRequest()
    {
        $middleware = new CustomMiddleware(['enabled' => true]);
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $called = false;
        $next = function () use (&$called) {
            $called = true;
            return 'processed';
        };

        $result = $middleware->handle($request, $response, $next);

        $this->assertTrue($called);
        $this->assertEquals('processed', $result);
    }
}
```

## 🔗 Links Relacionados

- [RateLimitMiddleware](RateLimitMiddleware.md) - Depreciado; veja o substituto `RateLimiter`
- [ValidationMiddleware](ValidationMiddleware.md) - Validação de dados via `Validator`
- [CustomMiddleware](CustomMiddleware.md) - Criação de middleware customizado

---

## 📚 Recursos Adicionais

- **PSR Compliance**: Suporte a PSR-15 e PSR-7
- **Testing**: Consulte `tests/Middleware/` para exemplos de testes reais do framework

Para dúvidas ou contribuições, consulte o [guia de contribuição](../../contributing/README.md).
