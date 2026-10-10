<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use PivotPHP\Http\ExpressRequest;
use PivotPHP\Http\ExpressResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Controller com dependência de construtor (não instanciável via `new` sem
 * argumentos), usado para provar a resolução via container em
 * [Classe::class, 'métodoDeInstância'].
 */
final class DiGreetingController
{
    public function __construct(private string $prefix)
    {
    }

    public function greet(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        return $res->json(['greeting' => $this->prefix . ' hello']);
    }
}
