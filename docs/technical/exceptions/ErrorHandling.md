# Tratamento de erros

Toda exceção que escapa de um middleware ou de uma rota é capturada por `Application::handle()` e
convertida em resposta JSON. O status vem da exceção:

| Exceção | Status |
|---|---|
| `PivotPHP\Core\Exceptions\HttpException` (e subclasses, como `ContextualException`) | `getStatusCode()` |
| `PivotPHP\Http\Exception\HttpExceptionInterface` (ex.: JSON malformado no corpo) | `getStatusCode()` (`400`) |
| Qualquer outra | `500` |

Headers da `HttpException` (`new HttpException(429, '...', ['Retry-After' => '30'])`) são aplicados
à resposta.

## Produção (`app.debug = false`)

```json
{"error": true, "message": "Too Many Requests", "error_id": "err_6aca78cac92281.91497996"}
```

A mensagem é sempre o texto padrão do status — mensagens de exceção não chegam ao cliente. O
`error_id` também é registrado no log junto com a exceção completa, para correlação.

## Desenvolvimento (`app.debug = true`)

A resposta inclui `message`, `file`, `line` e `trace`. Nunca habilite em produção.

```php
$app->getConfig()->set('app.debug', true);    // ou APP_DEBUG via config/app.php
```

## Erros de PHP

`run()` instala handlers que convertem warnings/notices em `ErrorException` (tratadas como acima)
e os remove ao terminar. `handle()` não altera os handlers globais.

## Respostas de erro próprias

Para devolver um erro com corpo específico, responda diretamente em vez de lançar:

```php
$app->post('/users', function ($req, $res) {
    if (!is_string($req->input('email'))) {
        return $res->status(422)->json(['errors' => ['email' => 'required']]);
    }
    // ...
});
```

Ou capture exceções do domínio num middleware e converta-as:

```php
$app->use(function ($req, $res, $next) {
    try {
        return $next();
    } catch (DomainValidationException $e) {
        return $res->status(422)->json(['errors' => $e->errors()]);
    }
});
```
