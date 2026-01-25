<?php

declare(strict_types=1);

namespace PivotPHP\Core\Contracts\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * Interface para pool de streams
 *
 * Abstrai pooling de objetos Stream categorizados por tamanho.
 */
interface StreamPoolInterface
{
    /**
     * Obter Stream do pool
     *
     * @param int $expectedSize Tamanho esperado
     * @return StreamInterface
     */
    public function getStream(int $expectedSize = 0): StreamInterface;

    /**
     * Retornar Stream ao pool
     *
     * @param StreamInterface $stream
     * @return void
     */
    public function releaseStream(StreamInterface $stream): void;

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
}
