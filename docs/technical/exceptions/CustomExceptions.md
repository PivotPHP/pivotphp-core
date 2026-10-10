# Exceções

## `HttpException`

`PivotPHP\Core\Exceptions\HttpException(int $statusCode = 500, string $message = '', array $headers = [])`

```php
use PivotPHP\Core\Exceptions\HttpException;

throw new HttpException(404, 'User not found');
throw new HttpException(429, 'Rate limited', ['Retry-After' => '60']);
```

O status e os headers vão para a resposta; fora do modo debug a mensagem é substituída pelo texto
padrão do status ([ErrorHandling.md](ErrorHandling.md)).

## `ContextualException`

`PivotPHP\Core\Exceptions\Enhanced\ContextualException` estende `HttpException` com contexto e
sugestões, usada pelo framework em erros de configuração de rotas e handlers:

```php
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;

try {
    $app->get('/x', [Controller::class, 'privateMethod']);
} catch (ContextualException $e) {
    $e->getCategory();      // ex.: 'ROUTING'
    $e->getContext();       // dados do erro
    $e->getSuggestions();   // como corrigir
}
```

Fábricas: `routeNotFound()`, `handlerError()`, `parameterError()`, `middlewareError()`.

## Exceções próprias

Estenda `HttpException` para erros que devem virar um status específico:

```php
final class PaymentRequiredException extends HttpException
{
    public function __construct(string $message = 'Payment required')
    {
        parent::__construct(402, $message);
    }
}
```

Ou implemente `PivotPHP\Http\Exception\HttpExceptionInterface` (`getStatusCode(): int`) numa
exceção que precise estender outra classe.
