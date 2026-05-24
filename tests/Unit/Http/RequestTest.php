<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет методы Request, связанные с IP-адресом, схемой и хостом.
 */
final class RequestTest extends TestCase
{
    /**
     * ip() возвращает REMOTE_ADDR, если resolved IP не задан.
     */
    public function testIpReturnsFallbackToRemoteAddr(): void
    {
        $request = new Request(['REMOTE_ADDR' => '1.2.3.4'], [], [], [], []);

        self::assertSame('1.2.3.4', $request->ip());
    }

    /**
     * ip() игнорирует X-Forwarded-For без resolved IP.
     */
    public function testIpIgnoresXForwardedForWithoutResolvedIp(): void
    {
        $request = new Request([
            'REMOTE_ADDR'          => '1.2.3.4',
            'HTTP_X_FORWARDED_FOR' => '9.9.9.9',
        ], [], [], [], []);

        self::assertSame('1.2.3.4', $request->ip());
    }

    /**
     * ip() возвращает resolved IP, установленный через withTrustedData().
     */
    public function testIpReturnsResolvedIpWhenSet(): void
    {
        $request = new Request(['REMOTE_ADDR' => '10.0.0.1'], [], [], [], []);
        $request = $request->withTrustedData('5.5.5.5', null, null);

        self::assertSame('5.5.5.5', $request->ip());
    }

    /**
     * remoteAddr() всегда возвращает REMOTE_ADDR, не обращая внимания на resolved IP.
     */
    public function testRemoteAddrReturnsRawValue(): void
    {
        $request = new Request(['REMOTE_ADDR' => '10.0.0.1'], [], [], [], []);
        $request = $request->withTrustedData('5.5.5.5', null, null);

        self::assertSame('10.0.0.1', $request->remoteAddr());
    }

    /**
     * isSecure() возвращает false, если схема не установлена и HTTPS не задан.
     */
    public function testIsSecureReturnsFalseByDefault(): void
    {
        $request = new Request(['REQUEST_SCHEME' => 'http'], [], [], [], []);

        self::assertFalse($request->isSecure());
    }

    /**
     * isSecure() возвращает true при REQUEST_SCHEME = https в server-данных.
     */
    public function testIsSecureWithServerRequestScheme(): void
    {
        $request = new Request(['REQUEST_SCHEME' => 'https'], [], [], [], []);

        self::assertTrue($request->isSecure());
    }

    /**
     * isSecure() возвращает true при HTTPS = on в server-данных.
     */
    public function testIsSecureWithServerHttpsOn(): void
    {
        $request = new Request(['HTTPS' => 'on'], [], [], [], []);

        self::assertTrue($request->isSecure());
    }

    /**
     * isSecure() возвращает true при HTTPS = 1 в server-данных.
     */
    public function testIsSecureWithServerHttpsFlag(): void
    {
        $request = new Request(['HTTPS' => '1'], [], [], [], []);

        self::assertTrue($request->isSecure());
    }

    /**
     * isSecure() использует resolved схему, а не server-переменные.
     */
    public function testIsSecureWithResolvedHttpsScheme(): void
    {
        $request = new Request(['REQUEST_SCHEME' => 'http'], [], [], [], []);
        $request = $request->withTrustedData(null, 'https', null);

        self::assertTrue($request->isSecure());
    }

    /**
     * isSecure() возвращает false при resolved схеме http, даже если HTTPS задан в server.
     */
    public function testIsSecureWithResolvedHttpSchemeOverridesServer(): void
    {
        $request = new Request(['HTTPS' => 'on'], [], [], [], []);
        $request = $request->withTrustedData(null, 'http', null);

        self::assertFalse($request->isSecure());
    }

    /**
     * host() возвращает HTTP_HOST, если resolved хост не задан.
     */
    public function testHostReturnsFallbackToHttpHost(): void
    {
        $request = new Request(['HTTP_HOST' => 'example.com'], [], [], [], []);

        self::assertSame('example.com', $request->host());
    }

    /**
     * host() возвращает resolved хост, установленный через withTrustedData().
     */
    public function testHostReturnsResolvedHost(): void
    {
        $request = new Request(['HTTP_HOST' => 'internal.local'], [], [], [], []);
        $request = $request->withTrustedData(null, null, 'public.example.com');

        self::assertSame('public.example.com', $request->host());
    }

    /**
     * withTrustedData() возвращает новый экземпляр (immutability).
     */
    public function testWithTrustedDataReturnsNewInstance(): void
    {
        $original = new Request(['REMOTE_ADDR' => '10.0.0.1'], [], [], [], []);
        $resolved = $original->withTrustedData('5.5.5.5', 'https', 'public.example.com');

        self::assertNotSame($original, $resolved);
        self::assertSame('10.0.0.1', $original->ip());
        self::assertSame('5.5.5.5', $resolved->ip());
    }

    /**
     * withTrustedData() с null-значениями сохраняет предыдущие resolved данные.
     */
    public function testWithTrustedDataNullPreservesExisting(): void
    {
        $request = new Request(['REMOTE_ADDR' => '10.0.0.1'], [], [], [], []);
        $request = $request->withTrustedData('5.5.5.5', 'https', 'public.example.com');
        $request = $request->withTrustedData(null, null, null);

        self::assertSame('5.5.5.5', $request->ip());
        self::assertTrue($request->isSecure());
        self::assertSame('public.example.com', $request->host());
    }

    /**
     * withUri() сохраняет resolved данные при смене URI.
     */
    public function testWithUriPreservesResolvedData(): void
    {
        $request = new Request(
            ['REMOTE_ADDR' => '10.0.0.1', 'REQUEST_URI' => '/old'],
            [],
            [],
            [],
            []
        );
        $request = $request->withTrustedData('5.5.5.5', 'https', 'example.com');
        $request = $request->withUri('/new');

        self::assertSame('5.5.5.5', $request->ip());
        self::assertTrue($request->isSecure());
        self::assertSame('example.com', $request->host());
    }

    /**
     * withRouteParams() сохраняет resolved данные.
     */
    public function testWithRouteParamsPreservesResolvedData(): void
    {
        $request = new Request(['REMOTE_ADDR' => '10.0.0.1'], [], [], [], []);
        $request = $request->withTrustedData('5.5.5.5', 'https', 'example.com');
        $request = $request->withRouteParams(['id' => '42']);

        self::assertSame('5.5.5.5', $request->ip());
        self::assertTrue($request->isSecure());
    }
}
