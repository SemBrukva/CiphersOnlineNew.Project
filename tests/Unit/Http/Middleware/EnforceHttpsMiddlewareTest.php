<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Config\Config;
use App\Http\Middleware\EnforceHttpsMiddleware;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет поведение EnforceHttpsMiddleware.
 */
final class EnforceHttpsMiddlewareTest extends TestCase
{
    /**
     * Проверяет redirect на HTTPS в production при включённом флаге.
     */
    public function testRedirectsToHttpsWhenEnabledInProduction(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'env'         => 'production',
                'force_https' => true,
            ],
        ]);

        $middleware = new EnforceHttpsMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/contacts?page=1',
            'HTTP_HOST'      => 'example.com',
            'REQUEST_SCHEME' => 'http',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('next'));

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('https://example.com/contacts?page=1', $response->getHeaders()['Location'] ?? null);
    }

    /**
     * Проверяет, что в local-режиме redirect не выполняется.
     */
    public function testDoesNotRedirectOutsideProduction(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'env'         => 'local',
                'force_https' => true,
            ],
        ]);

        $middleware = new EnforceHttpsMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/contacts',
            'HTTP_HOST'      => 'example.com',
            'REQUEST_SCHEME' => 'http',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok', 200));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $response->getContent());
    }

    /**
     * Если запрос уже HTTPS (через resolved схему) — redirect не выполняется.
     */
    public function testDoesNotRedirectWhenAlreadySecureViaResolvedScheme(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'env'         => 'production',
                'force_https' => true,
            ],
        ]);

        $middleware = new EnforceHttpsMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/page',
            'HTTP_HOST'      => 'example.com',
            'REQUEST_SCHEME' => 'http',
        ], [], [], [], []);
        $request = $request->withTrustedData(null, 'https', null);

        $response = $middleware->process($request, static fn (): Response => new Response('ok', 200));

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * Redirect использует хост, разрешённый TrustedProxyMiddleware.
     */
    public function testUsesResolvedHostInRedirectLocation(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'env'         => 'production',
                'force_https' => true,
            ],
        ]);

        $middleware = new EnforceHttpsMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/page',
            'HTTP_HOST'      => 'internal.proxy',
            'REQUEST_SCHEME' => 'http',
        ], [], [], [], []);
        $request = $request->withTrustedData(null, null, 'public.example.com');

        $response = $middleware->process($request, static fn (): Response => new Response('next'));

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('https://public.example.com/page', $response->getHeaders()['Location'] ?? null);
    }

    /**
     * Если force_https выключен — redirect не выполняется.
     */
    public function testDoesNotRedirectWhenForceHttpsDisabled(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'env'         => 'production',
                'force_https' => false,
            ],
        ]);

        $middleware = new EnforceHttpsMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/page',
            'HTTP_HOST'      => 'example.com',
            'REQUEST_SCHEME' => 'http',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok', 200));

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * Если HTTP_HOST отсутствует — redirect не выполняется (нельзя построить Location).
     */
    public function testDoesNotRedirectWhenHostIsEmpty(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'env'         => 'production',
                'force_https' => true,
            ],
        ]);

        $middleware = new EnforceHttpsMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/page',
            'REQUEST_SCHEME' => 'http',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok', 200));

        self::assertSame(200, $response->getStatusCode());
    }
}
