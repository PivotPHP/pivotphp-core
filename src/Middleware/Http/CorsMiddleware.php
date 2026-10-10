<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Http;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 CORS Middleware
 */
class CorsMiddleware implements MiddlewareInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            [
                'origin' => '*',
                'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
                'credentials' => false,
                'max_age' => 86400, // 24 hours
                'expose_headers' => [],
            ],
            $config
        );
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();

        // Handle preflight quickly (optimized)
        if ($method === 'OPTIONS') {
            return $this->handlePreflightOptimized($request);
        }

        // Process request normally
        $response = $handler->handle($request);

        // Add CORS headers efficiently
        return $this->addCorsHeadersOptimized($request, $response);
    }

    /**
     * Compatível apenas com PSR-15. Remove suporte a mocks legados.
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->process($request, $handler);
    }

    private function handlePreflightOptimized(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response(200);

        // Métodos sem espaço após vírgula
        $methods = $this->config['methods'];
        if (is_array($methods)) {
            $methods = implode(',', $methods);
        } else {
            $methods = str_replace(' ', '', (string)$methods);
        }

        // Build headers array for efficient processing
        $headers = [
            'Access-Control-Allow-Origin' => $this->getAllowedOrigin($request),
            'Access-Control-Allow-Methods' => $methods,
            'Access-Control-Allow-Headers' => str_replace(
                ' ',
                '',
                is_array($this->config['headers']) ?
                    implode(',', $this->config['headers']) :
                    (string)$this->config['headers']
            ),
            'Access-Control-Max-Age' => (string) $this->config['max_age']
        ];

        if ($this->config['credentials']) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        // Apply all headers at once
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    private function addCorsHeadersOptimized(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Métodos sem espaço após vírgula
        $methods = $this->config['methods'];
        if (is_array($methods)) {
            $methods = implode(',', $methods);
        } else {
            $methods = str_replace(' ', '', (string)$methods);
        }

        $headers = [
            'Access-Control-Allow-Origin' => $this->getAllowedOrigin($request),
            'Access-Control-Allow-Methods' => $methods,
            'Access-Control-Allow-Headers' => str_replace(
                ' ',
                '',
                is_array($this->config['headers']) ?
                    implode(',', $this->config['headers']) :
                    (string)$this->config['headers']
            )
        ];

        if ($this->config['credentials']) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        if (!empty($this->config['expose_headers'])) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $this->config['expose_headers']);
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    private function getAllowedOrigin(ServerRequestInterface $request): string
    {
        $origin = $request->getHeaderLine('Origin');

        if ($this->config['origin'] === '*') {
            return '*';
        }

        if (is_array($this->config['origin'])) {
            return in_array($origin, $this->config['origin']) ? $origin : 'null';
        }

        return $this->config['origin'];
    }
}
