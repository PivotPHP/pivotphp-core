<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Routing\Router\Router;

/**
 * Cobre o fluxo completo de rota com parâmetro `{id}` (SPEC-002):
 * o router casa a rota e o Request extrai o parâmetro para $req->param().
 */
class BraceParameterIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        Router::clear();
    }

    protected function tearDown(): void
    {
        Router::clear();
    }

    public function testBraceParameterEndToEnd(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->get(
            '/users/{id}',
            function ($req, $res) {
                return $res->json(['id' => $req->param('id')]);
            }
        );

        // Request construído com o path real (como Request::createFromGlobals()).
        $response = $app->handle(new Request('GET', '/users/123', '/users/123'));

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getBodyAsString(), true);
        $this->assertSame(123, $body['id']);
    }
}
