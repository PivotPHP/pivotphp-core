<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use PivotPHP\Core\Http\Psr7\Factory\StreamFactory;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

/**
 * Load Shedder Middleware
 *
 * Provides basic request limiting for the microframework.
 * Simple and effective protection against overload.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 *
 * @deprecated v2.1.0 Use \PivotPHP\Core\Middleware\RateLimiter instead.
 */
class LoadShedder
{
    /**
     * Strategy constants for compatibility
     */
    public const STRATEGY_PRIORITY = 'priority';
    public const STRATEGY_CONSERVATIVE = 'conservative';
    public const STRATEGY_AGGRESSIVE = 'aggressive';
    public const STRATEGY_ADAPTIVE = 'adaptive';

    /**
     * Simple configuration
     */
    private int $maxRequests;
    private int $windowSeconds;
    private array $requestCounts = [];
    private bool $enabled = true;

    /**
     * Constructor
     */
    public function __construct(int $maxRequests = 100, int $windowSeconds = 60)
    {
        trigger_error('LoadShedder is deprecated. Use RateLimiter instead.', E_USER_DEPRECATED);
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Enable load shedding
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable load shedding
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if request should be shed
     */
    private function shouldShed(Request $request): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $currentTime = time();
        $windowStart = $currentTime - $this->windowSeconds;

        // Clean old entries
        $this->requestCounts = array_filter(
            $this->requestCounts,
            fn($timestamp) => $timestamp > $windowStart
        );

        // Count requests from this client
        $clientKey = $clientIp . ':' . $currentTime;
        $clientRequests = array_filter(
            $this->requestCounts,
            fn($timestamp, $key) => strpos($key, $clientIp . ':') === 0,
            ARRAY_FILTER_USE_BOTH
        );

        // Check if over limit
        if (count($clientRequests) >= $this->maxRequests) {
            return true;
        }

        // Record this request
        $this->requestCounts[$clientKey] = $currentTime;

        return false;
    }

    /**
     * Middleware handler
     */
    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        return $this->handle($request, $response, $next);
    }

    /**
     * Handle method for PSR-15 compatibility
     */
    public function handle(Request $request, Response $response, callable $next): Response
    {
        trigger_error('LoadShedder is deprecated. Use RateLimiter instead.', E_USER_DEPRECATED);
        if ($this->shouldShed($request)) {
            $json = json_encode(
                [
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $this->windowSeconds,
                ]
            ) ?: '{"error":"Too Many Requests"}';
            return $response
                ->withStatus(429, 'Too Many Requests')
                ->withHeader('Content-Type', 'application/json')
                ->withBody((new StreamFactory())->createStream($json));
        }

        return $next($request, $response);
    }

    /**
     * Get simple statistics
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->enabled,
            'max_requests' => $this->maxRequests,
            'window_seconds' => $this->windowSeconds,
            'active_clients' => count(
                array_unique(
                    array_map(
                        fn($key) => explode(':', $key)[0],
                        array_keys($this->requestCounts)
                    )
                )
            ),
            'total_requests_tracked' => count($this->requestCounts),
        ];
    }
}
