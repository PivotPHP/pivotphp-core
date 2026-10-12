<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use PivotPHP\Http\ExpressResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adaptador para middlewares Express callable($req, $res, $next) (Padrão Adapter).
 */
final class ExpressMiddlewareAdapter implements MiddlewareInterface
{
    /** @var callable */
    private $middleware;

    public function __construct(callable $middleware)
    {
        $this->middleware = $middleware;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = new ExpressResponse();
        $downstream = null;

        // $next() accepts an optional (modified) request and runs the rest of the
        // pipeline once; its response is kept even if the middleware does not return it.
        $next = static function (?ServerRequestInterface $nextRequest = null) use (
            $handler,
            $request,
            &$downstream
        ): ResponseInterface {
            return $downstream = $handler->handle($nextRequest ?? $request);
        };

        $result = ($this->middleware)($request, $response, $next);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if ($result instanceof ExpressResponse) {
            return $result->psr7();
        }

        return $downstream ?? $handler->handle($request);
    }
}
