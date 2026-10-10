# Examples

Every example runs with the PHP built-in server (`php -S localhost:8000 examples/<path>`) and is
exercised over HTTP by `tests/Integration/ExamplesTest.php`.

| Example | Shows |
|---|---|
| [01-basics/hello-world.php](../../examples/01-basics/hello-world.php) | Minimal app, route parameter |
| [01-basics/basic-routes.php](../../examples/01-basics/basic-routes.php) | In-memory CRUD, status codes |
| [01-basics/request-response.php](../../examples/01-basics/request-response.php) | `ExpressRequest` / `ExpressResponse` API |
| [01-basics/json-api.php](../../examples/01-basics/json-api.php) | Pagination, search, validation, path-scoped middleware |
| [02-routing/regex-routing.php](../../examples/02-routing/regex-routing.php) | Regex constraints |
| [02-routing/route-constraints.php](../../examples/02-routing/route-constraints.php) | Constraint shortcuts, route middleware |
| [02-routing/route-groups.php](../../examples/02-routing/route-groups.php) | `Router::group()` with group middleware |
| [02-routing/route-parameters.php](../../examples/02-routing/route-parameters.php) | Parameters and query string |
| [02-routing/static-files.php](../../examples/02-routing/static-files.php) | `$app->staticFiles()` |
| [03-middleware/custom-middleware.php](../../examples/03-middleware/custom-middleware.php) | PSR-15 and callable middleware |
| [03-middleware/middleware-stack.php](../../examples/03-middleware/middleware-stack.php) | Execution order |
| [03-middleware/auth-middleware.php](../../examples/03-middleware/auth-middleware.php) | Custom API-key authentication |
| [03-middleware/cors-middleware.php](../../examples/03-middleware/cors-middleware.php) | CORS with pivotphp/security |
| [04-api/rest-api.php](../../examples/04-api/rest-api.php) | REST controller, 201/204/404/405/422 |
| [06-security/jwt-auth.php](../../examples/06-security/jwt-auth.php) | JWT login and protected route |
| [07-advanced/array-callables.php](../../examples/07-advanced/array-callables.php) | Array callable controllers |
| [api_documentation_example.php](../../examples/api_documentation_example.php) | OpenAPI / Swagger UI |
