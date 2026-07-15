<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Implementation of PSR-7 ServerRequestInterface
 *
 * This class implements the ServerRequestInterface for handling server requests
 * following the PSR-7 HTTP Message Interface standard.
 *
 * @package PivotPHP\Core\Http\Psr7
 * @since 2.1.0
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $serverParams;

    /**
     * @var array<string, string>
     */
    private array $cookieParams = [];

    /**
     * @var array<string, mixed>
     */
    private array $queryParams = [];

    /**
     * @var array<string, UploadedFileInterface>
     */
    private array $uploadedFiles = [];

    /**
     * @var mixed
     */
    private $parsedBody;

    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    public function __construct(
        string $method,
        UriInterface|string $uri,
        ?StreamInterface $body = null,
        array $headers = [],
        string $version = '1.1',
        array $serverParams = []
    ) {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        $body = $body ?? Stream::createFromString('');

        parent::__construct($method, $uri, $body, $headers, $version);
        $this->serverParams = $serverParams;
    }

    /**
     * Retrieve server parameters.

     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * Retrieve cookies.
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * Return an instance with the specified cookies.
     */
    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;
        return $clone;
    }

    /**
     * Retrieve query string arguments.
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Return an instance with the specified query string arguments.
     */
    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->queryParams = $query;
        return $clone;
    }

    /**
     * Retrieve normalized file upload data.
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;
        return $clone;
    }

    /**
     * Retrieve any parameters provided in the request body.
     */
    public function getParsedBody()
    {
        if (!is_array($this->parsedBody) && !is_object($this->parsedBody) && $this->parsedBody !== null) {
            return null;
        }
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified body parameters.
     */
    public function withParsedBody($data)
    {
        $clone = clone $this;
        $clone->parsedBody = $data;
        return $clone;
    }

    /**
     * Retrieve attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     */
    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     */
    public function withoutAttribute($name)
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }

    /**
     * Create a ServerRequest from PHP globals
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = self::createUriFromGlobals();
        $headers = self::getHeadersFromGlobals();
        $body = Stream::createFromString(file_get_contents('php://input') ?: '');
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? '1.1';
        $protocol = substr($protocol, 5); // Remove "HTTP/"

        $request = new self($method, $uri, $body, $headers, $protocol, $_SERVER);

        return $request
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    /**
     * Create URI from global variables
     */
    private static function createUriFromGlobals(): Uri
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $port = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null;
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $query = $_SERVER['QUERY_STRING'] ?? '';

        $uri = new Uri();
        $uri = $uri->withScheme($scheme)
                   ->withHost($host)
                   ->withPath($path);

        if ($query !== '') {
            $uri = $uri->withQuery($query);
        }

        if (
            $port !== null &&
            !(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))
        ) {
            $uri = $uri->withPort($port);
        }

        return $uri;
    }

    /**
     * Get headers from global variables
     */
    private static function getHeadersFromGlobals(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = substr($key, 5);
                $name = str_replace('_', '-', $name);
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = [$value];
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', $key);
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = [$value];
            }
        }

        return $headers;
    }

    /**
     * Normalize uploaded files
     */
    private static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $normalized[$key] = self::normalizeNestedFiles($file);
            } else {
                $normalized[$key] = self::createUploadedFile($file);
            }
        }

        return $normalized;
    }

    /**
     * Normalize nested uploaded files
     */
    private static function normalizeNestedFiles(array $file): array
    {
        $normalized = [];

        foreach (array_keys($file['name']) as $key) {
            $normalized[$key] = self::createUploadedFile(
                [
                    'name' => $file['name'][$key],
                    'type' => $file['type'][$key],
                    'tmp_name' => $file['tmp_name'][$key],
                    'error' => $file['error'][$key],
                    'size' => $file['size'][$key],
                ]
            );
        }

        return $normalized;
    }

    /**
     * Create UploadedFile from file array
     */
    private static function createUploadedFile(array $file): UploadedFile
    {
        if (!isset($file['tmp_name']) || !is_string($file['tmp_name'])) {
            throw new \InvalidArgumentException('Invalid file specification');
        }

        $stream = Stream::createFromFile($file['tmp_name']);

        return new UploadedFile(
            $stream,
            $file['size'] ?? null,
            $file['error'] ?? \UPLOAD_ERR_OK,
            $file['name'] ?? null,
            $file['type'] ?? null
        );
    }
}
