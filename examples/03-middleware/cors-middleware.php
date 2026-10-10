<?php

/**
 * PivotPHP — CORS with pivotphp/security
 *
 * Run:   php -S localhost:8000 examples/03-middleware/cors-middleware.php
 * Try:   curl -i http://localhost:8000/api/data -H 'Origin: https://app.example.com'
 *        curl -i -X OPTIONS http://localhost:8000/api/data \
 *             -H 'Origin: https://app.example.com' -H 'Access-Control-Request-Method: PUT'
 *        curl -i http://localhost:8000/api/data -H 'Origin: https://evil.example'
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Http\Factory\Psr17Factory;
use PivotPHP\Security\Cors\CorsConfig;
use PivotPHP\Security\Cors\CorsMiddleware;

$app = new Application();

// Preflight (OPTIONS) is answered by the middleware with 204; disallowed origins receive no
// Access-Control-Allow-Origin. '*' together with credentials is rejected when the config is built.
$app->use(new CorsMiddleware(new Psr17Factory(), new CorsConfig(
    allowedOrigins: ['https://app.example.com'],
    allowedMethods: ['GET', 'POST', 'PUT', 'DELETE'],
    allowedHeaders: ['Content-Type', 'Authorization'],
    exposedHeaders: ['X-Total-Count'],
    allowCredentials: true,
    maxAge: 600,
)));

$app->get('/api/data', fn ($req, $res) => $res->header('X-Total-Count', '2')->json(['items' => ['a', 'b']]));

$app->run();
