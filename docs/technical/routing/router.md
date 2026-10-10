# Roteamento

O roteador é o pacote [`pivotphp/core-routing`](https://github.com/PivotPHP/pivotphp-core-routing)
(`PivotPHP\Routing\Router\Router`). O core registra rotas nele, casa a requisição e executa o handler
com `ExpressRequest`/`ExpressResponse`. A documentação completa do roteador (padrões, grupos,
arquivos estáticos) está no README do core-routing; esta página cobre o uso pelo core.

## Registrar rotas

```php
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/:id<\d+>', [UserController::class, 'show']);
$app->post('/users', [UserController::class, 'store']);
$app->put('/users/:id<\d+>', [UserController::class, 'update']);
$app->patch('/users/:id<\d+>', [UserController::class, 'patch']);
$app->delete('/users/:id<\d+>', [UserController::class, 'destroy']);
```

Parâmetros: `:id`, `{id}`; constraints: `:id<\d+>` e atalhos `<int>`, `<slug>`, `<alpha>`,
`<alnum>`, `<uuid>`, `<date>`, `<year>`, `<month>`, `<day>`. Leitura no handler:
`$req->param('id')`, `$req->params()`.

## Grupos e middlewares de rota

Grupos e middlewares por rota usam o `Router` diretamente (a `Application` não tem `group()`):

```php
use PivotPHP\Routing\Router\Router;

Router::group('/admin', function (): void {
    Router::get('/stats', fn ($req, $res) => $res->json(['ok' => true]));
}, [$requireAdmin]);

// Middleware só desta rota: 4º argumento
Router::get('/reports', $handler, [], $auditMiddleware);
```

Middlewares de grupo/rota seguem as mesmas formas dos globais (PSR-15 ou
`fn ($req, $res, $next)`) e rodam depois dos middlewares globais.

## Semântica HTTP

| Situação | Resposta |
|---|---|
| Caminho sem rota | `404` |
| Caminho com rota para outro método | `405` + `Allow` |
| `HEAD` sem rota `HEAD` | executa a rota `GET`, sem corpo |
| `OPTIONS` sem rota `OPTIONS` | `204` + `Allow` |

## Arquivos estáticos

```php
$app->staticFiles('/assets', __DIR__ . '/public');
```

Cada arquivo do diretório vira uma rota `GET` (`StaticFileManager` do core-routing): não há listagem
de diretório, dotfiles não são servidos e `../` não sai da pasta. Links simbólicos são seguidos —
veja [STATIC_FILE_MANAGERS.md](STATIC_FILE_MANAGERS.md).

## Estado do roteador

O `Router` mantém estado estático; a `Application` chama `Router::clear()` no boot, então cada
instância começa com a tabela de rotas vazia. Em testes que usam o `Router` diretamente, chame
`Router::clear()` no `setUp()`.
