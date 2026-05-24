<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware CORS для API: отвечает на preflight и добавляет CORS-заголовки.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Обрабатывает CORS-заголовки и preflight-запросы.
     */
    public function process(Request $request, callable $next): Response
    {
        $origin = $request->header('Origin');
        $originValue = is_string($origin) ? trim($origin) : '';
        $config = config('cors', []);

        if ($originValue !== '' && $this->isOriginAllowed($originValue, $config['allowed_origins'] ?? ['*'])) {
            $this->sendCorsHeaders($originValue, $config);
        }

        $preflightMethod = $request->header('Access-Control-Request-Method');
        if ($request->getMethod() === 'OPTIONS' && is_string($preflightMethod) && $preflightMethod !== '') {
            return new Response('', 204, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return $next($request);
    }

    /**
     * Проверяет, разрешён ли Origin по конфигурации.
     *
     * @param mixed $allowedOrigins
     */
    private function isOriginAllowed(string $origin, mixed $allowedOrigins): bool
    {
        if (!is_array($allowedOrigins)) {
            return false;
        }

        if (in_array('*', $allowedOrigins, true)) {
            return true;
        }

        return in_array($origin, $allowedOrigins, true);
    }

    /**
     * Устанавливает CORS-заголовки для ответа.
     *
     * @param array<string, mixed> $config
     */
    private function sendCorsHeaders(string $origin, array $config): void
    {
        $allowedOrigins = $config['allowed_origins'] ?? ['*'];
        $allowAny = is_array($allowedOrigins) && in_array('*', $allowedOrigins, true);
        $allowCredentials = (bool) ($config['allow_credentials'] ?? false);

        $allowOrigin = $allowAny && !$allowCredentials ? '*' : $origin;
        header('Access-Control-Allow-Origin: ' . $allowOrigin);
        header('Vary: Origin');

        $methods = $config['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        if (is_array($methods) && $methods !== []) {
            header('Access-Control-Allow-Methods: ' . implode(', ', $methods));
        }

        $headers = $config['allowed_headers'] ?? ['Content-Type', 'Authorization'];
        if (is_array($headers) && $headers !== []) {
            header('Access-Control-Allow-Headers: ' . implode(', ', $headers));
        }

        $exposedHeaders = $config['exposed_headers'] ?? [];
        if (is_array($exposedHeaders) && $exposedHeaders !== []) {
            header('Access-Control-Expose-Headers: ' . implode(', ', $exposedHeaders));
        }

        if ($allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        $maxAge = (int) ($config['max_age'] ?? 600);
        if ($maxAge > 0) {
            header('Access-Control-Max-Age: ' . (string) $maxAge);
        }
    }
}
