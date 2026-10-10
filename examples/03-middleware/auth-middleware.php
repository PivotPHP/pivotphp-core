<?php

/**
 * PivotPHP — API key authentication with a custom PSR-15 middleware
 *
 * For JWT use pivotphp/security (see examples/06-security/jwt-auth.php). This example shows how to
 * write your own authentication middleware: fail closed (401) and pass the identity to the route
 * as a request attribute.
 *
 * Run:   php -S localhost:8000 examples/03-middleware/auth-middleware.php
 * Try:   curl -i http://localhost:8000/api/me
 *        curl -i http://localhost:8000/api/me -H 'X-API-Key: key-alice'
 *        curl http://localhost:8000/health
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Http\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ApiKeyMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string, string> $keys     API key => user id (load from a secret store in production)
     * @param list<string>          $public   paths that skip authentication
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responses,
        private readonly array $keys,
        private readonly array $public = [],
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getUri()->getPath(), $this->public, true)) {
            return $handler->handle($request);
        }

        $key = $request->getHeaderLine('X-API-Key');
        foreach ($this->keys as $validKey => $userId) {
            if ($key !== '' && hash_equals($validKey, $key)) {
                return $handler->handle($request->withAttribute('user', $userId));
            }
        }

        $response = $this->responses->createResponse(401)->withHeader('Content-Type', 'application/json');
        $response->getBody()->write('{"error":"Invalid or missing API key"}');

        return $response;
    }
}

$app = new Application();

$app->use(new ApiKeyMiddleware(new Psr17Factory(), ['key-alice' => 'alice'], ['/health']));

$app->get('/health', fn ($req, $res) => $res->json(['status' => 'ok']));
$app->get('/api/me', fn ($req, $res) => $res->json(['user' => $req->psr7()->getAttribute('user')]));

$app->run();
