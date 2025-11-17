<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Security;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use PivotPHP\Core\Http\Psr7\Response;
use PivotPHP\Core\Http\Psr7\Stream;
use PivotPHP\Core\Exceptions\HttpException;

/**
 * CSRF Protection Middleware
 *
 * Provides Cross-Site Request Forgery (CSRF) protection by validating
 * tokens in form submissions and AJAX requests.
 *
 * @package PivotPHP\Core\Middleware\Security
 * @since 1.1.2
 */
class CsrfMiddleware implements MiddlewareInterface
{
    private string $fieldName;

    public function __construct(string $fieldName = '_csrf_token')
    {
        $this->fieldName = $fieldName;
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Validate CSRF token for all state-changing methods
        $method = strtoupper($request->getMethod());
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $this->getTokenFromRequest($request);

            // Ensure session is started
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $sessionToken = $_SESSION[$this->fieldName] ?? null;

            if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
                throw new HttpException(
                    403,
                    'CSRF token inválido ou ausente',
                    ['Content-Type' => 'application/json']
                );
            }
        }

        // Generate new token for next request
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$this->fieldName] = bin2hex(random_bytes(32));

        return $handler->handle($request);
    }

    /**
     * Get CSRF token from request (form data or header)
     */
    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        // Priority 1: Form data
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody[$this->fieldName])) {
            return $parsedBody[$this->fieldName];
        }

        // Priority 2: X-CSRF-TOKEN header (for APIs)
        $headers = $request->getHeader('X-CSRF-TOKEN');
        if (!empty($headers)) {
            return $headers[0];
        }

        // Priority 3: X-XSRF-TOKEN header (alternative naming)
        $headers = $request->getHeader('X-XSRF-TOKEN');
        if (!empty($headers)) {
            return $headers[0];
        }

        return null;
    }

    /**
     * Get token
     */
    public static function getToken(string $fieldName = '_csrf_token'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION[$fieldName])) {
            $_SESSION[$fieldName] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$fieldName];
    }

    /**
     * HiddenField method
     */
    public static function hiddenField(string $fieldName = '_csrf_token'): string
    {
        $token = self::getToken($fieldName);
        return
            '<input type="hidden" name="' .
            htmlspecialchars($fieldName) .
            '" value="' .
            htmlspecialchars($token) .
            '">';
    }

    /**
     * MetaTag method
     */
    public static function metaTag(string $fieldName = '_csrf_token'): string
    {
        $token = self::getToken($fieldName);
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}
