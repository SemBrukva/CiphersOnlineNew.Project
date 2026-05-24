<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cache\CacheInterface;
use App\Database\Database;
use App\Http\Request;
use App\Http\Response;
use Throwable;

/**
 * Контроллер health-check эндпоинта для проверки готовности приложения.
 */
final readonly class HealthController
{
    /**
     * Создаёт экземпляр health-check контроллера.
     */
    public function __construct(
        private Database $database,
        private CacheInterface $cache,
        private string $storagePath = __DIR__ . '/../../storage'
    ) {
    }

    /**
     * Возвращает состояние приложения и статусы ключевых зависимостей.
     *
     * GET /healthz
     */
    public function status(Request $request): Response
    {
        unset($request);

        $checks = [
            'db' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $status = $this->resolveStatus($checks);
        $httpCode = $status === 'fail' ? 503 : 200;

        return Response::json([
            'status' => $status,
            'checks' => $checks,
        ], $httpCode);
    }

    /**
     * Проверяет доступность базы данных.
     */
    private function checkDatabase(): string
    {
        try {
            $result = $this->database->fetch('SELECT 1 AS probe');
            return is_array($result) ? 'ok' : 'fail';
        } catch (Throwable) {
            return 'fail';
        }
    }

    /**
     * Проверяет базовую работоспособность кеша (set/get/delete).
     */
    private function checkCache(): string
    {
        $key = 'healthz:' . bin2hex(random_bytes(8));

        try {
            $this->cache->set($key, 'ok', 5);
            $value = $this->cache->get($key);
            $this->cache->delete($key);

            return $value === 'ok' ? 'ok' : 'degraded';
        } catch (Throwable) {
            return 'degraded';
        }
    }

    /**
     * Проверяет доступность директории storage для записи.
     */
    private function checkStorage(): string
    {
        return is_dir($this->storagePath) && is_writable($this->storagePath) ? 'ok' : 'fail';
    }

    /**
     * Рассчитывает общий статус приложения на основе результатов проверок.
     *
     * @param array<string, string> $checks
     */
    private function resolveStatus(array $checks): string
    {
        if ($checks['db'] !== 'ok' || $checks['storage'] !== 'ok') {
            return 'fail';
        }

        if ($checks['cache'] !== 'ok') {
            return 'degraded';
        }

        return 'ok';
    }
}
