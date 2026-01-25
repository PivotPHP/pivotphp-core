<?php

declare(strict_types=1);

namespace PivotPHP\Core\Contracts;

/**
 * Interface para cache de serialização
 *
 * Abstrai caching de dados serializados com hash optimization.
 */
interface SerializationCacheInterface
{
    /**
     * Armazenar item no cache
     *
     * @param string $key Chave do cache
     * @param mixed $value Valor a armazenar
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Obter item do cache
     *
     * @param string $key Chave do cache
     * @return mixed Valor armazenado
     */
    public function get(string $key): mixed;

    /**
     * Verificar se item existe no cache
     *
     * @param string $key Chave do cache
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Remover item do cache
     *
     * @param string $key Chave do cache
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Obter tamanho serializado de dados
     *
     * @param mixed $data Dados
     * @param string|null $cacheKey Chave opcional para cache
     * @return int Tamanho em bytes
     */
    public function getSerializedSize(mixed $data, ?string $cacheKey = null): int;

    /**
     * Obter tamanho total serializado de múltiplos objetos
     *
     * @param array $objects Objetos
     * @param array $cacheKeys Chaves de cache
     * @return int Tamanho total em bytes
     */
    public function getTotalSerializedSize(array $objects, array $cacheKeys = []): int;

    /**
     * Obter dados serializados
     *
     * @param mixed $data Dados
     * @return string Dados serializados
     */
    public function getSerializedData(mixed $data): string;

    /**
     * Limpar cache
     *
     * @return void
     */
    public function clearCache(): void;

    /**
     * Obter estatísticas
     *
     * @return array
     */
    public function getStats(): array;
}
