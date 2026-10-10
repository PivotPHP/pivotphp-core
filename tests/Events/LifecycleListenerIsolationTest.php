<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Events;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Events\RequestReceived;
use PivotPHP\Core\Events\ResponseSent;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stringable;

/**
 * A failing lifecycle listener (logging/metrics) must not change the response (SPEC-085).
 */
class LifecycleListenerIsolationTest extends TestCase
{
    private Application $app;

    /** @var AbstractLogger&object{messages: list<string>} */
    private AbstractLogger $logger;

    protected function setUp(): void
    {
        $this->app = new Application(__DIR__ . '/../..');
        $this->app->boot(); // the logging provider binds the logger on boot; replace it afterwards
        $this->logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $messages = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $detail = is_string($context['message'] ?? null) ? $context['message'] : '';
                $this->messages[] = (string) $message . ' ' . $detail;
            }
        };
        $this->app->getContainer()->instance(LoggerInterface::class, $this->logger);
        $this->app->get('/ok', fn ($req, $res) => $res->json(['ok' => true]));
        $this->app->get(
            '/fail',
            function (): void {
                throw new RuntimeException('route failed');
            }
        );
    }

    public function testFailingRequestReceivedListenerDoesNotBreakTheRequest(): void
    {
        $this->app->on(
            RequestReceived::class,
            function (): void {
                throw new RuntimeException('metrics down');
            }
        );

        $response = $this->app->handle(new ServerRequest('GET', '/ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('metrics down', implode("\n", $this->logger->messages));
    }

    public function testFailingResponseSentListenerKeepsTheResponse(): void
    {
        $this->app->on(
            ResponseSent::class,
            function (): void {
                throw new RuntimeException('audit down');
            }
        );

        $response = $this->app->handle(new ServerRequest('GET', '/ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"ok":true}', (string) $response->getBody());
    }

    public function testResponseSentIsDispatchedOnceEvenWhenTheRouteFails(): void
    {
        $count = 0;
        $this->app->on(
            ResponseSent::class,
            function () use (&$count): void {
                $count++;
            }
        );

        $response = $this->app->handle(new ServerRequest('GET', '/fail'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(1, $count);
    }

    public function testResponseSentIsDispatchedOnceWhenItsListenerFails(): void
    {
        $count = 0;
        $this->app->on(
            ResponseSent::class,
            function () use (&$count): void {
                $count++;
                throw new RuntimeException('again');
            }
        );

        $this->app->handle(new ServerRequest('GET', '/ok'));

        $this->assertSame(1, $count);
    }
}
