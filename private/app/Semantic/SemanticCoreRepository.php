<?php

declare(strict_types=1);

namespace App\Semantic;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Читает файловое семантическое ядро сайта из JSON-файлов.
 */
final readonly class SemanticCoreRepository
{
    /**
     * Создаёт репозиторий семантического ядра.
     */
    public function __construct(private string $rootPath)
    {
    }

    /**
     * Возвращает путь к корню файлов семантики.
     */
    public function rootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * Возвращает все кластеры семантического ядра.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $clusters = [];

        foreach ($this->jsonFiles() as $path) {
            $cluster = $this->readFile($path);
            $cluster['_file'] = $this->relativePath($path);
            $cluster['_absolute_file'] = $path;
            $cluster['_total_volume'] = $this->totalVolume($cluster);
            $cluster['_queries_count'] = count($cluster['queries'] ?? []);
            $clusters[] = $cluster;
        }

        usort($clusters, static function (array $a, array $b): int {
            return strcmp((string) ($a['locale'] ?? ''), (string) ($b['locale'] ?? ''))
                ?: strcmp((string) ($a['tool']['slug'] ?? ''), (string) ($b['tool']['slug'] ?? ''))
                ?: strcmp((string) ($a['cluster'] ?? ''), (string) ($b['cluster'] ?? ''));
        });

        return $clusters;
    }

    /**
     * Возвращает сводку по семантическому ядру.
     *
     * @return array{clusters: int, queries: int, total_volume: int, locales: array<string, int>, statuses: array<string, int>, issues: int}
     */
    public function summary(): array
    {
        $summary = [
            'clusters' => 0,
            'queries' => 0,
            'total_volume' => 0,
            'locales' => [],
            'statuses' => [],
            'issues' => 0,
        ];

        foreach ($this->all() as $cluster) {
            $locale = (string) ($cluster['locale'] ?? 'unknown');
            $status = (string) ($cluster['status'] ?? 'unknown');

            $summary['clusters']++;
            $summary['queries'] += (int) $cluster['_queries_count'];
            $summary['total_volume'] += (int) $cluster['_total_volume'];
            $summary['locales'][$locale] = ($summary['locales'][$locale] ?? 0) + 1;
            $summary['statuses'][$status] = ($summary['statuses'][$status] ?? 0) + 1;
        }

        $summary['issues'] = count($this->validateAll());

        ksort($summary['locales']);
        ksort($summary['statuses']);

        return $summary;
    }

    /**
     * Проверяет все файлы семантического ядра.
     *
     * @return array<int, array{file: string, message: string}>
     */
    public function validateAll(): array
    {
        $issues = [];
        $seen = [];

        foreach ($this->jsonFiles() as $path) {
            try {
                $cluster = $this->readFile($path);
            } catch (RuntimeException $e) {
                $issues[] = ['file' => $this->relativePath($path), 'message' => $e->getMessage()];
                continue;
            }

            foreach ($this->validateCluster($cluster, $path) as $message) {
                $issues[] = ['file' => $this->relativePath($path), 'message' => $message];
            }

            $identity = implode('|', [
                (string) ($cluster['locale'] ?? ''),
                (string) ($cluster['tool']['slug'] ?? ''),
                (string) ($cluster['cluster'] ?? ''),
            ]);

            if (isset($seen[$identity])) {
                $issues[] = [
                    'file' => $this->relativePath($path),
                    'message' => 'Дублируется связка locale + tool.slug + cluster с файлом ' . $seen[$identity],
                ];
            }

            $seen[$identity] = $this->relativePath($path);
        }

        return $issues;
    }

    /**
     * Проверяет один кластер семантики.
     *
     * @param  array<string, mixed> $cluster Данные кластера.
     * @return string[]                     Список найденных проблем.
     */
    private function validateCluster(array $cluster, string $path): array
    {
        $issues = [];

        foreach (['schema', 'locale', 'cluster', 'intent', 'status', 'tool', 'queries'] as $field) {
            if (!array_key_exists($field, $cluster)) {
                $issues[] = 'Отсутствует поле ' . $field;
            }
        }

        if (($cluster['schema'] ?? null) !== 'semantic-core.v1') {
            $issues[] = 'Поле schema должно быть semantic-core.v1';
        }

        if (!is_array($cluster['tool'] ?? null)) {
            $issues[] = 'Поле tool должно быть объектом';
        } else {
            foreach (['slug', 'url', 'content_file'] as $field) {
                if (trim((string) ($cluster['tool'][$field] ?? '')) === '') {
                    $issues[] = 'Отсутствует tool.' . $field;
                }
            }

            $contentFile = trim((string) ($cluster['tool']['content_file'] ?? ''));
            if ($contentFile !== '' && !is_file(BASE_PATH . '/' . ltrim($contentFile, '/'))) {
                $issues[] = 'Связанный content_file не найден: ' . $contentFile;
            }
        }

        if (!is_array($cluster['queries'] ?? null) || $cluster['queries'] === []) {
            $issues[] = 'Поле queries должно быть непустым массивом';
        } else {
            $primaryCount = 0;
            foreach ($cluster['queries'] as $index => $query) {
                if (!is_array($query)) {
                    $issues[] = 'Запрос #' . ($index + 1) . ' должен быть объектом';
                    continue;
                }

                if (trim((string) ($query['query'] ?? '')) === '') {
                    $issues[] = 'У запроса #' . ($index + 1) . ' пустое поле query';
                }

                if (isset($query['volume']) && (!is_int($query['volume']) || $query['volume'] < 0)) {
                    $issues[] = 'У запроса #' . ($index + 1) . ' volume должен быть неотрицательным целым числом';
                }

                if (isset($query['score']) && (!is_int($query['score']) || $query['score'] < 0)) {
                    $issues[] = 'У запроса #' . ($index + 1) . ' score должен быть неотрицательным целым числом';
                }

                if (($query['priority'] ?? '') === 'primary') {
                    $primaryCount++;
                }
            }

            if ($primaryCount === 0) {
                $issues[] = 'Нужен хотя бы один запрос с priority=primary';
            }
        }

        $expectedLocale = basename(dirname(dirname($path)));
        if (($cluster['locale'] ?? null) !== $expectedLocale) {
            $issues[] = 'Поле locale не совпадает с директорией: ожидается ' . $expectedLocale;
        }

        return $issues;
    }

    /**
     * Читает и декодирует JSON-файл.
     *
     * @return array<string, mixed>
     */
    private function readFile(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Не удалось прочитать файл');
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Некорректный JSON: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new RuntimeException('Корень JSON должен быть объектом');
        }

        return $data;
    }

    /**
     * Возвращает все JSON-файлы семантического ядра.
     *
     * @return string[]
     */
    private function jsonFiles(): array
    {
        if (!is_dir($this->rootPath)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->rootPath));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Считает суммарную частотность кластера.
     *
     * @param array<string, mixed> $cluster Данные кластера.
     */
    private function totalVolume(array $cluster): int
    {
        $total = 0;

        foreach (($cluster['queries'] ?? []) as $query) {
            if (is_array($query)) {
                $total += (int) ($query['volume'] ?? $query['score'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Преобразует абсолютный путь в путь относительно проекта.
     */
    private function relativePath(string $path): string
    {
        return ltrim(str_replace(BASE_PATH, '', $path), '/');
    }
}
