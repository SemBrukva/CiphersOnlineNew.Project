<?php

declare(strict_types=1);

namespace App\Http\Session;

use Redis;
use RuntimeException;
use SessionHandlerInterface;

/**
 * Обработчик хранения сессий в Redis.
 *
 * Реализует нативный PHP-интерфейс SessionHandlerInterface.
 * Соединение устанавливается лениво при первом обращении.
 * GC не требуется — Redis сам вытесняет истёкшие ключи по TTL (SETEX).
 */
final class RedisSessionHandler implements SessionHandlerInterface
{
    /** @var Redis|null Экземпляр соединения; null до первого обращения. */
    private ?Redis $client = null;

    /** @var int TTL хранения данных сессии в секундах. */
    private int $ttl;

    /** @var string Префикс ключей сессий в Redis. */
    private string $prefix;

    /**
     * @param array<string, mixed> $config Параметры: host, port, password, database, prefix, ttl (секунды).
     */
    public function __construct(private readonly array $config)
    {
        $this->ttl    = (int) ($config['ttl']    ?? 86400);
        $this->prefix = (string) ($config['prefix'] ?? 'sess_');
    }

    /**
     * Открывает хранилище сессий (в Redis — no-op, соединение ленивое).
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Закрывает хранилище сессий (в Redis — no-op).
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Читает данные сессии по идентификатору.
     * Возвращает пустую строку, если ключ не найден.
     */
    public function read(string $id): string
    {
        $value = $this->client()->get($this->prefix . $id);

        return is_string($value) ? $value : '';
    }

    /**
     * Сохраняет данные сессии с заданным TTL (SETEX).
     */
    public function write(string $id, string $data): bool
    {
        return (bool) $this->client()->setex($this->prefix . $id, $this->ttl, $data);
    }

    /**
     * Удаляет данные сессии из Redis.
     */
    public function destroy(string $id): bool
    {
        $this->client()->del($this->prefix . $id);

        return true;
    }

    /**
     * Сборка мусора — Redis удаляет ключи по TTL самостоятельно.
     */
    public function gc(int $max_lifetime): int
    {
        return 0;
    }

    /**
     * Возвращает экземпляр Redis, создавая соединение при первом вызове.
     *
     * @throws RuntimeException Если расширение Redis не установлено.
     */
    private function client(): Redis
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (!class_exists(Redis::class)) {
            throw new RuntimeException('Расширение PHP Redis не установлено.');
        }

        $this->client = new Redis();
        $this->client->connect(
            (string) ($this->config['host'] ?? '127.0.0.1'),
            (int)    ($this->config['port'] ?? 6379),
        );

        if (!empty($this->config['password'])) {
            $this->client->auth((string) $this->config['password']);
        }

        if (isset($this->config['database'])) {
            $this->client->select((int) $this->config['database']);
        }

        return $this->client;
    }
}
