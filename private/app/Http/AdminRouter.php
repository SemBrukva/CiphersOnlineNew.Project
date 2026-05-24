<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Маркерный подкласс Router для панели администратора.
 *
 * Позволяет регистрировать отдельный экземпляр роутера в контейнере
 * с маршрутами из config/admin_routes.php и административным префиксом пути.
 */
final readonly class AdminRouter extends Router
{
}
