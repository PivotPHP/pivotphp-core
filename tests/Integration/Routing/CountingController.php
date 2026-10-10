<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use PivotPHP\Http\ExpressRequest;
use PivotPHP\Http\ExpressResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Controller com dependência de construtor e contador de instâncias, para
 * verificar a resolução por requisição (SPEC-041).
 */
final class CountingController
{
    private static int $instances = 0;

    private int $id;

    public function __construct(private string $prefix)
    {
        self::$instances++;
        $this->id = self::$instances;
    }

    public static function reset(): void
    {
        self::$instances = 0;
    }

    public function id(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        return $res->json(['id' => $this->id, 'prefix' => $this->prefix]);
    }
}
