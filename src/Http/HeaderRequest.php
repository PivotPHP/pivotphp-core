<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http;

/**
 * HeaderRequest class manages and facilitates access to request headers.
 * Converts headers to camelCase and allows access via properties or methods.
 *
 * @property array $headers Array of converted headers.
 */
class HeaderRequest
{
    /**
     * Array of converted headers.
     *
     * @var array<string, mixed>
     */
    private $headers;

    /**
     * Constructor. Initializes and converts request headers.
     */
    public function __construct()
    {
        // Fallback para getallheaders() se não estiver disponível (como em testes CLI)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headerName = str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                    );
                    $headers[$headerName] = $value;
                }
            }
        }

        $this->headers = [];
        foreach ($headers as $key => $value) {
            $key = self::headerToCamel($key);
            $this->headers[$key] = $value;
        }
    }

    /**
     * Allows access to headers via property.
     *
     * @param  string $name Header name.
     * @return string|null Header value or null if not exists.
     */
    public function __get($name)
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Returns the value of a header.
     *
     * @param  string $name Header name.
     * @return string|null Valor do cabeçalho ou null se não existir.
     */
    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Retorna todos os cabeçalhos convertidos.
     *
     * @return array<string, mixed>
     */
    public function getAllHeaders()
    {
        return $this->headers;
    }

    /**
     * Verifica se um cabeçalho existe.
     *
     * @param  string $name Nome do cabeçalho.
     * @return bool
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * Obtém o valor de Authorization.
     *
     * @return string|null
     */
    public function authorization(): ?string
    {
        return $this->getHeader('authorization');
    }

    /**
     * Obtém o Bearer token do cabeçalho Authorization.
     *
     * @return string|null
     */
    public function bearerToken(): ?string
    {
        $auth = $this->authorization();
        if ($auth && preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Obtém o Content-Type.
     *
     * @return string|null
     */
    public function contentType(): ?string
    {
        return $this->getHeader('contentType');
    }

    /**
     * Obtém o Accept.
     *
     * @return string|null
     */
    public function accept(): ?string
    {
        return $this->getHeader('accept');
    }

    /**
     * Verifica se aceita JSON.
     *
     * @return bool
     */
    public function acceptsJson(): bool
    {
        $accept = $this->accept();
        return $accept && (strpos($accept, 'application/json') !== false || strpos($accept, '*/*') !== false);
    }

    /**
     * Verifica se aceita HTML.
     *
     * @return bool
     */
    public function acceptsHtml(): bool
    {
        $accept = $this->accept();
        return $accept && (strpos($accept, 'text/html') !== false || strpos($accept, '*/*') !== false);
    }

    /**
     * Convert a hyphenated header name to camelCase.
     * Example: "Content-Type" → "contentType"
     */
    public static function headerToCamel(string $header): string
    {
        $header = trim($header, ':');
        $parts = explode('-', $header);
        $parts = array_map('ucfirst', $parts);
        return lcfirst(implode('', $parts));
    }
}
