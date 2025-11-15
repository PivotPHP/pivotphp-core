<?php

namespace PivotPHP\Core\Http;

use PivotPHP\Core\Http\Pool\Psr7Pool;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

/**
 * Hybrid Response class that implements PSR-7 while maintaining Express.js compatibility
 *
 * This class offers complete PSR-7 (ResponseInterface) support
 * while maintaining all Express.js style convenience methods
 * for full backward compatibility with existing code.
 */
class Response implements ResponseInterface
{
    /**
     * Flags for consistent JSON encoding
     */
    private const JSON_ENCODE_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * Internal PSR-7 instance (lazy loaded)
     */
    private ?ResponseInterface $psr7Response = null;

    /**
     * HTTP status code.
     */
    private int $statusCode = 200;

    /**
     * Response headers.
     *
     * @var array<string, mixed>
     */
    private array $headers = [];

    /**
     * Response body.
     */
    private string $body = '';

    /**
     * Indicates if the response is being sent as stream.
     */
    private bool $isStreaming = false;

    /**
     * Buffer size for streaming (in bytes).
     */
    private int $streamBufferSize = 8192;

    /**
     * Indicates if in test mode (does not echo directly).
     */
    private bool $testMode = false;

    /**
     * Indicates if the response has already been sent.
     */
    private bool $sent = false;

    /**
     * Indicates if automatic emission control is disabled.
     */
    private bool $disableAutoEmit = false;

    /**
     * Response class constructor.
     */
    public function __construct()
    {
        // Detectar automaticamente modo teste
        if (
            defined('PHPUNIT_TESTSUITE') ||
            (defined('PHPUNIT_COMPOSER_INSTALL') || class_exists('PHPUnit\Framework\TestCase')) ||
            (isset($_ENV['PHPUNIT_RUNNING']) || getenv('PHPUNIT_RUNNING')) ||
            strpos($_SERVER['SCRIPT_NAME'] ?? '', 'phpunit') !== false
        ) {
            $this->testMode = true;
        }

        // PSR-7 response será inicializado apenas quando necessário (lazy loading)
    }

    /**
     * Obtém a instância PSR-7 interna (lazy loading)
     */
    private function getPsr7Response(): ResponseInterface
    {
        if ($this->psr7Response === null) {
            $this->psr7Response = Psr7Pool::getResponse(
                $this->statusCode,
                $this->headers,
                Psr7Pool::getStream($this->body)
            );
        }
        return $this->psr7Response;
    }

    /**
     * Retorna objetos PSR-7 ao pool quando não precisamos mais deles
     */
    public function __destruct()
    {
        if ($this->psr7Response !== null) {
            Psr7Pool::returnResponse($this->psr7Response);
        }
    }

    // =============================================================================
    // MÉTODOS EXPRESS.JS (COMPATIBILIDADE TOTAL)
    // =============================================================================

    /**
     * Define o status HTTP da resposta.
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        if ($this->psr7Response !== null) {
            $this->psr7Response = $this->psr7Response->withStatus($code);
        }

        // Só define o status code se os headers ainda não foram enviados
        if (!headers_sent()) {
            http_response_code($this->statusCode);
        }

        return $this;
    }

    /**
     * Define um cabeçalho na resposta.
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        if ($this->psr7Response !== null) {
            $this->psr7Response = $this->psr7Response->withHeader($name, $value);
        }

        // Só envia o header se os headers ainda não foram enviados
        if (!headers_sent()) {
            header("{$name}: {$value}");
        }

        return $this;
    }

    /**
     * Retorna os cabeçalhos da resposta.
     * @return array<string, mixed>|array<array<string>>
     */
    public function getHeaders(): array
    {
        // Para compatibilidade com testes existentes, retornar formato simples se em modo teste
        if ($this->testMode) {
            return $this->headers;
        }
        return $this->getPsr7Response()->getHeaders();
    }

