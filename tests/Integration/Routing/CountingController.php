<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Routing;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

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

    public function id(Request $req, Response $res): Response
    {
        return $res->json(['id' => $this->id, 'prefix' => $this->prefix]);
    }
}
