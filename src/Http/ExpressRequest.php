<?php

namespace PivotPHP\Core\Http;

use PivotPHP\Core\Http\Contracts\ExpressRequestInterface;
use PivotPHP\Core\Http\Contracts\AttributeInterface;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Uri;
use PivotPHP\Core\Http\Pool\Psr7Pool;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use stdClass;

/**
 * Express.js-style Request Adapter
 *
 * This class provides an Express.js-compatible API as a facade/adapter over PSR-7 ServerRequest.
 * It eliminates the redundancy of the previous hybrid implementation by using composition
 * instead of maintaining parallel data structures.
 *
 * Architecture:
 * - Wraps a PSR-7 ServerRequest instance (composition over inheritance)
 * - Provides Express.js convenience methods
 * - Implements PSR-7 ServerRequestInterface for middleware compatibility
 * - No data duplication - single source of truth (PSR-7)
 *
 * @package PivotPHP\Core\Http
 */
class ExpressRequest implements ExpressRequestInterface, ServerRequestInterface, AttributeInterface
{
    /**
     * PSR-7 ServerRequest instance (single source of truth)
     */
    private ServerRequestInterface $psr7Request;

    /**
     * Route path pattern (e.g., "/users/:id")
     */
    private string $path = '';

    /**
     * Actual request path (e.g., "/users/123")
     */
    private string $pathCallable = '';

    /**
     * Cached HeaderRequest instance for legacy compatibility
     */
    private ?HeaderRequest $headerRequest = null;

    /**
     * Cached query parameters as stdClass for legacy compatibility
     */
    private ?stdClass $cachedQuery = null;

    /**
     * Cached body for legacy compatibility
     */
    private mixed $cachedBody = null;

    /**
     * Cached params as stdClass for legacy compatibility
     */
    private ?stdClass $cachedParams = null;

    /**
     * Constructor
     *
     * @param ServerRequestInterface $psr7Request PSR-7 request to wrap
     * @param string $path Route path pattern
     * @param string $pathCallable Actual request path
     */
    public function __construct(
        ServerRequestInterface $psr7Request,
        string $path = '',
        string $pathCallable = ''
    ) {
        $this->psr7Request = $psr7Request;
        $this->path = $path;
        $this->pathCallable = $pathCallable;
    }

    /**
     * Destructor - return PSR-7 object to pool
     */
    public function __destruct()
    {
        Psr7Pool::returnServerRequest($this->psr7Request);
    }

    // ========================================================================
    // STATIC FACTORY METHOD
    // ========================================================================

    /**
     * Create Request from PHP globals
     *
     * @return self
     */
    public static function createFromGlobals(): self
    {
        $psr7Request = ServerRequest::createFromGlobals();
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        return new self($psr7Request, $path, $path);
    }

    // ========================================================================
    // EXPRESS.JS CONVENIENCE METHODS
    // ========================================================================

    /**
     * Get a route parameter value
     *
     * @param string $key Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->psr7Request->getAttribute("route_params.{$key}", $default);
    }

    /**
     * Get a query string parameter value
     *
     * @param string $key Query parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $queryParams = $this->psr7Request->getQueryParams();
        return $queryParams[$key] ?? $default;
    }

    /**
     * Get a request body parameter value
     *
     * @param string $key Body parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        $parsedBody = $this->psr7Request->getParsedBody();

        if (is_array($parsedBody)) {
            return $parsedBody[$key] ?? $default;
        }

        if (is_object($parsedBody)) {
            return $parsedBody->{$key} ?? $default;
        }

        return $default;
    }

    /**
     * Get an uploaded file
     *
     * @param string $key File input name
     * @return array|null
     */
    public function file(string $key): ?array
    {
        $uploadedFiles = $this->psr7Request->getUploadedFiles();
        $file = $uploadedFiles[$key] ?? null;

        if ($file === null) {
            return null;
        }

        // Convert UploadedFileInterface to array format
        if ($file instanceof \Psr\Http\Message\UploadedFileInterface) {
            return [
                'name' => $file->getClientFilename(),
                'type' => $file->getClientMediaType(),
                'size' => $file->getSize(),
                'error' => $file->getError(),
                'tmp_name' => $file->getStream()->getMetadata('uri') ?? ''
            ];
        }

        return is_array($file) ? $file : null;
    }

