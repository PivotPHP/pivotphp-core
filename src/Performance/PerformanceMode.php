<?php

declare(strict_types=1);

namespace PivotPHP\Core\Performance;

use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

/**
 * Performance Mode for PivotPHP Core
 *
 * Simple and effective performance optimizations for the microframework.
 * Focuses on essential optimizations without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 *
 * @deprecated v2.2.2 - código de otimização sem uso no caminho de execução (SPEC-049); remoção na v3.0.0.
 */
class PerformanceMode
{
    /**
     * Performance profiles - simplified
     */
    public const PROFILE_DEVELOPMENT = 'development';
    public const PROFILE_PRODUCTION = 'production';
    public const PROFILE_TEST = 'test';

    // Backward compatibility with HighPerformanceMode
    public const PROFILE_STANDARD = 'standard';
    public const PROFILE_HIGH = 'high';
    public const PROFILE_EXTREME = 'extreme';

    /**
     * Current mode
     */
    private static ?string $currentMode = null;

    /**
     * Performance monitor instance
     */
    private static ?PerformanceMonitor $monitor = null;

    /**
     * Enable simple performance optimizations
     */
    public static function enable(string $profile = self::PROFILE_PRODUCTION): void
    {
        self::$currentMode = $profile;

        switch ($profile) {
            case self::PROFILE_DEVELOPMENT:
                // Minimal optimizations for development
                OptimizedHttpFactory::initialize(['enable_pooling' => false]);
                break;

            case self::PROFILE_TEST:
                // No optimizations for tests - they add overhead to small workloads
                OptimizedHttpFactory::initialize(['enable_pooling' => false]);
                break;

            case self::PROFILE_PRODUCTION:
            case self::PROFILE_STANDARD:
            case self::PROFILE_HIGH:
            case self::PROFILE_EXTREME:
                // Enable only proven optimizations for production
                // All legacy profiles map to production level
                OptimizedHttpFactory::initialize(
                    [
                        'enable_pooling' => true,
                        'initial_size' => 20,       // Smaller, more reasonable pool
                        'max_size' => 100,          // Don't over-allocate
                    ]
                );
                break;
        }
    }

    /**
     * Disable performance mode
     */
    public static function disable(): void
    {
        self::$currentMode = null;
        OptimizedHttpFactory::initialize(['enable_pooling' => false]);
    }

    /**
     * Get current mode
     */
    public static function getCurrentMode(): ?string
    {
        return self::$currentMode;
    }

    /**
     * Check if performance mode is enabled
     */
    public static function isEnabled(): bool
    {
        return self::$currentMode !== null;
    }

    /**
     * Get simple status
     */
    public static function getStatus(): array
    {
        return [
            'enabled' => self::isEnabled(),
            'mode' => self::$currentMode,
            'pool_enabled' => self::$currentMode === self::PROFILE_PRODUCTION,
        ];
    }

    /**
     * Get monitor for compatibility with HighPerformanceMode
     */
    public static function getMonitor(): PerformanceMonitor
    {
        if (self::$monitor === null) {
            self::$monitor = new PerformanceMonitor();
        }
        return self::$monitor;
    }
}
