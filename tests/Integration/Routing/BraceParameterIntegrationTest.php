<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Nyholm\Psr7\ServerRequest;
use PivotPHP\Routing\Router\Router;

/**
 * Cobre o fluxo completo de rota com parâmetro `{id}` (SPEC-002):
 * o router casa a rota e o Request extrai o parâmetro para $req->param().
 */
class BraceParameterIntegrationTest extends TestCase
{
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
        $response = $app->handle(new ServerRequest('GET', '/users/123'));

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('123', $body['id']);
    }
}
