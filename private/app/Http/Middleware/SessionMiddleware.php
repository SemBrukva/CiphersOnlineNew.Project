<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;

/**
 * Глобальный middleware для запуска HTTP-сессии.
 *
 * Должен быть первым в глобальном стеке middleware,
 * чтобы сессия была доступна для всех последующих слоёв.
 */
final readonly class SessionMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(private Session $session)
    {
    }

    /**
     * Запускает сессию перед передачей управления следующему обработчику.
     */
    public function process(Request $request, callable $next): Response
    {
        $this->session->start();

        return $next($request);
    }
}
