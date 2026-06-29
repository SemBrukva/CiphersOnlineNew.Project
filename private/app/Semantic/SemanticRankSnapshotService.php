<?php

declare(strict_types=1);

namespace App\Semantic;

use App\Repository\SemanticCoreRepository;
use App\Yandex\WebmasterApiException;
use App\Yandex\WebmasterClient;
use DateTimeImmutable;
use RuntimeException;

/**
 * Снимает позиции запросов семантического ядра через API поисковых систем.
 */
final readonly class SemanticRankSnapshotService
{
    private const PROVIDER_YANDEX_WEBMASTER = 'yandex_webmaster';

    /**
     * Создаёт сервис сбора позиций семантического ядра.
     *
     * @param array<string, mixed> $config Конфигурация yandex_webmaster.
     */
    public function __construct(
        private SemanticCoreRepository $semanticCore,
        private WebmasterClient $webmaster,
        private array $config,
    ) {
    }

    /**
     * Собирает снимок позиций из Яндекс Вебмастера за дату.
     *
     * @param  array<string, mixed> $options Опции: locale, limit, dry_run, record_missing.
     * @return array<string, bool|int|string|array<int, string>> Сводка результата.
     */
    public function collectYandexWebmaster(string $date, array $options = []): array
    {
        $this->assertDate($date);

        if (!$this->semanticCore->isReady()) {
            throw new RuntimeException('Таблицы семантического ядра не найдены. Выполните: php bin/console migrate');
        }

        $queries = $this->semanticCore->trackedQueries(
            isset($options['locale']) ? (string) $options['locale'] : null,
            isset($options['limit']) ? (int) $options['limit'] : null,
        );

        $sampleSize = max(0, (int) ($options['sample_size'] ?? 0));
        $semanticSamples = $this->querySamples($queries, $sampleSize);

        if ($queries === []) {
            return $this->emptyResult($date, (bool) ($options['dry_run'] ?? false), $semanticSamples);
        }

        $requestedDate = $date;
        $dateAdjusted = false;
        try {
            $records = $this->fetchYandexRecords($date);
        } catch (WebmasterApiException $e) {
            $fallbackDate = $this->latestAllowedDate($e);
            if (!((bool) ($options['auto_date'] ?? false)) || $fallbackDate === null || $fallbackDate === $date) {
                throw $e;
            }

            $date = $fallbackDate;
            $dateAdjusted = true;
            $records = $this->fetchYandexRecords($date);
        }

        $recordMissing = array_key_exists('record_missing', $options)
            ? (bool) $options['record_missing']
            : (bool) ($this->config['record_missing'] ?? true);
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $matched = 0;
        $missing = 0;
        $saved = 0;

        foreach ($queries as $query) {
            $normalized = $this->normalizeQuery((string) $query['query']);
            $record = $records['by_query'][$normalized] ?? null;

            if ($record === null) {
                $missing++;
                if (!$recordMissing) {
                    continue;
                }

                $snapshot = [
                    'query_id' => (int) $query['id'],
                    'provider' => self::PROVIDER_YANDEX_WEBMASTER,
                    'checked_at' => $date,
                    'position' => null,
                    'impressions' => null,
                    'clicks' => null,
                    'ctr' => null,
                    'url' => null,
                    'raw_json' => $this->json(['missing' => true, 'query' => (string) $query['query']]),
                ];
            } else {
                $matched++;
                $snapshot = [
                    'query_id' => (int) $query['id'],
                    'provider' => self::PROVIDER_YANDEX_WEBMASTER,
                    'checked_at' => $date,
                    'position' => $this->nullableInt($record['position'] ?? null),
                    'impressions' => $this->nullableInt($record['impressions'] ?? null),
                    'clicks' => $this->nullableInt($record['clicks'] ?? null),
                    'ctr' => $this->nullableFloat($record['ctr'] ?? null),
                    'url' => isset($record['url']) ? mb_substr((string) $record['url'], 0, 255) : null,
                    'raw_json' => $this->json($record['raw'] ?? []),
                ];
            }

            if (!$dryRun) {
                $this->semanticCore->upsertRankSnapshot($snapshot);
            }

            $saved++;
        }

        return [
            'date' => $date,
            'requested_date' => $requestedDate,
            'date_adjusted' => $dateAdjusted,
            'dry_run' => $dryRun,
            'queries' => count($queries),
            'api_records' => (int) $records['records'],
            'api_pages' => (int) $records['pages'],
            'api_total_count' => (int) $records['count'],
            'matched' => $matched,
            'missing' => $missing,
            'saved' => $saved,
            'semantic_samples' => $semanticSamples,
            'api_samples' => $sampleSize > 0 ? $records['samples'] : [],
            'missing_samples' => $sampleSize > 0 ? $this->missingSamples($queries, $records['by_query'], $sampleSize) : [],
        ];
    }

    /**
     * Возвращает последнюю доступную дату из ошибки ограничения диапазона.
     */
    private function latestAllowedDate(WebmasterApiException $exception): ?string
    {
        if ($exception->errorCode() !== 'RESTRICTIONS_VIOLATED') {
            return null;
        }

        if (!preg_match('/to \(inclusively\) (?<date>\d{4}-\d{2}-\d{2})/u', $exception->apiErrorMessage(), $matches)) {
            return null;
        }

        $date = (string) $matches['date'];
        $this->assertDate($date);

        return $date;
    }

    /**
     * Загружает страницы query-analytics/list и индексирует их по запросу.
     *
     * @return array{by_query: array<string, array<string, mixed>>, records: int, pages: int, count: int, samples: array<int, string>}
     */
    private function fetchYandexRecords(string $date): array
    {
        $pageSize = (int) ($this->config['page_size'] ?? 500);
        $maxPages = (int) ($this->config['max_pages'] ?? 40);
        $byQuery = [];
        $totalRecords = 0;
        $count = 0;
        $pages = 0;
        $samples = [];

        for ($page = 0; $page < $maxPages; $page++) {
            $payload = $this->payload($date, $page * $pageSize, $pageSize);
            $data = $this->webmaster->queryAnalyticsList($payload);
            $items = $this->items($data);
            $count = max($count, isset($data['count']) ? (int) $data['count'] : 0);

            $pages++;
            $totalRecords += count($items);

            foreach ($items as $item) {
                $record = $this->recordFromItem($item, $date);
                if ($record === null) {
                    continue;
                }

                $key = $this->normalizeQuery((string) $record['query']);
                if (count($samples) < 25) {
                    $samples[] = (string) $record['query'];
                }

                $current = $byQuery[$key] ?? null;
                if ($current === null || $this->isBetterRecord($record, $current)) {
                    $byQuery[$key] = $record;
                }
            }

            $count = isset($data['count']) ? (int) $data['count'] : null;
            if ($items === [] || ($count !== null && ($page + 1) * $pageSize >= $count)) {
                break;
            }
        }

        return ['by_query' => $byQuery, 'records' => $totalRecords, 'pages' => $pages, 'count' => $count, 'samples' => $samples];
    }

    /**
     * Формирует тело запроса query-analytics/list.
     *
     * @return array<string, mixed>
     */
    private function payload(string $date, int $offset, int $limit): array
    {
        $payload = [
            'offset' => $offset,
            'limit' => $limit,
            'text_indicator' => 'QUERY',
            'device_type_indicator' => (string) ($this->config['device_type_indicator'] ?? 'ALL'),
            'sort_by_date' => [
                'date' => $date,
                'statistic_field' => 'IMPRESSIONS',
                'by' => 'DESC',
            ],
        ];

        $regionIds = $this->config['region_ids'] ?? [];
        if (is_array($regionIds) && $regionIds !== []) {
            $payload['region_ids'] = array_values(array_map('intval', $regionIds));
        }

        return $payload;
    }

    /**
     * Возвращает элементы аналитики из ответа API.
     *
     * @param  array<string, mixed> $data Ответ API.
     * @return array<int, array<string, mixed>>
     */
    private function items(array $data): array
    {
        $items = $data['text_indicator_to_statistics'] ?? $data['query_analytics'] ?? $data['items'] ?? [];

        return is_array($items) ? array_values(array_filter($items, 'is_array')) : [];
    }

    /**
     * Преобразует одну запись API в нормализованный снимок.
     *
     * @param  array<string, mixed> $item Запись query analytics.
     * @return array<string, mixed>|null
     */
    private function recordFromItem(array $item, string $date): ?array
    {
        $query = $this->indicatorValue($item['text_indicator'] ?? null);
        if ($query === '') {
            $query = trim((string) ($item['query'] ?? $item['query_text'] ?? ''));
        }

        if ($query === '') {
            return null;
        }

        $stats = $this->statistics($item, $date);

        return [
            'query' => $query,
            'url' => $this->indicatorValue($item['popular_complementary_indicator'] ?? null),
            'position' => $stats['POSITION'] ?? null,
            'impressions' => $stats['IMPRESSIONS'] ?? null,
            'clicks' => $stats['CLICKS'] ?? null,
            'ctr' => $stats['CTR'] ?? null,
            'raw' => $item,
        ];
    }

    /**
     * Достаёт значение текстового индикатора.
     */
    private function indicatorValue(mixed $indicator): string
    {
        if (is_string($indicator)) {
            return trim($indicator);
        }

        if (is_array($indicator)) {
            foreach (['value', 'text', 'url'] as $key) {
                if (isset($indicator[$key])) {
                    return trim((string) $indicator[$key]);
                }
            }
        }

        return '';
    }

    /**
     * Собирает статистику записи за нужную дату.
     *
     * @param  array<string, mixed> $item Запись API.
     * @return array<string, int|float>
     */
    private function statistics(array $item, string $date): array
    {
        $stats = [];
        $source = $item['statistics'] ?? $item['statistic'] ?? [];
        $this->walkStatistics($source, $date, $stats);

        return $stats;
    }

    /**
     * Рекурсивно обходит статистику API.
     *
     * @param mixed                    $value  Текущий узел.
     * @param array<string, int|float> $result Найденные метрики.
     */
    private function walkStatistics(mixed $value, string $date, array &$result): void
    {
        if (!is_array($value)) {
            return;
        }

        $field = strtoupper((string) ($value['field'] ?? $value['statistic_field'] ?? $value['name'] ?? ''));
        $nodeDate = (string) ($value['date'] ?? $value['checked_at'] ?? '');
        if ($field !== '' && array_key_exists('value', $value) && ($nodeDate === '' || $nodeDate === $date)) {
            $numeric = $this->nullableFloat($value['value']);
            if ($numeric !== null) {
                $result[$field] = $numeric;
            }
        }

        foreach ($value as $key => $child) {
            $upperKey = strtoupper((string) $key);
            if (in_array($upperKey, ['POSITION', 'IMPRESSIONS', 'CLICKS', 'CTR'], true) && is_numeric($child)) {
                $result[$upperKey] = (float) $child;
                continue;
            }

            $this->walkStatistics($child, $date, $result);
        }
    }

    /**
     * Определяет, какая запись лучше для одного и того же запроса.
     *
     * @param array<string, mixed> $candidate Новая запись.
     * @param array<string, mixed> $current   Текущая выбранная запись.
     */
    private function isBetterRecord(array $candidate, array $current): bool
    {
        $candidatePosition = $this->nullableFloat($candidate['position'] ?? null);
        $currentPosition = $this->nullableFloat($current['position'] ?? null);

        if ($candidatePosition !== null && $currentPosition !== null) {
            return $candidatePosition < $currentPosition;
        }

        if ($candidatePosition !== null) {
            return true;
        }

        $candidateImpressions = $this->nullableInt($candidate['impressions'] ?? null) ?? 0;
        $currentImpressions = $this->nullableInt($current['impressions'] ?? null) ?? 0;

        return $candidateImpressions > $currentImpressions;
    }

    /**
     * Проверяет формат даты YYYY-MM-DD.
     */
    private function assertDate(string $date): void
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new RuntimeException('Дата должна быть в формате YYYY-MM-DD.');
        }
    }

    /**
     * Нормализует поисковый запрос.
     */
    private function normalizeQuery(string $query): string
    {
        return preg_replace('/\s+/u', ' ', trim(mb_strtolower($query))) ?? trim(mb_strtolower($query));
    }

    /**
     * Приводит значение к целому числу или null.
     */
    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    /**
     * Приводит значение к числу с плавающей точкой или null.
     */
    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Кодирует исходные данные API.
     */
    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Возвращает пустую сводку сбора.
     *
     * @param  array<int, string> $semanticSamples Примеры запросов из semantic-core.
     * @return array<string, mixed>
     */
    private function emptyResult(string $date, bool $dryRun, array $semanticSamples = []): array
    {
        return [
            'date' => $date,
            'requested_date' => $date,
            'date_adjusted' => false,
            'dry_run' => $dryRun,
            'queries' => 0,
            'api_records' => 0,
            'api_pages' => 0,
            'api_total_count' => 0,
            'matched' => 0,
            'missing' => 0,
            'saved' => 0,
            'semantic_samples' => $semanticSamples,
            'api_samples' => [],
            'missing_samples' => [],
        ];
    }

    /**
     * Возвращает примеры запросов из semantic-core.
     *
     * @param array<int, array<string, mixed>> $queries Запросы semantic-core.
     * @return array<int, string>
     */
    private function querySamples(array $queries, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $samples = [];
        foreach ($queries as $query) {
            $samples[] = (string) ($query['query'] ?? '');
            if (count($samples) >= $limit) {
                break;
            }
        }

        return $samples;
    }

    /**
     * Возвращает примеры semantic-core запросов, которых нет в ответе Вебмастера.
     *
     * @param array<int, array<string, mixed>>          $queries Запросы semantic-core.
     * @param array<string, array<string, mixed>>       $records Записи Вебмастера по нормализованному запросу.
     * @return array<int, string>
     */
    private function missingSamples(array $queries, array $records, int $limit): array
    {
        $samples = [];
        foreach ($queries as $query) {
            $text = (string) ($query['query'] ?? '');
            if (isset($records[$this->normalizeQuery($text)])) {
                continue;
            }

            $samples[] = $text;
            if (count($samples) >= $limit) {
                break;
            }
        }

        return $samples;
    }
}
