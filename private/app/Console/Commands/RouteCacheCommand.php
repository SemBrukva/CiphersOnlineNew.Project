<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Http\RouteCache;
use App\Http\RouteLoader;

/**
 * Команда сборки кеша маршрутов приложения.
 */
final readonly class RouteCacheCommand implements CommandInterface
{
    /** Путь к директории кеша приложения. */
    private const CACHE_DIR = __DIR__ . '/../../../storage/cache';

    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private RouteLoader $routeLoader)
    {
    }

    /**
     * Собирает скомпилированные маршруты в единый кеш-файл.
     */
    public function handle(array $args): int
    {
        $merged = $this->routeLoader->loadMerged(
            config('routes', []),
            config('admin_routes', []),
            config('api_routes', [])
        );

        $routes = RouteCache::compile(
            $merged['web'],
            $merged['admin'],
            $merged['api'],
            (string) config('admin.path', '/admin')
        );

        $cachePath = self::CACHE_DIR . '/routes.php';
        RouteCache::dump($cachePath, $routes);

        echo "Кеш маршрутов создан: {$cachePath}" . PHP_EOL;

        return 0;
    }
}
