<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Migrator;

/**
 * Команда отката последнего пакета миграций базы данных.
 */
final readonly class MigrateRollbackCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Migrator $migrator)
    {
    }

    /**
     * Откатывает все миграции из последнего пакета и выводит результат.
     */
    public function handle(array $args): int
    {
        $rolled = $this->migrator->rollback();

        if (empty($rolled)) {
            echo 'Нечего откатывать.' . PHP_EOL;
            return 0;
        }

        foreach ($rolled as $name) {
            echo "Откачена: {$name}" . PHP_EOL;
        }

        return 0;
    }
}
