<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Implementation of PSR-7 ResponseInterface
 */
class Response extends Message implements ResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase;

    /** @var array<int, string> HTTP status codes and reason phrases */
    private const STATUS_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot",
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * __construct method
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        ?StreamInterface $body = null,
        string $version = '1.1',
        ?string $reasonPhrase = null
    ) {
        if ($body === null) {
            $resource = fopen('php://temp', 'r+');
            if ($resource === false) {
                throw new \RuntimeException('Unable to create temporary stream');
            }
            $body = new \PivotPHP\Core\Http\Psr7\Stream($resource);
        }

        parent::__construct($body, $headers, $version);

        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase ?? self::STATUS_PHRASES[$statusCode] ?? '';
    }

    /**
     * Gets the response status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        if ($code < 100 || $code > 599) {
            throw new \InvalidArgumentException('Status code must be an integer between 100 and 599');
        }

        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase ?: (self::STATUS_PHRASES[$code] ?? '');

        return $clone;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Reset response for pooling reuse
     *
     * @internal Used by ResponsePool
     */
    public function reset(int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->reasonPhrase = self::STATUS_PHRASES[$statusCode] ?? '';
        $this->headers = [];
        $this->headerNames = [];

        // Reset body to empty stream
        if (isset($this->body)) {
            $this->body = Stream::createFromString('');
        }

        return $this;
    }

    /**
     * Clear all headers for pooling
     *
     * @internal Used by ResponsePool
     */
    public function clearHeaders(): self
    {
        $this->headers = [];
        $this->headerNames = [];
        return $this;
    }

    /**
     * Check if this response can be safely pooled
     */
    public function canBePooled(): bool
    {
        // Don't pool responses with large bodies or special headers
        $body = $this->getBody();
        $bodySize = $body->getSize();

        // Don't pool large responses
        if ($bodySize !== null && $bodySize > 8192) {
            return false;
        }

        // Don't pool responses with streaming or special headers
        $contentType = $this->getHeaderLine('content-type');
        if (
            strpos($contentType, 'text/event-stream') !== false ||
            strpos($contentType, 'multipart/') !== false
        ) {
            return false;
        }

        return true;
    }

    /**
     * Compatibilidade Express: permite uso de status() como atalho para withStatus()
     *
     * @param int $code
     * @return $this
     */
    public function status(int $code)
    {
        $new = $this->withStatus($code);
        // Copia propriedades para manter compatibilidade com Express
        foreach (get_object_vars($new) as $k => $v) {
            $this->$k = $v;
        }
        return $this;
    }

    /**
     * Retorna uma nova resposta com corpo JSON e header apropriado
     *
     * @param array $data
     * @return $this
     */
    public function json(array $data)
    {
        $new = $this->withHeader('Content-Type', 'application/json');
        $body = json_encode($data);
        if ($body === false) {
            $body = '';
        }
        $stream = \PivotPHP\Core\Http\Psr7\Stream::createFromString($body);
        $new = $new->withBody($stream);
        // Copia propriedades para manter compatibilidade
        foreach (get_object_vars($new) as $k => $v) {
            $this->$k = $v;
        }
        return $this;
    }
}
