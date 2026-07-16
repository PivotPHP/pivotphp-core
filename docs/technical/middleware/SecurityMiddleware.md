# SecurityMiddleware (não existe mais)

> ⚠️ **Página corrigida.** Esta página documentava anteriormente uma classe `SecurityMiddleware`
> com opções de configuração extensas (`development()`/`production()`/`strict()`,
> `customHeaders`, `contentSecurityPolicy`, etc.) que **não existe em `src/`**. Uma classe
> `SecurityMiddleware` chegou a existir no histórico do projeto (commit `d94de55`), mas foi
> substituída por `SecurityHeadersMiddleware`, uma implementação bem mais simples, antes da
> v2.0.0. O conteúdo anterior desta página não corresponde a nenhuma versão real do código —
> foi substituído pelo conteúdo abaixo, que reflete a classe real.

## SecurityHeadersMiddleware

**Localização real**: `src/Middleware/Security/SecurityHeadersMiddleware.php`
**Namespace**: `PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware`

```php
use PivotPHP\Core\Middleware\Security\SecurityHeadersMiddleware;

$app->use(new SecurityHeadersMiddleware());
```

A classe não aceita parâmetros de configuração — `__construct()` não tem argumentos, e os
métodos estáticos `create()`, `strict()`, `csrfOnly()`, `xssOnly()` existem apenas por
compatibilidade e todos retornam `new self()` (nenhum aplica configuração diferente).

**Headers adicionados** (fixos):
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`

Não há suporte a Content-Security-Policy, HSTS, ou headers customizáveis nesta classe.

## Proteções relacionadas (classes separadas)

O que a documentação antiga descrevia como "opções" de `SecurityMiddleware` (CSRF, XSS) são,
na implementação real, **middlewares independentes**:

- `PivotPHP\Core\Middleware\Security\CsrfMiddleware` — proteção CSRF
  (`src/Middleware/Security/CsrfMiddleware.php`)
- `PivotPHP\Core\Middleware\Security\XssMiddleware` — proteção XSS
  (`src/Middleware/Security/XssMiddleware.php`)

```php
use PivotPHP\Core\Middleware\Security\{SecurityHeadersMiddleware, CsrfMiddleware, XssMiddleware};

$app->use(new SecurityHeadersMiddleware());
$app->use(new CsrfMiddleware());
$app->use(new XssMiddleware());
```

Consulte o código-fonte de cada classe para as opções de configuração reais antes de usá-las
em produção.

## Ver também

- [Middlewares README](README.md) - visão geral dos middlewares do framework
