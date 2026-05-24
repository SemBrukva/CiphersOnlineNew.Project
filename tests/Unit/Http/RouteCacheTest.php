<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Middleware\AdminMiddleware;
use App\Http\RouteCache;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет компиляцию и загрузку кеша маршрутов.
 */
final class RouteCacheTest extends TestCase
{
    /**
     * Проверяет, что compile() корректно собирает web/admin/api маршруты.
     */
    public function testCompileBuildsPrefixedRoutesAndAdminMiddleware(): void
    {
        $compiled = RouteCache::compile(
            ['GET /' => ['controller' => 'Home', 'method' => 'index']],
            ['GET /dashboard' => ['controller' => 'Admin', 'method' => 'index']],
            ['GET /users' => ['controller' => 'Api', 'method' => 'users']],
            '/admin'
        );

        self::assertArrayHasKey('GET /', $compiled['web']);
        self::assertArrayHasKey('GET /admin/dashboard', $compiled['admin']);
        self::assertArrayHasKey('GET /api/users', $compiled['api']);
        self::assertSame(
            AdminMiddleware::class,
            $compiled['admin']['GET /admin/dashboard']['middleware'][0]
        );
    }

    /**
     * Проверяет, что dump() и load() сохраняют и читают структуру маршрутов без потерь.
     */
    public function testDumpAndLoadKeepCompiledRoutes(): void
    {
        $cacheFile = sys_get_temp_dir() . '/routes-cache-test-' . bin2hex(random_bytes(4)) . '.php';
        $routes = [
            'web' => ['GET /' => ['controller' => 'Home', 'method' => 'index']],
            'admin' => ['GET /admin' => ['controller' => 'Admin', 'method' => 'index', 'middleware' => [AdminMiddleware::class]]],
            'api' => ['GET /api/ping' => ['controller' => 'Api', 'method' => 'ping']],
        ];

        RouteCache::dump($cacheFile, $routes);
        $loaded = RouteCache::load($cacheFile);

        self::assertSame($routes, $loaded);

        unlink($cacheFile);
    }
}
