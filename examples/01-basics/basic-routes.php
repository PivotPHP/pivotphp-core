<?php

/**
 * PivotPHP — Basic routes (in-memory CRUD)
 *
 * Run:   php -S localhost:8000 examples/01-basics/basic-routes.php
 * Try:   curl http://localhost:8000/users
 *        curl http://localhost:8000/users/1
 *        curl -X POST http://localhost:8000/users -H 'Content-Type: application/json' -d '{"name":"Ana"}'
 *        curl -X PUT http://localhost:8000/users/1 -H 'Content-Type: application/json' -d '{"name":"Bia"}'
 *        curl -X DELETE http://localhost:8000/users/1
 *
 * Data lives in memory: every request starts again from the initial list (PHP is per-request).
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

$users = [
    1 => ['id' => 1, 'name' => 'Alice'],
    2 => ['id' => 2, 'name' => 'Bob'],
];

$app->get('/users', fn ($req, $res) => $res->json(array_values($users)));

$app->get('/users/:id<\d+>', function ($req, $res) use ($users) {
    $id = (int) $req->param('id');

    return isset($users[$id])
        ? $res->json($users[$id])
        : $res->error(404, 'User not found');
});

$app->post('/users', function ($req, $res) {
    // JSON and form bodies are parsed by the core; read fields with $req->input()
    $name = $req->input('name');

    if (!is_string($name) || trim($name) === '') {
        return $res->error(422, 'The "name" field is required');
    }

    return $res->status(201)->json(['id' => 3, 'name' => trim($name)]);
});

$app->put('/users/:id<\d+>', function ($req, $res) use ($users) {
    $id = (int) $req->param('id');
    if (!isset($users[$id])) {
        return $res->error(404, 'User not found');
    }

    $name = $req->input('name', $users[$id]['name']);

    return $res->json(['id' => $id, 'name' => is_string($name) ? $name : $users[$id]['name']]);
});

$app->delete('/users/:id<\d+>', function ($req, $res) use ($users) {
    $id = (int) $req->param('id');

    return isset($users[$id]) ? $res->noContent() : $res->error(404, 'User not found');
});

$app->run();
