<?php

declare(strict_types=1);

namespace App\Cache;

/**
 * Контракт для реализаций кеша.
 */
interface CacheInterface
{
    /**
     * Возвращает значение из кеша или $default, если ключ отсутствует.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Сохраняет значение в кеш на указанное число секунд.
     *
     * @param int $ttl Время жизни в секундах (0 — бессрочно).
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void;

    /**
     * Проверяет наличие ключа в кеше.
     */
    public function has(string $key): bool;

    /**
     * Удаляет ключ из кеша.
     */
    public function delete(string $key): void;

    /**
     * Возвращает значение из кеша; если ключ отсутствует — вычисляет через $callback, сохраняет и возвращает.
     *
     * @param int      $ttl      Время жизни в секундах.
     * @param callable $callback Фабрика значения при отсутствии в кеше.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Очищает весь кеш.
     */
    public function flush(): void;

    /**
     * Возвращает статистику обращений к кешу за время жизни объекта.
     *
     * @return array{hits: int, misses: int}
     */
    public function getStats(): array;

    /**
     * Возвращает тегированный кеш для группового сброса ключей по тегу.
     */
    public function tag(string $tag): TaggedCacheInterface;
}
