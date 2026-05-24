<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Устраняет дублирование URL с завершающим слешем.
 *
 * Если путь запроса заканчивается на «/» (кроме корневого «/»),
 * выполняет 301-редирект на тот же URL без хвостового слеша.
 * Должен стоять первым в глобальном стеке middleware.
 */
final readonly class TrailingSlashMiddleware implements MiddlewareInterface
{
    /**
     * Перенаправляет URL с хвостовым слешем на канонический вид.
     */
    public function process(Request $request, callable $next): Response
    {
        $uri  = $request->getUri();
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        if ($path !== '/' && str_ends_with($path, '/')) {
            $query    = parse_url($uri, PHP_URL_QUERY);
            $canonical = rtrim($path, '/') . ($query !== null ? '?' . $query : '');

            return new Response('', 301, ['Location' => $canonical]);
        }

        return $next($request);
    }
}
