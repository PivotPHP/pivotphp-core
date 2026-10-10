<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Nyholm\Psr7\ServerRequest;
use PivotPHP\Core\Tests\Integration\Routing\DiGreetingController;
use PivotPHP\Core\Tests\Integration\Routing\HealthController;
use PivotPHP\Routing\Router\Router;

/**
 * Cobre a resolução de [Classe::class, 'métodoDeInstância'] pela aplicação:
 * via container quando o controller está registrado (DI), e via instanciação
 * lazy quando não está.
 */
class ArrayCallableContainerResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        Router::clear();
    }

    protected function tearDown(): void
    {
        Router::clear();
    }

    public function testInstanceMethodResolvedViaContainer(): void
    {
        $app = new Application(__DIR__ . '/../../..');
        $app->instance(DiGreetingController::class, new DiGreetingController('di:'));

        $app->get('/di-greet', [DiGreetingController::class, 'greet']);

        $response = $app->handle(new ServerRequest('GET', '/di-greet'));

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('di: hello', $body['greeting']);
    }

    public function testNonBoundControllerFallsBackToLazyInstantiation(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        // HealthController tem construtor sem argumentos → instanciação lazy funciona.
        $app->get('/lazy-health', [HealthController::class, 'healthCheck']);

        $response = $app->handle(new ServerRequest('GET', '/lazy-health'));

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('ok', $body['status']);
    }
}
