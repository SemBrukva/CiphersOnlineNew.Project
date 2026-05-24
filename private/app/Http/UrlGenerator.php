<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

/**
 * Генератор URL по именованным маршрутам.
 */
final class UrlGenerator
{
    /** @var array<string, string> Карта route-name → path pattern. */
    private array $namedRoutes = [];

    /**
     * Создаёт генератор URL и строит карту именованных маршрутов.
     *
     * @param array<string, array<string, mixed>> $webRoutes
     * @param array<string, array<string, mixed>> $adminRoutes
     * @param array<string, array<string, mixed>> $apiRoutes
     */
    public function __construct(array $webRoutes, array $adminRoutes, array $apiRoutes, string $adminPath)
    {
        $this->registerRoutes($webRoutes);
        $this->registerRoutes($adminRoutes, $adminPath);
        $this->registerRoutes($apiRoutes, '/api');
    }

    /**
     * Строит URL по имени маршрута и набору параметров.
     *
     * @param array<string, scalar> $params
     */
    public function url(string $name, array $params = []): string
    {
        $pattern = $this->namedRoutes[$name] ?? null;

        if ($pattern === null) {
            throw new RuntimeException('Route name not found: ' . $name);
        }

        return preg_replace_callback(
            '/\{([a-z_][a-z0-9_]*)(?::[^}]+)?\}/i',
            static function (array $matches) use ($name, $params): string {
                $param = $matches[1];

                if (!array_key_exists($param, $params)) {
                    throw new RuntimeException(sprintf('Missing route parameter "%s" for route "%s"', $param, $name));
                }

                return rawurlencode((string) $params[$param]);
            },
            $pattern
        ) ?? $pattern;
    }

    /**
     * Регистрирует набор маршрутов в карту named routes.
     *
     * @param array<string, array<string, mixed>> $routes
     */
    private function registerRoutes(array $routes, string $prefix = ''): void
    {
        foreach ($routes as $key => $route) {
            $name = $route['name'] ?? null;

            if (!is_string($name) || $name === '') {
                continue;
            }

            if (isset($this->namedRoutes[$name])) {
                throw new RuntimeException('Duplicate route name: ' . $name);
            }

            [$method, $path] = explode(' ', $key, 2);
            unset($method);

            $fullPath = $prefix . ($path === '/' ? '' : $path);
            $this->namedRoutes[$name] = $fullPath === '' ? '/' : $fullPath;
        }
    }
}
