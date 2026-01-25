<?php

declare(strict_types=1);

namespace PivotPHP\Core\Contracts\Psr7;

/**
 * Interface para cache de operações
 *
 * Abstrai cache de regex compilados, JSON encoding, validações, etc.
 */
interface OperationsCacheInterface
{
    /**
     * Obter padrão compilado
     *
     * @param string $pattern Padrão regex
     * @return string Padrão compilado
     */
    public function getCompiledPattern(string $pattern): string;

    /**
     * Obter JSON cached
     *
     * @param mixed $data Dados a codificar
     * @return string|null JSON cached ou null
     */
    public function getCachedJson(mixed $data): ?string;

    /**
     * Obter parâmetro cached
     *
     * @param string $cacheKey Chave do cache
     * @return mixed Valor cached
     */
    public function getCachedParameter(string $cacheKey): mixed;

    /**
     * Validar nome de header
     *
     * @param string $name Nome do header
     * @return bool
     */
    public function isValidHeaderName(string $name): bool;

    /**
     * Obter tipo MIME
     *
     * @param string $extension Extensão do arquivo
     * @return string Tipo MIME
     */
    public function getMimeType(string $extension): string;

    /**
     * Aquecer o cache
     *
     * @return void
     */
    public function warmUp(): void;

    /**
     * Limpar cache
     *
     * @return void
     */
    public function clearAll(): void;

    /**
     * Obter estatísticas
     *
     * @return array
     */
    public function getStats(): array;
}
