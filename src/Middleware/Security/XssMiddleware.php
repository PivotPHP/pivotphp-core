<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Security;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * XSS Protection Middleware
 *
 * Provides protection against Cross-Site Scripting (XSS) attacks by
 * sanitizing request input and adding appropriate security headers.
 *
 * @package PivotPHP\Core\Middleware\Security
 * @since 1.1.2
 */
class XssMiddleware implements MiddlewareInterface
{
    private string $allowedTags;
    private bool $enableCsp;

    public function __construct(string $allowedTags = '', bool $enableCsp = true)
    {
        $this->allowedTags = $allowedTags;
        $this->enableCsp = $enableCsp;
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Sanitize input
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody)) {
            $parsedBody = $this->sanitizeArray($parsedBody, $this->allowedTags);
            $request = $request->withParsedBody($parsedBody);
        }

        // Handle request
        $response = $handler->handle($request);

        // Add security headers
        if ($this->enableCsp) {
            $response = $this->addSecurityHeaders($response);
        }

        return $response;
    }

    /**
     * Add security headers to response
     */
    private function addSecurityHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'")
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * Sanitize method (optimized with single regex pass)
     */
    public static function sanitize(string $input, string $allowedTags = ''): string
    {
        if ($input === '') {
            return '';
        }

        $input = trim($input);

        // Optimized: Remove dangerous elements in a single pass
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',  // Remove script tags
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',  // Remove iframe tags
            '/<svg\b[^>]*>(.*?)<\/svg>/is',        // Remove svg tags
            '/<embed\b[^>]*>(.*?)<\/embed>/is',    // Remove embed tags
            '/<object\b[^>]*>(.*?)<\/object>/is',  // Remove object tags
            '/on\w+\s*=\s*(["\']).*?\1/is',        // Remove event handlers
            '/javascript:/is',                      // Remove javascript: protocol
            '/vbscript:/is',                        // Remove vbscript: protocol
            '/data:text\/html/is',                  // Remove data:text/html
        ];

        $input = preg_replace($patterns, '', $input);

        // Handle null result from preg_replace
        if ($input === null) {
            return '';
        }

        // Final cleanup with strip_tags
        return strip_tags($input, $allowedTags);
    }

    /**
     * CleanUrl method
     */
    public static function cleanUrl(string $url): string
    {
        // Remove javascript: e outros protocolos perigosos
        if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return '';
        }
        return $url;
    }

    /**
     * ContainsXss method
     */
    public static function containsXss(string $input): bool
    {
        // Detecta tags e atributos perigosos
        return preg_match('/<\s*script|on\w+\s*=|javascript:/i', $input) === 1;
    }

    private function sanitizeArray(array $data, string $allowedTags = ''): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $allowedTags);
            } else {
                $sanitized[$key] = self::sanitize((string)$value, $allowedTags);
            }
        }
        return $sanitized;
    }
}
