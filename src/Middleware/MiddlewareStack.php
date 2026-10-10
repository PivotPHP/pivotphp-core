<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

/**
 * Pilha de middlewares simples (sem cache/compilação de pipeline).
 */
class MiddlewareStack
{
    /**
     * @var array<callable>
     */
    private array $middlewares = [];

    /**
     * Adiciona um middleware à stack.
     */
    public function add(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Executa todos os middlewares na stack.
     *
     * @return mixed
     */
    public function execute(
        Request $request,
        Response $response,
        callable $finalHandler
    ) {
        if (empty($this->middlewares)) {
            return $finalHandler($request, $response);
        }

        $stack = $finalHandler;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $current = $stack;
            $stack = static function ($req, $res) use ($middleware, $current) {
                return $middleware($req, $res, $current);
            };
        }

        return $stack($request, $response);
    }

    /**
     * Obtém todos os middlewares.
     *
     * @return array<callable>
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
        return empty($this->middlewares);
    }
}
