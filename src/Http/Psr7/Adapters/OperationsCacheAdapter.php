<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Adapters;

use PivotPHP\Core\Contracts\Psr7\OperationsCacheInterface;
use PivotPHP\Core\Http\Psr7\Cache\OperationsCache;

/**
 * Adapter para OperationsCache
 *
 * Implementa OperationsCacheInterface delegando para OperationsCache.
 */
class OperationsCacheAdapter implements OperationsCacheInterface
{
    /**
     * {@inheritDoc}
     */
    public function getCompiledPattern(string $pattern): string
    {
        return OperationsCache::getCompiledPattern($pattern);
    }

    /**
     * {@inheritDoc}
     */
    public function getCachedJson(mixed $data): ?string
    {
        /** @var array<mixed> $arrayData */
        $arrayData = is_array($data) ? $data : [];
        return OperationsCache::getCachedJson($arrayData);
    }

    /**
     * {@inheritDoc}
     */
    public function getCachedParameter(string $cacheKey): mixed
    {
        return OperationsCache::getCachedParameter($cacheKey);
    }

    /**
     * {@inheritDoc}
     */
    public function isValidHeaderName(string $name): bool
    {
        return OperationsCache::isValidHeaderName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getMimeType(string $extension): string
    {
        return OperationsCache::getMimeType($extension);
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(): void
    {
        OperationsCache::warmUp();
    }

    /**
     * {@inheritDoc}
     */
    public function clearAll(): void
    {
        OperationsCache::clearAll();
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        return OperationsCache::getStats();
    }
}
