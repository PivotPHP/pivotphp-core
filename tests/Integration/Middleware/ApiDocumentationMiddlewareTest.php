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

    public function testPathTemplatingAndParametersGeneration(): void
    {
        $app = new Application(__DIR__ . '/../../..');

        $app->use(new ApiDocumentationMiddleware());

        $app->get('/users/:id', fn($req, $res) => $res->json([]));
        $app->get('/n/:id<\d+>', fn($req, $res) => $res->json([]));
        $app->get('/posts/{slug}', fn($req, $res) => $res->json([]));
        $app->get('/tags/{slug<[a-z0-9-]+>}', fn($req, $res) => $res->json([]));
        $app->get('/users/:userId/posts/:postId', fn($req, $res) => $res->json([]));

        $docs = $app->handle(new ServerRequest('GET', '/docs'));
        $this->assertSame(200, $docs->getStatusCode());

        $spec = json_decode((string) $docs->getBody(), true);
        $paths = $spec['paths'];

        $this->assertArrayHasKey('/users/{id}', $paths);
        $this->assertArrayNotHasKey('/users/:id', $paths);
        $this->assertSame(
            [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'string']
                ]
            ],
            $paths['/users/{id}']['get']['parameters']
        );

        $this->assertArrayHasKey('/n/{id}', $paths);
        $this->assertArrayNotHasKey('/n/:id<\d+>', $paths);

        $this->assertArrayHasKey('/posts/{slug}', $paths);
        $this->assertArrayHasKey('/tags/{slug}', $paths);
        $this->assertArrayNotHasKey('/tags/{slug<[a-z0-9-]+>}', $paths);

        $this->assertArrayHasKey('/users/{userId}/posts/{postId}', $paths);
        $this->assertCount(2, $paths['/users/{userId}/posts/{postId}']['get']['parameters']);
        $this->assertSame('userId', $paths['/users/{userId}/posts/{postId}']['get']['parameters'][0]['name']);
        $this->assertSame('postId', $paths['/users/{userId}/posts/{postId}']['get']['parameters'][1]['name']);
    }
}
