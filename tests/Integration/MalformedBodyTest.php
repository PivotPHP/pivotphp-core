<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;

/**
 * A malformed JSON body is a client error: handle() returns 400 instead of throwing.
 */
class MalformedBodyTest extends TestCase
{
    public function testInvalidJsonBodyReturns400(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $app->post('/items', fn ($req, $res) => $res->json(['ok' => true]));

        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('POST', '/items')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream('{invalid'));

        $response = $app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testValidJsonBodyReachesTheRoute(): void
    {
        $app = new Application(__DIR__ . '/../..');
        $app->post('/items', fn ($req, $res) => $res->status(201)->json(['name' => $req->input('name')]));

        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('POST', '/items')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream('{"name":"book"}'));

        $response = $app->handle($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"name":"book"}', (string) $response->getBody());
    }
}
