<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP request implementation (PSR-7)
 *
 * This class implements the RequestInterface for handling HTTP requests
 * following the PSR-7 HTTP Message Interface standard.
 *
 * @package PivotPHP\Core\Http\Psr7
 * @since 2.1.0
 */
class Request extends Message implements RequestInterface
{
    private string $method;
    private ?string $requestTarget = null;
    private UriInterface $uri;

    /**
     * Constructor
     */
    public function __construct(
        string $method,
        UriInterface $uri,
        StreamInterface $body,
        array $headers = [],
        string $version = '1.1'
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;

        parent::__construct($body, $headers, $version);

        $this->updateHostFromUri();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }

        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        if ($requestTarget === $this->getRequestTarget()) {
            return $this;
        }

        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod(string $method): RequestInterface
    {
        if (!is_string($method) || $method === '') {
            throw new \InvalidArgumentException('Method must be a non-empty string');
        }

        $method = strtoupper($method);
        if ($method === $this->method) {
            return $this;
        }

        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $clone = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $clone->updateHostFromUri();
        }

        return $clone;
    }

    /**
     * Update Host header from URI
     */
    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();

        if ($host === '') {
            return;
        }

        if (($port = $this->uri->getPort()) !== null) {
            $host .= ':' . $port;
        }

        if ($this->hasHeader('Host')) {
            $header = $this->getHeaderLine('Host');
            if ($header !== $host) {
                $this->headers = $this->withHeader('Host', $host)->headers;
                $this->headerNames = $this->withHeader('Host', $host)->headerNames;
            }
        } else {
            $this->headers = $this->withHeader('Host', $host)->headers;
            $this->headerNames = $this->withHeader('Host', $host)->headerNames;
        }
    }
}
