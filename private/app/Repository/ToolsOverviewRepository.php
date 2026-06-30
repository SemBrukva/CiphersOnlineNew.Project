<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий агрегированной аналитики по инструментам для дашборда.
 */
final class ToolsOverviewRepository extends AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::CIPHERS);
    }

    /**
     * Возвращает список всех инструментов с агрегированными метриками.
     *
     * @param  string $since30d Дата начала периода 30 дней (Y-m-d H:i:s).
     * @return array<int, array<string, mixed>>
     */
    public function listTools(string $since30d): array
    {
        return $this->db->fetchAll(
            'SELECT
                c.id,
                c.alias,
                c.published,
                c.calculation_mode,
                c.updated_at,
                cat.alias AS category_alias,
                (SELECT COUNT(*) FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = c.id) AS blocks_count,
                (SELECT COUNT(*) FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = c.id) AS faq_count,
                (SELECT COUNT(*) FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = c.id) AS examples_count,
                (SELECT COUNT(*) FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = c.id) AS tags_count,
                COALESCE(
                    (SELECT COUNT(*) FROM ' . Tables::TOOL_USAGE_EVENTS . '
                     WHERE tool_slug = c.alias AND created_at >= ?),
                    0
                ) AS usage_30d,
                COALESCE(
                    (SELECT COUNT(*) FROM ' . Tables::SEMANTIC_CLUSTERS . ' WHERE tool_slug = c.alias),
                    0
                ) AS clusters_count,
                COALESCE(
                    (SELECT COUNT(sq.id)
                     FROM ' . Tables::SEMANTIC_QUERIES . ' sq
                     JOIN ' . Tables::SEMANTIC_CLUSTERS . ' sc ON sc.id = sq.cluster_id
                     WHERE sc.tool_slug = c.alias),
                    0
                ) AS queries_count,
                COALESCE(
                    (SELECT SUM(total_score) FROM ' . Tables::SEMANTIC_CLUSTERS . ' WHERE tool_slug = c.alias),
                    0
                ) AS semantic_score
             FROM ' . Tables::CIPHERS . ' c
             JOIN ' . Tables::CIPHER_CATEGORIES . ' cat ON cat.id = c.category_id
             ORDER BY cat.sort_order ASC, c.sort_order ASC, c.id ASC',
            [$since30d]
        );
    }

    /**
     * Возвращает полноту переводов для каждого инструмента × язык.
     *
     * Возвращает массив вида [cipher_id => [locale => ['score' => 0-4, 'has_name' => bool, ...]]]
     *
     * @return array<int, array<string, array<string, mixed>>>
     */
    public function translationCompleteness(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT
                app_id,
                language,
                CASE WHEN name IS NOT NULL AND name != \'\' THEN 1 ELSE 0 END AS has_name,
                CASE WHEN meta_title IS NOT NULL AND meta_title != \'\' THEN 1 ELSE 0 END AS has_meta_title,
                CASE WHEN meta_description IS NOT NULL AND meta_description != \'\' THEN 1 ELSE 0 END AS has_meta_desc,
                CASE WHEN description IS NOT NULL AND description != \'\' THEN 1 ELSE 0 END AS has_description
             FROM ' . Tables::CIPHERS_TRANSLATIONS
        );

        $result = [];

        foreach ($rows as $row) {
            $id = (int) ($row['app_id'] ?? 0);
            $lang = (string) ($row['language'] ?? '');

            if ($id < 1 || $lang === '') {
                continue;
            }

            $hasName = (bool) ($row['has_name'] ?? false);
            $hasMeta = (bool) ($row['has_meta_title'] ?? false);
            $hasMetaDesc = (bool) ($row['has_meta_desc'] ?? false);
            $hasDesc = (bool) ($row['has_description'] ?? false);

            $result[$id][$lang] = [
                'score'        => (int) $hasName + (int) $hasMeta + (int) $hasMetaDesc + (int) $hasDesc,
                'has_name'     => $hasName,
                'has_meta_title' => $hasMeta,
                'has_meta_desc'  => $hasMetaDesc,
                'has_description' => $hasDesc,
            ];
        }

        return $result;
    }

    /**
     * Возвращает агрегированные метрики позиций из последнего снимка по каждому инструменту.
     *
     * @return array<string, array<string, mixed>> Ключ — tool_slug.
     */
    public function latestRankStats(): array
    {
        $latestDate = $this->db->fetch(
            'SELECT MAX(checked_at) AS max_date FROM ' . Tables::SEMANTIC_RANK_SNAPSHOTS
        );

        if ($latestDate === null || $latestDate['max_date'] === null) {
            return [];
        }

        $rows = $this->db->fetchAll(
            'SELECT
                sc.tool_slug,
                ROUND(AVG(CASE WHEN s.position > 0 THEN s.position ELSE NULL END), 1) AS avg_position,
                SUM(COALESCE(s.impressions, 0)) AS total_impressions,
                SUM(COALESCE(s.clicks, 0)) AS total_clicks,
                MAX(s.checked_at) AS last_checked_at
             FROM ' . Tables::SEMANTIC_RANK_SNAPSHOTS . ' s
             JOIN ' . Tables::SEMANTIC_QUERIES . ' sq ON sq.id = s.query_id
             JOIN ' . Tables::SEMANTIC_CLUSTERS . ' sc ON sc.id = sq.cluster_id
             WHERE s.checked_at = ?
             GROUP BY sc.tool_slug',
            [$latestDate['max_date']]
        );

        $result = [];

        foreach ($rows as $row) {
            $slug = (string) ($row['tool_slug'] ?? '');

            if ($slug === '') {
                continue;
            }

            $result[$slug] = [
                'avg_position'    => $row['avg_position'] !== null ? (float) $row['avg_position'] : null,
                'total_impressions' => (int) ($row['total_impressions'] ?? 0),
                'total_clicks'    => (int) ($row['total_clicks'] ?? 0),
                'last_checked_at' => (string) ($row['last_checked_at'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * Возвращает кеш индексации инструментов, сгруппированный по tool_slug.
     *
     * @param  string $provider Провайдер (yandex, google).
     * @return array<string, array<string, array<string, mixed>>> Ключ — [tool_slug][locale].
     */
    public function indexationBySlug(string $provider = 'yandex'): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $rows = $this->db->fetchAll(
            'SELECT tool_slug, locale, indexing_status, http_code, crawl_date, checked_at
             FROM ' . Tables::TOOL_INDEXATION_SNAPSHOTS . '
             WHERE provider = ?
             ORDER BY checked_at DESC',
            [$provider]
        );

        $result = [];

        foreach ($rows as $row) {
            $slug = (string) ($row['tool_slug'] ?? '');
            $locale = (string) ($row['locale'] ?? '');

            if ($slug === '' || $locale === '') {
                continue;
            }

            $result[$slug][$locale] = [
                'indexing_status' => (string) ($row['indexing_status'] ?? ''),
                'http_code'       => $row['http_code'] !== null ? (int) $row['http_code'] : null,
                'crawl_date'      => (string) ($row['crawl_date'] ?? ''),
                'checked_at'      => (string) ($row['checked_at'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * Вставляет или обновляет запись индексации инструмента.
     *
     * @param array<string, mixed> $data Поля: tool_slug, locale, url, provider, indexing_status, http_code, crawl_date.
     */
    public function upsertIndexation(array $data): void
    {
        $now = date('Y-m-d H:i:s');

        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::TOOL_INDEXATION_SNAPSHOTS . '
             WHERE tool_slug = ? AND locale = ? AND provider = ? LIMIT 1',
            [(string) ($data['tool_slug'] ?? ''), (string) ($data['locale'] ?? ''), (string) ($data['provider'] ?? 'yandex')]
        );

        if ($existing !== null) {
            $this->db->execute(
                'UPDATE ' . Tables::TOOL_INDEXATION_SNAPSHOTS . '
                 SET url = ?, indexing_status = ?, http_code = ?, crawl_date = ?, checked_at = ?, updated_at = ?
                 WHERE id = ?',
                [
                    (string) ($data['url'] ?? ''),
                    $data['indexing_status'] ?? null,
                    $data['http_code'] ?? null,
                    $data['crawl_date'] ?? null,
                    $now,
                    $now,
                    (int) $existing['id'],
                ]
            );
        } else {
            $this->db->insert(Tables::TOOL_INDEXATION_SNAPSHOTS, [
                'tool_slug'       => (string) ($data['tool_slug'] ?? ''),
                'locale'          => (string) ($data['locale'] ?? ''),
                'url'             => (string) ($data['url'] ?? ''),
                'provider'        => (string) ($data['provider'] ?? 'yandex'),
                'indexing_status' => $data['indexing_status'] ?? null,
                'http_code'       => $data['http_code'] ?? null,
                'crawl_date'      => $data['crawl_date'] ?? null,
                'checked_at'      => $now,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    /**
     * Возвращает список опубликованных инструментов с alias категории для построения URL.
     *
     * @return array<int, array{tool_slug: string, category_alias: string}>
     */
    public function listPublishedWithCategoryAlias(): array
    {
        return $this->db->fetchAll(
            'SELECT c.alias AS tool_slug, cat.alias AS category_alias
             FROM ' . Tables::CIPHERS . ' c
             JOIN ' . Tables::CIPHER_CATEGORIES . ' cat ON cat.id = c.category_id
             WHERE c.published = 1'
        );
    }

    /**
     * Проверяет, существует ли таблица индексации в БД.
     */
    private function tableExists(): bool
    {
        try {
            $this->db->fetch('SELECT 1 FROM ' . Tables::TOOL_INDEXATION_SNAPSHOTS . ' LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
