<?php

declare(strict_types=1);

namespace PivotPHP\Core\Json\Adapters;

use PivotPHP\Core\Contracts\JsonOptimizerInterface;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Adapter para JsonBufferPool
 *
 * Implementa JsonOptimizerInterface delegando para JsonBufferPool
 * mantendo compatibilidade com código que usa pooling.
 */
class JsonBufferPoolAdapter implements JsonOptimizerInterface
{
    /**
     * {@inheritDoc}
     */
    public function encodeJson(mixed $data, int $flags = 0): string
    {
        return JsonBufferPool::encodeWithPool($data, $flags);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldOptimize(mixed $data): bool
    {
        return JsonBufferPool::shouldUsePooling($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        return JsonBufferPool::getStats();
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(): void
    {
        JsonBufferPool::warmUp();
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(): void
    {
        JsonBufferPool::clearCache();
    }
}
