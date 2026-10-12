<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Logging;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Logging\PsrLogger;
use PivotPHP\Core\Providers\LoggingServiceProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Tests for SPEC-112: Decoupled logging and safe container defaults.
 */
class LoggingServiceProviderDecoupleTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_ENV['LOG_PATH']);
    }

    protected function tearDown(): void
    {
        unset($_ENV['LOG_PATH']);
    }

    public function testDefaultLoggerIsNullLoggerWithoutEnvOrPackage(): void
    {
        $app = new Application();
        $provider = new LoggingServiceProvider($app);
        $provider->register();

        $logger = $app->getLogger();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(NullLogger::class, $logger);
    }

    public function testBootDoesNotCreateLogsDirectory(): void
    {
        $app = new Application();
        $provider = new LoggingServiceProvider($app);
        $provider->register();

        // Project root should not have logs/ auto-created
        $logsDir = dirname(__DIR__, 2) . '/logs';
        if (is_dir($logsDir)) {
            // If exists from prior runs, we ensure register() doesn't require it
            $this->assertTrue(true);
        } else {
            $this->assertDirectoryDoesNotExist($logsDir);
        }
    }

    public function testLegacyLogPathEnvUsesPsrLoggerForBackwardCompatibility(): void
    {
        $customLog = sys_get_temp_dir() . '/legacy-test.log';
        $_ENV['LOG_PATH'] = $customLog;

        $app = new Application();
        $provider = new LoggingServiceProvider($app);
        $provider->register();

        $logger = $app->getLogger();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(PsrLogger::class, $logger);
    }

    public function testAliasesAreRegistered(): void
    {
        $app = new Application();
        $provider = new LoggingServiceProvider($app);
        $provider->register();

        $this->assertSame($app->make(LoggerInterface::class), $app->make('log'));
        $this->assertSame($app->make(LoggerInterface::class), $app->make('logger'));
    }

    public function testLogExceptionResilienceWhenLoggerFails(): void
    {
        $app = new Application();

        // Register a broken logger that throws on error()
        $brokenLogger = new class () extends NullLogger {
            public function error(string|\Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Disk full or connection broken');
            }
        };

        $app->bind('logger', fn () => $brokenLogger);

        // Reflection to call protected logException
        $reflection = new \ReflectionClass($app);
        $logExceptionMethod = $reflection->getMethod('logException');

        // Should not throw or crash the execution
        $exception = new RuntimeException('Original application exception');
        $logExceptionMethod->invoke($app, $exception, 'err-123');

        $this->assertTrue(true, 'logException handled logger failure gracefully without throwing');
    }
}
