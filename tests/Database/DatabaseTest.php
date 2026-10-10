<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Database;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Database\Database;

class DatabaseTest extends TestCase
{
    public function testSqliteConnectionAndQueries(): void
    {
        $db = new Database(['driver' => 'sqlite', 'database' => ':memory:']);

        $db->statement('CREATE TABLE books (id INTEGER PRIMARY KEY, title TEXT)');
        $db->insert('INSERT INTO books (title) VALUES (:title)', ['title' => 'Dom Casmurro']);

        $rows = $db->select('SELECT * FROM books');

        $this->assertCount(1, $rows);
        $this->assertEquals('Dom Casmurro', $rows[0]['title']);

        $one = $db->selectOne('SELECT * FROM books WHERE id = :id', ['id' => 1]);
        $this->assertNotNull($one);
        $this->assertEquals('Dom Casmurro', $one['title']);
    }

    public function testSqliteTransaction(): void
    {
        $db = new Database(['driver' => 'sqlite', 'database' => ':memory:']);

        $db->statement('CREATE TABLE t (id INTEGER PRIMARY KEY, v TEXT)');

        $result = $db->transaction(
            function (Database $db) {
                $db->insert('INSERT INTO t (v) VALUES (:v)', ['v' => 'x']);

                return 'ok';
            }
        );

        $this->assertEquals('ok', $result);
        $this->assertCount(1, $db->select('SELECT * FROM t'));
    }

    public function testExecMultipleStatements(): void
    {
        $db = new Database(['driver' => 'sqlite', 'database' => ':memory:']);

        $db->exec(
            'CREATE TABLE a (id INTEGER PRIMARY KEY);'
            . 'CREATE TABLE b (id INTEGER PRIMARY KEY);'
        );

        $tables = $db->select(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name IN ('a', 'b') ORDER BY name"
        );
        $names = array_column($tables, 'name');

        $this->assertContains('a', $names);
        $this->assertContains('b', $names);
    }

    public function testBindsParametersWithNativeTypes(): void
    {
        $db = new Database(['driver' => 'sqlite', 'database' => ':memory:']);
        $db->exec('CREATE TABLE t (id INTEGER PRIMARY KEY); INSERT INTO t VALUES (1),(2),(3)');

        // SPEC-104: integer bindings for LIMIT/OFFSET must be bound as PARAM_INT (not text),
        // so pagination works on strict drivers (e.g. PostgreSQL).
        $rows = $db->select('SELECT id FROM t ORDER BY id LIMIT ? OFFSET ?', [2, 1]);
        $this->assertSame([2, 3], array_map(static fn (array $r): int => (int) $r['id'], $rows));

        // Booleans and null keep their type instead of being forced to strings.
        $probe = $db->selectOne('SELECT ? AS flag, ? AS missing', [true, null]);
        $this->assertNotNull($probe);
        $this->assertSame(1, (int) $probe['flag']);
        $this->assertNull($probe['missing']);
    }
}
