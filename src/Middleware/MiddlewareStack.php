<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Contracts\SerializationCacheInterface;
use PivotPHP\Core\Contracts\MiddlewarePipelineCompilerInterface;
use PivotPHP\Core\Utils\Adapters\SerializationCacheAdapter;
use PivotPHP\Core\Middleware\Adapters\MiddlewarePipelineCompilerAdapter;
use PivotPHP\Core\Middleware\Adapters\SimpleMiddlewarePipelineCompiler;

/**
 * Classe para gerenciar e executar uma stack de middlewares com otimizações.
 * Incorpora cache de pipelines, otimizações de execução e estatísticas.
 */
class MiddlewareStack
{
    /**
     * @var array<callable>
     */
    private array $middlewares = [];

    /**
     * Cache de pipelines compilados
     * @var array<string, callable>
     */
    private static array $compiledPipelines = [];

    /**
     * Estatísticas de execução
     * @var array<string, array>
     */
    private static array $stats = [];

    /**
     * Middlewares pré-processados por grupo
     * @var array<string, callable>
     */
    private static array $groupMiddlewares = [];

    /**
     * Pipeline compiler instance
     * @var MiddlewarePipelineCompilerInterface|null
     */
    private static ?MiddlewarePipelineCompilerInterface $compiler = null;

    /**
     * Serialization cache (injeção)
     */
    private static ?SerializationCacheInterface $serializationCache = null;

    /**
     * Adiciona um middleware à stack.
     *
     * @param  callable $middleware
     * @return void
     */
    public function add(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Executa todos os middlewares na stack com otimizações.
     *
     * @param  Request  $request
     * @param  Response $response
     * @param  callable $finalHandler
     * @param  string|null $cacheKey Chave para cache do pipeline compilado
     * @return mixed
     */
    public function execute(
        Request $request,
        Response $response,
        callable $finalHandler,
        ?string $cacheKey = null
    ) {
        // Se não há middlewares, executa o handler final
        if (empty($this->middlewares)) {
            return $finalHandler($request, $response);
        }

        // Try to use advanced pre-compiled pipeline first
        $compiler = self::getCompiler();
        if ($cacheKey) {
            $compiled = $compiler->getCompiledPipeline($cacheKey);
            if ($compiled !== null) {
                return $compiled($request, $response, $finalHandler);
            }
        }

        // Use legacy compiled pipelines as fallback
        if ($cacheKey && isset(self::$compiledPipelines[$cacheKey])) {
            $pipeline = self::$compiledPipelines[$cacheKey];
            return $pipeline($request, $response, $finalHandler);
        }

        // Create and compile new optimized pipeline
        $pipeline = $this->compileOptimizedPipeline($this->middlewares, $cacheKey);

        // Also compile with advanced compiler for future use
        if ($cacheKey) {
            $compiler->compilePipeline($cacheKey, $this->middlewares);
        }

        return $pipeline($request, $response, $finalHandler);
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
     *
     * @return void
     */
    public function clear(): void
    {
        $this->middlewares = [];
    }

    /**
     * Conta o número de middlewares.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->middlewares);
    }

    /**
     * Verifica se a stack está vazia.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->middlewares);
    }

    /**
     * Compila pipeline otimizado de middlewares
     */
    public function compileOptimizedPipeline(array $middlewares, ?string $cacheKey = null): callable
    {
        if ($cacheKey && isset(self::$compiledPipelines[$cacheKey])) {
            return self::$compiledPipelines[$cacheKey];
        }

        $startTime = microtime(true);

        // Se não há middlewares, retorna função pass-through
        if (empty($middlewares)) {
            $pipeline = function ($req, $resp, $next) {
                return $next($req, $resp);
            };

            if ($cacheKey) {
                self::$compiledPipelines[$cacheKey] = $pipeline;
            }
            return $pipeline;
        }

        // Cria pipeline otimizado
        $pipeline = $this->createOptimizedPipeline($middlewares);

        // Cache do pipeline
        if ($cacheKey) {
            self::$compiledPipelines[$cacheKey] = $pipeline;
        }

        // Registra estatísticas
        $compilationTime = (microtime(true) - $startTime) * 1000;
        self::$stats[$cacheKey ?? 'anonymous'] = [
            'middleware_count' => count($middlewares),
            'compilation_time_ms' => $compilationTime,
            'executions' => 0,
            'total_execution_time_ms' => 0,
            'avg_execution_time_ms' => 0
        ];

        return $pipeline;
    }

    /**
     * Cria pipeline otimizado evitando closures desnecessárias
     */
    private function createOptimizedPipeline(array $middlewares): callable
    {
        // Reverse middlewares para execução correta
        $reversedMiddlewares = array_reverse($middlewares);

        return function ($req, $resp, $finalHandler = null) use ($reversedMiddlewares) {
            $startTime = microtime(true);

            // Cria stack de execução otimizada
            $stack = $finalHandler ?? function ($req, $resp) {
                return $resp;
            };

            // Compila middlewares em uma única função
            foreach ($reversedMiddlewares as $middleware) {
                $currentStack = $stack;
                $stack = function ($req, $resp) use ($middleware, $currentStack) {
                    return call_user_func($middleware, $req, $resp, $currentStack);
                };
            }

            // Executa o stack compilado
            $result = $stack($req, $resp);

            // Atualiza estatísticas (apenas se tiver cache key)
            $executionTime = (microtime(true) - $startTime) * 1000;
            self::updateExecutionStats('compiled_pipeline', $executionTime);

            return $result;
        };
    }

    /**
     * Compila middlewares específicos para um grupo
     * @param array<callable> $middlewares
     */
    public static function compileGroupMiddlewares(string $groupPrefix, array $middlewares): void
    {
        $cacheKey = 'group:' . $groupPrefix;
        $stack = new self();
        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $stack->add($middleware);
            }
        }
        self::$groupMiddlewares[$groupPrefix] = $stack->compileOptimizedPipeline($middlewares, $cacheKey);
    }