    /**
     * Check if a file was uploaded
     *
     * @param string $key File input name
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        $file = $this->file($key);
        return $file !== null && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get the client IP address
     *
     * @return string
     */
    public function ip(): string
    {
        $serverParams = $this->psr7Request->getServerParams();

        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ips = explode(',', $serverParams[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get the User-Agent header
     *
     * @return string
     */
    public function userAgent(): string
    {
        $serverParams = $this->psr7Request->getServerParams();
        return $serverParams['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if request is an AJAX request
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        $requestedWith = $this->header('X-Requested-With');
        return !empty($requestedWith) && strtolower($requestedWith) === 'xmlhttprequest';
    }

    /**
     * Check if request is over HTTPS
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        $serverParams = $this->psr7Request->getServerParams();

        return (!empty($serverParams['HTTPS']) && $serverParams['HTTPS'] !== 'off')
            || (!empty($serverParams['HTTP_X_FORWARDED_PROTO']) && $serverParams['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($serverParams['HTTP_X_FORWARDED_SSL']) && $serverParams['HTTP_X_FORWARDED_SSL'] === 'on');
    }

    /**
     * Get the full URL of the request
     *
     * @return string
     */
    public function fullUrl(): string
    {
        $uri = $this->psr7Request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $port = $uri->getPort();
        $path = $uri->getPath();
        $query = $uri->getQuery();

        $url = ($scheme ? "{$scheme}://" : '') . $host;

        if ($port !== null && (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443))) {
            $url .= ":{$port}";
        }

        $url .= $path;

        if ($query !== '') {
            $url .= "?{$query}";
        }

        return $url;
    }

    /**
     * Get a header value
     *
     * @param string $name Header name
     * @return string|null
     */
    public function header(string $name): ?string
    {
        $headerLine = $this->psr7Request->getHeaderLine($name);
        return $headerLine !== '' ? $headerLine : null;
    }

    /**
     * Set multiple headers at once
     *
     * @param array $headers Associative array of headers
     * @return static
     */
    public function setHeaders(array $headers): self
    {
        $request = $this->psr7Request;

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        // Return new instance with updated PSR-7 request
        return new self($request, $this->path, $this->pathCallable);
    }

    // ========================================================================
    // LEGACY COMPATIBILITY METHODS
    // ========================================================================

    /**
     * Get the route path pattern
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the route path pattern
     *
     * @param string $path Route path pattern
     * @return static
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get the actual request path
     *
     * @return string
     */
    public function getPathCallable(): string
    {
        return $this->pathCallable;
    }

    /**
     * Get all route parameters as stdClass
     *
     * @return stdClass
     */
    public function getParams(): stdClass
    {
        $params = $this->psr7Request->getAttribute('route_params', []);
        return (object) (is_array($params) ? $params : []);
    }

    /**
     * Get a route parameter (legacy method)
     *
     * @param string $key Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->param($key, $default);
    }

    /**
     * Get client IP (legacy method)
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip();
    }

    /**
     * Get all query parameters as stdClass
     *
     * @return stdClass
     */
    public function getQuerys(): stdClass
    {
        return (object) $this->psr7Request->getQueryParams();
    }

    /**
     * Get a query parameter (legacy method)
     *
     * @param string $key Query parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    /**
     * Set a custom attribute
     *
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     * @return static
     */
    public function setAttribute(string $name, mixed $value): self
    {
        $this->psr7Request = $this->psr7Request->withAttribute($name, $value);
        return $this;
    }

    /**
     * Check if a custom attribute exists
     *
     * @param string $name Attribute name
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return $this->psr7Request->getAttribute($name) !== null;
    }

    /**
     * Remove a custom attribute
     *
     * @param string $name Attribute name
     * @return static
     */
    public function removeAttribute(string $name): self
    {
        $this->psr7Request = $this->psr7Request->withoutAttribute($name);
        return $this;
    }

    /**
     * Set multiple attributes at once
     *
     * @param array $attributes Associative array of attributes
     * @return static
     */
    public function setAttributes(array $attributes): self
    {
        $request = $this->psr7Request;

        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $this->psr7Request = $request;
        return $this;
    }

    // ========================================================================
    // MAGIC METHODS FOR DYNAMIC PROPERTIES
    // ========================================================================

    /**
     * Get a dynamic property
     *
     * @param string $name Property name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        // Handle special properties that map to class properties/methods
        return match ($name) {
            'method' => $this->getMethod(),
            'path' => $this->getPath(),
            'pathCallable' => $this->getPathCallable(),
            'params' => $this->cachedParams ??= $this->getParams(),
            'query' => $this->cachedQuery ??= $this->getQuerys(),
            'body' => $this->cachedBody ??= $this->getParsedBodyAsObject(),
            'headers' => $this->getHeaderRequest(),
            'files' => $this->getUploadedFiles(),
            default => $this->psr7Request->getAttribute($name),
        };
    }

    /**
     * Get parsed body as stdClass for legacy compatibility
     *
     * @return stdClass|array
     */
    private function getParsedBodyAsObject(): stdClass|array
    {
        $body = $this->getParsedBody();

        // If body is null (GET requests), return empty array
        if ($body === null) {
            return [];
        }

        // If body is array, convert to stdClass for property access
        if (is_array($body)) {
            return (object) $body;
        }

        // Otherwise return as-is (could be object already)
        return $body;
    }

    /**
     * Get HeaderRequest instance (lazy loading for legacy compatibility)
     *
     * @return HeaderRequest
     */
    private function getHeaderRequest(): HeaderRequest
    {
        if ($this->headerRequest === null) {
            $this->headerRequest = new HeaderRequest();
        }
        return $this->headerRequest;
    }

    /**
     * Set a dynamic property
     *
     * @param string $name Property name
     * @param mixed $value Property value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->psr7Request = $this->psr7Request->withAttribute($name, $value);
    }

    /**
     * Check if a dynamic property is set
     *
     * @param string $name Property name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        // Check special properties first
        if (in_array($name, ['method', 'path', 'pathCallable', 'params', 'query', 'body', 'headers', 'files'], true)) {
            return true;
        }

        return $this->psr7Request->getAttribute($name) !== null;
    }

    /**
     * Unset a dynamic property
     *
     * @param string $name Property name
     * @return void
     */
    public function __unset(string $name): void
    {
        $this->psr7Request = $this->psr7Request->withoutAttribute($name);
    }

    // ========================================================================
    // PSR-7 ServerRequestInterface DELEGATION METHODS
    // ========================================================================

    /**
     * {@inheritdoc}
     */
    public function getServerParams(): array
    {
        return $this->psr7Request->getServerParams();
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams(): array
    {
        return $this->psr7Request->getCookieParams();
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withCookieParams($cookies),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams(): array
    {
        return $this->psr7Request->getQueryParams();
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withQueryParams($query),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles(): array
    {
        return $this->psr7Request->getUploadedFiles();
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withUploadedFiles($uploadedFiles),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->psr7Request->getParsedBody();
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withParsedBody($data),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->psr7Request->getAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        return $this->psr7Request->getAttribute($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($name, $value): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withAttribute($name, $value),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($name): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withoutAttribute($name),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget(): string
    {
        return $this->psr7Request->getRequestTarget();
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withRequestTarget($requestTarget),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->psr7Request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withMethod($method),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): UriInterface
    {
        return $this->psr7Request->getUri();
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withUri($uri, $preserveHost),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->psr7Request->getProtocolVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withProtocolVersion($version),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->psr7Request->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name): bool
    {
        return $this->psr7Request->hasHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name): array
    {
        return $this->psr7Request->getHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name): string
    {
        return $this->psr7Request->getHeaderLine($name);
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withHeader($name, $value),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withAddedHeader($name, $value),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withoutHeader($name),
            $this->path,
            $this->pathCallable
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->psr7Request->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        return new self(
            $this->psr7Request->withBody($body),
            $this->path,
            $this->pathCallable
        );
    }
}
