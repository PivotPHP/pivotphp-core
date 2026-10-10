<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Factory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use PivotPHP\Core\Http\Psr7\Response;
use PivotPHP\Core\Http\Psr7\Stream;

/**
 * PSR-17 Response Factory — criação direta (sem pooling).
 */
class ResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $resource = fopen('php://temp', 'r+');
        if ($resource === false) {
            throw new \RuntimeException('Unable to create temporary stream');
        }

        return new Response($code, [], new Stream($resource), '1.1', $reasonPhrase);
    }
}
