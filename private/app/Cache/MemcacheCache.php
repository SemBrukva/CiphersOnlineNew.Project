<?php

declare(strict_types=1);

namespace App\Cache;

use Memcached;
use RuntimeException;

/**
 * Реализация кеша через расширение Memcached.
 * Соединение устанавливается лениво при первом обращении.
 */
final class MemcacheCache implements CacheInterface
{
    /** @var Memcached|null Экземпляр соединения; null до первого обращения. */
    private ?Memcached $client = null;

    /** @var int Число успешных попаданий в кеш (get/remember вернул сохранённое значение). */
    private int $hits = 0;

    /** @var int Число промахов кеша (ключ не найден). */
    private int $misses = 0;

    /**
     * @param array<string, mixed> $config Конфигурация: host, port, ttl.
     * @param string               $prefix Префикс всех ключей.
     */
    public function __construct(
        private readonly array  $config,
        private readonly string $prefix = '',
    ) {
    }

    /**
     * Возвращает значение из кеша или $default, если ключ отсутствует.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->client()->get($this->key($key));

        if ($this->client()->getResultCode() === Memcached::RES_NOTFOUND) {
            $this->misses++;
            return $default;
        }

        $this->hits++;
        return $value;
    }

    /**
     * Сохраняет значение в кеш на $ttl секунд (0 — бессрочно).
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->client()->set($this->key($key), $value, $ttl);
    }

    /**
     * Проверяет наличие ключа в кеше.
     */
    public function has(string $key): bool
    {
        $this->client()->get($this->key($key));

        return $this->client()->getResultCode() !== Memcached::RES_NOTFOUND;
    }

    /**
     * Удаляет ключ из кеша.
     */
    public function delete(string $key): void
    {
        $this->client()->delete($this->key($key));
    }

    /**
     * Возвращает значение из кеша; если ключ отсутствует — вычисляет через $callback, сохраняет и возвращает.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $rawValue = $this->client()->get($this->key($key));
        $found    = $this->client()->getResultCode() !== Memcached::RES_NOTFOUND;

        if ($found) {
            $this->hits++;
            return $rawValue;
        }

        $this->misses++;
        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Полностью очищает кеш (все ключи с любыми префиксами).
     */
    public function flush(): void
    {
        $this->client()->flush();
    }

    /**
     * Возвращает статистику попаданий и промахов за текущий запрос.
     *
     * @return array{hits: int, misses: int}
     */
    public function getStats(): array
    {
        return ['hits' => $this->hits, 'misses' => $this->misses];
    }

    /**
     * Возвращает тегированный кеш для группового сброса ключей по тегу.
     */
    public function tag(string $tag): TaggedCacheInterface
    {
        return new TaggedCache($this, $tag);
    }

    /**
     * Возвращает статистику потребления памяти Memcached.
     *
     * @return array{used_mb: float|null, limit_mb: float|null}
     */
    public function getMemoryStats(): array
    {
        $stats = $this->client()->getStats();
        $first = reset($stats);

        if (!is_array($first)) {
            return ['used_mb' => null, 'limit_mb' => null];
        }

        $usedBytes = isset($first['bytes']) ? (float) $first['bytes'] : null;
        $limitBytes = isset($first['limit_maxbytes']) ? (float) $first['limit_maxbytes'] : null;

        return [
            'used_mb' => $usedBytes !== null ? round($usedBytes / 1024 / 1024, 3) : null,
            'limit_mb' => $limitBytes !== null ? round($limitBytes / 1024 / 1024, 3) : null,
        ];
    }

    /**
     * Возвращает ключ с добавленным префиксом.
     */
    private function key(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Возвращает экземпляр Memcached, создавая соединение при первом вызове.
     *
     * @throws RuntimeException Если расширение Memcached не установлено.
     */
    private function client(): Memcached
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (!class_exists(Memcached::class)) {
            throw new RuntimeException('Расширение PHP Memcached не установлено.');
        }

        $this->client = new Memcached();
        $this->client->addServer(
            $this->config['host'] ?? '127.0.0.1',
            (int) ($this->config['port'] ?? 11211),
        );

        return $this->client;
    }
}
