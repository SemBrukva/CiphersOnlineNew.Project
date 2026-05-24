<?php

declare(strict_types=1);

namespace App\Http\Session;

use Memcached;
use RuntimeException;
use SessionHandlerInterface;

/**
 * Обработчик хранения сессий в Memcached.
 *
 * Реализует нативный PHP-интерфейс SessionHandlerInterface.
 * Соединение устанавливается лениво при первом обращении.
 * GC не требуется — Memcached сам вытесняет истёкшие ключи по TTL.
 */
final class MemcachedSessionHandler implements SessionHandlerInterface
{
    /** @var Memcached|null Экземпляр соединения; null до первого обращения. */
    private ?Memcached $client = null;

    /** @var int TTL хранения данных сессии в секундах. */
    private int $ttl;

    /** @var string Префикс ключей сессий в Memcached. */
    private string $prefix;

    /**
     * @param array<string, mixed> $config Параметры: host, port, prefix, ttl (секунды).
     */
    public function __construct(private readonly array $config)
    {
        $this->ttl    = (int) ($config['ttl']    ?? 86400);
        $this->prefix = (string) ($config['prefix'] ?? 'sess_');
    }

    /**
     * Открывает хранилище сессий (в Memcached — no-op).
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Закрывает хранилище сессий (в Memcached — no-op).
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

        if ($this->client()->getResultCode() === Memcached::RES_NOTFOUND) {
            return '';
        }

        return is_string($value) ? $value : '';
    }

    /**
     * Сохраняет данные сессии с заданным TTL.
     */
    public function write(string $id, string $data): bool
    {
        return $this->client()->set($this->prefix . $id, $data, $this->ttl);
    }

    /**
     * Удаляет данные сессии из Memcached.
     */
    public function destroy(string $id): bool
    {
        $result = $this->client()->delete($this->prefix . $id);

        return $result || $this->client()->getResultCode() === Memcached::RES_NOTFOUND;
    }

    /**
     * Сборка мусора — Memcached удаляет ключи по TTL самостоятельно.
     */
    public function gc(int $max_lifetime): int
    {
        return 0;
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
            (string) ($this->config['host'] ?? '127.0.0.1'),
            (int)    ($this->config['port'] ?? 11211),
        );

        return $this->client;
    }
}
