<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\Exception\ForbiddenException;
use App\Http\Exception\UnauthorizedException;
use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware для защиты административных API-маршрутов.
 *
 * Выбрасывает 401 если пользователь не аутентифицирован,
 * и 403 если пользователь не является администратором.
 */
final readonly class ApiAdminMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(
        private Auth $auth
    ) {
    }

    /**
     * Проверяет права администратора и выбрасывает исключение при отказе.
     */
    public function process(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            throw new UnauthorizedException();
        }

        $adminIds = config('admin.ids', []);

        if (!in_array($this->auth->id(), $adminIds, true)) {
            throw new ForbiddenException();
        }

        return $next($request);
    }
}
