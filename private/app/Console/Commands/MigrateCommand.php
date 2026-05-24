<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Migrator;
use Throwable;

/**
 * Команда применения всех ожидающих миграций базы данных.
 */
final readonly class MigrateCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Migrator $migrator)
    {
    }

    /**
     * Запускает ожидающие миграции и выводит результат в консоль.
     */
    public function handle(array $args): int
    {
        try {
            $ran = $this->migrator->run();
        } catch (Throwable $e) {
            echo 'Ошибка миграции: ' . $e->getMessage() . PHP_EOL;

            return 1;
        }

        if (empty($ran)) {
            echo 'Нет ожидающих миграций.' . PHP_EOL;
            return 0;
        }

        foreach ($ran as $name) {
            echo "Применена: {$name}" . PHP_EOL;
        }

        return 0;
    }
}
