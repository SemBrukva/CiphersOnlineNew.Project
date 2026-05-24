<?php

declare(strict_types=1);

namespace App\Cache;

/**
 * Контракт для тегированного кеша — позволяет инвалидировать группу ключей одной командой.
 */
interface TaggedCacheInterface
{
    /**
     * Возвращает значение из кеша или $default, если ключ отсутствует.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Сохраняет значение в кеш и регистрирует ключ в индексе тега.
     *
     * @param int $ttl Время жизни в секундах (0 — бессрочно).
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void;

    /**
     * Проверяет наличие ключа в кеше.
     */
    public function has(string $key): bool;

    /**
     * Удаляет ключ из кеша и из индекса тега.
     */
    public function delete(string $key): void;

    /**
     * Возвращает значение из кеша; если ключ отсутствует — вычисляет, сохраняет (с регистрацией в теге) и возвращает.
     *
     * @param int      $ttl      Время жизни в секундах.
     * @param callable $callback Фабрика значения при отсутствии в кеше.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Удаляет из кеша все ключи, зарегистрированные под этим тегом.
     */
    public function flush(): void;
}
