<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-type RequestCase array{0: string, 1: string, 2: int, 3?: string, 4?: array<string, string>, 5?: string}
 *
 * Runs every example under the PHP built-in server and checks its documented routes over real
 * HTTP, so examples cannot silently drift from the framework API.
 */
class ExamplesTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    /** @var resource|null */
    private $server = null;
    private int $port = 0;

    protected function tearDown(): void
    {
        if (is_resource($this->server)) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
    }

    /**
     * Each case: [method, uri, expected status, body substring?, request headers?, request body?].
     *
     * @return iterable<string, array{string, list<RequestCase>}>
     */
    public static function examples(): iterable
    {
        $json = ['Content-Type' => 'application/json'];

        yield 'hello-world' => ['01-basics/hello-world.php', [
            ['GET', '/', 200, '"framework":"PivotPHP Core"'],
            ['GET', '/hello/PivotPHP', 200, 'Hello, PivotPHP!'],
        ]
        ];
        yield 'basic-routes' => ['01-basics/basic-routes.php', [
            ['GET', '/users', 200, 'Alice'],
            ['GET', '/users/1', 200, '"id":1'],
            ['GET', '/users/99', 404],
            ['GET', '/users/abc', 404],
            ['POST', '/users', 201, '"name":"Ana"', $json, '{"name":"Ana"}'],
            ['POST', '/users', 422, '', $json, '{}'],
            ['PUT', '/users/1', 200, '"name":"Bia"', $json, '{"name":"Bia"}'],
            ['DELETE', '/users/1', 204],
        ]
        ];
        yield 'request-response' => ['01-basics/request-response.php', [
            ['GET', '/inspect/42?page=2', 200, '"param_id":"42"', ['X-Trace' => 'abc']],
            ['GET', '/text', 200, 'plain text'],
            ['GET', '/html', 200, '<h1>PivotPHP</h1>'],
            ['GET', '/old-path', 301],
            ['GET', '/cookie', 200, '"cookie":"set"'],
        ]
        ];
        yield 'json-api' => ['01-basics/json-api.php', [
            ['GET', '/api/products?page=1&limit=2', 200, '"total":3'],
            ['GET', '/api/products?category=books', 200, 'PHP Book'],
            ['GET', '/api/products/1', 200, 'Smartphone'],
            ['GET', '/api/products/9', 404],
            ['GET', '/api/search?q=lap', 200, 'Laptop'],
            ['POST', '/api/products', 201, '"name":"Tablet"', $json, '{"name":"Tablet","price":499.9,"category":"x"}'],
            ['POST', '/api/products', 422, 'errors', $json, '{"name":"Tablet"}'],
            ['POST', '/api/products', 400, '', $json, '{invalid'],
        ]
        ];
        yield 'regex-routing' => ['02-routing/regex-routing.php', [
            ['GET', '/', 200],
            ['GET', '/users/123', 200],
            ['GET', '/users/invalid', 404],
            ['GET', '/api/v1/posts/2024/12/25', 200],
        ]
        ];
        yield 'route-constraints' => ['02-routing/route-constraints.php', [
            ['GET', '/', 200],
            ['GET', '/users/123', 200],
            ['GET', '/users/abc', 404],
            ['GET', '/posts/hello-world', 200],
            ['GET', '/secure/high/data', 200, '"security_level":"high"'],
            ['GET', '/secure/root/data', 404],
        ]
        ];
        yield 'route-groups' => ['02-routing/route-groups.php', [
            ['GET', '/api/v1/status', 200, '"status":"ok"'],
            ['GET', '/api/v1/admin/stats', 401],
            ['GET', '/api/v1/admin/stats', 200, '"users":42', ['X-Admin-Key' => 'secret']],
        ]
        ];
        yield 'route-parameters' => ['02-routing/route-parameters.php', [
            ['GET', '/users/42', 200, '"user_id":42'],
            ['GET', '/api/users/123/posts/456', 200, '"postId":"456"'],
            ['GET', '/articles/hello-world', 200, '"slug":"hello-world"'],
            ['GET', '/search?q=php&page=2', 200, '"page":2'],
        ]
        ];
        yield 'static-files' => ['02-routing/static-files.php', [
            ['GET', '/assets/css/app.css', 200, 'font-family'],
            ['GET', '/assets/data.json', 200, 'static JSON file'],
            ['GET', '/assets/missing.css', 404],
        ]
        ];
        yield 'auth-middleware' => ['03-middleware/auth-middleware.php', [
            ['GET', '/api/me', 401],
            ['GET', '/api/me', 200, '"user":"alice"', ['X-API-Key' => 'key-alice']],
            ['GET', '/health', 200],
        ]
        ];
        yield 'cors-middleware' => ['03-middleware/cors-middleware.php', [
            ['GET', '/api/data', 200, '"items"', ['Origin' => 'https://app.example.com']],
            ['OPTIONS', '/api/data', 204, '', [
                'Origin' => 'https://app.example.com',
                'Access-Control-Request-Method' => 'PUT',
            ]
            ],
            ['OPTIONS', '/api/data', 403, '', [
                'Origin' => 'https://evil.example',
                'Access-Control-Request-Method' => 'PUT',
            ]
            ],
        ]
        ];
        yield 'custom-middleware' => ['03-middleware/custom-middleware.php', [
            ['GET', '/', 200, '"request_id":"'],
            ['GET', '/maintenance', 503],
        ]
        ];
        yield 'middleware-stack' => ['03-middleware/middleware-stack.php', [
            ['GET', '/', 200, '"order":["global-1","global-2","route"]'],
        ]
        ];
        yield 'rest-api' => ['04-api/rest-api.php', [
            ['GET', '/api/v1/products', 200, 'Smartphone'],
            ['GET', '/api/v1/products/1', 200, 'Smartphone'],
            ['GET', '/api/v1/products/9', 404],
            ['POST', '/api/v1/products', 201, 'Notebook', $json, '{"name":"Notebook","price":2500.99}'],
            ['POST', '/api/v1/products', 422, '', $json, '{"name":"Notebook"}'],
            ['DELETE', '/api/v1/products/1', 204],
            ['PATCH', '/api/v1/products', 405],
        ]
        ];
        yield 'jwt-auth' => ['06-security/jwt-auth.php', [
            ['GET', '/api/profile', 401],
            ['POST', '/login', 401, '', $json, '{"username":"alice","password":"wrong"}'],
            ['POST', '/login', 200, '"token"', $json, '{"username":"alice","password":"secret"}'],
        ]
        ];
        yield 'api-documentation' => ['api_documentation_example.php', [
            ['GET', '/', 200, 'Documentation Example'],
            ['GET', '/docs', 200, '"openapi":"3.0.0"'],
            ['GET', '/swagger', 200, 'swagger-ui'],
            ['POST', '/users', 201, '"name":"Ana"', $json, '{"name":"Ana"}'],
        ]
        ];
        yield 'array-callables' => ['07-advanced/array-callables.php', [
            ['GET', '/', 200],
            ['GET', '/users', 200],
            ['POST', '/users', 201, 'John', $json, '{"name":"John","email":"john@example.com"}'],
        ]
        ];
    }

    /**
     * @param list<RequestCase> $cases
     */
    #[DataProvider('examples')]
    public function testExampleRoutes(string $example, array $cases): void
    {
        $this->startServer($example);

        foreach ($cases as $case) {
            [$method, $uri, $status] = $case;
            [$code, $body] = $this->request($method, $uri, $case[4] ?? [], $case[5] ?? '');

            $label = "{$example}: {$method} {$uri}";
            $this->assertSame($status, $code, "{$label} returned {$code}: " . substr($body, 0, 300));
            if (($case[3] ?? '') !== '') {
                $this->assertStringContainsString($case[3], $body, $label);
            }
        }
    }

    public function testJwtExampleIssuesTokenAcceptedByProtectedRoute(): void
    {
        $this->startServer('06-security/jwt-auth.php');

        [, $login] = $this->request(
            'POST',
            '/login',
            ['Content-Type' => 'application/json'],
            '{"username":"alice","password":"secret"}'
        );
        $token = json_decode($login, true)['token'] ?? null;
        $this->assertIsString($token);

        [$code, $body] = $this->request('GET', '/api/profile', ['Authorization' => "Bearer {$token}"]);

        $this->assertSame(200, $code);
        $this->assertStringContainsString('"role":"admin"', $body);
    }

    private function startServer(string $example): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($socket);
        $name = (string) stream_socket_get_name($socket, false);
        $this->port = (int) substr($name, strrpos($name, ':') + 1);
        fclose($socket);

        $root = (string) realpath(self::ROOT);
        $script = $root . '/examples/' . $example;
        $this->server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$this->port}", '-t', dirname($script), $script],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            $root
        ) ?: null;
        $this->assertIsResource($this->server);

        for ($i = 0; $i < 100; $i++) {
            $connection = @fsockopen('127.0.0.1', $this->port);
            if ($connection !== false) {
                fclose($connection);

                return;
            }
            usleep(50_000);
        }

        $this->fail("Built-in server for {$example} did not start");
    }

    /**
     * @param array<string, string> $headers
     * @return array{int, string}
     */
    private function request(string $method, string $uri, array $headers = [], string $body = ''): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }

        $context = stream_context_create(
            ['http' => [
                'method' => $method,
                'header' => implode("\r\n", $lines),
                'content' => $body,
                'ignore_errors' => true,
                'follow_location' => 0,
                'timeout' => 10,
            ]
            ]
        );

        $content = @file_get_contents("http://127.0.0.1:{$this->port}{$uri}", false, $context);
        /** @var list<string> $responseHeaders */
        $responseHeaders = $http_response_header ?? [];
        preg_match('#^HTTP/\S+\s+(\d{3})#', $responseHeaders[0] ?? '', $match);

        return [(int) ($match[1] ?? 0), $content === false ? '' : $content];
    }
}
