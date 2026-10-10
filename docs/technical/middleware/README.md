# 🛡️ Middlewares do PivotPHP

O PivotPHP executa uma pipeline **PSR-15**. Middlewares globais são registrados com `$app->use()`
(alias `middleware()`) e rodam, em ordem de registro, **antes do roteamento** — inclusive para
404 e OPTIONS. Middlewares de grupo/rota (via `Router`) rodam depois dos globais.

## Formas aceitas

### PSR-15

Qualquer `Psr\Http\Server\MiddlewareInterface`:

```php
final class TimingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = hrtime(true);
        $response = $handler->handle($request);

        return $response->withHeader('X-Response-Time', sprintf('%.2fms', (hrtime(true) - $start) / 1e6));
    }
}

$app->use(new TimingMiddleware());
```

### Callable

`fn (ServerRequestInterface $req, ExpressResponse $res, callable $next)`:

| Operação | Como |
|---|---|
| Continuar | `return $next();` |
| Passar requisição alterada | `return $next($req->withAttribute('tenant', 'acme'));` |
| Alterar a resposta | `return $next()->withHeader('X-Foo', 'bar');` |
| Responder sem chamar a rota | `return $res->status(403)->json(['error' => 'blocked']);` |

`$next()` executa o restante da pipeline **uma vez** e devolve o `ResponseInterface`. Alterações
feitas em `$res` não são mescladas na resposta de `$next()` — altere a resposta devolvida.

### Grupos e rotas

```php
use PivotPHP\Routing\Router\Router;

Router::group('/admin', function (): void {
    Router::get('/stats', $handler);
}, [$requireAdmin]);

Router::get('/reports', $handler, [], $auditMiddleware); // 4º argumento: só esta rota
```

## 🛡️ Segurança

Desde a **v4.0.0** o core não tem middlewares de segurança próprios: CORS, headers de segurança,
CSRF, autenticação JWT, rate limiting e resolução do IP do cliente atrás de proxies vêm do pacote
[`pivotphp/security`](https://github.com/PivotPHP/pivotphp-security), dependência do core.

| Necessidade | Classe |
|---|---|
| Headers de segurança (HSTS, CSP, `nosniff`, `X-Frame-Options`, Referrer-Policy) | `PivotPHP\Security\Headers\SecurityHeadersMiddleware` |
| CORS | `PivotPHP\Security\Cors\CorsMiddleware` |
| CSRF (autenticação por cookie) | `PivotPHP\Security\Csrf\CsrfMiddleware` |
| JWT (verificar / emitir) | `PivotPHP\Security\Jwt\JwtAuthMiddleware` / `JwtIssuer` |
| Rate limiting | `PivotPHP\Security\RateLimit\RateLimitMiddleware` |
| IP real atrás de proxy | `PivotPHP\Security\Proxy\TrustedProxyMiddleware` |

Ordem recomendada:

```php
$app->use(new TrustedProxyMiddleware($proxyConfig));          // IP real do cliente primeiro
$app->use(new SecurityHeadersMiddleware());                   // headers também nas respostas de erro
$app->use(new CorsMiddleware($factory, $corsConfig));         // antes da autenticação (preflight)
$app->use(new RateLimitMiddleware($factory, $limiter));       // antes de processamento pesado
$app->use(new JwtAuthMiddleware($factory, $jwtConfig));       // autenticação
```

Equivalência com as classes removidas: [SecurityMiddleware.md](SecurityMiddleware.md).
O antigo `RateLimiter` do core foi removido por não limitar em PHP-FPM:
[RateLimitMiddleware.md](RateLimitMiddleware.md).

## 📖 Middlewares do core

| Classe | Uso |
|---|---|
| `PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware` | OpenAPI 3 em `/docs` e Swagger UI em `/swagger` |

Erros são tratados pela própria `Application` (respostas JSON com `error_id`; detalhes só com
`app.debug`).

## ✅ Validação

Não há middleware de validação; use `PivotPHP\Core\Validation\Validator` no handler ou no seu
middleware — veja [ValidationMiddleware.md](ValidationMiddleware.md).

## 🧪 Testando

```php
$app->use(new TimingMiddleware());
$app->get('/x', fn ($req, $res) => $res->json(['ok' => true]));

$response = $app->handle(new \Nyholm\Psr7\ServerRequest('GET', '/x'));
$this->assertTrue($response->hasHeader('X-Response-Time'));
```

Mais exemplos: [CustomMiddleware.md](CustomMiddleware.md) e `examples/03-middleware/`.
