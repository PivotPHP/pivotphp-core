<?php

/**
 * PivotPHP — Custom middleware (callable and PSR-15)
 *
 * Callable middleware: fn (ServerRequestInterface $req, ExpressResponse $res, callable $next).
 *  - $next() runs the rest of the pipeline and returns the PSR-7 response;
 *  - $next($req->withAttribute(...)) forwards a modified request;
 *  - return $res->...->json(...) to answer without calling the route.
 * Any PSR-15 MiddlewareInterface also works with $app->use().
 *
 * Run:   php -S localhost:8000 examples/03-middleware/custom-middleware.php
 * Try:   curl -i http://localhost:8000/
 *        curl -i http://localhost:8000/maintenance
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware: adds the processing time to the response.
 */
final class TimingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = hrtime(true);
        $response = $handler->handle($request);

        return $response->withHeader('X-Response-Time', sprintf('%.2fms', (hrtime(true) - $start) / 1e6));
    }
}

$app = new Application();

$app->use(new TimingMiddleware());

// Request id: forwarded to the route as an attribute and echoed in the response
$app->use(function ($req, $res, $next) {
    $id = bin2hex(random_bytes(8));
    $response = $next($req->withAttribute('request_id', $id));

    return $response->withHeader('X-Request-Id', $id);
});

// Short-circuit: answers without reaching the route
$app->use(function ($req, $res, $next) {
    if ($req->getUri()->getPath() === '/maintenance') {
        return $res->status(503)->header('Retry-After', '120')->json(['error' => 'Under maintenance']);
    }

    return $next();
});

$app->get('/', fn ($req, $res) => $res->json([
    'request_id' => $req->psr7()->getAttribute('request_id'),
]));

$app->run();
