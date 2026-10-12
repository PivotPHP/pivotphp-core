<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Core\Logging\PsrLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Logging Service Provider
 */
class LoggingServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->app->singleton(
            LoggerInterface::class,
            function (): LoggerInterface {
                // 1. If pivotphp/logger companion package is available, use its factory
                if (class_exists('PivotPHP\Logger\LoggerFactory')) {
                    /** @var array<string, mixed> $config */
                    $config = [];
                    $loggingConfig = $this->app->getConfig()->get('logging');
                    if (is_array($loggingConfig)) {
                        $config = $loggingConfig;
                    }
                    /** @var LoggerInterface */
                    return \PivotPHP\Logger\LoggerFactory::create($config);
                }

                // 2. Legacy fallback: if LOG_PATH is explicitly set in env, use deprecated PsrLogger
                $envPath = $_ENV['LOG_PATH'] ?? null;
                if (is_string($envPath) && $envPath !== '') {
                    return new PsrLogger($envPath);
                }

                // 3. Safe default: NullLogger (no disk I/O side effects during container boot)
                return new NullLogger();
            }
        );

        // Aliases
        $this->app->alias('log', LoggerInterface::class);
        $this->app->alias('logger', LoggerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return [
            LoggerInterface::class,
            'log',
            'logger',
        ];
    }
}
