<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7;

use Psr\Http\Message\StreamInterface;

/**
 * HTTP message body stream implementation (PSR-7)
 *
 * This class implements the StreamInterface for handling HTTP message bodies
 * following the PSR-7 HTTP Message Interface standard.
 *
 * @package PivotPHP\Core\Http\Psr7
 * @since 2.1.0
 */
class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $stream;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $meta = null;

    /**
     * @var bool
     */
    private bool $readable = false;

    /**
     * @var bool
     */
    private bool $writable = false;

    /**
     * @var bool
     */
    private bool $seekable = false;

    /**
     * @var int|null
     */
    private ?int $size = null;

    /**
     * @var bool
     */
    private bool $sizeCalculated = false;

    /**
     * @var bool
     */
    private bool $reusable = true;

    /**
     * Constructor
     *
     * @param resource|string $body
     */
    public function __construct($body = '')
    {
        if (is_string($body)) {
            $resource = fopen('php://temp', 'r+');
            if ($resource === false) {
                throw new \RuntimeException('Unable to create temporary stream');
            }
            fwrite($resource, $body);
            rewind($resource);
            $body = $resource;
        }

        if (!is_resource($body)) {
            throw new \InvalidArgumentException('Stream must be a resource or string');
        }        $this->stream = $body;
        $this->meta = stream_get_meta_data($this->stream);

        $this->readable = $this->isReadableMode($this->meta['mode']);
        $this->writable = $this->isWritableMode($this->meta['mode']);
        $this->seekable = (bool) $this->meta['seekable'];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }
            return $this->getContents();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);

        $this->size = null;
        $this->meta = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        if ($this->sizeCalculated && $this->size !== null) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            $this->sizeCalculated = true;
            return $this->size;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        $result = ftell($this->stream);
        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        return feof($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException(
                'Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $string): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        // Reset size so that it will be re-calculated on next call to getSize()
        $this->size = null;

        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $length): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        if ($length < 0) {
            throw new \InvalidArgumentException('Length parameter cannot be negative');
        }

        if ($length === 0) {
            return '';
        }

        $string = fread($this->stream, $length);
        if ($string === false) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(?string $key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        }

        if (!$this->meta) {
            $this->meta = stream_get_meta_data($this->stream);
        }

        if ($key === null) {
            return $this->meta;
        }

        return $this->meta[$key] ?? null;
    }

    /**
     * Create a stream from a string content
     */
    public static function createFromString(string $content): self
    {
        $resource = fopen('php://memory', 'r+');
        if ($resource === false) {
            throw new \RuntimeException('Unable to create stream');
        }

        if ($content !== '') {
            fwrite($resource, $content);
            fseek($resource, 0);
        }

        return new self($resource);
    }

    /**
     * Create a stream from a file
     */
    public static function createFromFile(string $filename, string $mode = 'r'): self
    {
        if (!file_exists($filename) && !in_array($mode[0], ['w', 'a', 'x', 'c'])) {
            throw new \RuntimeException("File '{$filename}' does not exist");
        }

        $resource = fopen($filename, $mode);
        if ($resource === false) {
            throw new \RuntimeException("Unable to open file '{$filename}'");
        }

        return new self($resource);
    }

    /**
     * Check if mode is readable
     */
    private function isReadableMode(string $mode): bool
    {
        return str_contains($mode, 'r') || str_contains($mode, '+');
    }

    /**
     * Check if mode is writable
     */
    private function isWritableMode(string $mode): bool
    {
        return str_contains($mode, 'w') ||
               str_contains($mode, 'a') ||
               str_contains($mode, 'x') ||
               str_contains($mode, 'c') ||
               str_contains($mode, '+');
    }

    /**
     * Check if stream can be reused in pool
     */
    public function isReusable(): bool
    {
        return $this->reusable && isset($this->stream) && $this->isSeekable();
    }

    /**
     * Mark stream as non-reusable (for special streams)
     */
    public function markNonReusable(): self
    {
        $this->reusable = false;
        return $this;
    }

    /**
     * Truncate stream to given length (for pooling)
     */
    public function truncate(int $length = 0): bool
    {
        if (!isset($this->stream) || !$this->isWritable()) {
            return false;
        }

        $result = ftruncate($this->stream, max(0, $length));
        if ($result) {
            $this->size = null;
            $this->sizeCalculated = false;
        }

        return $result;
    }
}
