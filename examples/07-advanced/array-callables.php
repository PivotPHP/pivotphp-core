<?php

/**
 * PivotPHP — Array callable routes
 *
 * Controllers registered as [Controller::class, 'method'] or [$instance, 'method'].
 * 
 * 🚀 How to run:
 * php -S localhost:8000 examples/07-advanced/array-callables.php
 * 
 * 🧪 How to test:
 * curl http://localhost:8000/
 * curl http://localhost:8000/users
 * curl -X POST http://localhost:8000/users -H "Content-Type: application/json" -d '{"name":"John","email":"john@example.com"}'
 * curl http://localhost:8000/users/123
 * curl -X PUT http://localhost:8000/users/123 -H "Content-Type: application/json" -d '{"name":"John Updated"}'
 * curl -X DELETE http://localhost:8000/users/123
 * curl http://localhost:8000/admin/dashboard
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

// 👥 User Controller - Demonstrates instance method callables
class UserController 
{
    private array $users = [
        ['id' => 1, 'name' => 'Alice Johnson', 'email' => 'alice@example.com'],
        ['id' => 2, 'name' => 'Bob Smith', 'email' => 'bob@example.com'],
        ['id' => 3, 'name' => 'Carol Brown', 'email' => 'carol@example.com']
    ];
    
    public function index($req, $res) 
    {
        return $res->json([
            'users' => $this->users,
            'total' => count($this->users),
            'controller_type' => 'instance_method',
            'callable_format' => '[$controller, \'index\']'
        ]);
    }
    
    public function show($req, $res) 
    {
        $id = (int) $req->param('id');
        $user = $this->findUser($id);
        
        if (!$user) {
            return $res->status(404)->json(['error' => 'User not found']);
        }
        
        return $res->json([
            'user' => $user,
            'controller_type' => 'instance_method',
            'callable_format' => '[$controller, \'show\']'
        ]);
    }
    
    public function store($req, $res) 
    {
        $name = $req->input('name');
        $email = $req->input('email');

        if (!is_string($name) || !is_string($email)) {
            return $res->status(400)->json([
                'error' => 'Name and email are required'
            ]);
        }
        
        $newUser = [
            'id' => max(array_column($this->users, 'id')) + 1,
            'name' => $name,
            'email' => $email
        ];
        
        $this->users[] = $newUser;
        
        return $res->status(201)->json([
            'message' => 'User created successfully',
            'user' => $newUser,
            'controller_type' => 'instance_method'
        ]);
    }
    
    public function update($req, $res) 
    {
        $id = (int) $req->param('id');
        foreach ($this->users as &$user) {
            if ($user['id'] === $id) {
                $user['name'] = $req->input('name', $user['name']);
                $user['email'] = $req->input('email', $user['email']);
                
                return $res->json([
                    'message' => 'User updated successfully',
                    'user' => $user,
                    'controller_type' => 'instance_method'
                ]);
            }
        }
        
        return $res->status(404)->json(['error' => 'User not found']);
    }
    
    public function destroy($req, $res) 
    {
        $id = (int) $req->param('id');
        
        foreach ($this->users as $index => $user) {
            if ($user['id'] === $id) {
                unset($this->users[$index]);
                $this->users = array_values($this->users);
                
                return $res->json([
                    'message' => 'User deleted successfully',
                    'controller_type' => 'instance_method'
                ]);
            }
        }
        
        return $res->status(404)->json(['error' => 'User not found']);
    }
    
    private function findUser(int $id): ?array
    {
        foreach ($this->users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }
}

// 🔧 Admin Controller - Demonstrates static method callables
class AdminController 
{
    public static function dashboard($req, $res) 
    {
        return $res->json([
            'admin_dashboard' => [
                'total_users' => 150,
                'active_sessions' => 23,
                'server_status' => 'healthy',
                'uptime' => '15 days, 4 hours'
            ],
            'controller_type' => 'static_method',
            'callable_format' => '[AdminController::class, \'dashboard\']',
            'php_version_compatibility' => PHP_VERSION
        ]);
    }
    
    public static function settings($req, $res) 
    {
        return $res->json([
            'settings' => [
                'maintenance_mode' => false,
                'debug_enabled' => true,
                'cache_enabled' => true,
                'rate_limiting' => true
            ],
            'controller_type' => 'static_method',
            'editable' => false,
            'note' => 'This is a demo endpoint'
        ]);
    }
    
    public static function logs($req, $res) 
    {
        $level = $req->query('level', 'info');
        $limit = (int) $req->query('limit', 10);
        
        $logs = [
            ['timestamp' => '2024-01-15 10:30:00', 'level' => 'info', 'message' => 'User login successful'],
            ['timestamp' => '2024-01-15 10:25:00', 'level' => 'warning', 'message' => 'High memory usage detected'],
            ['timestamp' => '2024-01-15 10:20:00', 'level' => 'error', 'message' => 'Database connection failed'],
            ['timestamp' => '2024-01-15 10:15:00', 'level' => 'info', 'message' => 'Application started']
        ];
        
        // Filter by level if specified
        if ($level !== 'all') {
            $logs = array_filter($logs, fn($log) => $log['level'] === $level);
        }
        
        // Apply limit
        $logs = array_slice($logs, 0, $limit);
        
        return $res->json([
            'logs' => array_values($logs),
            'filters' => ['level' => $level, 'limit' => $limit],
            'controller_type' => 'static_method'
        ]);
    }
}

// 📦 Product Controller - Demonstrates mixed callable patterns
class ProductController 
{
    private static array $products = [
        ['id' => 1, 'name' => 'Laptop Pro', 'price' => 1299.99, 'category' => 'electronics'],
        ['id' => 2, 'name' => 'Wireless Mouse', 'price' => 29.99, 'category' => 'accessories'],
        ['id' => 3, 'name' => 'USB-C Hub', 'price' => 79.99, 'category' => 'accessories']
    ];
    
    public static function index($req, $res) 
    {
        $category = $req->query('category');
        $products = self::$products;
        
        if ($category) {
            $products = array_filter($products, fn($p) => $p['category'] === $category);
        }
        
        return $res->json([
            'products' => array_values($products),
            'filters' => ['category' => $category],
            'controller_type' => 'static_method',
            'note' => 'This static method can be called without instantiation'
        ]);
    }
    
    public function featured($req, $res) 
    {
        // Instance method for demonstration
        $featured = array_slice(self::$products, 0, 2);
        
        return $res->json([
            'featured_products' => $featured,
            'controller_type' => 'instance_method',
            'note' => 'This instance method requires controller instantiation'
        ]);
    }
}

// 🎯 Create Application and Controllers
$app = new Application();

// Instantiate controllers for instance methods
$userController = new UserController();
$productController = new ProductController();

// 🏠 Home route - Demonstrates available endpoints
$app->get('/', function($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - Array Callables Demo',
        'features' => [
            'PHP 8.4+ compatibility',
            'callable|array union types',
            'Instance and static method support',
            'Full backward compatibility',
            'Clean controller organization'
        ],
        'available_routes' => [
            'Users (Instance Methods)' => [
                'GET /users' => 'List all users',
                'POST /users' => 'Create new user',
                'GET /users/:id' => 'Get specific user',
                'PUT /users/:id' => 'Update user',
                'DELETE /users/:id' => 'Delete user'
            ],
            'Admin (Static Methods)' => [
                'GET /admin/dashboard' => 'Admin dashboard',
                'GET /admin/settings' => 'System settings',
                'GET /admin/logs' => 'System logs (with filters)'
            ],
            'Products (Mixed Methods)' => [
                'GET /products' => 'List products (static)',
                'GET /products/featured' => 'Featured products (instance)'
            ]
        ],
        'callable_examples' => [
            'Instance Method' => '[$controller, \'method\']',
            'Static Method' => '[Controller::class, \'method\']',
            'Closure' => 'function($req, $res) { ... }'
        ],
        'version_info' => [
            'framework' => 'PivotPHP Core ' . Application::VERSION,
            'php_version' => PHP_VERSION,
            'array_callable_support' => 'YES'
        ]
    ]);
});

// 👥 User Routes - Instance Method Callables
$app->get('/users', [$userController, 'index']);
$app->post('/users', [$userController, 'store']);
$app->get('/users/:id', [$userController, 'show']);
$app->put('/users/:id', [$userController, 'update']);
$app->delete('/users/:id', [$userController, 'destroy']);

// 🔧 Admin Routes - Static Method Callables
$app->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$app->get('/admin/settings', [AdminController::class, 'settings']);
$app->get('/admin/logs', [AdminController::class, 'logs']);

// 📦 Product Routes - Mixed Method Callables
$app->get('/products', [ProductController::class, 'index']); // Static
$app->get('/products/featured', [$productController, 'featured']); // Instance

// 🧪 Demonstration route - Shows all callable types
$app->get('/demo/callables', function($req, $res) {
    return $res->json([
        'demonstration' => 'All callable types supported by PivotPHP',
        'supported_formats' => [
            [
                'type' => 'Closure',
                'syntax' => 'function($req, $res) { ... }',
                'example' => 'This current route!',
                'use_case' => 'Quick routes, simple logic'
            ],
            [
                'type' => 'Instance Method Array',
                'syntax' => '[$controller, \'method\']',
                'example' => '[$userController, \'index\']',
                'use_case' => 'Stateful controllers, dependency injection'
            ],
            [
                'type' => 'Static Method Array',
                'syntax' => '[Controller::class, \'method\']',
                'example' => '[AdminController::class, \'dashboard\']',
                'use_case' => 'Stateless operations, utility methods'
            ],
            [
                'type' => 'Named Function',
                'syntax' => '\'functionName\'',
                'example' => '\'handleRequest\'',
                'use_case' => 'Reusable functions, simple handlers'
            ]
        ],
        'new_in_v1_1_3' => [
            'PHP 8.4+ compatibility',
            'callable|array union types',
            'Enhanced type safety',
            'Better IDE support',
            'Improved performance'
        ],
        'backward_compatibility' => 'All existing code continues to work without changes'
    ]);
});

// 🚀 Run the application
$app->run();