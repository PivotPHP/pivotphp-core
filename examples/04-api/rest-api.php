<?php

/**
 * PivotPHP — REST API with a controller
 *
 * Status codes: 200 read, 201 + Location on create, 204 on delete, 404 unknown id,
 * 405 + Allow for a wrong method (handled by the core), 422 validation errors.
 *
 * Run:   php -S localhost:8000 examples/04-api/rest-api.php
 * Try:   curl http://localhost:8000/api/v1/products
 *        curl http://localhost:8000/api/v1/products/1
 *        curl -i -X POST http://localhost:8000/api/v1/products -H 'Content-Type: application/json' \
 *             -d '{"name":"Notebook","price":2500.99}'
 *        curl -i -X DELETE http://localhost:8000/api/v1/products/1
 *        curl -i -X PATCH http://localhost:8000/api/v1/products
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Validation\Validator;

final class ProductController
{
    /** @var array<int, array{id: int, name: string, price: float}> */
    private array $products = [
        1 => ['id' => 1, 'name' => 'Smartphone', 'price' => 899.99],
        2 => ['id' => 2, 'name' => 'Laptop', 'price' => 1299.99],
    ];

    public function index($req, $res)
    {
        return $res->json(['data' => array_values($this->products)]);
    }

    public function show($req, $res)
    {
        $product = $this->products[(int) $req->param('id')] ?? null;

        return $product !== null ? $res->json(['data' => $product]) : $res->error(404, 'Product not found');
    }

    public function store($req, $res)
    {
        $data = $req->psr7()->getParsedBody();
        $data = is_array($data) ? $data : [];

        $validator = new Validator(['name' => 'required|string', 'price' => 'required|numeric']);
        if (!$validator->validate($data)) {
            return $res->status(422)->json(['errors' => $validator->getErrors()]);
        }

        $product = ['id' => 3, 'name' => (string) $data['name'], 'price' => (float) $data['price']];

        return $res->status(201)->header('Location', '/api/v1/products/3')->json(['data' => $product]);
    }

    public function destroy($req, $res)
    {
        return isset($this->products[(int) $req->param('id')])
            ? $res->noContent()
            : $res->error(404, 'Product not found');
    }
}

$app = new Application();
$products = new ProductController();

$app->get('/api/v1/products', [$products, 'index']);
$app->post('/api/v1/products', [$products, 'store']);
$app->get('/api/v1/products/:id<\d+>', [$products, 'show']);
$app->delete('/api/v1/products/:id<\d+>', [$products, 'destroy']);

$app->run();
