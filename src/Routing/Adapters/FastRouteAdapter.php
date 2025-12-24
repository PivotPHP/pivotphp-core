<?php

declare(strict_types=1);

namespace PivotPHP\Core\Routing\Adapters;

use PivotPHP\Core\Routing\Contracts\RouterInterface;
use PivotPHP\Core\Routing\Router;

/**
 * FastRoute Adapter
 *
 * Default router adapter that wraps the static Router from pivotphp/core-routing.
 * Provides backward compatibility while implementing the RouterInterface.
 *
 * @package PivotPHP\Core\Routing\Adapters
 * @since 2.0.1
 */
class FastRouteAdapter implements RouterInterface
{
    /**
     * Current group prefix
     */
    private string $groupPrefix = '';

    /**
     * Current group options
     * @var array<string, mixed>
     */
    private array $groupOptions = [];

    /**
     * Add a route to the router
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $path Route path pattern (e.g., /users/:id)
     * @param callable|array<class-string|object, string>|string $handler Route handler
     * @param array<string, mixed> $options Additional route options
     * @return void
     */
    public function addRoute(string $method, string $path, callable|array|string $handler, array $options = []): void
    {
        // Apply group prefix if within a group
        $fullPath = $this->groupPrefix . $path;

        // Merge group options with route options
        $mergedOptions = array_merge($this->groupOptions, $options);

        // Convert string handler to callable if needed
        $callableHandler = $handler;
        if (is_string($handler) && !is_callable($handler)) {
            // Wrap string handler in a closure for Router compatibility
            $callableHandler = fn(...$args) => $handler;
        }

        // Register route using static Router
        // Router methods signature: method(string $path, callable|array $handler, array $metadata = [], callable ...$middlewares)
        Router::{strtolower($method)}($fullPath, $callableHandler, $mergedOptions);
    }

    /**
     * Dispatch a request to find matching route
     *
     * @param string $method HTTP method
     * @param string $path Request path
     * @return array{method: string, path: string, handler: callable|array<class-string|object, string>|string, params: array<string, string>, options: array<string, mixed>}|null
     */
    public function dispatch(string $method, string $path): ?array
    {
        // Use Router::identify which handles all the matching logic
        $route = Router::identify($method, $path);

        if (!$route) {
            return null;
        }

        // Normalize the structure to match RouterInterface contract
        return [
            'method' => $route['method'],
            'path' => $route['path'],
            'handler' => $route['handler'],
            'params' => $route['matched_params'] ?? [],
            'options' => $route['metadata'] ?? [],
        ];
    }

    /**
     * Get all registered routes
     *
     * @return array<int, array{method: string, path: string, handler: mixed, options?: array<string, mixed>}>
     */
    public function getRoutes(): array
    {
        return Router::getRoutes();
    }

    /**
     * Add a route group with shared prefix and options
     *
     * @param string $prefix Group prefix (e.g., /api/v1)
     * @param callable $callback Callback to register routes within group
     * @param array<string, mixed> $options Group options (middleware, etc.)
     * @return void
     */
    public function group(string $prefix, callable $callback, array $options = []): void
    {
        // Store previous group state
        $previousPrefix = $this->groupPrefix;
        $previousOptions = $this->groupOptions;

        // Set current group state
        $this->groupPrefix .= $prefix;
        $this->groupOptions = array_merge($this->groupOptions, $options);

        // Execute callback (routes registered within will use group prefix)
        $callback($this);

        // Restore previous group state
        $this->groupPrefix = $previousPrefix;
        $this->groupOptions = $previousOptions;
    }

    /**
     * Clear all registered routes
     *
     * @return void
     */
    public function clear(): void
    {
        Router::clear();
        $this->groupPrefix = '';
        $this->groupOptions = [];
    }
}
