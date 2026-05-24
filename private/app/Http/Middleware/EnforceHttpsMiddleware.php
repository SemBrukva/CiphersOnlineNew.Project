<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware принудительного перехода на HTTPS в production.
 *
 * Использует Request::isSecure() и Request::host() — с учётом данных,
 * разрешённых TrustedProxyMiddleware, если тот стоит раньше в стеке.
 */
final class EnforceHttpsMiddleware implements MiddlewareInterface
{
    /**
     * Выполняет redirect на HTTPS при включённой настройке force_https.
     */
    public function process(Request $request, callable $next): Response
    {
        $env        = (string) config('app.env', 'production');
        $forceHttps = (bool) config('app.force_https', false);

        if ($env !== 'production' || !$forceHttps || $request->isSecure()) {
            return $next($request);
        }

        $host = $request->host();

        if ($host === '') {
            return $next($request);
        }

        return new Response('', 301, [
            'Location' => 'https://' . $host . $request->getUri(),
        ]);
    }
}
