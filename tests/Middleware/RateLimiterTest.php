<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\RateLimiter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RateLimiterTest extends TestCase
{
    private function allow(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, ['Content-Type' => 'application/json'], '{"status":"ok"}');
            }
        };
    }

    private function req(string $ip = '1.2.3.4'): ServerRequest
    {
        return new ServerRequest('GET', '/test', [], null, '1.1', ['REMOTE_ADDR' => $ip]);
    }

    public function testDefaultConfigurationAddsHeaders(): void
    {
        $response = (new RateLimiter())->process($this->req(), $this->allow());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('X-RateLimit-Limit'));
        $this->assertTrue($response->hasHeader('X-RateLimit-Remaining'));
        $this->assertTrue($response->hasHeader('X-RateLimit-Reset'));
    }

    public function testFixedWindowRejectsWhenExceeded(): void
    {
        $limiter = new RateLimiter([
            'strategy' => RateLimiter::STRATEGY_FIXED_WINDOW,
            'max_requests' => 1,
            'window_size' => 60,
        ]);

        $limiter->process($this->req(), $this->allow());
        $response = $limiter->process($this->req(), $this->allow());

        $this->assertSame(429, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Retry-After'));
    }

    public function testCustomRejectResponse(): void
    {
        $limiter = new RateLimiter([
            'strategy' => RateLimiter::STRATEGY_FIXED_WINDOW,
            'max_requests' => 1,
            'window_size' => 60,
            'reject_response' => [
                'status' => 503,
                'body' => ['message' => 'Service Unavailable'],
                'headers' => ['Retry-After' => '120'],
            ],
        ]);

        $limiter->process($this->req(), $this->allow());
        $response = $limiter->process($this->req(), $this->allow());

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('120', $response->getHeaderLine('Retry-After'));
        $this->assertSame(
            ['message' => 'Service Unavailable'],
            json_decode((string) $response->getBody(), true)
        );
    }

    public function testWhitelistBypasses(): void
    {
        $limiter = new RateLimiter(['whitelist' => ['1.2.3.4']]);
        $response = $limiter->process($this->req(), $this->allow());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('X-RateLimit-Limit'));
    }

    public function testBlacklistRejects(): void
    {
        $limiter = new RateLimiter(['blacklist' => ['1.2.3.4']]);
        $response = $limiter->process($this->req(), $this->allow());

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('blacklisted', $response->getHeaderLine('X-RateLimit-Reason'));
    }

    public function testCustomKeyGenerator(): void
    {
        $limiter = new RateLimiter([
            'strategy' => RateLimiter::STRATEGY_FIXED_WINDOW,
            'max_requests' => 1,
            'window_size' => 60,
            'key_generator' => static fn (ServerRequestInterface $r): string => $r->getHeaderLine('X-Api-Key'),
        ]);

        $a = new ServerRequest('GET', '/test', ['X-Api-Key' => 'k1']);
        $b = new ServerRequest('GET', '/test', ['X-Api-Key' => 'k2']);

        $this->assertSame(200, $limiter->process($a, $this->allow())->getStatusCode());
        $this->assertSame(200, $limiter->process($b, $this->allow())->getStatusCode());
        $this->assertSame(429, $limiter->process($a, $this->allow())->getStatusCode());
    }
}
