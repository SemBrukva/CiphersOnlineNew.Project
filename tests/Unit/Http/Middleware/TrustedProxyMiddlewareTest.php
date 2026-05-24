<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\TrustedProxyMiddleware;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет поведение TrustedProxyMiddleware.
 */
final class TrustedProxyMiddlewareTest extends TestCase
{
    /**
     * Строит Request с заданными server-данными.
     *
     * @param array<string, string> $server
     */
    private function makeRequest(array $server = []): Request
    {
        return new Request(
            array_merge(['REMOTE_ADDR' => '1.2.3.4', 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], $server),
            [],
            [],
            [],
            []
        );
    }

    /** Финальный next-обработчик, возвращающий переданный ему запрос через замыкание. */
    private function captureRequest(?Request &$captured): callable
    {
        return static function (Request $request) use (&$captured): Response {
            $captured = $request;

            return new Response('ok');
        };
    }

    /**
     * Пустой список прокси — middleware пропускает запрос без изменений.
     */
    public function testPassesThroughWhenNoProxiesConfigured(): void
    {
        $middleware = new TrustedProxyMiddleware([]);
        $request    = $this->makeRequest(['HTTP_X_FORWARDED_FOR' => '9.9.9.9']);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('1.2.3.4', $captured->ip());
    }

    /**
     * Если REMOTE_ADDR не в списке доверенных — IP не разрешается из заголовков.
     */
    public function testPassesThroughWhenRemoteAddrNotTrusted(): void
    {
        $middleware = new TrustedProxyMiddleware(['10.0.0.1']);
        $request    = $this->makeRequest([
            'REMOTE_ADDR'          => '5.5.5.5',
            'HTTP_X_FORWARDED_FOR' => '9.9.9.9',
        ]);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('5.5.5.5', $captured->ip());
    }

    /**
     * Если REMOTE_ADDR совпадает с доверенным прокси — IP берётся из X-Forwarded-For.
     */
    public function testResolvesIpFromXForwardedFor(): void
    {
        $middleware = new TrustedProxyMiddleware(['1.2.3.4']);
        $request    = $this->makeRequest(['HTTP_X_FORWARDED_FOR' => '9.9.9.9, 10.0.0.1']);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('9.9.9.9', $captured->ip());
    }

    /**
     * IP берётся из X-Real-IP, если X-Forwarded-For отсутствует.
     */
    public function testResolvesIpFromXRealIp(): void
    {
        $middleware = new TrustedProxyMiddleware(['1.2.3.4']);
        $request    = $this->makeRequest(['HTTP_X_REAL_IP' => '8.8.8.8']);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('8.8.8.8', $captured->ip());
    }

    /**
     * remoteAddr() остаётся неизменным даже после разрешения IP.
     */
    public function testRemoteAddrRemainsUnchanged(): void
    {
        $middleware = new TrustedProxyMiddleware(['1.2.3.4']);
        $request    = $this->makeRequest(['HTTP_X_FORWARDED_FOR' => '9.9.9.9']);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('1.2.3.4', $captured->remoteAddr());
    }

    /**
     * Схема разрешается из X-Forwarded-Proto.
     */
    public function testResolvesSchemeFromXForwardedProto(): void
    {
        $middleware = new TrustedProxyMiddleware(['1.2.3.4']);
        $request    = $this->makeRequest(['HTTP_X_FORWARDED_PROTO' => 'https']);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertTrue($captured->isSecure());
    }

    /**
     * Схема разрешается из первого элемента списка в X-Forwarded-Proto.
     */
    public function testResolvesSchemeFromFirstProtoValue(): void
    {
        $middleware = new TrustedProxyMiddleware(['1.2.3.4']);
        $request    = $this->makeRequest(['HTTP_X_FORWARDED_PROTO' => 'https, http']);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertTrue($captured->isSecure());
    }

    /**
     * Хост разрешается из X-Forwarded-Host.
     */
    public function testResolvesHostFromXForwardedHost(): void
    {
        $middleware = new TrustedProxyMiddleware(['1.2.3.4']);
        $request    = $this->makeRequest(['HTTP_X_FORWARDED_HOST' => 'public.example.com']);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('public.example.com', $captured->host());
    }

    /**
     * Символ '*' означает доверять всем прокси.
     */
    public function testWildcardTrustsAnyRemoteAddr(): void
    {
        $middleware = new TrustedProxyMiddleware(['*']);
        $request    = $this->makeRequest([
            'REMOTE_ADDR'          => '185.12.34.56',
            'HTTP_X_FORWARDED_FOR' => '9.9.9.9',
        ]);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('9.9.9.9', $captured->ip());
    }

    /**
     * CIDR-диапазон 10.0.0.0/8 включает адрес 10.10.20.30.
     */
    public function testCidrMatchesIpInRange(): void
    {
        $middleware = new TrustedProxyMiddleware(['10.0.0.0/8']);
        $request    = $this->makeRequest([
            'REMOTE_ADDR'          => '10.10.20.30',
            'HTTP_X_FORWARDED_FOR' => '5.6.7.8',
        ]);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('5.6.7.8', $captured->ip());
    }

    /**
     * CIDR-диапазон 10.0.0.0/8 не включает адрес 192.168.1.1.
     */
    public function testCidrDoesNotMatchIpOutsideRange(): void
    {
        $middleware = new TrustedProxyMiddleware(['10.0.0.0/8']);
        $request    = $this->makeRequest([
            'REMOTE_ADDR'          => '192.168.1.1',
            'HTTP_X_FORWARDED_FOR' => '5.6.7.8',
        ]);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('192.168.1.1', $captured->ip());
    }

    /**
     * /32 CIDR совпадает только с точным IP.
     */
    public function testCidrSlash32MatchesExactIp(): void
    {
        $middleware = new TrustedProxyMiddleware(['1.2.3.4/32']);
        $request    = $this->makeRequest(['HTTP_X_FORWARDED_FOR' => '9.9.9.9']);

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        self::assertSame('9.9.9.9', $captured->ip());
    }

    /**
     * Если заголовков пересылки нет — IP, схема и хост остаются null.
     */
    public function testNoForwardedHeadersLeavesRequestUnchanged(): void
    {
        $middleware = new TrustedProxyMiddleware(['1.2.3.4']);
        $request    = $this->makeRequest();

        $captured = null;
        $middleware->process($request, $this->captureRequest($captured));

        // resolved IP отсутствует → ip() возвращает REMOTE_ADDR
        self::assertSame('1.2.3.4', $captured->ip());
        self::assertFalse($captured->isSecure());
    }
}
