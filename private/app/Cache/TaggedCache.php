<?php

declare(strict_types=1);

namespace App\Cache;

/**
 * Тегированная обёртка над CacheInterface.
 *
 * Хранит список ключей тега в кеше под служебным ключом `_tag:<name>`.
 * Операция flush() удаляет все зарегистрированные ключи и сам индекс.
 *
 * Ограничение: обновление индекса не атомарно — при одновременных записях
 * возможна неполная инвалидация (принято для Memcached; Redis устраняет через SET).
 */
final class TaggedCache implements TaggedCacheInterface
{
    /** @var string Ключ индекса тега в базовом кеше. */
    private readonly string $indexKey;

    /**
     * @param CacheInterface $cache Базовая реализация кеша.
     * @param string         $tag   Имя тега.
     */
    public function __construct(
        private readonly CacheInterface $cache,
        string $tag,
    ) {
        $this->indexKey = '_tag:' . $tag;
    }

    /**
     * Возвращает значение из кеша или $default, если ключ отсутствует.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    /**
     * Сохраняет значение в кеш и регистрирует ключ в индексе тега.
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->cache->set($key, $value, $ttl);
        $this->addToIndex($key);
    }

    /**
     * Проверяет наличие ключа в кеше.
     */
    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    /**
     * Удаляет ключ из кеша и из индекса тега.
     */
    public function delete(string $key): void
    {
        $this->cache->delete($key);
        $this->removeFromIndex($key);
    }

    /**
     * Возвращает значение из кеша; если ключ отсутствует — вычисляет, сохраняет и возвращает.
     * Ключ регистрируется в индексе тега при сохранении.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Удаляет все ключи, зарегистрированные под этим тегом, и очищает индекс.
     */
    public function flush(): void
    {
        foreach ($this->getIndexKeys() as $key) {
            $this->cache->delete($key);
        }

        $this->cache->delete($this->indexKey);
    }

    /**
     * Добавляет ключ в индекс тега, если его там ещё нет.
     */
    private function addToIndex(string $key): void
    {
        $keys = $this->getIndexKeys();

        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
        }

        $this->cache->set($this->indexKey, $keys, 0);
    }

    /**
     * Удаляет ключ из индекса тега.
     */
    private function removeFromIndex(string $key): void
    {
        $keys = array_values(array_filter(
            $this->getIndexKeys(),
            static fn (string $k): bool => $k !== $key,
        ));

        $this->cache->set($this->indexKey, $keys, 0);
    }

    /**
     * Возвращает список ключей из индекса тега.
     *
     * @return string[]
     */
    private function getIndexKeys(): array
    {
        $keys = $this->cache->get($this->indexKey, []);

        return is_array($keys) ? $keys : [];
    }
}
