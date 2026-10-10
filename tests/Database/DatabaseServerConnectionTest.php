<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Database\Database;

/**
 * SPEC-074: conexão real com MySQL e PostgreSQL.
 *
 * Roda quando DB_MYSQL_HOST / DB_PGSQL_HOST estão definidos (job `databases` do CI);
 * caso contrário, é pulado. Credenciais: DB_USERNAME, DB_PASSWORD, DB_DATABASE.
 */
class DatabaseServerConnectionTest extends TestCase
{
    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function servers(): array
    {
        return [
            'mysql, porta padrão' => ['mysql', 'DB_MYSQL_HOST', false],
            'mysql, porta explícita' => ['mysql', 'DB_MYSQL_HOST', true],
            'mariadb (alias)' => ['mariadb', 'DB_MYSQL_HOST', false],
            'pgsql, porta padrão' => ['pgsql', 'DB_PGSQL_HOST', false],
            'pgsql, porta explícita' => ['pgsql', 'DB_PGSQL_HOST', true],
            'postgres (alias)' => ['postgres', 'DB_PGSQL_HOST', false],
        ];
    }

    #[DataProvider('servers')]
    public function testConnectsAndQueries(string $driver, string $hostVar, bool $explicitPort): void
    {
        $host = getenv($hostVar);
        if (!is_string($host) || $host === '') {
            $this->markTestSkipped("{$hostVar} não definido");
        }

        $config = [
            'driver' => $driver,
            'host' => $host,
            'database' => (string) getenv('DB_DATABASE'),
            'username' => (string) getenv('DB_USERNAME'),
            'password' => (string) getenv('DB_PASSWORD'),
        ];
        if ($explicitPort) {
            $config['port'] = str_contains($hostVar, 'PGSQL') ? 5432 : 3306;
        }

        $db = new Database($config);

        $row = $db->selectOne('SELECT 1 AS one');
        $this->assertNotNull($row);
        $this->assertEquals(1, $row['one']);
    }
}
