<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Controller\Api\GuestController;
use App\Http\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-маршрута шифра Бифид.
 */
final class BifidApiRouteTest extends TestCase
{
    /**
     * Проверяет, что маршрут Bifid зарегистрирован в конфигурации API.
     */
    public function testBifidApiRouteIsRegistered(): void
    {
        $routes = require PRIVATE_PATH . '/config/api_routes.php';

        self::assertArrayHasKey('POST /tools/bifid', $routes);
        self::assertSame(GuestController::class, $routes['POST /tools/bifid']['controller']);
        self::assertSame('bifid', $routes['POST /tools/bifid']['method']);
        self::assertSame([RateLimitMiddleware::class], $routes['POST /tools/bifid']['middleware']);
        self::assertSame('api.tools.bifid', $routes['POST /tools/bifid']['name']);
    }
}
