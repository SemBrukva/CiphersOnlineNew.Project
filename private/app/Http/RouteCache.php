<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\AdminMiddleware;
use RuntimeException;

/**
 * Компилирует и загружает кеш маршрутов приложения.
 */
final class RouteCache
{
    /**
     * Компилирует маршруты всех HTTP-веток в единый массив.
     *
     * @param array<string, array<string, mixed>> $webRoutes   Маршруты основной веб-ветки.
     * @param array<string, array<string, mixed>> $adminRoutes Маршруты панели администратора.
     * @param array<string, array<string, mixed>> $apiRoutes   Маршруты API.
     * @return array{web: array<string, array<string, mixed>>, admin: array<string, array<string, mixed>>, api: array<string, array<string, mixed>>}
     */
    public static function compile(array $webRoutes, array $adminRoutes, array $apiRoutes, string $adminPrefix): array
    {
        return [
            'web' => $webRoutes,
            'admin' => self::compileAdminRoutes($adminRoutes, $adminPrefix),
            'api' => self::compileApiRoutes($apiRoutes),
        ];
    }

    /**
     * Сохраняет массив маршрутов в PHP-кеш файл.
     *
     * @param array<string, mixed> $routes Скомпилированные маршруты.
     */
    public static function dump(string $path, array $routes): void
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $payload = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($routes, true) . ";\n";
        file_put_contents($path, $payload);
    }

    /**
     * Загружает маршруты из кеш-файла.
     *
     * @return array{web: array<string, array<string, mixed>>, admin: array<string, array<string, mixed>>, api: array<string, array<string, mixed>>}|null
     */
    public static function load(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $routes = require $path;

        if (!is_array($routes)) {
            throw new RuntimeException('Файл кеша маршрутов повреждён: ожидается массив.');
        }

        if (!isset($routes['web'], $routes['admin'], $routes['api'])) {
            throw new RuntimeException('Файл кеша маршрутов повреждён: отсутствуют обязательные секции.');
        }

        return $routes;
    }

    /**
     * Добавляет префикс `/api` ко всем API-маршрутам.
     *
     * @param array<string, array<string, mixed>> $routes
     * @return array<string, array<string, mixed>>
     */
    private static function compileApiRoutes(array $routes): array
    {
        $compiled = [];

        foreach ($routes as $key => $route) {
            [$method, $path] = explode(' ', $key, 2);
            $fullPath = '/api' . ($path === '/' ? '' : $path);
            $compiled["{$method} {$fullPath}"] = $route;
        }

        return $compiled;
    }

    /**
     * Добавляет admin-префикс и обязательный AdminMiddleware к admin-маршрутам.
     *
     * @param array<string, array<string, mixed>> $routes
     * @return array<string, array<string, mixed>>
     */
    private static function compileAdminRoutes(array $routes, string $prefix): array
    {
        $compiled = [];

        foreach ($routes as $key => $route) {
            [$method, $path] = explode(' ', $key, 2);
            $fullPath = $prefix . ($path === '/' ? '' : $path);
            $middleware = $route['middleware'] ?? [];

            if (!in_array(AdminMiddleware::class, $middleware, true)) {
                array_unshift($middleware, AdminMiddleware::class);
            }

            $route['middleware'] = $middleware;
            $compiled["{$method} {$fullPath}"] = $route;
        }

        return $compiled;
    }
}
