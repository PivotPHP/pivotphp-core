# Extensões e hooks

## Extensões

Uma extensão é um **service provider** (`PivotPHP\Core\Providers\ServiceProvider`): `register()`
cria bindings no container e `boot()` roda quando a aplicação inicializa.

```php
use PivotPHP\Core\Providers\ServiceProvider;

final class MailerProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('mailer', fn () => new Mailer($_ENV['SMTP_DSN'] ?? ''));
    }

    public function boot(): void
    {
        $this->app->get('/mailer/health', fn ($req, $res) => $res->json(['ok' => true]));
    }
}

$app->register(MailerProvider::class);                  // ou:
$app->registerExtension('mailer', MailerProvider::class); // registra com nome (ver extensions())
```

Providers também podem ser listados em `config/app.php` (`'providers' => [...]`).

## Hooks

O `HookManager` oferece ações (efeitos colaterais) e filtros (transformam um valor), com prioridade:

```php
$app->addAction('app.booting', fn () => error_log('booting'));
$app->addFilter('user.display_name', fn (string $name) => ucfirst($name));

$app->doAction('order.created', ['id' => 42]);           // dispara os seus hooks
$name = $app->applyFilter('user.display_name', 'ana');  // "Ana"
```

O core dispara **apenas** `app.registered` e `app.booting` (durante o boot). Qualquer outro hook só
roda quando a aplicação chama `doAction()`/`applyFilter()` — para observar requisições e respostas,
use os eventos PSR-14 (`RequestReceived`, `ResponseSent`) ou um middleware.

## Eventos

```php
use PivotPHP\Core\Events\RequestReceived;

$app->on(RequestReceived::class, function (RequestReceived $event): void {
    // ...
});
```

Hooks, extensões e listeners podem ser registrados logo após `new Application()`, antes do boot.
