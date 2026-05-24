<?php

declare(strict_types=1);

namespace App\Cache;

/**
 * Заглушка кеша — не сохраняет ничего.
 * Используется в локальном окружении или когда кеш явно отключён.
 */
final class NullCache implements CacheInterface
{
    /** @var int Количество обращений к remember(), когда ключ «найден» (всегда 0). */
    private int $hits = 0;

    /** @var int Количество обращений к remember(), когда ключ не найден (всегда = числу вызовов). */
    private int $misses = 0;

    /**
     * Всегда возвращает $default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    /**
     * Ничего не сохраняет.
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
    }

    /**
     * Всегда возвращает false.
     */
    public function has(string $key): bool
    {
        return false;
    }

    /**
     * Ничего не удаляет.
     */
    public function delete(string $key): void
    {
    }

    /**
     * Всегда вычисляет и возвращает результат $callback без кеширования.
     * Каждый вызов считается miss.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $this->misses++;

        return $callback();
    }

    /**
     * Ничего не делает.
     */
    public function flush(): void
    {
    }

    /**
     * Возвращает статистику: hits всегда 0, misses = число вызовов remember().
     *
     * @return array{hits: int, misses: int}
     */
    public function getStats(): array
    {
        return ['hits' => $this->hits, 'misses' => $this->misses];
    }

    /**
     * Возвращает тегированный кеш — для NullCache все операции остаются no-op.
     */
    public function tag(string $tag): TaggedCacheInterface
    {
        return new TaggedCache($this, $tag);
    }
}
