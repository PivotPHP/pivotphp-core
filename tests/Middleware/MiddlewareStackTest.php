<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\MiddlewareStack;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareStackTest extends TestCase
{
    private function passthrough(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }

    public function testBasicMiddlewareStack(): void
    {
        $stack = new MiddlewareStack();

        $this->assertInstanceOf(MiddlewareStack::class, $stack);
    }

    public function testMiddlewareStackAddMiddleware(): void
    {
        $stack = new MiddlewareStack();
        $stack->add($this->passthrough());

        $this->assertCount(1, $stack->getMiddlewares());
    }

    public function testMiddlewareStackHasMiddlewares(): void
    {
        $stack = new MiddlewareStack();

        $this->assertIsArray($stack->getMiddlewares());
        $this->assertCount(0, $stack->getMiddlewares());
    }

    public function testMiddlewareStackMultipleMiddlewares(): void
    {
        $stack = new MiddlewareStack();
        $stack->add($this->passthrough());
        $stack->add($this->passthrough());

        $this->assertCount(2, $stack->getMiddlewares());
    }

    public function testMiddlewareStackEmpty(): void
    {
        $stack = new MiddlewareStack();

        $this->assertTrue($stack->isEmpty());
    }
}
