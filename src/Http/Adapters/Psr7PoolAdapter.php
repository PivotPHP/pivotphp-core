<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Adapters;

use PivotPHP\Core\Contracts\Psr7PoolInterface;
use PivotPHP\Core\Http\Pool\Psr7Pool;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Adapter para Psr7Pool
 *
 * Implementa Psr7PoolInterface delegando para Psr7Pool
 * mantendo compatibilidade com object pooling.
 */
class Psr7PoolAdapter implements Psr7PoolInterface
{
    /**
     * {@inheritDoc}
     */
    public function getServerRequest(
        string $method,
        UriInterface $uri,
        StreamInterface $body,
        array $headers = [],
        string $protocol = '1.1',
        ?array $cookies = null
    ): ServerRequestInterface {
        return Psr7Pool::getServerRequest($method, $uri, $body, $headers, $protocol, $cookies ?? []);
    }

    /**
     * {@inheritDoc}
     */
    public function getResponse(
        int $statusCode = 200,
        array $headers = [],
        ?StreamInterface $body = null
    ): ResponseInterface {
        return Psr7Pool::getResponse($statusCode, $headers, $body);
    }

    /**
     * {@inheritDoc}
     */
    public function getUri(string $uri = ''): UriInterface
    {
        return Psr7Pool::getUri($uri);
    }

    /**
     * {@inheritDoc}
     */
    public function getStream(string $content = ''): StreamInterface
    {
        return Psr7Pool::getStream($content);
    }

    public function returnStream(StreamInterface $stream): void
    {
        Psr7Pool::returnStream($stream);
    }

    public function returnUri(UriInterface $uri): void
    {
        Psr7Pool::returnUri($uri);
    }

    /**
     * {@inheritDoc}
     */
    public function returnServerRequest(ServerRequestInterface $request): void
    {
        Psr7Pool::returnServerRequest($request);
    }

    /**
     * {@inheritDoc}
     */
    public function returnResponse(ResponseInterface $response): void
    {
        Psr7Pool::returnResponse($response);
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(): void
    {
        Psr7Pool::warmUp();
    }

    /**
     * {@inheritDoc}
     */
    public function clearPools(): void
    {
        Psr7Pool::clearPools();
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        return Psr7Pool::getStats();
    }
}
