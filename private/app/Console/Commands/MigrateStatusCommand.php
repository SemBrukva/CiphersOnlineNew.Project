<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Migrator;

/**
 * Команда отображения статуса всех миграций базы данных.
 */
final readonly class MigrateStatusCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Migrator $migrator)
    {
    }

    /**
     * Выводит список всех миграций с указанием статуса и номера пакета.
     */
    public function handle(array $args): int
    {
        $status = $this->migrator->status();

        if (empty($status)) {
            echo 'Файлы миграций не найдены.' . PHP_EOL;
            return 0;
        }

        foreach ($status as $item) {
            $mark  = $item['ran'] ? '[+]' : '[ ]';
            $batch = $item['ran'] ? " (пакет {$item['batch']})" : '';
            echo "{$mark} {$item['migration']}{$batch}" . PHP_EOL;
        }

        return 0;
    }
}
