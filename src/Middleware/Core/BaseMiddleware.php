<?php

namespace PivotPHP\Core\Middleware\Core;

use PivotPHP\Core\Http\ExpressRequest as Request;
use PivotPHP\Core\Http\ExpressResponse as Response;
use PivotPHP\Core\Exceptions\HttpException;

/**
 * Classe base para middlewares.
 */
abstract class BaseMiddleware implements MiddlewareInterface
{
    /**
     * Executa o middleware.
     *
     * @param  Request  $request
     * @param  Response $response
     * @param  callable $next
     * @return mixed
     */
    abstract public function handle($request, $response, callable $next);

    /**
     * Make the middleware callable for backward compatibility
     *
     * @param  Request  $request
     * @param  Response $response
     * @param  callable $next
     * @return mixed
     */
    public function __invoke($request, $response, callable $next)
    {
        return $this->handle($request, $response, $next);
    }

    /**
     * Obtém um valor do cabeçalho da requisição.
     *
     * @param  mixed  $request
     * @param  string $header
     * @param  mixed  $default
     * @return mixed
     */
    protected static function getHeader(
        $request,
        string $header,
        $default = null
    ) {
        if ($request instanceof Request) {
            return $request->header($header) ?? $default;
        }

        // Handle generic objects for testing - check if headers is an object
        if (is_object($request) && isset($request->headers)) {
            if (is_object($request->headers) && property_exists($request->headers, $header)) {
                return $request->headers->$header;
            } elseif (is_array($request->headers) && isset($request->headers[$header])) {
                return $request->headers[$header];
            }
        }

        // Fallback to $_SERVER for testing
        $serverKey = 'HTTP_' . str_replace('-', '_', strtoupper($header));
        return $_SERVER[$serverKey] ?? $default;
    }

    /**
     * Verifica se a requisição é AJAX.
     *
     * @param  mixed $request
     * @return bool
     */
    protected function isAjaxRequest($request): bool
    {
        if ($request instanceof Request) {
            return $request->isAjax();
        }

        return $this->getHeader($request, 'X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Obtém o IP do cliente.
     *
     * @param  Request $request
     * @return string
     */
    protected function getClientIp(Request $request): string
    {
        return $request->ip();
    }

    /**
     * Verifica se a requisição é HTTPS.
     *
     * @param  Request $request
     * @return bool
     */
    protected function isSecureRequest(Request $request): bool
    {
        return $request->isSecure();
    }

    /**
     * Responde com erro JSON.
     *
     * @param  Response             $response
     * @param  int                  $statusCode
     * @param  string               $message
     * @param  array<string, mixed> $data
     * @return Response
     */
    protected function respondWithError(
        Response $response,
        int $statusCode,
        string $message,
        array $data = []
    ): Response {
        throw new HttpException($statusCode, $message, ['Content-Type' => 'application/json']);
    }

    /**
     * Responde com sucesso JSON.
     *
     * @param  Response $response
     * @param  mixed    $data
     * @param  string   $message
     * @return Response
     */
    protected function respondWithSuccess(
        Response $response,
        $data = null,
        string $message = 'Success'
    ): Response {
        $result = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $result['data'] = $data;
        }

        return $response->json($result);
    }
}
