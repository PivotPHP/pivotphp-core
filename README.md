# PivotPHP Microframework

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Latest Stable Version](https://poser.pugx.org/pivotphp/core/v/stable)](https://packagist.org/packages/pivotphp/core)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%209-brightgreen.svg)](https://phpstan.org/)
[![PSR-7 / PSR-15](https://img.shields.io/badge/PSR--7%20%2F%20PSR--15-compliant-brightgreen)](https://www.php-fig.org/psr/psr-15/)

---

## 🚀 O que é o PivotPHP?

**PivotPHP** é um microframework PHP inspirado no Express.js para construir **APIs**, pensado para
provas de conceito, protótipos e estudos. A sintaxe é a do Express (`$app->get(...)`,
`$req->param()`, `$res->json()`), sobre mensagens **PSR-7** e uma pipeline **PSR-15**.

O core faz uma coisa: liga aplicação, roteamento e pipeline. As demais responsabilidades vivem em
pacotes próprios, instalados junto com o core:

| Pacote | Responsabilidade |
|---|---|
| [`pivotphp/http`](https://github.com/PivotPHP/pivotphp-http) | PSR-7/PSR-17 (nyholm/psr7), fachada `ExpressRequest`/`ExpressResponse`, parsing de corpo, emissor |
| [`pivotphp/core-routing`](https://github.com/PivotPHP/pivotphp-core-routing) | Registro, compilação e casamento de rotas; grupos; arquivos estáticos |
| [`pivotphp/security`](https://github.com/PivotPHP/pivotphp-security) | CORS, headers de segurança, CSRF, JWT, rate limiting, proxies confiáveis |

## ✨ Recursos

- 🛣️ Rotas Express (`get/post/put/patch/delete`), parâmetros `:id`/`{id}`, constraints (`:id<\d+>`, `<slug>`, `<uuid>`...), grupos e middlewares por rota
- 🎯 Handlers como closure, função ou array callable (`[Controller::class, 'metodo']`)
- 🧅 Pipeline PSR-15: qualquer `MiddlewareInterface` ou middleware callable `fn ($req, $res, $next)`
- 📦 Corpo JSON/form parseado automaticamente; JSON inválido → `400`
- 🚦 `405` com `Allow`, `OPTIONS` e `HEAD` tratados pelo core
- 🛡️ Segurança via `pivotphp/security` (validada na configuração, *fail closed*)
- 📚 Documentação OpenAPI 3 / Swagger UI gerada das rotas
- 🏗️ Container PSR-11, service providers, eventos PSR-14, hooks e extensões
- 🧪 PHPStan nível 9, PSR-12, exemplos verificados por testes

---

## 🚀 Início Rápido

```bash
composer require pivotphp/core
```

```php
<?php
require 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

$app->get('/hello/:name', fn ($req, $res) => $res->json([
    'message' => 'Hello, ' . $req->param('name') . '!',
]));

$app->post('/users', function ($req, $res) {
    $name = $req->input('name');                       // JSON ou form

    if (!is_string($name) || $name === '') {
        return $res->error(422, 'The "name" field is required');
    }

    return $res->status(201)->json(['name' => $name]);
});

$app->run();
```

```bash
php -S localhost:8000 index.php
curl http://localhost:8000/hello/PivotPHP
```

## 🛣️ Rotas

```php
// Parâmetros e constraints
$app->get('/users/:id<\d+>', [UserController::class, 'show']);   // só dígitos
$app->get('/articles/:slug<slug>', fn ($req, $res) => $res->json(['slug' => $req->param('slug')]));
$app->get('/files/{name}', fn ($req, $res) => $res->json($req->params()));

// Handlers: closure, função nomeada ou array callable (métodos públicos)
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [$controller, 'store']);

// Grupos e middlewares por grupo/rota (cada Application tem seu próprio router)
$app->group('/admin', function () use ($app): void {
    $app->get('/stats', fn ($req, $res) => $res->json(['ok' => true]));
}, [$requireAdmin]);

$app->get('/health', fn ($req, $res) => $res->json(['ok' => true]), [], $logRequest); // middleware de rota
```

`'Controller@method'` não é suportado (`TypeError`). Guia completo:
[Sintaxe de rotas](docs/technical/routing/SYNTAX_GUIDE.md).

## 📨 Requisição e resposta

Os handlers recebem `ExpressRequest` e `ExpressResponse` (`pivotphp/http`):

```php
$app->get('/inspect/:id', fn ($req, $res) => $res
    ->status(200)
    ->header('X-Example', 'yes')
    ->json([
        'id' => $req->param('id'),
        'page' => $req->query('page', '1'),
        'trace' => $req->header('X-Trace'),
        'ip' => $req->ip(),
        'psr7' => $req->psr7()->getMethod(),   // ServerRequestInterface subjacente
    ]));
```

Respostas: `json()`, `text()`, `html()`, `redirect()`, `noContent()`, `send()`, `error()`;
cookies com `cookie($nome, $valor, ['httpOnly' => true, 'sameSite' => 'Lax'])`.

## 🧅 Middlewares

```php
use Psr\Http\Server\MiddlewareInterface;

// PSR-15
$app->use(new TimingMiddleware());

// Callable: $req é o ServerRequestInterface; altere a resposta devolvida por $next()
$app->use(function ($req, $res, $next) {
    $response = $next($req->withAttribute('request_id', bin2hex(random_bytes(8))));

    return $response->withHeader('X-Powered-By', 'PivotPHP');
});

// Curto-circuito: responda sem chamar $next()
$app->use(fn ($req, $res, $next) => $req->getHeaderLine('X-Block') !== ''
    ? $res->error(403, 'Blocked')
    : $next());
```

Middlewares globais rodam em ordem de registro, antes do roteamento (inclusive para 404/OPTIONS).

## 🛡️ Segurança

```php
use PivotPHP\Http\Factory\Psr17Factory;
use PivotPHP\Security\Cors\{CorsConfig, CorsMiddleware};
use PivotPHP\Security\Headers\SecurityHeadersMiddleware;
use PivotPHP\Security\Jwt\{JwtAuthMiddleware, JwtConfig, JwtIssuer};

$factory = new Psr17Factory();
$jwt = new JwtConfig($_ENV['JWT_SECRET'], publicPaths: ['/login', '/health']); // HS256: ≥ 32 bytes

$app->use(new SecurityHeadersMiddleware());
$app->use(new CorsMiddleware($factory, new CorsConfig(['https://app.example.com'], allowCredentials: true)));
$app->use(new JwtAuthMiddleware($factory, $jwt));

$app->post('/login', fn ($req, $res) => $res->json([
    'token' => (new JwtIssuer($jwt))->issue(['sub' => '42'], ttl: 3600),
]));
$app->get('/me', fn ($req, $res) => $res->json($req->psr7()->getAttribute('user')));
```

JWT precisa de `firebase/php-jwt`; headers, CSRF e rate limit têm adapters próprios — veja o
[README do pivotphp/security](https://github.com/PivotPHP/pivotphp-security#readme).

## 📖 OpenAPI / Swagger

```php
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;

$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',        // JSON OpenAPI 3.0
    'swagger_path' => '/swagger',  // Swagger UI
]));
```

Gera método + caminho de cada rota registrada; descrições e esquemas não são inferidos.

## 🔍 Erros

Erros viram respostas JSON (`{"error": true, "message": ..., "error_id": ...}`); com
`app.debug` ligado, a resposta inclui detalhes da exceção. Exceções que implementam
`PivotPHP\Http\Exception\HttpExceptionInterface` definem o status (ex.: JSON inválido → `400`).
Falhas de configuração de rota lançam `ContextualException` com contexto e sugestões
(`getContext()`, `getSuggestions()`, `getCategory()`).

## 📚 Exemplos e documentação

- [`examples/`](examples/) — cada exemplo roda com `php -S` e é verificado por `tests/Integration/ExamplesTest.php`
- [Índice da documentação](docs/index.md) · [Referência da API](docs/API_REFERENCE.md) · [Guia de migração](docs/MIGRATION_GUIDE.md)

## 🔄 Migração para a 4.0

A 4.0 adota `pivotphp/http` e `pivotphp/security`, remove a camada HTTP própria, os middlewares de
segurança nativos e as otimizações sem efeito em PHP-FPM. Veja o [CHANGELOG](CHANGELOG.md) e o
[guia de migração](docs/MIGRATION_GUIDE.md).

## 🧩 Extensões

- [`pivotphp/cycle-orm`](https://github.com/PivotPHP/pivotphp-cycle-orm) — ⏸️ **pausado** (repositório arquivado;
  suporta apenas o core 1.x).

Extensões são service providers (`PivotPHP\Core\Providers\ServiceProvider`) registrados com
`$app->register(MeuProvider::class)`.

---

## ⚠️ Manutenção do Projeto

**PivotPHP é mantido por uma pessoa** e é indicado para provas de conceito, protótipos, estudos e
projetos educacionais. Para sistemas críticos com suporte dedicado, considere Laravel, Symfony ou
Slim.

## 🤝 Contribuindo

Veja o [Guia de Contribuição](CONTRIBUTING.md). Issues e PRs são bem-vindos.

## 📄 Licença

MIT — veja [LICENSE](LICENSE).
