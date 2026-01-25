<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Facades;

use PivotPHP\Core\Contracts\Psr7PoolInterface;
use PivotPHP\Core\Http\Adapters\Psr7PoolAdapter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Façade para Psr7Pool
 *
 * Fornece acesso global ao pool interface,
 * delegando chamadas ao pool configurado ou ao adapter padrão.
 */
class HttpPoolFacade
{
    /**
     * @var Psr7PoolInterface|null
     */
    private static ?Psr7PoolInterface $pool = null;

    /**
     * Configurar pool interface
     *
     * @param Psr7PoolInterface $pool
     * @return void
     */
    public static function setPool(Psr7PoolInterface $pool): void
    {
        self::$pool = $pool;
    }

    /**
     * Obter pool interface
     *
     * @return Psr7PoolInterface
     */
    public static function getPool(): Psr7PoolInterface
    {
        if (self::$pool === null) {
            self::$pool = new Psr7PoolAdapter();
        }
        return self::$pool;
    }

    /**
     * Obter ServerRequest do pool
     *
     * @param string $method
     * @param UriInterface $uri
     * @param StreamInterface $body
     * @param array $headers
     * @param string $protocol
     * @param array|null $cookies
     * @return ServerRequestInterface
     */
    public static function getServerRequest(
        string $method,
        UriInterface $uri,
        StreamInterface $body,
        array $headers = [],
        string $protocol = '1.1',
        ?array $cookies = null
    ): ServerRequestInterface {
        return self::getPool()->getServerRequest($method, $uri, $body, $headers, $protocol, $cookies);
    }

    /**
     * Obter Response do pool
     *
     * @param int $statusCode
     * @param array $headers
     * @param StreamInterface|null $body
     * @return ResponseInterface
     */
    public static function getResponse(
        int $statusCode = 200,
        array $headers = [],
        ?StreamInterface $body = null
    ): ResponseInterface {
        return self::getPool()->getResponse($statusCode, $headers, $body);
    }

    /**
     * Obter Uri do pool
     *
     * @param string $uri
     * @return UriInterface
     */
    public static function getUri(string $uri = ''): UriInterface
    {
        return self::getPool()->getUri($uri);
    }

    /**
     * Obter Stream do pool
     *
     * @param string $content
     * @return StreamInterface
     */
    public static function getStream(string $content = ''): StreamInterface
    {
        return self::getPool()->getStream($content);
    }

    /**
     * Retornar Stream ao pool
     */
    public static function returnStream(StreamInterface $stream): void
    {
        self::getPool()->returnStream($stream);
    }

    /**
     * Retornar Uri ao pool
     */
    public static function returnUri(UriInterface $uri): void
    {
        self::getPool()->returnUri($uri);
    }

    /**
     * Retornar ServerRequest ao pool
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    public static function returnServerRequest(ServerRequestInterface $request): void
    {
        self::getPool()->returnServerRequest($request);
    }

    /**
     * Retornar Response ao pool
     *
     * @param ResponseInterface $response
     * @return void
     */
    public static function returnResponse(ResponseInterface $response): void
    {
        self::getPool()->returnResponse($response);
    }

    /**
     * Aquecer pool
     *
     * @return void
     */
    public static function warmUp(): void
    {
        self::getPool()->warmUp();
    }

    /**
     * Limpar pools
     *
     * @return void
     */
    public static function clearPools(): void
    {
        self::getPool()->clearPools();
    }

    /**
     * Obter estatísticas
     *
     * @return array
     */
    public static function getStats(): array
    {
        return self::getPool()->getStats();
    }
}
