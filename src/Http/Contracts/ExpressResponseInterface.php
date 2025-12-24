<?php

namespace PivotPHP\Core\Http\Contracts;

/**
 * Express.js-style Response API Interface
 *
 * This interface defines the Express.js-compatible API for HTTP responses.
 * It provides convenient methods for sending responses without dealing with
 * the complexity of PSR-7 interfaces.
 *
 * This is the public-facing API that developers should use for working with responses.
 *
 * @package PivotPHP\Core\Http\Contracts
 */
interface ExpressResponseInterface
{
    // ========================================================================
    // EXPRESS.JS CONVENIENCE METHODS
    // ========================================================================

    /**
     * Set the HTTP status code
     *
     * @param int $code HTTP status code (e.g., 200, 404, 500)
     * @return static Fluent interface
     *
     * @example
     * $res->status(201)->json(['created' => true]);
     * $res->status(404)->send('Not Found');
     */
    public function status(int $code): self;

    /**
     * Set a response header
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return static Fluent interface
     *
     * @example
     * $res->header('Content-Type', 'application/json')
     *     ->header('X-Custom-Header', 'value')
     *     ->send('Hello');
     */
    public function header(string $name, string $value): self;

    /**
     * Send a JSON response
     *
     * Automatically sets Content-Type to application/json and encodes the data.
     *
     * @param mixed $data Data to encode as JSON
     * @return static
     *
     * @example
     * $res->json(['users' => $users]);
     * $res->status(201)->json(['id' => $newId]);
     */
    public function json(mixed $data): self;

    /**
     * Send a plain text response
     *
     * @param mixed $text Text to send
     * @return static
     *
     * @example
     * $res->text('Hello World');
     * $res->status(200)->text('Success');
     */
    public function text(mixed $text): self;

    /**
     * Send an HTML response
     *
     * @param mixed $html HTML content to send
     * @return static
     *
     * @example
     * $res->html('<h1>Welcome</h1>');
     * $res->status(200)->html($htmlTemplate);
     */
    public function html(mixed $html): self;

    /**
     * Send a redirect response
     *
     * @param string $url URL to redirect to
     * @param int $code HTTP redirect code (default: 302)
     * @return static
     *
     * @example
     * $res->redirect('/login');
     * $res->redirect('https://example.com', 301); // Permanent redirect
     */
    public function redirect(string $url, int $code = 302): self;

    /**
     * Set a cookie
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $expire Expiration time (Unix timestamp, default: 0)
     * @param string $path Cookie path (default: '/')
     * @param string $domain Cookie domain (default: '')
     * @param bool $secure HTTPS only (default: false)
     * @param bool $httponly HTTP only flag (default: true)
     * @return static
     *
     * @example
     * $res->cookie('session_id', 'abc123', time() + 3600);
     * $res->cookie('remember_me', 'yes', time() + 86400 * 30);
     */
    public function cookie(
        string $name,
        string $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true
    ): self;

    /**
     * Clear a cookie
     *
     * @param string $name Cookie name
     * @param string $path Cookie path (default: '/')
     * @param string $domain Cookie domain (default: '')
     * @return static
     *
     * @example
     * $res->clearCookie('session_id');
     */
    public function clearCookie(
        string $name,
        string $path = '/',
        string $domain = ''
    ): self;

    /**
     * Send an error response
     *
     * @param int $code HTTP error code
     * @param string $message Error message (default: '')
     * @return static
     *
     * @example
     * $res->error(404, 'User not found');
     * $res->error(500, 'Internal server error');
     */
    public function error(int $code, string $message = ''): self;

    /**
     * Send a success response with optional data
     *
     * @param mixed $data Response data (default: null)
     * @param string $message Success message (default: 'Success')
     * @return static
     *
     * @example
     * $res->success($userData);
     * $res->success(null, 'User deleted successfully');
     */
    public function success(mixed $data = null, string $message = 'Success'): self;

    /**
     * Send a response with any data
     *
     * @param mixed $data Data to send
     * @return static
     *
     * @example
     * $res->send('Hello World');
     * $res->send(['key' => 'value']);
     */
    public function send(mixed $data = ''): self;

    // ========================================================================
    // STREAMING METHODS (Server-Sent Events, File Streaming, etc.)
    // ========================================================================

