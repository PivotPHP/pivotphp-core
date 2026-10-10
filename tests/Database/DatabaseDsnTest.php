<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Database\Database;

/**
 * SPEC-074: DSN por driver (porta padrão, charset só no MySQL, aliases).
 */
class DatabaseDsnTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private function dsn(string $driver, array $config): string
    {
        $method = new \ReflectionMethod(Database::class, 'buildDsn');
        $method->setAccessible(true);

        $dsn = $method->invoke(null, $driver, $config + ['database' => 'app']);
        $this->assertIsString($dsn);

        return $dsn;
    }

    public function testMysqlUsesPort3306AndUtf8mb4ByDefault(): void
    {
        $this->assertSame(
            'mysql:host=localhost;port=3306;dbname=app;charset=utf8mb4',
            $this->dsn('mysql', [])
        );
    }

    public function testMariadbIsAnAliasOfMysql(): void
    {
        $this->assertSame(
            'mysql:host=db;port=3307;dbname=app;charset=latin1',
            $this->dsn('mariadb', ['host' => 'db', 'port' => 3307, 'charset' => 'latin1'])
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pgsqlDrivers(): array
    {
        return [
            'pgsql' => ['pgsql'],
            'postgres' => ['postgres'],
            'postgresql' => ['postgresql'],
            'uppercase' => ['PGSQL'],
        ];
    }

    #[DataProvider('pgsqlDrivers')]
    public function testPgsqlUsesPort5432AndNoCharset(string $driver): void
    {
        $this->assertSame('pgsql:host=localhost;port=5432;dbname=app', $this->dsn($driver, []));
    }

    public function testPgsqlCharsetBecomesClientEncodingOption(): void
    {
        $this->assertSame(
            "pgsql:host=pg;port=6543;dbname=app;options='--client_encoding=UTF8'",
            $this->dsn('pgsql', ['host' => 'pg', 'port' => 6543, 'charset' => 'UTF8'])
        );
    }

    public function testUnsupportedDriverIsRejectedWithClearMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported database driver 'sqlsrv'");

        new Database(['driver' => 'sqlsrv', 'database' => 'app', 'username' => 'u', 'password' => 'p']);
    }
}
