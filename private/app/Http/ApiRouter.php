<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Маркерный подкласс Router для обработки запросов к API.
 *
 * Позволяет регистрировать отдельный экземпляр роутера в контейнере
 * с маршрутами из config/api_routes.php и префиксом /api.
 */
final readonly class ApiRouter extends Router
{
}
