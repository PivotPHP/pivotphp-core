# Requisição (`ExpressRequest`)

Handlers de rota recebem `PivotPHP\Http\ExpressRequest`, uma fachada **somente leitura** sobre o
`ServerRequestInterface` (PSR-7). O objeto PSR-7 fica disponível em `$req->psr7()`.

```php
$app->get('/users/:id', function ($req, $res) {
    return $res->json([
        'id' => $req->param('id'),
        'page' => $req->query('page', '1'),
    ]);
});
```

## Métodos

| Método | Retorno |
|---|---|
| `method()` | método HTTP |
| `path()` | caminho da URI |
| `param($nome, $padrao = null)` / `params()` | parâmetros de rota |
| `query($nome, $padrao = null)` | query string |
| `input($nome, $padrao = null)` | corpo parseado (JSON ou formulário), depois query string |
| `json()` | corpo decodificado como array (`MalformedJsonException` se inválido) |
| `header($nome, $padrao = null)` | linha do header |
| `cookie($nome, $padrao = null)` | cookie |
| `file($nome)` | `UploadedFileInterface` ou `null` |
| `ip()` | `REMOTE_ADDR` (atrás de proxy, use o atributo `client_ip` do `TrustedProxyMiddleware`) |
| `userAgent()`, `isAjax()`, `isJson()`, `isSecure()`, `accepts($tipo)` | utilitários |
| `psr7()` | `ServerRequestInterface` subjacente |

## Atributos

```php
$tenant = $req->psr7()->getAttribute('tenant');   // definido por um middleware
$user = $req->psr7()->getAttribute('user');       // claims do JwtAuthMiddleware
```

## Corpo

O core parseia o corpo antes da pipeline:

```php
$app->post('/users', function ($req, $res) {
    $name = $req->input('name');                 // campo do JSON ou do formulário
    $all = $req->psr7()->getParsedBody();        // array completo
    // ...
});
```

JSON inválido responde `400` automaticamente.
