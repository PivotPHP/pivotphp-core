<?php

declare(strict_types=1);

namespace PivotPHP\Core\Providers;

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Logging\PsrLogger;
use Psr\Log\LoggerInterface;

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
            function () {
                $logPath = $this->getLogPath();
                return new PsrLogger($logPath);
            }
        );

        // Aliases
        $this->app->alias('log', LoggerInterface::class);
        $this->app->alias('logger', LoggerInterface::class);
    }

    /**
     * Get the log file path
     */
    private function getLogPath(): string
    {
        // Try to get from environment or config
        if (isset($_ENV['LOG_PATH'])) {
            return $_ENV['LOG_PATH'];
        }

        // Default to logs directory in project root
        $projectRoot = dirname(dirname(__DIR__));
        $logsDir = $projectRoot . '/logs';

        // Create logs directory if it doesn't exist
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        return $logsDir . '/express-php.log';
    }

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return [
            LoggerInterface::class,
            'log',
            'logger'
        ];
    }
}
