<?php

/**
 * PivotPHP — Static files
 *
 * $app->staticFiles() registers every file of a directory as a GET route
 * (StaticFileManager from pivotphp/core-routing): no directory listing, no path traversal.
 *
 * Run:   php -S localhost:8000 examples/02-routing/static-files.php
 * Try:   curl http://localhost:8000/assets/css/app.css
 *        curl http://localhost:8000/assets/data.json
 *        curl http://localhost:8000/
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

$app->staticFiles('/assets', __DIR__ . '/public');

$app->get('/', fn ($req, $res) => $res->json([
    'static_files' => ['/assets/css/app.css', '/assets/data.json'],
]));

$app->run();
