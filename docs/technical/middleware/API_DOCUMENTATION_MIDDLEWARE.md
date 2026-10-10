# API Documentation Middleware

## Overview

The `ApiDocumentationMiddleware` is a PivotPHP Core feature that automatically generates OpenAPI 3.0.0 specification and serves Swagger UI for your API routes.

## Features

- ✅ **Automatic OpenAPI 3.0.0 Generation** - Generates complete OpenAPI specification from all routes
- ✅ **Swagger UI Integration** - Provides interactive documentation interface
- ✅ **PHPDoc Parsing** - Extracts route metadata from PHPDoc comments
- ✅ **Zero Configuration** - Works out of the box with sensible defaults
- ✅ **Customizable Endpoints** - Configure custom paths for documentation
- ✅ **CORS Support** - Includes CORS headers for cross-origin requests

## Basic Usage

### Simple Setup

```php
<?php

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;

$app = new Application();

// Add automatic documentation middleware
$app->use(new ApiDocumentationMiddleware());

// Your routes with PHPDoc documentation
$app->get('/users', function($req, $res) {
    /**
     * @summary List all users
     * @description Returns a list of all users in the system
     * @tags Users
     * @response 200 array List of users
     */
    return $res->json(['users' => User::all()]);
});

$app->run();
```

### Access Documentation

- **Swagger UI**: `http://localhost:8080/swagger`
- **OpenAPI JSON**: `http://localhost:8080/docs`

## Configuration Options

```php
$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',                    // JSON OpenAPI endpoint
    'swagger_path' => '/swagger',              // Swagger UI endpoint
    'base_url' => 'http://localhost:8080',     // Base URL for API
    'enabled' => true                          // Enable/disable middleware
]));
```

## PHPDoc Documentation Format

### Basic Route Documentation

```php
$app->get('/users', function($req, $res) {
    /**
     * @summary List all users
     * @description Returns a list of all users in the system
     * @tags Users
     * @response 200 array List of users
     */
    return $res->json(['users' => User::all()]);
});
```

### Route with Parameters

```php
$app->get('/users/:id', function($req, $res) {
    /**
     * @summary Get user by ID
     * @description Returns a single user by their ID
     * @tags Users
     * @param int id User ID
     * @response 200 object User object
     * @response 404 object User not found
     */
    $userId = $req->param('id');
    return $res->json(['user' => User::find($userId)]);
});
```

### POST Route with Body

```php
$app->post('/users', function($req, $res) {
    /**
     * @summary Create new user
     * @description Creates a new user in the system
     * @tags Users
     * @body object User data
     * @response 201 object Created user
     * @response 400 object Validation error
     */
    $userData = $req->getBody();
    $user = User::create($userData);
    return $res->status(201)->json(['user' => $user]);
});
```

### Route with Query Parameters

```php
$app->get('/products', function($req, $res) {
    /**
     * @summary List all products
     * @description Returns a list of all products with pagination
     * @tags Products
     * @query int limit Maximum number of products to return
     * @query int offset Number of products to skip
     * @query string search Search term for product names
     * @response 200 array List of products
     */
    $limit = $req->query('limit', 10);
    $offset = $req->query('offset', 0);
    $search = $req->query('search');
    
    return $res->json(['products' => Product::search($search, $limit, $offset)]);
});
```

## Supported PHPDoc Tags

| Tag | Description | Example |
|-----|-------------|---------|
| `@summary` | Short description of the endpoint | `@summary Get user by ID` |
| `@description` | Detailed description | `@description Returns a single user by their ID` |
| `@tags` | Group routes in Swagger UI | `@tags Users` |
| `@param` | Path parameters | `@param int id User ID` |
| `@query` | Query parameters | `@query int limit Maximum results` |
| `@body` | Request body | `@body object User data` |
| `@response` | Response codes and descriptions | `@response 200 object User object` |

## Complete Example

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;

$app = new Application();

// Add documentation middleware
$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/api/docs',
    'swagger_path' => '/api/swagger',
    'base_url' => 'https://api.example.com'
]));

// Users API
$app->get('/users', function($req, $res) {
    /**
     * @summary List all users
     * @description Returns a paginated list of all users
     * @tags Users
     * @query int limit Maximum number of users to return (default: 10)
     * @query int offset Number of users to skip (default: 0)
     * @response 200 array List of users
     */
    return $res->json(['users' => User::paginate()]);
});

$app->get('/users/:id', function($req, $res) {
    /**
     * @summary Get user by ID
     * @description Returns a single user by their ID
     * @tags Users
     * @param int id User ID
     * @response 200 object User object
     * @response 404 object User not found
     */
    $user = User::find($req->param('id'));
    return $user ? $res->json(['user' => $user]) : $res->status(404)->json(['error' => 'User not found']);
});

