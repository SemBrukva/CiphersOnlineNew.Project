<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий семантического ядра в БД.
 */
final class SemanticCoreRepository extends AbstractRepository
{
    /**
     * Создаёт репозиторий семантического ядра.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::SEMANTIC_CLUSTERS);
    }

    /**
     * Проверяет, существуют ли таблицы семантического ядра.
     */
    public function isReady(): bool
    {
        try {
            $this->db->fetch('SELECT 1 FROM ' . Tables::SEMANTIC_CLUSTERS . ' LIMIT 1');
            $this->db->fetch('SELECT 1 FROM ' . Tables::SEMANTIC_QUERIES . ' LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Синхронизирует cluster JSON в БД и возвращает id кластера.
     *
     * @param array<string, mixed> $cluster Данные semantic-core JSON.
     */
    public function syncCluster(array $cluster): int
    {
        $now = date('Y-m-d H:i:s');
        $tool = is_array($cluster['tool'] ?? null) ? $cluster['tool'] : [];
        $source = is_array($cluster['source'] ?? null) ? $cluster['source'] : [];

        $data = [
            'schema_version' => (string) ($cluster['schema'] ?? 'semantic-core.v1'),
            'locale' => (string) ($cluster['locale'] ?? ''),
            'cluster' => (string) ($cluster['cluster'] ?? ''),
            'intent' => (string) ($cluster['intent'] ?? ''),
            'status' => (string) ($cluster['status'] ?? ''),
            'tool_slug' => (string) ($tool['slug'] ?? ''),
            'url' => (string) ($tool['url'] ?? ''),
            'content_file' => (string) ($tool['content_file'] ?? ''),
            'json_path' => (string) ($cluster['_file'] ?? ''),
            'source_provider' => (string) ($source['provider'] ?? ''),
            'score_metric' => (string) (($cluster['analysis']['score_metric'] ?? null) ?: ($source['score_metric'] ?? '')),
            'total_score' => $this->totalScore($cluster),
            'queries_count' => count($cluster['queries'] ?? []),
            'analysis_json' => $this->json($cluster['analysis'] ?? null),
            'curation_json' => $this->json($cluster['curation'] ?? null),
            'notes' => is_array($cluster['notes'] ?? null)
                ? implode("\n", array_map('strval', $cluster['notes']))
                : (string) ($cluster['notes'] ?? ''),
            'synced_at' => $now,
            'updated_at' => $now,
        ];

        $existing = $this->findBy([
            'locale' => $data['locale'],
            'tool_slug' => $data['tool_slug'],
            'cluster' => $data['cluster'],
        ]);

        if ($existing === null) {
            $data['created_at'] = $now;
            $clusterId = $this->insert($data);
        } else {
            $clusterId = (int) $existing['id'];
            $this->update($clusterId, $data);
        }

        $this->replaceQueries($clusterId, $cluster['queries'] ?? [], $now);

        return $clusterId;
    }

    /**
     * Возвращает список кластеров с агрегатами для админки.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dashboardRows(): array
    {
        if (!$this->isReady()) {
            return [];
        }

        return $this->db->fetchAll(
            'SELECT
                c.*,
                SUM(CASE WHEN q.priority = \'primary\' THEN 1 ELSE 0 END) AS primary_queries,
                SUM(CASE WHEN q.priority = \'secondary\' THEN 1 ELSE 0 END) AS secondary_queries,
                SUM(CASE WHEN q.priority = \'long_tail\' THEN 1 ELSE 0 END) AS long_tail_queries,
                COUNT(s.id) AS rank_snapshots,
                MIN(s.position) AS best_position,
                MAX(s.checked_at) AS last_rank_checked_at
             FROM ' . Tables::SEMANTIC_CLUSTERS . ' c
             LEFT JOIN ' . Tables::SEMANTIC_QUERIES . ' q ON q.cluster_id = c.id
             LEFT JOIN ' . Tables::SEMANTIC_RANK_SNAPSHOTS . ' s ON s.query_id = q.id
             GROUP BY c.id
             ORDER BY c.total_score DESC, c.locale ASC, c.tool_slug ASC'
        );
    }

    /**
     * Возвращает сводку по БД для админки.
     *
     * @return array{ready: bool, clusters: int, queries: int, total_score: int, rank_snapshots: int}
     */
    public function dashboardSummary(): array
    {
        if (!$this->isReady()) {
            return [
                'ready' => false,
                'clusters' => 0,
                'queries' => 0,
                'total_score' => 0,
                'rank_snapshots' => 0,
            ];
        }

        $clusters = $this->db->fetch('SELECT COUNT(*) AS cnt, COALESCE(SUM(total_score), 0) AS score FROM ' . Tables::SEMANTIC_CLUSTERS);
        $queries = $this->db->fetch('SELECT COUNT(*) AS cnt FROM ' . Tables::SEMANTIC_QUERIES);
        $snapshots = $this->db->fetch('SELECT COUNT(*) AS cnt FROM ' . Tables::SEMANTIC_RANK_SNAPSHOTS);

        return [
            'ready' => true,
            'clusters' => (int) ($clusters['cnt'] ?? 0),
            'queries' => (int) ($queries['cnt'] ?? 0),
            'total_score' => (int) ($clusters['score'] ?? 0),
            'rank_snapshots' => (int) ($snapshots['cnt'] ?? 0),
        ];
    }

    /**
     * Заменяет запросы кластера данными из JSON.
     *
     * @param mixed[] $queries Список запросов.
     */
    private function replaceQueries(int $clusterId, array $queries, string $now): void
    {
        $this->db->execute('DELETE FROM ' . Tables::SEMANTIC_QUERIES . ' WHERE cluster_id = ?', [$clusterId]);

        foreach (array_values($queries) as $index => $query) {
            if (!is_array($query)) {
                continue;
            }

            $this->db->insert(
                'INSERT INTO ' . Tables::SEMANTIC_QUERIES . '
                    (cluster_id, query, normalized_query, score, competitiveness, priority, target, intent_json, sort_order, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $clusterId,
                    (string) ($query['query'] ?? ''),
                    $this->normalizeQuery((string) ($query['query'] ?? '')),
                    (int) ($query['score'] ?? $query['volume'] ?? 0),
                    (string) ($query['competitiveness'] ?? ''),
                    (string) ($query['priority'] ?? ''),
                    (string) ($query['target'] ?? ''),
                    $this->json($query['intent'] ?? []),
                    $index + 1,
                    $now,
                    $now,
                ]
            );
        }
    }

    /**
     * Возвращает суммарный score кластера.
     *
     * @param array<string, mixed> $cluster Данные semantic-core JSON.
     */
    private function totalScore(array $cluster): int
    {
        if (isset($cluster['analysis']['total_score'])) {
            return (int) $cluster['analysis']['total_score'];
        }

        $total = 0;
        foreach (($cluster['queries'] ?? []) as $query) {
            if (is_array($query)) {
                $total += (int) ($query['score'] ?? $query['volume'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Нормализует поисковый запрос для поиска дублей.
     */
    private function normalizeQuery(string $query): string
    {
        return preg_replace('/\s+/u', ' ', trim(mb_strtolower($query))) ?? trim(mb_strtolower($query));
    }

    /**
     * Кодирует значение в JSON для хранения в БД.
     */
    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
