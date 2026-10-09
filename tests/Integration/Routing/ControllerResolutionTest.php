<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Tests\Integration\Routing\CountingController;
use PivotPHP\Routing\Router\Router;

/**
 * Cobre a resolução de controller por requisição (SPEC-041).
 */
class ControllerResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        Router::clear();
        CountingController::reset();
    }

    protected function tearDown(): void
    {
        Router::clear();
    }

    public function testRouteRegisteredBeforeBindingResolvesAtRequestTime(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        // Rota registrada ANTES do binding do controller.
        $app->get('/hi', [CountingController::class, 'id']);

        // Binding só depois — não deve importar (resolução é lazy).
        $app->instance(CountingController::class, new CountingController('bound'));

        $response = $app->handle(new Request('GET', '/hi', '/hi'));

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getBodyAsString(), true);
        $this->assertSame('bound', $body['prefix']);
    }

    public function testBindCreatesNewInstancePerRequest(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->bind(CountingController::class, fn() => new CountingController('fresh'));
        $app->get('/fresh', [CountingController::class, 'id']);

        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $response = $app->handle(new Request('GET', '/fresh', '/fresh'));
            $body = json_decode($response->getBodyAsString(), true);
            $ids[] = $body['id'];
        }

        $this->assertSame([1, 2, 3], $ids);
    }

    public function testSingletonReusesInstance(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->singleton(CountingController::class, fn() => new CountingController('shared'));
        $app->get('/shared', [CountingController::class, 'id']);

        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $response = $app->handle(new Request('GET', '/shared', '/shared'));
            $body = json_decode($response->getBodyAsString(), true);
            $ids[] = $body['id'];
        }

        $this->assertSame([1, 1, 1], $ids);
    }
}
