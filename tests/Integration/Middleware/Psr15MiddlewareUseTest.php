<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Cobre que middlewares PSR-15 funcionam com $app->use() (SPEC-023).
 */
class Psr15MiddlewareUseTest extends TestCase
{
    public function testPsr15MiddlewareObjectViaUse(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->use(
            new class implements MiddlewareInterface {
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface {
                    $response = $handler->handle($request);

                    return $response->withHeader('X-Psr15', 'yes');
                }
            }
        );

        $app->get(
            '/hello',
            function ($req, $res) {
                return $res->json(['ok' => true]);
            }
        );

        $response = $app->handle(new ServerRequest('GET', '/hello'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('yes', $response->getHeaderLine('X-Psr15'));
    }
}
