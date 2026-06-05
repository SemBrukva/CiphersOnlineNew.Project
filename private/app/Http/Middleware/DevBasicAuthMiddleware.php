<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware принудительной Basic Auth для dev-окружения.
 */
final class DevBasicAuthMiddleware implements MiddlewareInterface
{
    /**
     * Проверяет Basic Auth для всех запросов в APP_ENV=dev.
     */
    public function process(Request $request, callable $next): Response
    {
        trigger_error('Dev Basic Auth middleware is active in dev environment: '.config('app.env'), E_USER_NOTICE);
        if ((string) config('app.env', 'production') !== 'dev') {
            return $next($request);
        }

        $expectedUser     = (string) config('app.dev_basic_auth.username', '');
        $expectedPassword = (string) config('app.dev_basic_auth.password', '');

        if ($expectedUser === '' || $expectedPassword === '') {
            return new Response('Dev Basic Auth is not configured.', 503, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        [$user, $password] = $this->credentials($request);

        if (
            $user !== null
            && $password !== null
            && hash_equals($expectedUser, $user)
            && hash_equals($expectedPassword, $password)
        ) {
            return $next($request);
        }

        return $this->challenge();
    }

    /**
     * Возвращает логин и пароль Basic Auth из серверных переменных или заголовков.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function credentials(Request $request): array
    {
        $user     = $request->server('PHP_AUTH_USER');
        $password = $request->server('PHP_AUTH_PW');

        if (is_string($user) && is_string($password)) {
            return [$user, $password];
        }

        $authorization = $request->header('Authorization')
            ?? $request->server('REDIRECT_HTTP_AUTHORIZATION')
            ?? $request->server('HTTP_AUTHORIZATION');

        if (!is_string($authorization)) {
            return [null, null];
        }

        return $this->credentialsFromAuthorizationHeader($authorization);
    }

    /**
     * Разбирает значение заголовка Authorization для схемы Basic.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function credentialsFromAuthorizationHeader(string $authorization): array
    {
        if (!preg_match('/^\s*Basic\s+(.+)\s*$/i', $authorization, $matches)) {
            return [null, null];
        }

        $decoded = base64_decode($matches[1], true);

        if ($decoded === false || !str_contains($decoded, ':')) {
            return [null, null];
        }

        [$user, $password] = explode(':', $decoded, 2);

        return [$user, $password];
    }

    /**
     * Возвращает ответ-запрос авторизации Basic Auth.
     */
    private function challenge(): Response
    {
        $realm = str_replace('"', '', (string) config('app.dev_basic_auth.realm', 'Dev Server'));

        return new Response('Unauthorized', 401, [
            'Content-Type'     => 'text/plain; charset=utf-8',
            'WWW-Authenticate' => 'Basic realm="' . $realm . '", charset="UTF-8"',
        ]);
    }
}
