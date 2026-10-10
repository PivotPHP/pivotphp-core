<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Pilha de middlewares PSR-15.
 */
final class MiddlewareStack
{
    /**
     * @var array<int, MiddlewareInterface>
     */
    private array $middlewares = [];

    /**
     * Adiciona um middleware PSR-15 à stack.
     */
    public function add(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Executa a pipeline PSR-15, delegando ao handler final quando não há
     * mais middlewares.
     */
    public function execute(
        ServerRequestInterface $request,
        RequestHandlerInterface $finalHandler
    ): ResponseInterface {
        $next = $finalHandler;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = new class ($middleware, $next) implements RequestHandlerInterface {
                private MiddlewareInterface $middleware;

                private RequestHandlerInterface $next;

                public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $next)
                {
                    $this->middleware = $middleware;
                    $this->next = $next;
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        return $next->handle($request);
    }

    /**
     * @return array<int, MiddlewareInterface>
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Limpa todos os middlewares.
     */
    public function clear(): void
    {
        $this->middlewares = [];
    }

    /**
     * Conta o número de middlewares.
     */
    public function count(): int
    {
        return count($this->middlewares);
    }

    /**
     * Verifica se a stack está vazia.
     */
    public function isEmpty(): bool
    {
        return $this->middlewares === [];
    }
}
