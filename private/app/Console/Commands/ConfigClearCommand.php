<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;

/**
 * Команда очистки кеша конфигурации приложения.
 */
final class ConfigClearCommand implements CommandInterface
{
    /** Путь к директории кеша приложения. */
    private const CACHE_DIR = __DIR__ . '/../../../storage/cache';

    /**
     * Удаляет файл кеша конфигурации, если он существует.
     */
    public function handle(array $args): int
    {
        $cachePath = self::CACHE_DIR . '/config.php';

        if (is_file($cachePath)) {
            unlink($cachePath);
            echo "Кеш конфигурации удалён: {$cachePath}" . PHP_EOL;
            return 0;
        }

        echo "Кеш конфигурации не найден: {$cachePath}" . PHP_EOL;

        return 0;
    }
}
