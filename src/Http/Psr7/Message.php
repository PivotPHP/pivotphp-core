<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7;

use PivotPHP\Core\Contracts\Psr7\HeaderPoolInterface;
use PivotPHP\Core\Http\Psr7\Adapters\HeaderPoolAdapter;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Base HTTP message implementation (PSR-7)
 *
 * This class implements the base functionality for both HTTP requests and responses
 * following the PSR-7 HTTP Message Interface standard.
 *
 * @package PivotPHP\Core\Http\Psr7
 * @since 2.1.0
 */
class Message implements MessageInterface
{
    /**
     * Protocol version
     */
    protected string $protocolVersion = '1.1';

    /**
     * HTTP headers
     *
     * @var array<string, array<string>>
     */
    protected array $headers = [];

    /**
     * Header names (for case-insensitive lookup)
     *
     * @var array<string, string>
     */
    protected array $headerNames = [];

    /**
     * Message body
     */
    protected StreamInterface $body;

    /**
     * Header pool (injeção)
     */
    protected ?HeaderPoolInterface $headerPool = null;

    /**
     * Constructor
     */
    public function __construct(
        StreamInterface $body,
        array $headers = [],
        string $version = '1.1'
    ) {
        $this->body = $body;
        $this->protocolVersion = $version;
        $this->setHeaders($headers);
    }

    /**
     * Injetar pool de headers
     */
    public function setHeaderPool(HeaderPoolInterface $pool): void
    {
        $this->headerPool = $pool;
    }

    /**
     * Obter pool de headers (fallback para adapter)
     */
    private function getHeaderPool(): HeaderPoolInterface
    {
        return $this->headerPool ?? new HeaderPoolAdapter();
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion(string $version)
    {
        if ($this->protocolVersion === $version) {
            return $this;
        }

        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader(string $name)
    {
        return isset($this->headerNames[$this->getHeaderPool()->getNormalizedName($name)]);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name)
    {
        $normalizedName = $this->getHeaderPool()->getNormalizedName($name);

        if (!isset($this->headerNames[$normalizedName])) {
            return [];
        }

        $headerName = $this->headerNames[$normalizedName];

        return $this->headers[$headerName] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine(string $name)
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader(string $name, $value)
    {
        // Optimized version with header pooling
        $normalized = $this->getHeaderPool()->getNormalizedName($name);
        $clone = clone $this;

        // Remove existing header if present
        if (isset($clone->headerNames[$normalized])) {
            unset($clone->headers[$clone->headerNames[$normalized]]);
        }

        $clone->headerNames[$normalized] = $name;
        $clone->headers[$name] = $this->getHeaderPool()->getHeaderValues($name, $value);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader(string $name, $value)
    {
        // Optimized version with header pooling
        $normalized = $this->getHeaderPool()->getNormalizedName($name);
        $clone = clone $this;
        $valueArray = $this->getHeaderPool()->getHeaderValues($name, $value);

        if (isset($clone->headerNames[$normalized])) {
            $headerName = $clone->headerNames[$normalized];
            $clone->headers[$headerName] = array_merge($clone->headers[$headerName], $valueArray);
        } else {
            $clone->headerNames[$normalized] = $name;
            $clone->headers[$name] = $valueArray;
        }

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader(string $name)
    {
        $normalized = $this->getHeaderPool()->getNormalizedName($name);

        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $clone = clone $this;
        $headerName = $clone->headerNames[$normalized];

        unset($clone->headers[$headerName], $clone->headerNames[$normalized]);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        if ($body === $this->body) {
            return $this;
        }

        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * Set headers from array
     *
     * @param array<string, string|array<string>> $headers
     */
    protected function setHeaders(array $headers): void
    {
        $this->headers = [];
        $this->headerNames = [];

        // Optimized version with header pooling
        foreach ($headers as $name => $value) {
            $normalized = $this->getHeaderPool()->getNormalizedName($name);
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = $this->getHeaderPool()->getHeaderValues($name, $value);
        }
    }

    /**
     * Validate header name
     *
     * @throws \InvalidArgumentException
     */
    protected function validateHeaderName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid header name: {$name}");
        }
    }

    /**
     * Normalize header value
     *
     * @param string|array<string> $value
     * @return array<string>
     * @throws \InvalidArgumentException
     */
    protected function normalizeHeaderValue($value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        if (empty($value)) {
            throw new \InvalidArgumentException('Header value cannot be empty');
        }

        return array_map(
            function ($v) {
                $v = (string) $v;

                if (preg_match('/[^\x09\x0A\x0D\x20-\x7E\x80-\xFE]/', $v) > 0) {
                    throw new \InvalidArgumentException('Header value contains invalid characters');
                }

                return trim($v, " \t");
            },
            $value
        );
    }

    /**
     * Add header with strict validation
     *
     * @param string $name
     * @param string|array<string> $value
     * @return MessageInterface
     * @throws \InvalidArgumentException
     */
    public function withHeaderStrict(string $name, $value): MessageInterface
    {
        $value = $this->getHeaderPool()->getValidatedHeaderValues($name, $value);
        $normalized = $this->getHeaderPool()->getNormalizedName($name);
        $clone = clone $this;

        // Remove existing header if present
        if (isset($clone->headerNames[$normalized])) {
            unset($clone->headers[$clone->headerNames[$normalized]]);
        }

        $clone->headerNames[$normalized] = $name;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /**
     * Add header with strict validation
     *
     * @param string $name
     * @param string|array<string> $value
     * @return MessageInterface
     * @throws \InvalidArgumentException
     */
    public function withAddedHeaderStrict(string $name, $value): MessageInterface
    {
        $value = $this->getHeaderPool()->getValidatedHeaderValues($name, $value);
        $normalized = $this->getHeaderPool()->getNormalizedName($name);
        $clone = clone $this;

        if (isset($clone->headerNames[$normalized])) {
            $headerName = $clone->headerNames[$normalized];
            $clone->headers[$headerName] = array_merge($clone->headers[$headerName], $value);
        } else {
            $clone->headerNames[$normalized] = $name;
            $clone->headers[$name] = $value;
        }

        return $clone;
    }
}
