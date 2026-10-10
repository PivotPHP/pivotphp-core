# Rate limiting (removido do core)

> ⚠️ **Removido na v4.0.0 por falha de segurança em PHP-FPM.** O `RateLimiter` do core guardava os
> contadores na memória da própria instância (a opção `storage` era ignorada). Como o PHP-FPM recria o
> estado a cada requisição, o contador zerava e **o limite nunca era atingido**. Use o
> `RateLimitMiddleware` do pacote [`pivotphp/security`](https://github.com/PivotPHP/pivotphp-security),
> que usa armazenamento compartilhado e lock ([SPEC-093](https://github.com/PivotPHP/pivotphp-specs/blob/main/SPECS/SPEC-093-core-ratelimiter-fpm-validation.md)).

## Histórico

- `Middleware\Performance\RateLimitMiddleware` e `Middleware\LoadShedder`: removidos na v3.0.0
  (ciclo de depreciação 2.1.0 → 3.0.0; o primeiro usava `$_SESSION`).
- `Middleware\RateLimiter` e o alias `'rate-limiter'`: removidos na v4.0.0 pela falha descrita acima.
  Junto saíram as estratégias, `whitelist`, `blacklist`, `reject_response` e `key_generator`.

## Migração

```php
use PivotPHP\Security\Proxy\TrustedProxyConfig;
use PivotPHP\Security\Proxy\TrustedProxyMiddleware;
use PivotPHP\Security\RateLimit\RateLimitMiddleware;
use Symfony\Component\RateLimiter\RateLimiterFactory;

$app->use(new TrustedProxyMiddleware(new TrustedProxyConfig(['10.0.0.0/8'])));
$app->use(new RateLimitMiddleware($responseFactory, new RateLimiterFactory(
    ['id' => 'api', 'policy' => 'sliding_window', 'limit' => 1000, 'interval' => '1 hour'],
    $storage,      // armazenamento compartilhado entre workers (ex.: CacheStorage sobre Redis)
    $lockFactory,  // symfony/lock — contagem correta sob concorrência
)));
```

Veja o README do [`pivotphp/security`](https://github.com/PivotPHP/pivotphp-security#rate-limiting)
para todas as opções.
