<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use Nyholm\Psr7\ServerRequest;
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;
use PivotPHP\Routing\Router\Router;

/**
 * Cobre que ApiDocumentationMiddleware funciona via $app->use() (SPEC-022).
 */
class ApiDocumentationMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        Router::clear();
    }

    protected function tearDown(): void
    {
        Router::clear();
    }

    public function testDocsAndSwaggerAreServed(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->use(new ApiDocumentationMiddleware());

        $app->get(
            '/users',
            function ($req, $res) {
                return $res->json(['users' => []]);
            }
        );

        $docs = $app->handle(new ServerRequest('GET', '/docs'));
        $this->assertSame(200, $docs->getStatusCode());

        $spec = json_decode((string) $docs->getBody(), true);
        $this->assertSame('3.0.0', $spec['openapi']);
        $this->assertSame(Application::VERSION, $spec['info']['version']);

        $swagger = $app->handle(new ServerRequest('GET', '/swagger'));
        $this->assertSame(200, $swagger->getStatusCode());
        $this->assertStringContainsString('swagger-ui', (string) $swagger->getBody());
    }

    public function testNormalRouteStillWorksWithMiddlewareRegistered(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->use(new ApiDocumentationMiddleware());

        $app->get(
            '/users',
            function ($req, $res) {
                return $res->json(['users' => []]);
            }
        );

        $response = $app->handle(new ServerRequest('GET', '/users'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
