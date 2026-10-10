<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

/**
 * handle() must not change global error/exception handlers; run() installs them
 * for the SAPI request and restores them afterwards.
 */
class ApplicationGlobalHandlersTest extends TestCase
{
    private static function currentErrorHandler(): mixed
    {
        $current = set_error_handler(static fn (): bool => false);
        restore_error_handler();

        return $current;
    }

    public function testHandleDoesNotInstallGlobalHandlers(): void
    {
        $before = self::currentErrorHandler();

        $app = new Application(__DIR__ . '/../..');
        $app->get('/ok', fn ($req, $res) => $res->json(['ok' => true]));
        $app->handle(new ServerRequest('GET', '/ok'));

        $this->assertSame($before, self::currentErrorHandler());
    }

    public function testRunRestoresGlobalHandlers(): void
    {
        $before = self::currentErrorHandler();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/ok';

        $app = new Application(__DIR__ . '/../..');
        $app->get('/ok', fn ($req, $res) => $res->json(['ok' => true]));

        ob_start();
        $app->run();
        $output = (string) ob_get_clean();

        $this->assertSame('{"ok":true}', $output);
        $this->assertSame($before, self::currentErrorHandler());
    }
}
