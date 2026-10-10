# HTTP no PivotPHP

Desde a **4.0.0** a camada HTTP vem do pacote [`pivotphp/http`](https://github.com/PivotPHP/pivotphp-http):

- **Mensagens PSR-7/PSR-17**: [nyholm/psr7](https://github.com/Nyholm/psr7) (`psr/http-message` ^1.1 ou ^2.0).
- **Fachada Express**: rotas recebem `PivotPHP\Http\ExpressRequest` e `PivotPHP\Http\ExpressResponse`.
- **Corpo**: JSON (`application/json`, `application/*+json`) e formulário (inclusive PUT/PATCH/DELETE) são
  parseados pelo core antes da pipeline; corpo malformado gera `400`.
- **Emissão**: `Application::run()` usa `SapiEmitter` (corpo em stream; sem corpo em `HEAD`/`204`/`304`).

| Página | Conteúdo |
|---|---|
| [request.md](request.md) | `ExpressRequest` |
| [response.md](response.md) | `ExpressResponse` |

## Onde cada objeto aparece

| Contexto | Requisição | Resposta |
|---|---|---|
| Handler de rota `fn ($req, $res)` | `ExpressRequest` | `ExpressResponse` (retorne o resultado de `json()`/`text()`/...) |
| Middleware callable `fn ($req, $res, $next)` | `ServerRequestInterface` (PSR-7) | `ExpressResponse` para curto-circuito; `$next()` devolve `ResponseInterface` |
| Middleware PSR-15 `process($request, $handler)` | `ServerRequestInterface` | `ResponseInterface` |

Atributos definidos por middlewares (`$request->withAttribute(...)`) chegam à rota via
`$req->psr7()->getAttribute(...)`. Parâmetros de rota ficam no atributo `route_params` e são lidos
com `$req->param()`/`$req->params()`.

## Testando

```php
use Nyholm\Psr7\ServerRequest;

$response = $app->handle(new ServerRequest('GET', '/users/1'));
$this->assertSame(200, $response->getStatusCode());
```

`handle()` aceita qualquer `ServerRequestInterface` e não altera estado global (os handlers de
erro do PHP são instalados apenas em `run()`).
