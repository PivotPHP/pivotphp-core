<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Adapters;

use PivotPHP\Core\Contracts\Psr7\ResponsePoolInterface;
use PivotPHP\Core\Http\Psr7\Pool\ResponsePool;
use Psr\Http\Message\ResponseInterface;

/**
 * Adapter para ResponsePool
 *
 * Implementa ResponsePoolInterface delegando para ResponsePool.
 */
class ResponsePoolAdapter implements ResponsePoolInterface
{
    /**
     * {@inheritDoc}
     */
    public function getResponse(int $status = 200): ResponseInterface
    {
        return ResponsePool::getResponse($status);
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonResponse(mixed $data, int $code = 200): ResponseInterface
    {
        /** @var array<mixed> $arrayData */
        $arrayData = is_array($data) ? $data : [];
        return ResponsePool::getJsonResponse($arrayData, $code);
    }

    /**
     * {@inheritDoc}
     */
    public function getTextResponse(string $text, int $code = 200): ResponseInterface
    {
        return ResponsePool::getTextResponse($text, $code);
    }

    /**
     * {@inheritDoc}
     */
    public function getHtmlResponse(string $html, int $code = 200): ResponseInterface
    {
        return ResponsePool::getHtmlResponse($html, $code);
    }

    /**
     * {@inheritDoc}
     */
    public function releaseResponse(ResponseInterface $response): void
    {
        if ($response instanceof \PivotPHP\Core\Http\Psr7\Response) {
            ResponsePool::releaseResponse($response);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(): void
    {
        ResponsePool::warmUp();
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        return ResponsePool::getStats();
    }

    /**
     * {@inheritDoc}
     */
    public function clearAll(): void
    {
        ResponsePool::clearAll();
    }

    /**
     * {@inheritDoc}
     */
    public function garbageCollect(): int
    {
        return ResponsePool::garbageCollect();
    }
}
