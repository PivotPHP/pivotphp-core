<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Database;

use Error;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Database\Database;
use RuntimeException;

/**
 * transaction() must roll back on any Throwable, including Error (SPEC-073).
 */
class DatabaseTransactionTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite not available');
        }

        $this->db = new Database(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db->exec('CREATE TABLE items (name TEXT)');
    }

    private function itemCount(): int
    {
        return (int) $this->db->select('SELECT COUNT(*) AS n FROM items')[0]['n'];
    }

    public function testErrorRollsBackAndClosesTheTransaction(): void
    {
        try {
            $this->db->transaction(
                function (Database $db): void {
                    $db->statement('INSERT INTO items (name) VALUES (?)', ['a']);
                    throw new Error('boom');
                }
            );
            $this->fail('Error should propagate');
        } catch (Error $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame(0, $this->itemCount());
        $this->assertFalse($this->db->getPdo()->inTransaction());
    }

    public function testExceptionRollsBack(): void
    {
        try {
            $this->db->transaction(
                function (Database $db): void {
                    $db->statement('INSERT INTO items (name) VALUES (?)', ['a']);
                    throw new RuntimeException('fail');
                }
            );
        } catch (RuntimeException) {
        }

        $this->assertSame(0, $this->itemCount());
        $this->assertFalse($this->db->getPdo()->inTransaction());
    }

    public function testSuccessCommitsAndReturnsTheResult(): void
    {
        $result = $this->db->transaction(
            function (Database $db): string {
                $db->statement('INSERT INTO items (name) VALUES (?)', ['a']);

                return 'done';
            }
        );

        $this->assertSame('done', $result);
        $this->assertSame(1, $this->itemCount());
    }

    public function testNewTransactionCanStartAfterAFailedOne(): void
    {
        try {
            $this->db->transaction(fn () => throw new Error('first'));
        } catch (Error) {
        }

        $this->db->transaction(fn (Database $db) => $db->statement('INSERT INTO items (name) VALUES (?)', ['b']));

        $this->assertSame(1, $this->itemCount());
    }
}
