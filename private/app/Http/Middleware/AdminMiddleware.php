<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware для защиты маршрутов панели администратора.
 *
 * Пропускает запрос, только если пользователь аутентифицирован
 * и его ID присутствует в списке ADMIN_IDS из .env.
 */
final readonly class AdminMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(private Auth $auth)
    {
    }

    /**
     * Проверяет права администратора; возвращает 302 или 403 при отказе.
     */
    public function process(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            return redirect(locale_url('/login'));
        }

        $adminIds = config('admin.ids', []);

        if (!in_array($this->auth->id(), $adminIds, true)) {
            return new Response('403 Forbidden', 403);
        }

        return $next($request);
    }
}
