# Modo debug

O modo debug é a chave de configuração `app.debug` (padrão `false`). Ela não é lida
automaticamente do ambiente: defina-a no `config/app.php` ou em código.

```php
// config/app.php
return [
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
];
```

```php
$app = new Application(__DIR__);              // carrega config/ do basePath no boot
$app->getConfig()->set('app.debug', true);    // ou em código
```

`PivotPHP\Core\Core\Environment::isDebug()` / `isDevelopment()` leem `APP_DEBUG`/`APP_ENV` e podem
alimentar essa configuração.

## O que muda

| | `app.debug = false` | `app.debug = true` |
|---|---|---|
| Corpo de erro | `error`, mensagem padrão do status, `error_id` | `error`, `message`, `file`, `line`, `trace` |
| `display_errors` | `0` | `1` |
| Log da exceção | sim (com `error_id`) | sim |

> ⚠️ Nunca habilite em produção: a resposta expõe mensagens, caminhos e stack traces.

## Logs

Exceções são registradas pelo logger PSR-3 do container (`PivotPHP\Core\Logging\PsrLogger`). O
arquivo padrão é `pivotphp.log` no diretório temporário do sistema, ou o caminho em `LOG_PATH`.

## Inspecionando rotas

```php
use PivotPHP\Routing\Router\Router;

echo Router::toString();          // "GET /users => Callable" ...
$routes = Router::getRoutes();    // array com method, path, handler...
```
