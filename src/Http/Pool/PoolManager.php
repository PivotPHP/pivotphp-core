<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Pool;

/**
 * Pool Manager (generic, instance-based)
 *
 * Simple and effective object pooling for the microframework.
 * Provides basic pooling functionality without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 *
 * NÃO CONFUNDIR com \PivotPHP\Core\Http\Psr7\Pool\PoolManager — são classes
 * diferentes, com propósitos diferentes, apesar do nome igual:
 * - Esta classe (Http\Pool\PoolManager): pool genérico por instância,
 *   rent()/return()/borrow() em pools nomeados arbitrariamente por string.
 *   Não tem conhecimento de HTTP/PSR-7.
 * - Http\Psr7\Pool\PoolManager: coordenador 100% estático dos pools PSR-7
 *   específicos (ResponsePool, HeaderPool, OperationsCache). Sendo estático,
 *   não pode implementar a mesma interface de instância que esta classe —
 *   unificação real exigiria reescrever um dos dois de raiz, o que não se
 *   justifica hoje: nenhuma das duas classes é usada no caminho de produção
 *   do framework (o pooling real de request/response é feito via
 *   HttpPoolFacade/Psr7Pool, não por nenhum destes dois PoolManager).
 */
class PoolManager
{
    /**
     * Pool storage
     */
    private array $pools = [];

    /**
     * Simple configuration
     */
    private int $maxPoolSize = 50;
    private bool $enabled = true;

    /**
     * Pool statistics
     */
    private array $statistics = [
        'borrowed' => 0,
        'returned' => 0,
        'expanded' => 0,
        'pool_hits' => 0,
        'pool_misses' => 0,
    ];

    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Constructor for singleton (public for testing)
     */
    public function __construct(array $config = [])
    {
        // Initialize basic pools
        $this->pools = [
            'request' => [],
            'response' => [],
            'stream' => [],
        ];

        // Apply configuration
        if (isset($config['maxPoolSize'])) {
            $this->maxPoolSize = $config['maxPoolSize'];
        }
        if (isset($config['enabled'])) {
            $this->enabled = $config['enabled'];
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Enable pooling
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable pooling
     */
    public function disable(): void
    {
        $this->enabled = false;
        $this->clearAll();
    }

    /**
     * Set maximum pool size
     */
    public function setMaxPoolSize(int $size): void
    {
        $this->maxPoolSize = max(1, $size);
    }

    /**
     * Rent an object from pool
     */
    public function rent(string $poolName): ?object
    {
        if (!$this->enabled) {
            $this->statistics['pool_misses']++;
            return null;
        }

        // If pool is empty, track as miss but we'll create a new object
        if (empty($this->pools[$poolName])) {
            $this->statistics['pool_misses']++;
            $this->statistics['borrowed']++;
            // If we're borrowing more than pool size, track expansion
            if ($this->statistics['borrowed'] > $this->maxPoolSize) {
                $this->statistics['expanded']++;
            }
            return null; // Caller should create new object
        }

        $this->statistics['borrowed']++;
        $this->statistics['pool_hits']++;
        return array_pop($this->pools[$poolName]);
    }

    /**
     * Return an object to pool
     */
    public function return(string $poolName, object $object): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->pools[$poolName])) {
            $this->pools[$poolName] = [];
        }

        // Don't exceed max pool size
        if (count($this->pools[$poolName]) < $this->maxPoolSize) {
            $this->pools[$poolName][] = $object;
            $this->statistics['returned']++;
        }

        // Check if we need to expand (track expansion attempts)
        if (count($this->pools[$poolName]) >= $this->maxPoolSize * 0.8) {
            $this->statistics['expanded']++;
        }
    }

    /**
     * Clear all pools
     */
    public function clearAll(): void
    {
        $this->pools = [
            'request' => [],
            'response' => [],
            'stream' => [],
        ];
    }

    /**
     * Clear specific pool
     */
    public function clearPool(string $poolName): void
    {
        if (isset($this->pools[$poolName])) {
            $this->pools[$poolName] = [];
        }
    }

    /**
     * Get simple pool statistics
     */
    public function getStats(): array
    {
        $stats = [
            'enabled' => $this->enabled,
            'max_pool_size' => $this->maxPoolSize,
            'pools' => [],
            'stats' => array_merge(
                $this->statistics,
                [
                    'total_operations' => $this->statistics['borrowed'] + $this->statistics['returned'],
                ]
            ),
            'scaling_state' => [
                'request' => [
                    'current_size' => $this->statistics['borrowed'],
                    'max_size' => $this->maxPoolSize,
                ],
                'response' => [
                    'current_size' => $this->statistics['borrowed'],
                    'max_size' => $this->maxPoolSize,
                ],
            ],
        ];

        foreach ($this->pools as $name => $pool) {
            $stats['pools'][$name] = [
                'size' => count($pool),
                'utilization' => count($pool) / $this->maxPoolSize,
            ];
        }

        return $stats;
    }

    /**
     * Reset pool statistics
     */
    public function resetStats(): void
    {
        // Simple implementation - just clear all pools and reset stats
        $this->clearAll();
        $this->statistics = [
            'borrowed' => 0,
            'returned' => 0,
            'expanded' => 0,
            'pool_hits' => 0,
            'pool_misses' => 0,
        ];
    }

    /**
     * Reset pool statistics (static version)
     */
    public static function resetStatsStatic(): void
    {
        $instance = self::getInstance();
        $instance->resetStats();
    }

    /**
     * Get pool size
     */
    public function getPoolSize(string $poolName): int
    {
        return count($this->pools[$poolName] ?? []);
    }

    /**
     * Check if pool exists
     */
    public function hasPool(string $poolName): bool
    {
        return isset($this->pools[$poolName]);
    }

    /**
     * Get optimal pool sizes (simplified)
     */
    public function getOptimalPoolSizes(): array
    {
        return [
            'request' => $this->maxPoolSize,
            'response' => $this->maxPoolSize,
            'stream' => $this->maxPoolSize,
        ];
    }

    /**
     * Get memory recommendations (simplified)
     */
    public function getMemoryRecommendations(): array
    {
        return [
            'current_usage' => 'normal',
            'recommended_action' => 'none',
            'tier' => 'normal',
        ];
    }

    /**
     * Get detailed statistics
     */
    public function getDetailedStats(): array
    {
        $stats = $this->getStats();
        $stats['detailed'] = true;
        $stats['memory_usage'] = memory_get_usage(true);
        $stats['memory_tier'] = 'normal';
        return $stats;
    }

    /**
     * Force cleanup if needed
     */
    public function forceCleanupIfNeeded(): void
    {
        // Simple implementation - clear if too many items
        foreach ($this->pools as $poolName => $pool) {
            if (count($pool) > $this->maxPoolSize) {
                $this->clearPool($poolName);
            }
        }
    }

    /**
     * Borrow object from pool (can create new objects if pool is empty)
     */
    public function borrow(string $poolName, ?array $config = null): object
    {
        // Try to get from pool first
        $object = $this->rent($poolName);

        // If pool is empty, create new object and track expansion
        if ($object === null) {
            // Create a simple stdClass object with the config
            $object = new \stdClass();
            if ($config) {
                foreach ($config as $key => $value) {
                    $object->$key = $value;
                }
            }

            // Since we're going beyond pool capacity, this is an expansion
            $this->statistics['expanded']++;
        }

        return $object;
    }
}
