<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Uploaded file implementation (PSR-7)
 *
 * This class implements the UploadedFileInterface for handling uploaded files
 * following the PSR-7 HTTP Message Interface standard.
 *
 * @package PivotPHP\Core\Http\Psr7
 * @since 2.1.0
 */
class UploadedFile implements UploadedFileInterface
{
    private ?string $file = null;
    private ?StreamInterface $stream = null;
    private ?string $clientFilename;
    private ?string $clientMediaType;
    private int $error;
    private int $size;
    private bool $moved = false;

    /**
     * Constructor
     */
    public function __construct(
        StreamInterface|string $streamOrFile,
        int $size,
        int $error,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ) {
        $this->size = $size;
        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if ($error === UPLOAD_ERR_OK) {
            if (is_string($streamOrFile)) {
                $this->file = $streamOrFile;
            } elseif ($streamOrFile instanceof StreamInterface) {
                $this->stream = $streamOrFile;
            } else {
                throw new \InvalidArgumentException(
                    'Invalid stream or file provided for UploadedFile'
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(): StreamInterface
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot retrieve stream due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream after it has been moved');
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        if ($this->file === null) {
            throw new \RuntimeException('No stream or file available');
        }

        $resource = fopen($this->file, 'r');
        if ($resource === false) {
            throw new \RuntimeException("Unable to open file: {$this->file}");
        }

        return new Stream($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo(string $targetPath): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Cannot move file due to upload error');
        }

        if ($this->moved) {
            throw new \RuntimeException('File has already been moved');
        }

        if (empty($targetPath)) {
            throw new \InvalidArgumentException('Target path cannot be empty');
        }

        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir) || !is_writable($targetDir)) {
            throw new \RuntimeException(
                "Target directory does not exist or is not writable: {$targetDir}"
            );
        }

        $sapi = PHP_SAPI;
        switch (true) {
            case (str_starts_with($sapi, 'cli') || str_starts_with($sapi, 'phpdbg')):
                // CLI environment
                $this->writeFile($targetPath);
                break;
            default:
                // Web environment
                if ($this->file) {
                    $this->moved = is_uploaded_file($this->file) && move_uploaded_file($this->file, $targetPath);
                } else {
                    $this->writeFile($targetPath);
                }
        }

        if (!$this->moved) {
            throw new \RuntimeException("Error occurred while moving uploaded file to {$targetPath}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Write file to target path
     *
     * @throws \RuntimeException
     */
    private function writeFile(string $targetPath): void
    {
        $handle = fopen($targetPath, 'wb+');
        if ($handle === false) {
            throw new \RuntimeException("Unable to write to target path: {$targetPath}");
        }

        $stream = $this->getStream();
        $stream->rewind();

        while (!$stream->eof()) {
            $data = $stream->read(4096);
            if (fwrite($handle, $data) === false) {
                fclose($handle);
                throw new \RuntimeException("Error writing to target file: {$targetPath}");
            }
        }

        fclose($handle);
        $this->moved = true;
    }
}
