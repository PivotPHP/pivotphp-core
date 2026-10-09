<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Logging;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Logging\PsrLogger;

/**
 * Cobre o nome de arquivo de log padrão (SPEC-019).
 */
class LoggerDefaultFilenameTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_ENV['LOG_PATH']);
    }

    public function testDefaultFilenameIsPivotphp(): void
    {
        $logger = new PsrLogger();

        $this->assertSame('pivotphp.log', basename($logger->getLogPath()));
    }

    public function testLogPathEnvOverridesDefault(): void
    {
        $_ENV['LOG_PATH'] = '/tmp/custom-app.log';
        $logger = new PsrLogger();

        $this->assertSame('/tmp/custom-app.log', $logger->getLogPath());

        unset($_ENV['LOG_PATH']);
    }

    public function testDefaultLogFilenameConstant(): void
    {
        $this->assertSame('pivotphp.log', PsrLogger::DEFAULT_LOG_FILENAME);
    }
}
