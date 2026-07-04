<?php

declare(strict_types=1);

namespace App\Analytics;

use App\Cache\CacheInterface;
use App\Repository\AnalyticsRepository;

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
        private AnalyticsRepository $repo,
    ) {
    }

    /**
     * Записывает факт использования инструмента с cooldown-дедупликацией.
     *
     * Если в кеше есть запись о недавнем использовании данного инструмента
     * этим пользователем/IP — событие не дублируется.
     */
    public function recordUse(string $toolSlug, ?int $userId, string $ipHash, string $mode, string $source = 'local'): void
    {
        if (!config('analytics.enabled', true)) {
            return;
        }

        $source = in_array($source, ['local', 'embed'], true) ? $source : 'local';
        $cacheKey = $this->cooldownKey($userId, $ipHash, $toolSlug, $source);

        if ($this->cache->has($cacheKey)) {
            return;
        }

        $this->repo->record(
            mb_substr($toolSlug, 0, 100),
            in_array($mode, ['encode', 'decode'], true) ? $mode : 'encode',
            $userId,
            $ipHash,
            $source,
        );

        $ttl = (int) config('analytics.cooldown_seconds', 300);
        $this->cache->set($cacheKey, 1, $ttl);
    }

    /**
     * Формирует ключ cooldown для тройки (идентификатор пользователя, инструмент, источник).
     *
     * Источник включён в ключ, чтобы использование в embed и на основном сайте
     * дедуплицировалось независимо.
     */
    private function cooldownKey(?int $userId, string $ipHash, string $toolSlug, string $source): string
    {
        $identity = $userId !== null ? "u:{$userId}" : "ip:{$ipHash}";

        return "analytics:cd:{$source}:{$identity}:{$toolSlug}";
    }
}
