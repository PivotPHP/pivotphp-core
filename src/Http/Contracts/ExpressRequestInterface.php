<?php

namespace PivotPHP\Core\Http\Contracts;

use stdClass;

/**
 * Express.js-style Request API Interface
 *
 * This interface defines the Express.js-compatible API for HTTP requests.
 * It provides convenient methods for accessing request data without dealing
 * with the complexity of PSR-7 interfaces.
 *
 * This is the public-facing API that developers should use for working with requests.
 *
 * @package PivotPHP\Core\Http\Contracts
 */
interface ExpressRequestInterface
{
    // ========================================================================
    // EXPRESS.JS CONVENIENCE METHODS
    // ========================================================================

    /**
     * Get a route parameter value
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed Parameter value or default
     *
     * @example
     * // Route: /users/:id
     * $id = $req->param('id'); // Returns "123" for /users/123
     */
    public function param(string $key, mixed $default = null): mixed;

    /**
     * Get a query string parameter value
     *
     * @param string $key Query parameter name
     * @param mixed $default Default value if not found
     * @return mixed Query parameter value or default
     *
     * @example
     * // URL: /users?search=john&limit=10
     * $search = $req->get('search'); // Returns "john"
     * $limit = $req->get('limit');   // Returns "10"
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get a request body parameter value (from POST data)
     *
     * @param string $key Body parameter name
     * @param mixed $default Default value if not found
     * @return mixed Body parameter value or default
     *
     * @example
     * // POST data: {"name": "John", "email": "john@example.com"}
     * $name = $req->input('name');   // Returns "John"
     * $email = $req->input('email'); // Returns "john@example.com"
     */
    public function input(string $key, mixed $default = null): mixed;

    /**
     * Get an uploaded file
     *
     * @param string $key File input name
     * @return array|null File information array or null if not found
     *
     * @example
     * // <input type="file" name="avatar">
     * $file = $req->file('avatar');
     * // Returns: ['name' => 'photo.jpg', 'type' => 'image/jpeg', ...]
     */
    public function file(string $key): ?array;

    /**
     * Check if a file was uploaded
     *
     * @param string $key File input name
     * @return bool True if file exists, false otherwise
     *
     * @example
     * if ($req->hasFile('avatar')) {
     *     $file = $req->file('avatar');
     *     // Process file...
     * }
     */
    public function hasFile(string $key): bool;

    /**
     * Get the client IP address
     *
     * @return string Client IP address
     *
     * @example
     * $ip = $req->ip(); // Returns "192.168.1.1"
     */
    public function ip(): string;

    /**
     * Get the User-Agent header
     *
     * @return string User agent string
     *
     * @example
     * $userAgent = $req->userAgent(); // Returns "Mozilla/5.0..."
     */
    public function userAgent(): string;

    /**
     * Check if request is an AJAX request
     *
     * @return bool True if AJAX request, false otherwise
     *
     * @example
     * if ($req->isAjax()) {
     *     return $res->json(['data' => $data]);
     * }
     */
    public function isAjax(): bool;

    /**
     * Check if request is over HTTPS
     *
     * @return bool True if HTTPS, false otherwise
     *
     * @example
     * if (!$req->isSecure()) {
     *     return $res->redirect('https://' . $_SERVER['HTTP_HOST']);
     * }
     */
    public function isSecure(): bool;

    /**
     * Get the full URL of the request
     *
     * @return string Full URL including scheme, host, and path
     *
     * @example
     * $url = $req->fullUrl(); // Returns "https://example.com/users/123?page=2"
     */
    public function fullUrl(): string;

    /**
     * Get a header value
     *
     * @param string $name Header name (case-insensitive)
     * @return string|null Header value or null if not found
     *
     * @example
     * $token = $req->header('Authorization'); // Returns "Bearer token123"
     * $type = $req->header('Content-Type');   // Returns "application/json"
     */
    public function header(string $name): ?string;

    /**
     * Set multiple headers at once
     *
     * @param array $headers Associative array of headers
     * @return static
     *
     * @example
     * $req->setHeaders([
     *     'X-Custom-Header' => 'value',
     *     'X-Request-ID' => '12345'
     * ]);
     */
    public function setHeaders(array $headers): self;

    // ========================================================================
    // LEGACY COMPATIBILITY METHODS (maintained for backward compatibility)
    // ========================================================================

    /**
     * Get the route path pattern
     *
     * @return string Route path pattern (e.g., "/users/:id")
     */
    public function getPath(): string;

    /**
     * Set the route path pattern
     *
     * @param string $path Route path pattern
     * @return static
     */
    public function setPath(string $path): self;

    /**
     * Get the actual request path (callable path)
     *
     * @return string Actual request path (e.g., "/users/123")
     */
    public function getPathCallable(): string;

    /**
     * Get all route parameters as stdClass
     *
     * @return stdClass All route parameters
     */
    public function getParams(): stdClass;

    /**
     * Get a route parameter (legacy method, use param() instead)
     *
     * @param string $key Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function getParam(string $key, mixed $default = null): mixed;

    /**
     * Get client IP (legacy method, use ip() instead)
     *
     * @return string
     */
    public function getIp(): string;

    /**
     * Get all query parameters as stdClass
     *
     * @return stdClass All query parameters
     */
    public function getQuerys(): stdClass;

    /**
     * Get a query parameter (legacy method, use get() instead)
     *
     * @param string $key Query parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null): mixed;

    /**
     * Set a custom attribute on the request
     *
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     * @return static
     *
     * @example
     * $req->setAttribute('user', $authenticatedUser);
     */
    public function setAttribute(string $name, mixed $value): self;

    /**
     * Check if a custom attribute exists
     *
     * @param string $name Attribute name
     * @return bool
     */
    public function hasAttribute(string $name): bool;

    /**
     * Remove a custom attribute
     *
     * @param string $name Attribute name
     * @return static
     */
    public function removeAttribute(string $name): self;

    /**
     * Set multiple attributes at once
     *
     * @param array $attributes Associative array of attributes
     * @return static
     */
    public function setAttributes(array $attributes): self;

    // ========================================================================
    // MAGIC METHODS FOR DYNAMIC PROPERTIES
    // ========================================================================

    /**
     * Get a dynamic property (e.g., $req->user)
     *
     * @param string $name Property name
     * @return mixed
     */
    public function __get(string $name): mixed;

    /**
     * Set a dynamic property (e.g., $req->user = $user)
     *
     * @param string $name Property name
     * @param mixed $value Property value
     * @return void
     */
    public function __set(string $name, mixed $value): void;

    /**
     * Check if a dynamic property is set
     *
     * @param string $name Property name
     * @return bool
     */
    public function __isset(string $name): bool;

    /**
     * Unset a dynamic property
     *
     * @param string $name Property name
     * @return void
     */
    public function __unset(string $name): void;

    // ========================================================================
    // STATIC FACTORY METHOD
    // ========================================================================

    /**
     * Create a Request instance from PHP globals ($_GET, $_POST, $_SERVER, etc.)
     *
     * @return static
     *
     * @example
     * $request = Request::createFromGlobals();
     */
    public static function createFromGlobals(): self;
}
