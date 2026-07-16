<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7;

use Psr\Http\Message\UriInterface;

/**
 * URI implementation (PSR-7)
 *
 * This class implements the UriInterface for handling URIs
 * following the PSR-7 HTTP Message Interface standard.
 *
 * @package PivotPHP\Core\Http\Psr7
 * @since 2.1.0
 */
class Uri implements UriInterface
{
    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';

    /**
     * Standard ports for schemes
     */
    private const STANDARD_PORTS = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'ftps' => 990,
    ];

    /**
     * Constructor
     */
    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new \InvalidArgumentException("Unable to parse URI: $uri");
            }

            $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
            $this->userInfo = $parts['user'] ?? '';
            $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
            $this->port = $parts['port'] ?? null;
            $this->path = $parts['path'] ?? '';
            $this->query = $parts['query'] ?? '';
            $this->fragment = $parts['fragment'] ?? '';

            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority(): string
    {
        $authority = $this->host;
        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme(string $scheme): UriInterface
    {
        $scheme = strtolower($scheme);
        if ($this->scheme === $scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;
        $clone->removeDefaultPort();

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $info = $user;
        if ($password !== null && $password !== '') {
            $info .= ':' . $password;
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $clone = clone $this;
        $clone->userInfo = $info;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost(string $host): UriInterface
    {
        $host = strtolower($host);
        if ($this->host === $host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort(?int $port): UriInterface
    {
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new \InvalidArgumentException(
                'Port must be between 1 and 65535, inclusive'
            );
        }

        if ($this->port === $port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;
        $clone->removeDefaultPort();

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath(string $path): UriInterface
    {
        $path = $this->filterPath($path);

        if ($this->path === $path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery(string $query): UriInterface
    {
        $query = $this->filterQueryAndFragment($query);

        if ($this->query === $query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment(string $fragment): UriInterface
    {
        $fragment = $this->filterQueryAndFragment($fragment);

        if ($this->fragment === $fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        $uri .= $this->path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * Remove default port for scheme if present
     */
    private function removeDefaultPort(): void
    {
        if (
            $this->port !== null &&
            isset(self::STANDARD_PORTS[$this->scheme]) &&
            $this->port === self::STANDARD_PORTS[$this->scheme]
        ) {
            $this->port = null;
        }
    }

    /**
     * Filter path component
     */
    private function filterPath(string $path): string
    {
        $result = preg_replace_callback(
            '/(?:[^' . $this->getUnreservedChars() . ':@!$&\'()*+,;=%\/]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $path
        );
        return $result ?? '';
    }

    /**
     * Filter query and fragment components
     */
    private function filterQueryAndFragment(string $str): string
    {
        $result = preg_replace_callback(
            '/(?:[^' . $this->getUnreservedChars() . ':@!$&\'()*+,;=%\/?]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $str
        );
        return $result ?? '';
    }

    /**
     * Get unreserved characters regex pattern
     */
    private function getUnreservedChars(): string
    {
        return 'a-zA-Z0-9_\-\.~';
    }

    /**
     * URL encode callback
     */
    private function rawurlencodeMatchZero(array $match): string
    {
        return rawurlencode($match[0]);
    }
}
