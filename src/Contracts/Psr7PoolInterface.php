<?php

declare(strict_types=1);

namespace PivotPHP\Core\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Interface para pool de objetos PSR-7
 *
 * Abstrai a implementação de object pooling para Request/Response,
 * permitindo otimizações sem acoplamento ao core.
 */
interface Psr7PoolInterface
{
    /**
     * Obter ServerRequest do pool
     *
     * @param string $method Método HTTP
     * @param UriInterface $uri URI da requisição
     * @param StreamInterface $body Body da requisição
     * @param array $headers Headers
     * @param string $protocol Versão do protocolo
     * @param array|null $cookies Cookies
     * @return ServerRequestInterface
     */
    public function getServerRequest(
        string $method,
        UriInterface $uri,
        StreamInterface $body,
        array $headers = [],
        string $protocol = '1.1',
        ?array $cookies = null
    ): ServerRequestInterface;

    /**
     * Obter Response do pool
     *
     * @param int $statusCode Status code
     * @param array $headers Headers
     * @param StreamInterface|null $body Body
     * @return ResponseInterface
     */
    public function getResponse(
        int $statusCode = 200,
        array $headers = [],
        ?StreamInterface $body = null
    ): ResponseInterface;

    /**
     * Obter Uri do pool
     *
     * @param string $uri URI string
     * @return UriInterface
     */
    public function getUri(string $uri = ''): UriInterface;

    /**
     * Obter Stream do pool
     *
     * @param string $content Conteúdo do stream
     * @return StreamInterface
     */
    public function getStream(string $content = ''): StreamInterface;

    /**
     * Retornar Stream ao pool
     *
     * @param StreamInterface $stream
     * @return void
     */
    public function returnStream(StreamInterface $stream): void;

    /**
     * Retornar Uri ao pool
     *
     * @param UriInterface $uri
     * @return void
     */
    public function returnUri(UriInterface $uri): void;

    /**
     * Retornar ServerRequest ao pool
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    public function returnServerRequest(ServerRequestInterface $request): void;

    /**
     * Retornar Response ao pool
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function returnResponse(ResponseInterface $response): void;

    /**
     * Aquecer os pools
     *
     * @return void
     */
    public function warmUp(): void;

    /**
     * Limpar todos os pools
     *
     * @return void
     */
    public function clearPools(): void;

    /**
     * Obter estatísticas
     *
     * @return array
     */
    public function getStats(): array;
}
