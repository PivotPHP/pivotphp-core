<?php

declare(strict_types=1);

namespace PivotPHP\Core\Performance;

/**
 * Performance Monitor
 *
 * Simple and effective performance monitoring for the microframework.
 * Provides basic metrics without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 *
 * @deprecated v2.2.2 - código de otimização sem uso no caminho de execução (SPEC-049); remoção na v3.0.0.
 */
class PerformanceMonitor
{
    /**
     * Simple metrics storage
     */
    private static array $metrics = [
        'requests_total' => 0,
        'requests_success' => 0,
        'requests_error' => 0,
        'start_time' => 0,
        'peak_memory' => 0,
    ];

    /**
     * Active requests tracking
     */
    private static array $activeRequests = [];

    /**
     * Request context storage
     */
    private static array $requestContext = [];

    /**
     * Completed requests for statistics
     */
    private static array $completedRequests = [];

    /**
     * Error tracking
     */
    private static array $errors = [];

    /**
     * Memory samples
     */
    private static array $memorySamples = [];

    /**
     * Constructor for instance usage
     * @param array $config Configuration array (not used in simplified implementation)
     * @phpstan-ignore-next-line
     */
    public function __construct(array $config = [])
    {
        // Configuration is not used in this simplified implementation
        // Following 'Simplicidade sobre Otimização Prematura' principle

        if (self::$metrics['start_time'] === 0) {
            self::init();
        }
    }

    /**
     * Initialize monitor
     */
    public static function init(): void
    {
        self::$metrics['start_time'] = microtime(true);
        self::$metrics['peak_memory'] = memory_get_peak_usage(true);
    }

    /**
     * Record a request
     */
    public static function recordRequest(bool $success = true): void
    {
        self::$metrics['requests_total']++;

        if ($success) {
            self::$metrics['requests_success']++;
        } else {
            self::$metrics['requests_error']++;
        }

        // Update peak memory
        self::$metrics['peak_memory'] = max(
            self::$metrics['peak_memory'],
            memory_get_peak_usage(true)
        );
    }

    /**
     * Get simple metrics
     */
    public static function getMetrics(): array
    {
        $uptime = self::$metrics['start_time'] > 0
            ? microtime(true) - self::$metrics['start_time']
            : 0;

        $errorRate = self::$metrics['requests_total'] > 0
            ? (self::$metrics['requests_error'] / self::$metrics['requests_total']) * 100
            : 0;

        $requestsPerSecond = $uptime > 0
            ? self::$metrics['requests_total'] / max($uptime, 0.001) // Minimum 1ms to avoid division by zero
            : (self::$metrics['requests_total'] > 0 ? self::$metrics['requests_total'] * 1000 : 0);

        return [
            'uptime' => round($uptime, 2),
            'requests_total' => self::$metrics['requests_total'],
            'requests_success' => self::$metrics['requests_success'],
            'requests_error' => self::$metrics['requests_error'],
            'error_rate' => round($errorRate, 2),
            'requests_per_second' => round($requestsPerSecond, 2),
            'memory_current' => self::formatBytes(memory_get_usage(true)),
            'memory_peak' => self::formatBytes(self::$metrics['peak_memory']),
        ];
    }

