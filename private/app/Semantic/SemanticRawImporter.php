<?php

declare(strict_types=1);

namespace App\Semantic;

use RuntimeException;

/**
 * Импортирует сырую семантику из CSV в curated JSON semantic-core.
 */
final readonly class SemanticRawImporter
{
    /**
     * Создаёт импортёр сырой семантики.
     */
    public function __construct(
        private string $rawRootPath,
        private string $coreRootPath,
    ) {
    }

    /**
     * Импортирует один CSV-файл сырой семантики.
     *
     * @return array{input: string, output: string, queries: int, total_score: int, created: bool}
     */
    public function import(string $csvPath, bool $force = false): array
    {
        $csvPath = $this->absolutePath($csvPath);
        if (!is_file($csvPath)) {
            throw new RuntimeException('CSV-файл не найден: ' . $csvPath);
        }

        $metaPath = preg_replace('/\.csv$/', '.meta.json', $csvPath);
        if (!is_string($metaPath) || !is_file($metaPath)) {
            throw new RuntimeException('Meta-файл не найден рядом с CSV: ' . $csvPath);
        }

        $meta = $this->readMeta($metaPath);
        $rows = $this->readRows($csvPath);
        if ($rows === []) {
            throw new RuntimeException('CSV не содержит запросов: ' . $csvPath);
        }

        $outputPath = $this->outputPath($csvPath);
        if (is_file($outputPath) && !$force) {
            throw new RuntimeException('JSON уже существует, используйте --force для перезаписи: ' . $outputPath);
        }

        $document = $this->buildDocument($meta, $rows, $outputPath);
        $directory = dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Не удалось создать директорию: ' . $directory);
        }

        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        file_put_contents($outputPath, $json . PHP_EOL);

        return [
            'input' => $this->relativePath($csvPath),
            'output' => $this->relativePath($outputPath),
            'queries' => count($document['queries']),
            'total_score' => (int) $document['analysis']['total_score'],
            'created' => true,
        ];
    }

    /**
     * Импортирует все CSV-файлы из корня сырой семантики.
     *
     * @return array<int, array{input: string, output: string, queries: int, total_score: int, created: bool}>
     */
    public function importAll(bool $force = false): array
    {
        $files = glob($this->rawRootPath . '/*/*/*.csv') ?: [];
        sort($files);

        $results = [];
        foreach ($files as $file) {
            $results[] = $this->import($file, $force);
        }

        return $results;
    }

    /**
     * Создаёт JSON-документ semantic-core.
     *
     * @param array<string, mixed>        $meta Данные meta.json.
     * @param array<int, array<string, mixed>> $rows Строки CSV.
     * @return array<string, mixed>
     */
    private function buildDocument(array $meta, array $rows, string $outputPath): array
    {
        $tool = (string) $meta['tool'];
        $locale = (string) $meta['locale'];
        $queries = $this->buildQueries($rows);
        $analysis = $this->buildAnalysis($queries, $meta);

        return [
            'schema' => 'semantic-core.v1',
            'locale' => $locale,
            'cluster' => (string) $meta['cluster'],
            'intent' => 'tool',
            'status' => 'draft',
            'source' => [
                'provider' => (string) ($meta['source'] ?? 'unknown'),
                'score_metric' => (string) ($meta['score_metric'] ?? 'score'),
                'raw_file' => $this->relativePath($this->rawPathFromOutputPath($outputPath)),
                'imported_at' => (string) ($meta['imported_at'] ?? date(DATE_ATOM)),
            ],
            'tool' => [
                'slug' => $tool,
                'url' => $this->toolUrl($locale, $tool),
                'content_file' => 'private/storage/content/' . $tool . '/' . $locale . '.json',
            ],
            'analysis' => $analysis,
            'queries' => $queries,
            'notes' => 'Черновик создан из raw-семантики. Перед sync в БД стоит проверить primary/secondary и target.',
        ];
    }

    /**
     * Формирует список запросов с приоритетами, интентами и целевыми зонами страницы.
     *
     * @param array<int, array<string, mixed>> $rows Строки CSV.
     * @return array<int, array<string, mixed>>
     */
    private function buildQueries(array $rows): array
    {
        usort($rows, static fn (array $a, array $b): int => ((int) $b['score']) <=> ((int) $a['score']));

        $maxScore = max(1, (int) ($rows[0]['score'] ?? 1));
        $queries = [];

        foreach ($rows as $index => $row) {
            $score = (int) $row['score'];
            $query = (string) $row['query'];
            $intents = $this->detectIntents($query);
            $priority = $this->priority($index, $score, $maxScore);

            $queries[] = [
                'query' => $query,
                'score' => $score,
                'competitiveness' => (string) ($row['competitiveness'] ?? ''),
                'priority' => $priority,
                'intent' => $intents,
                'target' => $this->target($priority, $intents),
            ];
        }

        return $queries;
    }

    /**
     * Создаёт аналитический блок для агента и админки.
     *
     * @param array<int, array<string, mixed>> $queries Запросы semantic-core.
     * @param array<string, mixed>             $meta    Данные meta.json.
     * @return array<string, mixed>
     */
    private function buildAnalysis(array $queries, array $meta): array
    {
        $words = [];
        $intentCounts = [];
        $modifiers = [];
        $totalScore = 0;

        foreach ($queries as $query) {
            $totalScore += (int) $query['score'];
            foreach ($query['intent'] as $intent) {
                $intentCounts[$intent] = ($intentCounts[$intent] ?? 0) + 1;
            }

            foreach ($this->words((string) $query['query']) as $word) {
                $words[$word] = ($words[$word] ?? 0) + (int) $query['score'];
            }

            foreach ($this->detectModifiers((string) $query['query']) as $modifier) {
                $modifiers[$modifier] = ($modifiers[$modifier] ?? 0) + 1;
            }
        }

        arsort($words);
        arsort($modifiers);
        arsort($intentCounts);

        return [
            'score_metric' => (string) ($meta['score_metric'] ?? 'score'),
            'total_score' => $totalScore,
            'primary_terms' => array_slice(array_keys($words), 0, 12),
            'modifiers' => array_slice(array_keys($modifiers), 0, 12),
            'intents' => $intentCounts,
            'content_recommendations' => $this->recommendations($queries, $intentCounts, $modifiers),
        ];
    }

    /**
     * Определяет приоритет запроса по позиции и относительному весу.
     */
    private function priority(int $index, int $score, int $maxScore): string
    {
        if ($index === 0) {
            return 'primary';
        }

        if ($index <= 4 || $score >= (int) round($maxScore * 0.03)) {
            return 'secondary';
        }

        return 'long_tail';
    }

    /**
     * Определяет целевую область страницы для запроса.
     *
     * @param string[] $intents Интенты запроса.
     */
    private function target(string $priority, array $intents): string
    {
        if ($priority === 'primary') {
            return 'meta_title';
        }

        if (in_array('audio', $intents, true)) {
            return 'faq';
        }

        if ($priority === 'long_tail') {
            if (in_array('decode', $intents, true) || in_array('encode', $intents, true)) {
                return 'faq';
            }

            if (in_array('online', $intents, true)) {
                return 'content_block';
            }

            return 'faq';
        }

        if (in_array('decode', $intents, true) || in_array('encode', $intents, true) || in_array('translate', $intents, true)) {
            return 'intro';
        }

        return 'content_block';
    }

    /**
     * Определяет интенты запроса по устойчивым модификаторам.
     *
     * @return string[]
     */
    private function detectIntents(string $query): array
    {
        $query = mb_strtolower($query);
        $intents = [];

        if (str_contains($query, 'перевод') || str_contains($query, 'перевести') || str_contains($query, 'translator')) {
            $intents[] = 'translate';
        }

        if (str_contains($query, 'с азбуки') || str_contains($query, 'из азбуки') || str_contains($query, 'на русский') || str_contains($query, 'в текст') || str_contains($query, 'расшифр') || str_contains($query, 'дешифр') || str_contains($query, 'декод') || str_contains($query, 'decrypt') || str_contains($query, 'decode')) {
            $intents[] = 'decode';
        }

        if (str_contains($query, 'на азбуку') || str_contains($query, 'в азбуку') || str_contains($query, 'с русского') || str_contains($query, 'текст в') || str_contains($query, 'зашифр') || preg_match('/(^|[^\p{L}\p{N}])шифратор([^\p{L}\p{N}]|$)/u', $query) === 1 || str_contains($query, 'кодир') || str_contains($query, 'encrypt') || str_contains($query, 'encode')) {
            $intents[] = 'encode';
        }

        if (str_contains($query, 'шифр') || str_contains($query, 'cipher')) {
            $intents[] = 'cipher';
        }

        if (str_contains($query, 'звук') || str_contains($query, 'звуком')) {
            $intents[] = 'audio';
        }

        if (str_contains($query, 'английск')) {
            $intents[] = 'english';
        }

        if (str_contains($query, 'онлайн')) {
            $intents[] = 'online';
        }

        if ($intents === []) {
            $intents[] = 'tool';
        }

        return array_values(array_unique($intents));
    }

    /**
     * Выделяет устойчивые модификаторы запросов.
     *
     * @return string[]
     */
    private function detectModifiers(string $query): array
    {
        $query = mb_strtolower($query);
        $patterns = [
            'на русский',
            'с русского',
            'на английский',
            'онлайн',
            'со звуком',
            'по звуку',
            'в текст',
            'русский язык',
            'морзянка',
            'расшифровка',
            'дешифратор',
            'зашифровать',
            'шифратор',
        ];

        return array_values(array_filter($patterns, static fn (string $pattern): bool => str_contains($query, $pattern)));
    }

    /**
     * Формирует рекомендации для генерации контента.
     *
     * @param array<int, array<string, mixed>> $queries      Запросы semantic-core.
     * @param array<string, int>               $intentCounts Счётчики интентов.
     * @param array<string, int>               $modifiers    Счётчики модификаторов.
     * @return string[]
     */
    private function recommendations(array $queries, array $intentCounts, array $modifiers): array
    {
        $primary = (string) ($queries[0]['query'] ?? '');
        $recommendations = [];

        if ($primary !== '') {
            $recommendations[] = 'В meta_title и первом экране сохранить точную связку «' . $primary . '».';
        }

        if (($intentCounts['decode'] ?? 0) > 0) {
            $recommendations[] = 'В intro и FAQ явно покрыть сценарий расшифровки или декодирования, не обещая функций, которых нет в инструменте.';
        }

        if (($intentCounts['encode'] ?? 0) > 0) {
            $recommendations[] = 'В intro и примерах явно покрыть сценарий шифрования или кодирования исходного текста.';
        }

        if (($intentCounts['translate'] ?? 0) > 0) {
            $recommendations[] = 'В описании естественно покрыть двустороннюю конвертацию, если она поддерживается текущим инструментом.';
        }

        if (($intentCounts['online'] ?? 0) > 0) {
            $recommendations[] = 'Подчеркнуть онлайн-сценарий в intro или основном блоке без перегрузки title вторичными формулировками.';
        }

        if (isset($modifiers['со звуком']) || isset($modifiers['по звуку'])) {
            $recommendations[] = 'Добавить FAQ или блок про звук только в пределах фактических возможностей инструмента.';
        }

        return $recommendations;
    }

    /**
     * Возвращает значимые слова запроса без частых служебных слов.
     *
     * @return string[]
     */
    private function words(string $query): array
    {
        $stopWords = ['с', 'на', 'в', 'из', 'по', 'и', 'язык', 'текст', 'онлайн', 'для', 'со'];
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($query), $matches);

        return array_values(array_filter($matches[0], static fn (string $word): bool => mb_strlen($word) > 2 && !in_array($word, $stopWords, true)));
    }

    /**
     * Читает и проверяет meta.json.
     *
     * @return array<string, mixed>
     */
    private function readMeta(string $metaPath): array
    {
        $data = json_decode((string) file_get_contents($metaPath), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Meta-файл должен содержать JSON-объект: ' . $metaPath);
        }

        foreach (['schema', 'locale', 'cluster', 'tool'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new RuntimeException('В meta.json отсутствует поле ' . $field);
            }
        }

        if ($data['schema'] !== 'semantic-raw.v1') {
            throw new RuntimeException('Meta schema должна быть semantic-raw.v1');
        }

        return $data;
    }

    /**
     * Читает строки CSV.
     *
     * @return array<int, array{query: string, score: int, competitiveness: string}>
     */
    private function readRows(string $csvPath): array
    {
        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Не удалось открыть CSV: ' . $csvPath);
        }

        $header = fgetcsv($handle, 0, ';');
        if ($header === false) {
            fclose($handle);
            return [];
        }

        $header = array_map(static fn (string $value): string => trim($value), $header);
        $indexes = array_flip($header);
        foreach (['query', 'score'] as $field) {
            if (!isset($indexes[$field])) {
                fclose($handle);
                throw new RuntimeException('В CSV отсутствует колонка ' . $field);
            }
        }

        $rows = [];
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $query = trim((string) ($row[$indexes['query']] ?? ''));
            if ($query === '') {
                continue;
            }

            $rows[] = [
                'query' => $query,
                'score' => max(0, (int) ($row[$indexes['score']] ?? 0)),
                'competitiveness' => trim((string) ($row[$indexes['competitiveness']] ?? '')),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Строит публичный URL инструмента.
     */
    private function toolUrl(string $locale, string $tool): string
    {
        $prefix = config('locale.multilang', false) ? '/' . $locale : '/' . $locale;

        return $prefix . '/' . ltrim($tool, '/');
    }

    /**
     * Строит путь JSON-файла semantic-core по CSV-файлу semantic-raw.
     */
    private function outputPath(string $csvPath): string
    {
        $relative = ltrim(str_replace($this->rawRootPath, '', $csvPath), '/');

        return $this->coreRootPath . '/' . preg_replace('/\.csv$/', '.json', $relative);
    }

    /**
     * Восстанавливает путь raw CSV по пути output JSON.
     */
    private function rawPathFromOutputPath(string $outputPath): string
    {
        $relative = ltrim(str_replace($this->coreRootPath, '', $outputPath), '/');

        return $this->rawRootPath . '/' . preg_replace('/\.json$/', '.csv', $relative);
    }

    /**
     * Преобразует путь в абсолютный путь проекта.
     */
    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return BASE_PATH . '/' . ltrim($path, '/');
    }

    /**
     * Преобразует абсолютный путь в путь относительно проекта.
     */
    private function relativePath(string $path): string
    {
        return ltrim(str_replace(BASE_PATH, '', $path), '/');
    }
}
