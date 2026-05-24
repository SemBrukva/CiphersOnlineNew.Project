<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Attribute\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Загружает маршруты из атрибутов контроллеров и объединяет их с конфигом.
 */
final readonly class RouteLoader
{
    /**
     * @param string $controllerPath      Путь к директории контроллеров.
     * @param string $controllerNamespace Базовый namespace контроллеров.
     */
    public function __construct(
        private string $controllerPath = __DIR__ . '/../Controller',
        private string $controllerNamespace = 'App\\Controller'
    ) {
    }

    /**
     * Возвращает маршруты, объявленные через #[Route].
     *
     * @return array{web: array<string, array<string, mixed>>, admin: array<string, array<string, mixed>>, api: array<string, array<string, mixed>>}
     */
    public function loadFromAttributes(): array
    {
        $routes = [
            'web' => [],
            'admin' => [],
            'api' => [],
        ];

        if (!is_dir($this->controllerPath)) {
            return $routes;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->controllerPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->resolveClassName($file->getPathname());

            if (!class_exists($className)) {
                require_once $file->getPathname();
            }

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract()) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(Route::class) as $attribute) {
                    /** @var Route $routeAttribute */
                    $routeAttribute = $attribute->newInstance();
                    $group = $this->detectGroup($className, $routeAttribute->group);
                    $routeKey = $this->buildRouteKey($routeAttribute->method, $routeAttribute->path);

                    if (isset($routes[$group][$routeKey])) {
                        throw new RuntimeException(sprintf(
                            'Duplicate route key "%s" in %s group (attribute).',
                            $routeKey,
                            $group
                        ));
                    }

                    $route = [
                        'controller' => $className,
                        'method' => $method->getName(),
                        'source' => 'attribute',
                    ];

                    if ($routeAttribute->name !== null && $routeAttribute->name !== '') {
                        $route['name'] = $routeAttribute->name;
                    }

                    if ($routeAttribute->middleware !== []) {
                        $route['middleware'] = $routeAttribute->middleware;
                    }

                    $routes[$group][$routeKey] = $route;
                }
            }
        }

        return $routes;
    }

    /**
     * Объединяет конфиг-маршруты и маршруты из атрибутов.
     *
     * @param array<string, array<string, mixed>> $webRoutes
     * @param array<string, array<string, mixed>> $adminRoutes
     * @param array<string, array<string, mixed>> $apiRoutes
     * @return array{web: array<string, array<string, mixed>>, admin: array<string, array<string, mixed>>, api: array<string, array<string, mixed>>}
     */
    public function loadMerged(array $webRoutes, array $adminRoutes, array $apiRoutes): array
    {
        $attributes = $this->loadFromAttributes();
        $merged = [
            'web' => $this->mergeGroupRoutes($webRoutes, $attributes['web'], 'web'),
            'admin' => $this->mergeGroupRoutes($adminRoutes, $attributes['admin'], 'admin'),
            'api' => $this->mergeGroupRoutes($apiRoutes, $attributes['api'], 'api'),
        ];

        $this->assertUniqueNames($merged);

        return $merged;
    }

    /**
     * Объединяет маршруты одной группы с проверкой дублей route-key.
     *
     * @param array<string, array<string, mixed>> $configRoutes
     * @param array<string, array<string, mixed>> $attributeRoutes
     * @return array<string, array<string, mixed>>
     */
    private function mergeGroupRoutes(array $configRoutes, array $attributeRoutes, string $group): array
    {
        foreach ($attributeRoutes as $key => $route) {
            if (isset($configRoutes[$key])) {
                throw new RuntimeException(sprintf(
                    'Duplicate route key "%s" in %s group (config + attribute).',
                    $key,
                    $group
                ));
            }

            $configRoutes[$key] = $route;
        }

        return $configRoutes;
    }

    /**
     * Проверяет уникальность именованных маршрутов.
     *
     * @param array{web: array<string, array<string, mixed>>, admin: array<string, array<string, mixed>>, api: array<string, array<string, mixed>>} $routes
     */
    private function assertUniqueNames(array $routes): void
    {
        $names = [];

        foreach ($routes as $group => $groupRoutes) {
            foreach ($groupRoutes as $key => $route) {
                $name = $route['name'] ?? null;

                if (!is_string($name) || $name === '') {
                    continue;
                }

                if (isset($names[$name])) {
                    throw new RuntimeException(sprintf(
                        'Duplicate route name "%s" (%s and %s).',
                        $name,
                        $names[$name],
                        $group . ':' . $key
                    ));
                }

                $names[$name] = $group . ':' . $key;
            }
        }
    }

    /**
     * Определяет группу маршрута: web/admin/api.
     */
    private function detectGroup(string $className, ?string $explicitGroup): string
    {
        if ($explicitGroup !== null) {
            $normalized = strtolower($explicitGroup);
            if (in_array($normalized, ['web', 'admin', 'api'], true)) {
                return $normalized;
            }

            throw new RuntimeException('Unsupported route group in attribute: ' . $explicitGroup);
        }

        if (str_contains($className, '\\Admin\\')) {
            return 'admin';
        }

        if (str_contains($className, '\\Api\\')) {
            return 'api';
        }

        return 'web';
    }

    /**
     * Формирует route-key формата "METHOD /path".
     */
    private function buildRouteKey(string $method, string $path): string
    {
        $method = strtoupper(trim($method));
        $path = '/' . ltrim(trim($path), '/');

        return $method . ' ' . $path;
    }

    /**
     * Преобразует абсолютный путь файла в FQCN контроллера.
     */
    private function resolveClassName(string $filePath): string
    {
        $relative = substr($filePath, strlen(rtrim($this->controllerPath, '/\\')) + 1);
        $relativeWithoutExt = preg_replace('/\.php$/', '', $relative) ?? $relative;
        $suffix = str_replace(['/', '\\'], '\\', $relativeWithoutExt);

        return rtrim($this->controllerNamespace, '\\') . '\\' . ltrim($suffix, '\\');
    }
}
