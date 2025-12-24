<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Factory;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\ExpressRequest;
use PivotPHP\Core\Http\ExpressResponse;
use PivotPHP\Core\Http\Pool\Psr7Pool;
use PivotPHP\Core\Http\Psr7\Uri;
use PivotPHP\Core\Http\Psr7\Stream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Factory otimizada para objetos HTTP com pooling
 *
 * Esta factory utiliza object pooling para criar objetos HTTP de forma mais eficiente,
 * reduzindo a pressão sobre o garbage collector e melhorando a performance.
 *
 * @package PivotPHP\Core\Http\Factory
 * @since 2.1.1
 */
class OptimizedHttpFactory
{
    /**
     * Indica se o pool foi inicializado
     */
    private static bool $initialized = false;

    /**
     * Configurações do pool
     */
    private static array $config = [
        'enable_pooling' => true,
        'warm_up_pools' => true,
        'max_pool_size' => 100,
        'enable_metrics' => true,
    ];

    /**
     * Inicializa a factory e o pool
     */
    public static function initialize(array $config = []): void
    {
        if (self::$initialized) {
            return;
        }

        self::$config = array_merge(self::$config, $config);

        if (self::$config['warm_up_pools']) {
            Psr7Pool::warmUp();
        }

        self::$initialized = true;
    }

    /**
     * Cria Request híbrido otimizado
     *
     * Creates an Express.js-style Request that wraps a PSR-7 ServerRequest
     */
    public static function createRequest(
        string $method,
        string $path,
        string $pathCallable
    ): Request {
        self::ensureInitialized();

        // Create PSR-7 ServerRequest first
        $uri = self::createUri($pathCallable);
        $serverParams = $_SERVER ?? [];
        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];

        $psr7Request = Psr7Pool::getServerRequest(
            $method,
            $uri,
            self::createStream(''),
            $headers,
            '1.1',
            $serverParams
        );

