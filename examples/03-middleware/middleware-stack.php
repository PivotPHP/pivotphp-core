<?php

/**
 * PivotPHP — Middleware order
 *
 * Global middlewares run in registration order before the route, and unwind in reverse order
 * after it (onion model). Route and group middlewares run after the global ones.
 *
 * Run:   php -S localhost:8000 examples/03-middleware/middleware-stack.php
 * Try:   curl -i http://localhost:8000/
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Routing\Router\Router;

$app = new Application();

/**
 * Appends $name to the "trace" attribute on the way in and to X-Trace on the way out.
 */
$step = static fn (string $name) => function ($req, $res, $next) use ($name) {
    $trace = $req->getAttribute('trace', []);
    $response = $next($req->withAttribute('trace', [...$trace, $name]));

    return $response->withAddedHeader('X-Trace', $name);
};

$app->use($step('global-1'));
$app->use($step('global-2'));

// Route middleware (4th argument of $app->get)
$app->get('/', fn ($req, $res) => $res->json([
    'order' => $req->psr7()->getAttribute('trace'),
]), [], $step('route'));

$app->run();
