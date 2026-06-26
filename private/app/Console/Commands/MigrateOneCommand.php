<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Migrator;
use Throwable;

/**
 * Команда применения одной указанной миграции базы данных.
 */
final readonly class MigrateOneCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Migrator $migrator)
    {
    }

    /**
     * Запускает одну миграцию по имени файла и выводит результат.
     */
    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            echo 'Использование: php bin/console migrate:one <migration>' . PHP_EOL;
            echo 'Пример: php bin/console migrate:one 2026_06_26_121343_seed_anagram_solver' . PHP_EOL;

            return 1;
        }

        try {
            $ran = $this->migrator->runOne($name);
        } catch (Throwable $e) {
            echo 'Ошибка миграции: ' . $e->getMessage() . PHP_EOL;

            return 1;
        }

        $migration = basename($name, '.php');

        if (!$ran) {
            echo "Миграция уже применена: {$migration}" . PHP_EOL;
            return 0;
        }

        echo "Применена: {$migration}" . PHP_EOL;

        return 0;
    }
}
