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
        $date = (string) ($options['date'] ?? (new DateTimeImmutable('yesterday'))->format('Y-m-d'));

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
        echo 'Queries: ' . $result['queries'] . PHP_EOL;
        echo 'API pages: ' . $result['api_pages'] . PHP_EOL;
        echo 'API records: ' . $result['api_records'] . PHP_EOL;
        echo 'Matched: ' . $result['matched'] . PHP_EOL;
        echo 'Missing: ' . $result['missing'] . PHP_EOL;
        echo 'Saved: ' . $result['saved'] . PHP_EOL;

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
            }
        }

        return $options;
    }
}
