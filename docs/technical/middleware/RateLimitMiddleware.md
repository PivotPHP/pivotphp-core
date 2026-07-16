# RateLimitMiddleware

> ⚠️ **Depreciado desde v2.1.0.** `RateLimitMiddleware` usa `$_SESSION`, o que viola o
> princípio stateless do HTTP e é problemático em runtimes assíncronos/concorrentes (Swoole,
> ReactPHP, FrankenPHP). Construí-lo dispara um `E_USER_DEPRECATED`. Use
> [`PivotPHP\Core\Middleware\RateLimiter`](#substituto-recomendado-ratelimiter) no lugar.
> Remoção planejada para v3.0.0 (ver `CHANGELOG.md`, seção `[2.1.0]`).

Middleware (PSR-15) para controle de taxa de requisições (Rate Limiting).

**Localização real**: `src/Middleware/Performance/RateLimitMiddleware.php`
(namespace `PivotPHP\Core\Middleware\Performance`)

## Uso

```php
use PivotPHP\Core\Middleware\Performance\RateLimitMiddleware;

$app->use(new RateLimitMiddleware([
    'windowMs' => 900000,  // janela em milissegundos (padrão: 15 minutos)
    'max' => 100,          // número máximo de requisições na janela
]));
```

## Configurações Disponíveis

- `windowMs` (int): janela de tempo **em milissegundos** (padrão: `900000` = 15 min)
- `max` (int): número máximo de requisições por janela (padrão: `100`)
- `message` (string): mensagem retornada ao exceder o limite
- `statusCode` (int): código HTTP retornado (padrão: `429`)
- `keyGenerator` (callable|null): função para gerar a chave de rate limit (padrão: por IP)

## Substituto recomendado: `RateLimiter`

```php
use PivotPHP\Core\Middleware\RateLimiter;

$app->use(new RateLimiter([
    'strategy' => RateLimiter::STRATEGY_SLIDING_WINDOW, // fixed_window|sliding_window|token_bucket|leaky_bucket
    'max_requests' => 100,
    'window_size' => 60,      // segundos
    'burst_size' => 10,
    'storage' => 'memory',    // memory, redis, apcu
    'whitelist' => [],
    'blacklist' => [],
]));
```

`RateLimiter` não usa `$_SESSION` e suporta múltiplas estratégias (sliding window, token
bucket, leaky bucket) e armazenamento pluggable. Ver `src/Middleware/RateLimiter.php`.

## Boas Práticas

- Prefira `RateLimiter` em vez de `RateLimitMiddleware` em código novo.
- Ajuste os limites conforme o perfil da aplicação.
