<?php

namespace PivotPHP\Core\Http;

use PivotPHP\Core\Http\Contracts\ExpressResponseInterface;
use PivotPHP\Core\Http\Psr7\Response as Psr7Response;
use PivotPHP\Core\Http\Pool\Psr7Pool;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Express.js-style Response Adapter
 *
 * This class provides an Express.js-compatible API as a facade/adapter over PSR-7 Response.
 * It eliminates the redundancy of the previous hybrid implementation by using composition
 * instead of maintaining parallel data structures.
 *
 * Architecture:
 * - Wraps a PSR-7 Response instance (composition over inheritance)
 * - Provides Express.js convenience methods
 * - Implements PSR-7 ResponseInterface for middleware compatibility
 * - No data duplication - single source of truth (PSR-7)
 *
 * @package PivotPHP\Core\Http
 */
class ExpressResponse implements ExpressResponseInterface, ResponseInterface
{
    /**
     * PSR-7 Response instance (single source of truth)
     */
    private ResponseInterface $psr7Response;

    /**
     * Test mode flag (prevents actual output)
     */
    private bool $testMode = false;

    /**
     * Auto-emit disabled flag
     */
    private bool $disableAutoEmit = false;

    /**
     * Response sent flag
     */
    private bool $sent = false;

    /**
     * Streaming mode flag
     */
    private bool $streaming = false;

    /**
     * Stream buffer size
     */
    private int $streamBufferSize = 8192;

    /**
     * JSON encoding flags
     */
    private const JSON_ENCODE_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * Constructor
     *
     * @param ResponseInterface|null $psr7Response PSR-7 response to wrap
     */
    public function __construct(?ResponseInterface $psr7Response = null)
    {
        $this->psr7Response = $psr7Response ?? Psr7Pool::getResponse();
    }

    /**
     * Destructor - return PSR-7 object to pool
     */
    public function __destruct()
    {
        if (!$this->streaming) {
            Psr7Pool::returnResponse($this->psr7Response);
        }
    }

    // ========================================================================
    // EXPRESS.JS CONVENIENCE METHODS
    // ========================================================================

    /**
     * Set the HTTP status code
     *
     * @param int $code Status code
     * @return static
     */
    public function status(int $code): self
    {
        $this->psr7Response = $this->psr7Response->withStatus($code);
        return $this;
    }

    /**
     * Set a response header
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return static
     */
    public function header(string $name, string $value): self
    {
        $this->psr7Response = $this->psr7Response->withHeader($name, $value);
        return $this;
    }

    /**
     * Send a JSON response
     *
     * @param mixed $data Data to encode
     * @return static
     */
    public function json(mixed $data): self
    {
        $this->header('Content-Type', 'application/json; charset=utf-8');

        // Use JSON pooling for better performance
        $encoded = $this->encodeJson($data);

        $this->psr7Response = $this->psr7Response->withBody(
            Psr7Pool::getStream($encoded)
        );

        if (!$this->testMode && !$this->disableAutoEmit) {
            $this->emit();
        }

        return $this;
    }

    /**
     * Send a plain text response
     *
     * @param mixed $text Text to send
     * @return static
     */
    public function text(mixed $text): self
    {
        $this->header('Content-Type', 'text/plain; charset=utf-8');

        $textString = $this->convertToString($text);

        $this->psr7Response = $this->psr7Response->withBody(
            Psr7Pool::getStream($textString)
        );

        if (!$this->testMode && !$this->disableAutoEmit) {
            $this->emit();
        }

        return $this;
    }

    /**
     * Send an HTML response
     *
     * @param mixed $html HTML content
     * @return static
     */
    public function html(mixed $html): self
    {
        $this->header('Content-Type', 'text/html; charset=utf-8');

        $htmlString = $this->convertToString($html);

        $this->psr7Response = $this->psr7Response->withBody(
            Psr7Pool::getStream($htmlString)
        );

        if (!$this->testMode && !$this->disableAutoEmit) {
            $this->emit();
        }

        return $this;
    }

    /**
     * Send a redirect response
     *
     * @param string $url URL to redirect to
     * @param int $code Redirect code
     * @return static
     */
    public function redirect(string $url, int $code = 302): self
    {
        $this->status($code);
        $this->header('Location', $url);
        return $this;
    }