    /**
     * Obtém pipeline pré-compilado para um grupo
     */
    public static function getGroupPipeline(string $groupPrefix): ?callable
    {
        $pipeline = self::$groupMiddlewares[$groupPrefix] ?? null;
        return is_callable($pipeline) ? $pipeline : null;
    }

    /**
     * Executa pipeline otimizado para um grupo específico
     */
    public static function executeForGroup(
        string $groupPrefix,
        Request $req,
        Response $resp,
        callable $finalHandler
    ): mixed {
        $pipeline = self::getGroupPipeline($groupPrefix);

        if ($pipeline) {
            return $pipeline($req, $resp, $finalHandler);
        }

        // Fallback para execução normal
        return $finalHandler($req, $resp);
    }

    /**
     * Benchmark de pipeline específico
     */
    public static function benchmarkPipeline(array $middlewares, int $iterations = 1000): array
    {
        // Usa cache de serialização otimizado para gerar chave
        $serializedData = self::getSerializationCache()->getSerializedData($middlewares);
        $cacheKey = 'benchmark:' . md5($serializedData);

        // Compila pipeline
        $compilationStart = microtime(true);
        $stack = new self();
        foreach ($middlewares as $middleware) {
            $stack->add($middleware);
        }
        $pipeline = $stack->compileOptimizedPipeline($middlewares, $cacheKey);
        $compilationTime = (microtime(true) - $compilationStart) * 1000;

        // Mock request/response para benchmark
        $mockReq = new \stdClass();
        $mockResp = new \stdClass();
        $mockNext = function ($req, $resp) {
            return $resp;
        };

        // Executa benchmark
        $executionStart = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $pipeline($mockReq, $mockResp, $mockNext);
        }

        $executionEnd = microtime(true);
        $totalExecutionTime = ($executionEnd - $executionStart) * 1000;

