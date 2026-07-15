<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Adapters;

use PivotPHP\Core\Contracts\Psr7\HeaderPoolInterface;
use PivotPHP\Core\Http\Psr7\Pool\HeaderPool;

/**
 * Adapter para HeaderPool
 *
 * Implementa HeaderPoolInterface delegando para HeaderPool.
 */
class HeaderPoolAdapter implements HeaderPoolInterface
{
    /**
     * {@inheritDoc}
     */
    public function getNormalizedName(string $name): string
    {
        return HeaderPool::getNormalizedName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderValues(string $name, mixed $value): array
    {
        if (is_array($value)) {
            /** @var array<string> $value */
            return HeaderPool::getHeaderValues($name, $value);
        }
        return HeaderPool::getHeaderValues($name, (string)$value);
    }

    /**
     * {@inheritDoc}
     */
    public function getValidatedHeaderValues(string $name, mixed $value): array
    {
        if (is_array($value)) {
            /** @var array<string> $value */
            return HeaderPool::getValidatedHeaderValues($name, $value);
        }
        return HeaderPool::getValidatedHeaderValues($name, (string)$value);
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(): void
    {
        HeaderPool::warmUp();
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        return HeaderPool::getStats();
    }

    /**
     * {@inheritDoc}
     */
    public function clearAll(): void
    {
        HeaderPool::clearAll();
    }
}