    /**
     * Set a cookie
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $expire Expiration time
     * @param string $path Cookie path
     * @param string $domain Cookie domain
     * @param bool $secure Secure flag
     * @param bool $httponly HTTP only flag
     * @return static
     */
    public function cookie(
        string $name,
        string $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true
    ): self {
        if (!$this->testMode) {
            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
        return $this;
    }

    /**
     * Clear a cookie
     *
     * @param string $name Cookie name
     * @param string $path Cookie path
     * @param string $domain Cookie domain
     * @return static
     */
    public function clearCookie(
        string $name,
        string $path = '/',
        string $domain = ''
    ): self {
        if (!$this->testMode) {
            setcookie($name, '', time() - 3600, $path, $domain);
        }
        return $this;
    }

    /**
     * Send an error response
     *
     * @param int $code HTTP error code
     * @param string $message Error message
     * @return static
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
     * Send a success response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @return static
     */
    public function success(mixed $data = null, string $message = 'Success'): self
    {
        $response = ['message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->json($response);
    }

    /**
     * Send a response with any data
     *
     * @param mixed $data Data to send
     * @return static
     */
    public function send(mixed $data = ''): self
    {
        if (is_array($data) || is_object($data)) {
            return $this->json($data);
        }

        return $this->text($data);
    }

    // ========================================================================
    // STREAMING METHODS
    // ========================================================================

    /**
     * Set the buffer size for streaming
     *
     * @param int $size Buffer size
     * @return static
     */
    public function setStreamBufferSize(int $size): self
    {
        $this->streamBufferSize = $size;
        return $this;
    }

    /**
     * Start a streaming response
     *
     * @param string|null $contentType Content type
     * @return static
     */
    public function startStream(?string $contentType = null): self
    {
        $this->streaming = true;

        if ($contentType !== null) {
            $this->header('Content-Type', $contentType);
        }

        $this->header('Cache-Control', 'no-cache');
        $this->header('Connection', 'keep-alive');
        $this->header('X-Accel-Buffering', 'no');

        if (!$this->testMode) {
            // Emit headers
            http_response_code($this->psr7Response->getStatusCode());
            foreach ($this->psr7Response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header("{$name}: {$value}", false);
                }
            }
            flush();
        }

        return $this;
    }

    /**
     * Write data to an active stream
     *
     * @param string $data Data to write
     * @param bool $flush Flush output buffer
     * @return static
     */
    public function write(string $data, bool $flush = true): self
    {
        if (!$this->testMode) {
            echo $data;
            if ($flush) {
                flush();
            }
        }

        return $this;
    }

    /**
     * Write JSON data to an active stream
     *
     * @param mixed $data Data to encode and write
     * @param bool $flush Flush output buffer
     * @return static
     */
    public function writeJson(mixed $data, bool $flush = true): self
    {
        $encoded = $this->encodeJson($data);
        return $this->write($encoded, $flush);
    }

    /**
     * Stream a file to the client
     *
     * @param string $filePath Path to file
     * @param array $headers Additional headers
     * @return static
     */
    public function streamFile(string $filePath, array $headers = []): self
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return $this->error(404, 'File not found');
        }

        $this->streaming = true;

        // Set default headers
        $this->header('Content-Type', mime_content_type($filePath) ?: 'application/octet-stream');
        $this->header('Content-Length', (string) filesize($filePath));

        // Apply additional headers
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        if (!$this->testMode) {
            // Emit headers
            http_response_code($this->psr7Response->getStatusCode());
            foreach ($this->psr7Response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header("{$name}: {$value}", false);
                }
            }