        return [
            'middleware_count' => count($middlewares),
            'compilation_time_ms' => round($compilationTime, 3),
            'iterations' => $iterations,
            'total_execution_time_ms' => round($totalExecutionTime, 3),
            'avg_execution_time_ms' => round($totalExecutionTime / $iterations, 6),
            'ops_per_second' => round($iterations / (($executionEnd - $executionStart)), 0)
        ];
    }

    /**
     * Atualiza estatísticas de execução
     */
    private static function updateExecutionStats(string $key, float $executionTime): void
    {
        if (!isset(self::$stats[$key])) {
            self::$stats[$key] = [
                'executions' => 0,
                'total_execution_time_ms' => 0,
                'avg_execution_time_ms' => 0
            ];
        }

        self::$stats[$key]['executions']++;
        self::$stats[$key]['total_execution_time_ms'] += $executionTime;
        self::$stats[$key]['avg_execution_time_ms'] =
            self::$stats[$key]['total_execution_time_ms'] / self::$stats[$key]['executions'];
    }

    /**
     * Obtém estatísticas de todos os pipelines
     */
    public static function getStats(): array
    {
        return self::$stats;
    }

    /**
     * Limpa caches e estatísticas incluindo cache de serialização
     */
    public static function clearCache(): void
    {
        self::$compiledPipelines = [];
        self::$stats = [];
        self::$groupMiddlewares = [];

        // Limpa cache de serialização relacionado
        self::getSerializationCache()->clearCache();
    }

    /**
     * Detecta middlewares redundantes
     */
    public static function detectRedundantMiddlewares(array $middlewares): array
    {
        $signatures = [];
        $redundant = [];

        foreach ($middlewares as $index => $middleware) {
            $signature = self::getMiddlewareSignature($middleware);

            if (isset($signatures[$signature])) {
                $redundant[] = [
                    'index' => $index,
                    'duplicate_of' => $signatures[$signature],
                    'signature' => $signature
                ];
            } else {
                $signatures[$signature] = $index;
            }
        }

        return $redundant;
    }

    /**
     * Gera assinatura de um middleware para detecção de duplicatas
     */
    private static function getMiddlewareSignature(callable $middleware): string
    {
        if (is_string($middleware)) {
            return 'string:' . $middleware;
        }

        if (is_array($middleware)) {
            return 'array:' . implode('::', $middleware);
        }

        if ($middleware instanceof \Closure) {
            $reflection = new \ReflectionFunction($middleware);
            return 'closure:' . $reflection->getFileName() . ':' . $reflection->getStartLine();
        }

        if (is_object($middleware)) {
            return 'object:' . spl_object_hash($middleware);
        }

        return 'unknown:' . gettype($middleware);
    }

    /**
     * Otimiza automaticamente um array de middlewares
     */
    public static function optimize(array $middlewares): array
    {
        // Remove middlewares redundantes
        $redundant = self::detectRedundantMiddlewares($middlewares);
        $redundantIndexes = array_column($redundant, 'index');

        $optimized = [];
        foreach ($middlewares as $index => $middleware) {
            if (!in_array($index, $redundantIndexes)) {
                $optimized[] = $middleware;
            }
        }

        return $optimized;
    }

    /**
     * Get or create pipeline compiler instance
     */
    private static function getCompiler(): MiddlewarePipelineCompilerInterface
    {
        if (self::$compiler === null) {
            if (class_exists(MiddlewarePipelineCompiler::class)) {
                self::$compiler = new MiddlewarePipelineCompilerAdapter();
            } else {
                self::$compiler = new SimpleMiddlewarePipelineCompiler();
            }
        }
        return self::$compiler;
    }

    /**
     * Setter para compiler (injeção)
     */
    public static function setCompiler(MiddlewarePipelineCompilerInterface $compiler): void
    {
        self::$compiler = $compiler;
    }

    /**
     * Setter para cache de serialização
     */
    public static function setSerializationCache(SerializationCacheInterface $cache): void
    {
        self::$serializationCache = $cache;
    }

    /**
     * Getter para cache de serialização com fallback
     */
    private static function getSerializationCache(): SerializationCacheInterface
    {
        return self::$serializationCache ?? new SerializationCacheAdapter();
    }
}
