<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

/**
 * Configuration files read the environment, so the .env file must be loaded before config/ (SPEC-101).
 */
class EnvBeforeConfigTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/pivotphp-env-' . bin2hex(random_bytes(4));
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "PIVOTPHP_TEST_FLAG=from-dotenv\n");
        file_put_contents(
            $this->basePath . '/config/app.php',
            "<?php return ['flag' => \$_ENV['PIVOTPHP_TEST_FLAG'] ?? 'missing'];"
        );
    }

    protected function tearDown(): void
    {
        unset($_ENV['PIVOTPHP_TEST_FLAG']);
        putenv('PIVOTPHP_TEST_FLAG');
        @unlink($this->basePath . '/.env');
        @unlink($this->basePath . '/config/app.php');
        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);
    }

    public function testConfigFilesSeeVariablesFromDotEnv(): void
    {
        $app = Application::create($this->basePath);
        $app->boot();

        $this->assertSame('from-dotenv', $app->getConfig()->get('app.flag'));
    }

    public function testRealEnvironmentTakesPrecedenceOverDotEnv(): void
    {
        putenv('PIVOTPHP_TEST_FLAG=from-real-env');
        $_ENV['PIVOTPHP_TEST_FLAG'] = 'from-real-env';

        $app = Application::create($this->basePath);
        $app->boot();

        $this->assertSame('from-real-env', $app->getConfig()->get('app.flag'));
    }
}