    /**
     * Reset metrics
     */
    public static function reset(): void
    {
        self::$metrics = [
            'requests_total' => 0,
            'requests_success' => 0,
            'requests_error' => 0,
            'start_time' => microtime(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Get status summary
     */
    public static function getStatus(): array
    {
        $metrics = self::getMetrics();

        return [
            'status' => $metrics['error_rate'] < 5 ? 'healthy' : 'degraded',
            'uptime' => $metrics['uptime'],
            'requests_per_second' => $metrics['requests_per_second'],
            'error_rate' => $metrics['error_rate'],
            'memory_usage' => $metrics['memory_current'],
        ];
    }

    /**
     * Format bytes for human readability
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Start request tracking (static)
     */
    public static function startRequestStatic(?string $requestId = null, array $context = []): string
    {
        $requestId = $requestId ?? uniqid('req_', true);

        self::$activeRequests[$requestId] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context,
        ];

        self::$requestContext[$requestId] = $context;

        return $requestId;
    }

    /**
     * End request tracking (static)
     */
    public static function endRequestStatic(string $requestId, mixed $success = true): void
    {
        if (!isset(self::$activeRequests[$requestId])) {
            return;
        }

        $requestData = self::$activeRequests[$requestId];
        $duration = max(0.0, microtime(true) - $requestData['start_time']);
        $memoryUsed = memory_get_usage(true) - $requestData['start_memory'];

        // Store completed request data
        self::$completedRequests[] = [
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'success' => $success,
            'timestamp' => microtime(true),
        ];

        // Keep only the last 100 completed requests
        if (count(self::$completedRequests) > 100) {
            self::$completedRequests = array_slice(self::$completedRequests, -100);
        }

        // Record the request
        $successBool = is_bool($success) ? $success : ($success >= 200 && $success < 400);
        self::recordRequest($successBool);

        // Clean up
        unset(self::$activeRequests[$requestId]);
        unset(self::$requestContext[$requestId]);
    }

    /**
     * Get live metrics
     */
    public static function getLiveMetrics(): array
    {
        $metrics = self::getMetrics();

        return array_merge(
            $metrics,
            [
                'active_requests' => count(self::$activeRequests),
                'memory_pressure' => self::calculateMemoryPressure(),
                'average_response_time' => self::calculateAverageResponseTime(),
                'system_load' => self::getSystemLoad(),
                'current_load' => self::getSystemLoad(),
            ]
        );
    }

    /**
     * Calculate memory pressure
     */
    private static function calculateMemoryPressure(): float
    {
        $currentMemory = memory_get_usage(true);
        $memoryLimit = self::getMemoryLimit();

        if ($memoryLimit <= 0) {
            return 0.0;
        }

        return min(($currentMemory / $memoryLimit), 1.0);
    }

    /**
     * Calculate average response time from completed requests
     */
    private static function calculateAverageResponseTime(): float
    {
        if (empty(self::$completedRequests)) {
            return 0.0;
        }

        $totalTime = 0;
        $count = count(self::$completedRequests);

        foreach (self::$completedRequests as $request) {
            $totalTime += $request['duration'];
        }

        return $totalTime / $count;
    }

    /**
     * Get system load
     */
    private static function getSystemLoad(): float
    {
        // Simple load calculation based on active requests
        $load = count(self::$activeRequests) / max(1, 10); // Assume 10 concurrent requests is normal
        return min(1.0, $load);
    }

    /**
     * Get memory limit
     */
    private static function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return 0; // Unlimited
        }

        return self::parseBytes($memoryLimit);
    }

    /**
     * Instance method: Start request tracking
     */
    public function startRequest(?string $requestId = null, array $context = []): string
    {
        return self::startRequestStatic($requestId, $context);
    }

    /**
     * Instance method: End request tracking
     */
    public function endRequest(string $requestId, mixed $success = true): void
    {
        self::endRequestStatic($requestId, $success);
    }

