<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Factory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use PivotPHP\Core\Http\Psr7\Response;
use PivotPHP\Core\Http\Psr7\Stream;
use PivotPHP\Core\Contracts\Psr7\ResponsePoolInterface;
use PivotPHP\Core\Http\Psr7\Adapters\ResponsePoolAdapter;

/**
 * PSR-17 Response Factory implementation with object pooling optimization
 */
class ResponseFactory implements ResponseFactoryInterface
{
    public function __construct(private ?ResponsePoolInterface $responsePool = null)
    {
    }

    private function pool(): ResponsePoolInterface
    {
        return $this->responsePool ?? new ResponsePoolAdapter();
    }

    /**
     * Create a new response using object pool
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        // Use object pool for better performance
        return $this->pool()->getResponse($code);
    }

    /**
     * Create response with JSON body (optimized)
     */
    public function createJsonResponse(array $data, int $code = 200): ResponseInterface
    {
        return $this->pool()->getJsonResponse($data, $code);
    }

    /**
     * Create response with text body (optimized)
     */
    public function createTextResponse(string $text, int $code = 200): ResponseInterface
    {
        return $this->pool()->getTextResponse($text, $code);
    }

    /**
     * Create response with HTML body (optimized)
     */
    public function createHtmlResponse(string $html, int $code = 200): ResponseInterface
    {
        return $this->pool()->getHtmlResponse($html, $code);
    }

    /**
     * Create legacy response without pooling (for compatibility)
     */
    public function createResponseLegacy(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $resource = fopen('php://temp', 'r+');
        if ($resource === false) {
            throw new \RuntimeException('Unable to create temporary stream');
        }
        $stream = new Stream($resource);
        return new Response($code, [], $stream, '1.1', $reasonPhrase);
    }

    /**
     * Warm up the response pool
     */
    public static function warmUp(): void
    {
        (new ResponsePoolAdapter())->warmUp();
    }

    /**
     * Get pool statistics
     */
    public static function getPoolStats(): array
    {
        return (new ResponsePoolAdapter())->getStats();
    }
}
