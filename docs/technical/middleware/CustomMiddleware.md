# Middleware customizado

Escreva middlewares como **PSR-15** (`Psr\Http\Server\MiddlewareInterface`) — funcionam no
PivotPHP e em qualquer pipeline PSR-15 — ou como callable `fn ($req, $res, $next)` para casos
simples. Regras gerais:

- **Uma responsabilidade por middleware.**
- **Falhe fechado**: em verificações de segurança, devolva a resposta de erro e não chame o handler.
- **Passe dados adiante como atributos** (`$request->withAttribute()`), nunca como propriedades.
- **Não guarde estado entre requisições** em propriedades estáticas: em PHP-FPM cada requisição
  começa do zero; em runtimes persistentes o estado vaza entre usuários.

## PSR-15 com resposta de erro

```php
use PivotPHP\Http\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequireJsonMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responses)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $unsafe = in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true);

        if ($unsafe && !str_contains($request->getHeaderLine('Content-Type'), 'application/json')) {
            $response = $this->responses->createResponse(415)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write('{"error":"Content-Type must be application/json"}');

            return $response;
        }

        return $handler->handle($request);
    }
}

$app->use(new RequireJsonMiddleware(new Psr17Factory()));
```

## Callable com atributo

```php
$app->use(function ($req, $res, $next) {
    $id = $req->getHeaderLine('X-Request-Id') ?: bin2hex(random_bytes(8));

    return $next($req->withAttribute('request_id', $id))->withHeader('X-Request-Id', $id);
});

$app->get('/', fn ($req, $res) => $res->json(['request_id' => $req->psr7()->getAttribute('request_id')]));
```

## Autenticação própria

Para JWT use `pivotphp/security`. Para outro esquema (API key, Basic), siga o modelo de
[`examples/03-middleware/auth-middleware.php`](../../../examples/03-middleware/auth-middleware.php):
compare segredos com `hash_equals()`, responda `401` sem chamar o handler e coloque a identidade em
um atributo.

## Testes

```php
public function testRejectsNonJsonBody(): void
{
    $app = new Application(__DIR__ . '/..');
    $app->use(new RequireJsonMiddleware(new Psr17Factory()));
    $app->post('/items', fn ($req, $res) => $res->status(201)->json([]));

    $response = $app->handle(new ServerRequest('POST', '/items', ['Content-Type' => 'text/plain']));

    $this->assertSame(415, $response->getStatusCode());
}
```
