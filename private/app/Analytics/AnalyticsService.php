<?php

declare(strict_types=1);

namespace App\Analytics;

use App\Cache\CacheInterface;
use App\Database\Database;
use App\Database\Tables;

/**
 * Сервис аналитики использования инструментов.
 *
 * Записывает событие использования инструмента с cooldown-дедупликацией через кеш:
 * одно событие на пользователя/IP per инструмент в течение заданного окна времени.
 */
final readonly class AnalyticsService
{
    /**
     * Создаёт экземпляр сервиса аналитики.
     */
    public function __construct(
        private CacheInterface $cache,
        private Database $db,
    ) {
    }

    /**
     * Записывает факт использования инструмента с cooldown-дедупликацией.
     *
     * Если в кеше есть запись о недавнем использовании данного инструмента
     * этим пользователем/IP — событие не дублируется.
     */
    public function recordUse(string $toolSlug, ?int $userId, string $ipHash, string $mode): void
    {
        if (!config('analytics.enabled', true)) {
            return;
        }

        $cacheKey = $this->cooldownKey($userId, $ipHash, $toolSlug);

        if ($this->cache->has($cacheKey)) {
            return;
        }

        $this->db->insert(Tables::TOOL_USAGE_EVENTS, [
            'tool_slug'  => mb_substr($toolSlug, 0, 100),
            'mode'       => in_array($mode, ['encode', 'decode'], true) ? $mode : 'encode',
            'user_id'    => $userId,
            'ip_hash'    => $ipHash,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $ttl = (int) config('analytics.cooldown_seconds', 300);
        $this->cache->set($cacheKey, 1, $ttl);
    }

    /**
     * Формирует ключ cooldown для пары (идентификатор пользователя, инструмент).
     */
    private function cooldownKey(?int $userId, string $ipHash, string $toolSlug): string
    {
        $identity = $userId !== null ? "u:{$userId}" : "ip:{$ipHash}";

        return "analytics:cd:{$identity}:{$toolSlug}";
    }
}
