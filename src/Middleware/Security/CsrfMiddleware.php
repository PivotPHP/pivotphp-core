<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Security;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
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
        if (strtoupper($request->getMethod()) === 'POST') {
            $parsedBody = $request->getParsedBody();
            $token = is_array($parsedBody) ? ($parsedBody[$this->fieldName] ?? null) : null;
            $sessionToken = $_SESSION[$this->fieldName] ?? null;
            if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
                throw new HttpException(403, 'CSRF token inválido ou ausente', ['Content-Type' => 'application/json']);
            }
        }
        // Gera novo token para próxima requisição
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$this->fieldName] = bin2hex(random_bytes(32));
        return $handler->handle($request);
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
