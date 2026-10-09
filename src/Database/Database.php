<?php

namespace PivotPHP\Core\Database;

/**
 * Conexão simples com banco de dados usando PDO.
 *
 * Drivers suportados: sqlite (via `database` = caminho ou ':memory:') e
 * mysql/mariadb/pgsql (via `host`, `port`, `database`, `username`, `password`).
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

        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        $charset = $this->config['charset'] ?? 'utf8mb4';

        $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";

        $this->pdo = new \PDO($dsn, $username, $password, $options);
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
     * Executa uma query genérica
     */
    public function statement(string $query, array $bindings = []): bool
    {
        $statement = $this->pdo->prepare($query);
        return $statement->execute($bindings);
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
        } catch (\Exception $e) {
            $this->rollback();
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
