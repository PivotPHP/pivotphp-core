# Validação de Dados

> **Nota de revisão:** este documento descrevia uma classe `ValidationMiddleware` que nunca
> existiu em `src/`. O framework não tem um middleware PSR-15 dedicado a validação — o que
> existe é `PivotPHP\Core\Validation\Validator` (`src/Validation/Validator.php`), uma classe
> de validação standalone (não é `MiddlewareInterface`). Este documento foi reescrito para
> refletir a API real.

## Uso

```php
use PivotPHP\Core\Validation\Validator;

$app->post('/users', function ($req, $res) {
    $validator = new Validator([
        'email' => 'required|email',
        'password' => 'required|min:8',
    ]);

    if (!$validator->validate((array) $req->body)) {
        return $res->status(422)->json(['errors' => $validator->getErrors()]);
    }

    // ... segue o processamento
    return $res->status(201)->json(['message' => 'created']);
});
```

Também é possível usar o método estático de conveniência:

```php
$validator = Validator::make($data, [
    'email' => 'required|email',
    'password' => 'required|min:8',
]);

if (!$validator->validate($data)) {
    // ...
}
```

## API

- `__construct(array $rules = [], array $messages = [])`
- `setRules(array $rules): self`
- `setMessages(array $messages): self`
- `validate(array $data): bool`
- `getErrors(): array` — erros por campo, cada um com uma lista de mensagens
- `getFirstError(): ?string`
- `static make(array $data, array $rules, array $messages = []): self`

## Regras suportadas

`required`, `string`, `numeric`, `integer`, `email`, `min:N`, `max:N`, `in:a,b,c`, `regex:/.../`.
Regras podem ser combinadas com `|` em uma string (ex.: `'required|min:8'`) ou passadas como
array.

## Integração como middleware

Como `Validator` não implementa `MiddlewareInterface`, para reutilizar a mesma validação em
várias rotas, envolva-a em seu próprio middleware PSR-15:

```php
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use PivotPHP\Core\Validation\Validator;
use PivotPHP\Core\Exceptions\HttpException;

class UserValidationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $validator = new Validator([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $data = (array) $request->getParsedBody();
        if (!$validator->validate($data)) {
            // HttpException::__construct(int $statusCode, string $message, array $headers,
            // ?Throwable $previous) has no dedicated slot for structured error data — build
            // your own JSON response instead of throwing if you need $validator->getErrors()
            // in the payload.
            throw new HttpException(422, implode(' ', array_map(
                fn ($msgs) => implode(' ', $msgs),
                $validator->getErrors()
            )));
        }

        return $handler->handle($request);
    }
}
```

## Boas Práticas

- Valide sempre os dados de entrada do usuário.
- Retorne mensagens de erro específicas por campo (`getErrors()`), não apenas um booleano.
