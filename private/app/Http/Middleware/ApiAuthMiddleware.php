<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\Exception\UnauthorizedException;
use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware для защиты API-маршрутов от неавторизованных запросов.
 *
 * В отличие от AuthMiddleware, не делает редирект,
 * а выбрасывает UnauthorizedException для JSON-обработчика API.
 */
final readonly class ApiAuthMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(
        private Auth $auth
    ) {
    }

    /**
     * Проверяет аутентификацию и выбрасывает 401-исключение при отказе.
     */
    public function process(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            throw new UnauthorizedException();
        }

        return $next($request);
    }
}
