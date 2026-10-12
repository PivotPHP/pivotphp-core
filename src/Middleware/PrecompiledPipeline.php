<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Pipeline pré-compilado de middlewares imutável e reutilizável (Padrão Chain of Responsibility).
 */
final class PrecompiledPipeline
{
    private RequestHandlerInterface $pipeline;

    /**
     * @param array<int, MiddlewareInterface> $middlewares
     */
    public function __construct(array $middlewares, RequestHandlerInterface $finalHandler)
    {
        $handler = $finalHandler;
        for ($i = count($middlewares) - 1; $i >= 0; $i--) {
            $handler = new MiddlewareHandler($middlewares[$i], $handler);
        }
        $this->pipeline = $handler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->pipeline->handle($request);
    }
}
