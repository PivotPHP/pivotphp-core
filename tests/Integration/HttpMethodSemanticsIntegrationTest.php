<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

/**
 * Semântica de métodos HTTP no core (HEAD→GET, 405 `Allow`, OPTIONS) — SPEC-072.
 */
class HttpMethodSemanticsIntegrationTest extends TestCase
{
    private function app(): Application
    {
        $app = new Application(__DIR__ . '/../../..');
        $app->get('/thing', fn ($req, $res) => $res->json(['ok' => true]));
        $app->boot();

        return $app;
    }

    public function testWrongMethodReturns405WithAllow(): void
    {
        $response = $this->app()->handle(new ServerRequest('POST', '/thing'));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertStringContainsString('GET', $response->getHeaderLine('Allow'));
        $this->assertStringContainsString('HEAD', $response->getHeaderLine('Allow'));
    }

    public function testOptionsReturns204WithAllow(): void
    {
        $response = $this->app()->handle(new ServerRequest('OPTIONS', '/thing'));

        $this->assertSame(204, $response->getStatusCode());
        $this->assertStringContainsString('GET', $response->getHeaderLine('Allow'));
    }

    public function testHeadUsesGetRoute(): void
    {
        $response = $this->app()->handle(new ServerRequest('HEAD', '/thing'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUnknownPathStillReturns404(): void
    {
        $response = $this->app()->handle(new ServerRequest('GET', '/missing'));

        $this->assertSame(404, $response->getStatusCode());
    }
}
