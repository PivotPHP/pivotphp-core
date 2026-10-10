<?php

/**
 * PivotPHP — Request and response API
 *
 * Run:   php -S localhost:8000 examples/01-basics/request-response.php
 * Try:   curl 'http://localhost:8000/inspect/42?page=2' -H 'X-Trace: abc'
 *        curl http://localhost:8000/text
 *        curl http://localhost:8000/html
 *        curl -i http://localhost:8000/old-path
 *        curl -i http://localhost:8000/cookie
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// Request: ExpressRequest facade over the PSR-7 request ($req->psr7())
$app->get('/inspect/:id', fn ($req, $res) => $res->json([
    'method' => $req->method(),
    'path' => $req->path(),
    'param_id' => $req->param('id'),
    'query_page' => $req->query('page', '1'),
    'header_trace' => $req->header('X-Trace'),
    'ip' => $req->ip(),
    'user_agent' => $req->userAgent(),
    'accepts_json' => $req->accepts('application/json'),
]));

// Response: setters are fluent; json/text/html/redirect/noContent finish the response
$app->get('/text', fn ($req, $res) => $res->text('plain text'));
$app->get('/html', fn ($req, $res) => $res->html('<h1>PivotPHP</h1>'));
$app->get('/old-path', fn ($req, $res) => $res->redirect('/text', 301));
$app->get('/cookie', fn ($req, $res) => $res
    ->cookie('theme', 'dark', ['path' => '/', 'httpOnly' => true, 'sameSite' => 'Lax', 'maxAge' => 3600])
    ->header('X-Example', 'cookie')
    ->json(['cookie' => 'set']));

$app->run();
