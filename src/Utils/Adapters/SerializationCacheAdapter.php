<?php

declare(strict_types=1);

namespace PivotPHP\Core\Utils\Adapters;

use PivotPHP\Core\Contracts\SerializationCacheInterface;
use PivotPHP\Core\Utils\SerializationCache;

/**
 * Adapter para SerializationCache com fallback
 *
 * Implementa SerializationCacheInterface delegando para SerializationCache
 * quando habilitado, ou usando fallback simples quando desabilitado.
 */
class SerializationCacheAdapter implements SerializationCacheInterface
{
    /**
     * @var bool Se o cache de serialização está habilitado
     */
    private bool $enabled;

    /**
     * Construtor
     *
     * @param bool $enabled Se deve usar cache de serialização
     */
    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): void
    {
        if ($this->enabled) {
            SerializationCache::set($key, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): mixed
    {
        if ($this->enabled) {
            return SerializationCache::get($key);
        }
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        if ($this->enabled) {
            return SerializationCache::has($key);
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        if ($this->enabled) {
            SerializationCache::remove($key);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSerializedSize(mixed $data, ?string $cacheKey = null): int
    {
        if ($this->enabled) {
            return SerializationCache::getSerializedSize($data, $cacheKey);
        }
        // Fallback: calcular tamanho sem cache
        return strlen(serialize($data));
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalSerializedSize(array $objects, array $cacheKeys = []): int
    {
        if ($this->enabled) {
            return SerializationCache::getTotalSerializedSize($objects, $cacheKeys);
        }
        // Fallback: somar tamanhos sem cache
        $total = 0;
        foreach ($objects as $obj) {
            $total += strlen(serialize($obj));
        }
        return $total;
    }

    /**
     * {@inheritDoc}
     */
    public function getSerializedData(mixed $data): string
    {
        if ($this->enabled) {
            $result = SerializationCache::getSerializedData($data);
            return is_string($result) ? $result : serialize($data);
        }
        // Fallback: serializar diretamente
        return serialize($data);
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(): void
    {
        if ($this->enabled) {
            SerializationCache::clearCache();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        if ($this->enabled) {
            return SerializationCache::getStats();
        }
        return ['enabled' => false];
    }

    /**
     * Habilitar/desabilitar cache
     *
     * @param bool $enabled
     * @return void
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Verificar se cache está habilitado
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
