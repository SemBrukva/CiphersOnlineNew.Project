<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Cache\CacheInterface;
use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;
use App\Repository\RedirectRepository;

/**
 * Middleware обработки HTTP-редиректов из базы данных.
 *
 * При каждом GET/HEAD-запросе проверяет путь по таблице активных редиректов
 * (список кешируется). При совпадении — инкрементирует счётчик и отдаёт редирект.
 */
final readonly class RedirectMiddleware implements MiddlewareInterface
{
    public const string CACHE_KEY = 'redirects:active_map';
    public const int    CACHE_TTL = 3600;

    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(
        private RedirectRepository $redirects,
        private CacheInterface $cache
    ) {
    }

    /**
     * Проверяет путь запроса по таблице редиректов и выполняет редирект при совпадении.
     */
    public function process(Request $request, callable $next): Response
    {
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path     = $request->path();
        $redirect = $this->findRedirect($path);

        if ($redirect === null) {
            return $next($request);
        }

        $this->redirects->incrementHitCount((int) $redirect['id']);

        return new Response('', (int) $redirect['status_code'], [
            'Location' => $redirect['to_path'],
        ]);
    }

    /**
     * Ищет активный редирект для заданного пути. Результаты кешируются.
     *
     * @return array<string, mixed>|null
     */
    private function findRedirect(string $path): ?array
    {
        /** @var array<string, array<string, mixed>> $map */
        $map = $this->cache->tag('redirects')->remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $rows = $this->redirects->listActive();

            $map = [];
            foreach ($rows as $row) {
                $map[$row['from_path']] = $row;
            }

            return $map;
        });

        return $map[$path] ?? null;
    }
}
