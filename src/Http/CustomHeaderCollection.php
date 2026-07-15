<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http;

/**
 * HeaderRequest implementation for requests with custom headers.
 *
 * Used by Request::setHeaders() to allow overriding specific headers
 * while falling back to the current environment headers.
 */
class CustomHeaderCollection extends HeaderRequest
{
    /** @var array<string, string> */
    private array $customHeaders;

    /** @var array<string, mixed> */
    protected array $headers;

    /**
     * @param array<string, string> $customHeaders
     */
    public function __construct(array $customHeaders = [])
    {
        $this->customHeaders = $customHeaders;
        $this->headers = [];

        // Process custom headers first
        foreach ($customHeaders as $key => $value) {
            $key = trim($key, ':');
            $key = self::headerToCamel($key);
            $this->headers[$key] = $value;
        }

        // Fall back to environment headers not already overridden
        $existingHeaders = function_exists('getallheaders') ? getallheaders() : [];
        if (empty($existingHeaders)) {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headerName = str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                    );
                    $key = self::headerToCamel($headerName);

                    if (!isset($this->headers[$key])) {
                        $this->headers[$key] = $value;
                    }
                }
            }
        }
    }

    /**
     * Override getHeader to handle custom headers properly.
     */
    public function getHeader($name): ?string
    {
        if (isset($this->customHeaders[$name])) {
            return (string) $this->customHeaders[$name];
        }

        $key = self::headerToCamel(trim($name, ':'));
        $value = $this->headers[$key] ?? null;
        return $value !== null && (is_string($value) || is_numeric($value)) ? (string) $value : null;
    }

    /**
     * Override hasHeader to check both formats.
     */
    public function hasHeader($name): bool
    {
        if (isset($this->customHeaders[$name])) {
            return true;
        }

        $key = self::headerToCamel(trim($name, ':'));
        return isset($this->headers[$key]);
    }

}
