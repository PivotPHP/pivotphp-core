<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Http;

use PivotPHP\Core\Http\Psr7\Response as Psr7Response;
use PivotPHP\Core\Http\Psr7\Stream;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * API Documentation Middleware
 *
 * Simple and effective automatic API documentation generation for the microframework.
 * Generates an OpenAPI 3.0.0 specification from all routes registered in the Router.
 * Each route produces a basic path entry with its HTTP method and path.
 * No PHPDoc comment parsing is performed — metadata is derived from registered routes only.
 *
 * Provides two endpoints:
 * - /docs    JSON OpenAPI 3.0.0 specification
 * - /swagger Swagger UI interface (loads Swagger UI from unpkg CDN)
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class ApiDocumentationMiddleware implements MiddlewareInterface
{
    private string $docsPath = '/docs';
    private string $swaggerPath = '/swagger';
    private ?string $baseUrl = null;
    private bool $enabled = true;

    /**
     * Constructor
     */
    public function __construct(array $options = [])
    {
        $this->docsPath = $options['docs_path'] ?? '/docs';
        $this->swaggerPath = $options['swagger_path'] ?? '/swagger';
        $this->baseUrl = $options['base_url'] ?? null;
        $this->enabled = $options['enabled'] ?? true;
    }

    /**
     * Process the middleware
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        // Handle documentation endpoints
        if ($path === $this->docsPath) {
            return $this->handleApiDocs($request);
        }

        if ($path === $this->swaggerPath) {
            return $this->handleSwaggerUi($request);
        }

        return $handler->handle($request);
    }

    /**
     * Handle API documentation JSON endpoint
     */
    private function handleApiDocs(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $docs = $this->generateOpenApiDocs();

            $json = json_encode($docs, JSON_THROW_ON_ERROR);
            return (new Psr7Response(200))
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withBody(Stream::createFromString($json));
        } catch (\Exception $e) {
            return $this->createErrorResponse('Error generating documentation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate OpenAPI documentation from application routes
     *
     * @return array<string, mixed>
     */
    private function generateOpenApiDocs(): array
    {
        $baseUrl = $this->baseUrl ?? 'http://localhost:8080';

        // Get routes from Router (static class)
        $routes = Router::getRoutes();

        $paths = [];
        foreach ($routes as $route) {
            $path = $route['path'] ?? '/';
            $method = strtolower($route['method'] ?? 'get');

            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }

            $paths[$path][$method] = [
                'summary' => 'Route: ' . $method . ' ' . $path,
                'responses' => [
                    '200' => [
                        'description' => 'Successful response'
                    ]
                ]
            ];
        }

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'PivotPHP API',
                'version' => '2.0.0',
                'description' => 'Auto-generated API documentation'
            ],
            'servers' => [
                ['url' => $baseUrl]
            ],
            'paths' => $paths
        ];
    }

    /**
     * Handle Swagger UI endpoint
     */
    private function handleSwaggerUi(ServerRequestInterface $request): ResponseInterface
    {
        return (new Psr7Response(200))
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody(Stream::createFromString($this->getSwaggerUiHtml()));
    }

    /**
     * Get Swagger UI HTML
     */
    private function getSwaggerUiHtml(): string
    {
        $docsUrl = $this->docsPath;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>API Documentation - PivotPHP Core</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: '{$docsUrl}',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
HTML;
    }

    /**
     * Create error response
     */
    private function createErrorResponse(string $message, int $statusCode = 500): ResponseInterface
    {
        $json = json_encode(['error' => $message], JSON_THROW_ON_ERROR);
        return (new Psr7Response($statusCode))
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::createFromString($json));
    }

    /**
     * Magic method for direct invocation
     */
    public function __invoke(Request $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        return $this->process($request, $this->createHandler($next));
    }

    /**
     * Create handler from callable
     */
    private function createHandler(callable $next): RequestHandlerInterface
    {
        return new class ($next) implements RequestHandlerInterface {
            /** @var callable */
            private $next;

            public function __construct(callable $next)
            {
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->next)($request);
            }
        };
    }
}
