<?php

namespace PivotPHP\Core\Tests\Security;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Response;

class DummyHandler implements RequestHandlerInterface
{
    public bool $called = false;
    public ServerRequestInterface $request;
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->called = true;
        $this->request = $request;
        return new Response(200);
    }
}