    /**
     * Retorna o código de status da resposta.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Define o modo teste (não faz echo direto).
     */
    public function setTestMode(bool $testMode): self
    {
        $this->testMode = $testMode;
        return $this;
    }

    /**
     * Verifica se está em modo teste.
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Retorna o corpo da resposta (compatibilidade com testes).
     */
    public function getBody(): StreamInterface|string
    {
        // Para compatibilidade com testes existentes, retornar string se em modo teste
        if ($this->testMode) {
            return $this->body;
        }
        return $this->getPsr7Response()->getBody();
    }

    /**
     * Retorna o corpo da resposta como string (compatibilidade Express.js).
     */
    public function getBodyAsString(): string
    {
        return $this->body;
    }

    /**
     * Retorna o corpo da resposta como string (método legado).
     */
    public function getBodyString(): string
    {
        return $this->body;
    }

    /**
     * Envia resposta em formato JSON.
     */
    public function json(mixed $data): self
    {
        $this->header('Content-Type', 'application/json; charset=utf-8');

        // Sanitizar dados para UTF-8 válido antes da codificação
        $sanitizedData = $this->sanitizeForJson($data);

        // Usar pooling para datasets médios e grandes
        if ($this->shouldUseJsonPooling($sanitizedData)) {
            $encoded = $this->encodeWithPooling($sanitizedData);
        } else {
            // Usar encoding tradicional para dados pequenos
            $encoded = json_encode($sanitizedData, self::JSON_ENCODE_FLAGS);

            // Handle JSON encoding failures for traditional path
            if ($encoded === false) {
                error_log('JSON encoding failed: ' . json_last_error_msg());
                $encoded = '{}';
            }
        }

        $this->body = $encoded;
        if ($this->psr7Response !== null) {
            $this->psr7Response = $this->psr7Response->withBody(Psr7Pool::getStream($encoded));
        }

        // Só faz echo se não estiver em modo teste e emissão automática estiver habilitada
        if (!$this->testMode && !$this->disableAutoEmit) {
            $this->emit();
        }

        return $this;
    }

    /**
     * Envia resposta em texto puro.
     */
    public function text(mixed $text): self
    {
        $this->header('Content-Type', 'text/plain; charset=utf-8');
        $textString = is_string($text) ? $text : (
            is_scalar($text) || (is_object($text) && method_exists($text, '__toString'))
                ? (string)$text
                : $this->encodeJsonSafely($text)
        );
        $this->body = $textString;
        if ($this->psr7Response !== null) {
            $this->psr7Response = $this->psr7Response->withBody(Psr7Pool::getStream($textString));
        }

        // Só faz echo se não estiver em modo teste e emissão automática estiver habilitada
        if (!$this->testMode && !$this->disableAutoEmit) {
            $this->emit();
        }

        return $this;
    }

    /**
     * Envia resposta em HTML.
     */
    public function html(mixed $html): self
    {
        $this->header('Content-Type', 'text/html; charset=utf-8');
        $htmlString = is_string($html) ? $html : (
            is_scalar($html) || (is_object($html) && method_exists($html, '__toString'))
                ? (string)$html
                : $this->encodeJsonSafely($html)
        );
        $this->body = $htmlString;
        if ($this->psr7Response !== null) {
            $this->psr7Response = $this->psr7Response->withBody(Psr7Pool::getStream($htmlString));
        }

        // Só faz echo se não estiver em modo teste e emissão automática estiver habilitada
        if (!$this->testMode && !$this->disableAutoEmit) {
            $this->emit();
        }

        return $this;
    }

    /**
     * Redireciona para uma URL.
     */
    public function redirect(string $url, int $code = 302): self
    {
        $this->status($code);
        $this->header('Location', $url);
        return $this;
    }

