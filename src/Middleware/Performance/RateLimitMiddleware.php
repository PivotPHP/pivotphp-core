<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Performance;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 Rate Limiting Middleware
 *
 * @deprecated v2.1.0 Use \PivotPHP\Core\Middleware\RateLimiter instead. This class uses $_SESSION which violates HTTP statelessness.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private array $options;

    public function __construct(array $options = [])
    {
        trigger_error('RateLimitMiddleware is deprecated and uses $_SESSION. Use RateLimiter instead.', E_USER_DEPRECATED);
        $this->options = array_merge(
            [
                'windowMs' => 900000, // 15 minutos
                'max' => 100,
                'message' => 'Too many requests, please try again later.',
                'statusCode' => 429,
                'keyGenerator' => null,
            ],
            $options
        );
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $key = $this->getKey($request);
        $now = time();
        $windowStart = $now - ($this->options['windowMs'] / 1000);

        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        // Limpa entradas antigas
        $_SESSION['rate_limit'] = array_filter(
            $_SESSION['rate_limit'],
            fn($timestamps) => is_array($timestamps)
                && count(
                    array_filter(
                        $timestamps,
                        fn($t) => $t > $windowStart
                    )
                ) > 0
        );
        if (!isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = [];
        }
        // Limpa entradas antigas para esta chave
        $_SESSION['rate_limit'][$key] = array_filter(
            $_SESSION['rate_limit'][$key],
            fn($timestamp) => $timestamp > $windowStart
        );
        $currentCount = count($_SESSION['rate_limit'][$key]);
        if ($currentCount >= $this->options['max']) {
            $factory = new \PivotPHP\Core\Http\Psr7\Factory\ResponseFactory();
            $response = $factory->createResponse($this->options['statusCode']);
            $body = json_encode(
                [
                    'error' => true,
                    'message' => $this->options['message']
                ]
            ) ?: '{"error":true}';
            $response->getBody()->write($body);
            return $response->withHeader('Content-Type', 'application/json');
        }
        // Registra esta requisição
        $_SESSION['rate_limit'][$key][] = $now;
        return $handler->handle($request);
    }

    private function getKey(ServerRequestInterface $request): string
    {
        if ($this->options['keyGenerator'] && is_callable($this->options['keyGenerator'])) {
            $result = call_user_func($this->options['keyGenerator'], $request);
            return is_string($result) ? $result : 'unknown';
        }
        $serverParams = $request->getServerParams();
        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? 'unknown';
        return is_string($remoteAddr) ? $remoteAddr : 'unknown';
    }
}
