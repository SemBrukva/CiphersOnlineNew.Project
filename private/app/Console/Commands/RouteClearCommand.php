<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;

/**
 * Команда очистки кеша маршрутов приложения.
 */
final class RouteClearCommand implements CommandInterface
{
    /** Путь к директории кеша приложения. */
    private const CACHE_DIR = __DIR__ . '/../../../storage/cache';

    /**
     * Удаляет файл кеша маршрутов, если он существует.
     */
    public function handle(array $args): int
    {
        $cachePath = self::CACHE_DIR . '/routes.php';

        if (is_file($cachePath)) {
            unlink($cachePath);
            echo "Кеш маршрутов удалён: {$cachePath}" . PHP_EOL;
            return 0;
        }

        echo "Кеш маршрутов не найден: {$cachePath}" . PHP_EOL;

        return 0;
    }
}
