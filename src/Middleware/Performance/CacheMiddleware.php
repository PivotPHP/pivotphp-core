<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Performance;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Cache Middleware
 *
 * Provides HTTP response caching to improve application performance
 * by storing and serving cached responses for repeated requests.
 *
 * @package PivotPHP\Core\Middleware\Performance
 * @since 1.1.2
 */
class CacheMiddleware implements MiddlewareInterface
{
    private int $ttl;
    private string $cacheDir;

    /**
     * __construct method
     */
    public function __construct(int $ttl = 300, string $cacheDir = '/tmp/expressphp_cache')
    {
        $this->ttl = $ttl;
        $this->cacheDir = $cacheDir;
        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0777, true) && !is_dir($this->cacheDir)) {
                throw new RuntimeException("Cannot create cache directory: {$this->cacheDir}");
            }
        }
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $this->generateCacheKey($request);
        $cacheFile = $this->cacheDir . '/' . $key;

        // Try to read from cache
        try {
            if (file_exists($cacheFile) && (filemtime($cacheFile) + $this->ttl) > time()) {
                $cached = @file_get_contents($cacheFile);
                if ($cached !== false) {
                    try {
                        $response = unserialize($cached);
                        if ($response instanceof ResponseInterface) {
                            return $response;
                        }
                    } catch (Throwable $e) {
                        // Delete corrupted cache file
                        @unlink($cacheFile);
                        error_log('Cache file corrupted, deleted: ' . $cacheFile);
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('Cache read error: ' . $e->getMessage());
        }

        $response = $handler->handle($request);

        // Adiciona header de cache-control
        if (method_exists($response, 'withHeader')) {
            $response = $response->withHeader('Cache-Control', 'public, max-age=' . $this->ttl);
        }

        // Try to write to cache
        try {
            $written = @file_put_contents($cacheFile, serialize($response), LOCK_EX);
            if ($written === false) {
                error_log('Failed to write cache file: ' . $cacheFile);
            }
        } catch (Throwable $e) {
            error_log('Cache write error: ' . $e->getMessage());
        }

        return $response;
    }

    private function generateCacheKey(ServerRequestInterface $request): string
    {
        $uri = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery();
        $method = $request->getMethod();
        return md5($method . ':' . $uri . '?' . $query);
    }
}
