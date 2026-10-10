<?php

namespace PivotPHP\Core\Database;

/**
 * Conexão simples com banco de dados usando PDO.
 *
 * Drivers suportados: sqlite (via `database` = caminho ou ':memory:') e
 * mysql/mariadb (porta padrão 3306, `charset` padrão utf8mb4) e pgsql/postgres/postgresql
 * (porta padrão 5432), via `host`, `port`, `database`, `username`, `password`.
 */
class Database
{
    private \PDO $pdo;
    private array $config;

    /**
     * __construct method
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Estabelece conexão com o banco
     */
    private function connect(): void
    {
        $driver = $this->config['driver'] ?? 'mysql';

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($driver === 'sqlite') {
            $database = $this->config['database'] ?? ':memory:';
            $this->pdo = new \PDO("sqlite:{$database}", null, null, $options);

            return;
        }

        $dsn = self::buildDsn($driver, $this->config);
        $username = $this->config['username'];
        $password = $this->config['password'];

        $this->pdo = new \PDO($dsn, $username, $password, $options);
    }

    /**
     * Monta o DSN de servidor por driver: porta padrão própria (mysql 3306, pgsql 5432),
     * `charset` só no MySQL e aliases `mariadb` → mysql, `postgres`/`postgresql` → pgsql.
     */
    private static function buildDsn(string $driver, array $config): string
    {
        $driver = match (strtolower($driver)) {
            'mysql', 'mariadb' => 'mysql',
            'pgsql', 'postgres', 'postgresql' => 'pgsql',
            default => throw new \InvalidArgumentException(
                "Unsupported database driver '{$driver}'. Use sqlite, mysql/mariadb or pgsql/postgres."
            ),
        };

        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? ($driver === 'pgsql' ? 5432 : 3306);
        $database = $config['database'];

        $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";

        if ($driver === 'mysql') {
            return $dsn . ';charset=' . ($config['charset'] ?? 'utf8mb4');
        }

        // PostgreSQL não aceita `charset` no DSN; a codificação vai como opção do cliente.
        if (isset($config['charset'])) {
            $dsn .= ";options='--client_encoding={$config['charset']}'";
        }

        return $dsn;
    }

    /**
     * Executa uma query SELECT
     */
    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        return (array) $statement->fetchAll();
    }

    /**
     * Executa uma query SELECT e retorna apenas um registro
     */
    public function selectOne(string $query, array $bindings = []): ?array
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        $result = $statement->fetch();

        if ($result === false) {
            return null;
        }

        return is_array($result) ? $result : null;
    }

    /**
     * Executa uma query INSERT
     */
    public function insert(string $query, array $bindings = []): bool
    {
        $statement = $this->pdo->prepare($query);
        return $statement->execute($bindings);
    }

    /**
     * Executa uma query UPDATE
     */
    public function update(string $query, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        return $statement->rowCount();
    }

    /**
     * Executa uma query DELETE
     */
    public function delete(string $query, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        return $statement->rowCount();
    }

    /**
     * Executa uma query genérica (um único statement).
     *
     * Para SQL com múltiplos statements (ex.: schema/migrações), use exec().
     */
    public function statement(string $query, array $bindings = []): bool
    {
        $statement = $this->pdo->prepare($query);
        return $statement->execute($bindings);
    }

    /**
     * Executa SQL com múltiplos statements (ex.: schema/migrações).
     *
     * Delega a PDO::exec(), que aceita vários statements separados por ';'.
     * Retorna o número de linhas afetadas pela última operação.
     */
    public function exec(string $sql): int
    {
        $result = $this->pdo->exec($sql);

        if ($result === false) {
            throw new \PDOException('Falha ao executar SQL (PDO::exec retornou false).');
        }

        return $result;
    }

    /**
     * Retorna o último ID inserido
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId() ?: '';
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Desfaz uma transação
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Executa uma função dentro de uma transação
     *
     * @param  callable $callback
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            // Any failure (Exception or Error) rolls back; the guard avoids masking the original
            // error when the transaction is no longer active (SPEC-073).
            if ($this->pdo->inTransaction()) {
                $this->rollback();
            }
            throw $e;
        }
    }

    /**
     * Retorna a instância PDO
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}
