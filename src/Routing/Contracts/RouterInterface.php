<?php

declare(strict_types=1);

namespace PivotPHP\Core\Routing\Contracts;

/**
 * Router Interface
 *
 * Contract for pluggable routing implementations.
 * Allows custom routing engines to be injected into the Application.
 *
 * @package PivotPHP\Core\Routing\Contracts
 * @since 2.0.1
 */
interface RouterInterface
{
    /**
     * Add a route to the router
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $path Route path pattern (e.g., /users/:id)
     * @param callable|array<class-string|object, string>|string $handler Route handler
     * @param array<string, mixed> $options Additional route options (middleware, name, etc.)
     * @return void
     */
    public function addRoute(string $method, string $path, callable|array|string $handler, array $options = []): void;

    /**
     * Dispatch a request to find matching route
     *
     * @param string $method HTTP method
     * @param string $path Request path
     * @return array{handler: callable|array|string, params: array<string, string>, options: array<string, mixed>, path: string}|null
     * @throws \RuntimeException If route not found or invalid
     */
    public function dispatch(string $method, string $path): ?array;

    /**
     * Get all registered routes
     *
     * @return array<int, array{method: string, path: string, handler: mixed, options?: array<string, mixed>}>
     */
    public function getRoutes(): array;

    /**
     * Add a route group with shared prefix and options
     *
     * @param string $prefix Group prefix (e.g., /api/v1)
     * @param callable $callback Callback to register routes within group
     * @param array<string, mixed> $options Group options (middleware, etc.)
     * @return void
     */
    public function group(string $prefix, callable $callback, array $options = []): void;

    /**
     * Clear all registered routes
     *
     * @return void
     */
    public function clear(): void;
}
