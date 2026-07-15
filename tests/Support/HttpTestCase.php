<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * Base test case for HTTP tests.
 * Safely saves and restores superglobals between tests.
 */
abstract class HttpTestCase extends TestCase
{
    private array $originalServer = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServer = $_SERVER;
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SERVER = $this->originalServer;
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_COOKIE = [];
    }
}
