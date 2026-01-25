<?php

declare(strict_types=1);

namespace PivotPHP\Core\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use PivotPHP\Core\Http\Response;

/**
 * Interface para compilador de middleware pipeline
 *
 * Abstrai compilação otimizada de pipelines de middleware.
 */
interface MiddlewarePipelineCompilerInterface
{
    /**
     * Compilar pipeline de middlewares em callable
     *
     * @param string $cacheKey Chave do cache
     * @param array $middlewares Array de middlewares
     * @return callable Pipeline compilado
     */
    public function compilePipeline(string $cacheKey, array $middlewares): callable;

    /**
     * Obter pipeline compilado do cache
     *
     * @param string $cacheKey Chave do cache
     * @return callable|null Pipeline compilado ou null se não existe
     */
    public function getCompiledPipeline(string $cacheKey): ?callable;

    /**
     * Aquecer cache com padrões comuns
     *
     * @return void
     */
    public function warmUp(): void;

    /**
     * Limpar cache compilado
     *
     * @return void
     */
    public function clearCache(): void;

    /**
     * Obter estatísticas
     *
     * @return array
     */
    public function getStats(): array;
}
