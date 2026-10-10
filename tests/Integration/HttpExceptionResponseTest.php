<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Exceptions\HttpException;
use RuntimeException;

/**
 * Exceptions thrown by routes become JSON responses: HttpException status and headers are applied;
 * outside debug mode the message is replaced by the generic status text.
 */
class HttpExceptionResponseTest extends TestCase
{
    private function handle(callable $route): \Psr\Http\Message\ResponseInterface
    {
        $app = new Application(__DIR__ . '/../..');
        $app->get('/x', $route);

        return $app->handle(new ServerRequest('GET', '/x'));
    }

    public function testHttpExceptionStatusAndHeadersAreApplied(): void
    {
        $response = $this->handle(
            function (): void {
                throw new HttpException(429, 'Slow down', ['Retry-After' => '30']);
            }
        );

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('30', $response->getHeaderLine('Retry-After'));
        $this->assertStringContainsString('"message":"Too Many Requests"', (string) $response->getBody());
    }

    public function testServerErrorMessageIsHidden(): void
    {
        $response = $this->handle(
            function (): void {
                throw new RuntimeException('database password is wrong');
            }
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('password', (string) $response->getBody());
        $this->assertStringContainsString('"error_id"', (string) $response->getBody());
    }

    public function testHttp5xxMessageIsHidden(): void
    {
        $response = $this->handle(
            function (): void {
                throw new HttpException(503, 'redis at 10.0.0.3 is down');
            }
        );

        $this->assertSame(503, $response->getStatusCode());
        $this->assertStringNotContainsString('10.0.0.3', (string) $response->getBody());
    }
}
