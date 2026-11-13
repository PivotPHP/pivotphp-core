<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Core\Fixtures;

use PivotPHP\Http\ExpressRequest;
use PivotPHP\Http\ExpressResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Controller usado apenas pelo teste de handlers preguiçosos: nenhum outro teste deve referenciá-lo,
 * para que seja possível verificar em que momento a classe é carregada.
 */
final class LazyLoadedController
{
    public function show(ExpressRequest $request, ExpressResponse $response): ResponseInterface
    {
        return $response->json(['handler' => 'instance']);
    }

    public static function ping(ExpressRequest $request, ExpressResponse $response): ResponseInterface
    {
        return $response->json(['handler' => 'static']);
    }
}