    /**
     * Set the buffer size for streaming
     *
     * @param int $size Buffer size in bytes
     * @return static
     */
    public function setStreamBufferSize(int $size): self;

    /**
     * Start a streaming response
     *
     * @param string|null $contentType Content type (default: null)
     * @return static
     *
     * @example
     * $res->startStream('text/event-stream');
     * $res->write("data: Hello\n\n");
     */
    public function startStream(?string $contentType = null): self;

    /**
     * Write data to an active stream
     *
     * @param string $data Data to write
     * @param bool $flush Flush output buffer (default: true)
     * @return static
     *
     * @example
     * $res->startStream()
     *     ->write('Chunk 1')
     *     ->write('Chunk 2')
     *     ->endStream();
     */
    public function write(string $data, bool $flush = true): self;

    /**
     * Write JSON data to an active stream
     *
     * @param mixed $data Data to encode and write
     * @param bool $flush Flush output buffer (default: true)
     * @return static
     *
     * @example
     * $res->startStream('application/json')
     *     ->writeJson(['chunk' => 1])
     *     ->writeJson(['chunk' => 2]);
     */
    public function writeJson(mixed $data, bool $flush = true): self;

    /**
     * Stream a file to the client
     *
     * @param string $filePath Path to file
     * @param array $headers Additional headers
     * @return static
     *
     * @example
     * $res->streamFile('/path/to/video.mp4', [
     *     'Content-Disposition' => 'attachment; filename="video.mp4"'
     * ]);
     */
    public function streamFile(string $filePath, array $headers = []): self;

    /**
     * Stream a resource to the client
     *
     * @param mixed $resource Stream resource
     * @param string|null $contentType Content type
     * @return static
     *
     * @example
     * $handle = fopen('large-file.csv', 'r');
     * $res->streamResource($handle, 'text/csv');
     */
    public function streamResource(mixed $resource, ?string $contentType = null): self;

    /**
     * Send a Server-Sent Event
     *
     * @param mixed $data Event data
     * @param string|null $event Event name
     * @param string|null $id Event ID
     * @param int|null $retry Retry interval in milliseconds
     * @return static
     *
     * @example
     * $res->startStream('text/event-stream')
     *     ->sendEvent(['time' => time()], 'update', '123', 3000);
     */
    public function sendEvent(
        mixed $data,
        ?string $event = null,
        ?string $id = null,
        ?int $retry = null
    ): self;

    /**
     * Send a heartbeat event (keeps connection alive)
     *
     * @return static
     *
     * @example
     * // In a loop
     * while (true) {
     *     sleep(30);
     *     $res->sendHeartbeat();
     * }
     */
    public function sendHeartbeat(): self;

    /**
     * End a streaming response
     *
     * @return static
     *
     * @example
     * $res->startStream()
     *     ->write('Data')
     *     ->endStream();
     */
    public function endStream(): self;

    /**
     * Check if response is currently streaming
     *
     * @return bool True if streaming, false otherwise
     */
    public function isStreaming(): bool;

    // ========================================================================
    // STATUS AND CONTROL METHODS
    // ========================================================================

    /**
     * Get the current HTTP status code
     *
     * @return int Status code
     *
     * @example
     * $code = $res->getStatusCode(); // Returns 200
     */
    public function getStatusCode(): int;

    /**
     * Enable or disable test mode (prevents actual output)
     *
     * @param bool $testMode True to enable test mode
     * @return static
     */
    public function setTestMode(bool $testMode): self;

    /**
     * Check if test mode is enabled
     *
     * @return bool True if in test mode
     */
    public function isTestMode(): bool;

    /**
     * Disable automatic emission of response
     *
     * @param bool $disable True to disable auto-emit
     * @return static
     */
    public function disableAutoEmit(bool $disable = true): self;

    /**
     * Check if response has been sent
     *
     * @return bool True if response was sent
     */
    public function isSent(): bool;

    /**
     * Manually emit the response
     *
     * @param bool $includeHeaders Include headers in emission (default: true)
     * @return void
     */
    public function emit(bool $includeHeaders = true): void;

    /**
     * Reset the sent state (for testing purposes)
     *
     * @return static
     */
    public function resetSentState(): self;
}
