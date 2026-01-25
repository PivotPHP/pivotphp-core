<?php

declare(strict_types=1);

namespace PivotPHP\Core\Contracts\Psr7;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface para pool de Response
 *
 * Abstrai pooling de objetos Response por status code.
 */
interface ResponsePoolInterface
{
    /**
     * Obter Response do pool
     *
     * @param int $status Status code
     * @return ResponseInterface
     */
    public function getResponse(int $status = 200): ResponseInterface;

    /**
     * Obter Response JSON do pool
     *
     * @param mixed $data Dados JSON
     * @param int $code Status code
     * @return ResponseInterface
     */
    public function getJsonResponse(mixed $data, int $code = 200): ResponseInterface;

    /**
     * Obter Response texto do pool
     *
     * @param string $text Texto
     * @param int $code Status code
     * @return ResponseInterface
     */
    public function getTextResponse(string $text, int $code = 200): ResponseInterface;

    /**
     * Obter Response HTML do pool
     *
     * @param string $html HTML
     * @param int $code Status code
     * @return ResponseInterface
     */
    public function getHtmlResponse(string $html, int $code = 200): ResponseInterface;

    /**
     * Retornar Response ao pool
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function releaseResponse(ResponseInterface $response): void;

    /**
     * Aquecer o pool
     *
     * @return void
     */
    public function warmUp(): void;

    /**
     * Obter estatísticas
     *
     * @return array
     */
    public function getStats(): array;

    /**
     * Limpar o pool
     *
     * @return void
     */
    public function clearAll(): void;

    /**
     * Coleta de lixo de objetos inativos
     */
    public function garbageCollect(): int;
}
