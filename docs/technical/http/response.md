# Resposta (`ExpressResponse`)

Handlers de rota recebem `PivotPHP\Http\ExpressResponse`. Setters são fluentes; os métodos de
envio devolvem o `ResponseInterface` final, que a rota deve **retornar**.

```php
$app->post('/users', fn ($req, $res) => $res
    ->status(201)
    ->header('Location', '/users/42')
    ->json(['id' => 42]));
```

## Setters (retornam `$this`)

| Método | Efeito |
|---|---|
| `status($codigo)` | status HTTP |
| `header($nome, $valor)` | define um header |
| `cookie($nome, $valor, $opcoes)` | `Set-Cookie`; opções `expires`, `maxAge`, `path`, `domain`, `secure`, `httpOnly`, `sameSite` (`SameSite=None` força `Secure`; valores inválidos lançam exceção) |

## Envio (retornam `ResponseInterface`)

| Método | Content-Type |
|---|---|
| `json($dados, $status = null)` | `application/json; charset=utf-8` |
| `text($texto, $status = null)` | `text/plain; charset=utf-8` |
| `html($html, $status = null)` | `text/html; charset=utf-8` |
| `send($dados, $status = null)` | JSON para array/objeto, texto para o resto |
| `error($status, $mensagem)` | JSON `{"error": "..."}` |
| `redirect($url, $status = 302)` | header `Location` |
| `noContent($status = 204)` | sem corpo |

`psr7()` devolve o `ResponseInterface` atual. Uma rota também pode retornar qualquer
`ResponseInterface` diretamente.
