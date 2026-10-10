<?php

/**
 * PivotPHP — JSON API with pagination, search and validation
 *
 * Run:   php -S localhost:8000 examples/01-basics/json-api.php
 * Try:   curl 'http://localhost:8000/api/products?page=1&limit=2'
 *        curl 'http://localhost:8000/api/products?category=books'
 *        curl http://localhost:8000/api/products/1
 *        curl 'http://localhost:8000/api/search?q=lap'
 *        curl -X POST http://localhost:8000/api/products -H 'Content-Type: application/json' \
 *             -d '{"name":"Tablet","price":499.9,"category":"electronics"}'
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Validation\Validator;

$app = new Application();

$products = [
    1 => ['id' => 1, 'name' => 'Smartphone', 'price' => 899.99, 'category' => 'electronics'],
    2 => ['id' => 2, 'name' => 'Laptop', 'price' => 1299.99, 'category' => 'electronics'],
    3 => ['id' => 3, 'name' => 'PHP Book', 'price' => 49.99, 'category' => 'books'],
];

// Global middleware scoped by path inside the middleware. $req is the PSR-7 request;
// change the response returned by $next().
$app->use(function ($req, $res, $next) {
    $response = $next();

    return str_starts_with($req->getUri()->getPath(), '/api/')
        ? $response->withHeader('X-API-Version', '1.0')
        : $response;
});

$app->get('/api/products', function ($req, $res) use ($products) {
    $page = max(1, (int) $req->query('page', 1));
    $limit = min(100, max(1, (int) $req->query('limit', 10)));
    $category = $req->query('category');

    $items = array_values(array_filter(
        $products,
        fn (array $p): bool => $category === null || $p['category'] === $category
    ));

    return $res->json([
        'data' => array_slice($items, ($page - 1) * $limit, $limit),
        'meta' => ['page' => $page, 'limit' => $limit, 'total' => count($items)],
    ]);
});

$app->get('/api/products/:id<\d+>', function ($req, $res) use ($products) {
    $id = (int) $req->param('id');

    return isset($products[$id]) ? $res->json($products[$id]) : $res->error(404, 'Product not found');
});

$app->get('/api/search', function ($req, $res) use ($products) {
    $query = strtolower((string) $req->query('q', ''));

    $results = array_values(array_filter(
        $products,
        fn (array $p): bool => $query !== '' && str_contains(strtolower($p['name']), $query)
    ));

    return $res->json(['query' => $query, 'results' => $results]);
});

$app->post('/api/products', function ($req, $res) {
    $data = $req->psr7()->getParsedBody();
    $data = is_array($data) ? $data : [];

    $validator = new Validator([
        'name' => 'required|string',
        'price' => 'required|numeric',
        'category' => 'required|string',
    ]);

    if (!$validator->validate($data)) {
        return $res->status(422)->json(['errors' => $validator->getErrors()]);
    }

    return $res->status(201)->json(['id' => 4] + array_intersect_key($data, array_flip(['name', 'price', 'category'])));
});

$app->run();
