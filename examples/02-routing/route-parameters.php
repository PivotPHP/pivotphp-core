<?php

/**
 * PivotPHP — Route parameters and query string
 *
 * Run:   php -S localhost:8000 examples/02-routing/route-parameters.php
 * Try:   curl http://localhost:8000/users/42
 *        curl http://localhost:8000/api/users/123/posts/456
 *        curl http://localhost:8000/articles/hello-world
 *        curl 'http://localhost:8000/search?q=php&page=2'
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// :id<\d+> only matches digits; anything else is 404
$app->get('/users/:id<\d+>', fn ($req, $res) => $res->json(['user_id' => (int) $req->param('id')]));

// Several parameters; $req->params() returns all of them
$app->get('/api/users/:userId<\d+>/posts/:postId<\d+>', fn ($req, $res) => $res->json([
    'params' => $req->params(),
]));

// Named constraint shortcuts: <int>, <slug>, <alpha>, <alnum>, <uuid>, <date>...
$app->get('/articles/:slug<slug>', fn ($req, $res) => $res->json(['slug' => $req->param('slug')]));

// Query string
$app->get('/search', fn ($req, $res) => $res->json([
    'q' => $req->query('q', ''),
    'page' => (int) $req->query('page', 1),
]));

$app->run();
