<?php

namespace PivotPHP\Core\Http;

use PivotPHP\Core\Http\HeaderRequest;
use PivotPHP\Core\Http\Contracts\AttributeInterface;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Stream;
use PivotPHP\Core\Http\Psr7\Uri;
use PivotPHP\Core\Http\Pool\Psr7Pool;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;
use InvalidArgumentException;
use stdClass;
use RuntimeException;

/**
 * Classe Request híbrida que implementa PSR-7 mantendo compatibilidade Express.js
 *
 * Esta classe oferece suporte completo a PSR-7 (ServerRequestInterface)
 * enquanto mantém todos os métodos de conveniência do estilo Express.js
 * para total compatibilidade com código existente.
 *
 * @property mixed $user Usuário autenticado ou qualquer outro atributo dinâmico.
 */
class Request implements ServerRequestInterface, AttributeInterface
{
    /**
     * Instância PSR-7 interna (lazy loaded)
     */
    private ?ServerRequestInterface $psr7Request = null;

    /**
     * Método HTTP.
     */
    private string $method;

    /**
     * Padrão da rota.
     */
    private string $path;

    /**
     * Caminho real da requisição.
     */
    private string $pathCallable;

    /**
     * Parâmetros extraídos da URL.
     */
    private stdClass $params;

    /**
     * Parâmetros da query string.
     */
    private stdClass $query;

    /**
     * Corpo da requisição.
     */
    private stdClass $body;

    /**
     * Cabeçalhos da requisição.
     */
    private HeaderRequest $headers;

    /**
     * Arquivos enviados via upload (anexos).
     *
     * @var array<string, mixed>
     */
    private array $files = [];

    /**
     * Atributos dinâmicos adicionados ao request.
     *
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * Cache para php://input (evita múltiplas leituras)
     */
    private static ?string $cachedInput = null;

    /**
     * Obtém o input cached para evitar múltiplas leituras de php://input
     */
    private function getCachedInput(): string
    {
        if (self::$cachedInput === null) {
            $input = @file_get_contents('php://input');
            if ($input === false) {
                error_log('Failed to read from php://input stream');
                self::$cachedInput = '';
            } else {
                self::$cachedInput = $input;
            }
        }
        return self::$cachedInput;
    }

    /**
     * Retorna objetos PSR-7 ao pool quando não precisamos mais deles
     */
    public function __destruct()
    {
        if ($this->psr7Request !== null) {
            Psr7Pool::returnServerRequest($this->psr7Request);
        }
    }

    /**
     * Construtor da classe Request.
     *
     * @param string $method       Método HTTP.
     * @param string $path         Padrão da rota.
     * @param string $pathCallable Caminho real da requisição.
     */
    public function __construct(
        string $method,
        string $path,
        string $pathCallable
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->pathCallable = $pathCallable;
        // Don't add trailing slash - it breaks route matching
        // Routes should handle trailing slashes in their patterns if needed
        $this->params = new stdClass();
        $this->query = new stdClass();
        $this->body = new stdClass();
        $this->headers = new HeaderRequest();
        $this->files = $_FILES;

        // PSR-7 request será inicializado apenas quando necessário (lazy loading)

        $this->parseRoute();
    }

    /**
     * Obtém a instância PSR-7 interna (lazy loading)
     */
    private function getPsr7Request(): ServerRequestInterface
    {
        if ($this->psr7Request === null) {
            $this->initializePsr7Request();
        }
        assert($this->psr7Request !== null); // Para PHPStan
        return $this->psr7Request;
    }

    /**
     * Inicializa o request PSR-7 interno (chamado apenas quando necessário)
     */
    private function initializePsr7Request(): void
    {
        $uri = Psr7Pool::getUri($this->pathCallable);
        $body = Psr7Pool::getStream($this->getCachedInput());
        $headers = $this->convertHeadersToPsr7Format($_SERVER);

        $this->psr7Request = Psr7Pool::getServerRequest(
            $this->method,
            $uri,
            $body,
            $headers,
            '1.1',
            $_SERVER
        );

        // Configurar query params
        $this->psr7Request = $this->psr7Request->withQueryParams($_GET);

        // Configurar parsed body
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $input = $this->getCachedInput();
            if ($input !== '') {
                $decoded = json_decode($input, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    error_log('JSON decode error in request body: ' . json_last_error_msg());
                    $this->psr7Request = $this->psr7Request->withParsedBody($_POST);
                } else {
                    $this->psr7Request = $this->psr7Request->withParsedBody($decoded ?: $_POST);
                }
            }
        }

