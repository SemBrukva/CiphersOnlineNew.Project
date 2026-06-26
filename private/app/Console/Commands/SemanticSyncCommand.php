<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Repository\SemanticCoreRepository as SemanticCoreDbRepository;
use App\Semantic\SemanticCoreRepository as SemanticCoreFileRepository;
use Throwable;

/**
 * Синхронизирует semantic-core JSON в таблицы БД.
 */
final readonly class SemanticSyncCommand implements CommandInterface
{
    /**
     * Создаёт команду синхронизации семантики.
     */
    public function __construct(
        private SemanticCoreFileRepository $files,
        private SemanticCoreDbRepository $db,
    ) {
    }

    /**
     * Выполняет синхронизацию JSON-файлов semantic-core в БД.
     *
     * @param  string[] $args Аргументы команды.
     * @return int            Код завершения.
     */
    public function handle(array $args): int
    {
        if (!$this->db->isReady()) {
            echo 'Таблицы семантического ядра не найдены. Выполните: php bin/console migrate' . PHP_EOL;
            return 1;
        }

        $issues = $this->files->validateAll();
        if ($issues !== []) {
            echo 'Semantic-core содержит ошибки, синхронизация остановлена:' . PHP_EOL;
            foreach ($issues as $issue) {
                echo '  - ' . $issue['file'] . ': ' . $issue['message'] . PHP_EOL;
            }

            return 1;
        }

        try {
            $clusters = $this->files->all();
            foreach ($clusters as $cluster) {
                $id = $this->db->syncCluster($cluster);
                echo 'Synced #' . $id . ': ' . ($cluster['_file'] ?? '<unknown>') . PHP_EOL;
            }
        } catch (Throwable $e) {
            echo 'Ошибка синхронизации: ' . $e->getMessage() . PHP_EOL;
            return 1;
        }

        echo 'Готово: ' . count($clusters) . ' кластер(ов).' . PHP_EOL;

        return 0;
    }
}
