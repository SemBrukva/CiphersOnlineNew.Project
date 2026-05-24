<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Cache\CacheInterface;
use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\RequestContext;
use App\Http\Response;

/**
 * Middleware ограничения частоты запросов по IP для чувствительных endpoint-ов.
 *
 * Правила берутся из конфигурации rate_limit.rules.
 */
final readonly class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт middleware rate limit.
     */
    public function __construct(
        private CacheInterface $cache,
        private RequestContext $context
    ) {
    }

    /**
     * Проверяет лимит запроса и возвращает 429 при превышении.
     */
    public function process(Request $request, callable $next): Response
    {
        $rule = $this->resolveRule($request);

        if ($rule === null) {
            return $next($request);
        }

        $window   = max(1, $rule['window_seconds']);
        $limit    = max(1, $rule['max_attempts']);
        $clientIp = $request->ip() ?: 'unknown';
        $key      = 'rate_limit:' . $rule['name'] . ':' . md5($clientIp);
        $attempts = (int) $this->cache->get($key, 0);

        if ($attempts >= $limit) {
            $requestId = $this->context->requestId;

            if (str_starts_with($request->path(), '/api')) {
                return Response::json([
                    'error' => [
                        'code' => 'too_many_requests',
                        'message' => 'Too Many Requests',
                        'request_id' => $requestId,
                    ],
                ], 429);
            }

            return new Response('Too Many Requests', 429, [
                'Retry-After' => (string) $window,
            ]);
        }

        $this->cache->set($key, $attempts + 1, $window);

        return $next($request);
    }

    /**
     * Возвращает правило лимита для текущего запроса или null, если оно не найдено.
     *
     * @return array{name:string,method:string,path:string,max_attempts:int,window_seconds:int}|null
     */
    private function resolveRule(Request $request): ?array
    {
        $rules = config('rate_limit.rules', []);
        $path  = $request->path();
        $verb  = strtoupper($request->getMethod());

        foreach ($rules as $name => $rule) {
            $ruleMethod = strtoupper((string) ($rule['method'] ?? 'GET'));
            $rulePath   = (string) ($rule['path'] ?? '');

            if ($ruleMethod === $verb && $rulePath === $path) {
                $rule['name'] = (string) $name;

                return $rule;
            }
        }

        return null;
    }
}
