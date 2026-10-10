<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PivotPHP\Core\Core\Application;
use PivotPHP\Http\Factory\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test HTTP client that drives the real Application pipeline
 * (`$app->handle(ServerRequestInterface)`), built on PivotPHP\Http.
 */
class TestHttpClient
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $uri, array $options = []): TestResponse
    {
        try {
            if (!$this->isAppBooted()) {
                $this->app->boot();
            }

            $response = $this->app->handle($this->buildRequest($method, $uri, $options));

            return new TestResponse(
                $response->getStatusCode(),
                $this->flattenHeaders($response),
                (string) $response->getBody()
            );
        } catch (\Throwable $e) {
            return new TestResponse(
                500,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => $e->getMessage()])
            );
        }
    }

    /**
     * @param array<int, array{method: string, uri: string, options?: array<string, mixed>}> $requests
     * @return array<int, TestResponse>
     */
    public function concurrentRequests(array $requests): array
    {
        $results = [];

        foreach ($requests as $i => $request) {
            $results[$i] = $this->request(
                $request['method'],
                $request['uri'],
                $request['options'] ?? []
            );
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function buildRequest(string $method, string $uri, array $options): ServerRequestInterface
    {
        /** @var array<string, string> $headers */
        $headers = $options['headers'] ?? [];
        $data = $options['data'] ?? null;

        $server = [
            'REQUEST_METHOD' => strtoupper($method),
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => $uri,
        ];

        $body = null;
        if ($data !== null) {
            $body = is_string($data) ? $data : (string) json_encode($data);
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/json';
            }
        }

        return ServerRequestFactory::fromArrays($server, $headers, [], [], null, [], $body);
    }

    private function isAppBooted(): bool
    {
        $reflection = new \ReflectionClass($this->app);
        $booted = $reflection->getProperty('booted');
        $booted->setAccessible(true);

        return (bool) $booted->getValue($this->app);
    }

    /**
     * @return array<string, string>
     */
    private function flattenHeaders(ResponseInterface $response): array
    {
        $headers = [];

        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        return $headers;
    }
}
