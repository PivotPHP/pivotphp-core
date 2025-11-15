<?php

namespace PivotPHP\Core\Cache;

use RuntimeException;

/**
 * Driver de cache em arquivo
 */
class FileCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/express-cache';

        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0755, true) && !is_dir($this->cacheDir)) {
                throw new RuntimeException("Cannot create cache directory: {$this->cacheDir}");
            }
        }
    }

    /**
     * @param  mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $fileContents = @file_get_contents($file);
        if ($fileContents === false) {
            $error = error_get_last();
            error_log("FileCache read failed for {$file}: " . ($error['message'] ?? 'Unknown error'));
            return $default;
        }

        try {
            $data = unserialize($fileContents);
        } catch (\Throwable $e) {
            // Handle corrupted/invalid serialized data (catch all exceptions)
            $this->delete($key);
            return $default;
        }

        // Check if unserialize returned false (corrupted data) or invalid structure
        if (
            $data === false ||
            !is_array($data) ||
            !array_key_exists('expires', $data) ||
            !array_key_exists('value', $data)
        ) {
            $this->delete($key);
            return $default;
        }

        if ($data['expires'] && time() >= $data['expires']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $expires = ($ttl !== null && $ttl !== 0) ? time() + $ttl : null;

        $data = [
            'value' => $value,
            'expires' => $expires
        ];

        $written = @file_put_contents($file, serialize($data), LOCK_EX);
        if ($written === false) {
            error_log("FileCache write failed for {$file}");
            return false;
        }
        return true;
    }

    /**
     * Delete a cache entry by key
     */
    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * Clear all cache entries
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*');

        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        return true;
    }

    /**
     * Check if a cache entry exists
     */
    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return false;
        }

        $fileContents = @file_get_contents($file);
        if ($fileContents === false) {
            $error = error_get_last();
            error_log("FileCache read failed for {$file}: " . ($error['message'] ?? 'Unknown error'));
            return false;
        }

        try {
            $data = unserialize($fileContents);
        } catch (\Throwable $e) {
            error_log("FileCache unserialize failed for {$key}: " . $e->getMessage());
            $this->delete($key);
            return false;
        }

        // Check if unserialize returned false (corrupted data) or invalid structure
        if (
            $data === false ||
            !is_array($data) ||
            !array_key_exists('expires', $data) ||
            !array_key_exists('value', $data)
        ) {
            $this->delete($key);
            return false;
        }

        if ($data['expires'] && time() >= $data['expires']) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    private function getFilePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
