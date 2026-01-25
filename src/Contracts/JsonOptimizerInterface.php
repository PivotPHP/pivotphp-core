<?php

declare(strict_types=1);

namespace PivotPHP\Core\Contracts;

/**
 * Interface para otimização de JSON
 *
 * Abstrai a implementação de pooling de buffers JSON,
 * permitindo que o core funcione sem performance-tools.
 */
interface JsonOptimizerInterface
{
    /**
     * Codificar dados para JSON com otimização
     *
     * @param mixed $data Dados a codificar
     * @param int $flags Flags de encoding
     * @return string JSON codificado
     */
    public function encodeJson(mixed $data, int $flags = 0): string;

    /**
     * Verificar se deve usar otimizações
     *
     * @param mixed $data Dados a verificar
     * @return bool True se deve otimizar
     */
    public function shouldOptimize(mixed $data): bool;

    /**
     * Obter estatísticas de uso
     *
     * @return array Estatísticas do optimizer
     */
    public function getStats(): array;

    /**
     * Aquecer cache de buffers
     *
     * @return void
     */
    public function warmUp(): void;

    /**
     * Limpar todos os caches
     *
     * @return void
     */
    public function clearCache(): void;
}
