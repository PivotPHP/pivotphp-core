<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Adapters;

use Psr\Http\Message\ServerRequestInterface;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Uri;
use PivotPHP\Core\Http\Psr7\Stream;
use PivotPHP\Core\Http\Psr7\UploadedFile;

/**
 * Adapter to convert PHP globals to PSR-7 ServerRequest
 */
class GlobalsToServerRequestAdapter
{
    /**
     * Create a ServerRequest from PHP global variables
     */
    public static function fromGlobals(
        ?array $server = null,
        ?array $query = null,
        ?array $body = null,
        ?array $cookies = null,
        ?array $files = null
    ): ServerRequestInterface {
        $server = $server ?? $_SERVER;
        $query = $query ?? $_GET;
        $body = $body ?? $_POST;
        $cookies = $cookies ?? $_COOKIE;
        $files = $files ?? $_FILES;

        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $uri = self::createUriFromServer($server);
        $headers = self::extractHeadersFromServer($server);
        $parsedBody = $body ?: null;
        $uploadedFiles = self::normalizeUploadedFiles($files);

        $bodyStream = Stream::createFromString(file_get_contents('php://input') ?: '');

        $request = new ServerRequest(
            $method,
            $uri,
            $bodyStream,
            $headers,
            $server['SERVER_PROTOCOL'] ?? '1.1',
            $server
        );

        return $request
            ->withQueryParams($query)
            ->withParsedBody($parsedBody)
            ->withCookieParams($cookies)
            ->withUploadedFiles($uploadedFiles);
    }

    /**
     * Create URI from server variables
     */
    private static function createUriFromServer(array $server): Uri
    {
        $scheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? 'localhost';
        $port = isset($server['SERVER_PORT']) ? (int) $server['SERVER_PORT'] : null;
        $path = parse_url($server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $query = $server['QUERY_STRING'] ?? '';

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
     * Extract headers from server variables
     */
    private static function extractHeadersFromServer(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
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
     * Normalize uploaded files array to PSR-7 format
     */
    private static function normalizeUploadedFiles(array $files): array
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
        $tmpName = $file['tmp_name'] ?? '';

        if (!file_exists($tmpName) || !is_readable($tmpName)) {
            $stream = Stream::createFromString('');
        } else {
            $stream = Stream::createFromFile($tmpName);
        }

        return new UploadedFile(
            $stream,
            $file['size'],
            $file['error'],
            $file['name'],
            $file['type']
        );
    }
}