    /**
     * Instance method: Get performance metrics with detailed structure
     */
    public function getPerformanceMetrics(): array
    {
        $metrics = self::getMetrics();
        $latencyStats = self::calculateLatencyStats();

        return [
            'latency' => [
                'min' => $latencyStats['min'],
                'max' => $latencyStats['max'],
                'p50' => $latencyStats['avg'],
                'p95' => $latencyStats['p95'],
                'p99' => $latencyStats['p99'],
                'avg' => $latencyStats['avg'],
            ],
            'throughput' => [
                'requests_per_second' => $metrics['requests_per_second'],
                'rps' => $metrics['requests_per_second'],
                'total_requests' => $metrics['requests_total'],
                'successful_requests' => $metrics['requests_success'],
                'failed_requests' => $metrics['requests_error'],
                'success_rate' => $metrics['requests_total'] > 0
                    ? $metrics['requests_success'] / $metrics['requests_total']
                    : 0.0,
                'error_rate' => $metrics['requests_total'] > 0
                    ? $metrics['requests_error'] / $metrics['requests_total']
                    : 0.0,
            ],
            'memory' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => self::getMemoryLimit(),
                'usage_percent' => self::calculateMemoryPressure(),
            ],
            'system' => [
                'uptime' => $metrics['uptime'],
                'active_requests' => count(self::$activeRequests),
                'error_rate' => $metrics['error_rate'],
            ],
        ];
    }

    /**
     * Get performance metrics with detailed structure (static)
     */
    public static function getPerformanceMetricsStatic(): array
    {
        $metrics = self::getMetrics();
        $latencyStats = self::calculateLatencyStats();

        return [
            'latency' => [
                'min' => $latencyStats['min'],
                'max' => $latencyStats['max'],
                'p50' => $latencyStats['avg'],
                'p95' => $latencyStats['p95'],
                'p99' => $latencyStats['p99'],
                'avg' => $latencyStats['avg'],
            ],
            'throughput' => [
                'requests_per_second' => $metrics['requests_per_second'],
                'rps' => $metrics['requests_per_second'],
                'total_requests' => $metrics['requests_total'],
                'successful_requests' => $metrics['requests_success'],
                'failed_requests' => $metrics['requests_error'],
                'success_rate' => $metrics['requests_total'] > 0
                    ? $metrics['requests_success'] / $metrics['requests_total']
                    : 0.0,
                'error_rate' => $metrics['requests_total'] > 0
                    ? $metrics['requests_error'] / $metrics['requests_total']
                    : 0.0,
            ],
            'memory' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => self::getMemoryLimit(),
                'usage_percent' => self::calculateMemoryPressure(),
            ],
            'system' => [
                'uptime' => $metrics['uptime'],
                'active_requests' => count(self::$activeRequests),
                'error_rate' => $metrics['error_rate'],
            ],
        ];
    }

    /**
     * Calculate latency statistics from completed requests
     */
    private static function calculateLatencyStats(): array
    {
        if (empty(self::$completedRequests)) {
            return [
                'min' => 0.0,
                'max' => 0.0,
                'avg' => 0.0,
                'p95' => 0.0,
                'p99' => 0.0,
            ];
        }

        $durations = array_column(self::$completedRequests, 'duration');
        sort($durations);

        $count = count($durations);
        $min = min($durations);
        $max = max($durations);
        $avg = array_sum($durations) / $count;

        $p95Index = (int)ceil($count * 0.95) - 1;
        $p99Index = (int)ceil($count * 0.99) - 1;

        $p95 = $durations[max(0, $p95Index)];
        $p99 = $durations[max(0, $p99Index)];

        return [
            'min' => max(0.0, $min),
            'max' => max(0.0, $max),
            'avg' => max(0.0, $avg),
            'p95' => max(0.0, $p95),
            'p99' => max(0.0, $p99),
        ];
    }

    /**
     * Parse memory string to bytes
     */
    private static function parseBytes(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int)$size;

        switch ($last) {
            case 'g':
                $size *= 1024;
                // no break
            case 'm':
                $size *= 1024;
                // no break
            case 'k':
                $size *= 1024;
        }

        return $size;
    }

    /**
     * Record memory sample
     */
    public function recordMemorySample(): void
    {
        self::recordMemorySampleStatic();
    }

    /**
     * Record memory sample (static)
     */
    public static function recordMemorySampleStatic(): void
    {
        self::$memorySamples[] = [
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ];

        // Keep only the last 100 samples
        if (count(self::$memorySamples) > 100) {
            self::$memorySamples = array_slice(self::$memorySamples, -100);
        }
    }

    /**
     * Record error
     */
    public function recordError(string $type, array $context = []): void
    {
        self::recordErrorStatic($type, $context);
    }

    /**
     * Record error (static)
     */
    public static function recordErrorStatic(string $type, array $context = []): void
    {
        self::$errors[] = [
            'type' => $type,
            'context' => $context,
            'timestamp' => microtime(true),
        ];

        // Keep only the last 100 errors
        if (count(self::$errors) > 100) {
            self::$errors = array_slice(self::$errors, -100);
        }

        // Also record as a failed request
        self::recordRequest(false);
    }
}