        // Parse query parameters from $_SERVER['QUERY_STRING'] if available
        if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') {
            parse_str($_SERVER['QUERY_STRING'], $queryParams);
            $psr7Request = $psr7Request->withQueryParams($queryParams);
        }

        // Set parsed body from $_POST if available
        if (!empty($_POST)) {
            $psr7Request = $psr7Request->withParsedBody($_POST);
        }

        // Set uploaded files from $_FILES if available
        if (!empty($_FILES)) {
            $psr7Request = $psr7Request->withUploadedFiles($_FILES);
        }

        // Wrap with Express.js adapter
        return new ExpressRequest($psr7Request, $path, $pathCallable);
    }

    /**
     * Cria Response híbrido otimizado
     *
     * Creates an Express.js-style Response that wraps a PSR-7 Response
     */
    public static function createResponse(): Response
    {
        self::ensureInitialized();

        // Create PSR-7 Response from pool
        $psr7Response = Psr7Pool::getResponse();

        // Wrap with Express.js adapter
        return new ExpressResponse($psr7Response);
    }

    /**
     * Cria ServerRequest PSR-7 otimizado
     */
    public static function createServerRequest(
        string $method,
        string $uri,
        array $serverParams = [],
        array $headers = []
    ): ServerRequestInterface {
        self::ensureInitialized();

        return Psr7Pool::getServerRequest(
            $method,
            self::createUri($uri),
            self::createStream(''),
            $headers,
            '1.1',
            $serverParams
        );
    }

    /**
     * Cria Response PSR-7 otimizado
     */
    public static function createPsr7Response(
        int $statusCode = 200,
        array $headers = [],
        string $body = ''
    ): ResponseInterface {
        self::ensureInitialized();

        return Psr7Pool::getResponse(
            $statusCode,
            $headers,
            self::createStream($body)
        );
    }

    /**
     * Cria Stream otimizado
     */
    public static function createStream(string $content = ''): StreamInterface
    {
        self::ensureInitialized();

        if (self::$config['enable_pooling']) {
            return Psr7Pool::getStream($content);
        }

        return Stream::createFromString($content);
    }

    /**
     * Cria Stream a partir de arquivo
     */
    public static function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        self::ensureInitialized();
        return Stream::createFromFile($filename, $mode);
    }

    /**
     * Cria Uri otimizado
     */
    public static function createUri(string $uri = ''): UriInterface
    {
        self::ensureInitialized();

        if (self::$config['enable_pooling']) {
            return Psr7Pool::getUri($uri);
        }

        return new Uri($uri);
    }

    /**
     * Cria Request a partir de globais PHP
     */
    public static function createRequestFromGlobals(): Request
    {
        self::ensureInitialized();
        return Request::createFromGlobals();
    }

    /**
     * Retorna objeto para o pool (uso manual)
     */
    public static function returnToPool(object $object): void
    {
        if (!self::$config['enable_pooling']) {
            return;
        }

        if ($object instanceof ServerRequestInterface) {
            Psr7Pool::returnServerRequest($object);
        } elseif ($object instanceof ResponseInterface) {
            Psr7Pool::returnResponse($object);
        } elseif ($object instanceof StreamInterface) {
            Psr7Pool::returnStream($object);
        } elseif ($object instanceof UriInterface) {
            Psr7Pool::returnUri($object);
        }
    }

    /**
     * Obtém estatísticas do pool
     */
    public static function getPoolStats(): array
    {
        if (!self::$config['enable_metrics']) {
            return ['metrics_disabled' => true];
        }

        return Psr7Pool::getStats();
    }

    /**
     * Enable pooling
     */
    public static function enablePooling(): void
    {
        self::$config['enable_pooling'] = true;
    }

    /**
     * Disable pooling
     */
    public static function disablePooling(): void
    {
        self::$config['enable_pooling'] = false;
        Psr7Pool::clearPools();
    }

    /**
     * Limpa todos os pools
     */
    public static function clearPools(): void
    {
        Psr7Pool::clearPools();
    }

    /**
     * Pré-aquece os pools
     */
    public static function warmUpPools(): void
    {
        Psr7Pool::warmUp();
    }

    /**
     * Obtém configuração atual
     */
    public static function getConfig(): array
    {
        return self::$config;
    }

    /**
     * Atualiza configuração
     */
    public static function updateConfig(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Verifica se o pooling está habilitado
     */
    public static function isPoolingEnabled(): bool
    {
        return self::$config['enable_pooling'];
    }

    /**
     * Habilita/desabilita pooling
     */
    public static function setPoolingEnabled(bool $enabled): void
    {
        self::$config['enable_pooling'] = $enabled;
    }

    /**
     * Obtém métricas de performance
     */
    public static function getPerformanceMetrics(): array
    {
        if (!self::$config['enable_metrics']) {
            return ['metrics_disabled' => true];
        }

        $stats = Psr7Pool::getStats();

        return [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
            ],
            'pool_efficiency' => $stats['efficiency'],
            'pool_usage' => $stats['pool_sizes'],
            'object_reuse' => $stats['usage'],
            'recommendations' => self::generateRecommendations($stats),
        ];
    }

    /**
     * Gera recomendações baseadas nas métricas
     */
    private static function generateRecommendations(array $stats): array
    {
        $recommendations = [];

        foreach ($stats['efficiency'] as $type => $rate) {
            if ($rate < 50) {
                $recommendations[] = "Low {$type} ({$rate}%) - consider increasing pool size or warming up";
            } elseif ($rate > 90) {
                $recommendations[] = "Excellent {$type} ({$rate}%) - optimal pool utilization";
            }
        }

        return $recommendations ?: ['Pool performance is within acceptable ranges'];
    }

    /**
     * Garante que a factory foi inicializada
     */
    private static function ensureInitialized(): void
    {
        if (!self::$initialized) {
            self::initialize();
        }
    }

    /**
     * Reseta a factory (útil para testes)
     */
    public static function reset(): void
    {
        self::$initialized = false;
        self::$config = [
            'enable_pooling' => true,
            'warm_up_pools' => true,
            'max_pool_size' => 100,
            'enable_metrics' => true,
        ];
        self::clearPools();
    }
}
