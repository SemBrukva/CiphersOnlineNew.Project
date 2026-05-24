<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;

/**
 * Middleware CSRF-защиты.
 *
 * Проверяет наличие и корректность токена при мутирующих запросах
 * (POST, PUT, PATCH, DELETE). Безопасные методы (GET, HEAD, OPTIONS)
 * пропускаются без проверки.
 *
 * Токен передаётся либо в теле запроса как `_csrf_token`,
 * либо в заголовке `X-CSRF-Token` (для AJAX).
 */
final readonly class CsrfMiddleware implements MiddlewareInterface
{
    private const array SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(private Session $session)
    {
    }

    /**
     * Проверяет CSRF-токен для мутирующих запросов.
     */
    public function process(Request $request, callable $next): Response
    {
        if (!in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            $token = $request->input('_csrf_token')
                ?? $request->header('X-CSRF-Token');

            if (!hash_equals($this->session->csrfToken(), (string) $token)) {
                return new Response('Недопустимый CSRF-токен.', 419);
            }
        }

        return $next($request);
    }
}