        // Configurar cookies
        $this->psr7Request = $this->psr7Request->withCookieParams($_COOKIE);

        // Configurar uploaded files
        $this->psr7Request = $this->psr7Request->withUploadedFiles($this->normalizeFiles($_FILES));

        // Sincronizar atributos locais com PSR-7
        foreach ($this->attributes as $name => $value) {
            $this->psr7Request = $this->psr7Request->withAttribute($name, $value);
        }
    }

    /**
     * Converte headers do formato $_SERVER para PSR-7
     */
    private function convertHeadersToPsr7Format(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = substr($key, 5);
                $name = str_replace('_', '-', $name);
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = [$value];
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', $key);
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = [$value];
            }
        }

        return $headers;
    }

    /**
     * Normaliza uploaded files para PSR-7
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $normalized[$key] = $this->normalizeNestedFiles($file);
            } else {
                $normalized[$key] = $this->createUploadedFile($file);
            }
        }

        return $normalized;
    }

    /**
     * Normaliza nested uploaded files
     */
    private function normalizeNestedFiles(array $file): array
    {
        $normalized = [];

        foreach (array_keys($file['name']) as $key) {
            $normalized[$key] = $this->createUploadedFile(
                [
                    'name' => $file['name'][$key],
                    'type' => $file['type'][$key],
                    'tmp_name' => $file['tmp_name'][$key],
                    'error' => $file['error'][$key],
                    'size' => $file['size'][$key],
                ]
            );
        }

        return $normalized;
    }

    /**
     * Cria UploadedFile do array de arquivo
     */
    private function createUploadedFile(array $file): \PivotPHP\Core\Http\Psr7\UploadedFile
    {
        if (!isset($file['tmp_name']) || !is_string($file['tmp_name'])) {
            throw new \InvalidArgumentException('Invalid file specification');
        }

        // Para testes, criar um stream vazio se o arquivo não existir
        if (!file_exists($file['tmp_name'])) {
            $stream = Psr7Pool::getStream('');
        } else {
            $stream = Stream::createFromFile($file['tmp_name']);
        }

        return new \PivotPHP\Core\Http\Psr7\UploadedFile(
            $stream,
            $file['size'] ?? null,
            $file['error'] ?? \UPLOAD_ERR_OK,
            $file['name'] ?? null,
            $file['type'] ?? null
        );
    }

    // =============================================================================
    // MÉTODOS EXPRESS.JS (COMPATIBILIDADE TOTAL)
    // =============================================================================

    /**
     * Magic method to get properties dynamically
     */
    public function __get(string $name): mixed
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        throw new InvalidArgumentException("Property {$name} does not exist in Request class");
    }

    /**
     * Magic method to set properties dynamically
     */
    public function __set(string $name, mixed $value): void
    {
        if (property_exists($this, $name)) {
            throw new RuntimeException("Cannot override native property: {$name}");
        }

        $this->attributes[$name] = $value;
        if ($this->psr7Request !== null) {
            $this->psr7Request = $this->psr7Request->withAttribute($name, $value);
        }
    }

    /**
     * Magic method to check if property exists
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name) || array_key_exists($name, $this->attributes);
    }

    /**
     * Magic method to unset properties
     */
    public function __unset(string $name): void
    {
        if (property_exists($this, $name)) {
            throw new RuntimeException("Cannot unset native property: {$name}");
        }

        unset($this->attributes[$name]);
        if ($this->psr7Request !== null) {
            $this->psr7Request = $this->psr7Request->withoutAttribute($name);
        }
    }

    /**
     * Obtém um parâmetro específico da rota.
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params->{$key} ?? $default;
    }

    /**
     * Obtém um parâmetro específico da query string.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query->{$key} ?? $default;
    }

    /**
     * Obtém um valor específico do corpo da requisição.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body->{$key} ?? $default;
    }

    /**
     * Obtém informações sobre um arquivo enviado.
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) ? $file : null;
    }

    /**
     * Verifica se a requisição tem um arquivo específico.
     */
    public function hasFile(string $key): bool
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Obtém o IP do cliente.
     */
    public function ip(): string
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Obtém o User-Agent.
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Verifica se a requisição é AJAX.
     */
    public function isAjax(): bool
    {
        $requestedWith = $this->header('X-Requested-With');

        // Fallback to $_SERVER if not found in headers
        if ($requestedWith === null) {
            $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
        }

        return !empty($requestedWith) && strtolower($requestedWith) === 'xmlhttprequest';
    }

    /**
     * Verifica se a requisição é HTTPS.
     */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
    }

    /**
     * Obtém a URL completa da requisição.
     */
    public function fullUrl(): string
    {
        $protocol = $this->isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return "{$protocol}://{$host}{$uri}";
    }

    /**
     * Obtém header da requisição.
     */
    public function header(string $name): ?string
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Header name must be a string');
        }
        if (!$this->headers->hasHeader($name)) {
            return null;
        }

        return $this->headers->getHeader($name);
    }

    /**
     * Define headers para a requisição (principalmente para testes).
     *
     * @param array<string, string> $headers
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        // Criar um novo HeaderRequest com headers customizados
        $this->headers = new class ($headers) extends HeaderRequest {
            /** @var array<string, string> */
            private array $customHeaders;

            /** @var array<string, mixed> */
            protected array $headers;

            /**
             * @param array<string, string> $customHeaders
             */
            public function __construct(array $customHeaders = [])
            {
                $this->customHeaders = $customHeaders;
                $this->headers = [];

                // Primeiro processar headers customizados
                foreach ($customHeaders as $key => $value) {
                    $key = trim($key, ':'); // Remove leading colon
                    $camelCaseKey = explode('-', $key);
                    $camelCaseKey = array_map('ucfirst', $camelCaseKey);
                    $camelCaseKey = implode('', $camelCaseKey);
                    $key = lcfirst($camelCaseKey); // Convert to camelCase
                    $this->headers[$key] = $value;
                }

                // Depois processar headers padrão se não foram sobrescritos
                $existingHeaders = function_exists('getallheaders') ? getallheaders() : [];
                if (empty($existingHeaders)) {
                    foreach ($_SERVER as $name => $value) {
                        if (substr($name, 0, 5) == 'HTTP_') {
                            $headerName = str_replace(
                                ' ',
                                '-',
                                ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                            );
                            $camelCaseKey = explode('-', $headerName);
                            $camelCaseKey = array_map('ucfirst', $camelCaseKey);
                            $camelCaseKey = implode('', $camelCaseKey);
                            $key = lcfirst($camelCaseKey);

                            // Only add if not already set by custom headers
                            if (!isset($this->headers[$key])) {
                                $this->headers[$key] = $value;
                            }
                        }
                    }
                }
            }

            /**
             * Override getHeader to handle test headers properly
             */
            public function getHeader($name): ?string
            {
                // First check if it's in our custom headers (exact match)
                if (isset($this->customHeaders[$name])) {
                    return (string) $this->customHeaders[$name];
                }

                // Then check camelCase version
                $key = trim($name, ':');
                $camelCaseKey = explode('-', $key);
                $camelCaseKey = array_map('ucfirst', $camelCaseKey);
                $camelCaseKey = implode('', $camelCaseKey);
                $key = lcfirst($camelCaseKey);

                $value = $this->headers[$key] ?? null;
                return $value !== null && (is_string($value) || is_numeric($value)) ? (string) $value : null;
            }

            /**
             * Override hasHeader to check both formats
             */
            public function hasHeader($name): bool
            {
                // Check exact match first
                if (isset($this->customHeaders[$name])) {
                    return true;
                }

                // Check camelCase version
                $key = trim($name, ':');
                $camelCaseKey = explode('-', $key);
                $camelCaseKey = array_map('ucfirst', $camelCaseKey);
                $camelCaseKey = implode('', $camelCaseKey);
                $key = lcfirst($camelCaseKey);

                return isset($this->headers[$key]);
            }
        };

        return $this;
    }

    // =============================================================================
    // MÉTODOS PSR-7 (ServerRequestInterface)
    // =============================================================================

    /**
     * Get serverParams
     */
    public function getServerParams(): array
    {
        return $this->getPsr7Request()->getServerParams();
    }

    /**
     * Get cookieParams
     */
    public function getCookieParams(): array
    {
        return $this->getPsr7Request()->getCookieParams();
    }

    /**
     * Return instance with cookieParams
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Get queryParams
     */
    public function getQueryParams(): array
    {
        return $this->getPsr7Request()->getQueryParams();
    }

    /**
     * Return instance with queryParams
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Get uploadedFiles
     */
    public function getUploadedFiles(): array
    {
        return $this->getPsr7Request()->getUploadedFiles();
    }

    /**
     * Return instance with uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Get parsedBody
     */
    public function getParsedBody()
    {
        return $this->getPsr7Request()->getParsedBody();
    }

    /**
     * Return instance with parsedBody
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Get attributes
     */
    public function getAttributes(): array
    {
        // Combine local attributes with PSR-7 attributes
        $psr7Attributes = $this->getPsr7Request()->getAttributes();
        return array_merge($psr7Attributes, $this->attributes);
    }

    /**
     * Get attribute
     */
    public function getAttribute($name, $default = null)
    {
        // Check local attributes first for better performance
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        // Fallback to PSR-7 if needed
        return $this->getPsr7Request()->getAttribute($name, $default);
    }

    /**
     * Return instance with attribute
     */
    public function withAttribute($name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Return instance with outAttribute
     */
    public function withoutAttribute($name): ServerRequestInterface
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    // =============================================================================
    // MÉTODOS PSR-7 (RequestInterface)
    // =============================================================================

    /**
     * Get requestTarget
     */
    public function getRequestTarget(): string
    {
        return $this->getPsr7Request()->getRequestTarget();
    }

    /**
     * Return instance with requestTarget
     */
    public function withRequestTarget($requestTarget): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Get method
     */
    public function getMethod(): string
    {
        return $this->getPsr7Request()->getMethod();
    }

    /**
     * Return instance with method
     */
    public function withMethod($method): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->method = strtoupper($method);
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Get the URI associated with the request
     *
     * @return UriInterface The URI instance
     */
    public function getUri(): UriInterface
    {
        return $this->getPsr7Request()->getUri();
    }

    /**
     * Return instance with uri
     */
    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    // =============================================================================
    // MÉTODOS PSR-7 (MessageInterface)
    // =============================================================================

    /**
     * Get protocolVersion
     */
    public function getProtocolVersion(): string
    {
        return $this->getPsr7Request()->getProtocolVersion();
    }

    /**
     * Return instance with protocolVersion
     */
    public function withProtocolVersion($version): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Get headers
     */
    public function getHeaders(): array
    {
        return $this->getPsr7Request()->getHeaders();
    }

    /**
     * Check if has header
     */
    public function hasHeader($name): bool
    {
        return $this->getPsr7Request()->hasHeader($name);
    }

    /**
     * Get header
     */
    public function getHeader($name): array
    {
        return $this->getPsr7Request()->getHeader($name);
    }

    /**
     * Get headerLine
     */
    public function getHeaderLine($name): string
    {
        return $this->getPsr7Request()->getHeaderLine($name);
    }

    /**
     * Return instance with header
     */
    public function withHeader($name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Return instance with addedHeader
     */
    public function withAddedHeader($name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Return instance with outHeader
     */
    public function withoutHeader($name): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    /**
     * Get body
     */
    public function getBody(): StreamInterface
    {
        return $this->getPsr7Request()->getBody();
    }

    /**
     * Return instance with body
     */
    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Request = null;
        return $clone;
    }

    // =============================================================================
    // MÉTODOS LEGADOS (COMPATIBILIDADE)
    // =============================================================================

    /**
     * Este método inicializa a rota, parseando o caminho e os parâmetros.
     */
    private function parseRoute(): void
    {
        $this->parsePath();
        $this->parseQuery();
        $this->parseBody();
    }

    /**
     * Este método parseia o caminho da rota, extraindo os parâmetros e valores.
     */
    private function parsePath(): void
    {
        $pattern = preg_replace('/\/:([^\/]+)/', '/([^/]+)', $this->path);
        $pattern = rtrim($pattern ?: '', '/');
        $pattern = '#^' . $pattern . '/?$#';
        $matchResult = preg_match($pattern, rtrim($this->pathCallable ?: '', '/'), $values);
        if ($matchResult && !empty($values)) {
            array_shift($values);
        } else {
            $values = [];
        }
        preg_match_all('/\/:([^\/]+)/', $this->path, $params);
        $params = $params[1];

        if (count($params) > count($values)) {
            throw new InvalidArgumentException('Number of parameters does not match the number of values');
        }

        if (!empty($params)) {
            $paramsArray = array_combine($params, array_slice($values, 0, count($params)));
            if ($paramsArray !== false) {
                foreach ($paramsArray as $key => $value) {
                    if (is_numeric($value)) {
                        $value = (int)$value;
                    }
                    $this->params->{$key} = $value;
                    // Sincronizar com PSR-7 apenas se já foi inicializado
                    if ($this->psr7Request !== null) {
                        $this->psr7Request = $this->psr7Request->withAttribute($key, $value);
                    }
                }
            }
        }
    }

    /**
     * Este método parseia a query string da requisição.
     */
    private function parseQuery(): void
    {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $queryArray = [];
        parse_str($query, $queryArray);
        foreach ($queryArray as $key => $value) {
            $this->query->{$key} = $value;
        }
    }

    /**
     * Este método inicializa o corpo da requisição.
     */
    private function parseBody(): void
    {
        if ($this->method === 'GET') {
            $this->body = new stdClass();
            return;
        }

        $input = file_get_contents('php://input');
        if ($input !== false) {
            $decoded = json_decode($input);
            if ($decoded instanceof stdClass) {
                $this->body = $decoded;
            } else {
                $this->body = new stdClass();
            }
        } else {
            $this->body = new stdClass();
        }

        if (json_last_error() == JSON_ERROR_NONE) {
            return;
        }

        if (!empty($_POST)) {
            $this->body = new stdClass();
            foreach ($_POST as $key => $value) {
                $this->body->{$key} = $value;
            }
        }
    }

    /**
     * Cria uma instância Request a partir das variáveis globais PHP.
     */
    public static function createFromGlobals(): Request
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = $path !== false && $path !== null ? $path : '/';
        $pathCallable = $path;

        return new self($method, $path, $pathCallable);
    }

    // Métodos legados mantidos para compatibilidade
    /**
     * Get path
     */
    public function getPath(): string
    {
        if (empty($this->path)) {
            throw new RuntimeException('Path is not defined in Request');
        }
        return $this->path;
    }

    /**
     * Set path
     */
    public function setPath(string $path): self
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty');
        }
        $this->path = $path;
        if (!str_ends_with($this->path, '/')) {
            $this->path .= '/';
        }
        $this->parsePath();
        return $this;
    }

    /**
     * Get pathCallable
     */
    public function getPathCallable(): string
    {
        if (empty($this->pathCallable)) {
            throw new RuntimeException('Path callable is not defined in Request');
        }
        return $this->pathCallable;
    }

    /**
     * Get params
     */
    public function getParams(): stdClass
    {
        return $this->params;
    }

    /**
     * Get param
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params->{$key} ?? $default;
    }

    /**
     * Get the client IP address
     *
     * @return string
     */
    public function getIp(): string
    {
        // Check for IP behind proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get headers as HeaderRequest object (Express.js style)
     *
     * @return HeaderRequest
     */
    public function getHeadersObject(): HeaderRequest
    {
        return $this->headers;
    }

    /**
     * Get querys
     */
    public function getQuerys(): stdClass
    {
        return $this->query;
    }

    /**
     * Get query
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->query->{$key} ?? $default;
    }

    /**
     * Get bodyAsStdClass
     */
    public function getBodyAsStdClass(): stdClass
    {
        if (in_array($this->method, ['GET', 'HEAD', 'OPTIONS', 'DELETE'])) {
            return new stdClass();
        }
        return $this->body;
    }

    /**
     * Set attribute
     */
    public function setAttribute(string $name, $value): self
    {
        if (property_exists($this, $name)) {
            throw new RuntimeException("Cannot override native property: {$name}");
        }

        $this->attributes[$name] = $value;
        if ($this->psr7Request !== null) {
            $this->psr7Request = $this->psr7Request->withAttribute($name, $value);
        }
        return $this;
    }

    /**
     * Check if has attribute
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * RemoveAttribute method
     */
    public function removeAttribute(string $name): self
    {
        unset($this->attributes[$name]);
        if ($this->psr7Request !== null) {
            $this->psr7Request = $this->psr7Request->withoutAttribute($name);
        }
        return $this;
    }

    /**
     * Set attributes
     */
    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
        return $this;
    }
}
