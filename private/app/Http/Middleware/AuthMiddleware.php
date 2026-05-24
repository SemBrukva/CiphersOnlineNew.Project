<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware для защиты маршрутов от неавторизованного доступа.
 *
 * Перенаправляет на страницу входа, если пользователь не аутентифицирован.
 * Используется как per-route middleware в config/routes.php.
 */
final readonly class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(private Auth $auth)
    {
    }

    /**
     * Проверяет аутентификацию и либо пропускает запрос, либо редиректит на /login.
     */
    public function process(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            return redirect(locale_url('/login'));
        }

        return $next($request);
    }
}