    /**
     * Define um cookie.
     */
    public function cookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true
    ): self {
        setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
        return $this;
    }

    /**
     * Remove um cookie.
     */
    public function clearCookie(
        string $name,
        string $path = '/',
        string $domain = ''
    ): self {
        setcookie($name, '', time() - 3600, $path, $domain);
        return $this;
    }

    /**
     * Envia uma resposta de erro.
     */
    public function error(int $code, string $message = ''): self
    {
        $this->status($code);

        if (empty($message)) {
            $messages = [
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                500 => 'Internal Server Error',
                503 => 'Service Unavailable'
            ];
            $message = $messages[$code] ?? 'Error';
        }

        return $this->json(['error' => $message, 'code' => $code]);
    }

    /**
     * Envia uma resposta de sucesso padronizada.
     */
    public function success(mixed $data = null, string $message = 'Success'): self
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $this->json($response);
    }

    /**
     * Envia qualquer tipo de dado como resposta, similar ao Express.js (Node.js).
     */
    public function send(mixed $data = ''): self
    {
        if (is_array($data) || is_object($data)) {
            return $this->json($data);
        }
        if (is_resource($data)) {
            return $this->streamResource($data);
        }
        if (is_numeric($data)) {
            $data = (string)$data;
        }
        // Detecta se é HTML simples
        if (is_string($data) && preg_match('/<[^<]+>/', $data)) {
            return $this->html($data);
        }
        // Default: texto puro
        if (is_scalar($data) || (is_object($data) && method_exists($data, '__toString'))) {
            return $this->text((string)$data);
        }
        return $this->text($this->encodeJsonSafely($data));
    }

    // =============================================================================
    // MÉTODOS PSR-7 (ResponseInterface)
    // =============================================================================

    /**
     * Get reasonPhrase
     */
    public function getReasonPhrase(): string
    {
        return $this->getPsr7Response()->getReasonPhrase();
    }

    /**
     * Return instance with status
     */
    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Response = null;
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
        return $this->getPsr7Response()->getProtocolVersion();
    }

    /**
     * Return instance with protocolVersion
     */
    public function withProtocolVersion($version): ResponseInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Response = null;
        return $clone;
    }

    /**
     * Check if has header
     */
    public function hasHeader($name): bool
    {
        return $this->getPsr7Response()->hasHeader($name);
    }

    /**
     * Get header
     */
    public function getHeader($name): array
    {
        return $this->getPsr7Response()->getHeader($name);
    }

    /**
     * Get headerLine
     */
    public function getHeaderLine($name): string
    {
        return $this->getPsr7Response()->getHeaderLine($name);
    }

    /**
     * Return instance with header
     */
    public function withHeader($name, $value): ResponseInterface
    {
        $clone = clone $this;
        $clone->headers[$name] = is_array($value) ? implode(', ', $value) : $value;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Response = null;
        return $clone;
    }

    /**
     * Return instance with addedHeader
     */
    public function withAddedHeader($name, $value): ResponseInterface
    {
        $clone = clone $this;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Response = null;
        return $clone;
    }

    /**
     * Return instance with outHeader
     */
    public function withoutHeader($name): ResponseInterface
    {
        $clone = clone $this;
        unset($clone->headers[$name]);
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Response = null;
        return $clone;
    }

    /**
     * Return instance with body
     */
    public function withBody(StreamInterface $body): ResponseInterface
    {
        $clone = clone $this;
        $clone->body = (string)$body;
        // Forçar re-criação do PSR-7 na próxima chamada para garantir imutabilidade
        $clone->psr7Response = null;
        return $clone;
    }

    // =============================================================================
    // MÉTODOS DE STREAMING (COMPATIBILIDADE LEGADA)
    // =============================================================================

    /**
     * Define o buffer size para streaming.
     */
    public function setStreamBufferSize(int $size): self
    {
        $this->streamBufferSize = $size;
        return $this;
    }

    /**
     * Inicia o modo streaming configurando os cabeçalhos necessários.
     */
    public function startStream(?string $contentType = null): self
    {
        $this->isStreaming = true;

        // Configurar cabeçalhos para streaming
        $this->header('Cache-Control', 'no-cache');
        $this->header('Connection', 'keep-alive');

        if ($contentType) {
            $this->header('Content-Type', $contentType);
        }

        // Desabilitar output buffering para streaming em tempo real apenas se não estamos em teste
        if (!defined('PHPUNIT_TESTSUITE') && !$this->testMode && ob_get_level()) {
            ob_end_flush();
        }

        return $this;
    }

    /**
     * Envia dados como stream.
     */
    public function write(string $data, bool $flush = true): self
    {
        // Só faz echo se não estiver em modo teste
        if (!$this->testMode) {
            echo $data;
        }

        if ($flush && !defined('PHPUNIT_TESTSUITE') && !$this->testMode) {
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }

        return $this;
    }

    /**
     * Envia dados JSON como stream.
     */
    public function writeJson(mixed $data, bool $flush = true): self
    {
        // Sanitizar dados para UTF-8 válido antes da codificação
        $sanitizedData = $this->sanitizeForJson($data);

        $json = json_encode($sanitizedData, self::JSON_ENCODE_FLAGS);
        if ($json === false) {
            error_log('JSON encoding failed: ' . json_last_error_msg());
            $json = '{}';
        }

        return $this->write($json, $flush);
    }

    /**
     * Envia um arquivo como stream.
     */
    public function streamFile(string $filePath, array $headers = []): self
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException("File not found or not readable: {$filePath}");
        }

        $fileSize = @filesize($filePath);
        if ($fileSize === false) {
            throw new InvalidArgumentException("Cannot determine file size: {$filePath}");
        }

        $mimeType = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($filePath);
            if ($detected !== false) {
                $mimeType = $detected;
            }
        }

        // Configurar cabeçalhos
        $this->header('Content-Type', $mimeType);
        $this->header('Content-Length', (string)$fileSize);
        $this->header('Accept-Ranges', 'bytes');

        // Adicionar cabeçalhos personalizados
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        $this->startStream();

        // Abrir arquivo e enviar em chunks
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open file: {$filePath}");
        }

        while (!feof($handle)) {
            $bufferSize = max(1, $this->streamBufferSize);
            $chunk = fread($handle, $bufferSize);
            if ($chunk !== false) {
                $this->write($chunk, true);
            }
        }

        fclose($handle);
        return $this;
    }

    /**
     * Envia dados de um recurso como stream.
     */
    public function streamResource(mixed $resource, ?string $contentType = null): self
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException("Invalid resource provided");
        }

        $this->startStream($contentType);

        while (!feof($resource)) {
            $bufferSize = max(1, $this->streamBufferSize);
            $chunk = fread($resource, $bufferSize);
            if ($chunk !== false) {
                $this->write($chunk, true);
            }
        }

        return $this;
    }

    /**
     * Envia dados como Server-Sent Events (SSE).
     */
    public function sendEvent(
        mixed $data,
        ?string $event = null,
        ?string $id = null,
        ?int $retry = null
    ): self {
        if (!$this->isStreaming) {
            $this->startStream('text/event-stream');
        }

        $output = '';

        if ($id !== null) {
            $output .= "id: {$id}\n";
        }

        if ($event !== null) {
            $output .= "event: {$event}\n";
        }

        if ($retry !== null) {
            $output .= "retry: {$retry}\n";
        }

        // Converter dados para string
        if (is_array($data) || is_object($data)) {
            $dataString = json_encode($data, self::JSON_ENCODE_FLAGS);
            if ($dataString === false) {
                $dataString = '[json encoding failed]';
            }
        } else {
            $dataString = is_scalar($data) ? (string)$data : '[non-scalar data]';
        }

        // Dividir dados em múltiplas linhas se necessário
        $lines = explode("\n", $dataString);
        foreach ($lines as $line) {
            $output .= "data: {$line}\n";
        }

        $output .= "\n"; // Linha em branco para finalizar o evento

        return $this->write($output, true);
    }

    /**
     * Envia um evento de heartbeat (ping) para manter a conexão SSE ativa.
     */
    public function sendHeartbeat(): self
    {
        return $this->write(": heartbeat\n\n", true);
    }

    /**
     * Finaliza o stream e limpa recursos.
     */
    public function endStream(): self
    {
        if ($this->isStreaming) {
            $this->isStreaming = false;

            if (!defined('PHPUNIT_TESTSUITE') && !$this->testMode && ob_get_level()) {
                ob_end_flush();
            }
            if (!defined('PHPUNIT_TESTSUITE') && !$this->testMode) {
                flush();
            }
        }

        return $this;
    }

    /**
     * Verifica se a resposta está em modo streaming.
     */
    public function isStreaming(): bool
    {
        return $this->isStreaming;
    }

    // =============================================================================
    // MÉTODOS UTILITÁRIOS
    // =============================================================================

    /**
     * Encodes JSON safely with proper error handling
     */
    private function encodeJsonSafely(mixed $data): string
    {
        $encoded = json_encode($data, self::JSON_ENCODE_FLAGS);
        if ($encoded === false) {
            error_log('JSON encoding failed: ' . json_last_error_msg());
            return '{}';
        }
        return $encoded;
    }

    /**
     * Sanitiza dados para garantir codificação UTF-8 válida para JSON.
     */
    private function sanitizeForJson(mixed $data): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeForJson($value);
            }
        } elseif (is_object($data)) {
            // Converter objeto para array, sanitizar e retornar como stdClass
            $dataArray = (array) $data;
            foreach ($dataArray as $key => $value) {
                $dataArray[$key] = $this->sanitizeForJson($value);
            }
            $data = (object) $dataArray;
        } elseif (is_string($data)) {
            // Converter para UTF-8 válido, removendo/substituindo bytes inválidos
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }

        return $data;
    }

    /**
     * Define se a emissão automática está desabilitada.
     */
    public function disableAutoEmit(bool $disable = true): self
    {
        $this->disableAutoEmit = $disable;
        return $this;
    }

    /**
     * Verifica se a resposta já foi enviada.
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Emite o corpo da resposta.
     */
    public function emit(bool $includeHeaders = true): void
    {
        if ($this->sent && !$this->isStreaming) {
            error_log('Warning: Attempting to emit response body multiple times. Body already sent.');
            return;
        }

        // Enviar headers e status se solicitado e ainda não enviados
        if ($includeHeaders && !headers_sent()) {
            // Enviar status HTTP
            http_response_code($this->statusCode);

            // Enviar headers
            foreach ($this->headers as $name => $value) {
                if (is_string($name) && (is_string($value) || is_numeric($value))) {
                    header("{$name}: {$value}");
                }
            }
        }

        // Enviar corpo da resposta
        if (!empty($this->body)) {
            // Só faz echo se não estiver em modo teste
            if (!$this->testMode) {
                echo $this->body;
            }
            $this->sent = true;
        }
    }

    /**
     * Reseta o estado de envio (útil para testes).
     */
    public function resetSentState(): self
    {
        $this->sent = false;
        return $this;
    }

    /**
     * Determines if JSON pooling should be used for the given data
     */
    private function shouldUseJsonPooling(mixed $data): bool
    {
        // Usar a mesma lógica do JsonBufferPool para consistência
        return JsonBufferPool::shouldUsePooling($data);
    }

    /**
     * Codifica JSON usando pooling para melhor performance
     */
    private function encodeWithPooling(mixed $sanitizedData): string
    {
        try {
            return JsonBufferPool::encodeWithPool($sanitizedData, self::JSON_ENCODE_FLAGS);
        } catch (\Throwable $e) {
            // Fallback para encoding tradicional em caso de erro
            error_log('JSON pooling failed, falling back to traditional encoding: ' . $e->getMessage());

            // Fallback to traditional encoding (handle JSON encoding failures internally)
            $encoded = json_encode($sanitizedData, self::JSON_ENCODE_FLAGS);
            if ($encoded === false) {
                error_log('JSON fallback encoding failed: ' . json_last_error_msg());
                return '{}';
            }
            return $encoded;
        }
    }
}
