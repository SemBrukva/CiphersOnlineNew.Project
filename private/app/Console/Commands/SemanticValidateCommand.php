<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Semantic\SemanticCoreRepository;

/**
 * Проверяет JSON-файлы семантического ядра.
 */
final readonly class SemanticValidateCommand implements CommandInterface
{
    /**
     * Создаёт команду проверки семантики.
     */
    public function __construct(private SemanticCoreRepository $semanticCore)
    {
    }

    /**
     * Выполняет проверку файлов семантического ядра.
     *
     * @param  string[] $args Аргументы команды.
     * @return int            Код завершения.
     */
    public function handle(array $args): int
    {
        $summary = $this->semanticCore->summary();
        $issues = $this->semanticCore->validateAll();

        echo 'Semantic core: ' . $this->semanticCore->rootPath() . PHP_EOL;
        echo 'Clusters: ' . $summary['clusters'] . PHP_EOL;
        echo 'Queries: ' . $summary['queries'] . PHP_EOL;
        echo 'Total score: ' . $summary['total_volume'] . PHP_EOL;

        if ($issues === []) {
            echo PHP_EOL . 'OK: semantic core is valid.' . PHP_EOL;
            return 0;
        }

        echo PHP_EOL . 'Issues:' . PHP_EOL;
        foreach ($issues as $issue) {
            echo '  - ' . $issue['file'] . ': ' . $issue['message'] . PHP_EOL;
        }

        return 1;
    }
}
