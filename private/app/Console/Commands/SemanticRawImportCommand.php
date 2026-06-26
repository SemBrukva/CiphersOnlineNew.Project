<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Semantic\SemanticRawImporter;
use Throwable;

/**
 * Импортирует сырые CSV-файлы семантики в JSON semantic-core.
 */
final readonly class SemanticRawImportCommand implements CommandInterface
{
    /**
     * Создаёт команду импорта сырой семантики.
     */
    public function __construct(private SemanticRawImporter $importer)
    {
    }

    /**
     * Выполняет импорт CSV-файлов semantic-raw.
     *
     * @param  string[] $args Аргументы команды.
     * @return int            Код завершения.
     */
    public function handle(array $args): int
    {
        $force = in_array('--force', $args, true);
        $all = in_array('--all', $args, true);
        $paths = array_values(array_filter($args, static fn (string $arg): bool => !str_starts_with($arg, '--')));

        try {
            $results = $all
                ? $this->importer->importAll($force)
                : [$this->importer->import($paths[0] ?? '', $force)];
        } catch (Throwable $e) {
            $this->usage($e->getMessage());
            return 1;
        }

        foreach ($results as $result) {
            echo $result['input'] . ' -> ' . $result['output']
                . ' (' . $result['queries'] . ' queries, score ' . $result['total_score'] . ')' . PHP_EOL;
        }

        return 0;
    }

    /**
     * Выводит справку по команде.
     */
    private function usage(string $error): void
    {
        echo 'Ошибка: ' . $error . PHP_EOL . PHP_EOL;
        echo 'Использование:' . PHP_EOL;
        echo '  php bin/console semantic:raw:import <csv_path> [--force]' . PHP_EOL;
        echo '  php bin/console semantic:raw:import --all [--force]' . PHP_EOL;
    }
}
