<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Adapters;

use PivotPHP\Core\Contracts\Psr7\StreamPoolInterface;
use PivotPHP\Core\Http\Psr7\Pool\EnhancedStreamPool;
use Psr\Http\Message\StreamInterface;

/**
 * Adapter para EnhancedStreamPool
 *
 * Implementa StreamPoolInterface delegando para EnhancedStreamPool.
 */
class StreamPoolAdapter implements StreamPoolInterface
{
    /**
     * {@inheritDoc}
     */
    public function getStream(int $expectedSize = 0): StreamInterface
    {
        return EnhancedStreamPool::getStream($expectedSize);
    }

    /**
     * {@inheritDoc}
     */
    public function releaseStream(StreamInterface $stream): void
    {
        if ($stream instanceof \PivotPHP\Core\Http\Psr7\Stream) {
            EnhancedStreamPool::releaseStream($stream);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(): void
    {
        EnhancedStreamPool::warmUp();
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        return EnhancedStreamPool::getStats();
    }
}
