# PivotPHP Core — API Reference (4.1)

## Quick Start

```php
use PivotPHP\Core\Core\Application;

$app = new Application(__DIR__);           // or Application::create(__DIR__)

$app->get('/hello/:name', fn ($req, $res) => $res->json(['hello' => $req->param('name')]));

$app->run();                               // reads the SAPI request and emits the response
```

`$app->handle(ServerRequestInterface $request): ResponseInterface` runs the same pipeline without
emitting — use it in tests and workers.

## Routes

```php
$app->get($path, $handler);
$app->post($path, $handler);
$app->put($path, $handler);
$app->patch($path, $handler);
$app->delete($path, $handler);
```

- **Parameters**: `/users/:id`, `/users/{id}`.
- **Constraints**: `/users/:id<\d+>`; shortcuts `<int>`, `<slug>`, `<alpha>`, `<alnum>`, `<uuid>`, `<date>`,
  `<year>`, `<month>`, `<day>`.
- **Handlers**: closure, named function, or array callable `[Controller::class, 'method']` /
  `[$instance, 'method']` (public methods; classes are resolved through the container).
  `'Controller@method'` is not supported.
- **Groups and route middleware** come from `pivotphp/core-routing`:
  `Router::group($prefix, $callback, $middlewares)` and the 4th argument of `Router::get(...)`.
- **HTTP semantics**: unknown path → `404`; known path with another method → `405` + `Allow`;
  `HEAD` falls back to `GET`; `OPTIONS` answers `204` + `Allow` when no route handles it.
- **Static files**: `$app->staticFiles('/assets', __DIR__ . '/public')` registers each file as a route.

Syntax guide: [technical/routing/SYNTAX_GUIDE.md](technical/routing/SYNTAX_GUIDE.md).

## Request and response

Route handlers receive `PivotPHP\Http\ExpressRequest` and `PivotPHP\Http\ExpressResponse`
(package `pivotphp/http`) and must return the response:

```php
$app->post('/users', function ($req, $res) {
    $name = $req->input('name');               // JSON or form body, then query string

    return $res->status(201)->header('Location', '/users/1')->json(['name' => $name]);
});
```

Full method lists: [request.md](technical/http/request.md), [response.md](technical/http/response.md).

## Middleware

`$app->use($middleware)` (alias `middleware()`) registers a **global** middleware; it runs, in
registration order, around routing (also for 404/OPTIONS). Accepted forms:

```php
// PSR-15
$app->use(new MyPsr15Middleware());

// Callable: $req is the PSR-7 ServerRequestInterface, $res an ExpressResponse for short-circuits,
// $next(?ServerRequestInterface $request = null): ResponseInterface runs the rest of the pipeline once.
$app->use(function ($req, $res, $next) {
    if ($req->getHeaderLine('Authorization') === '') {
        return $res->error(401, 'Unauthorized');              // short-circuit
    }

    $response = $next($req->withAttribute('start', microtime(true)));

    return $response->withHeader('X-Handled-By', 'PivotPHP'); // change the downstream response
});
```

Changes made to `$res` are **not** merged into the response returned by `$next()`; modify the
returned response instead. Security middlewares (CORS, headers, CSRF, JWT, rate limit, trusted
proxies) come from [`pivotphp/security`](https://github.com/PivotPHP/pivotphp-security).

## Errors

- Uncaught exceptions become JSON error responses with an `error_id`; with `app.debug` enabled the
  response includes exception details.
- `PivotPHP\Core\Exceptions\HttpException($status, $message)` and any
  `PivotPHP\Http\Exception\HttpExceptionInterface` set the response status (malformed JSON → `400`).
- PHP error/exception handlers are installed only by `run()` and restored afterwards.

## Configuration

```php
$app = new Application(__DIR__);
$app->getConfig()->setConfigPath(__DIR__ . '/config')->loadAll();

$debug = $app->getConfig()->get('app.debug', false);
$app->getConfig()->set('custom.setting', 'value');
```

## Container and providers

```php
$app->bind('mailer', fn ($container) => new Mailer());
$app->singleton('cache', fn ($container) => new Cache());

$mailer = $app->make('mailer');

$app->register(MyServiceProvider::class);   // extends PivotPHP\Core\Providers\ServiceProvider
```

Controllers used as array callables are built by the container, so constructor dependencies are
injected. Note: `$app->get()` registers a GET route — resolve services with `make()`.

## Events

PSR-14: `$app->on(RequestReceived::class, $listener)`; lifecycle events `ApplicationStarted`,
`RequestReceived` and `ResponseSent` (namespace `PivotPHP\Core\Events`).

## Validation

`PivotPHP\Core\Validation\Validator` validates arrays with string rules
(`'required|email|max:255'`):

```php
use PivotPHP\Core\Validation\Validator;

$validator = Validator::make($req->json(), [
    'name'  => 'required|string|max:255',
    'email' => 'required|email',
    'age'   => 'nullable|integer|min:18',
]);

if (!$validator->validate($req->json())) {
    return $res->status(422)->json(['errors' => $validator->getErrors()]);
}
```

Rules: `required`, `nullable`, `sometimes`, `string`, `numeric`, `integer`, `email`, `min:n`,
`max:n`, `in:a,b,c`, `regex:/.../`. Since 4.1.0, fields without `required` do not fail when absent,
`nullable` accepts `null`, and `min`/`max` are type-aware (numeric value, `mb_strlen` for strings,
item count for arrays). Unknown rules throw `\InvalidArgumentException`.

## Version

```php
Application::VERSION
```

## Examples

Runnable examples live in [`examples/`](../examples/) and are verified by
`tests/Integration/ExamplesTest.php`.

## Benchmarks

Cross-framework benchmarks live in
[pivotphp-benchmarks](https://github.com/PivotPHP/pivotphp-benchmarks).
