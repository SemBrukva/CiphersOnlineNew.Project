<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware разрешения реального IP-адреса, схемы и хоста клиента через доверенные прокси.
 *
 * Должен стоять первым в глобальном стеке middleware.
 * Только если REMOTE_ADDR входит в список доверенных прокси — читаются заголовки
 * X-Forwarded-For, X-Forwarded-Proto, X-Forwarded-Host и результат записывается в запрос.
 */
final readonly class TrustedProxyMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт экземпляр middleware.
     *
     * @param string[] $proxies Список доверенных прокси: IP, CIDR или '*' (доверять всем).
     */
    public function __construct(private array $proxies)
    {
    }

    /**
     * Разрешает реальные данные клиента из заголовков, если прокси доверенный.
     */
    public function process(Request $request, callable $next): Response
    {
        if ($this->proxies === []) {
            return $next($request);
        }

        if (!$this->isTrusted($request->remoteAddr())) {
            return $next($request);
        }

        $request = $request->withTrustedData(
            $this->resolveIp($request),
            $this->resolveScheme($request),
            $this->resolveHost($request),
        );

        return $next($request);
    }

    /**
     * Проверяет, входит ли IP в список доверенных прокси.
     */
    private function isTrusted(string $ip): bool
    {
        foreach ($this->proxies as $proxy) {
            if ($proxy === '*') {
                return true;
            }

            if (str_contains($proxy, '/')) {
                if ($this->matchCidr($ip, $proxy)) {
                    return true;
                }
            } elseif ($ip === $proxy) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет, попадает ли IPv4-адрес в CIDR-диапазон.
     */
    private function matchCidr(string $ip, string $cidr): bool
    {
        [$subnet, $prefix] = explode('/', $cidr, 2);

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $prefixLen = (int) $prefix;

        if ($prefixLen === 0) {
            return true;
        }

        $mask = ~0 << (32 - $prefixLen);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    /**
     * Извлекает реальный IP клиента из X-Forwarded-For или X-Real-IP.
     */
    private function resolveIp(Request $request): ?string
    {
        $forwarded = $request->header('X-Forwarded-For');

        if (is_string($forwarded) && $forwarded !== '') {
            $ip = trim(explode(',', $forwarded)[0]);

            if ($ip !== '') {
                return $ip;
            }
        }

        $realIp = $request->header('X-Real-IP');

        if (is_string($realIp) && $realIp !== '') {
            return trim($realIp);
        }

        return null;
    }

    /**
     * Извлекает схему запроса из X-Forwarded-Proto.
     */
    private function resolveScheme(Request $request): ?string
    {
        $proto = $request->header('X-Forwarded-Proto');

        if (is_string($proto) && $proto !== '') {
            return strtolower(trim(explode(',', $proto)[0]));
        }

        return null;
    }

    /**
     * Извлекает хост из X-Forwarded-Host.
     */
    private function resolveHost(Request $request): ?string
    {
        $host = $request->header('X-Forwarded-Host');

        if (is_string($host) && $host !== '') {
            return trim(explode(',', $host)[0]);
        }

        return null;
    }
}
