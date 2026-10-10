<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Middleware;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Http\Factory\Psr17Factory;
use PivotPHP\Security\Cors\CorsConfig;
use PivotPHP\Security\Cors\CorsMiddleware;
use PivotPHP\Security\Headers\SecurityHeadersMiddleware;
use PivotPHP\Security\Jwt\JwtAuthMiddleware;
use PivotPHP\Security\Jwt\JwtConfig;
use PivotPHP\Security\Jwt\JwtIssuer;
use PivotPHP\Security\Proxy\TrustedProxyConfig;
use PivotPHP\Security\Proxy\TrustedProxyMiddleware;

/**
 * The core delegates security to pivotphp/security (SPEC-092): its middlewares run in the
 * application pipeline, short-circuit responses are kept, and attributes reach the routes.
 */
class SecurityPackageIntegrationTest extends TestCase
{
    private const SECRET = 'a-32-bytes-long-secret-for-tests';

    private Application $app;
    private JwtConfig $jwt;

    protected function setUp(): void
    {
        $factory = new Psr17Factory();
        $this->jwt = new JwtConfig(self::SECRET, publicPaths: ['/health']);

        $this->app = new Application(__DIR__ . '/../../..');
        $this->app->use(new TrustedProxyMiddleware(new TrustedProxyConfig(['10.0.0.0/8'])));
        $this->app->use(new SecurityHeadersMiddleware());
        $this->app->use(
            new CorsMiddleware(
                $factory,
                new CorsConfig(['https://app.example.com'], allowCredentials: true)
            )
        );
        $this->app->use(new JwtAuthMiddleware($factory, $this->jwt));

        $this->app->get('/health', fn ($req, $res) => $res->json(['ok' => true]));
        $this->app->get(
            '/me',
            fn ($req, $res) => $res->json(
                [
                    'user' => $req->psr7()->getAttribute('user'),
                    'ip' => $req->psr7()->getAttribute('client_ip'),
                ]
            )
        );
    }

    /**
     * @param array<string, string> $headers
     */
    private function send(string $method, string $path, array $headers = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->app->handle(
            new ServerRequest($method, $path, $headers, null, '1.1', ['REMOTE_ADDR' => '10.0.0.5'])
        );
    }

    /**
     * SPEC-063: the preflight answered by the middleware is returned by the application.
     */
    public function testPreflightIsAnsweredWithoutAuthentication(): void
    {
        $response = $this->send(
            'OPTIONS',
            '/me',
            [
                'Origin' => 'https://app.example.com',
                'Access-Control-Request-Method' => 'GET',
                'Access-Control-Request-Headers' => 'authorization',
            ]
        );

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }

    /**
     * SPEC-054/065: claims and the trusted client IP reach the route.
     */
    public function testAuthenticatedRouteReceivesUserAndClientIp(): void
    {
        $token = (new JwtIssuer($this->jwt))->issue(['sub' => 'u1'], 60);

        $response = $this->send(
            'GET',
            '/me',
            [
                'Authorization' => 'Bearer ' . $token,
                'X-Forwarded-For' => '198.51.100.4',
            ]
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertSame('u1', $body['user']['sub']);
        $this->assertSame('198.51.100.4', $body['ip']);
    }

    public function testMissingTokenIsRejectedWithSecurityHeaders(): void
    {
        $response = $this->send('GET', '/me', ['Origin' => 'https://app.example.com']);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    /**
     * SPEC-055: public paths work through the application.
     */
    public function testPublicPathSkipsAuthentication(): void
    {
        $this->assertSame(200, $this->send('GET', '/health')->getStatusCode());
    }
}
