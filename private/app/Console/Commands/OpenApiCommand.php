<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Http\RouteCache;
use App\Http\RouteLoader;
use App\OpenApi\OpenApiGenerator;

/**
 * Генерирует OpenAPI 3.0 спецификацию из API-маршрутов и PHP-атрибутов контроллеров.
 *
 * Использование: php bin/console openapi:generate [путь/к/openapi.json]
 */
final readonly class OpenApiCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(
        private RouteLoader $routeLoader,
        private OpenApiGenerator $generator,
    ) {
    }

    /**
     * Генерирует спецификацию и записывает JSON-файл.
     *
     * @param string[] $args args[0] — путь к выходному файлу (необязателен).
     */
    public function handle(array $args): int
    {
        $outputPath = $args[0] ?? PUBLIC_PATH . '/openapi.json';

        $merged = $this->routeLoader->loadMerged(
            config('routes', []),
            config('admin_routes', []),
            config('api_routes', [])
        );

        $compiled = RouteCache::compile(
            $merged['web'],
            $merged['admin'],
            $merged['api'],
            (string) config('admin.path', '/admin')
        );

        $spec = $this->generator->generate($compiled['api']);

        $json = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            echo 'Ошибка сериализации спецификации в JSON.' . PHP_EOL;
            return 1;
        }

        $dir = dirname($outputPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $json . PHP_EOL);

        echo 'OpenAPI spec → ' . $outputPath . PHP_EOL;
        echo 'Paths: ' . count($spec['paths']) . PHP_EOL;

        return 0;
    }
}
