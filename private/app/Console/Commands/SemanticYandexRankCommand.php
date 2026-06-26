<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Semantic\SemanticRankSnapshotService;
use DateTimeImmutable;
use Throwable;

/**
 * Собирает позиции запросов семантического ядра из Яндекс Вебмастера.
 */
final readonly class SemanticYandexRankCommand implements CommandInterface
{
    /**
     * Создаёт команду сбора позиций.
     */
    public function __construct(private SemanticRankSnapshotService $snapshots)
    {
    }

    /**
     * Выполняет сбор снимка позиций.
     *
     * @param  string[] $args Аргументы команды.
     * @return int            Код завершения.
     */
    public function handle(array $args): int
    {
        $options = $this->parseOptions($args);
        $hasExplicitDate = array_key_exists('date', $options);
        $date = (string) ($options['date'] ?? (new DateTimeImmutable('yesterday'))->format('Y-m-d'));
        $options['auto_date'] = !$hasExplicitDate;

        try {
            $result = $this->snapshots->collectYandexWebmaster($date, $options);
        } catch (Throwable $e) {
            echo 'Ошибка сбора позиций Яндекс Вебмастера: ' . $e->getMessage() . PHP_EOL;
            return 1;
        }

        echo sprintf(
            "Yandex Webmaster rank snapshot: date=%s%s\n",
            $result['date'],
            $result['dry_run'] ? ' dry-run' : ''
        );
        if (($result['date_adjusted'] ?? false) === true) {
            echo 'Requested date: ' . $result['requested_date'] . PHP_EOL;
            echo 'Adjusted to latest available date: ' . $result['date'] . PHP_EOL;
        }
        echo 'Queries: ' . $result['queries'] . PHP_EOL;
        echo 'API pages: ' . $result['api_pages'] . PHP_EOL;
        echo 'API records: ' . $result['api_records'] . PHP_EOL;
        echo 'API total count: ' . $result['api_total_count'] . PHP_EOL;
        echo 'Matched: ' . $result['matched'] . PHP_EOL;
        echo 'Missing: ' . $result['missing'] . PHP_EOL;
        echo 'Saved: ' . $result['saved'] . PHP_EOL;

        if ((int) ($options['sample_size'] ?? 0) > 0) {
            $this->printSamples('Semantic-core samples', $result['semantic_samples'] ?? []);
            $this->printSamples('Yandex Webmaster samples', $result['api_samples'] ?? []);
            $this->printSamples('Missing semantic-core samples', $result['missing_samples'] ?? []);
        }

        return 0;
    }

    /**
     * Разбирает CLI-опции команды.
     *
     * @param  string[] $args Аргументы CLI.
     * @return array<string, mixed>
     */
    private function parseOptions(array $args): array
    {
        $options = [];

        foreach ($args as $arg) {
            if ($arg === '--dry-run') {
                $options['dry_run'] = true;
                continue;
            }

            if ($arg === '--no-missing') {
                $options['record_missing'] = false;
                continue;
            }

            if ($arg === '--debug-samples') {
                $options['sample_size'] = 20;
                continue;
            }

            if (str_starts_with($arg, '--date=')) {
                $options['date'] = substr($arg, 7);
                continue;
            }

            if (str_starts_with($arg, '--locale=')) {
                $options['locale'] = substr($arg, 9);
                continue;
            }

            if (str_starts_with($arg, '--limit=')) {
                $options['limit'] = max(1, (int) substr($arg, 8));
                continue;
            }

            if (str_starts_with($arg, '--sample-size=')) {
                $options['sample_size'] = max(1, (int) substr($arg, 14));
            }
        }

        return $options;
    }

    /**
     * Печатает примеры запросов для диагностики сопоставления.
     *
     * @param mixed $samples Список строк.
     */
    private function printSamples(string $title, mixed $samples): void
    {
        echo PHP_EOL . $title . ':' . PHP_EOL;

        if (!is_array($samples) || $samples === []) {
            echo '  <empty>' . PHP_EOL;
            return;
        }

        foreach ($samples as $sample) {
            echo '  - ' . (string) $sample . PHP_EOL;
        }
    }
}