$app->post('/users', function($req, $res) {
    /**
     * @summary Create new user
     * @description Creates a new user in the system
     * @tags Users
     * @body object User data (name, email, password)
     * @response 201 object Created user
     * @response 400 object Validation error
     */
    $userData = $req->getBody();
    $user = User::create($userData);
    return $res->status(201)->json(['user' => $user]);
});

$app->put('/users/:id', function($req, $res) {
    /**
     * @summary Update user
     * @description Updates an existing user
     * @tags Users
     * @param int id User ID
     * @body object User data to update
     * @response 200 object Updated user
     * @response 404 object User not found
     * @response 400 object Validation error
     */
    $user = User::find($req->param('id'));
    if (!$user) {
        return $res->status(404)->json(['error' => 'User not found']);
    }
    
    $user->update($req->getBody());
    return $res->json(['user' => $user]);
});

$app->delete('/users/:id', function($req, $res) {
    /**
     * @summary Delete user
     * @description Deletes a user from the system
     * @tags Users
     * @param int id User ID
     * @response 204 User deleted successfully
     * @response 404 object User not found
     */
    $user = User::find($req->param('id'));
    if (!$user) {
        return $res->status(404)->json(['error' => 'User not found']);
    }
    
    $user->delete();
    return $res->status(204);
});

// Health check
$app->get('/health', function($req, $res) {
    /**
     * @summary Health check
     * @description Returns the health status of the API
     * @tags System
     * @response 200 object Health status
     */
    return $res->json([
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '4.1.0'
    ]);
});

$app->run();
```

## Generated OpenAPI Structure

The middleware generates OpenAPI 3.0.0 specification with:

```json
{
  "openapi": "3.0.0",
  "info": {
    "title": "PivotPHP API",
    "version": "4.1.0",
    "description": "Auto-generated API documentation"
  },
  "servers": [
    {
      "url": "http://localhost:8080",
      "description": "Development server"
    }
  ],
  "paths": {
    "/users": {
      "get": {
        "summary": "List all users",
        "description": "Returns a paginated list of all users",
        "tags": ["Users"],
        "parameters": [
          {
            "name": "limit",
            "in": "query",
            "description": "Maximum number of users to return (default: 10)",
            "schema": {
              "type": "integer"
            }
          }
        ],
        "responses": {
          "200": {
            "description": "List of users",
            "content": {
              "application/json": {
                "schema": {
                  "type": "array"
                }
              }
            }
          }
        }
      }
    }
  }
}
```

## Error Handling

The middleware includes built-in error handling:

- **500 Internal Server Error**: When OpenAPI generation fails
- **404 Not Found**: When accessing non-existent documentation paths
- **Graceful Degradation**: If application instance is not available

## Security Considerations

- **Production Use**: Consider disabling documentation in production environments
- **CORS Headers**: Includes `Access-Control-Allow-Origin: *` for development
- **No Authentication**: Documentation endpoints are publicly accessible

## Integration with Other Middleware

The `ApiDocumentationMiddleware` works seamlessly with other PivotPHP middleware:

```php
use PivotPHP\Http\Factory\Psr17Factory;
use PivotPHP\Security\Cors\{CorsConfig, CorsMiddleware};
use PivotPHP\Security\Jwt\{JwtAuthMiddleware, JwtConfig};

$factory = new Psr17Factory();

// Security middlewares from pivotphp/security
$app->use(new CorsMiddleware($factory, new CorsConfig(['https://app.example.com'])));
$app->use(new JwtAuthMiddleware($factory, new JwtConfig($_ENV['JWT_SECRET'], publicPaths: ['/docs', '/swagger*'])));

// Add documentation middleware (will document all routes)
$app->use(new ApiDocumentationMiddleware());
```

## Advanced Usage

### Custom Error Responses

```php
$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',
    'swagger_path' => '/swagger',
    'error_handler' => function($error, $statusCode) {
        return [
            'error' => $error,
            'code' => $statusCode,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
]));
```

### Conditional Documentation

```php
$app->use(new ApiDocumentationMiddleware([
    'enabled' => $_ENV['APP_ENV'] !== 'production'
]));
```

## Example Output

Visit `/swagger` to see the interactive Swagger UI interface with:
- Route grouping by tags
- Interactive parameter forms
- Response examples
- Try-it-out functionality

Visit `/docs` to get the raw OpenAPI 3.0.0 JSON specification for integration with other tools.

## Related Documentation

- [OpenAPI 3.0.0 Specification](https://swagger.io/specification/)
- [Swagger UI Documentation](https://swagger.io/tools/swagger-ui/)
- [PivotPHP Middleware Guide](../middleware/README.md)
- [PivotPHP Routing Guide](../routing/README.md)