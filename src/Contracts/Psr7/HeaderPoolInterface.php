<?php

declare(strict_types=1);

namespace PivotPHP\Core\Contracts\Psr7;

/**
 * Interface para pool de headers
 *
 * Abstrai pooling e normalização de headers.
 */
interface HeaderPoolInterface
{
    /**
     * Obter nome normalizado de header
     *
     * @param string $name Nome do header
     * @return string Nome normalizado
     */
    public function getNormalizedName(string $name): string;

    /**
     * Obter valores do header
     *
     * @param string $name Nome do header
     * @param mixed $value Valor do header
     * @return array Valores processados
     */
    public function getHeaderValues(string $name, mixed $value): array;

    /**
     * Validar e obter valores do header
     *
     * @param string $name Nome do header
     * @param mixed $value Valor do header
     * @return array Valores validados
     */
    public function getValidatedHeaderValues(string $name, mixed $value): array;

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
     */
    public function clearAll(): void;
}
