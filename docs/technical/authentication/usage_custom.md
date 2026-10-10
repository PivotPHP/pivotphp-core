# Autenticação customizada (removido do core na 4.0.0)

> Os middlewares de segurança nativos do core (`CorsMiddleware`, `AuthMiddleware`, `CsrfMiddleware`,
> `SecurityHeadersMiddleware`, `XssMiddleware`), o `JWTHelper` e os helpers `Utils::csrfToken()`/
> `Utils::checkCsrf()` foram **removidos na v4.0.0**. A segurança agora vem do pacote
> [`pivotphp/security`](https://github.com/PivotPHP/pivotphp-security), instalado como dependência do core.

## Equivalência

| Antes (core ≤ 3.x) | Agora (`pivotphp/security`) |
|---|---|
| `Middleware\Http\CorsMiddleware` | `PivotPHP\Security\Cors\CorsMiddleware` + `CorsConfig` |
| `Middleware\Security\SecurityHeadersMiddleware` | `PivotPHP\Security\Headers\SecurityHeadersMiddleware` + `SecurityHeadersConfig` |
| `Middleware\Security\CsrfMiddleware`, `Utils::csrfToken()`/`checkCsrf()` | `PivotPHP\Security\Csrf\CsrfMiddleware` (tokens do `yiisoft/csrf`) |
| `Middleware\Security\AuthMiddleware` (JWT) | `PivotPHP\Security\Jwt\JwtAuthMiddleware` + `JwtConfig` |
| `Authentication\JWTHelper::encode()` | `PivotPHP\Security\Jwt\JwtIssuer::issue()` |
| `Middleware\RateLimiter` | `PivotPHP\Security\RateLimit\RateLimitMiddleware` (+ `Proxy\TrustedProxyMiddleware`) |
| `Middleware\Security\XssMiddleware` | sem substituto — escape a saída e use CSP (`SecurityHeadersMiddleware`) |

Autenticação Basic, Bearer opaco ou API key: escreva um middleware PSR-15 próprio
(veja [CustomMiddleware.md](../middleware/CustomMiddleware.md)).

## Exemplo

```php
use PivotPHP\Http\Factory\Psr17Factory;
use PivotPHP\Security\Cors\{CorsConfig, CorsMiddleware};
use PivotPHP\Security\Headers\SecurityHeadersMiddleware;
use PivotPHP\Security\Jwt\{JwtAuthMiddleware, JwtConfig};

$factory = new Psr17Factory();

$app->use(new SecurityHeadersMiddleware());
$app->use(new CorsMiddleware($factory, new CorsConfig(['https://app.example.com'], allowCredentials: true)));
$app->use(new JwtAuthMiddleware($factory, new JwtConfig($_ENV['JWT_SECRET'], publicPaths: ['/health'])));
```

Opções, ordem recomendada e avisos de segurança: [README do pivotphp/security](https://github.com/PivotPHP/pivotphp-security#readme).
Mudanças de comportamento em relação aos middlewares antigos: [MIGRATION_GUIDE.md](../../MIGRATION_GUIDE.md).
