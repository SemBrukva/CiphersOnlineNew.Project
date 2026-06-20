<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Controller\Api\GuestController;
use App\Http\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-маршрута шифра Трифид.
 */
final class TrifidApiRouteTest extends TestCase
{
    /**
     * Проверяет, что маршрут Trifid зарегистрирован в конфигурации API.
     */
    public function testTrifidApiRouteIsRegistered(): void
    {
        $routes = require PRIVATE_PATH . '/config/api_routes.php';

        self::assertArrayHasKey('POST /tools/trifid', $routes);
        self::assertSame(GuestController::class, $routes['POST /tools/trifid']['controller']);
        self::assertSame('trifid', $routes['POST /tools/trifid']['method']);
        self::assertSame([RateLimitMiddleware::class], $routes['POST /tools/trifid']['middleware']);
        self::assertSame('api.tools.trifid', $routes['POST /tools/trifid']['name']);
    }
}
