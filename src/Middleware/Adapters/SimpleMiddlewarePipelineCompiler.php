<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Adapters;

use PivotPHP\Core\Contracts\MiddlewarePipelineCompilerInterface;

/**
 * Middleware Pipeline Compiler Simples (fallback)
 *
 * Implementa MiddlewarePipelineCompilerInterface com compilação básica
 * sem otimizações avançadas. Usado quando MiddlewarePipelineCompiler
 * não está disponível ou está desabilitado.
 */
class SimpleMiddlewarePipelineCompiler implements MiddlewarePipelineCompilerInterface
{
    /**
     * @var array<string, callable> Cache de pipelines compilados
     */
    private array $compiledPipelines = [];

    /**
     * {@inheritDoc}
     */
    public function compilePipeline(string $cacheKey, array $middlewares): callable
    {
        // Verificar se já está compilado
        if (isset($this->compiledPipelines[$cacheKey])) {
            return $this->compiledPipelines[$cacheKey];
        }

        // Compilar pipeline básico
        $pipeline = $this->buildPipeline($middlewares);

        // Armazenar no cache
        $this->compiledPipelines[$cacheKey] = $pipeline;

        return $pipeline;
    }

    /**
     * {@inheritDoc}
     */
    public function getCompiledPipeline(string $cacheKey): ?callable
    {
        return $this->compiledPipelines[$cacheKey] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(): void
    {
        // Nada a fazer no compilador simples
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(): void
    {
        $this->compiledPipelines = [];
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        return [
            'compiled_count' => count($this->compiledPipelines),
            'cache_size' => count($this->compiledPipelines),
        ];
    }

    /**
     * Construir pipeline simples a partir de middlewares
     *
     * @param array $middlewares
     * @return callable
     */
    private function buildPipeline(array $middlewares): callable
    {
        return function ($request, $response, $finalHandler = null) use ($middlewares) {
            // Construir stack de execução
            $handler = $finalHandler ?? function ($req, $res) {
                return $res;
            };

            // Iterar middlewares em ordem reversa para montar o stack
            foreach (array_reverse($middlewares) as $middleware) {
                $currentHandler = $handler;

                $handler = function ($req, $res) use ($middleware, $currentHandler) {
                    if (is_callable($middleware)) {
                        return call_user_func($middleware, $req, $res, $currentHandler);
                    }
                    return $currentHandler($req, $res);
                };
            }

            // Executar pipeline
            return $handler($request, $response);
        };
    }
}
