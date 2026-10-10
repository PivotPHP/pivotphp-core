<?php

/**
 * 📚 PivotPHP v1.2.0 - API Documentation Example
 * 
 * Demonstrates automatic API documentation generation using ApiDocumentationMiddleware.
 * This replaces the deprecated OpenApiExporter with a more integrated approach.
 * 
 * 🚀 Como executar:
 * php -S localhost:8080 examples/api_documentation_example.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8080/             # API info
 * curl http://localhost:8080/docs         # OpenAPI JSON
 * curl http://localhost:8080/swagger      # Interactive Swagger UI
 * 
 * NOTA: OpenApiExporter foi deprecado em favor do ApiDocumentationMiddleware
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;

// Create application
$app = new Application();

// Add automatic API documentation middleware
// NOTE: This replaces the deprecated OpenApiExporter class
$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',        // JSON endpoint
    'swagger_path' => '/swagger',  // Swagger UI endpoint
    'base_url' => 'http://localhost:8080',
    'enabled' => true
]));

// Add some example routes with documentation
$app->get('/users', function($req, $res) {
    // Documentation is handled by the middleware automatically
    return $res->json([
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
    ]);
});

$app->get('/users/:id', function($req, $res) {
    // Documentation is handled by the middleware automatically
    $userId = $req->param('id');
    
    if ($userId === '1') {
        return $res->json(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
    }
    
    return $res->status(404)->json(['error' => 'User not found']);
});

$app->post('/users', function($req, $res) {
    // Documentation is handled by the middleware automatically
    // Simulate user creation
    $newUser = [
        'id' => 3,
        'name' => $req->input('name', 'Unknown'),
        'email' => $req->input('email', 'unknown@example.com'),
    ];
    
    return $res->status(201)->json($newUser);
});

$app->get('/products', function($req, $res) {
    // Documentation is handled by the middleware automatically
    return $res->json([
        ['id' => 1, 'name' => 'Laptop', 'price' => 999.99],
        ['id' => 2, 'name' => 'Mouse', 'price' => 29.99]
    ]);
});

$app->get('/health', function($req, $res) {
    // Documentation is handled by the middleware automatically
    return $res->json([
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.2.0'
    ]);
});

// Add info route
$app->get('/', function($req, $res) {
    return $res->json([
        'message' => 'PivotPHP Core API Documentation Example',
        'docs' => 'Visit /swagger for interactive documentation',
        'json' => 'Visit /docs for OpenAPI JSON',
        'version' => '1.2.0'
    ]);
});

// Run the application
$app->run();