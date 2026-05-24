<?php

declare(strict_types=1);

namespace Tests\Unit\Controller;

use App\Cache\CacheInterface;
use App\Controller\HealthController;
use App\Database\Database;
use App\Http\Request;
use App\Http\RequestContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Проверяет работу health-check контроллера.
 */
final class HealthControllerTest extends TestCase
{
    /**
     * Проверяет статус ok при доступных db/cache/storage.
     */
    public function testStatusReturnsOkWhenAllChecksPass(): void
    {
        $controller = new HealthController(
            $this->makeSqliteDatabase(':memory:'),
            new InMemoryHealthCache(),
            sys_get_temp_dir()
        );

        $response = $controller->status(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/healthz'], [], [], [], []));
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $payload['status']);
        self::assertSame('ok', $payload['checks']['db']);
        self::assertSame('ok', $payload['checks']['cache']);
        self::assertSame('ok', $payload['checks']['storage']);
    }

    /**
     * Проверяет статус degraded при недоступном кеше.
     */
    public function testStatusReturnsDegradedWhenCacheFails(): void
    {
        $controller = new HealthController(
            $this->makeSqliteDatabase(':memory:'),
            new FailingHealthCache(),
            sys_get_temp_dir()
        );

        $response = $controller->status(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/healthz'], [], [], [], []));
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('degraded', $payload['status']);
        self::assertSame('degraded', $payload['checks']['cache']);
    }

    /**
     * Проверяет статус fail при недоступной базе данных.
     */
    public function testStatusReturnsFailWhenDatabaseFails(): void
    {
        $controller = new HealthController(
            $this->makeSqliteDatabase('/definitely-not-existing/health-check.sqlite'),
            new InMemoryHealthCache(),
            sys_get_temp_dir()
        );

        $response = $controller->status(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/healthz'], [], [], [], []));
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('fail', $payload['status']);
        self::assertSame('fail', $payload['checks']['db']);
    }

    /**
     * Создаёт SQLite-обёртку для тестов.
     */
    private function makeSqliteDatabase(string $databasePath): Database
    {
        return new Database([
            'driver' => 'sqlite',
            'database' => $databasePath,
            'options' => [],
        ], new RequestContext('health-test', microtime(true), false));
    }
}

/**
 * Простая in-memory реализация кеша для тестов health-check.
 */
final class InMemoryHealthCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->items[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function delete(string $key): void
    {
        unset($this->items[$key]);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        if (!$this->has($key)) {
            $this->set($key, $callback(), $ttl);
        }

        return $this->get($key);
    }

    public function flush(): void
    {
        $this->items = [];
    }

    public function getStats(): array
    {
        return ['hits' => 0, 'misses' => 0];
    }

    public function tag(string $tag): \App\Cache\TaggedCacheInterface
    {
        return new \App\Cache\TaggedCache($this, $tag);
    }
}

/**
 * Реализация кеша, всегда выбрасывающая исключение.
 */
final class FailingHealthCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        throw new RuntimeException('Cache is down');
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        throw new RuntimeException('Cache is down');
    }

    public function has(string $key): bool
    {
        throw new RuntimeException('Cache is down');
    }

    public function delete(string $key): void
    {
        throw new RuntimeException('Cache is down');
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        throw new RuntimeException('Cache is down');
    }

    public function flush(): void
    {
        throw new RuntimeException('Cache is down');
    }

    public function getStats(): array
    {
        return ['hits' => 0, 'misses' => 0];
    }

    public function tag(string $tag): \App\Cache\TaggedCacheInterface
    {
        throw new RuntimeException('Cache is down');
    }
}
