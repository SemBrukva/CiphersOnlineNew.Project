<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Controller\Api\GuestController;
use App\Http\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-маршрута шифра Autokey.
 */
final class AutokeyApiRouteTest extends TestCase
{
    /**
     * Проверяет, что маршрут Autokey зарегистрирован в конфигурации API.
     */
    public function testAutokeyApiRouteIsRegistered(): void
    {
        $routes = require PRIVATE_PATH . '/config/api_routes.php';

        self::assertArrayHasKey('POST /tools/autokey', $routes);
        self::assertSame(GuestController::class, $routes['POST /tools/autokey']['controller']);
        self::assertSame('autokey', $routes['POST /tools/autokey']['method']);
        self::assertSame([RateLimitMiddleware::class], $routes['POST /tools/autokey']['middleware']);
        self::assertSame('api.tools.autokey', $routes['POST /tools/autokey']['name']);
    }
}
