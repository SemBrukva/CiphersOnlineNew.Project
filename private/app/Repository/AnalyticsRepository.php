<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий аналитики: запись событий и агрегированные выборки.
 */
final class AnalyticsRepository extends AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::TOOL_USAGE_EVENTS);
    }

    /**
     * Записывает событие использования инструмента.
     */
    public function record(string $toolSlug, string $mode, ?int $userId, string $ipHash): void
    {
        $this->insert([
            'tool_slug'  => $toolSlug,
            'mode'       => $mode,
            'user_id'    => $userId,
            'ip_hash'    => $ipHash,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Возвращает топ инструментов по числу использований за последние N дней.
     *
     * @return array<int, array{tool_slug: string, total: int, encodes: int, decodes: int}>
     */
    public function topTools(int $limit = 10, int $days = 30): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $this->db->fetchAll(
            'SELECT
                tool_slug,
                COUNT(*) AS total,
                SUM(CASE WHEN mode = \'encode\' THEN 1 ELSE 0 END) AS encodes,
                SUM(CASE WHEN mode = \'decode\' THEN 1 ELSE 0 END) AS decodes
             FROM ' . Tables::TOOL_USAGE_EVENTS . '
             WHERE created_at >= ?
             GROUP BY tool_slug
             ORDER BY total DESC
             LIMIT ?',
            [$since, $limit]
        );
    }

    /**
     * Возвращает общее число событий использования за последние N дней.
     */
    public function totalCount(int $days = 30): int
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS cnt FROM ' . Tables::TOOL_USAGE_EVENTS . ' WHERE created_at >= ?',
            [$since]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Возвращает число уникальных инструментов, использованных за последние N дней.
     */
    public function uniqueToolsCount(int $days = 30): int
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $row = $this->db->fetch(
            'SELECT COUNT(DISTINCT tool_slug) AS cnt FROM ' . Tables::TOOL_USAGE_EVENTS . ' WHERE created_at >= ?',
            [$since]
        );

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Возвращает дневную статистику использования за последние N дней.
     *
     * Результат — ассоциативный массив `['YYYY-MM-DD' => count]`, без пропусков:
     * дни без событий включены с нулевым значением.
     *
     * @return array<string, int>
     */
    public function dailyUsage(int $days = 30): array
    {
        $since = date('Y-m-d', strtotime("-{$days} days"));

        $rows = $this->db->fetchAll(
            'SELECT DATE(created_at) AS day, COUNT(*) AS cnt
             FROM ' . Tables::TOOL_USAGE_EVENTS . '
             WHERE created_at >= ?
             GROUP BY DATE(created_at)
             ORDER BY day ASC',
            [$since . ' 00:00:00']
        );

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[(string) $row['day']] = (int) $row['cnt'];
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $result[$day] = $byDay[$day] ?? 0;
        }

        return $result;
    }
}
