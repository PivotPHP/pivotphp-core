<?php

/**
 * PivotPHP — Hello World
 *
 * Run:   php -S localhost:8000 examples/01-basics/hello-world.php
 * Try:   curl http://localhost:8000/
 *        curl http://localhost:8000/hello/PivotPHP
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

$app->get('/', fn ($req, $res) => $res->json([
    'message' => 'Hello, World!',
    'framework' => 'PivotPHP Core',
    'version' => Application::VERSION,
]));

// Route parameters are read with $req->param()
$app->get('/hello/:name', fn ($req, $res) => $res->json([
    'message' => 'Hello, ' . $req->param('name') . '!',
]));

$app->run();
