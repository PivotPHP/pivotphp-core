<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Adapters;

use PivotPHP\Core\Contracts\MiddlewarePipelineCompilerInterface;
use PivotPHP\Core\Middleware\MiddlewarePipelineCompiler;

/**
 * Adapter para MiddlewarePipelineCompiler (avançado)
 *
 * Implementa MiddlewarePipelineCompilerInterface delegando para
 * MiddlewarePipelineCompiler com otimizações avançadas.
 */
class MiddlewarePipelineCompilerAdapter implements MiddlewarePipelineCompilerInterface
{
    /**
     * {@inheritDoc}
     */
    public function compilePipeline(string $cacheKey, array $middlewares): callable
    {
        return MiddlewarePipelineCompiler::compilePipeline($middlewares, $cacheKey);
    }

    /**
     * {@inheritDoc}
     */
    public function getCompiledPipeline(string $cacheKey): ?callable
    {
        return MiddlewarePipelineCompiler::getCompiledPipeline($cacheKey);
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(): void
    {
        MiddlewarePipelineCompiler::warmUp();
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(): void
    {
        MiddlewarePipelineCompiler::clearCache();
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        return MiddlewarePipelineCompiler::getStats();
    }
}
