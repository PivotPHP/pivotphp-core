<?php

/**
 * PivotPHP — Route groups with group middleware
 *
 * Groups are provided by pivotphp/core-routing (Router::group); routes registered inside the
 * group get the prefix, and the group middlewares run before the handler.
 *
 * Run:   php -S localhost:8000 examples/02-routing/route-groups.php
 * Try:   curl http://localhost:8000/api/v1/status
 *        curl http://localhost:8000/api/v1/admin/stats
 *        curl http://localhost:8000/api/v1/admin/stats -H 'X-Admin-Key: secret'
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Routing\Router\Router;

$app = new Application();

// Group middleware: blocks the request when the header is missing.
// Demo only — use pivotphp/security (JwtAuthMiddleware) for real authentication.
$requireAdmin = function ($req, $res, $next) {
    if ($req->getHeaderLine('X-Admin-Key') !== 'secret') {
        return $res->error(401, 'Admin key required');
    }

    return $next();
};

Router::group('/api/v1', function (): void {
    Router::get('/status', fn ($req, $res) => $res->json(['status' => 'ok', 'version' => 'v1']));
});

Router::group('/api/v1/admin', function (): void {
    Router::get('/stats', fn ($req, $res) => $res->json(['users' => 42, 'orders' => 7]));
}, [$requireAdmin]);

$app->run();
