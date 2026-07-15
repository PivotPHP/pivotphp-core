<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

/**
 * Rate limiting middleware for v1.1.0
 */
class RateLimiter
{
    /**
     * Rate limiting strategies
     */
    public const STRATEGY_FIXED_WINDOW = 'fixed_window';
    public const STRATEGY_SLIDING_WINDOW = 'sliding_window';
    public const STRATEGY_TOKEN_BUCKET = 'token_bucket';
    public const STRATEGY_LEAKY_BUCKET = 'leaky_bucket';

    /**
     * Configuration
     */
    private array $config = [
        'strategy' => self::STRATEGY_SLIDING_WINDOW,
        'max_requests' => 100,
        'window_size' => 60, // seconds
        'burst_size' => 10, // additional requests allowed in burst
        'key_generator' => null, // callable to generate rate limit key
        'storage' => 'memory', // memory, redis, apcu
        'reject_response' => [
            'status' => 429,
            'body' => ['error' => 'Too Many Requests'],
            'headers' => ['Retry-After' => '60'],
        ],
        'whitelist' => [], // IPs or keys to bypass rate limiting
        'blacklist' => [], // IPs or keys to always reject
    ];

    /**
     * Storage for rate limit data
     */
    private array $storage = [];

    /**
     * Metrics
     */
    private array $metrics = [
        'total_requests' => 0,
        'allowed_requests' => 0,
        'rejected_requests' => 0,
        'whitelisted_requests' => 0,
        'blacklisted_requests' => 0,
    ];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        // Set default key generator if not provided
        if (!$this->config['key_generator']) {
            $this->config['key_generator'] = function (Request $request) {
                return $request->ip();
            };
        }
    }

    /**
     * Handle the request
     */
    public function handle(
        Request $request,
        Response $response,
        callable $next
    ): Response {
        $this->metrics['total_requests']++;

        // Generate rate limit key
        $key = $this->generateKey($request);

        // Check whitelist
        if ($this->isWhitelisted($key)) {
            $this->metrics['whitelisted_requests']++;
            return $next($request, $response);
        }

        // Check blacklist
        if ($this->isBlacklisted($key)) {
            $this->metrics['blacklisted_requests']++;
            return $this->rejectRequest($response, 'blacklisted');
        }

        // Apply rate limiting
        $allowed = match ($this->config['strategy']) {
            self::STRATEGY_FIXED_WINDOW => $this->checkFixedWindow($key),
            self::STRATEGY_SLIDING_WINDOW => $this->checkSlidingWindow($key),
            self::STRATEGY_TOKEN_BUCKET => $this->checkTokenBucket($key),
            self::STRATEGY_LEAKY_BUCKET => $this->checkLeakyBucket($key),
            default => true,
        };

        if (!$allowed) {
            $this->metrics['rejected_requests']++;
            return $this->rejectRequest($response);
        }

        $this->metrics['allowed_requests']++;

        // Add rate limit headers
        $response = $this->addRateLimitHeaders($response, $key);

        return $next($request, $response);
    }

    /**
     * Generate rate limit key
     */
    private function generateKey(Request $request): string
    {
        $generator = $this->config['key_generator'];
        return (string) $generator($request);
    }

    /**
     * Check if key is whitelisted
     */
    private function isWhitelisted(string $key): bool
    {
        return in_array($key, $this->config['whitelist'], true);
    }

    /**
     * Check if key is blacklisted
     */
    private function isBlacklisted(string $key): bool
    {
        return in_array($key, $this->config['blacklist'], true);
    }

    /**
     * Fixed window rate limiting
     */
    private function checkFixedWindow(string $key): bool
    {
        $now = time();
        $window = (int) floor($now / $this->config['window_size']);
        $storageKey = "fixed_window:{$key}:{$window}";

        $count = $this->getFromStorage($storageKey, 0);

        if ($count >= $this->config['max_requests']) {
            return false;
        }

        $this->setInStorage($storageKey, $count + 1, $this->config['window_size']);
        return true;
    }

    /**
     * Sliding window rate limiting
     */
    private function checkSlidingWindow(string $key): bool
    {
        $now = microtime(true);
        $windowStart = $now - $this->config['window_size'];
        $storageKey = "sliding_window:{$key}";

        // Get request history
        $history = $this->getFromStorage($storageKey, []);

        // Remove old entries
        if (is_array($history)) {
            $history = array_filter($history, fn($timestamp) => $timestamp > $windowStart);
        } else {
            $history = [];
        }

        // Check if limit exceeded
        if (count($history) >= $this->config['max_requests']) {
            return false;
        }

        // Add current request
        $history[] = $now;
        $this->setInStorage($storageKey, $history, $this->config['window_size']);

        return true;
    }

    /**
     * Token bucket rate limiting
     */
    private function checkTokenBucket(string $key): bool
    {
        $now = microtime(true);
        $storageKey = "token_bucket:{$key}";

        $bucket = $this->getFromStorage(
            $storageKey,
            [
                'tokens' => $this->config['max_requests'],
                'last_refill' => $now,
            ]
        );

        // Ensure bucket is array
        if (!is_array($bucket)) {
            $bucket = [
                'tokens' => $this->config['max_requests'],
                'last_refill' => $now,
            ];
        }

        // Refill tokens
        $lastRefill = is_numeric($bucket['last_refill']) ? (float) $bucket['last_refill'] : $now;
        $tokens = is_numeric($bucket['tokens']) ? (float) $bucket['tokens'] : 0;

        $elapsed = $now - $lastRefill;
        $refillRate = $this->config['max_requests'] / $this->config['window_size'];
        $newTokens = $elapsed * $refillRate;

        $bucket['tokens'] = min(
            $this->config['max_requests'] + $this->config['burst_size'],
            $tokens + $newTokens
        );
        $bucket['last_refill'] = $now;

        // Check if token available
        if ((float) $bucket['tokens'] < 1) {
            $this->setInStorage($storageKey, $bucket);
            return false;
        }

        // Consume token
        $bucket['tokens'] = (float) $bucket['tokens'] - 1;
        $this->setInStorage($storageKey, $bucket);

        return true;
    }

    /**
     * Leaky bucket rate limiting
     */
    private function checkLeakyBucket(string $key): bool
    {
        $now = microtime(true);
        $storageKey = "leaky_bucket:{$key}";

        $bucket = $this->getFromStorage(
            $storageKey,
            [
                'volume' => 0,
                'last_leak' => $now,
            ]
        );

        // Ensure bucket is array
        if (!is_array($bucket)) {
            $bucket = [
                'volume' => 0,
                'last_leak' => $now,
            ];
        }

        // Leak water from bucket
        $elapsed = $now - (is_numeric($bucket['last_leak']) ? (float) $bucket['last_leak'] : $now);
        $leakRate = $this->config['max_requests'] / $this->config['window_size'];
        $leaked = $elapsed * $leakRate;

        $volume = is_numeric($bucket['volume']) ? (float) $bucket['volume'] : 0;
        $bucket['volume'] = max(0, $volume - $leaked);
        $bucket['last_leak'] = $now;

        // Check if bucket can accept more
        if ($bucket['volume'] >= $this->config['max_requests']) {
            $this->setInStorage($storageKey, $bucket);
            return false;
        }

        // Add water to bucket
        $bucket['volume']++;
        $this->setInStorage($storageKey, $bucket);

        return true;
    }

    /**
     * Get from storage
     */
    private function getFromStorage(string $key, mixed $default = null): mixed
    {
        // In-memory storage for now
        return $this->storage[$key] ?? $default;
    }

    /**
     * Set in storage
     */
    private function setInStorage(
        string $key,
        mixed $value,
        ?int $ttl = null
    ): void {
        // In-memory storage for now
        $this->storage[$key] = $value;

        // TODO: Implement TTL cleanup
    }

    /**
     * Reject request
     */
    private function rejectRequest(Response $response, string $reason = 'rate_limit'): Response
    {
        $config = $this->config['reject_response'];

        $response = $response
            ->status($config['status'])
            ->json($config['body'])
            ->header('X-RateLimit-Reason', $reason);

        foreach ($config['headers'] as $name => $value) {
            $response->header($name, (string) $value);
        }

        return $response;
    }

    /**
     * Add rate limit headers
     */
    private function addRateLimitHeaders(Response $response, string $key): Response
    {
        $limit = $this->config['max_requests'];
        $remaining = $this->getRemainingRequests($key);
        $reset = $this->getResetTime();

        return $response
            ->header('X-RateLimit-Limit', (string) $limit)
            ->header('X-RateLimit-Remaining', (string) $remaining)
            ->header('X-RateLimit-Reset', (string) $reset);
    }

    /**
     * Get remaining requests for key
     */
    private function getRemainingRequests(string $key): int
    {
        return match ($this->config['strategy']) {
            self::STRATEGY_FIXED_WINDOW => $this->getRemainingFixedWindow($key),
            self::STRATEGY_SLIDING_WINDOW => $this->getRemainingSlidingWindow($key),
            self::STRATEGY_TOKEN_BUCKET => $this->getRemainingTokenBucket($key),
            self::STRATEGY_LEAKY_BUCKET => $this->getRemainingLeakyBucket($key),
            default => 0,
        };
    }

    /**
     * Get remaining requests for fixed window
     */
    private function getRemainingFixedWindow(string $key): int
    {
        $now = time();
        $window = (int) floor($now / $this->config['window_size']);
        $storageKey = "fixed_window:{$key}:{$window}";

        $count = $this->getFromStorage($storageKey, 0);
        $count = is_numeric($count) ? (int) $count : 0;
        return (int) max(0, $this->config['max_requests'] - $count);
    }

    /**
     * Get remaining requests for sliding window
     */
    private function getRemainingSlidingWindow(string $key): int
    {
        $now = microtime(true);
        $windowStart = $now - $this->config['window_size'];
        $storageKey = "sliding_window:{$key}";

        $history = $this->getFromStorage($storageKey, []);
        if (is_array($history)) {
            $history = array_filter($history, fn($timestamp) => $timestamp > $windowStart);
            return (int) max(0, $this->config['max_requests'] - count($history));
        }
        return (int) $this->config['max_requests'];
    }

    /**
     * Get remaining requests for token bucket
     */
    private function getRemainingTokenBucket(string $key): int
    {
        $storageKey = "token_bucket:{$key}";
        $bucket = $this->getFromStorage($storageKey, ['tokens' => $this->config['max_requests']]);

        if (is_array($bucket) && isset($bucket['tokens'])) {
            $tokens = is_numeric($bucket['tokens']) ? (float) $bucket['tokens'] : 0;
            return max(0, (int) floor($tokens));
        }
        return $this->config['max_requests'];
    }

    /**
     * Get remaining requests for leaky bucket
     */
    private function getRemainingLeakyBucket(string $key): int
    {
        $storageKey = "leaky_bucket:{$key}";
        $bucket = $this->getFromStorage($storageKey, ['volume' => 0]);

        if (is_array($bucket) && isset($bucket['volume'])) {
            $volume = is_numeric($bucket['volume']) ? (float) $bucket['volume'] : 0;
            return (int) max(0, $this->config['max_requests'] - (int) ceil($volume));
        }
        return (int) $this->config['max_requests'];
    }

    /**
     * Get reset time
     */
    private function getResetTime(): int
    {
        return match ($this->config['strategy']) {
            self::STRATEGY_FIXED_WINDOW => $this->getFixedWindowReset(),
            default => time() + $this->config['window_size'],
        };
    }

    /**
     * Get fixed window reset time
     */
    private function getFixedWindowReset(): int
    {
        $now = time();
        $window = (int) floor($now / $this->config['window_size']);
        return ($window + 1) * $this->config['window_size'];
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        $allowRate = $this->metrics['total_requests'] > 0
            ? $this->metrics['allowed_requests'] / $this->metrics['total_requests']
            : 0.0;

        return array_merge(
            $this->metrics,
            [
                'allow_rate' => round($allowRate * 100, 2),
                'reject_rate' => round((1 - $allowRate) * 100, 2),
            ]
        );
    }

    /**
     * Reset rate limit for key
     */
    public function reset(string $key): void
    {
        $keysToRemove = [];

        // Find all keys that match the patterns
        foreach ($this->storage as $storageKey => $value) {
            if (
                str_contains($storageKey, "fixed_window:{$key}:") ||
                $storageKey === "sliding_window:{$key}" ||
                $storageKey === "token_bucket:{$key}" ||
                $storageKey === "leaky_bucket:{$key}"
            ) {
                $keysToRemove[] = $storageKey;
            }
        }

        // Remove matching keys
        foreach ($keysToRemove as $storageKey) {
            unset($this->storage[$storageKey]);
        }
    }

    /**
     * Update configuration
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
}
