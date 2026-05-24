<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Http\RouteCache;
use App\Http\RouteLoader;

/**
 * Команда вывода списка всех зарегистрированных маршрутов.
 */
final readonly class RouteListCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private RouteLoader $routeLoader)
    {
    }

    /**
     * Выводит список маршрутов из конфига и атрибутов.
     */
    public function handle(array $args): int
    {
        $merged = $this->routeLoader->loadMerged(
            config('routes', []),
            config('admin_routes', []),
            config('api_routes', [])
        );

        $compiled = RouteCache::compile($merged['web'], $merged['admin'], $merged['api'], (string) config('admin.path', '/admin'));

        echo str_pad('METHOD', 8) . ' '
            . str_pad('URI', 36) . ' '
            . str_pad('NAME', 28) . ' '
            . "ACTION\n";
        echo str_repeat('-', 96) . PHP_EOL;

        $rows = $this->collectRows($compiled);

        foreach ($rows as $row) {
            echo str_pad($row['method'], 8) . ' '
                . str_pad($row['uri'], 36) . ' '
                . str_pad($row['name'], 28) . ' '
                . $row['action'] . PHP_EOL;
        }

        echo PHP_EOL . 'Всего маршрутов: ' . count($rows) . PHP_EOL;

        return 0;
    }

    /**
     * @param array{web: array<string, array<string, mixed>>, admin: array<string, array<string, mixed>>, api: array<string, array<string, mixed>>} $compiled
     * @return array<int, array{method: string, uri: string, name: string, action: string}>
     */
    private function collectRows(array $compiled): array
    {
        $rows = [];

        foreach (['web', 'admin', 'api'] as $group) {
            foreach ($compiled[$group] as $key => $route) {
                [$method, $uri] = explode(' ', $key, 2);
                $controller = (string) ($route['controller'] ?? '');
                $action = $controller . '::' . (string) ($route['method'] ?? '');

                $rows[] = [
                    'method' => $method,
                    'uri' => $uri,
                    'name' => (string) ($route['name'] ?? ''),
                    'action' => $action,
                ];
            }
        }

        usort($rows, static fn (array $a, array $b): int => strcmp($a['uri'], $b['uri']) ?: strcmp($a['method'], $b['method']));

        return $rows;
    }
}