            // Stream file
            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                return $this->error(500, 'Failed to open file');
            }

            while (!feof($handle)) {
                $chunk = fread($handle, max(1, $this->streamBufferSize));
                if ($chunk !== false) {
                    echo $chunk;
                    flush();
                }
            }
            fclose($handle);
        }

        return $this;
    }

    /**
     * Stream a resource to the client
     *
     * @param mixed $resource Stream resource
     * @param string|null $contentType Content type
     * @return static
     */
    public function streamResource(mixed $resource, ?string $contentType = null): self
    {
        if (!is_resource($resource)) {
            return $this->error(500, 'Invalid resource');
        }

        $this->streaming = true;

        if ($contentType !== null) {
            $this->header('Content-Type', $contentType);
        }

        if (!$this->testMode) {
            // Emit headers
            http_response_code($this->psr7Response->getStatusCode());
            foreach ($this->psr7Response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header("{$name}: {$value}", false);
                }
            }

            // Stream resource
            while (!feof($resource)) {
                $chunk = fread($resource, max(1, $this->streamBufferSize));
                if ($chunk !== false) {
                    echo $chunk;
                    flush();
                }
            }
        }

        return $this;
    }

    /**
     * Send a Server-Sent Event
     *
     * @param mixed $data Event data
     * @param string|null $event Event name
     * @param string|null $id Event ID
     * @param int|null $retry Retry interval
     * @return static
     */
    public function sendEvent(
        mixed $data,
        ?string $event = null,
        ?string $id = null,
        ?int $retry = null
    ): self {
        // Auto-start streaming if not already started
        if (!$this->streaming) {
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

        $dataString = is_string($data) ? $data : $this->encodeJson($data);
        $output .= "data: {$dataString}\n\n";

        return $this->write($output);
    }

    /**
     * Send a heartbeat event
     *
     * @return static
     */
    public function sendHeartbeat(): self
    {
        return $this->write(": heartbeat\n\n");
    }

    /**
     * End a streaming response
     *
     * @return static
     */
    public function endStream(): self
    {
        $this->streaming = false;
        $this->sent = true;
        return $this;
    }

    /**
     * Check if response is currently streaming
     *
     * @return bool
     */
    public function isStreaming(): bool
    {
        return $this->streaming;
    }

    // ========================================================================
    // STATUS AND CONTROL METHODS
    // ========================================================================

    /**
     * Get the current HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->psr7Response->getStatusCode();
    }

    /**
     * Get body as string (legacy compatibility)
     *
     * @return string
     */
    public function getBodyAsString(): string
    {
        return (string) $this->psr7Response->getBody();
    }

    /**
     * Get body as string (alias)
     *
     * @return string
     */
    public function getBodyString(): string
    {
        return $this->getBodyAsString();
    }

    /**
     * Enable or disable test mode
     *
     * @param bool $testMode Test mode flag
     * @return static
     */
    public function setTestMode(bool $testMode): self
    {
        $this->testMode = $testMode;
        return $this;
    }

    /**
     * Check if test mode is enabled
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Disable automatic emission of response
     *
     * @param bool $disable Disable flag
     * @return static
     */
    public function disableAutoEmit(bool $disable = true): self
    {
        $this->disableAutoEmit = $disable;
        return $this;
    }

    /**
     * Check if response has been sent
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Manually emit the response
     *
     * @param bool $includeHeaders Include headers in emission
     * @return void
     */
    public function emit(bool $includeHeaders = true): void
    {
        if ($this->sent || $this->testMode) {
            return;
        }

        if ($includeHeaders) {
            http_response_code($this->psr7Response->getStatusCode());
            foreach ($this->psr7Response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header("{$name}: {$value}", false);
                }
            }
        }

        echo (string) $this->psr7Response->getBody();
        $this->sent = true;
    }

    /**
     * Reset the sent state (for testing)
     *
     * @return static
     */
    public function resetSentState(): self
    {
        $this->sent = false;
        return $this;
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Encode data to JSON with pooling optimization
     *
     * @param mixed $data Data to encode
     * @return string
     */
    private function encodeJson(mixed $data): string
    {
        try {
            // Use JSON pooling for better performance
            $encoded = JsonBufferPool::encodeWithPool($data, self::JSON_ENCODE_FLAGS);

            // encodeWithPool returns string, but we check for safety
            if (!is_string($encoded) || $encoded === '') {
                error_log('JSON encoding failed: ' . json_last_error_msg());
                return '{}';
            }

            return $encoded;
        } catch (\JsonException $e) {
            // Sanitize invalid data by returning empty JSON object
            error_log('JSON encoding error: ' . $e->getMessage());
            return '{}';
        }
    }

    /**
     * Convert value to string representation
     *
     * @param mixed $value Value to convert
     * @return string
     */
    private function convertToString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return $this->encodeJson($value);
    }

    // ========================================================================
    // PSR-7 ResponseInterface DELEGATION METHODS
    // ========================================================================

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase(): string
    {
        return $this->psr7Response->getReasonPhrase();
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        $newResponse = clone $this;
        $newResponse->psr7Response = $this->psr7Response->withStatus($code, $reasonPhrase);
        return $newResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->psr7Response->getProtocolVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version): ResponseInterface
    {
        $newResponse = clone $this;
        $newResponse->psr7Response = $this->psr7Response->withProtocolVersion($version);
        return $newResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->psr7Response->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name): bool
    {
        return $this->psr7Response->hasHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name): array
    {
        return $this->psr7Response->getHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name): string
    {
        return $this->psr7Response->getHeaderLine($name);
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value): ResponseInterface
    {
        $newResponse = clone $this;
        $newResponse->psr7Response = $this->psr7Response->withHeader($name, $value);
        return $newResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value): ResponseInterface
    {
        $newResponse = clone $this;
        $newResponse->psr7Response = $this->psr7Response->withAddedHeader($name, $value);
        return $newResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name): ResponseInterface
    {
        $newResponse = clone $this;
        $newResponse->psr7Response = $this->psr7Response->withoutHeader($name);
        return $newResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->psr7Response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body): ResponseInterface
    {
        $newResponse = clone $this;
        $newResponse->psr7Response = $this->psr7Response->withBody($body);
        return $newResponse;
    }
}
